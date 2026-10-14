<?php $__env->startSection('title', 'Chat Support'); ?>

<?php $__env->startSection('content'); ?>
<div class="mb-5 flex flex-col gap-3 md:flex-row md:items-end md:justify-between">
    <div>
        <h1 class="text-xl font-black text-slate-800">
            <i class="fas fa-headset mr-2 text-indigo-600"></i>
            Chat Support
        </h1>
        <p class="mt-1 text-sm text-slate-500">
            Customer conversations, unread messages and support replies.
        </p>
    </div>

    <form method="GET" action="<?php echo e(route('chat.index')); ?>"
          class="flex flex-wrap items-end gap-2 rounded-xl border border-slate-200 bg-white p-3">
        <div>
            <label class="mb-1 block text-xs font-bold text-slate-500">Search</label>
            <input type="search"
                   name="q"
                   value="<?php echo e(request('q')); ?>"
                   placeholder="Customer, email, phone..."
                   class="rounded-lg border-slate-300 text-sm">
        </div>

        <div>
            <label class="mb-1 block text-xs font-bold text-slate-500">Status</label>
            <select name="status" class="rounded-lg border-slate-300 text-sm">
                <option value="">All</option>
                <option value="open" <?php if(request('status') === 'open'): echo 'selected'; endif; ?>>Open</option>
                <option value="closed" <?php if(request('status') === 'closed'): echo 'selected'; endif; ?>>Closed</option>
            </select>
        </div>

        <button class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-bold text-white">
            <i class="fas fa-filter mr-1"></i> Filter
        </button>
    </form>
</div>

<?php if(session('status')): ?>
    <div class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-700">
        <i class="fas fa-circle-check mr-1"></i>
        <?php echo e(session('status')); ?>

    </div>
<?php endif; ?>

<?php if($chatSetupMissing ?? false): ?>
    <div class="mb-5 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">
        <div class="font-black">
            <i class="fas fa-triangle-exclamation mr-1"></i>
            Chat database tables are not installed.
        </div>
        <p class="mt-1">
            Run <code>php artisan migrate</code>, then clear Laravel caches.
        </p>
    </div>
<?php endif; ?>

<div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
    <div class="overflow-x-auto">
        <table class="min-w-[860px] w-full text-sm">
            <thead class="border-b border-slate-200 bg-slate-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-black uppercase text-slate-500">Customer</th>
                    <th class="px-4 py-3 text-left text-xs font-black uppercase text-slate-500">Contact</th>
                    <th class="px-4 py-3 text-left text-xs font-black uppercase text-slate-500">Unread</th>
                    <th class="px-4 py-3 text-left text-xs font-black uppercase text-slate-500">Status</th>
                    <th class="px-4 py-3 text-left text-xs font-black uppercase text-slate-500">Started</th>
                    <th class="px-4 py-3 text-right text-xs font-black uppercase text-slate-500">Action</th>
                </tr>
            </thead>

            <tbody class="divide-y divide-slate-100">
                <?php $__empty_1 = true; $__currentLoopData = $conversations; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $conversation): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <tr class="<?php echo e((int)$conversation->unread_count > 0 ? 'bg-amber-50/60' : 'hover:bg-slate-50'); ?>">
                        <td class="px-4 py-3">
                            <div class="font-bold text-slate-800">
                                <?php echo e(trim($conversation->first_name.' '.$conversation->last_name)); ?>

                            </div>
                        </td>

                        <td class="px-4 py-3 text-slate-600">
                            <div><?php echo e($conversation->email); ?></div>
                            <div class="text-xs text-slate-400"><?php echo e($conversation->phone); ?></div>
                        </td>

                        <td class="px-4 py-3">
                            <?php if((int)$conversation->unread_count > 0): ?>
                                <span class="inline-flex min-w-7 items-center justify-center rounded-full bg-red-600 px-2 py-1 text-xs font-black text-white">
                                    <?php echo e($conversation->unread_count); ?>

                                </span>
                            <?php else: ?>
                                <span class="text-slate-300">0</span>
                            <?php endif; ?>
                        </td>

                        <td class="px-4 py-3">
                            <span class="rounded-full px-2.5 py-1 text-xs font-bold
                                <?php echo e($conversation->status === 'open'
                                    ? 'bg-emerald-100 text-emerald-700'
                                    : 'bg-slate-100 text-slate-500'); ?>">
                                <?php echo e(ucfirst($conversation->status)); ?>

                            </span>
                        </td>

                        <td class="px-4 py-3 whitespace-nowrap text-slate-500">
                            <?php echo e(optional($conversation->created_at)->format('d M Y H:i')); ?>

                        </td>

                        <td class="px-4 py-3 text-right">
                            <a href="<?php echo e(route('chat.show', $conversation->id)); ?>"
                               class="inline-flex items-center gap-1 rounded-lg border border-indigo-200 bg-indigo-50 px-3 py-1.5 text-xs font-bold text-indigo-700 hover:bg-indigo-100">
                                <i class="fas fa-comments"></i>
                                Open
                            </a>
                        </td>
                    </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <tr>
                        <td colspan="6" class="px-4 py-12 text-center text-slate-400">
                            <i class="far fa-comments mb-2 block text-3xl"></i>
                            No support conversations found.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php echo $__env->make('partials.table-pagination', ['paginator' => $conversations], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /home/kemmytec/nsales/resources/views/chat/index.blade.php ENDPATH**/ ?>