<?php

namespace App\Http\Controllers;

use App\Models\OrderLog;
use App\Models\Order;
use App\Support\ExportsCsv;
use Illuminate\Http\Request;

class OrderLogsController extends Controller
{
    use ExportsCsv;

    public function index(Request $request)
    {
        $query = OrderLog::query();
        if ($request->filled('period') && $request->period !== 'all') {
            $query = $this->applyPeriod($query, $request->period, 'created_at');
        }
        $query = $this->applySearch($query, (string) $request->query('q', ''), ['action', 'description']);
        $order_logs = $query->latest()->paginate($this->perPage($request));
        $periodStats = $this->periodStats(OrderLog::class, 'created_at');
        $order_options = Order::orderBy('id')->get();
        return view('order_logs.index', ['items' => $order_logs, 'order_options' => $order_options, 'periodStats' => $periodStats]);
    }

    public function exportCsv(Request $request)
    {
        $allColumns = ['order_id', 'action', 'description', 'created_by', 'updated_by', 'created_at', 'updated_at'];
        $columns = $request->query('columns', $allColumns);
        $columns = array_values(array_intersect($allColumns, $columns));
        if (empty($columns)) { $columns = $allColumns; }
        $query = OrderLog::query();
        if ($request->filled('period') && $request->period !== 'all') {
            $query = $this->applyPeriod($query, $request->period, 'created_at');
        }
        $query = $this->applySearch($query, (string) $request->query('q', ''), ['action', 'description']);
        $rows = $query->with(['order'])->get();
        $data = $rows->map(function ($row) use ($columns) {
            $out = [];
            foreach ($columns as $col) {
                if ($col === 'order_id') { $out[] = $row->order?->id ?? $row->order_id; continue; }
                $out[] = $row->{$col};
            }
            return $out;
        });
        $headers = array_map(fn ($c) => str($c)->replace('_id', '')->replace('_', ' ')->title()->toString(), $columns);
        return $this->csvResponse('order_logs-'.now()->format('Ymd').'.csv', $headers, $data);
    }

    public function exportPdf(Request $request)
    {
        $allColumns = ['order_id', 'action', 'description', 'created_by', 'updated_by', 'created_at', 'updated_at'];
        $columns = $request->query('columns', $allColumns);
        $columns = array_values(array_intersect($allColumns, $columns));
        if (empty($columns)) { $columns = $allColumns; }
        $query = OrderLog::query();
        if ($request->filled('period') && $request->period !== 'all') {
            $query = $this->applyPeriod($query, $request->period, 'created_at');
        }
        $query = $this->applySearch($query, (string) $request->query('q', ''), ['action', 'description']);
        $rows = $query->with(['order'])->get();
        $data = $rows->map(function ($row) use ($columns) {
            $out = [];
            foreach ($columns as $col) {
                if ($col === 'order_id') { $out[] = $row->order?->id ?? $row->order_id; continue; }
                $out[] = $row->{$col};
            }
            return $out;
        });
        $headers = array_map(fn ($c) => str($c)->replace('_id', '')->replace('_', ' ')->title()->toString(), $columns);
        $options = new \Dompdf\Options();
        $options->set('isRemoteEnabled', true);
        $dompdf = new \Dompdf\Dompdf($options);
        $dompdf->loadHtml(view('partials.export-pdf', ['title' => 'OrderLogs', 'headers' => $headers, 'rows' => $data, 'generatedBy' => auth()->user()?->name ?? 'System'])->render());
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();
        return $dompdf->stream('order_logs-'.now()->format('Ymd').'.pdf');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'order_id' => 'required|exists:orders,id',
            'action' => 'required',
            'description' => 'nullable',
        ]);
        OrderLog::create($validated);
        return redirect()->route('order_logs.index')->with('status', 'OrderLog created successfully.');
    }

    public function show(OrderLog $order_log)
    {
        return view('order_logs.show', ['order_log' => $order_log]);
    }

    public function update(Request $request, OrderLog $order_log)
    {
        $validated = $request->validate([
            'order_id' => 'sometimes|required|exists:orders,id',
            'action' => 'sometimes|required',
            'description' => 'nullable',
        ]);
        $order_log->update($validated);
        return redirect()->route('order_logs.index')->with('status', 'OrderLog updated successfully.');
    }

    public function destroy(OrderLog $order_log)
    {
        $order_log->delete();
        return redirect()->route('order_logs.index')->with('status', 'OrderLog deleted.');
    }
}
