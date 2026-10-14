@extends('layouts.app')
@section('title', 'Slide Details')
@section('content')
<h1 class="text-xl font-semibold text-slate-800 mb-4">Slide #{{ $slide->id }}</h1>
<div class="bg-white shadow rounded-lg p-6 max-w-2xl">
<div class='py-2 border-b border-slate-100'><span class='text-xs uppercase text-slate-400 block'>Title</span><span class='text-slate-800'>{{ $slide->title }}</span></div>
<div class='py-2 border-b border-slate-100'><span class='text-xs uppercase text-slate-400 block'>Subtitle</span><span class='text-slate-800'>{{ $slide->subtitle }}</span></div>
<div class='py-2 border-b border-slate-100'><span class='text-xs uppercase text-slate-400 block'>Badge Text</span><span class='text-slate-800'>{{ $slide->badge_text }}</span></div>
<div class='py-2 border-b border-slate-100'><span class='text-xs uppercase text-slate-400 block'>Image Url</span><span class='text-slate-800'>{{ $slide->image_url }}</span></div>
<div class='py-2 border-b border-slate-100'><span class='text-xs uppercase text-slate-400 block'>Cta Label</span><span class='text-slate-800'>{{ $slide->cta_label }}</span></div>
<div class='py-2 border-b border-slate-100'><span class='text-xs uppercase text-slate-400 block'>Cta Link</span><span class='text-slate-800'>{{ $slide->cta_link }}</span></div>
<div class='py-2 border-b border-slate-100'><span class='text-xs uppercase text-slate-400 block'>Bg Color</span><span class='text-slate-800'>{{ $slide->bg_color }}</span></div>
<div class='py-2 border-b border-slate-100'><span class='text-xs uppercase text-slate-400 block'>Sort Order</span><span class='text-slate-800'>{{ $slide->sort_order }}</span></div>
<div class='py-2 border-b border-slate-100'><span class='text-xs uppercase text-slate-400 block'>Is Active</span><span class='text-slate-800'>{{ $slide->is_active ? 'Yes' : 'No' }}</span></div>
<div class='py-2 border-b border-slate-100'><span class='text-xs uppercase text-slate-400 block'>Created By</span><span class='text-slate-800'>{{ $slide->createdBy?->name ?? '—' }}</span></div>
<div class='py-2 border-b border-slate-100'><span class='text-xs uppercase text-slate-400 block'>Updated By</span><span class='text-slate-800'>{{ $slide->updatedBy?->name ?? '—' }}</span></div>
<div class='py-2 border-b border-slate-100'><span class='text-xs uppercase text-slate-400 block'>Created At</span><span class='text-slate-800'>{{ $slide->created_at?->format('d M Y H:i') ?? '—' }}</span></div>
<div class='py-2 border-b border-slate-100'><span class='text-xs uppercase text-slate-400 block'>Updated At</span><span class='text-slate-800'>{{ $slide->updated_at?->format('d M Y H:i') ?? '—' }}</span></div>
</div>
<a href="{{ route('slides.index') }}" class="inline-block mt-4 text-sm text-slate-500">&larr; Back to list</a>
@endsection
