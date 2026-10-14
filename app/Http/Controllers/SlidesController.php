<?php

namespace App\Http\Controllers;

use App\Models\Slide;
use App\Support\ExportsCsv;
use Illuminate\Http\Request;

class SlidesController extends Controller
{
    use ExportsCsv;

    public function index(Request $request)
    {
        $query = Slide::query();
        if ($request->filled('period') && $request->period !== 'all') {
            $query = $this->applyPeriod($query, $request->period, 'created_at');
        }
        $query = $this->applySearch($query, (string) $request->query('q', ''), ['title', 'subtitle', 'badge_text', 'cta_label']);
        $slides = $query->latest()->paginate($this->perPage($request));
        $stat1 = Slide::where('is_active', true)->count();
        $periodStats = $this->periodStats(Slide::class, 'created_at');
        return view('slides.index', ['items' => $slides, 'stat1' => $stat1, 'periodStats' => $periodStats]);
    }

    public function exportCsv(Request $request)
    {
        $allColumns = ['title', 'subtitle', 'badge_text', 'image_url', 'cta_label', 'cta_link', 'bg_color', 'sort_order', 'is_active', 'created_by', 'updated_by', 'created_at', 'updated_at'];
        $columns = $request->query('columns', $allColumns);
        $columns = array_values(array_intersect($allColumns, $columns));
        if (empty($columns)) { $columns = $allColumns; }
        $query = Slide::query();
        if ($request->filled('period') && $request->period !== 'all') {
            $query = $this->applyPeriod($query, $request->period, 'created_at');
        }
        $query = $this->applySearch($query, (string) $request->query('q', ''), ['title', 'subtitle', 'badge_text', 'cta_label']);
        $rows = $query->get();
        $data = $rows->map(function ($row) use ($columns) {
            $out = [];
            foreach ($columns as $col) {
                $out[] = $row->{$col};
            }
            return $out;
        });
        $headers = array_map(fn ($c) => str($c)->replace('_id', '')->replace('_', ' ')->title()->toString(), $columns);
        return $this->csvResponse('slides-'.now()->format('Ymd').'.csv', $headers, $data);
    }

    public function exportPdf(Request $request)
    {
        $allColumns = ['title', 'subtitle', 'badge_text', 'image_url', 'cta_label', 'cta_link', 'bg_color', 'sort_order', 'is_active', 'created_by', 'updated_by', 'created_at', 'updated_at'];
        $columns = $request->query('columns', $allColumns);
        $columns = array_values(array_intersect($allColumns, $columns));
        if (empty($columns)) { $columns = $allColumns; }
        $query = Slide::query();
        if ($request->filled('period') && $request->period !== 'all') {
            $query = $this->applyPeriod($query, $request->period, 'created_at');
        }
        $query = $this->applySearch($query, (string) $request->query('q', ''), ['title', 'subtitle', 'badge_text', 'cta_label']);
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
        $dompdf->loadHtml(view('partials.export-pdf', ['title' => 'Slides', 'headers' => $headers, 'rows' => $data, 'generatedBy' => auth()->user()?->name ?? 'System'])->render());
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();
        return $dompdf->stream('slides-'.now()->format('Ymd').'.pdf');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required',
            'subtitle' => 'nullable',
            'badge_text' => 'required',
            'image_url' => 'required',
            'cta_label' => 'required',
            'cta_link' => 'required',
            'bg_color' => 'required',
            'sort_order' => 'required|numeric',
            'is_active' => 'boolean',
        ]);
        Slide::create($validated);
        return redirect()->route('slides.index')->with('status', 'Slide created successfully.');
    }

    public function show(Slide $slide)
    {
        return view('slides.show', ['slide' => $slide]);
    }

    public function update(Request $request, Slide $slide)
    {
        $validated = $request->validate([
            'title' => 'sometimes|required',
            'subtitle' => 'nullable',
            'badge_text' => 'sometimes|required',
            'image_url' => 'sometimes|required',
            'cta_label' => 'sometimes|required',
            'cta_link' => 'sometimes|required',
            'bg_color' => 'sometimes|required',
            'sort_order' => 'sometimes|required|numeric',
            'is_active' => 'boolean',
        ]);
        $slide->update($validated);
        return redirect()->route('slides.index')->with('status', 'Slide updated successfully.');
    }

    public function destroy(Slide $slide)
    {
        $slide->delete();
        return redirect()->route('slides.index')->with('status', 'Slide deleted.');
    }
}
