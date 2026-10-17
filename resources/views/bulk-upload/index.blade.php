@extends('layouts.app')
@section('title', 'Bulk Upload')
@section('content')

<div class="mb-5">
    <h1 class="text-xl font-semibold text-slate-800">
        <i class="fa-solid fa-file-arrow-up mr-2 text-indigo-600"></i>
        Bulk Upload
    </h1>
    <p class="mt-1 text-sm text-slate-500">
        Download the NSales template first, enter your records, save as CSV, then upload it.
    </p>
</div>

@if (session('status'))
    <div data-flash class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
        <i class="fa-solid fa-circle-check mr-1"></i>
        {{ session('status') }}
    </div>
@endif

@if (session('importErrors') && count(session('importErrors')))
    <div class="mb-4 max-h-52 overflow-y-auto rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
        <div class="mb-1 font-semibold">
            <i class="fa-solid fa-triangle-exclamation mr-1"></i>
            Rows skipped
        </div>

        <ul class="list-inside list-disc space-y-1">
            @foreach (session('importErrors') as $e)
                <li>{{ $e }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="grid gap-6 xl:grid-cols-2">
    <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="mb-4 flex items-start justify-between gap-4">
            <div>
                <h2 class="font-semibold text-slate-800">
                    <i class="fa-solid fa-boxes-stacked mr-1 text-indigo-600"></i>
                    Import Products & Stock
                </h2>
                <p class="mt-1 text-sm text-slate-500">
                    Product details and opening/current stock can be uploaded in the same CSV.
                </p>
            </div>

            <a
                href="{{ route('bulk-upload.templates.products') }}"
                class="inline-flex shrink-0 items-center gap-2 rounded-lg bg-emerald-600 px-3 py-2 text-xs font-bold text-white hover:bg-emerald-700"
            >
                <i class="fa-solid fa-download"></i>
                Download Template
            </a>
        </div>

        <div class="mb-4 rounded-lg bg-slate-50 p-3 text-xs leading-5 text-slate-600">
            <div class="font-semibold text-slate-700">Template columns</div>
            <div class="mt-1">
                <code>product_name</code>,
                <code>category_name</code>,
                <code>sku</code>,
                <code>branch_name</code>,
                <code>supplier_name</code>,
                <code>quantity</code>,
                <code>unit_type</code>,
                <code>cost_price</code>,
                <code>selling_price</code>,
                <code>expiry_date</code>,
                <code>stock_date</code>,
                <code>description</code>,
                <code>image_url</code>.
            </div>

            <div class="mt-2">
                <strong>Date format:</strong> YYYY-MM-DD.
                Example: <code>{{ now()->format('Y-m-d') }}</code>.
            </div>

            <div class="mt-1">
                <strong>stock_date</strong> is the date the stock was received/uploaded.
                It is used for monthly supplier totals on Dashboard.
            </div>
        </div>

        <form
            method="POST"
            action="{{ route('bulk-upload.products') }}"
            enctype="multipart/form-data"
            class="space-y-3"
        >
            @csrf

            <label class="block">
                <span class="mb-1 block text-sm font-semibold text-slate-700">
                    Select completed product template
                </span>

                <input
                    type="file"
                    name="csv_file"
                    accept=".csv,text/csv"
                    required
                    class="block w-full rounded-lg border border-slate-300 bg-white p-2 text-sm"
                >
            </label>

            @error('csv_file')
                <p class="text-xs font-medium text-red-600">{{ $message }}</p>
            @enderror

            <button
                class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-bold text-white hover:bg-indigo-700"
            >
                <i class="fa-solid fa-cloud-arrow-up"></i>
                Upload Products CSV
            </button>
        </form>

        <div class="mt-5 border-t border-slate-100 pt-4">
            <div class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                Existing NSales names
            </div>

            <div class="mt-2 grid gap-3 sm:grid-cols-3">
                <div>
                    <div class="text-xs font-semibold text-slate-600">Branches</div>
                    <div class="mt-1 text-xs text-slate-500">
                        {{ $branches->pluck('name')->join(', ') ?: 'None' }}
                    </div>
                </div>

                <div>
                    <div class="text-xs font-semibold text-slate-600">Suppliers</div>
                    <div class="mt-1 text-xs text-slate-500">
                        {{ $suppliers->pluck('name')->join(', ') ?: 'None' }}
                    </div>
                </div>

                <div>
                    <div class="text-xs font-semibold text-slate-600">Categories</div>
                    <div class="mt-1 text-xs text-slate-500">
                        {{ $categories->pluck('category_name')->join(', ') ?: 'None' }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="mb-4 flex items-start justify-between gap-4">
            <div>
                <h2 class="font-semibold text-slate-800">
                    <i class="fa-solid fa-users mr-1 text-sky-600"></i>
                    Import Customers
                </h2>
                <p class="mt-1 text-sm text-slate-500">
                    Download the customer template and keep its first four instruction/header rows.
                </p>
            </div>

            <a
                href="{{ route('bulk-upload.templates.customers') }}"
                class="inline-flex shrink-0 items-center gap-2 rounded-lg bg-emerald-600 px-3 py-2 text-xs font-bold text-white hover:bg-emerald-700"
            >
                <i class="fa-solid fa-download"></i>
                Download Template
            </a>
        </div>

        <div class="mb-4 rounded-lg bg-slate-50 p-3 text-xs leading-5 text-slate-600">
            Customer columns:
            <code>name</code>,
            <code>branch_name</code>,
            <code>phone</code>,
            <code>email</code>,
            <code>address</code>.
        </div>

        <form
            method="POST"
            action="{{ route('bulk-upload.customers') }}"
            enctype="multipart/form-data"
            class="space-y-3"
        >
            @csrf

            <label class="block">
                <span class="mb-1 block text-sm font-semibold text-slate-700">
                    Default Branch
                </span>

                <select
                    name="default_branch_id"
                    class="w-full rounded-lg border-slate-300 text-sm"
                >
                    <option value="">No default branch</option>

                    @foreach ($branches as $branch)
                        <option value="{{ $branch->id }}">
                            {{ $branch->name }}
                        </option>
                    @endforeach
                </select>
            </label>

            <label class="block">
                <span class="mb-1 block text-sm font-semibold text-slate-700">
                    Select completed customer template
                </span>

                <input
                    type="file"
                    name="csv_file"
                    accept=".csv,text/csv"
                    required
                    class="block w-full rounded-lg border border-slate-300 bg-white p-2 text-sm"
                >
            </label>

            <button
                class="inline-flex items-center gap-2 rounded-lg bg-sky-600 px-4 py-2.5 text-sm font-bold text-white hover:bg-sky-700"
            >
                <i class="fa-solid fa-cloud-arrow-up"></i>
                Upload Customers CSV
            </button>
        </form>
    </div>
</div>
@endsection
