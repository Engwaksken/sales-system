<?php $__env->startSection('title', 'Products'); ?>
<?php $__env->startSection('content'); ?>
<div class="flex items-center justify-between mb-4">
    <h1 class="text-xl font-semibold text-slate-800 flex items-center gap-2"><i class="fa-solid fa-box text-slate-400"></i> Products</h1>
    <div class="flex items-center gap-2">
        <a href="<?php echo e(route('bulk-upload.index')); ?>" class="text-sm text-slate-500 hover:text-slate-700"><i class="fa-solid fa-file-arrow-up"></i> Bulk Upload</a>
        <button type="button" onclick="openCreateModal('products-modal', 'products-form', '<?php echo e(route('products.store')); ?>')" class="bg-indigo-600 text-white px-4 py-2 rounded-md text-sm hover:bg-indigo-700 flex items-center gap-2"><i class="fa-solid fa-plus"></i> New Product</button>
    </div>
</div>
<div class="grid grid-cols-2 md:grid-cols-2 gap-4 mb-6">
<?php echo $__env->make('partials.stat-card', ['icon' => 'fa-box', 'color' => 'indigo', 'label' => 'Total Products', 'value' => $items->total()], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php echo $__env->make('partials.stat-card', ['icon' => 'fa-circle-check', 'color' => 'emerald', 'label' => 'Active', 'value' => $stat1], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
</div>
<?php($isMoney = false)
@include('partials.period-stats', ['periodStats' => $periodStats, 'isMoney' => $isMoney])
@include('partials.business-filter-bar', [
    'searchPlaceholder' => 'Search products…',
    'clearRoute' => route('products.index'),
])
@include('partials.branch-supplier-stat-cards')
<details class="mb-4 bg-white rounded-lg shadow">
  <summary class="cursor-pointer px-4 py-2 text-sm text-slate-600 flex items-center gap-2 list-none">
    <i class="fa-solid fa-file-export"></i> Export <i class="fa-solid fa-chevron-down text-xs ml-1"></i>
  </summary>
  <form method="GET" action="{{ route('products.export.csv') }}" class="px-4 pb-4 pt-2 border-t border-slate-100">
    <div class="text-xs text-slate-400 mb-2">Select the columns to include:</div>
    <div class="grid grid-cols-2 md:grid-cols-4 gap-2 mb-3 text-sm">
<label class="flex items-center gap-2"><input type="checkbox" name="columns[]" value="name" checked class="rounded border-slate-300"> Name</label>
<label class="flex items-center gap-2"><input type="checkbox" name="columns[]" value="sku" checked class="rounded border-slate-300"> Sku</label>
<label class="flex items-center gap-2"><input type="checkbox" name="columns[]" value="branch_id" checked class="rounded border-slate-300"> Branch</label>
<label class="flex items-center gap-2"><input type="checkbox" name="columns[]" value="category_id" checked class="rounded border-slate-300"> Category</label>
<label class="flex items-center gap-2"><input type="checkbox" name="columns[]" value="description" checked class="rounded border-slate-300"> Description</label>
<label class="flex items-center gap-2"><input type="checkbox" name="columns[]" value="image_url" checked class="rounded border-slate-300"> Image Url</label>
<label class="flex items-center gap-2"><input type="checkbox" name="columns[]" value="is_active" checked class="rounded border-slate-300"> Is Active</label>
<label class="flex items-center gap-2"><input type="checkbox" name="columns[]" value="created_by" checked class="rounded border-slate-300"> Createdby</label>
<label class="flex items-center gap-2"><input type="checkbox" name="columns[]" value="updated_by" checked class="rounded border-slate-300"> Updatedby</label>
<label class="flex items-center gap-2"><input type="checkbox" name="columns[]" value="created_at" checked class="rounded border-slate-300"> Created At</label>
<label class="flex items-center gap-2"><input type="checkbox" name="columns[]" value="updated_at" checked class="rounded border-slate-300"> Updated At</label>
    </div>
    <input type="hidden" name="period" value="{{ request('period', 'all') }}">
    <input type="hidden" name="q" value="{{ request('q', '') }}">
    <input type="hidden" name="from" value="{{ request('from', '') }}">
    <input type="hidden" name="to" value="{{ request('to', '') }}">
    <input type="hidden" name="branch_id" value="{{ request('branch_id', '') }}">
    <input type="hidden" name="supplier_id" value="{{ request('supplier_id', '') }}">
    <div class="flex items-center gap-2">
      <button formaction="{{ route('products.export.csv') }}" class="bg-indigo-600 text-white px-4 py-1.5 rounded-md text-sm flex items-center gap-2"><i class="fa-solid fa-file-csv"></i> Export CSV</button>
      <button formaction="{{ route('products.export.pdf') }}" class="bg-slate-600 text-white px-4 py-1.5 rounded-md text-sm flex items-center gap-2"><i class="fa-solid fa-file-pdf"></i> Export PDF</button>
    </div>
  </form>
</details>
@if(session('status'))
<div class="mb-4 rounded-md bg-emerald-50 text-emerald-700 px-4 py-2 text-sm">{{ session('status') }}</div>
@endif
@if($errors->any())
<div class="mb-4 rounded-md bg-red-50 text-red-700 px-4 py-2 text-sm">
<ul>@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
</div>
@endif
<div class="bg-white shadow rounded-lg overflow-x-auto">
<table class="min-w-full divide-y divide-slate-200">
<thead class="bg-slate-50"><tr><th class='px-4 py-2 text-left text-xs font-semibold text-slate-500 uppercase'>Name</th><th class='px-4 py-2 text-left text-xs font-semibold text-slate-500 uppercase'>Sku</th><th class='px-4 py-2 text-left text-xs font-semibold text-slate-500 uppercase'>Image</th><th class='px-4 py-2 text-left text-xs font-semibold text-slate-500 uppercase'>Category</th><th class='px-4 py-2 text-left text-xs font-semibold text-slate-500 uppercase'>Branch</th><th class='px-4 py-2 text-left text-xs font-semibold text-slate-500 uppercase'>Is Active</th><th class='px-4 py-2 text-left text-xs font-semibold text-slate-500 uppercase'>Createdby</th><th class='px-4 py-2 text-left text-xs font-semibold text-slate-500 uppercase'>Updatedby</th><th class='px-4 py-2 text-left text-xs font-semibold text-slate-500 uppercase'>Created At</th><th class='px-4 py-2 text-left text-xs font-semibold text-slate-500 uppercase'>Updated At</th><th class="px-4 py-2"></th></tr></thead>
<tbody class="divide-y divide-slate-100">
@forelse($items as $row)
<tr><td class='px-4 py-2 text-sm text-slate-700'>{{ $row->name }}</td><td class='px-4 py-2 text-sm text-slate-700'>{{ $row->sku }}</td>
<td class="px-4 py-2">
    @php
        $imagePath = trim((string)($row->image_url ?? ''));
        $imageSrc = $imagePath === ''
            ? null
            : (\Illuminate\Support\Str::startsWith($imagePath, ['http://','https://'])
                ? $imagePath
                : asset('storage/'.ltrim($imagePath, '/')));
    ?>

    <?php if($imageSrc): ?>
        <button type="button"
                class="product-image-preview group relative block h-14 w-14 overflow-hidden rounded-lg border border-slate-200 bg-slate-50"
                data-src="<?php echo e($imageSrc); ?>"
                data-name="<?php echo e($row->name); ?>">
            <img src="<?php echo e($imageSrc); ?>"
                 alt="<?php echo e($row->name); ?>"
                 class="h-full w-full object-cover transition group-hover:scale-105"
                 onerror="this.closest('button').style.display='none'">
        </button>
    <?php else: ?>
        <div class="grid h-14 w-14 place-items-center rounded-lg border border-dashed border-slate-300 bg-slate-50 text-slate-300">
            <i class="fas fa-image"></i>
        </div>
    <?php endif; ?>
</td><td class='px-4 py-2 text-sm text-slate-700'><?php echo e($row->category?->category_name ?? '—'); ?></td><td class='px-4 py-2 text-sm text-slate-700'><?php echo e($row->branch?->name ?? '—'); ?></td><td class='px-4 py-2 text-sm text-slate-700'><?php echo e($row->is_active ? 'Yes' : 'No'); ?></td><td class='px-4 py-2 text-sm text-slate-700'><?php echo e($row->createdBy?->name ?? '—'); ?></td><td class='px-4 py-2 text-sm text-slate-700'><?php echo e($row->updatedBy?->name ?? '—'); ?></td><td class='px-4 py-2 text-sm text-slate-500'><?php echo e($row->created_at?->format('d M Y H:i') ?? '—'); ?></td><td class='px-4 py-2 text-sm text-slate-500'><?php echo e($row->updated_at?->format('d M Y H:i') ?? '—'); ?></td><td class="px-4 py-2 text-right text-sm space-x-3 whitespace-nowrap">
<button type="button" title="View" onclick='openViewModal("products-modal", "products-form", <?php echo json_encode($row, 15, 512) ?>)' class="text-slate-500 hover:text-slate-700"><i class="fa-solid fa-eye"></i></button>
<button type="button" title="Edit" onclick='openEditModal("products-modal", "products-form", "<?php echo e(route('products.update', $row->id)); ?>", <?php echo json_encode($row, 15, 512) ?>)' class="text-indigo-600 hover:text-indigo-800"><i class="fa-solid fa-pen"></i></button>
<form method="POST" action="<?php echo e(route('products.destroy', $row->id)); ?>" class="inline" onsubmit="return confirm('Delete this record?')">
<?php echo csrf_field(); ?> <?php echo method_field('DELETE'); ?>
<button title="Delete" class="text-red-600 hover:text-red-800"><i class="fa-solid fa-trash"></i></button>
</form>
</td></tr>
<?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_0): ?>
<tr><td colspan="11" class="px-4 py-6 text-center text-sm text-slate-400">No records yet.</td></tr>
<?php endif; ?>
</tbody>
</table>
</div>
<div class="mt-4"><?php echo e($items->links()); ?></div>

<div id="products-modal" class="fixed inset-0 bg-black/40 hidden items-center justify-center z-30 p-4">
  <div class="bg-white rounded-xl max-w-2xl w-full max-h-[90vh] overflow-y-auto p-6">
    <div class="flex justify-between items-center mb-4">
      <h2 id="products-modal-title" class="font-semibold text-lg flex items-center gap-2"><i class="fa-solid fa-box text-indigo-500"></i> New Product</h2>
      <button type="button" onclick="closeModal('products-modal')" class="text-slate-400 text-xl leading-none">&times;</button>
    </div>
    <form id="products-form" method="POST" action="<?php echo e(route('products.store')); ?>" class="space-y-4" enctype="multipart/form-data">
      <?php echo csrf_field(); ?>
      <input type="hidden" name="_method" value="POST">
      <div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Name</label>
  <input type="text" name="name" class="w-full rounded-md border-slate-300 shadow-sm" required>
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Sku</label>
  <input type="text" name="sku" class="w-full rounded-md border-slate-300 shadow-sm" >
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Branch Name</label>
  <select name="branch_id" class="w-full rounded-md border-slate-300 shadow-sm" >
    <option value="">-- Select Branch Name --</option>
    <?php $__currentLoopData = $branch_options; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $opt): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
    <option value="<?php echo e($opt->id); ?>"><?php echo e($opt->name); ?></option>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
  </select>
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Category Name</label>
  <select name="category_id" class="w-full rounded-md border-slate-300 shadow-sm" >
    <option value="">-- Select Category Name --</option>
    <?php $__currentLoopData = $category_options; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $opt): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
    <option value="<?php echo e($opt->id); ?>"><?php echo e($opt->category_name); ?></option>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
  </select>
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Description</label>
  <textarea name="description" rows="3" class="w-full rounded-md border-slate-300 shadow-sm" ></textarea>
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Product Image</label>
  <input type="file" name="image" accept="image/*" class="w-full rounded-md border-slate-300 shadow-sm text-sm">
  <p class="text-xs text-slate-400 mt-1">Leave blank to keep the current image when editing.</p>
  <div id="product-current-image-wrap" class="mt-3 hidden">
      <div class="mb-1 text-xs font-semibold text-slate-500">Current Image Preview</div>
      <img id="product-current-image"
           src=""
           alt="Current product image"
           class="h-28 w-28 rounded-xl border border-slate-200 object-cover">
  </div>
</div>
<div class="flex items-center gap-2">
  <input type="hidden" name="is_active" value="0">
  <input type="checkbox" name="is_active" value="1" class="rounded border-slate-300">
  <label class="text-sm font-medium text-slate-700">Is Active</label>
</div>
      <div class="pt-2 flex items-center gap-2">
        <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded-md text-sm hover:bg-indigo-700 flex items-center gap-2"><i class="fa-solid fa-check"></i> Save</button>
        <button type="button" onclick="closeModal('products-modal')" class="text-sm text-slate-500">Cancel</button>
      </div>
    </form>
  </div>
</div>
<?php $__env->stopSection(); ?>

<div id="product-image-modal"
     class="fixed inset-0 z-[100] hidden items-center justify-center bg-slate-950/70 p-4">
    <div class="relative max-h-[90vh] max-w-4xl overflow-hidden rounded-2xl bg-white p-3 shadow-2xl">
        <button type="button"
                id="productImageClose"
                class="absolute right-4 top-4 z-10 grid h-9 w-9 place-items-center rounded-full bg-black/60 text-white">
            <i class="fas fa-xmark"></i>
        </button>
        <img id="productImageLarge" src="" alt=""
             class="max-h-[82vh] max-w-full rounded-xl object-contain">
        <div id="productImageCaption" class="px-2 py-2 text-center text-sm font-bold text-slate-700"></div>
    </div>
</div>

<?php $__env->startPush('scripts'); ?>
<script>
(function () {
    const modal = document.getElementById('product-image-modal');
    const image = document.getElementById('productImageLarge');
    const caption = document.getElementById('productImageCaption');

    document.querySelectorAll('.product-image-preview').forEach(button => {
        button.addEventListener('click', () => {
            image.src = button.dataset.src || '';
            image.alt = button.dataset.name || 'Product image';
            caption.textContent = button.dataset.name || 'Product Image';
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        });
    });

    function closeImage() {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        image.src = '';
    }

    document.getElementById('productImageClose')?.addEventListener('click', closeImage);
    modal?.addEventListener('click', event => {
        if (event.target === modal) closeImage();
    });
})();
</script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /home/kemmytec/nsales/resources/views/products/index.blade.php ENDPATH**/ ?>