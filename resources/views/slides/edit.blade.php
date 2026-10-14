@extends('layouts.app')
@section('title', 'Edit Slide')
@section('content')
<h1 class="text-xl font-semibold text-slate-800 mb-4">Edit Slide</h1>
@if($errors->any())
<div class="mb-4 rounded-md bg-red-50 text-red-700 px-4 py-2 text-sm">
<ul>@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
</div>
@endif
<form method="POST" action="{{ route('slides.update', $slide->id) }}" class="bg-white shadow rounded-lg p-6 space-y-4 max-w-2xl">
@csrf @method('PUT')
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Title</label>
  <input type="text" name="title" value="{{ $slide->title }}" class="w-full rounded-md border-slate-300 shadow-sm" required>
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Subtitle</label>
  <textarea name="subtitle" rows="3" class="w-full rounded-md border-slate-300 shadow-sm" >{{ $slide->subtitle }}</textarea>
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Badge Text</label>
  <input type="text" name="badge_text" value="{{ $slide->badge_text }}" class="w-full rounded-md border-slate-300 shadow-sm" required>
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Image Url</label>
  <input type="text" name="image_url" value="{{ $slide->image_url }}" class="w-full rounded-md border-slate-300 shadow-sm" required>
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Cta Label</label>
  <input type="text" name="cta_label" value="{{ $slide->cta_label }}" class="w-full rounded-md border-slate-300 shadow-sm" required>
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Cta Link</label>
  <input type="text" name="cta_link" value="{{ $slide->cta_link }}" class="w-full rounded-md border-slate-300 shadow-sm" required>
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Bg Color</label>
  <input type="text" name="bg_color" value="{{ $slide->bg_color }}" class="w-full rounded-md border-slate-300 shadow-sm" required>
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Sort Order</label>
  <input type="number" name="sort_order" value="{{ $slide->sort_order }}" class="w-full rounded-md border-slate-300 shadow-sm" required>
</div>
<div class="flex items-center gap-2">
  <input type="hidden" name="is_active" value="0">
  <input type="checkbox" name="is_active" value="1" {{ $slide->is_active ? 'checked' : '' }} class="rounded border-slate-300">
  <label class="text-sm font-medium text-slate-700">Is Active</label>
</div>
<div class="pt-2">
<button class="bg-indigo-600 text-white px-4 py-2 rounded-md text-sm hover:bg-indigo-700">Update</button>
<a href="{{ route('slides.index') }}" class="ml-2 text-sm text-slate-500">Cancel</a>
</div>
</form>
@endsection
