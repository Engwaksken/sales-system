<div class="relative" id="topbarNotificationRoot" data-inbox-url="{{ route('my-notifications.index') }}" data-read-all-url="{{ route('my-notifications.read-all') }}">
    <button type="button" id="topbarNotificationButton" aria-expanded="false" aria-controls="topbarNotificationMenu"
            class="relative grid h-10 w-10 place-items-center rounded-full border border-slate-200 bg-white text-slate-500 shadow-sm hover:bg-slate-50">
        <i class="fas fa-bell"></i>
        <span id="topbarNotificationBadge" class="hidden absolute -right-1 -top-1 min-w-[19px] rounded-full bg-red-600 px-1.5 py-0.5 text-center text-[10px] font-black leading-4 text-white">0</span>
    </button>
    <div id="topbarNotificationMenu" class="absolute right-0 top-12 z-[90] hidden w-[380px] max-w-[92vw] overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl">
        <div class="flex items-center justify-between border-b px-4 py-3">
            <div class="flex min-w-0 items-center gap-2">
                @if(!empty($siteSettings['system_logo']))
                    <img src="{{ asset(ltrim($siteSettings['system_logo'], '/')) }}" alt="{{ $siteSettings['site_name'] ?? 'System' }}" class="h-8 w-8 rounded-lg border border-slate-100 object-contain p-1">
                @else
                    <span class="grid h-8 w-8 place-items-center rounded-lg bg-indigo-50 text-indigo-600"><i class="fas fa-bell"></i></span>
                @endif
                <div class="min-w-0"><div class="truncate font-black text-slate-800">{{ $siteSettings['site_name'] ?? 'Notifications' }}</div><div class="text-xs text-slate-400"><span id="topbarUnreadText">0</span> unread notifications</div></div>
            </div>
            <button type="button" id="markAllNotificationsRead" class="hidden text-xs font-bold text-indigo-600">Mark system read</button>
        </div>
        <div id="topbarNotificationItems" class="max-h-[65vh] overflow-y-auto" aria-live="polite"><p class="px-4 py-6 text-center text-sm text-slate-500">Loading notifications…</p></div>
        <p id="topbarNotificationError" class="hidden px-4 py-3 text-sm text-red-600" role="alert">Notifications could not be loaded. Please try again.</p>
    </div>
</div>

@once
@push('scripts')
<script>
(function(){
    const root=document.getElementById('topbarNotificationRoot');
    if(!root)return;
    const button=document.getElementById('topbarNotificationButton'), menu=document.getElementById('topbarNotificationMenu');
    const badge=document.getElementById('topbarNotificationBadge'), unreadText=document.getElementById('topbarUnreadText');
    const list=document.getElementById('topbarNotificationItems'), error=document.getElementById('topbarNotificationError');
    const markAll=document.getElementById('markAllNotificationsRead'), csrf=document.querySelector('meta[name="csrf-token"]')?.content;
    let unread=0, requestId=0, pending=false;
    const esc=value=>String(value??'').replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
    function renderCounts(counts){unread=Number(counts?.total)||0; unreadText.textContent=unread; badge.textContent=unread>99?'99+':String(unread); badge.classList.toggle('hidden',unread<=0); markAll.classList.toggle('hidden',!(Number(counts?.system)>0));}
    async function load(){const id=++requestId;error.classList.add('hidden');try{const r=await fetch(root.dataset.inboxUrl,{headers:{Accept:'application/json','X-Requested-With':'XMLHttpRequest'},credentials:'same-origin'});if(!r.ok)throw new Error('load failed');const data=await r.json();if(id!==requestId)return;renderCounts(data.counts);const groups=[['Orders',data.items?.orders||[],'order'],['Customer chat',data.items?.chats||[],'chat'],['System',data.items?.system||[],'system']];let html='';for(const [label,items,type] of groups){if(!items.length)continue;html+=`<section class="border-b"><div class="bg-slate-50 px-4 py-2 text-[10px] font-black uppercase text-slate-400">${label}</div>`;for(const item of items){const title=type==='order'?item.label:type==='chat'?'Support chat':item.title;const message=type==='order'?String(item.status||'').replaceAll('_',' '):item.message;html+=`<button type="button" class="inbox-item flex w-full gap-3 px-4 py-3 text-left hover:bg-slate-50 ${item.is_read?'opacity-60':''}" data-type="${type}" data-id="${item.id}" data-version="${item.notification_version||''}" data-read-url="${esc(item.read_url)}" data-target-url="${esc(item.target_url)}"><span class="grid h-9 w-9 shrink-0 place-items-center rounded-full bg-indigo-50 text-indigo-600"><i class="fas ${type==='order'?'fa-cart-shopping':type==='chat'?'fa-comments':'fa-circle-info'}"></i></span><span class="min-w-0 flex-1"><strong class="block truncate text-sm">${esc(title)}</strong><span class="block text-xs text-slate-500">${esc(message)}</span></span>${!item.is_read?'<span class="inbox-dot mt-2 h-2 w-2 rounded-full bg-red-500"></span>':''}</button>`;}html+='</section>';}
            list.innerHTML=html||'<p class="px-4 py-6 text-center text-sm text-slate-500">You’re all caught up.</p>';
        }catch(e){if(id===requestId){error.classList.remove('hidden');list.innerHTML='<button type="button" id="retryNotificationLoad" class="w-full px-4 py-6 text-center text-sm text-indigo-600">Could not load notifications. Retry</button>';document.getElementById('retryNotificationLoad')?.addEventListener('click',load);}}}
    button.addEventListener('click',e=>{e.stopPropagation();const opening=menu.classList.contains('hidden');menu.classList.toggle('hidden');button.setAttribute('aria-expanded',String(opening));if(opening)load();});
    document.addEventListener('click',e=>{if(!root.contains(e.target)){menu.classList.add('hidden');button.setAttribute('aria-expanded','false');}});
    list.addEventListener('click',async e=>{const item=e.target.closest('.inbox-item');if(!item||pending)return;pending=true;const target=item.dataset.targetUrl;const headers={'X-CSRF-TOKEN':csrf,'Accept':'application/json','X-Requested-With':'XMLHttpRequest','Content-Type':'application/json'};try{let response;if(item.dataset.type==='order')response=await fetch(item.dataset.readUrl,{method:'POST',headers,body:JSON.stringify({notification_version:Number(item.dataset.version)})});else if(item.dataset.readUrl)response=await fetch(item.dataset.readUrl,{method:'POST',headers});else response={ok:true,json:async()=>({})};if(response.status===409){await load();return;}if(!response.ok)throw new Error('read failed');const result=await response.json();if(result.counts)renderCounts(result.counts);if(item.dataset.type==='order'&&target){window.location.assign(target);return;}if(item.dataset.type==='chat'&&target){window.location.assign(target);return;}item.classList.add('opacity-60');item.querySelector('.inbox-dot')?.remove();if(target)window.location.assign(target);}catch(e){error.textContent='Could not mark this notification as read. Please try again.';error.classList.remove('hidden');}finally{pending=false;}});
    markAll.addEventListener('click',async()=>{if(pending)return;pending=true;try{const r=await fetch(root.dataset.readAllUrl,{method:'POST',headers:{'X-CSRF-TOKEN':csrf,'Accept':'application/json','X-Requested-With':'XMLHttpRequest','Content-Type':'application/json'},body:JSON.stringify({scope:'system'})});if(!r.ok)throw new Error();const data=await r.json();renderCounts(data.counts);await load();}catch(e){error.textContent='Could not mark notifications as read. Please try again.';error.classList.remove('hidden');}finally{pending=false;}});
})();
</script>
@endpush
@endonce
