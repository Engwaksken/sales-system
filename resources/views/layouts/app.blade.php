@php
    $siteSettings = $siteSettings ?? [];
    $systemName = $siteSettings['site_name'] ?? 'Kemmytech Sales';
    $systemLogo = !empty($siteSettings['system_logo']) ? asset($siteSettings['system_logo']) : null;
    $systemFavicon = !empty($siteSettings['system_favicon']) ? asset($siteSettings['system_favicon']) : null;

    $primary = $siteSettings['theme_primary'] ?? '#0f766e';
    $secondary = $siteSettings['theme_secondary'] ?? '#0ea5e9';
    $accent = $siteSettings['theme_accent'] ?? '#f59e0b';
    $sidebar = $siteSettings['theme_sidebar'] ?? '#0f172a';
    $sidebarText = $siteSettings['theme_sidebar_text'] ?? '#e2e8f0';
    $background = $siteSettings['theme_background'] ?? '#f8fafc';
    $surface = $siteSettings['theme_surface'] ?? '#ffffff';
    $fontFamily = $siteSettings['font_family'] ?? 'Lato';
    $fontSize = (int) ($siteSettings['font_size'] ?? 14);

    $hex = ltrim($primary, '#');
    $primaryRgb = strlen($hex) === 6
        ? implode(', ', [hexdec(substr($hex, 0, 2)), hexdec(substr($hex, 2, 2)), hexdec(substr($hex, 4, 2))])
        : '15, 118, 110';

    $googleFonts = ['Lato', 'Inter', 'Poppins', 'Open Sans', 'Roboto', 'Nunito', 'Montserrat', 'Outfit'];
    $loadGoogleFont = in_array($fontFamily, $googleFonts, true);
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Dashboard') - {{ $systemName }}</title>

    @if($systemFavicon)
        <link rel="icon" href="{{ $systemFavicon }}">
        <link rel="shortcut icon" href="{{ $systemFavicon }}">
    @endif

    @if($loadGoogleFont)
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family={{ urlencode($fontFamily) }}:ital,wght@0,400;0,500;0,600;0,700;0,800;0,900;1,400;1,700&display=swap" rel="stylesheet">
    @endif

    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="{{ asset('css/app-theme.css') }}">

    <style>
        :root {
            --theme-primary: {{ $primary }};
            --theme-primary-rgb: {{ $primaryRgb }};
            --theme-secondary: {{ $secondary }};
            --theme-accent: {{ $accent }};
            --theme-sidebar: {{ $sidebar }};
            --theme-sidebar-text: {{ $sidebarText }};
            --theme-background: {{ $background }};
            --theme-surface: {{ $surface }};
            --theme-font: "{{ $fontFamily }}", Arial, sans-serif;
            --theme-font-size: {{ max(12, min(20, $fontSize)) }}px;
        }
        .stat-card { transition: transform .15s ease, box-shadow .15s ease; }
        .stat-card:hover { transform: translateY(-2px); box-shadow: 0 8px 20px -8px rgba(0,0,0,.15); }
    </style>
    @stack('styles')
</head>
<body>
<div class="flex min-h-screen">
    <div id="mobileSidebarOverlay"
         class="fixed inset-0 bg-slate-950/50 z-40 hidden md:hidden"
         onclick="closeMobileSidebar()"></div>

    <aside id="appSidebar"
           class="theme-sidebar fixed md:static inset-y-0 left-0 z-50 w-64 flex flex-col flex-shrink-0 -translate-x-full md:translate-x-0 transition-transform duration-200">
        <div class="theme-brand px-4 py-4 text-lg font-bold text-white flex items-center gap-3">
            <div class="theme-brand-logo">
                @if($systemLogo)
                    <img src="{{ $systemLogo }}" alt="{{ $systemName }} logo">
                @else
                    <i class="fas fa-chart-line"></i>
                @endif
            </div>
            <span class="truncate">{{ $systemName }}</span>
            <button type="button" class="ml-auto md:hidden text-white/70 hover:text-white" onclick="closeMobileSidebar()" aria-label="Close menu">
                <i class="fas fa-xmark"></i>
            </button>
        </div>

        <nav class="py-3 text-sm flex-1 overflow-y-auto">
            @foreach ($navGroups ?? [] as $groupName => $items)
                <div class="theme-nav-group px-5 pt-4 pb-1 text-xs font-semibold uppercase tracking-wider">
                    {{ $groupName }}
                </div>

                @foreach ($items as $routeName => $entry)
                    @php
                        $label = is_array($entry) ? $entry['label'] : $entry;
                        $icon = is_array($entry) ? ($entry['icon'] ?? 'fa-circle') : 'fa-circle';

                        $href = match(true) {
                            $routeName === 'reports' => route('reports.show'),
                            \Illuminate\Support\Facades\Route::has($routeName.'.index') => route($routeName.'.index'),
                            \Illuminate\Support\Facades\Route::has($routeName) => route($routeName),
                            default => '#',
                        };

                        $active = request()->routeIs($routeName.'.*') || request()->routeIs($routeName);
                    @endphp

                    <a href="{{ $href }}"
                       class="theme-nav-link {{ $active ? 'is-active' : '' }} flex items-center gap-3 px-5 py-2.5 transition-colors">
                        <i class="fas {{ $icon }} w-4 text-center text-xs opacity-90"></i>
                        <span>{{ $label }}</span>
                    </a>
                @endforeach
            @endforeach
        </nav>

        <div class="px-5 py-3 border-t border-white/10 text-xs">
            <a href="{{ route('shop.index') }}" target="_blank"
               class="theme-nav-link hover:text-white flex items-center gap-2">
                <i class="fas fa-arrow-up-right-from-square"></i>
                <span>View storefront</span>
            </a>
        </div>
    </aside>

    <div class="flex-1 flex flex-col min-w-0">
        <header class="theme-header px-4 md:px-6 py-3 flex items-center justify-between border-b sticky top-0 z-30">
            <div class="flex items-center gap-3 min-w-0">
                <button type="button"
                        class="mobile-menu-button w-9 h-9 rounded-lg border border-slate-200 text-slate-600 bg-white"
                        onclick="openMobileSidebar()"
                        aria-label="Open navigation menu">
                    <i class="fas fa-bars"></i>
                </button>
                <div class="font-semibold text-slate-700 truncate">@yield('title', 'Dashboard')</div>
            </div>

            <div class="flex items-center gap-3 md:gap-4 text-sm">
                @auth
                    @include('partials.topbar-notifications')

                    <span class="text-slate-500 hidden sm:flex items-center gap-2">
                        <i class="fas fa-circle-user text-lg text-slate-400"></i>
                        <span class="max-w-40 truncate">{{ auth()->user()->name }}</span>
                        <span class="text-xs bg-slate-100 text-slate-500 px-2 py-0.5 rounded-full hidden lg:inline">
                            {{ auth()->user()->role }}
                        </span>
                    </span>

                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button class="text-red-600 hover:text-red-800 flex items-center gap-1" title="Logout">
                            <i class="fas fa-right-from-bracket"></i>
                            <span class="hidden md:inline">Logout</span>
                        </button>
                    </form>
                @endauth
            </div>
        </header>

        <main class="theme-main p-4 md:p-6 flex-1">
            @yield('content')
        </main>
    </div>
</div>

@if(($siteSettings['accessibility_widget_enabled'] ?? '1') === '1')
    <button type="button" class="a11y-launcher" id="a11yLauncher" aria-label="Open accessibility tools" title="Accessibility">
        <i class="fas fa-universal-access"></i>
    </button>

    <div class="a11y-panel" id="a11yPanel" hidden>
        <div class="a11y-panel-head">
            <strong><i class="fas fa-universal-access mr-1"></i> Accessibility</strong>
            <button type="button" id="a11yClose" aria-label="Close accessibility tools">
                <i class="fas fa-xmark"></i>
            </button>
        </div>
        <div class="a11y-actions">
            <button type="button" data-a11y="increase"><i class="fas fa-plus"></i> Text</button>
            <button type="button" data-a11y="decrease"><i class="fas fa-minus"></i> Text</button>
            <button type="button" data-a11y="contrast"><i class="fas fa-circle-half-stroke"></i> Contrast</button>
            <button type="button" data-a11y="grayscale"><i class="fas fa-droplet-slash"></i> Grayscale</button>
            <button type="button" data-a11y="links"><i class="fas fa-link"></i> Links</button>
            <button type="button" data-a11y="spacing"><i class="fas fa-arrows-left-right-to-line"></i> Spacing</button>
            <button type="button" data-a11y="read"><i class="fas fa-volume-high"></i> Read Page</button>
            <button type="button" data-a11y="stop-read"><i class="fas fa-volume-xmark"></i> Stop Voice</button>
            <button type="button" data-a11y="reset" style="grid-column: 1 / -1;"><i class="fas fa-rotate-left"></i> Reset accessibility</button>
        </div>
    </div>
@endif

<script>
function openMobileSidebar() {
    document.getElementById('appSidebar')?.classList.remove('-translate-x-full');
    document.getElementById('mobileSidebarOverlay')?.classList.remove('hidden');
    document.body.classList.add('overflow-hidden');
}
function closeMobileSidebar() {
    if (window.innerWidth < 768) {
        document.getElementById('appSidebar')?.classList.add('-translate-x-full');
        document.getElementById('mobileSidebarOverlay')?.classList.add('hidden');
        document.body.classList.remove('overflow-hidden');
    }
}

// Generic modal helpers shared by every module.
function openModal(id) {
    const el = document.getElementById(id);
    if (!el) return;
    el.classList.remove('hidden');
    el.classList.add('flex');
    document.body.classList.add('overflow-hidden');
}

function closeModal(id) {
    const el = document.getElementById(id);
    if (!el) return;
    el.classList.add('hidden');
    el.classList.remove('flex');
    document.body.classList.remove('overflow-hidden');
}

function fillFormFields(form, data) {
    for (const el of form.elements) {
        if (!el.name || el.name === '_token' || el.name === '_method') continue;
        if (el.type === 'file') continue;
        const value = data[el.name];
        if (value === undefined) continue;

        if (el.type === 'checkbox') {
            el.checked = !!Number(value);
        } else {
            el.value = value === null ? '' : value;
        }
    }
}

function setFormEnabled(form, enabled) {
    for (const el of form.elements) {
        if (el.name === '_token' || el.name === '_method') continue;
        el.disabled = !enabled;
    }
    const submitBtn = form.querySelector('button[type="submit"]');
    if (submitBtn) submitBtn.style.display = enabled ? '' : 'none';
}

function setModalTitle(modalId, text) {
    const el = document.getElementById(modalId.replace('-modal', '-modal-title'));
    if (el) el.textContent = text;
}

function openEditModal(modalId, formId, updateUrl, data) {
    const form = document.getElementById(formId);
    form.action = updateUrl;
    form.querySelector('input[name="_method"]').value = 'PUT';
    fillFormFields(form, data);
    setFormEnabled(form, true);
    setModalTitle(modalId, 'Edit');
    openModal(modalId);
}

function openCreateModal(modalId, formId, storeUrl) {
    const form = document.getElementById(formId);
    form.reset();
    form.action = storeUrl;
    form.querySelector('input[name="_method"]').value = 'POST';
    setFormEnabled(form, true);
    setModalTitle(modalId, 'New');
    openModal(modalId);
}

function openViewModal(modalId, formId, data) {
    const form = document.getElementById(formId);
    fillFormFields(form, data);
    setFormEnabled(form, false);
    setModalTitle(modalId, 'View');
    openModal(modalId);
}

function switchTab(group, tabName) {
    document.querySelectorAll(`[data-tabgroup="${group}"]`).forEach(btn => {
        const active = btn.dataset.tab === tabName;
        btn.classList.toggle('border-indigo-600', active);
        btn.classList.toggle('text-indigo-600', active);
        btn.classList.toggle('border-transparent', !active);
        btn.classList.toggle('text-slate-500', !active);
    });
    document.querySelectorAll(`[data-tabpanel="${group}"]`).forEach(panel => {
        panel.classList.toggle('hidden', panel.dataset.tab !== tabName);
    });
}

(function () {
    const root = document.documentElement;
    const launcher = document.getElementById('a11yLauncher');
    const panel = document.getElementById('a11yPanel');
    const close = document.getElementById('a11yClose');
    const storageKey = 'nsales-a11y';

    if (!launcher || !panel) return;

    let state = {
        scale: 1,
        contrast: false,
        grayscale: false,
        links: false,
        spacing: false
    };

    try {
        state = {...state, ...JSON.parse(localStorage.getItem(storageKey) || '{}')};
    } catch (_) {}

    function apply() {
        root.style.setProperty('--a11y-scale', state.scale);
        root.style.fontSize = `calc(var(--theme-font-size) * ${state.scale})`;
        root.classList.toggle('a11y-contrast', !!state.contrast);
        root.classList.toggle('a11y-grayscale', !!state.grayscale);
        root.classList.toggle('a11y-links', !!state.links);
        root.classList.toggle('a11y-spacing', !!state.spacing);
        localStorage.setItem(storageKey, JSON.stringify(state));
    }

    launcher.addEventListener('click', () => panel.hidden = !panel.hidden);
    close?.addEventListener('click', () => panel.hidden = true);

    panel.addEventListener('click', event => {
        const button = event.target.closest('[data-a11y]');
        if (!button) return;

        switch (button.dataset.a11y) {
            case 'increase': state.scale = Math.min(1.4, +(state.scale + .1).toFixed(1)); break;
            case 'decrease': state.scale = Math.max(.8, +(state.scale - .1).toFixed(1)); break;
            case 'contrast': state.contrast = !state.contrast; break;
            case 'grayscale': state.grayscale = !state.grayscale; break;
            case 'links': state.links = !state.links; break;
            case 'spacing': state.spacing = !state.spacing; break;
            case 'read': readPageAloud(); return;
            case 'stop-read': if ('speechSynthesis' in window) window.speechSynthesis.cancel(); return;
            case 'reset':
                state = {scale: 1, contrast: false, grayscale: false, links: false, spacing: false};
                break;
        }

        apply();
    });

    function readPageAloud() {
        if (!('speechSynthesis' in window)) {
            alert('Voice reading is not supported by this browser.');
            return;
        }
        window.speechSynthesis.cancel();
        const main = document.querySelector('main') || document.body;
        const clone = main.cloneNode(true);
        clone.querySelectorAll('script,style,noscript,button,input,select,textarea,svg,.a11y-panel').forEach(el => el.remove());
        const text = (clone.innerText || clone.textContent || '').replace(/\s+/g, ' ').trim();
        if (!text) return;
        const utterance = new SpeechSynthesisUtterance(text.slice(0, 30000));
        utterance.rate = 0.95;
        utterance.pitch = 1;
        window.speechSynthesis.speak(utterance);
    }

    apply();
})();
</script>


@auth
    @php
        $supportHref = (auth()->user()?->role ?? '') === 'customer'
            ? (\Illuminate\Support\Facades\Route::has('shop.index') ? route('shop.index').'#chat' : '#')
            : (\Illuminate\Support\Facades\Route::has('chat.index') ? route('chat.index') : '#');
    @endphp
    <a href="{{ $supportHref }}"
       class="fixed bottom-5 right-5 z-[80] inline-flex items-center gap-2 rounded-full bg-indigo-600 px-4 py-3 text-sm font-black text-white shadow-2xl hover:bg-indigo-700"
       title="Open chatbot / support">
        <i class="fa-solid fa-headset text-lg"></i>
        <span class="hidden sm:inline">Chat Support</span>
    </a>
@endauth


<script>
(function () {
    const fieldHints = {
        name: 'Enter the full name used in NSales.',
        title: 'Enter a short, clear title.',
        sku: 'Enter the product SKU/code, for example SKU-001.',
        branch_id: 'Select the branch name where this record belongs.',
        category_id: 'Select the category name for this product.',
        supplier_id: 'Select the supplier name when applicable.',
        product_id: 'Select the product name.',
        customer_id: 'Select the customer name.',
        amount: 'Enter the amount without currency symbols.',
        total_amount: 'Enter the full transaction amount.',
        cost_price: 'Enter the buying/cost price per unit.',
        selling_price: 'Enter the selling price per unit.',
        quantity: 'Enter the number of units.',
        total_stock: 'Enter the total stock received.',
        expense_date: 'Select the date the expense happened.',
        sale_date: 'Select the date of the sale.',
        expiry_date: 'Select the product expiry date if applicable.',
        payment_method: 'Select or enter how the payment was made.',
        description: 'Add short details that help explain this record.',
        email: 'Enter a valid email address.',
        phone: 'Enter a reachable phone number.'
    };

    function humanize(name) {
        return String(name || '')
            .replace(/_id$/i, '')
            .replace(/_/g, ' ')
            .replace(/\b\w/g, c => c.toUpperCase());
    }

    document.querySelectorAll('form input[name], form select[name], form textarea[name]').forEach(field => {
        if (field.type === 'hidden' || field.type === 'submit' || field.dataset.hintReady === '1') return;

        field.dataset.hintReady = '1';

        const name = field.name.replace(/\[\]$/, '');
        const hint = fieldHints[name];

        if (!field.placeholder && ['text','email','tel','number'].includes(field.type)) {
            field.placeholder = `Enter ${humanize(name)}`;
        }

        if (!hint) return;

        const wrapper = field.closest('div') || field.parentElement;
        if (!wrapper || wrapper.querySelector(':scope > [data-field-hint]')) return;

        const small = document.createElement('p');
        small.dataset.fieldHint = '1';
        small.className = 'mt-1 text-[11px] leading-4 text-slate-400';
        small.innerHTML = `<i class="fa-regular fa-circle-question mr-1"></i>${hint}`;
        wrapper.appendChild(small);
    });
})();
</script>

@include('partials.relationship-name-labels')
@stack('scripts')
</body>
</html>
