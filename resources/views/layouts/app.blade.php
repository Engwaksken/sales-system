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
        .theme-nav-details > summary { cursor: pointer; list-style: disclosure-closed inside; }
        .theme-nav-details[open] > summary { list-style-type: disclosure-open; }
        .theme-nav-details > summary:focus-visible,
        .theme-nav-details .theme-nav-link:focus-visible {
            outline: 2px solid currentColor;
            outline-offset: -2px;
        }
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
                @php
                    $groupActive = false;
                    foreach ($items as $groupRouteName => $groupEntry) {
                        if (request()->routeIs($groupRouteName.'.*') || request()->routeIs($groupRouteName)) {
                            $groupActive = true;
                            break;
                        }
                    }
                @endphp
                <details class="theme-nav-details" @if($groupActive) open @endif>
                    <summary class="theme-nav-group px-5 pt-4 pb-1 text-xs font-semibold uppercase tracking-wider">
                        {{ $groupName }}
                    </summary>

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
                </details>
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

<div id="app-dialog" class="fixed inset-0 z-[200] hidden items-center justify-center bg-slate-950/50 p-4" role="presentation">
    <section class="w-full max-w-md rounded-xl bg-white p-6 shadow-2xl" role="alertdialog" aria-modal="true" aria-labelledby="app-dialog-title" aria-describedby="app-dialog-message">
        <div class="mb-3 flex items-center gap-3">
            <span class="grid h-10 w-10 place-items-center rounded-full bg-indigo-50 text-indigo-600"><i class="fa-solid fa-circle-info" aria-hidden="true"></i></span>
            <h2 id="app-dialog-title" class="text-lg font-semibold text-slate-800">Notice</h2>
        </div>
        <p id="app-dialog-message" class="whitespace-pre-line text-sm leading-6 text-slate-600"></p>
        <div class="mt-6 flex justify-end gap-2">
            <button type="button" id="app-dialog-cancel" class="hidden rounded-md border border-slate-300 px-4 py-2 text-sm text-slate-700 hover:bg-slate-50">Cancel</button>
            <button type="button" id="app-dialog-ok" class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">OK</button>
        </div>
    </section>
</div>

<script>
const appDialog = (() => {
    const root = document.getElementById('app-dialog');
    const title = document.getElementById('app-dialog-title');
    const message = document.getElementById('app-dialog-message');
    const cancel = document.getElementById('app-dialog-cancel');
    const ok = document.getElementById('app-dialog-ok');
    let resolveDialog = null;
    function show(text, confirmation = false) {
        title.textContent = confirmation ? 'Please confirm' : 'Notice';
        message.textContent = text;
        cancel.classList.toggle('hidden', !confirmation);
        ok.textContent = confirmation ? 'Continue' : 'OK';
        root.classList.remove('hidden');
        root.classList.add('flex');
        document.body.classList.add('overflow-hidden');
        (confirmation ? cancel : ok).focus();
        return new Promise(resolve => { resolveDialog = resolve; });
    }
    function close(result) {
        if (!resolveDialog) return;
        root.classList.add('hidden');
        root.classList.remove('flex');
        document.body.classList.remove('overflow-hidden');
        const resolve = resolveDialog;
        resolveDialog = null;
        resolve(result);
    }
    cancel.addEventListener('click', () => close(false));
    ok.addEventListener('click', () => close(true));
    document.addEventListener('keydown', event => { if (event.key === 'Escape' && resolveDialog) close(false); });
    return { alert: text => show(text), confirm: text => show(text, true) };
})();
function appAlert(message) { return appDialog.alert(message); }
function appConfirm(message) { return appDialog.confirm(message); }

document.addEventListener('submit', async event => {
    const form = event.target;
    if (!form.matches('form[data-confirm]') || form.dataset.confirmed === '1') return;
    event.preventDefault();
    if (await appConfirm(form.dataset.confirm)) {
        form.dataset.confirmed = '1';
        if (event.submitter) form.requestSubmit(event.submitter);
        else form.requestSubmit();
    }
});
document.addEventListener('click', async event => {
    const button = event.target.closest('[data-confirm]:not(form)');
    if (!button || button.dataset.confirmed === '1') return;
    event.preventDefault();
    if (await appConfirm(button.dataset.confirm)) {
        button.dataset.confirmed = '1';
        button.click();
        button.dataset.confirmed = '0';
    }
});

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
// Page modals sit inside the content column, so the sidebar (z-50) painted over
// their left side and swallowed clicks on tabs and fields there. Lift every
// full-screen modal to <body>, above the sidebar.
function liftModal(el) {
    if (el.parentElement !== document.body) document.body.appendChild(el);
    el.style.zIndex = '60';
}
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.fixed.inset-0[id$="-modal"], .fixed.inset-0[role="dialog"]').forEach(liftModal);
});

function openModal(id) {
    const el = document.getElementById(id);
    if (!el) return;
    liftModal(el);
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
        const value = el.name.endsWith('[]') ? data[el.name.slice(0, -2)] : data[el.name];
        if (value === undefined) continue;

        if (el.type === 'checkbox' && el.name.endsWith('[]')) {
            const key = el.name.slice(0, -2);
            const list = Array.isArray(data[key]) ? data[key].map(String) : [];
            el.checked = list.includes(String(el.value));
            continue;
        }
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
        // Section tabs are buttons inside the form; they must stay clickable in view mode.
        if (el.dataset.tabgroup) continue;
        el.disabled = !enabled;
    }
    const submitBtn = form.querySelector('button[type="submit"]');
    if (submitBtn) submitBtn.style.display = enabled ? '' : 'none';
}

// Parts of a shared create/edit form can be limited to one mode:
// [data-mode-only="create"] (e.g. the multi-branch picker) or [data-mode-only="edit"].
// Hidden parts are disabled so their fields are not submitted or validated.
function setFormMode(form, mode) {
    form.dataset.mode = mode;
    form.querySelectorAll('[data-mode-only]').forEach(part => {
        const show = part.dataset.modeOnly === mode || (mode === 'view' && part.dataset.modeOnly === 'edit');
        part.hidden = !show;
        part.querySelectorAll('input, select, textarea').forEach(field => {
            if (!show) field.disabled = true;
            else if (mode !== 'view') field.disabled = false;
        });
    });
}

// "Select all" checkbox for a list of checkboxes: <input type="checkbox" data-check-all="branch_ids[]">
document.addEventListener('change', event => {
    const all = event.target.closest('[data-check-all]');
    if (all) {
        all.form.querySelectorAll(`input[type="checkbox"][name="${all.dataset.checkAll}"]:not(:disabled)`).forEach(box => { box.checked = all.checked; });
        return;
    }
    const box = event.target;
    if (box.type === 'checkbox' && box.form && box.name.endsWith('[]')) {
        const toggle = box.form.querySelector(`[data-check-all="${box.name}"]`);
        if (toggle) {
            const boxes = [...box.form.querySelectorAll(`input[type="checkbox"][name="${box.name}"]:not(:disabled)`)];
            toggle.checked = boxes.length > 0 && boxes.every(b => b.checked);
        }
    }
});

function setModalTitle(modalId, text) {
    const el = document.getElementById(modalId.replace('-modal', '-modal-title'));
    if (el) el.textContent = text;
}

function openEditModal(modalId, formId, updateUrl, data) {
    const form = document.getElementById(formId);
    clearRestoredErrors(form);
    form._modalData = data;
    form.action = updateUrl;
    form.querySelector('input[name="_method"]').value = 'PUT';
    fillFormFields(form, data);
    setFormEnabled(form, true);
    setFormMode(form, 'edit');
    setModalTitle(modalId, 'Edit');
    openModal(modalId);
}

function openCreateModal(modalId, formId, storeUrl) {
    const form = document.getElementById(formId);
    clearRestoredErrors(form);
    form._modalData = null;
    form.reset();
    form.action = storeUrl;
    form.querySelector('input[name="_method"]').value = 'POST';
    setFormEnabled(form, true);
    setFormMode(form, 'create');
    setModalTitle(modalId, 'New');
    openModal(modalId);
}

function openViewModal(modalId, formId, data) {
    const form = document.getElementById(formId);
    clearRestoredErrors(form);
    form._modalData = data;
    fillFormFields(form, data);
    setFormEnabled(form, false);
    setFormMode(form, 'view');
    setModalTitle(modalId, 'View');
    openModal(modalId);
}

// ---------------------------------------------------------------------------
// Keep modal form input across a failed submit.
// When a modal form is submitted, its values (never passwords or files) are
// saved to sessionStorage. If the next page load carries errors, the same
// modal is reopened in the same mode, refilled, and the errors are shown
// inside it. Pages with JS-built rows can listen for the "form:restored"
// event on the form (detail.pairs) to rebuild them.
// ---------------------------------------------------------------------------
const MODAL_RESTORE_KEY = 'nsales:modal-form-restore';
const MODAL_RESTORE_ERRORS = @json(isset($errors) ? $errors->getMessages() : []);
const MODAL_SELECTOR = '.fixed.inset-0[id], [role="dialog"][id]';
const RESTORE_SKIP_TYPES = ['file', 'password', 'submit', 'button', 'reset', 'image'];

function clearRestoredErrors(form) {
    if (!form) return;
    form.querySelectorAll('[data-restored-error]').forEach(el => el.remove());
    form.querySelectorAll('[data-restored-invalid]').forEach(el => {
        el.removeAttribute('data-restored-invalid');
        el.removeAttribute('aria-invalid');
        el.classList.remove('border-red-500', 'ring-1', 'ring-red-500');
    });
}

document.addEventListener('submit', event => {
    const form = event.target;
    if (!(form instanceof HTMLFormElement) || (form.getAttribute('method') || 'get').toLowerCase() !== 'post') return;
    const modal = form.closest(MODAL_SELECTOR);
    if (!modal) return;
    const pairs = [];
    const files = [];
    for (const el of form.elements) {
        if (!el.name || el.name === '_token' || el.disabled) continue;
        if (el.type === 'file') { if (el.files?.length) files.push(el.name); continue; }
        if (RESTORE_SKIP_TYPES.includes(el.type)) continue;
        if ((el.type === 'checkbox' || el.type === 'radio') && !el.checked) continue;
        if (el.tagName === 'SELECT' && el.multiple) {
            [...el.selectedOptions].forEach(option => pairs.push([el.name, option.value]));
            continue;
        }
        pairs.push([el.name, el.value]);
    }
    let data = null;
    try { data = form._modalData ? JSON.parse(JSON.stringify(form._modalData)) : null; } catch (e) { data = null; }
    const snapshot = {
        path: location.pathname,
        at: Date.now(),
        modalId: modal.id,
        formId: form.id || null,
        formIndex: [...document.forms].indexOf(form),
        action: form.getAttribute('action') ? form.action : '',
        mode: form.dataset.mode || null,
        data,
        pairs,
        files,
    };
    try { sessionStorage.setItem(MODAL_RESTORE_KEY, JSON.stringify(snapshot)); } catch (e) { /* storage unavailable */ }
});

function restoreFormValues(form, pairs) {
    const byName = new Map();
    pairs.forEach(([name, value]) => {
        if (!byName.has(name)) byName.set(name, []);
        byName.get(name).push(value);
    });
    const cursor = new Map();
    const done = new WeakSet();
    // Change handlers may add fields (e.g. rows loaded for a branch), so repeat
    // until no new matching fields appear.
    for (let pass = 0; pass < 3; pass++) {
        const pending = [...form.elements].filter(el =>
            el.name && el.name !== '_token' && byName.has(el.name) && !done.has(el) && !RESTORE_SKIP_TYPES.includes(el.type));
        if (!pending.length) break;
        for (const el of pending) {
            if (!el.isConnected) continue;
            done.add(el);
            const values = byName.get(el.name);
            if (el.type === 'checkbox' || el.type === 'radio') {
                el.checked = values.includes(el.value);
            } else if (el.tagName === 'SELECT' && el.multiple) {
                [...el.options].forEach(option => { option.selected = values.includes(option.value); });
            } else {
                const index = cursor.get(el.name) || 0;
                cursor.set(el.name, index + 1);
                if (index >= values.length) continue;
                el.value = values[index];
            }
            if (el.type !== 'hidden') {
                el.dispatchEvent(new Event('input', { bubbles: true }));
                el.dispatchEvent(new Event('change', { bubbles: true }));
            }
        }
    }
}

function fieldsForErrorKey(form, key) {
    const parts = key.split('.');
    const bracketed = parts[0] + parts.slice(1).map(part => `[${part}]`).join('');
    const candidates = [bracketed, `${bracketed}[]`];
    if (parts.length > 1 && /^\d+$/.test(parts[parts.length - 1])) {
        const base = parts.slice(0, -1);
        candidates.push(base[0] + base.slice(1).map(part => `[${part}]`).join('') + '[]');
    }
    for (const name of candidates) {
        const found = form.querySelectorAll(`[name="${CSS.escape(name)}"]`);
        if (found.length) return [...found];
    }
    return [];
}

function revealField(field) {
    for (let node = field.parentElement; node && node !== document.body; node = node.parentElement) {
        if (node.dataset.tabpanel && node.dataset.tab !== undefined && typeof switchTab === 'function') {
            switchTab(node.dataset.tabpanel, node.dataset.tab);
            continue;
        }
        // Page-specific tabs: data-<group>-panel="x" paired with data-<group>-tab="x".
        for (const attr of node.attributes) {
            const match = attr.name.match(/^data-([a-z0-9-]+)-panel$/);
            if (match) document.querySelector(`[data-${match[1]}-tab="${CSS.escape(attr.value)}"]`)?.click();
        }
    }
}

function showRestoredErrors(form, errors, files) {
    clearRestoredErrors(form);
    const messages = Object.values(errors).flat();
    if (messages.length) {
        const box = document.createElement('div');
        box.dataset.restoredError = '1';
        box.setAttribute('role', 'alert');
        box.className = 'rounded-md bg-red-50 px-4 py-2 text-sm text-red-700';
        const list = document.createElement('ul');
        list.className = 'list-disc pl-4';
        messages.forEach(message => {
            const item = document.createElement('li');
            item.textContent = message;
            list.appendChild(item);
        });
        box.appendChild(list);
        form.prepend(box);
    }

    let firstField = null;
    Object.entries(errors).forEach(([key, keyMessages]) => {
        const fields = fieldsForErrorKey(form, key);
        if (!fields.length) return;
        fields.forEach(field => {
            field.dataset.restoredInvalid = '1';
            field.setAttribute('aria-invalid', 'true');
            if (field.type !== 'hidden') field.classList.add('border-red-500', 'ring-1', 'ring-red-500');
        });
        const field = fields.find(el => el.type !== 'hidden');
        if (!field) return;
        firstField = firstField || field;
        const note = document.createElement('p');
        note.dataset.restoredError = '1';
        note.className = 'mt-1 text-xs text-red-600';
        note.textContent = keyMessages[0];
        const anchor = ['checkbox', 'radio'].includes(field.type) ? (field.closest('label') || field) : field;
        anchor.insertAdjacentElement('afterend', note);
    });

    (files || []).forEach(name => {
        const input = form.querySelector(`input[type="file"][name="${CSS.escape(name)}"]`);
        if (!input) return;
        const note = document.createElement('p');
        note.dataset.restoredError = '1';
        note.className = 'mt-1 text-xs text-amber-600';
        note.textContent = 'Please choose the file again. Files are not kept after an error.';
        input.insertAdjacentElement('afterend', note);
    });

    if (firstField) {
        revealField(firstField);
        setTimeout(() => firstField.focus({ preventScroll: false }), 50);
    }
}

// ---------------------------------------------------------------------------
// Multi-select delete for tables.
// Any table whose rows carry a DELETE form for a module registered in
// BulkDeleteController gets a checkbox column, a select-all box (shift-click
// selects a range) and a "Delete selected" bar that posts the ids to
// /{module}/bulk-delete.
// ---------------------------------------------------------------------------
const BULK_DELETE_URLS = @json(collect(\App\Http\Controllers\BulkDeleteController::RESOURCES)->keys()->filter(fn ($r) => \Illuminate\Support\Facades\Route::has("{$r}.bulk-destroy"))->map(fn ($r) => route("{$r}.bulk-destroy"))->values());

function initBulkDeleteTables(root = document) {
    const byBase = new Map(BULK_DELETE_URLS.map(url => [new URL(url, location.href).pathname.replace(/\/bulk-delete$/, ''), url]));
    const checkboxClass = 'h-4 w-4 cursor-pointer rounded border-slate-300 text-indigo-600 focus:ring-indigo-500';

    function deleteTarget(row) {
        const form = [...row.querySelectorAll('form')].find(f =>
            (f.querySelector('input[name="_method"]')?.value || '').toUpperCase() === 'DELETE');
        if (!form) return null;
        const match = new URL(form.action, location.href).pathname.match(/^(.*)\/(\d+)\/?$/);
        const bulkUrl = match && byBase.get(match[1]);
        return bulkUrl ? { bulkUrl, id: match[2] } : null;
    }

    root.querySelectorAll('table:not([data-bulk-ready])').forEach(table => {
        const tbody = table.tBodies[0];
        if (!tbody) return;
        const rows = [...tbody.rows];
        let bulkUrl = null;
        const rowIds = new Map();
        rows.forEach(row => {
            const target = deleteTarget(row);
            if (!target || (bulkUrl && bulkUrl !== target.bulkUrl)) return;
            bulkUrl = target.bulkUrl;
            rowIds.set(row, target.id);
        });
        if (!bulkUrl) return;
        table.dataset.bulkReady = '1';

        // Checkbox column.
        let selectAll = null;
        [...(table.tHead?.rows || [])].forEach((headRow, index) => {
            const th = document.createElement('th');
            th.className = 'w-10 px-4 py-2 text-left';
            if (index === 0) {
                selectAll = document.createElement('input');
                selectAll.type = 'checkbox';
                selectAll.className = checkboxClass;
                selectAll.setAttribute('aria-label', 'Select all rows');
                th.appendChild(selectAll);
            }
            headRow.insertBefore(th, headRow.firstElementChild);
        });
        rows.forEach(row => {
            if (!rowIds.has(row) && row.cells.length === 1 && row.cells[0].colSpan > 1) {
                row.cells[0].colSpan += 1; // "No records yet" style row
                return;
            }
            const td = document.createElement('td');
            td.className = 'w-10 px-4 py-2 align-middle';
            if (rowIds.has(row)) {
                const box = document.createElement('input');
                box.type = 'checkbox';
                box.className = checkboxClass;
                box.value = rowIds.get(row);
                box.dataset.bulkRow = '1';
                box.setAttribute('aria-label', 'Select row');
                td.appendChild(box);
            }
            row.insertBefore(td, row.firstElementChild);
        });
        [...(table.tFoot?.rows || [])].forEach(row => row.insertBefore(document.createElement('td'), row.firstElementChild));

        // Selection bar above the table.
        const bar = document.createElement('div');
        bar.className = 'mb-2 hidden flex-wrap items-center gap-3 rounded-lg border border-red-200 bg-red-50 px-4 py-2 text-sm text-red-800';
        bar.setAttribute('role', 'region');
        bar.setAttribute('aria-label', 'Selected rows');
        bar.innerHTML = `
            <span class="font-semibold" data-bulk-count aria-live="polite"></span>
            <button type="button" data-bulk-delete class="inline-flex items-center gap-2 rounded-md bg-red-600 px-3 py-1.5 text-white hover:bg-red-700 disabled:opacity-60">
                <i class="fa-solid fa-trash"></i> Delete selected
            </button>
            <button type="button" data-bulk-clear class="text-red-700 underline-offset-2 hover:underline">Clear selection</button>`;
        const anchor = table.closest('.overflow-x-auto, .overflow-auto') || table;
        anchor.parentNode.insertBefore(bar, anchor);

        const boxes = () => [...tbody.querySelectorAll('input[data-bulk-row]')];
        const selected = () => boxes().filter(box => box.checked);

        function update() {
            const all = boxes();
            const chosen = selected();
            all.forEach(box => box.closest('tr')?.classList.toggle('bg-indigo-50', box.checked));
            if (selectAll) {
                selectAll.checked = all.length > 0 && chosen.length === all.length;
                selectAll.indeterminate = chosen.length > 0 && chosen.length < all.length;
            }
            bar.querySelector('[data-bulk-count]').textContent =
                `${chosen.length} ${chosen.length === 1 ? 'record' : 'records'} selected`;
            bar.classList.toggle('hidden', chosen.length === 0);
            bar.classList.toggle('flex', chosen.length > 0);
        }

        selectAll?.addEventListener('change', () => {
            boxes().forEach(box => { box.checked = selectAll.checked; });
            update();
        });

        let lastClicked = null;
        tbody.addEventListener('click', event => {
            const box = event.target.closest('input[data-bulk-row]');
            if (!box) return;
            const all = boxes();
            if (event.shiftKey && lastClicked && lastClicked !== box && all.includes(lastClicked)) {
                const [from, to] = [all.indexOf(lastClicked), all.indexOf(box)].sort((a, b) => a - b);
                all.slice(from, to + 1).forEach(item => { item.checked = box.checked; });
            }
            lastClicked = box;
            update();
        });

        bar.querySelector('[data-bulk-clear]').addEventListener('click', () => {
            boxes().forEach(box => { box.checked = false; });
            update();
        });

        const deleteButton = bar.querySelector('[data-bulk-delete]');
        deleteButton.addEventListener('click', async () => {
            const ids = selected().map(box => box.value);
            if (!ids.length) return;
            const label = ids.length === 1 ? 'this record' : `these ${ids.length} records`;
            if (!(await appConfirm(`Delete ${label}? This cannot be undone.`))) return;
            deleteButton.disabled = true;
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = bulkUrl;
            form.hidden = true;
            const add = (name, value) => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = name;
                input.value = value;
                form.appendChild(input);
            };
            add('_token', document.querySelector('meta[name="csrf-token"]')?.content || '');
            ids.forEach(id => add('ids[]', id));
            document.body.appendChild(form);
            form.submit();
        });

        update();
    });
}
document.addEventListener('DOMContentLoaded', () => initBulkDeleteTables());

// Clear a field's error mark once the user edits it.
document.addEventListener('input', event => {
    const field = event.target;
    if (!field?.dataset?.restoredInvalid || !event.isTrusted) return;
    field.removeAttribute('data-restored-invalid');
    field.removeAttribute('aria-invalid');
    field.classList.remove('border-red-500', 'ring-1', 'ring-red-500');
});

document.addEventListener('DOMContentLoaded', () => {
    let snapshot = null;
    try {
        snapshot = JSON.parse(sessionStorage.getItem(MODAL_RESTORE_KEY) || 'null');
        sessionStorage.removeItem(MODAL_RESTORE_KEY);
    } catch (e) { snapshot = null; }
    if (!snapshot || !Object.keys(MODAL_RESTORE_ERRORS).length) return;
    if (snapshot.path !== location.pathname || Date.now() - snapshot.at > 10 * 60 * 1000) return;

    const form = (snapshot.formId && document.getElementById(snapshot.formId)) || document.forms[snapshot.formIndex];
    const modal = document.getElementById(snapshot.modalId);
    if (!form || !modal || !modal.contains(form)) return;

    // Reopen through the page's own helpers so page-specific hooks run too.
    if (snapshot.mode === 'edit' && snapshot.action && form.id) {
        window.openEditModal(snapshot.modalId, form.id, snapshot.action, snapshot.data || {});
    } else if (snapshot.mode === 'create' && snapshot.action && form.id) {
        window.openCreateModal(snapshot.modalId, form.id, snapshot.action);
    } else {
        if (snapshot.action) form.action = snapshot.action;
        openModal(snapshot.modalId);
    }

    restoreFormValues(form, snapshot.pairs || []);
    form.dispatchEvent(new CustomEvent('form:restored', { detail: { pairs: snapshot.pairs || [], errors: MODAL_RESTORE_ERRORS } }));
    showRestoredErrors(form, MODAL_RESTORE_ERRORS, snapshot.files);
});

function switchTab(group, tabName) {
    document.querySelectorAll(`[data-tabgroup="${group}"]`).forEach(btn => {
        const active = btn.dataset.tab === tabName;
        btn.setAttribute('aria-selected', active ? 'true' : 'false');
        btn.setAttribute('tabindex', active ? '0' : '-1');
        btn.classList.toggle('border-indigo-600', active);
        btn.classList.toggle('text-indigo-600', active);
        btn.classList.toggle('border-transparent', !active);
        btn.classList.toggle('text-slate-500', !active);
    });
    document.querySelectorAll(`[data-tabpanel="${group}"]`).forEach(panel => {
        panel.classList.toggle('hidden', panel.dataset.tab !== tabName);
    });
}

function organizeLongModalForms() {
    const layouts = {
        'efris_config-form': {
            'Business details': /^(branch_id|tin|taxpayer_|business_type|vat_registered)/,
            'EFRIS credentials': /^(environment|private_key_pem|public_key_pem|ura_public_key_pem|portal_)/,
            'Status': /^(is_active)$/
        },
        'order_payment_receipts-form': {
            'Order & customer': /^(order_id|order_number|receipt_number|customer_id|customer_name|phone|email)$/,
            'Payment details': /^(payment_method|gateway_|amount_paid|currency)$/,
            'Notes': /^(notes)$/
        },
        'payment_gateways-form': {
            'Gateway': /^(gateway_name|gateway_code|display_name|provider_type|description)$/,
            'Connection': /^(token_url|collect_url|base_url|status_url|client_id|client_secret|wallet_guid|callback_url|return_url)$/,
            'Capabilities': /^(supports_|is_active|is_default|sandbox_mode)/
        },
        'sales-form': {
            'Sale details': /^(customer_id|supplier_id|branch_id|user_id|invoice_no|sale_date)$/,
            'Payment': /^payments\[0\]\[(amount|method)\]$/,
            'Totals': /^(total_amount|discount|vat)$/
        }
    };

    Object.entries(layouts).forEach(([formId, definitions]) => {
        const form = document.getElementById(formId);
        if (!form || form.querySelector('[data-tabgroup]')) return;

        const sections = Object.keys(definitions).map(name => ({name, nodes: []}));
        const controls = [...form.children];
        controls.forEach(node => {
            const field = node.matches('[name]') ? node : node.querySelector('[name]:not([type="hidden"])');
            if (!field || field.type === 'hidden' || node.querySelector('button[type="submit"]')) return;
            const section = sections.find(item => definitions[item.name].test(field.name));
            if (section) section.nodes.push(node);
        });
        const populated = sections.filter(section => section.nodes.length);
        if (populated.length < 2) return;

        const group = `${formId}-sections`;
        const tabs = document.createElement('div');
        tabs.className = 'mb-4 flex flex-wrap gap-2 border-b border-slate-200';
        tabs.setAttribute('role', 'tablist');
        tabs.setAttribute('aria-label', 'Form sections');
        const firstField = controls.find(node => populated.some(section => section.nodes.includes(node)));
        if (firstField) form.insertBefore(tabs, firstField);
        const panels = populated.map((section, index) => {
            const button = document.createElement('button');
            button.type = 'button';
            button.setAttribute('role', 'tab');
            button.dataset.tabgroup = group;
            button.dataset.tab = String(index);
            button.setAttribute('aria-selected', index === 0 ? 'true' : 'false');
            button.className = `border-b-2 px-3 py-2 text-sm font-medium ${index === 0 ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-slate-500'}`;
            button.textContent = section.name;
            button.addEventListener('click', () => switchTab(group, String(index)));
            tabs.appendChild(button);

            const panel = document.createElement('div');
            panel.dataset.tabpanel = group;
            panel.dataset.tab = String(index);
            panel.className = `${form.classList.contains('grid') ? 'grid grid-cols-1 gap-4 md:grid-cols-2' : 'space-y-4'}${index ? ' hidden' : ''}`;
            section.nodes.forEach(node => panel.appendChild(node));
            return panel;
        });

        panels.forEach(panel => form.insertBefore(panel, form.querySelector('button[type="submit"]')?.parentElement || null));
    });
}
document.addEventListener('DOMContentLoaded', organizeLongModalForms);

// Keep the sidebar compact: expanding one navigation group collapses the others.
document.querySelectorAll('.theme-nav-details').forEach(group => {
    group.addEventListener('toggle', () => {
        if (!group.open) return;
        document.querySelectorAll('.theme-nav-details[open]').forEach(other => {
            if (other !== group) other.open = false;
        });
    });
});

// Bring required fields in hidden tab panels into view before native validation.
document.querySelectorAll('form').forEach(form => {
    form.addEventListener('invalid', event => {
        const panel = event.target.closest('[data-tabpanel]');
        if (panel) switchTab(panel.dataset.tabpanel, panel.dataset.tab);
    }, true);
});

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
            appAlert('Voice reading is not supported by this browser.');
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
        // List-page filter toolbars stay a single compact row: no hints or generated placeholders.
        if (field.closest('.filter-toolbar')) return;

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
@include('partials.flash-autohide')
</body>
</html>
