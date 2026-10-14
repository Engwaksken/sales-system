<?php $__env->startSection('title', 'Role Permissions'); ?>
<?php $__env->startSection('content'); ?>
<h1 class="text-xl font-semibold text-slate-800 mb-1 flex items-center gap-2">
    <i class="fa-solid fa-user-shield text-slate-400"></i> Role Permissions
</h1>
<p class="text-sm text-slate-500 mb-4">
    Tick a box to grant that role the permission. <strong>Admin</strong> isn't shown here —
    it always has full access regardless of these settings.
</p>

<?php if(session('status')): ?>
<div class="mb-4 rounded-md bg-emerald-50 text-emerald-700 px-4 py-2 text-sm"><?php echo e(session('status')); ?></div>
<?php endif; ?>

<form method="POST" action="<?php echo e(route('role_permissions.update')); ?>">
    <?php echo csrf_field(); ?>
    <?php echo method_field('PUT'); ?>

    <div class="bg-white shadow rounded-lg overflow-hidden">
        <div class="flex border-b border-slate-200 overflow-x-auto">
            <?php $__currentLoopData = $permissionGroups; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $category => $permissions): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <button type="button"
                    data-tabgroup="roleperms" data-tab="<?php echo e(str($category)->slug()); ?>"
                    onclick="switchTab('roleperms', '<?php echo e(str($category)->slug()); ?>')"
                    class="px-5 py-3 text-sm font-medium border-b-2 whitespace-nowrap <?php echo e($loop->first ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-slate-500'); ?> hover:text-indigo-600">
                <?php echo e($category); ?>

            </button>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>

        <?php $__currentLoopData = $permissionGroups; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $category => $permissions): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <div data-tabpanel="roleperms" data-tab="<?php echo e(str($category)->slug()); ?>" class="<?php echo e($loop->first ? '' : 'hidden'); ?>">
            <table class="min-w-full divide-y divide-slate-100">
                <thead>
                    <tr>
                        <th class="px-4 py-2 text-left text-xs font-semibold text-slate-500 uppercase">Permission</th>
                        <?php $__currentLoopData = $roles; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $role): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <th class="px-4 py-2 text-center text-xs font-semibold text-slate-500 uppercase"><?php echo e(ucfirst($role)); ?></th>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    <?php $__currentLoopData = $permissions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $permission): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <tr>
                        <td class="px-4 py-2 text-sm text-slate-700">
                            <?php echo e($permission->label); ?>

                            <?php if($permission->description): ?>
                            <div class="text-xs text-slate-400"><?php echo e($permission->description); ?></div>
                            <?php endif; ?>
                        </td>
                        <?php $__currentLoopData = $roles; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $role): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <td class="px-4 py-2 text-center">
                            <input type="checkbox"
                                   name="permissions[<?php echo e($role); ?>][]"
                                   value="<?php echo e($permission->permission_key); ?>"
                                   <?php echo e(in_array($permission->permission_key, $assigned[$role] ?? []) ? 'checked' : ''); ?>

                                   class="rounded border-slate-300 w-4 h-4">
                        </td>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </tbody>
            </table>
        </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </div>

    <button type="submit" class="mt-4 bg-indigo-600 text-white px-6 py-2 rounded-md text-sm hover:bg-indigo-700 flex items-center gap-2">
        <i class="fa-solid fa-check"></i> Save Role Permissions
    </button>
</form>

<?php echo $__env->make('partials.table-pagination', ['paginator' => $roles], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /home/kemmytec/nsales/resources/views/role_permissions/index.blade.php ENDPATH**/ ?>