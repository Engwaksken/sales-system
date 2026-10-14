<?php

namespace App\Http\Controllers;

use App\Models\IotecCollection;
use App\Models\Order;
use App\Models\OrderPaymentReceipt;
use App\Models\PaymentGateway;
use App\Services\Iotec\IotecClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class IotecPaymentController extends Controller
{
    /**
     * Initiate a mobile-money collection (STK push) for an order.
     * Equivalent of the original iotec_collect.php, but standardized on the
     * client_id/client_secret gateway config (see IotecClient docblock).
     */
    public function collect(Request $request)
    {
        $validated = $request->validate([
            'order_id' => 'required|string',
            'amount' => 'required|numeric|min:1',
            'phone' => 'required|string',
            'network' => 'required|string|in:MTN,AIRTEL,mtn,airtel',
        ]);

        $orderRef = trim($validated['order_id']);
        $amount = (float) $validated['amount'];
        $network = strtoupper($validated['network']);
        $phone = $this->sanitizePhone($validated['phone']);

        if (strlen($phone) < 10) {
            return response()->json(['status' => 'failed', 'msg' => 'Invalid phone number format.'], 422);
        }

        $order = Order::where('order_number', $orderRef)->orWhere('order_id', $orderRef)->first();

        try {
            $gateway = PaymentGateway::where('gateway_code', 'iotec')->where('is_active', true)->firstOrFail();
            $client = new IotecClient($gateway);

            $reference = $order?->order_number ?: $orderRef;
            $result = $client->initiateCollection(
                phone: $phone,
                amount: $amount,
                network: $network,
                reference: $reference,
                description: "Order {$orderRef} payment",
            );

            IotecCollection::create([
                'order_id' => $orderRef,
                'phone' => $phone,
                'network' => $network,
                'amount' => $amount,
                'iotec_ref' => $result['reference'],
                'status' => $result['status'],
                'raw_response' => json_encode($result['raw']),
            ]);

            if ($order) {
                $order->update([
                    'gateway_id' => $gateway->id,
                    'gateway_code' => 'iotec',
                    'gateway_reference' => $result['reference'],
                    'gateway_status' => strtoupper($result['status']),
                    'gateway_message' => $result['message'],
                    'mm_phone' => $phone,
                    'mm_network' => $network,
                    'payment_status' => $result['status'] === 'failed' ? 'failed' : 'pending',
                ]);
            }

            return response()->json([
                'status' => $result['status'],
                'msg' => $result['status'] === 'pending'
                    ? 'Payment prompt sent. Please approve on your phone.'
                    : ($result['message'] ?: 'Payment request failed.'),
                'ref' => $result['reference'],
            ]);
        } catch (Throwable $e) {
            Log::error('[Iotec] collect failed: '.$e->getMessage());

            IotecCollection::create([
                'order_id' => $orderRef,
                'phone' => $phone,
                'network' => $network,
                'amount' => $amount,
                'status' => 'failed',
                'raw_response' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => 'failed',
                'msg' => 'Could not connect to payment gateway. Try again.',
            ], 502);
        }
    }

    /**
     * Public webhook the IOTEC gateway calls when a collection's status changes.
     * Equivalent of the original iotec_webhook.php. Must stay outside auth/CSRF.
     */
    public function webhook(Request $request)
    {
        $data = $request->all();
        Log::channel('single')->info('[Iotec webhook] '.json_encode($data));

        $externalId = $data['externalId'] ?? null;
        $transactionId = $data['id'] ?? null;
        $status = $data['status'] ?? 'pending';

        if (! $externalId) {
            return response('Missing externalId', 400);
        }

        $paymentStatus = IotecClient::normalizeWebhookStatus($status);

        $order = Order::where('order_number', $externalId)->orWhere('order_id', $externalId)->first();

        if ($order) {
            $order->update([
                'gateway_reference' => $transactionId,
                'gateway_status' => $status,
                'payment_status' => $paymentStatus,
            ]);

            if ($paymentStatus === 'paid') {
                OrderPaymentReceipt::create([
                    'order_id' => $order->id,
                    'order_number' => $order->order_number ?? $order->order_id,
                    'receipt_number' => 'RCT-'.now()->format('Ymd').'-'.Str::upper(Str::random(6)),
                    'customer_id' => $order->customer_id,
                    'customer_name' => $order->customer_name,
                    'phone' => $order->phone,
                    'email' => $order->email,
                    'payment_method' => 'Mobile Money (IOTEC)',
                    'gateway_code' => 'iotec',
                    'gateway_reference' => $transactionId,
                    'gateway_status' => $status,
                    'amount_paid' => $order->total_amount,
                    'currency' => 'UGX',
                ]);
            }
        }

        return response('OK', 200);
    }

    /**
     * Poll IOTEC for the current status of an order's payment.
     * Equivalent of the original check_payment_status.php (IOTEC branch only —
     * the original also stubbed a 'yopayments' gateway that was never implemented).
     */
    public function status(Request $request)
    {
        $orderNumber = trim((string) $request->input('order_number', $request->input('order_id', '')));

        if ($orderNumber === '') {
            return response()->json(['status' => 'error', 'msg' => 'Order number is required.'], 400);
        }

        $order = Order::where('order_number', $orderNumber)->first();
        if (! $order) {
            return response()->json(['status' => 'error', 'msg' => 'Order not found.'], 404);
        }

        if (! $order->gateway_code) {
            return response()->json(['status' => 'error', 'msg' => 'This order has no payment gateway assigned.'], 422);
        }

        if (! $order->gateway_reference) {
            return response()->json([
                'status' => 'success',
                'msg' => 'Payment has not been initiated yet.',
                'order_number' => $orderNumber,
                'gateway_code' => $order->gateway_code,
                'gateway_status' => $order->gateway_status,
                'gateway_message' => $order->gateway_message ?? 'Awaiting payment initiation.',
                'payment_status' => $order->payment_status ?? 'pending',
            ]);
        }

        if ($order->gateway_code !== 'iotec') {
            return response()->json(['status' => 'error', 'msg' => 'Gateway status checker not implemented for this gateway.'], 501);
        }

        try {
            $gateway = PaymentGateway::where('gateway_code', 'iotec')->where('is_active', true)->firstOrFail();
            $client = new IotecClient($gateway);
            $result = $client->checkStatus($order->gateway_reference);

            $paymentStatus = IotecClient::mapPaymentStatus($result['gateway_status']);

            $order->update([
                'gateway_reference' => $result['reference'] ?? $order->gateway_reference,
                'gateway_status' => $result['gateway_status'],
                'gateway_message' => $result['message'],
                'payment_status' => $paymentStatus,
            ]);

            return response()->json([
                'status' => 'success',
                'msg' => 'Payment status checked successfully.',
                'order_number' => $orderNumber,
                'gateway_code' => 'iotec',
                'gateway_reference' => $result['reference'],
                'gateway_status' => $result['gateway_status'],
                'gateway_message' => $result['message'],
                'payment_status' => $paymentStatus,
                'response' => $result['raw'],
            ]);
        } catch (Throwable $e) {
            Log::error('[check_payment_status] '.$e->getMessage());

            return response()->json([
                'status' => 'error',
                'msg' => 'An unexpected error occurred while checking payment status.',
            ], 500);
        }
    }

    protected function sanitizePhone(string $raw): string
    {
        $digits = preg_replace('/\D/', '', $raw);

        if (str_starts_with($digits, '0') && strlen($digits) === 10) {
            $digits = '256'.substr($digits, 1);
        }

        return $digits;
    }
}
