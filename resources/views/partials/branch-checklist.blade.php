{{-- Multi-branch picker: @include('partials.branch-checklist', ['branches' => $branch_options, 'label' => '...', 'hint' => '...']) --}}
<fieldset class="rounded-lg border border-slate-200 p-3">
    <legend class="px-1 text-sm font-medium text-slate-700">{{ $label ?? 'Branches' }}</legend>
    @if(count($branches) > 1)
        <label class="flex items-center gap-2 text-sm font-semibold text-slate-700 pb-2 mb-2 border-b border-slate-100">
            <input type="checkbox" data-check-all="branch_ids[]" class="rounded border-slate-300"> All branches
        </label>
    @endif
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-4 gap-y-1 max-h-48 overflow-y-auto">
        @foreach($branches as $branch)
            <label class="flex items-center gap-2 text-sm text-slate-700 py-0.5">
                <input type="checkbox" name="branch_ids[]" value="{{ $branch->id }}" class="rounded border-slate-300"> {{ $branch->name }}
            </label>
        @endforeach
    </div>
    @if(!empty($hint))<p class="text-xs text-slate-500 mt-2">{{ $hint }}</p>@endif
</fieldset>
