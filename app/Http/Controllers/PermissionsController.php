<?php

namespace App\Http\Controllers;

use App\Models\Permission;
use App\Support\ExportsCsv;
use Illuminate\Http\Request;

class PermissionsController extends Controller
{
    use ExportsCsv;

    public function index(Request $request)
    {
        $query = Permission::query();
        if ($request->filled('period') && $request->period !== 'all') {
            $query = $this->applyPeriod($query, $request->period, 'created_at');
        }
        $query = $this->applySearch($query, (string) $request->query('q', ''), ['permission_key', 'label', 'category', 'description']);
        $permissions = $query->latest()->paginate($this->perPage($request));
        $periodStats = $this->periodStats(Permission::class, 'created_at');
        return view('permissions.index', ['items' => $permissions, 'periodStats' => $periodStats]);
    }

    public function exportCsv(Request $request)
    {
        $allColumns = ['permission_key', 'label', 'category', 'description', 'created_by', 'updated_by', 'created_at', 'updated_at'];
        $columns = $request->query('columns', $allColumns);
        $columns = array_values(array_intersect($allColumns, $columns));
        if (empty($columns)) { $columns = $allColumns; }
        $query = Permission::query();
        if ($request->filled('period') && $request->period !== 'all') {
            $query = $this->applyPeriod($query, $request->period, 'created_at');
        }
        $query = $this->applySearch($query, (string) $request->query('q', ''), ['permission_key', 'label', 'category', 'description']);
        $rows = $query->get();
        $data = $rows->map(function ($row) use ($columns) {
            $out = [];
            foreach ($columns as $col) {
                $out[] = $row->{$col};
            }
            return $out;
        });
        $headers = array_map(fn ($c) => str($c)->replace('_id', '')->replace('_', ' ')->title()->toString(), $columns);
        return $this->csvResponse('permissions-'.now()->format('Ymd').'.csv', $headers, $data);
    }

    public function exportPdf(Request $request)
    {
        $allColumns = ['permission_key', 'label', 'category', 'description', 'created_by', 'updated_by', 'created_at', 'updated_at'];
        $columns = $request->query('columns', $allColumns);
        $columns = array_values(array_intersect($allColumns, $columns));
        if (empty($columns)) { $columns = $allColumns; }
        $query = Permission::query();
        if ($request->filled('period') && $request->period !== 'all') {
            $query = $this->applyPeriod($query, $request->period, 'created_at');
        }
        $query = $this->applySearch($query, (string) $request->query('q', ''), ['permission_key', 'label', 'category', 'description']);
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
        $dompdf->loadHtml(view('partials.export-pdf', ['title' => 'Permissions', 'headers' => $headers, 'rows' => $data, 'generatedBy' => auth()->user()?->name ?? 'System'])->render());
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();
        return $dompdf->stream('permissions-'.now()->format('Ymd').'.pdf');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'permission_key' => 'required',
            'label' => 'required',
            'category' => 'required',
            'description' => 'required',
        ]);
        Permission::create($validated);
        return redirect()->route('permissions.index')->with('status', 'Permission created successfully.');
    }

    public function show(Permission $permission)
    {
        return view('permissions.show', ['permission' => $permission]);
    }

    public function update(Request $request, Permission $permission)
    {
        $validated = $request->validate([
            'permission_key' => 'sometimes|required',
            'label' => 'sometimes|required',
            'category' => 'sometimes|required',
            'description' => 'sometimes|required',
        ]);
        $permission->update($validated);
        return redirect()->route('permissions.index')->with('status', 'Permission updated successfully.');
    }

    public function destroy(Permission $permission)
    {
        $permission->delete();
        return redirect()->route('permissions.index')->with('status', 'Permission deleted.');
    }
}
