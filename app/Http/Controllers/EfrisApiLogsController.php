<?php

namespace App\Http\Controllers;

use App\Models\EfrisApiLog;
use App\Models\EfrisConfig;
use App\Support\ExportsCsv;
use Illuminate\Http\Request;

class EfrisApiLogsController extends Controller
{
    use ExportsCsv;

    public function index(Request $request)
    {
        $query = EfrisApiLog::query();
        if ($request->filled('period') && $request->period !== 'all') {
            $query = $this->applyPeriod($query, $request->period, 'created_at');
        }
        $query = $this->applySearch($query, (string) $request->query('q', ''), ['operation_code', 'operation_name', 'reference_type', 'efris_return_code']);
        $efris_api_logs = $query->latest()->paginate($this->perPage($request));
        $periodStats = $this->periodStats(EfrisApiLog::class, 'created_at');
        $config_options = EfrisConfig::orderBy('id')->get();
        return view('efris_api_logs.index', ['items' => $efris_api_logs, 'config_options' => $config_options, 'periodStats' => $periodStats]);
    }

    public function exportCsv(Request $request)
    {
        $allColumns = ['config_id', 'operation_code', 'operation_name', 'http_method', 'endpoint_url', 'reference_type', 'reference_id', 'request_body', 'response_body', 'http_status_code', 'efris_return_code', 'efris_return_msg', 'is_success', 'latency_ms', 'requested_at', 'responded_at', 'created_by', 'updated_by', 'created_at', 'updated_at'];
        $columns = $request->query('columns', $allColumns);
        $columns = array_values(array_intersect($allColumns, $columns));
        if (empty($columns)) { $columns = $allColumns; }
        $query = EfrisApiLog::query();
        if ($request->filled('period') && $request->period !== 'all') {
            $query = $this->applyPeriod($query, $request->period, 'created_at');
        }
        $query = $this->applySearch($query, (string) $request->query('q', ''), ['operation_code', 'operation_name', 'reference_type', 'efris_return_code']);
        $rows = $query->with(['config'])->get();
        $data = $rows->map(function ($row) use ($columns) {
            $out = [];
            foreach ($columns as $col) {
                if ($col === 'config_id') { $out[] = $row->config?->id ?? $row->config_id; continue; }
                $out[] = $row->{$col};
            }
            return $out;
        });
        $headers = array_map(fn ($c) => str($c)->replace('_id', '')->replace('_', ' ')->title()->toString(), $columns);
        return $this->csvResponse('efris_api_logs-'.now()->format('Ymd').'.csv', $headers, $data);
    }

    public function exportPdf(Request $request)
    {
        $allColumns = ['config_id', 'operation_code', 'operation_name', 'http_method', 'endpoint_url', 'reference_type', 'reference_id', 'request_body', 'response_body', 'http_status_code', 'efris_return_code', 'efris_return_msg', 'is_success', 'latency_ms', 'requested_at', 'responded_at', 'created_by', 'updated_by', 'created_at', 'updated_at'];
        $columns = $request->query('columns', $allColumns);
        $columns = array_values(array_intersect($allColumns, $columns));
        if (empty($columns)) { $columns = $allColumns; }
        $query = EfrisApiLog::query();
        if ($request->filled('period') && $request->period !== 'all') {
            $query = $this->applyPeriod($query, $request->period, 'created_at');
        }
        $query = $this->applySearch($query, (string) $request->query('q', ''), ['operation_code', 'operation_name', 'reference_type', 'efris_return_code']);
        $rows = $query->with(['config'])->get();
        $data = $rows->map(function ($row) use ($columns) {
            $out = [];
            foreach ($columns as $col) {
                if ($col === 'config_id') { $out[] = $row->config?->id ?? $row->config_id; continue; }
                $out[] = $row->{$col};
            }
            return $out;
        });
        $headers = array_map(fn ($c) => str($c)->replace('_id', '')->replace('_', ' ')->title()->toString(), $columns);
        $options = new \Dompdf\Options();
        $options->set('isRemoteEnabled', true);
        $dompdf = new \Dompdf\Dompdf($options);
        $dompdf->loadHtml(view('partials.export-pdf', ['title' => 'EfrisApiLogs', 'headers' => $headers, 'rows' => $data, 'generatedBy' => auth()->user()?->name ?? 'System'])->render());
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();
        return $dompdf->stream('efris_api_logs-'.now()->format('Ymd').'.pdf');
    }

    public function show(EfrisApiLog $efris_api_log)
    {
        return view('efris_api_logs.show', ['efris_api_log' => $efris_api_log]);
    }

}
