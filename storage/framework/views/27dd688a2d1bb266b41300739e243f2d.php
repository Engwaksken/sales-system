<?php $__env->startSection('title', 'Bulk Upload'); ?>
<?php $__env->startSection('content'); ?>

<div class="mb-5">
    <h1 class="text-xl font-semibold text-slate-800">
        <i class="fa-solid fa-file-arrow-up mr-2 text-indigo-600"></i>
        Bulk Upload
    </h1>
    <p class="mt-1 text-sm text-slate-500">
        Download the NSales template first, enter your records, save as CSV, then upload it.
    </p>
</div>

<?php if(session('status')): ?>
    <div class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
        <i class="fa-solid fa-circle-check mr-1"></i>
        <?php echo e(session('status')); ?>

    </div>
<?php endif; ?>

<?php if(session('importErrors') && count(session('importErrors'))): ?>
    <div class="mb-4 max-h-52 overflow-y-auto rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
        <div class="mb-1 font-semibold">
            <i class="fa-solid fa-triangle-exclamation mr-1"></i>
            Rows skipped
        </div>

        <ul class="list-inside list-disc space-y-1">
            <?php $__currentLoopData = session('importErrors'); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $e): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <li><?php echo e($e); ?></li>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </ul>
    </div>
<?php endif; ?>

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
                href="<?php echo e(route('bulk-upload.templates.products')); ?>"
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
                Example: <code><?php echo e(now()->format('Y-m-d')); ?></code>.
            </div>

            <div class="mt-1">
                <strong>stock_date</strong> is the date the stock was received/uploaded.
                It is used for monthly supplier totals on Dashboard.
            </div>
        </div>

        <form
            method="POST"
            action="<?php echo e(route('bulk-upload.products')); ?>"
            enctype="multipart/form-data"
            class="space-y-3"
        >
            <?php echo csrf_field(); ?>

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

            <?php $__errorArgs = ['csv_file'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                <p class="text-xs font-medium text-red-600"><?php echo e($message); ?></p>
            <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>

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
                        <?php echo e($branches->pluck('name')->join(', ') ?: 'None'); ?>

                    </div>
                </div>

                <div>
                    <div class="text-xs font-semibold text-slate-600">Suppliers</div>
                    <div class="mt-1 text-xs text-slate-500">
                        <?php echo e($suppliers->pluck('name')->join(', ') ?: 'None'); ?>

                    </div>
                </div>

                <div>
                    <div class="text-xs font-semibold text-slate-600">Categories</div>
                    <div class="mt-1 text-xs text-slate-500">
                        <?php echo e($categories->pluck('category_name')->join(', ') ?: 'None'); ?>

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
                href="<?php echo e(route('bulk-upload.templates.customers')); ?>"
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
            action="<?php echo e(route('bulk-upload.customers')); ?>"
            enctype="multipart/form-data"
            class="space-y-3"
        >
            <?php echo csrf_field(); ?>

            <label class="block">
                <span class="mb-1 block text-sm font-semibold text-slate-700">
                    Default Branch
                </span>

                <select
                    name="default_branch_id"
                    class="w-full rounded-lg border-slate-300 text-sm"
                >
                    <option value="">No default branch</option>

                    <?php $__currentLoopData = $branches; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $branch): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($branch->id); ?>">
                            <?php echo e($branch->name); ?>

                        </option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
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
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /home/kemmytec/nsales/resources/views/bulk-upload/index.blade.php ENDPATH**/ ?>