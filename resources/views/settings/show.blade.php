@extends('layouts.app')
@section('title', 'Setting Details')
@section('content')
<h1 class="text-xl font-semibold text-slate-800 mb-4">Setting #{{ $setting->id }}</h1>
<div class="bg-white shadow rounded-lg p-6 max-w-2xl">
<div class='py-2 border-b border-slate-100'><span class='text-xs uppercase text-slate-400 block'>Setting Key</span><span class='text-slate-800'>{{ $setting->setting_key }}</span></div>
<div class='py-2 border-b border-slate-100'><span class='text-xs uppercase text-slate-400 block'>Setting Value</span><span class='text-slate-800'>{{ $setting->setting_value }}</span></div>
</div>
<a href="{{ route('settings.index') }}" class="inline-block mt-4 text-sm text-slate-500">&larr; Back to list</a>
@endsection
