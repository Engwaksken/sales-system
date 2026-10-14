@php
    $paginator = $paginator ?? null;
@endphp

@if($paginator instanceof \Illuminate\Contracts\Pagination\Paginator)
    <div class="mt-4 flex flex-col gap-3 rounded-xl border border-slate-200 bg-white px-4 py-3 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex flex-wrap items-center gap-3 text-xs text-slate-500">
            @if(method_exists($paginator, 'total'))
                <span>
                    Showing
                    <strong>{{ $paginator->firstItem() ?? 0 }}</strong>
                    –
                    <strong>{{ $paginator->lastItem() ?? 0 }}</strong>
                    of
                    <strong>{{ $paginator->total() }}</strong>
                </span>
            @endif

            <form method="GET" action="{{ url()->current() }}" class="flex items-center gap-2">
                @foreach(request()->except(['page','per_page']) as $key => $value)
                    @if(!is_array($value))
                        <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                    @endif
                @endforeach

                <label class="font-bold">Rows</label>
                <select name="per_page"
                        onchange="this.form.submit()"
                        class="rounded-lg border-slate-300 px-2 py-1 text-xs">
                    @foreach([10,20,50,100] as $size)
                        <option value="{{ $size }}"
                            @selected((int)request('per_page', $paginator->perPage()) === $size)>
                            {{ $size }}
                        </option>
                    @endforeach
                </select>
            </form>
        </div>

        <div>{{ $paginator->onEachSide(1)->links() }}</div>
    </div>
@endif
