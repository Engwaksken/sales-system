<?php
    use App\Models\AppNotification;
    use App\Models\Order;
    use App\Models\ChatMessage;

    $currentUser = auth()->user();
    $isCustomer = ($currentUser?->role ?? '') === 'customer';

    $appNotifications = AppNotification::query()
        ->where(function ($q) use ($currentUser) {
            $q->whereNull('user_id');
            if ($currentUser) $q->orWhere('user_id', $currentUser->id);
        })
        ->latest('id')
        ->limit(20)
        ->get();

    $appUnread = $appNotifications->where('is_read', false)->count();

    $orderNotifications = collect();
    try {
        $query = Order::query()
            ->whereIn('status', ['pending','confirmed','processing'])
            ->latest('id');

        if ($isCustomer) {
            if ($currentUser?->customer?->id) {
                $query->where('customer_id', $currentUser->customer->id);
            } else {
                $query->where('email', $currentUser->email);
            }
        }

        $orderNotifications = $query->limit(5)->get();
    } catch (\Throwable $e) { report($e); }

    $chatNotifications = collect();
    try {
        $query = ChatMessage::query()->with('conversation')->where('is_read', false)->latest('id');

        if ($isCustomer) {
            $query->whereHas('conversation', fn($q) => $q->where('email', $currentUser->email));
        } else {
            $query->where('sender_type','!=','staff');
        }

        $chatNotifications = $query->limit(5)->get();
    } catch (\Throwable $e) { report($e); }

    $notificationCount = $appUnread + $orderNotifications->count() + $chatNotifications->count();
?>

<div class="relative" id="topbarNotificationRoot">
    <button type="button" id="topbarNotificationButton"
            class="relative grid h-10 w-10 place-items-center rounded-full border border-slate-200 bg-white text-slate-500 shadow-sm hover:bg-slate-50">
        <i class="fas fa-bell"></i>
        <span id="topbarNotificationBadge"
              class="<?php echo e($notificationCount > 0 ? '' : 'hidden'); ?> absolute -right-1 -top-1 min-w-[19px] rounded-full bg-red-600 px-1.5 py-0.5 text-center text-[10px] font-black leading-4 text-white">
            <?php echo e($notificationCount); ?>

        </span>
    </button>

    <div id="topbarNotificationMenu"
         class="absolute right-0 top-12 z-[90] hidden w-[380px] max-w-[92vw] overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl">
        <div class="flex items-center justify-between border-b px-4 py-3">
            <div>
                <div class="font-black text-slate-800">Notifications</div>
                <div class="text-xs text-slate-400"><span id="topbarUnreadText"><?php echo e($notificationCount); ?></span> unread</div>
            </div>
            <?php if($appUnread > 0): ?>
                <button type="button" id="markAllNotificationsRead" class="text-xs font-bold text-indigo-600">
                    Mark system read
                </button>
            <?php endif; ?>
        </div>

        <div class="max-h-[65vh] overflow-y-auto">
            <?php if($orderNotifications->isNotEmpty()): ?>
                <div class="border-b">
                    <div class="bg-slate-50 px-4 py-2 text-[10px] font-black uppercase text-slate-400">Orders</div>
                    <?php $__currentLoopData = $orderNotifications; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $order): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <a href="<?php echo e(route('orders.index')); ?>" class="flex gap-3 px-4 py-3 hover:bg-slate-50">
                            <span class="grid h-9 w-9 shrink-0 place-items-center rounded-full bg-amber-50 text-amber-600"><i class="fas fa-cart-shopping"></i></span>
                            <span class="min-w-0 flex-1">
                                <strong class="block truncate text-sm"><?php echo e($order->order_number ?? $order->order_id ?? ('Order #'.$order->id)); ?></strong>
                                <span class="block truncate text-xs text-slate-500"><?php echo e(ucfirst(str_replace('_',' ',$order->status ?? 'pending'))); ?></span>
                            </span>
                        </a>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </div>
            <?php endif; ?>

            <?php if($chatNotifications->isNotEmpty()): ?>
                <div class="border-b">
                    <div class="bg-slate-50 px-4 py-2 text-[10px] font-black uppercase text-slate-400">Chatbot / Support</div>
                    <?php $__currentLoopData = $chatNotifications; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $message): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <a href="<?php echo e($isCustomer ? route('shop.index').'#chat' : route('chat.show',$message->conversation_id)); ?>" class="flex gap-3 px-4 py-3 hover:bg-slate-50">
                            <span class="grid h-9 w-9 shrink-0 place-items-center rounded-full bg-indigo-50 text-indigo-600"><i class="fas fa-comments"></i></span>
                            <span class="min-w-0 flex-1">
                                <strong class="block truncate text-sm">Support chat</strong>
                                <span class="block truncate text-xs text-slate-500"><?php echo e(\Illuminate\Support\Str::limit($message->message,70)); ?></span>
                            </span>
                            <span class="mt-2 h-2 w-2 rounded-full bg-red-500"></span>
                        </a>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </div>
            <?php endif; ?>

            <?php if($appNotifications->isNotEmpty()): ?>
                <div>
                    <div class="bg-slate-50 px-4 py-2 text-[10px] font-black uppercase text-slate-400">System</div>
                    <?php $__currentLoopData = $appNotifications; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $notice): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <button type="button"
                                class="system-notification-item flex w-full gap-3 px-4 py-3 text-left hover:bg-slate-50 <?php echo e($notice->is_read ? 'opacity-60' : ''); ?>"
                                data-id="<?php echo e($notice->id); ?>"
                                data-read="<?php echo e($notice->is_read ? '1' : '0'); ?>"
                                data-target="<?php echo e(route('dashboard')); ?>">
                            <span class="grid h-9 w-9 shrink-0 place-items-center rounded-full bg-sky-50 text-sky-600"><i class="fas fa-circle-info"></i></span>
                            <span class="min-w-0 flex-1">
                                <strong class="block truncate text-sm"><?php echo e($notice->title); ?></strong>
                                <span class="block text-xs text-slate-500"><?php echo e(\Illuminate\Support\Str::limit($notice->message,90)); ?></span>
                            </span>
                            <?php if(!$notice->is_read): ?><span class="notification-unread-dot mt-2 h-2 w-2 rounded-full bg-red-500"></span><?php endif; ?>
                        </button>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php if (! $__env->hasRenderedOnce('309ad270-90c9-4a29-86e3-ec695fac3683')): $__env->markAsRenderedOnce('309ad270-90c9-4a29-86e3-ec695fac3683'); ?>
<?php $__env->startPush('scripts'); ?>
<script>
(function(){
    const root=document.getElementById('topbarNotificationRoot');
    const button=document.getElementById('topbarNotificationButton');
    const menu=document.getElementById('topbarNotificationMenu');
    const badge=document.getElementById('topbarNotificationBadge');
    const unreadText=document.getElementById('topbarUnreadText');
    const csrf=document.querySelector('meta[name="csrf-token"]')?.content;
    let unread=Number(unreadText?.textContent||0);

    function render(){
        if(unreadText) unreadText.textContent=Math.max(0,unread);
        if(!badge)return;
        if(unread<=0){badge.classList.add('hidden');badge.textContent='0';}
        else{badge.classList.remove('hidden');badge.textContent=unread>99?'99+':String(unread);}
    }

    button?.addEventListener('click',e=>{e.stopPropagation();menu?.classList.toggle('hidden');});
    document.addEventListener('click',e=>{if(root&&!root.contains(e.target))menu?.classList.add('hidden');});

    document.querySelectorAll('.system-notification-item').forEach(item=>{
        item.addEventListener('click',async()=>{
            if(item.dataset.read!=='1'){
                const res=await fetch(`/my-notifications/${item.dataset.id}/read`,{
                    method:'POST',
                    headers:{'X-CSRF-TOKEN':csrf,'Accept':'application/json','X-Requested-With':'XMLHttpRequest'}
                });
                if(res.ok){
                    item.dataset.read='1';
                    item.classList.add('opacity-60');
                    item.querySelector('.notification-unread-dot')?.remove();
                    unread=Math.max(0,unread-1);
                    render();
                }
            }
            window.location.href=item.dataset.target||'<?php echo e(route('dashboard')); ?>';
        });
    });

    document.getElementById('markAllNotificationsRead')?.addEventListener('click',async e=>{
        const res=await fetch('/my-notifications/read-all',{
            method:'POST',
            headers:{'X-CSRF-TOKEN':csrf,'Accept':'application/json','X-Requested-With':'XMLHttpRequest'}
        });
        if(res.ok){
            document.querySelectorAll('.system-notification-item').forEach(item=>{
                item.dataset.read='1';
                item.classList.add('opacity-60');
                item.querySelector('.notification-unread-dot')?.remove();
            });
            unread=Math.max(0,unread-<?php echo e($appUnread); ?>);
            render();
            e.currentTarget.remove();
        }
    });
})();
</script>
<?php $__env->stopPush(); ?>
<?php endif; ?>
<?php /**PATH /home/kemmytec/nsales/resources/views/partials/topbar-notifications.blade.php ENDPATH**/ ?>