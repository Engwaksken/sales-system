<?php

namespace App\Http\Controllers;

use App\Models\IotecCollection;
use App\Support\ExportsCsv;
use Illuminate\Http\Request;

class IotecCollectionsController extends Controller
{
    use ExportsCsv;

    public function index(Request $request)
    {
        $query = IotecCollection::query();
        if ($request->filled('period') && $request->period !== 'all') {
            $query = $this->applyPeriod($query, $request->period, 'created_at');
        }
        $query = $this->applySearch($query, (string) $request->query('q', ''), ['order_id', 'phone', 'network', 'iotec_ref']);
        $iotec_collections = $query->latest()->paginate($this->perPage($request));
        $stat1 = 'UGX '.number_format(IotecCollection::sum('amount'), 0);
        $periodStats = $this->periodStats(IotecCollection::class, 'created_at', 'amount');
        return view('iotec_collections.index', ['items' => $iotec_collections, 'stat1' => $stat1, 'periodStats' => $periodStats]);
    }

    public function exportCsv(Request $request)
    {
        $allColumns = ['order_id', 'phone', 'network', 'amount', 'iotec_ref', 'status', 'raw_response', 'created_by', 'updated_by', 'created_at', 'updated_at'];
        $columns = $request->query('columns', $allColumns);
        $columns = array_values(array_intersect($allColumns, $columns));
        if (empty($columns)) { $columns = $allColumns; }
        $query = IotecCollection::query();
        if ($request->filled('period') && $request->period !== 'all') {
            $query = $this->applyPeriod($query, $request->period, 'created_at');
        }
        $query = $this->applySearch($query, (string) $request->query('q', ''), ['order_id', 'phone', 'network', 'iotec_ref']);
        $rows = $query->get();
        $data = $rows->map(function ($row) use ($columns) {
            $out = [];
            foreach ($columns as $col) {
                $out[] = $row->{$col};
            }
            return $out;
        });
        $headers = array_map(fn ($c) => str($c)->replace('_id', '')->replace('_', ' ')->title()->toString(), $columns);
        return $this->csvResponse('iotec_collections-'.now()->format('Ymd').'.csv', $headers, $data);
    }

    public function exportPdf(Request $request)
    {
        $allColumns = ['order_id', 'phone', 'network', 'amount', 'iotec_ref', 'status', 'raw_response', 'created_by', 'updated_by', 'created_at', 'updated_at'];
        $columns = $request->query('columns', $allColumns);
        $columns = array_values(array_intersect($allColumns, $columns));
        if (empty($columns)) { $columns = $allColumns; }
        $query = IotecCollection::query();
        if ($request->filled('period') && $request->period !== 'all') {
            $query = $this->applyPeriod($query, $request->period, 'created_at');
        }
        $query = $this->applySearch($query, (string) $request->query('q', ''), ['order_id', 'phone', 'network', 'iotec_ref']);
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
        $dompdf->loadHtml(view('partials.export-pdf', ['title' => 'IotecCollections', 'headers' => $headers, 'rows' => $data, 'generatedBy' => auth()->user()?->name ?? 'System'])->render());
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();
        return $dompdf->stream('iotec_collections-'.now()->format('Ymd').'.pdf');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'order_id' => 'required',
            'phone' => 'required',
            'network' => 'required',
            'amount' => 'required|numeric',
            'iotec_ref' => 'nullable',
            'status' => 'required',
            'raw_response' => 'nullable',
        ]);
        IotecCollection::create($validated);
        return redirect()->route('iotec_collections.index')->with('status', 'IotecCollection created successfully.');
    }

    public function show(IotecCollection $iotec_collection)
    {
        return view('iotec_collections.show', ['iotec_collection' => $iotec_collection]);
    }

    public function update(Request $request, IotecCollection $iotec_collection)
    {
        $validated = $request->validate([
            'order_id' => 'sometimes|required',
            'phone' => 'sometimes|required',
            'network' => 'sometimes|required',
            'amount' => 'sometimes|required|numeric',
            'iotec_ref' => 'nullable',
            'status' => 'sometimes|required',
            'raw_response' => 'nullable',
        ]);
        $iotec_collection->update($validated);
        return redirect()->route('iotec_collections.index')->with('status', 'IotecCollection updated successfully.');
    }

    public function destroy(IotecCollection $iotec_collection)
    {
        $iotec_collection->delete();
        return redirect()->route('iotec_collections.index')->with('status', 'IotecCollection deleted.');
    }
}
