@extends('layouts.app')
@section('title', 'EmailSetting Details')
@section('content')
<h1 class="text-xl font-semibold text-slate-800 mb-4">EmailSetting #{{ $email_setting->id }}</h1>
<div class="bg-white shadow rounded-lg p-6 max-w-2xl">
<div class='py-2 border-b border-slate-100'><span class='text-xs uppercase text-slate-400 block'>Smtp Host</span><span class='text-slate-800'>{{ $email_setting->smtp_host }}</span></div>
<div class='py-2 border-b border-slate-100'><span class='text-xs uppercase text-slate-400 block'>Smtp Port</span><span class='text-slate-800'>{{ $email_setting->smtp_port }}</span></div>
<div class='py-2 border-b border-slate-100'><span class='text-xs uppercase text-slate-400 block'>Smtp User</span><span class='text-slate-800'>{{ $email_setting->smtp_user }}</span></div>
<div class='py-2 border-b border-slate-100'><span class='text-xs uppercase text-slate-400 block'>Smtp Password</span><span class='text-slate-800'>{{ $email_setting->smtp_password }}</span></div>
<div class='py-2 border-b border-slate-100'><span class='text-xs uppercase text-slate-400 block'>Smtp Secure</span><span class='text-slate-800'>{{ $email_setting->smtp_secure }}</span></div>
<div class='py-2 border-b border-slate-100'><span class='text-xs uppercase text-slate-400 block'>From Email</span><span class='text-slate-800'>{{ $email_setting->from_email }}</span></div>
<div class='py-2 border-b border-slate-100'><span class='text-xs uppercase text-slate-400 block'>From Name</span><span class='text-slate-800'>{{ $email_setting->from_name }}</span></div>
<div class='py-2 border-b border-slate-100'><span class='text-xs uppercase text-slate-400 block'>Created By</span><span class='text-slate-800'>{{ $email_setting->createdBy?->name ?? '—' }}</span></div>
<div class='py-2 border-b border-slate-100'><span class='text-xs uppercase text-slate-400 block'>Updated By</span><span class='text-slate-800'>{{ $email_setting->updatedBy?->name ?? '—' }}</span></div>
<div class='py-2 border-b border-slate-100'><span class='text-xs uppercase text-slate-400 block'>Created At</span><span class='text-slate-800'>{{ $email_setting->created_at?->format('d M Y H:i') ?? '—' }}</span></div>
<div class='py-2 border-b border-slate-100'><span class='text-xs uppercase text-slate-400 block'>Updated At</span><span class='text-slate-800'>{{ $email_setting->updated_at?->format('d M Y H:i') ?? '—' }}</span></div>
</div>
<a href="{{ route('email_settings.index') }}" class="inline-block mt-4 text-sm text-slate-500">&larr; Back to list</a>
@endsection
