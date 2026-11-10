@extends('layouts.app')

@section('title', 'Chat Support')

@section('content')
<div class="mb-4">
    <div>
        <h1 class="text-xl font-black text-slate-800">
            <i class="fas fa-headset mr-2 text-indigo-600"></i>
            Chat Support
        </h1>
        <p class="mt-1 text-sm text-slate-500">
            Customer conversations, unread messages and support replies.
        </p>
    </div>

</div>

<div class="filter-toolbar">
    <form method="GET" action="{{ route('chat.index') }}" class="filter-toolbar__filters">
        <div class="filter-search">
            <i class="fas fa-magnifying-glass"></i>
            <input type="search" name="q" value="{{ request('q') }}" aria-label="Search conversations"
                   placeholder="Customer, email, phone…" class="rounded-lg border-slate-300">
        </div>
        <select name="status" aria-label="Status" class="rounded-lg border-slate-300" onchange="this.form.submit()">
            <option value="">All Conversations</option>
            <option value="open" @selected(request('status') === 'open')>Open</option>
            <option value="closed" @selected(request('status') === 'closed')>Closed</option>
        </select>
        <button type="submit" class="filter-btn"><i class="fas fa-filter"></i> Filter</button>
        @if(request('q') || request('status'))
            <a href="{{ route('chat.index') }}" class="filter-clear"><i class="fas fa-xmark"></i> Clear</a>
        @endif
    </form>
</div>

@if(session('status'))
    <div data-flash class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-700">
        <i class="fas fa-circle-check mr-1"></i>
        {{ session('status') }}
    </div>
@endif

@if($chatSetupMissing ?? false)
    <div class="mb-5 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">
        <div class="font-black">
            <i class="fas fa-triangle-exclamation mr-1"></i>
            Chat database tables are not installed.
        </div>
        <p class="mt-1">
            Run <code>php artisan migrate</code>, then clear Laravel caches.
        </p>
    </div>
@endif

<div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
    <div class="overflow-x-auto">
        <table class="min-w-[860px] w-full text-sm">
            <thead class="border-b border-slate-200 bg-slate-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-black uppercase text-slate-500">Customer</th>
                    <th class="px-4 py-3 text-left text-xs font-black uppercase text-slate-500">Contact</th>
                    <th class="px-4 py-3 text-left text-xs font-black uppercase text-slate-500">Unread</th>
                    <th class="px-4 py-3 text-left text-xs font-black uppercase text-slate-500">Status</th>
                    <th class="px-4 py-3 text-left text-xs font-black uppercase text-slate-500">Started</th>
                    <th class="px-4 py-3 text-right text-xs font-black uppercase text-slate-500">Action</th>
                </tr>
            </thead>

            <tbody class="divide-y divide-slate-100">
                @forelse($conversations as $conversation)
                    <tr class="{{ (int)$conversation->unread_count > 0 ? 'bg-amber-50/60' : 'hover:bg-slate-50' }}">
                        <td class="px-4 py-3">
                            <div class="font-bold text-slate-800">
                                {{ trim($conversation->first_name.' '.$conversation->last_name) }}
                            </div>
                        </td>

                        <td class="px-4 py-3 text-slate-600">
                            <div>{{ $conversation->email }}</div>
                            <div class="text-xs text-slate-400">{{ $conversation->phone }}</div>
                        </td>

                        <td class="px-4 py-3">
                            @if((int)$conversation->unread_count > 0)
                                <span class="inline-flex min-w-7 items-center justify-center rounded-full bg-red-600 px-2 py-1 text-xs font-black text-white">
                                    {{ $conversation->unread_count }}
                                </span>
                            @else
                                <span class="text-slate-300">0</span>
                            @endif
                        </td>

                        <td class="px-4 py-3">
                            <span class="rounded-full px-2.5 py-1 text-xs font-bold
                                {{ $conversation->status === 'open'
                                    ? 'bg-emerald-100 text-emerald-700'
                                    : 'bg-slate-100 text-slate-500' }}">
                                {{ ucfirst($conversation->status) }}
                            </span>
                        </td>

                        <td class="px-4 py-3 whitespace-nowrap text-slate-500">
                            {{ optional($conversation->created_at)->format('d M Y H:i') }}
                        </td>

                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('chat.show', $conversation->id) }}"
                               class="inline-flex items-center gap-1 rounded-lg border border-indigo-200 bg-indigo-50 px-3 py-1.5 text-xs font-bold text-indigo-700 hover:bg-indigo-100">
                                <i class="fas fa-comments"></i>
                                Open
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-12 text-center text-slate-400">
                            <i class="far fa-comments mb-2 block text-3xl"></i>
                            No support conversations found.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@include('partials.table-pagination', ['paginator' => $conversations])
@endsection
