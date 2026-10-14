<?php

namespace App\Http\Controllers;

use App\Models\DeliveryZone;
use App\Support\ExportsCsv;
use Illuminate\Http\Request;

class DeliveryZonesController extends Controller
{
    use ExportsCsv;

    public function index(Request $request)
    {
        $query = DeliveryZone::query();
        if ($request->filled('period') && $request->period !== 'all') {
            $query = $this->applyPeriod($query, $request->period, 'created_at');
        }
        $query = $this->applySearch($query, (string) $request->query('q', ''), ['zone_name', 'description']);
        $delivery_zones = $query->latest()->paginate($this->perPage($request));
        $stat1 = DeliveryZone::where('is_active', true)->count();
        $periodStats = $this->periodStats(DeliveryZone::class, 'created_at');
        return view('delivery_zones.index', ['items' => $delivery_zones, 'stat1' => $stat1, 'periodStats' => $periodStats]);
    }

    public function exportCsv(Request $request)
    {
        $allColumns = ['zone_name', 'fee', 'description', 'is_active', 'created_by', 'updated_by', 'created_at', 'updated_at'];
        $columns = $request->query('columns', $allColumns);
        $columns = array_values(array_intersect($allColumns, $columns));
        if (empty($columns)) { $columns = $allColumns; }
        $query = DeliveryZone::query();
        if ($request->filled('period') && $request->period !== 'all') {
            $query = $this->applyPeriod($query, $request->period, 'created_at');
        }
        $query = $this->applySearch($query, (string) $request->query('q', ''), ['zone_name', 'description']);
        $rows = $query->get();
        $data = $rows->map(function ($row) use ($columns) {
            $out = [];
            foreach ($columns as $col) {
                $out[] = $row->{$col};
            }
            return $out;
        });
        $headers = array_map(fn ($c) => str($c)->replace('_id', '')->replace('_', ' ')->title()->toString(), $columns);
        return $this->csvResponse('delivery_zones-'.now()->format('Ymd').'.csv', $headers, $data);
    }

    public function exportPdf(Request $request)
    {
        $allColumns = ['zone_name', 'fee', 'description', 'is_active', 'created_by', 'updated_by', 'created_at', 'updated_at'];
        $columns = $request->query('columns', $allColumns);
        $columns = array_values(array_intersect($allColumns, $columns));
        if (empty($columns)) { $columns = $allColumns; }
        $query = DeliveryZone::query();
        if ($request->filled('period') && $request->period !== 'all') {
            $query = $this->applyPeriod($query, $request->period, 'created_at');
        }
        $query = $this->applySearch($query, (string) $request->query('q', ''), ['zone_name', 'description']);
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
        $dompdf->loadHtml(view('partials.export-pdf', ['title' => 'DeliveryZones', 'headers' => $headers, 'rows' => $data, 'generatedBy' => auth()->user()?->name ?? 'System'])->render());
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();
        return $dompdf->stream('delivery_zones-'.now()->format('Ymd').'.pdf');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'zone_name' => 'required',
            'fee' => 'required|numeric',
            'description' => 'nullable',
            'is_active' => 'boolean',
        ]);
        DeliveryZone::create($validated);
        return redirect()->route('delivery_zones.index')->with('status', 'DeliveryZone created successfully.');
    }

    public function show(DeliveryZone $delivery_zone)
    {
        return view('delivery_zones.show', ['delivery_zone' => $delivery_zone]);
    }

    public function update(Request $request, DeliveryZone $delivery_zone)
    {
        $validated = $request->validate([
            'zone_name' => 'sometimes|required',
            'fee' => 'sometimes|required|numeric',
            'description' => 'nullable',
            'is_active' => 'boolean',
        ]);
        $delivery_zone->update($validated);
        return redirect()->route('delivery_zones.index')->with('status', 'DeliveryZone updated successfully.');
    }

    public function destroy(DeliveryZone $delivery_zone)
    {
        $delivery_zone->delete();
        return redirect()->route('delivery_zones.index')->with('status', 'DeliveryZone deleted.');
    }
}
