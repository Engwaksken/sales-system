@extends('layouts.app')
@section('title', 'Edit NotificationsApp')
@section('content')
<h1 class="text-xl font-semibold text-slate-800 mb-4">Edit NotificationsApp</h1>
@if($errors->any())
<div class="mb-4 rounded-md bg-red-50 text-red-700 px-4 py-2 text-sm">
<ul>@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
</div>
@endif
<form method="POST" action="{{ route('notifications_app.update', $notifications_app->id) }}" class="bg-white shadow rounded-lg p-6 space-y-4 max-w-2xl">
@csrf @method('PUT')
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Title</label>
  <input type="text" name="title" value="{{ $notifications_app->title }}" class="w-full rounded-md border-slate-300 shadow-sm" required>
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Message</label>
  <textarea name="message" rows="3" class="w-full rounded-md border-slate-300 shadow-sm" required>{{ $notifications_app->message }}</textarea>
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Type</label>
  <input type="text" name="type" value="{{ $notifications_app->type }}" class="w-full rounded-md border-slate-300 shadow-sm" required>
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">User Id</label>
  <select name="user_id" class="w-full rounded-md border-slate-300 shadow-sm" >
    <option value="">-- Select User Id --</option>
    @foreach($user_options as $opt)
    <option value="{{ $opt->id }}" {{ ($notifications_app->user_id == $opt->id) ? 'selected' : '' }}>{{ $opt->name }}</option>
    @endforeach
  </select>
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Created By</label>
  <select name="created_by" class="w-full rounded-md border-slate-300 shadow-sm" >
    <option value="">-- Select Created By --</option>
    @foreach($created_by_options as $opt)
    <option value="{{ $opt->id }}" {{ ($notifications_app->created_by == $opt->id) ? 'selected' : '' }}>{{ $opt->name }}</option>
    @endforeach
  </select>
</div>
<div class="flex items-center gap-2">
  <input type="hidden" name="is_read" value="0">
  <input type="checkbox" name="is_read" value="1" {{ $notifications_app->is_read ? 'checked' : '' }} class="rounded border-slate-300">
  <label class="text-sm font-medium text-slate-700">Is Read</label>
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Read At</label>
  <input type="datetime-local" name="read_at" value="{{ $notifications_app->read_at }}" class="w-full rounded-md border-slate-300 shadow-sm" >
</div>
<div class="pt-2">
<button class="bg-indigo-600 text-white px-4 py-2 rounded-md text-sm hover:bg-indigo-700">Update</button>
<a href="{{ route('notifications_app.index') }}" class="ml-2 text-sm text-slate-500">Cancel</a>
</div>
</form>
@endsection
