<?php

namespace App\Http\Controllers;

use App\Models\ProductImage;
use App\Models\Product;
use App\Support\ExportsCsv;
use Illuminate\Http\Request;

class ProductImagesController extends Controller
{
    use ExportsCsv;

    public function index(Request $request)
    {
        $query = ProductImage::query();
        if ($request->filled('period') && $request->period !== 'all') {
            $query = $this->applyPeriod($query, $request->period, 'created_at');
        }
        $product_images = $query->latest()->paginate($this->perPage($request));
        $periodStats = $this->periodStats(ProductImage::class, 'created_at');
        $product_options = Product::orderBy('id')->get();
        return view('product_images.index', ['items' => $product_images, 'product_options' => $product_options, 'periodStats' => $periodStats]);
    }

    public function exportCsv(Request $request)
    {
        $allColumns = ['product_id', 'image_path', 'is_main', 'is_primary', 'created_by', 'updated_by', 'created_at', 'updated_at'];
        $columns = $request->query('columns', $allColumns);
        $columns = array_values(array_intersect($allColumns, $columns));
        if (empty($columns)) { $columns = $allColumns; }
        $query = ProductImage::query();
        if ($request->filled('period') && $request->period !== 'all') {
            $query = $this->applyPeriod($query, $request->period, 'created_at');
        }
        $rows = $query->with(['product'])->get();
        $data = $rows->map(function ($row) use ($columns) {
            $out = [];
            foreach ($columns as $col) {
                if ($col === 'product_id') { $out[] = $row->product?->name ?? $row->product_id; continue; }
                $out[] = $row->{$col};
            }
            return $out;
        });
        $headers = array_map(fn ($c) => str($c)->replace('_id', '')->replace('_', ' ')->title()->toString(), $columns);
        return $this->csvResponse('product_images-'.now()->format('Ymd').'.csv', $headers, $data);
    }

    public function exportPdf(Request $request)
    {
        $allColumns = ['product_id', 'image_path', 'is_main', 'is_primary', 'created_by', 'updated_by', 'created_at', 'updated_at'];
        $columns = $request->query('columns', $allColumns);
        $columns = array_values(array_intersect($allColumns, $columns));
        if (empty($columns)) { $columns = $allColumns; }
        $query = ProductImage::query();
        if ($request->filled('period') && $request->period !== 'all') {
            $query = $this->applyPeriod($query, $request->period, 'created_at');
        }
        $rows = $query->with(['product'])->get();
        $data = $rows->map(function ($row) use ($columns) {
            $out = [];
            foreach ($columns as $col) {
                if ($col === 'product_id') { $out[] = $row->product?->name ?? $row->product_id; continue; }
                $out[] = $row->{$col};
            }
            return $out;
        });
        $headers = array_map(fn ($c) => str($c)->replace('_id', '')->replace('_', ' ')->title()->toString(), $columns);
        $options = new \Dompdf\Options();
        $options->set('isRemoteEnabled', true);
        $dompdf = new \Dompdf\Dompdf($options);
        $dompdf->loadHtml(view('partials.export-pdf', ['title' => 'ProductImages', 'headers' => $headers, 'rows' => $data, 'generatedBy' => auth()->user()?->name ?? 'System'])->render());
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();
        return $dompdf->stream('product_images-'.now()->format('Ymd').'.pdf');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'image_path' => 'required',
            'is_main' => 'boolean',
            'is_primary' => 'boolean',
        ]);
        ProductImage::create($validated);
        return redirect()->route('product_images.index')->with('status', 'ProductImage created successfully.');
    }

    public function show(ProductImage $product_image)
    {
        return view('product_images.show', ['product_image' => $product_image]);
    }

    public function update(Request $request, ProductImage $product_image)
    {
        $validated = $request->validate([
            'product_id' => 'sometimes|required|exists:products,id',
            'image_path' => 'sometimes|required',
            'is_main' => 'boolean',
            'is_primary' => 'boolean',
        ]);
        $product_image->update($validated);
        return redirect()->route('product_images.index')->with('status', 'ProductImage updated successfully.');
    }

    public function destroy(ProductImage $product_image)
    {
        $product_image->delete();
        return redirect()->route('product_images.index')->with('status', 'ProductImage deleted.');
    }
}
