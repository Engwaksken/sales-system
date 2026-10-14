<?php

namespace App\Http\Controllers;

use App\Models\EfrisConfig;
use App\Models\Branch;
use App\Support\ExportsCsv;
use Illuminate\Http\Request;

class EfrisConfigController extends Controller
{
    use ExportsCsv;

    public function index(Request $request)
    {
        $query = EfrisConfig::query();
        if ($request->filled('period') && $request->period !== 'all') {
            $query = $this->applyPeriod($query, $request->period, 'created_at');
        }
        $query = $this->applySearch($query, (string) $request->query('q', ''), ['tin', 'taxpayer_name', 'taxpayer_address', 'taxpayer_email']);
        $efris_config = $query->latest()->paginate($this->perPage($request));
        $stat1 = EfrisConfig::where('is_active', true)->count();
        $periodStats = $this->periodStats(EfrisConfig::class, 'created_at');
        $branch_options = Branch::orderBy('id')->get();
        return view('efris_config.index', ['items' => $efris_config, 'branch_options' => $branch_options, 'stat1' => $stat1, 'periodStats' => $periodStats]);
    }

    public function exportCsv(Request $request)
    {
        $allColumns = ['branch_id', 'tin', 'taxpayer_name', 'taxpayer_address', 'taxpayer_email', 'taxpayer_phone', 'business_type', 'vat_registered', 'environment', 'private_key_pem', 'public_key_pem', 'ura_public_key_pem', 'portal_username', 'portal_password_enc', 'is_active', 'created_by', 'updated_by', 'created_at', 'updated_at'];
        $columns = $request->query('columns', $allColumns);
        $columns = array_values(array_intersect($allColumns, $columns));
        if (empty($columns)) { $columns = $allColumns; }
        $query = EfrisConfig::query();
        if ($request->filled('period') && $request->period !== 'all') {
            $query = $this->applyPeriod($query, $request->period, 'created_at');
        }
        $query = $this->applySearch($query, (string) $request->query('q', ''), ['tin', 'taxpayer_name', 'taxpayer_address', 'taxpayer_email']);
        $rows = $query->with(['branch'])->get();
        $data = $rows->map(function ($row) use ($columns) {
            $out = [];
            foreach ($columns as $col) {
                if ($col === 'branch_id') { $out[] = $row->branch?->name ?? $row->branch_id; continue; }
                $out[] = $row->{$col};
            }
            return $out;
        });
        $headers = array_map(fn ($c) => str($c)->replace('_id', '')->replace('_', ' ')->title()->toString(), $columns);
        return $this->csvResponse('efris_config-'.now()->format('Ymd').'.csv', $headers, $data);
    }

    public function exportPdf(Request $request)
    {
        $allColumns = ['branch_id', 'tin', 'taxpayer_name', 'taxpayer_address', 'taxpayer_email', 'taxpayer_phone', 'business_type', 'vat_registered', 'environment', 'private_key_pem', 'public_key_pem', 'ura_public_key_pem', 'portal_username', 'portal_password_enc', 'is_active', 'created_by', 'updated_by', 'created_at', 'updated_at'];
        $columns = $request->query('columns', $allColumns);
        $columns = array_values(array_intersect($allColumns, $columns));
        if (empty($columns)) { $columns = $allColumns; }
        $query = EfrisConfig::query();
        if ($request->filled('period') && $request->period !== 'all') {
            $query = $this->applyPeriod($query, $request->period, 'created_at');
        }
        $query = $this->applySearch($query, (string) $request->query('q', ''), ['tin', 'taxpayer_name', 'taxpayer_address', 'taxpayer_email']);
        $rows = $query->with(['branch'])->get();
        $data = $rows->map(function ($row) use ($columns) {
            $out = [];
            foreach ($columns as $col) {
                if ($col === 'branch_id') { $out[] = $row->branch?->name ?? $row->branch_id; continue; }
                $out[] = $row->{$col};
            }
            return $out;
        });
        $headers = array_map(fn ($c) => str($c)->replace('_id', '')->replace('_', ' ')->title()->toString(), $columns);
        $options = new \Dompdf\Options();
        $options->set('isRemoteEnabled', true);
        $dompdf = new \Dompdf\Dompdf($options);
        $dompdf->loadHtml(view('partials.export-pdf', ['title' => 'EfrisConfig', 'headers' => $headers, 'rows' => $data, 'generatedBy' => auth()->user()?->name ?? 'System'])->render());
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();
        return $dompdf->stream('efris_config-'.now()->format('Ymd').'.pdf');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'branch_id' => 'required|exists:branches,id',
            'tin' => 'required',
            'taxpayer_name' => 'required',
            'taxpayer_address' => 'nullable',
            'taxpayer_email' => 'nullable',
            'taxpayer_phone' => 'nullable',
            'business_type' => 'nullable',
            'vat_registered' => 'boolean',
            'environment' => 'required|in:sandbox,production',
            'private_key_pem' => 'nullable',
            'public_key_pem' => 'nullable',
            'ura_public_key_pem' => 'nullable',
            'portal_username' => 'nullable',
            'portal_password_enc' => 'nullable',
            'is_active' => 'boolean',
        ]);
        EfrisConfig::create($validated);
        return redirect()->route('efris_config.index')->with('status', 'EfrisConfig created successfully.');
    }

    public function show(EfrisConfig $efris_config)
    {
        return view('efris_config.show', ['efris_config' => $efris_config]);
    }

    public function update(Request $request, EfrisConfig $efris_config)
    {
        $validated = $request->validate([
            'branch_id' => 'sometimes|required|exists:branches,id',
            'tin' => 'sometimes|required',
            'taxpayer_name' => 'sometimes|required',
            'taxpayer_address' => 'nullable',
            'taxpayer_email' => 'nullable',
            'taxpayer_phone' => 'nullable',
            'business_type' => 'nullable',
            'vat_registered' => 'boolean',
            'environment' => 'sometimes|required|in:sandbox,production',
            'private_key_pem' => 'nullable',
            'public_key_pem' => 'nullable',
            'ura_public_key_pem' => 'nullable',
            'portal_username' => 'nullable',
            'portal_password_enc' => 'nullable',
            'is_active' => 'boolean',
        ]);
        $efris_config->update($validated);
        return redirect()->route('efris_config.index')->with('status', 'EfrisConfig updated successfully.');
    }

    public function destroy(EfrisConfig $efris_config)
    {
        $efris_config->delete();
        return redirect()->route('efris_config.index')->with('status', 'EfrisConfig deleted.');
    }
}
