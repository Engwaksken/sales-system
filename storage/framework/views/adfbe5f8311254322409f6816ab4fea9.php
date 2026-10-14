<?php $__env->startSection('title', 'Edit NavPage'); ?>
<?php $__env->startSection('content'); ?>
<h1 class="text-xl font-semibold text-slate-800 mb-4">Edit NavPage</h1>
<?php if($errors->any()): ?>
<div class="mb-4 rounded-md bg-red-50 text-red-700 px-4 py-2 text-sm">
<ul><?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $e): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><li><?php echo e($e); ?></li><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></ul>
</div>
<?php endif; ?>
<form method="POST" action="<?php echo e(route('nav_pages.update', $nav_page->id)); ?>" class="bg-white shadow rounded-lg p-6 space-y-4 max-w-2xl">
<?php echo csrf_field(); ?> <?php echo method_field('PUT'); ?>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Label</label>
  <input type="text" name="label" value="<?php echo e($nav_page->label); ?>" class="w-full rounded-md border-slate-300 shadow-sm" required>
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Url</label>
  <input type="text" name="url" value="<?php echo e($nav_page->url); ?>" class="w-full rounded-md border-slate-300 shadow-sm" required>
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Icon</label>
  <input type="text" name="icon" value="<?php echo e($nav_page->icon); ?>" class="w-full rounded-md border-slate-300 shadow-sm" required>
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Parent Id</label>
  <select name="parent_id" class="w-full rounded-md border-slate-300 shadow-sm" >
    <option value="">-- Select Parent Id --</option>
    <?php $__currentLoopData = $parent_options; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $opt): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
    <option value="<?php echo e($opt->id); ?>" <?php echo e(($nav_page->parent_id == $opt->id) ? 'selected' : ''); ?>><?php echo e($opt->label); ?></option>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
  </select>
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Sort Order</label>
  <input type="number" name="sort_order" value="<?php echo e($nav_page->sort_order); ?>" class="w-full rounded-md border-slate-300 shadow-sm" required>
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Permission Key</label>
  <input type="text" name="permission_key" value="<?php echo e($nav_page->permission_key); ?>" class="w-full rounded-md border-slate-300 shadow-sm" required>
</div>
<div class="flex items-center gap-2">
  <input type="hidden" name="is_active" value="0">
  <input type="checkbox" name="is_active" value="1" <?php echo e($nav_page->is_active ? 'checked' : ''); ?> class="rounded border-slate-300">
  <label class="text-sm font-medium text-slate-700">Is Active</label>
</div>
<div class="pt-2">
<button class="bg-indigo-600 text-white px-4 py-2 rounded-md text-sm hover:bg-indigo-700">Update</button>
<a href="<?php echo e(route('nav_pages.index')); ?>" class="ml-2 text-sm text-slate-500">Cancel</a>
</div>
</form>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /home/kemmytec/nsales/resources/views/nav_pages/edit.blade.php ENDPATH**/ ?>