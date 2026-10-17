<?php

namespace App\Http\Controllers;

use App\Models\EfrisApiLog;
use App\Models\EfrisConfig;
use App\Models\EfrisDevice;
use App\Models\EfrisInvoice;
use App\Models\Sale;
use App\Services\Efris\EfrisClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Throwable;

class EfrisInvoiceController extends Controller
{
    public function index()
    {
        $items = EfrisInvoice::with(['sale', 'device'])->latest()->paginate($this->perPage($request));

        return view('efris_invoices.index', ['items' => $items]);
    }

    public function show(EfrisInvoice $efris_invoice)
    {
        return view('efris_invoices.show', ['efris_invoice' => $efris_invoice]);
    }

    /**
     * Submit a sale to EFRIS as a T103 invoice.
     * Equivalent of the original process_efris_invoice.php.
     */
    public function submit(Request $request, Sale $sale)
    {
        $userId = Auth::id();

        try {
            $configId = (int) $request->input('config_id', 0);
            $invoiceType = trim((string) $request->input('invoice_type', '1'));
            $remarks = trim((string) $request->input('remarks', 'Sales invoice generated from POS'));

            $config = $configId > 0
                ? EfrisConfig::find($configId)
                : EfrisConfig::where('is_active', true)->latest('id')->first();

            if (! $config) {
                return $this->fail('No EFRIS configuration found. Create and activate one first.');
            }

            $device = EfrisDevice::where('config_id', $config->id)
                ->whereIn('activation_status', ['activated', 'pending'])
                ->orderByRaw("activation_status = 'activated' DESC")
                ->orderBy('id')
                ->first();

            if (! $device) {
                return $this->fail('No EFRIS device found for the selected configuration.');
            }

            $privatePem = (string) $config->private_key_pem; // decrypted automatically via 'encrypted' cast
            $uraPublicPem = trim((string) $config->ura_public_key_pem);
            $tin = trim((string) $config->tin);

            if ($privatePem === '') {
                return $this->fail('RSA private key is missing in EFRIS configuration.');
            }
            if ($uraPublicPem === '') {
                return $this->fail('URA public key is missing in EFRIS configuration.');
            }
            if ($tin === '') {
                return $this->fail('TIN is missing in EFRIS configuration.');
            }

            $sale->loadMissing(['customer', 'sale_items.product']);
            $items = $sale->sale_items;

            if ($items->isEmpty()) {
                return $this->fail('This sale has no sale items.');
            }

            $invoiceNo = trim((string) $request->input('invoice_no', ''));
            if ($invoiceNo === '') {
                // Fiscalise under the sale's own number so EFRIS and the printed invoice match.
                $invoiceNo = (string) ($sale->invoice_no ?: \App\Support\DocumentNumber::invoice());
            }

            $issuedAt = $sale->sale_date ?? $sale->created_at ?? now();
            $customer = $sale->customer;

            $buyer = [
                'buyerTin' => '',
                'buyerNinBrn' => '',
                'buyerPassport' => '',
                'buyerLcNo' => '',
                'buyerName' => $customer->name ?? 'Walk-in Customer',
                'buyerLegalName' => $customer->name ?? 'Walk-in Customer',
                'buyerEmail' => $customer->email ?? '',
                'buyerMobilePhone' => $customer->phone ?? '',
                'buyerAddress' => $customer->address ?? '',
            ];

            $goodsDetails = [];
            $netAmount = 0.0;
            $taxAmount = 0.0;
            $grossAmount = 0.0;
            $vatRate = $config->vat_registered ? 0.18 : 0.0;

            foreach ($items as $idx => $item) {
                $qty = round((float) $item->quantity, 4);
                $unitPrice = round((float) $item->price, 2);
                $lineGross = round((float) ($item->total ?? ($qty * $unitPrice)), 2);

                $lineNet = $vatRate > 0 ? round($lineGross / (1 + $vatRate), 2) : $lineGross;
                $lineTax = round($lineGross - $lineNet, 2);

                $netAmount += $lineNet;
                $taxAmount += $lineTax;
                $grossAmount += $lineGross;

                $goodsDetails[] = [
                    'item' => (string) ($idx + 1),
                    'itemCode' => (string) ($item->product->sku ?? $item->product_id ?? ('ITEM'.($idx + 1))),
                    'itemName' => (string) ($item->product->name ?? ('Item '.($idx + 1))),
                    'qty' => $qty,
                    'unitPrice' => $unitPrice,
                    'total' => $lineGross,
                    'discountTotal' => 0,
                    'taxRate' => $vatRate * 100,
                    'tax' => $lineTax,
                    'orderNumber' => (string) ($idx + 1),
                ];
            }

            $netAmount = round($netAmount, 2);
            $taxAmount = round($taxAmount, 2);
            $grossAmount = round($grossAmount, 2);

            $t103Body = [
                'invoiceNo' => $invoiceNo,
                'invoiceType' => $invoiceType,
                'invoiceKind' => '1',
                'issuedDate' => \Illuminate\Support\Carbon::parse($issuedAt)->format('Y-m-d H:i:s'),
                'currency' => 'UGX',
                'oriInvoiceNo' => '',
                'remarks' => $remarks,
                'sellerDetails' => [
                    'tin' => $tin,
                    'legalName' => (string) $config->taxpayer_name,
                    'businessName' => (string) $config->taxpayer_name,
                    'address' => (string) $config->taxpayer_address,
                    'emailAddress' => (string) $config->taxpayer_email,
                    'mobilePhone' => (string) $config->taxpayer_phone,
                ],
                'buyerDetails' => $buyer,
                'payWay' => '101',
                'operationType' => '101',
                'invoiceIndustryCode' => '101',
                'isBatch' => '0',
                'summary' => [
                    'netAmount' => $netAmount,
                    'taxAmount' => $taxAmount,
                    'grossAmount' => $grossAmount,
                    'itemCount' => count($goodsDetails),
                    'modeCode' => $config->vat_registered ? 'V' : 'N',
                ],
                'goodsDetails' => $goodsDetails,
            ];

            $client = new EfrisClient(
                tin: $tin,
                deviceSerial: (string) $device->device_serial_no,
                privatePem: $privatePem,
                uraPublicPem: $uraPublicPem,
                environment: (string) $config->environment,
            );

            $requestedAt = now();
            [$httpCode, $resp, $responseBody, $requestJson, $latencyMs] = $client->submitInvoice($t103Body);

            $retCode = (string) ($resp['returnStateInfo']['returnCode'] ?? '');
            $retMsg = (string) ($resp['returnStateInfo']['returnMessage'] ?? '');
            $isSuccess = $httpCode === 200 && $retCode === '00';

            $log = EfrisApiLog::create([
                'config_id' => $config->id,
                'operation_code' => 'T103',
                'operation_name' => 'Invoice Reporting',
                'http_method' => 'POST',
                'endpoint_url' => $client->baseUrl(),
                'reference_type' => 'sale',
                'reference_id' => $sale->id,
                'request_body' => substr($requestJson, 0, 65000),
                'response_body' => substr($responseBody, 0, 65000),
                'http_status_code' => $httpCode,
                'efris_return_code' => $retCode,
                'efris_return_msg' => substr($retMsg, 0, 490),
                'is_success' => $isSuccess,
                'latency_ms' => $latencyMs,
                'requested_at' => $requestedAt,
                'responded_at' => now(),
                'created_by' => $userId,
            ]);

            if (! $isSuccess) {
                return $this->fail(
                    $retMsg !== '' ? $retMsg : "HTTP {$httpCode} - T103 failed",
                    422,
                    [
                        'hint' => EfrisClient::hintForCode($retCode),
                        'sale_id' => $sale->id,
                        'invoice_no' => $invoiceNo,
                        'log_id' => $log->id,
                        'efris_code' => $retCode,
                        'raw_snippet' => substr($responseBody, 0, 400),
                    ]
                );
            }

            $invoice = EfrisInvoice::create([
                'sale_id' => $sale->id,
                'config_id' => $config->id,
                'device_id' => $device->id,
                'invoice_no' => (string) ($resp['basicInformation']['invoiceNo'] ?? $invoiceNo),
                'tin' => $tin,
                'issued_at' => \Illuminate\Support\Carbon::parse($issuedAt),
                'gross_amount' => $grossAmount,
                'status' => 'submitted',
                'qr_code' => (string) ($resp['summary']['qrCode'] ?? ''),
                'verification_code' => (string) ($resp['summary']['verificationCode'] ?? ''),
                'request_json' => substr($requestJson, 0, 65000),
                'response_json' => substr($responseBody, 0, 65000),
                'created_by' => $userId,
            ]);

            $device->increment('invoice_counter');

            return redirect()
                ->route('efris_invoices.show', $invoice->id)
                ->with('status', "Invoice {$invoice->invoice_no} submitted to EFRIS successfully.");
        } catch (Throwable $e) {
            report($e);

            return $this->fail($e->getMessage());
        }
    }

    protected function fail(string $message, int $status = 422, array $extra = [])
    {
        if (request()->wantsJson()) {
            return response()->json(array_merge(['success' => false, 'error' => $message], $extra), $status);
        }

        return back()->withErrors(['efris' => $message]);
    }
}
