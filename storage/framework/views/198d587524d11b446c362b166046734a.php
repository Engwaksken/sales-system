<?php $__env->startSection('title', 'Edit NotificationsApp'); ?>
<?php $__env->startSection('content'); ?>
<h1 class="text-xl font-semibold text-slate-800 mb-4">Edit NotificationsApp</h1>
<?php if($errors->any()): ?>
<div class="mb-4 rounded-md bg-red-50 text-red-700 px-4 py-2 text-sm">
<ul><?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $e): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><li><?php echo e($e); ?></li><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></ul>
</div>
<?php endif; ?>
<form method="POST" action="<?php echo e(route('notifications_app.update', $notifications_app->id)); ?>" class="bg-white shadow rounded-lg p-6 space-y-4 max-w-2xl">
<?php echo csrf_field(); ?> <?php echo method_field('PUT'); ?>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Title</label>
  <input type="text" name="title" value="<?php echo e($notifications_app->title); ?>" class="w-full rounded-md border-slate-300 shadow-sm" required>
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Message</label>
  <textarea name="message" rows="3" class="w-full rounded-md border-slate-300 shadow-sm" required><?php echo e($notifications_app->message); ?></textarea>
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Type</label>
  <input type="text" name="type" value="<?php echo e($notifications_app->type); ?>" class="w-full rounded-md border-slate-300 shadow-sm" required>
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">User Id</label>
  <select name="user_id" class="w-full rounded-md border-slate-300 shadow-sm" >
    <option value="">-- Select User Id --</option>
    <?php $__currentLoopData = $user_options; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $opt): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
    <option value="<?php echo e($opt->id); ?>" <?php echo e(($notifications_app->user_id == $opt->id) ? 'selected' : ''); ?>><?php echo e($opt->name); ?></option>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
  </select>
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Created By</label>
  <select name="created_by" class="w-full rounded-md border-slate-300 shadow-sm" >
    <option value="">-- Select Created By --</option>
    <?php $__currentLoopData = $created_by_options; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $opt): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
    <option value="<?php echo e($opt->id); ?>" <?php echo e(($notifications_app->created_by == $opt->id) ? 'selected' : ''); ?>><?php echo e($opt->name); ?></option>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
  </select>
</div>
<div class="flex items-center gap-2">
  <input type="hidden" name="is_read" value="0">
  <input type="checkbox" name="is_read" value="1" <?php echo e($notifications_app->is_read ? 'checked' : ''); ?> class="rounded border-slate-300">
  <label class="text-sm font-medium text-slate-700">Is Read</label>
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Read At</label>
  <input type="datetime-local" name="read_at" value="<?php echo e($notifications_app->read_at); ?>" class="w-full rounded-md border-slate-300 shadow-sm" >
</div>
<div class="pt-2">
<button class="bg-indigo-600 text-white px-4 py-2 rounded-md text-sm hover:bg-indigo-700">Update</button>
<a href="<?php echo e(route('notifications_app.index')); ?>" class="ml-2 text-sm text-slate-500">Cancel</a>
</div>
</form>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /home/kemmytec/nsales/resources/views/notifications_app/edit.blade.php ENDPATH**/ ?>