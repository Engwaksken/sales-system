<?php

namespace App\Http\Controllers;

use App\Models\NavPage;
use App\Support\ExportsCsv;
use Illuminate\Http\Request;

class NavPagesController extends Controller
{
    use ExportsCsv;

    public function index(Request $request)
    {
        $query = NavPage::query();
        if ($request->filled('period') && $request->period !== 'all') {
            $query = $this->applyPeriod($query, $request->period, 'created_at');
        }
        $query = $this->applySearch($query, (string) $request->query('q', ''), ['label', 'url', 'icon', 'permission_key']);
        $nav_pages = $query->latest()->paginate($this->perPage($request));
        $stat1 = NavPage::where('is_active', true)->count();
        $periodStats = $this->periodStats(NavPage::class, 'created_at');
        $parent_options = NavPage::orderBy('id')->get();
        return view('nav_pages.index', ['items' => $nav_pages, 'parent_options' => $parent_options, 'stat1' => $stat1, 'periodStats' => $periodStats]);
    }

    public function exportCsv(Request $request)
    {
        $allColumns = ['label', 'url', 'icon', 'parent_id', 'sort_order', 'permission_key', 'is_active', 'created_by', 'updated_by', 'created_at', 'updated_at'];
        $columns = $request->query('columns', $allColumns);
        $columns = array_values(array_intersect($allColumns, $columns));
        if (empty($columns)) { $columns = $allColumns; }
        $query = NavPage::query();
        if ($request->filled('period') && $request->period !== 'all') {
            $query = $this->applyPeriod($query, $request->period, 'created_at');
        }
        $query = $this->applySearch($query, (string) $request->query('q', ''), ['label', 'url', 'icon', 'permission_key']);
        $rows = $query->with(['parent'])->get();
        $data = $rows->map(function ($row) use ($columns) {
            $out = [];
            foreach ($columns as $col) {
                if ($col === 'parent_id') { $out[] = $row->parent?->label ?? $row->parent_id; continue; }
                $out[] = $row->{$col};
            }
            return $out;
        });
        $headers = array_map(fn ($c) => str($c)->replace('_id', '')->replace('_', ' ')->title()->toString(), $columns);
        return $this->csvResponse('nav_pages-'.now()->format('Ymd').'.csv', $headers, $data);
    }

    public function exportPdf(Request $request)
    {
        $allColumns = ['label', 'url', 'icon', 'parent_id', 'sort_order', 'permission_key', 'is_active', 'created_by', 'updated_by', 'created_at', 'updated_at'];
        $columns = $request->query('columns', $allColumns);
        $columns = array_values(array_intersect($allColumns, $columns));
        if (empty($columns)) { $columns = $allColumns; }
        $query = NavPage::query();
        if ($request->filled('period') && $request->period !== 'all') {
            $query = $this->applyPeriod($query, $request->period, 'created_at');
        }
        $query = $this->applySearch($query, (string) $request->query('q', ''), ['label', 'url', 'icon', 'permission_key']);
        $rows = $query->with(['parent'])->get();
        $data = $rows->map(function ($row) use ($columns) {
            $out = [];
            foreach ($columns as $col) {
                if ($col === 'parent_id') { $out[] = $row->parent?->label ?? $row->parent_id; continue; }
                $out[] = $row->{$col};
            }
            return $out;
        });
        $headers = array_map(fn ($c) => str($c)->replace('_id', '')->replace('_', ' ')->title()->toString(), $columns);
        $options = new \Dompdf\Options();
        $options->set('isRemoteEnabled', true);
        $dompdf = new \Dompdf\Dompdf($options);
        $dompdf->loadHtml(view('partials.export-pdf', ['title' => 'NavPages', 'headers' => $headers, 'rows' => $data, 'generatedBy' => auth()->user()?->name ?? 'System'])->render());
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();
        return $dompdf->stream('nav_pages-'.now()->format('Ymd').'.pdf');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'label' => 'required',
            'url' => 'required',
            'icon' => 'required',
            'parent_id' => 'nullable|exists:nav_pages,id',
            'sort_order' => 'required|numeric',
            'permission_key' => 'required',
            'is_active' => 'boolean',
        ]);
        NavPage::create($validated);
        return redirect()->route('nav_pages.index')->with('status', 'NavPage created successfully.');
    }

    public function show(NavPage $nav_page)
    {
        return view('nav_pages.show', ['nav_page' => $nav_page]);
    }

    public function update(Request $request, NavPage $nav_page)
    {
        $validated = $request->validate([
            'label' => 'sometimes|required',
            'url' => 'sometimes|required',
            'icon' => 'sometimes|required',
            'parent_id' => 'nullable|exists:nav_pages,id',
            'sort_order' => 'sometimes|required|numeric',
            'permission_key' => 'sometimes|required',
            'is_active' => 'boolean',
        ]);
        $nav_page->update($validated);
        return redirect()->route('nav_pages.index')->with('status', 'NavPage updated successfully.');
    }

    public function destroy(NavPage $nav_page)
    {
        $nav_page->delete();
        return redirect()->route('nav_pages.index')->with('status', 'NavPage deleted.');
    }
}
