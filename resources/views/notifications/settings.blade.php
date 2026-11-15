@extends('layouts.app')
@section('title', 'Notification settings')
@section('content')
<div class="mx-auto max-w-2xl">
    <h1 class="text-xl font-semibold text-slate-800">Notification settings</h1>
    <p class="mb-5 text-sm text-slate-500">Choose what you want to be told about. These settings apply on the website and in the mobile app.</p>

    @if(session('status'))<div class="mb-4 rounded bg-emerald-50 p-3 text-emerald-800">{{ session('status') }}</div>@endif

    <form method="POST" action="{{ route('notification-settings.update') }}" class="rounded-lg bg-white shadow">
        @csrf
        @method('PUT')
        @foreach(collect($options)->groupBy('channel') as $channel => $group)
            <div class="border-b px-5 pb-1 pt-4 text-xs font-semibold uppercase tracking-wider text-slate-500">
                {{ $channel === 'email' ? 'By email' : 'In the app and website' }}
            </div>
            @foreach($group as $option)
                <label class="flex cursor-pointer items-start gap-4 border-b px-5 py-4 hover:bg-slate-50">
                    <input type="checkbox" name="enabled[]" value="{{ $option['key'] }}" @checked($option['enabled'])
                           class="mt-1 h-5 w-5 rounded border-slate-300 text-teal-700">
                    <span>
                        <span class="block font-medium text-slate-800">{{ $option['label'] }}</span>
                        <span class="block text-sm text-slate-500">{{ $option['description'] }}</span>
                        @if($option['key'] === 'low_stock' && $low_stock_threshold !== null)
                            <span class="mt-2 flex items-center gap-2 text-sm text-slate-600">
                                Alert me when a product reaches
                                <input type="number" name="low_stock_threshold" min="0" max="100000" value="{{ $low_stock_threshold }}"
                                       class="w-24 rounded border-slate-300 py-1 text-sm" onclick="event.stopPropagation()">
                                or fewer
                            </span>
                        @endif
                    </span>
                </label>
            @endforeach
        @endforeach
        <div class="flex justify-end p-4">
            <button class="rounded bg-teal-700 px-5 py-2 text-sm font-semibold text-white hover:bg-teal-800">Save settings</button>
        </div>
    </form>
</div>
@endsection
