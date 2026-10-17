<?php

namespace App\Http\Controllers;

use App\Http\Middleware\CheckPermission;
use App\Support\Imports\Importer;
use App\Support\Imports\ImportModules;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** Upload → preview every row with its errors → confirm to save all rows at once. */
class ImportController extends Controller
{
    public function template(string $module): StreamedResponse
    {
        $definition = $this->module($module);
        [$headers, $example] = (new Importer($definition))->templateRows();

        return response()->streamDownload(function () use ($headers, $example): void {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // lets Excel open the file as UTF-8
            fputcsv($out, $headers);
            fputcsv($out, $example);
            fclose($out);
        }, "nsales-{$module}-template.csv", ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function preview(Request $request, string $module)
    {
        $definition = $this->module($module);
        $request->validate(['file' => ['required', 'file', 'mimes:csv,txt', 'max:5120']], [
            'file.mimes' => 'Upload a CSV file. In Excel use File > Save As > CSV.',
        ]);

        $importer = new Importer($definition);
        try {
            $rows = $importer->read($request->file('file'));
        } catch (RuntimeException $e) {
            return back()->withErrors(['file' => $e->getMessage()]);
        }

        $token = (string) Str::uuid();
        $request->session()->put("imports.{$token}", ['module' => $module, 'rows' => $rows, 'file' => $request->file('file')->getClientOriginalName()]);

        return view('imports.preview', [
            'module' => $definition,
            'rows' => $importer->check($rows),
            'token' => $token,
            'fileName' => $request->file('file')->getClientOriginalName(),
        ]);
    }

    public function confirm(Request $request, string $module)
    {
        $definition = $this->module($module);
        $token = (string) $request->input('token');
        $stored = $request->session()->get("imports.{$token}");
        if (! $stored || $stored['module'] !== $module) {
            return redirect()->route($definition['index'])->withErrors(['import' => 'This import has expired. Upload the file again.']);
        }

        try {
            $counts = (new Importer($definition))->commit($stored['rows'], auth()->id());
        } catch (RuntimeException $e) {
            return redirect()->route($definition['index'])->withErrors(['import' => $e->getMessage()]);
        }
        $request->session()->forget("imports.{$token}");

        return redirect()->route($definition['index'])->with('status',
            "{$definition['label']} import complete: {$counts['created']} added, {$counts['updated']} updated.");
    }

    private function module(string $key): array
    {
        $definition = ImportModules::get($key);
        abort_unless($definition, 404);
        abort_unless(CheckPermission::allows(auth()->user(), $definition['permission']), 403, 'You do not have permission to import into this page.');

        return $definition;
    }
}
