<?php

namespace App\Http\Controllers;

use App\Models\AppNotification;
use App\Models\User;
use App\Support\ExportsCsv;
use Illuminate\Http\Request;

class NotificationsAppController extends Controller
{
    use ExportsCsv;

    public function index(Request $request)
    {
        $query = AppNotification::query();
        if ($request->filled('period') && $request->period !== 'all') {
            $query = $this->applyPeriod($query, $request->period, 'created_at');
        }
        $query = $this->applySearch($query, (string) $request->query('q', ''), ['title', 'message', 'type']);
        $notifications_app = $query->latest()->paginate($this->perPage($request));
        $periodStats = $this->periodStats(AppNotification::class, 'created_at');
        $user_options = User::orderBy('id')->get();
        return view('notifications_app.index', ['items' => $notifications_app, 'user_options' => $user_options, 'periodStats' => $periodStats]);
    }

    public function exportCsv(Request $request)
    {
        $allColumns = ['title', 'message', 'type', 'user_id', 'created_by', 'is_read', 'read_at', 'updated_by', 'created_at', 'updated_at'];
        $columns = $request->query('columns', $allColumns);
        $columns = array_values(array_intersect($allColumns, $columns));
        if (empty($columns)) { $columns = $allColumns; }
        $query = AppNotification::query();
        if ($request->filled('period') && $request->period !== 'all') {
            $query = $this->applyPeriod($query, $request->period, 'created_at');
        }
        $query = $this->applySearch($query, (string) $request->query('q', ''), ['title', 'message', 'type']);
        $rows = $query->with(['user'])->get();
        $data = $rows->map(function ($row) use ($columns) {
            $out = [];
            foreach ($columns as $col) {
                if ($col === 'user_id') { $out[] = $row->user?->name ?? $row->user_id; continue; }
                $out[] = $row->{$col};
            }
            return $out;
        });
        $headers = array_map(fn ($c) => str($c)->replace('_id', '')->replace('_', ' ')->title()->toString(), $columns);
        return $this->csvResponse('notifications_app-'.now()->format('Ymd').'.csv', $headers, $data);
    }

    public function exportPdf(Request $request)
    {
        $allColumns = ['title', 'message', 'type', 'user_id', 'created_by', 'is_read', 'read_at', 'updated_by', 'created_at', 'updated_at'];
        $columns = $request->query('columns', $allColumns);
        $columns = array_values(array_intersect($allColumns, $columns));
        if (empty($columns)) { $columns = $allColumns; }
        $query = AppNotification::query();
        if ($request->filled('period') && $request->period !== 'all') {
            $query = $this->applyPeriod($query, $request->period, 'created_at');
        }
        $query = $this->applySearch($query, (string) $request->query('q', ''), ['title', 'message', 'type']);
        $rows = $query->with(['user'])->get();
        $data = $rows->map(function ($row) use ($columns) {
            $out = [];
            foreach ($columns as $col) {
                if ($col === 'user_id') { $out[] = $row->user?->name ?? $row->user_id; continue; }
                $out[] = $row->{$col};
            }
            return $out;
        });
        $headers = array_map(fn ($c) => str($c)->replace('_id', '')->replace('_', ' ')->title()->toString(), $columns);
        $options = new \Dompdf\Options();
        $options->set('isRemoteEnabled', true);
        $dompdf = new \Dompdf\Dompdf($options);
        $dompdf->loadHtml(view('partials.export-pdf', ['title' => 'NotificationsApp', 'headers' => $headers, 'rows' => $data, 'generatedBy' => auth()->user()?->name ?? 'System'])->render());
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();
        return $dompdf->stream('notifications_app-'.now()->format('Ymd').'.pdf');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required',
            'message' => 'required',
            'type' => 'required',
            'user_id' => 'nullable|exists:users,id',
            'is_read' => 'boolean',
            'read_at' => 'nullable',
        ]);
        AppNotification::create($validated);
        return redirect()->route('notifications_app.index')->with('status', 'NotificationsApp created successfully.');
    }

    public function show(AppNotification $notifications_app)
    {
        return view('notifications_app.show', ['notifications_app' => $notifications_app]);
    }

    public function update(Request $request, AppNotification $notifications_app)
    {
        $validated = $request->validate([
            'title' => 'sometimes|required',
            'message' => 'sometimes|required',
            'type' => 'sometimes|required',
            'user_id' => 'nullable|exists:users,id',
            'is_read' => 'boolean',
            'read_at' => 'nullable',
        ]);
        $notifications_app->update($validated);
        return redirect()->route('notifications_app.index')->with('status', 'NotificationsApp updated successfully.');
    }

    public function destroy(AppNotification $notifications_app)
    {
        $notifications_app->delete();
        return redirect()->route('notifications_app.index')->with('status', 'NotificationsApp deleted.');
    }
}
