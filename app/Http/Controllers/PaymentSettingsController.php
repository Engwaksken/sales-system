<?php

namespace App\Http\Controllers;

use App\Models\PaymentSetting;
use App\Support\ExportsCsv;
use Illuminate\Http\Request;

class PaymentSettingsController extends Controller
{
    use ExportsCsv;

    public function index(Request $request)
    {
        $query = PaymentSetting::query();
        if ($request->filled('period') && $request->period !== 'all') {
            $query = $this->applyPeriod($query, $request->period, 'created_at');
        }
        $query = $this->applySearch($query, (string) $request->query('q', ''), ['setting_key', 'setting_value']);
        $payment_settings = $query->latest()->paginate($this->perPage($request));
        $periodStats = $this->periodStats(PaymentSetting::class, 'created_at');
        return view('payment_settings.index', ['items' => $payment_settings, 'periodStats' => $periodStats]);
    }

    public function exportCsv(Request $request)
    {
        $allColumns = ['setting_key', 'setting_value', 'created_by', 'updated_by', 'created_at', 'updated_at'];
        $columns = $request->query('columns', $allColumns);
        $columns = array_values(array_intersect($allColumns, $columns));
        if (empty($columns)) { $columns = $allColumns; }
        $query = PaymentSetting::query();
        if ($request->filled('period') && $request->period !== 'all') {
            $query = $this->applyPeriod($query, $request->period, 'created_at');
        }
        $query = $this->applySearch($query, (string) $request->query('q', ''), ['setting_key', 'setting_value']);
        $rows = $query->get();
        $data = $rows->map(function ($row) use ($columns) {
            $out = [];
            foreach ($columns as $col) {
                $out[] = $row->{$col};
            }
            return $out;
        });
        $headers = array_map(fn ($c) => str($c)->replace('_id', '')->replace('_', ' ')->title()->toString(), $columns);
        return $this->csvResponse('payment_settings-'.now()->format('Ymd').'.csv', $headers, $data);
    }

    public function exportPdf(Request $request)
    {
        $allColumns = ['setting_key', 'setting_value', 'created_by', 'updated_by', 'created_at', 'updated_at'];
        $columns = $request->query('columns', $allColumns);
        $columns = array_values(array_intersect($allColumns, $columns));
        if (empty($columns)) { $columns = $allColumns; }
        $query = PaymentSetting::query();
        if ($request->filled('period') && $request->period !== 'all') {
            $query = $this->applyPeriod($query, $request->period, 'created_at');
        }
        $query = $this->applySearch($query, (string) $request->query('q', ''), ['setting_key', 'setting_value']);
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
        $dompdf->loadHtml(view('partials.export-pdf', ['title' => 'PaymentSettings', 'headers' => $headers, 'rows' => $data, 'generatedBy' => auth()->user()?->name ?? 'System'])->render());
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();
        return $dompdf->stream('payment_settings-'.now()->format('Ymd').'.pdf');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'setting_key' => 'required',
            'setting_value' => 'required',
        ]);
        PaymentSetting::create($validated);
        return redirect()->route('payment_settings.index')->with('status', 'PaymentSetting created successfully.');
    }

    public function show(PaymentSetting $payment_setting)
    {
        return view('payment_settings.show', ['payment_setting' => $payment_setting]);
    }

    public function update(Request $request, PaymentSetting $payment_setting)
    {
        $validated = $request->validate([
            'setting_key' => 'sometimes|required',
            'setting_value' => 'sometimes|required',
        ]);
        $payment_setting->update($validated);
        return redirect()->route('payment_settings.index')->with('status', 'PaymentSetting updated successfully.');
    }

    public function destroy(PaymentSetting $payment_setting)
    {
        $payment_setting->delete();
        return redirect()->route('payment_settings.index')->with('status', 'PaymentSetting deleted.');
    }
}
