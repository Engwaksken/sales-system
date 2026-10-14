<?php

namespace App\Http\Controllers;

use App\Models\EmailSetting;
use App\Support\ExportsCsv;
use Illuminate\Http\Request;

class EmailSettingsController extends Controller
{
    use ExportsCsv;

    public function index(Request $request)
    {
        $query = EmailSetting::query();
        if ($request->filled('period') && $request->period !== 'all') {
            $query = $this->applyPeriod($query, $request->period, 'created_at');
        }
        $query = $this->applySearch($query, (string) $request->query('q', ''), ['smtp_host', 'smtp_user', 'smtp_password', 'smtp_secure']);
        $email_settings = $query->latest()->paginate($this->perPage($request));
        $periodStats = $this->periodStats(EmailSetting::class, 'created_at');
        return view('email_settings.index', ['items' => $email_settings, 'periodStats' => $periodStats]);
    }

    public function exportCsv(Request $request)
    {
        $allColumns = ['smtp_host', 'smtp_port', 'smtp_user', 'smtp_password', 'smtp_secure', 'from_email', 'from_name', 'created_by', 'updated_by', 'created_at', 'updated_at'];
        $columns = $request->query('columns', $allColumns);
        $columns = array_values(array_intersect($allColumns, $columns));
        if (empty($columns)) { $columns = $allColumns; }
        $query = EmailSetting::query();
        if ($request->filled('period') && $request->period !== 'all') {
            $query = $this->applyPeriod($query, $request->period, 'created_at');
        }
        $query = $this->applySearch($query, (string) $request->query('q', ''), ['smtp_host', 'smtp_user', 'smtp_password', 'smtp_secure']);
        $rows = $query->get();
        $data = $rows->map(function ($row) use ($columns) {
            $out = [];
            foreach ($columns as $col) {
                $out[] = $row->{$col};
            }
            return $out;
        });
        $headers = array_map(fn ($c) => str($c)->replace('_id', '')->replace('_', ' ')->title()->toString(), $columns);
        return $this->csvResponse('email_settings-'.now()->format('Ymd').'.csv', $headers, $data);
    }

    public function exportPdf(Request $request)
    {
        $allColumns = ['smtp_host', 'smtp_port', 'smtp_user', 'smtp_password', 'smtp_secure', 'from_email', 'from_name', 'created_by', 'updated_by', 'created_at', 'updated_at'];
        $columns = $request->query('columns', $allColumns);
        $columns = array_values(array_intersect($allColumns, $columns));
        if (empty($columns)) { $columns = $allColumns; }
        $query = EmailSetting::query();
        if ($request->filled('period') && $request->period !== 'all') {
            $query = $this->applyPeriod($query, $request->period, 'created_at');
        }
        $query = $this->applySearch($query, (string) $request->query('q', ''), ['smtp_host', 'smtp_user', 'smtp_password', 'smtp_secure']);
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
        $dompdf->loadHtml(view('partials.export-pdf', ['title' => 'EmailSettings', 'headers' => $headers, 'rows' => $data, 'generatedBy' => auth()->user()?->name ?? 'System'])->render());
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();
        return $dompdf->stream('email_settings-'.now()->format('Ymd').'.pdf');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'smtp_host' => 'required',
            'smtp_port' => 'required|numeric',
            'smtp_user' => 'required',
            'smtp_password' => 'required',
            'smtp_secure' => 'required',
            'from_email' => 'required',
            'from_name' => 'nullable',
        ]);
        EmailSetting::create($validated);
        return redirect()->route('email_settings.index')->with('status', 'EmailSetting created successfully.');
    }

    public function show(EmailSetting $email_setting)
    {
        return view('email_settings.show', ['email_setting' => $email_setting]);
    }

    public function update(Request $request, EmailSetting $email_setting)
    {
        $validated = $request->validate([
            'smtp_host' => 'sometimes|required',
            'smtp_port' => 'sometimes|required|numeric',
            'smtp_user' => 'sometimes|required',
            'smtp_password' => 'sometimes|required',
            'smtp_secure' => 'sometimes|required',
            'from_email' => 'sometimes|required',
            'from_name' => 'nullable',
        ]);
        $email_setting->update($validated);
        return redirect()->route('email_settings.index')->with('status', 'EmailSetting updated successfully.');
    }

    public function destroy(EmailSetting $email_setting)
    {
        $email_setting->delete();
        return redirect()->route('email_settings.index')->with('status', 'EmailSetting deleted.');
    }
}
