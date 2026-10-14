<?php
    $paginator = $paginator ?? null;
?>

<?php if($paginator instanceof \Illuminate\Contracts\Pagination\Paginator): ?>
    <div class="mt-4 flex flex-col gap-3 rounded-xl border border-slate-200 bg-white px-4 py-3 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex flex-wrap items-center gap-3 text-xs text-slate-500">
            <?php if(method_exists($paginator, 'total')): ?>
                <span>
                    Showing
                    <strong><?php echo e($paginator->firstItem() ?? 0); ?></strong>
                    –
                    <strong><?php echo e($paginator->lastItem() ?? 0); ?></strong>
                    of
                    <strong><?php echo e($paginator->total()); ?></strong>
                </span>
            <?php endif; ?>

            <form method="GET" action="<?php echo e(url()->current()); ?>" class="flex items-center gap-2">
                <?php $__currentLoopData = request()->except(['page','per_page']); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $value): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <?php if(!is_array($value)): ?>
                        <input type="hidden" name="<?php echo e($key); ?>" value="<?php echo e($value); ?>">
                    <?php endif; ?>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

                <label class="font-bold">Rows</label>
                <select name="per_page"
                        onchange="this.form.submit()"
                        class="rounded-lg border-slate-300 px-2 py-1 text-xs">
                    <?php $__currentLoopData = [10,20,50,100]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $size): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($size); ?>"
                            <?php if((int)request('per_page', $paginator->perPage()) === $size): echo 'selected'; endif; ?>>
                            <?php echo e($size); ?>

                        </option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
            </form>
        </div>

        <div><?php echo e($paginator->onEachSide(1)->links()); ?></div>
    </div>
<?php endif; ?>
<?php /**PATH /home/kemmytec/nsales/resources/views/partials/table-pagination.blade.php ENDPATH**/ ?>