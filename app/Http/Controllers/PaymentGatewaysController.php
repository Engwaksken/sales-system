<?php

namespace App\Http\Controllers;

use App\Models\PaymentGateway;
use App\Support\ExportsCsv;
use Illuminate\Http\Request;

class PaymentGatewaysController extends Controller
{
    use ExportsCsv;

    public function index(Request $request)
    {
        $query = PaymentGateway::query();
        if ($request->filled('period') && $request->period !== 'all') {
            $query = $this->applyPeriod($query, $request->period, 'created_at');
        }
        $query = $this->applySearch($query, (string) $request->query('q', ''), ['gateway_name', 'gateway_code', 'display_name', 'description']);
        $payment_gateways = $query->latest()->paginate($this->perPage($request));
        $stat1 = PaymentGateway::where('is_active', true)->count();
        $periodStats = $this->periodStats(PaymentGateway::class, 'created_at');
        return view('payment_gateways.index', ['items' => $payment_gateways, 'stat1' => $stat1, 'periodStats' => $periodStats]);
    }

    public function exportCsv(Request $request)
    {
        $allColumns = ['gateway_name', 'gateway_code', 'display_name', 'provider_type', 'description', 'is_active', 'is_default', 'sandbox_mode', 'token_url', 'collect_url', 'base_url', 'status_url', 'client_id', 'client_secret', 'wallet_guid', 'callback_url', 'return_url', 'supports_collection', 'supports_disbursement', 'supports_mtn', 'supports_airtel', 'created_by', 'updated_by', 'created_at', 'updated_at'];
        $columns = $request->query('columns', $allColumns);
        $columns = array_values(array_intersect($allColumns, $columns));
        if (empty($columns)) { $columns = $allColumns; }
        $query = PaymentGateway::query();
        if ($request->filled('period') && $request->period !== 'all') {
            $query = $this->applyPeriod($query, $request->period, 'created_at');
        }
        $query = $this->applySearch($query, (string) $request->query('q', ''), ['gateway_name', 'gateway_code', 'display_name', 'description']);
        $rows = $query->get();
        $data = $rows->map(function ($row) use ($columns) {
            $out = [];
            foreach ($columns as $col) {
                $out[] = $row->{$col};
            }
            return $out;
        });
        $headers = array_map(fn ($c) => str($c)->replace('_id', '')->replace('_', ' ')->title()->toString(), $columns);
        return $this->csvResponse('payment_gateways-'.now()->format('Ymd').'.csv', $headers, $data);
    }

    public function exportPdf(Request $request)
    {
        $allColumns = ['gateway_name', 'gateway_code', 'display_name', 'provider_type', 'description', 'is_active', 'is_default', 'sandbox_mode', 'token_url', 'collect_url', 'base_url', 'status_url', 'client_id', 'client_secret', 'wallet_guid', 'callback_url', 'return_url', 'supports_collection', 'supports_disbursement', 'supports_mtn', 'supports_airtel', 'created_by', 'updated_by', 'created_at', 'updated_at'];
        $columns = $request->query('columns', $allColumns);
        $columns = array_values(array_intersect($allColumns, $columns));
        if (empty($columns)) { $columns = $allColumns; }
        $query = PaymentGateway::query();
        if ($request->filled('period') && $request->period !== 'all') {
            $query = $this->applyPeriod($query, $request->period, 'created_at');
        }
        $query = $this->applySearch($query, (string) $request->query('q', ''), ['gateway_name', 'gateway_code', 'display_name', 'description']);
        $rows = $query->get();
        $data = $rows->map(function ($row) use ($columns) {
            $out = [];
            foreach ($columns as $col) {
                $out[] = $row->{$col};
            }
            return $out;
        });
        $headers = array_map(fn ($c) => str($c)->replace('_id', '')->replace('_', ' ')->title()->toString(), $columns);
        $options = new \Dompdf\Options();
        $options->set('isRemoteEnabled', true);
        $dompdf = new \Dompdf\Dompdf($options);
        $dompdf->loadHtml(view('partials.export-pdf', ['title' => 'PaymentGateways', 'headers' => $headers, 'rows' => $data, 'generatedBy' => auth()->user()?->name ?? 'System'])->render());
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();
        return $dompdf->stream('payment_gateways-'.now()->format('Ymd').'.pdf');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'gateway_name' => 'required',
            'gateway_code' => 'required',
            'display_name' => 'required',
            'provider_type' => 'required|in:mobile_money,card,bank,wallet,mixed',
            'description' => 'nullable',
            'is_active' => 'boolean',
            'is_default' => 'boolean',
            'sandbox_mode' => 'boolean',
            'token_url' => 'nullable',
            'collect_url' => 'nullable',
            'base_url' => 'nullable',
            'status_url' => 'nullable',
            'client_id' => 'nullable',
            'client_secret' => 'nullable',
            'wallet_guid' => 'nullable',
            'callback_url' => 'nullable',
            'return_url' => 'nullable',
            'supports_collection' => 'boolean',
            'supports_disbursement' => 'boolean',
            'supports_mtn' => 'boolean',
            'supports_airtel' => 'boolean',
        ]);
        PaymentGateway::create($validated);
        return redirect()->route('payment_gateways.index')->with('status', 'PaymentGateway created successfully.');
    }

    public function show(PaymentGateway $payment_gateway)
    {
        return view('payment_gateways.show', ['payment_gateway' => $payment_gateway]);
    }

    public function update(Request $request, PaymentGateway $payment_gateway)
    {
        $validated = $request->validate([
            'gateway_name' => 'sometimes|required',
            'gateway_code' => 'sometimes|required',
            'display_name' => 'sometimes|required',
            'provider_type' => 'sometimes|required|in:mobile_money,card,bank,wallet,mixed',
            'description' => 'nullable',
            'is_active' => 'boolean',
            'is_default' => 'boolean',
            'sandbox_mode' => 'boolean',
            'token_url' => 'nullable',
            'collect_url' => 'nullable',
            'base_url' => 'nullable',
            'status_url' => 'nullable',
            'client_id' => 'nullable',
            'client_secret' => 'nullable',
            'wallet_guid' => 'nullable',
            'callback_url' => 'nullable',
            'return_url' => 'nullable',
            'supports_collection' => 'boolean',
            'supports_disbursement' => 'boolean',
            'supports_mtn' => 'boolean',
            'supports_airtel' => 'boolean',
        ]);
        $payment_gateway->update($validated);
        return redirect()->route('payment_gateways.index')->with('status', 'PaymentGateway updated successfully.');
    }

    public function destroy(PaymentGateway $payment_gateway)
    {
        $payment_gateway->delete();
        return redirect()->route('payment_gateways.index')->with('status', 'PaymentGateway deleted.');
    }
}
