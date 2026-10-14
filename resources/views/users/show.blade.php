@extends('layouts.app')
@section('title', 'User Details')
@section('content')
<h1 class="text-xl font-semibold text-slate-800 mb-4">User #{{ $user->id }}</h1>
<div class="bg-white shadow rounded-lg p-6 max-w-2xl">
<div class='py-2 border-b border-slate-100'><span class='text-xs uppercase text-slate-400 block'>Name</span><span class='text-slate-800'>{{ $user->name }}</span></div>
<div class='py-2 border-b border-slate-100'><span class='text-xs uppercase text-slate-400 block'>Email</span><span class='text-slate-800'>{{ $user->email }}</span></div>
<div class='py-2 border-b border-slate-100'><span class='text-xs uppercase text-slate-400 block'>Password</span><span class='text-slate-800'>{{ $user->password }}</span></div>
<div class='py-2 border-b border-slate-100'><span class='text-xs uppercase text-slate-400 block'>Role</span><span class='text-slate-800'>{{ $user->role }}</span></div>
<div class='py-2 border-b border-slate-100'><span class='text-xs uppercase text-slate-400 block'>Branch Id</span><span class='text-slate-800'>{{ $user->branch?->name ?? '—' }}</span></div>
<div class='py-2 border-b border-slate-100'><span class='text-xs uppercase text-slate-400 block'>Profile Pic</span><span class='text-slate-800'>{{ $user->profile_pic }}</span></div>
<div class='py-2 border-b border-slate-100'><span class='text-xs uppercase text-slate-400 block'>Verification Code</span><span class='text-slate-800'>{{ $user->verification_code }}</span></div>
<div class='py-2 border-b border-slate-100'><span class='text-xs uppercase text-slate-400 block'>Code Expires</span><span class='text-slate-800'>{{ $user->code_expires }}</span></div>
<div class='py-2 border-b border-slate-100'><span class='text-xs uppercase text-slate-400 block'>Remember Token</span><span class='text-slate-800'>{{ $user->remember_token }}</span></div>
<div class='py-2 border-b border-slate-100'><span class='text-xs uppercase text-slate-400 block'>Created By</span><span class='text-slate-800'>{{ $user->createdBy?->name ?? '—' }}</span></div>
<div class='py-2 border-b border-slate-100'><span class='text-xs uppercase text-slate-400 block'>Updated By</span><span class='text-slate-800'>{{ $user->updatedBy?->name ?? '—' }}</span></div>
<div class='py-2 border-b border-slate-100'><span class='text-xs uppercase text-slate-400 block'>Created At</span><span class='text-slate-800'>{{ $user->created_at?->format('d M Y H:i') ?? '—' }}</span></div>
<div class='py-2 border-b border-slate-100'><span class='text-xs uppercase text-slate-400 block'>Updated At</span><span class='text-slate-800'>{{ $user->updated_at?->format('d M Y H:i') ?? '—' }}</span></div>
</div>
<a href="{{ route('users.index') }}" class="inline-block mt-4 text-sm text-slate-500">&larr; Back to list</a>
@endsection
