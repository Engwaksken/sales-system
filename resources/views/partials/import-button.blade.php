{{-- Import button + upload dialog for a page listed in App\Support\Imports\ImportModules.
     Usage: @include('partials.import-button', ['module' => 'suppliers']) --}}
@php
    $importModule = \App\Support\Imports\ImportModules::get($module);
    $bulkPermission = \App\Support\Imports\ImportModules::BULK_UPLOAD_PAGE[$module] ?? null;
    $canImport = \App\Http\Middleware\CheckPermission::allows(auth()->user(), $importModule['permission'] ?? $bulkPermission ?? 'none');
@endphp
@if($canImport && $bulkPermission)
    <a href="{{ route('bulk-upload.index') }}" class="px-4 py-2 rounded-md text-sm border border-slate-300 bg-white text-slate-700 hover:bg-slate-50 flex items-center gap-2">
        <i class="fa-solid fa-file-arrow-up"></i> Import
    </a>
@elseif($canImport && $importModule)
    <button type="button" onclick="openModal('import-{{ $module }}-modal')" class="px-4 py-2 rounded-md text-sm border border-slate-300 bg-white text-slate-700 hover:bg-slate-50 flex items-center gap-2">
        <i class="fa-solid fa-file-arrow-up"></i> Import
    </button>

    <div id="import-{{ $module }}-modal" class="fixed inset-0 bg-black/40 hidden items-center justify-center z-30 p-4" role="dialog" aria-modal="true" aria-labelledby="import-{{ $module }}-title">
        <div class="bg-white rounded-xl shadow-xl w-full max-w-lg max-h-[90vh] overflow-y-auto">
            <div class="flex items-center justify-between px-5 py-4 border-b border-slate-100">
                <h2 id="import-{{ $module }}-title" class="font-semibold text-lg flex items-center gap-2"><i class="fa-solid fa-file-arrow-up text-indigo-500"></i> Import {{ $importModule['label'] }}</h2>
                <button type="button" onclick="closeModal('import-{{ $module }}-modal')" class="text-slate-400 hover:text-slate-600" aria-label="Close"><i class="fa-solid fa-xmark"></i></button>
            </div>
            <form method="POST" action="{{ route('imports.preview', $module) }}" enctype="multipart/form-data" class="p-5 space-y-4">
                @csrf
                <ol class="text-sm text-slate-600 space-y-2 list-decimal list-inside">
                    <li>
                        <a href="{{ route('imports.template', $module) }}" class="text-indigo-600 font-semibold hover:underline"><i class="fa-solid fa-download"></i> Download the template</a>
                        and fill in one row per record (the example row is ignored).
                    </li>
                    <li>In Excel, save it as <strong>CSV</strong>.</li>
                    <li>Upload it below. You will see every row and any errors <strong>before</strong> anything is saved.</li>
                </ol>
                @if(!empty($importModule['note']))
                    <div class="rounded-lg bg-amber-50 border border-amber-200 px-3 py-2 text-xs text-amber-800"><i class="fa-solid fa-circle-info"></i> {{ $importModule['note'] }}</div>
                @endif
                <details class="text-xs text-slate-500">
                    <summary class="cursor-pointer font-semibold text-slate-600">Columns</summary>
                    <ul class="mt-2 space-y-1">
                        @foreach($importModule['columns'] as $column => $def)
                            @php
                                $columnRequired = str_contains($def['rules'], 'required') && ! isset($def['default']);
                                $columnHint = $def['help'] ?? match (true) {
                                    (bool) preg_match('/in:([^|]+)/', $def['rules'], $opts) => str_replace(',', ', ', $opts[1]),
                                    ($def['type'] ?? '') === 'date' => 'e.g. 2026-08-15 or 15/08/2026',
                                    ($def['type'] ?? '') === 'boolean' => 'yes / no',
                                    default => '',
                                };
                            @endphp
                            <li>
                                <code class="text-slate-700">{{ $column }}</code>
                                @if($columnRequired)<span class="text-red-600">required</span>@endif
                                @if($columnHint !== '')&middot; {{ $columnHint }}@endif
                            </li>
                        @endforeach
                    </ul>
                </details>
                <div>
                    <label for="import-{{ $module }}-file" class="block text-sm font-medium text-slate-700 mb-1">CSV file</label>
                    <input id="import-{{ $module }}-file" type="file" name="file" accept=".csv,text/csv" required class="w-full text-sm rounded-md border border-slate-300 p-2">
                    <p class="text-xs text-slate-400 mt-1">Up to {{ number_format(\App\Support\Imports\Importer::MAX_ROWS) }} rows, 5 MB.</p>
                </div>
                <div class="flex items-center justify-end gap-2 pt-2">
                    <button type="button" onclick="closeModal('import-{{ $module }}-modal')" class="text-sm text-slate-500 px-3 py-2">Cancel</button>
                    <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded-md text-sm hover:bg-indigo-700 flex items-center gap-2"><i class="fa-solid fa-magnifying-glass"></i> Check file</button>
                </div>
            </form>
        </div>
    </div>
@endif
