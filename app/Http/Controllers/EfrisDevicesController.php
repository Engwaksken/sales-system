<?php

namespace App\Http\Controllers;

use App\Models\EfrisDevice;
use App\Models\Branch;
use App\Models\EfrisConfig;
use App\Support\ExportsCsv;
use Illuminate\Http\Request;

class EfrisDevicesController extends Controller
{
    use ExportsCsv;

    public function index(Request $request)
    {
        $query = EfrisDevice::query();
        if ($request->filled('period') && $request->period !== 'all') {
            $query = $this->applyPeriod($query, $request->period, 'created_at');
        }
        $query = $this->applySearch($query, (string) $request->query('q', ''), ['device_serial_no', 'device_type', 'device_name', 'activation_code']);
        $efris_devices = $query->latest()->paginate($this->perPage($request));
        $periodStats = $this->periodStats(EfrisDevice::class, 'created_at');
        $config_options = EfrisConfig::orderBy('id')->get();
        $branch_options = Branch::orderBy('id')->get();
        return view('efris_devices.index', ['items' => $efris_devices, 'config_options' => $config_options, 'branch_options' => $branch_options, 'periodStats' => $periodStats]);
    }

    public function exportCsv(Request $request)
    {
        $allColumns = ['config_id', 'branch_id', 'device_serial_no', 'device_type', 'device_name', 'activation_code', 'activation_status', 'activated_at', 'invoice_counter', 'notes', 'created_by', 'updated_by', 'created_at', 'updated_at'];
        $columns = $request->query('columns', $allColumns);
        $columns = array_values(array_intersect($allColumns, $columns));
        if (empty($columns)) { $columns = $allColumns; }
        $query = EfrisDevice::query();
        if ($request->filled('period') && $request->period !== 'all') {
            $query = $this->applyPeriod($query, $request->period, 'created_at');
        }
        $query = $this->applySearch($query, (string) $request->query('q', ''), ['device_serial_no', 'device_type', 'device_name', 'activation_code']);
        $rows = $query->with(['config', 'branch'])->get();
        $data = $rows->map(function ($row) use ($columns) {
            $out = [];
            foreach ($columns as $col) {
                if ($col === 'config_id') { $out[] = $row->config?->id ?? $row->config_id; continue; }
                if ($col === 'branch_id') { $out[] = $row->branch?->name ?? $row->branch_id; continue; }
                $out[] = $row->{$col};
            }
            return $out;
        });
        $headers = array_map(fn ($c) => str($c)->replace('_id', '')->replace('_', ' ')->title()->toString(), $columns);
        return $this->csvResponse('efris_devices-'.now()->format('Ymd').'.csv', $headers, $data);
    }

    public function exportPdf(Request $request)
    {
        $allColumns = ['config_id', 'branch_id', 'device_serial_no', 'device_type', 'device_name', 'activation_code', 'activation_status', 'activated_at', 'invoice_counter', 'notes', 'created_by', 'updated_by', 'created_at', 'updated_at'];
        $columns = $request->query('columns', $allColumns);
        $columns = array_values(array_intersect($allColumns, $columns));
        if (empty($columns)) { $columns = $allColumns; }
        $query = EfrisDevice::query();
        if ($request->filled('period') && $request->period !== 'all') {
            $query = $this->applyPeriod($query, $request->period, 'created_at');
        }
        $query = $this->applySearch($query, (string) $request->query('q', ''), ['device_serial_no', 'device_type', 'device_name', 'activation_code']);
        $rows = $query->with(['config', 'branch'])->get();
        $data = $rows->map(function ($row) use ($columns) {
            $out = [];
            foreach ($columns as $col) {
                if ($col === 'config_id') { $out[] = $row->config?->id ?? $row->config_id; continue; }
                if ($col === 'branch_id') { $out[] = $row->branch?->name ?? $row->branch_id; continue; }
                $out[] = $row->{$col};
            }
            return $out;
        });
        $headers = array_map(fn ($c) => str($c)->replace('_id', '')->replace('_', ' ')->title()->toString(), $columns);
        $options = new \Dompdf\Options();
        $options->set('isRemoteEnabled', true);
        $dompdf = new \Dompdf\Dompdf($options);
        $dompdf->loadHtml(view('partials.export-pdf', ['title' => 'EfrisDevices', 'headers' => $headers, 'rows' => $data, 'generatedBy' => auth()->user()?->name ?? 'System'])->render());
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();
        return $dompdf->stream('efris_devices-'.now()->format('Ymd').'.pdf');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'config_id' => 'required|exists:efris_config,id',
            'branch_id' => 'required|exists:branches,id',
            'device_serial_no' => 'required',
            'device_type' => 'nullable',
            'device_name' => 'nullable',
            'activation_code' => 'nullable',
            'activation_status' => 'required|in:pending,activated,suspended',
            'activated_at' => 'nullable',
            'invoice_counter' => 'required|numeric',
            'notes' => 'nullable',
        ]);
        EfrisDevice::create($validated);
        return redirect()->route('efris_devices.index')->with('status', 'EfrisDevice created successfully.');
    }

    public function show(EfrisDevice $efris_device)
    {
        return view('efris_devices.show', ['efris_device' => $efris_device]);
    }

    public function update(Request $request, EfrisDevice $efris_device)
    {
        $validated = $request->validate([
            'config_id' => 'sometimes|required|exists:efris_config,id',
            'branch_id' => 'sometimes|required|exists:branches,id',
            'device_serial_no' => 'sometimes|required',
            'device_type' => 'nullable',
            'device_name' => 'nullable',
            'activation_code' => 'nullable',
            'activation_status' => 'sometimes|required|in:pending,activated,suspended',
            'activated_at' => 'nullable',
            'invoice_counter' => 'sometimes|required|numeric',
            'notes' => 'nullable',
        ]);
        $efris_device->update($validated);
        return redirect()->route('efris_devices.index')->with('status', 'EfrisDevice updated successfully.');
    }

    public function destroy(EfrisDevice $efris_device)
    {
        $efris_device->delete();
        return redirect()->route('efris_devices.index')->with('status', 'EfrisDevice deleted.');
    }
}
