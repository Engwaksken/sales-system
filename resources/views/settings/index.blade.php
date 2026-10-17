@extends('layouts.app')

@section('title', 'System Settings')

@section('content')
@php
    $logoUrl = !empty($settings['system_logo']) ? asset($settings['system_logo']) : null;
    $faviconUrl = !empty($settings['system_favicon']) ? asset($settings['system_favicon']) : null;
@endphp

<div class="settings-page">
    <div class="settings-heading">
        <div>
            <div class="settings-kicker"><i class="fas fa-sliders"></i> Administration</div>
            <h1>System Settings</h1>
            <p>Manage your sales system identity, branding, typography, business rules and accessibility preferences.</p>
        </div>
        <button type="submit" form="system-settings-form" class="btn-theme-primary">
            <i class="fas fa-floppy-disk"></i>
            <span>Save Changes</span>
        </button>
    </div>

    @if(session('status'))
        <div data-flash class="settings-alert settings-alert-success">
            <i class="fas fa-circle-check"></i>
            <div>{{ session('status') }}</div>
        </div>
    @endif

    @if($errors->any())
        <div data-flash class="settings-alert settings-alert-error">
            <i class="fas fa-triangle-exclamation"></i>
            <div>
                <strong>Please correct the following:</strong>
                <ul>
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    <form id="system-settings-form"
          method="POST"
          action="{{ route('settings.update') }}"
          enctype="multipart/form-data">
        @csrf
        @method('PUT')

        <div class="settings-shell">
            <aside class="settings-tabs" aria-label="Settings sections">
                <button type="button" class="settings-tab active" data-settings-tab="general">
                    <span class="settings-tab-icon"><i class="fas fa-building"></i></span>
                    <span><strong>General</strong><small>System & business</small></span>
                </button>
                <button type="button" class="settings-tab" data-settings-tab="branding">
                    <span class="settings-tab-icon"><i class="fas fa-palette"></i></span>
                    <span><strong>Branding</strong><small>Logo & colors</small></span>
                </button>
                <button type="button" class="settings-tab" data-settings-tab="typography">
                    <span class="settings-tab-icon"><i class="fas fa-font"></i></span>
                    <span><strong>Typography</strong><small>Fonts & sizing</small></span>
                </button>
                <button type="button" class="settings-tab" data-settings-tab="sales">
                    <span class="settings-tab-icon"><i class="fas fa-receipt"></i></span>
                    <span><strong>Sales</strong><small>Tax & numbering</small></span>
                </button>
                <button type="button" class="settings-tab" data-settings-tab="accessibility">
                    <span class="settings-tab-icon"><i class="fas fa-universal-access"></i></span>
                    <span><strong>Accessibility</strong><small>PWD support</small></span>
                </button>
            </aside>

            <section class="settings-content">
                {{-- GENERAL --}}
                <div class="settings-panel active" data-settings-panel="general">
                    <div class="panel-heading">
                        <span class="panel-icon"><i class="fas fa-building"></i></span>
                        <div>
                            <h2>General Information</h2>
                            <p>Configure the system name, business details and operational defaults.</p>
                        </div>
                    </div>

                    <div class="settings-grid">
                        <div class="field-group col-span-2">
                            <label for="site_name">System Name <span>*</span></label>
                            <div class="field-control">
                                <i class="fas fa-store"></i>
                                <input id="site_name" name="site_name" type="text"
                                       value="{{ old('site_name', $settings['site_name']) }}"
                                       maxlength="100" required>
                            </div>
                            <small>Displayed in the browser title, sidebar and system branding.</small>
                        </div>

                        <div class="field-group">
                            <label for="currency">Currency <span>*</span></label>
                            <div class="field-control">
                                <i class="fas fa-money-bill-wave"></i>
                                <input id="currency" name="currency" type="text"
                                       value="{{ old('currency', $settings['currency']) }}" required>
                            </div>
                        </div>

                        <div class="field-group">
                            <label for="timezone">Timezone <span>*</span></label>
                            <div class="field-control">
                                <i class="fas fa-clock"></i>
                                <input id="timezone" name="timezone" type="text"
                                       value="{{ old('timezone', $settings['timezone']) }}" required>
                            </div>
                            <small>Example: Africa/Kampala</small>
                        </div>

                        <div class="field-group">
                            <label for="default_branch_id">Default Branch</label>
                            <div class="field-control">
                                <i class="fas fa-code-branch"></i>
                                <select id="default_branch_id" name="default_branch_id">
                                    <option value="">No default branch</option>
                                    @foreach($branch_options as $branch)
                                        <option value="{{ $branch->id }}"
                                            @selected((string) old('default_branch_id', $settings['default_branch_id']) === (string) $branch->id)>
                                            {{ $branch->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="field-group">
                            <label for="business_email">Business Email</label>
                            <div class="field-control">
                                <i class="fas fa-envelope"></i>
                                <input id="business_email" name="business_email" type="email"
                                       value="{{ old('business_email', $settings['business_email']) }}">
                            </div>
                        </div>

                        <div class="field-group">
                            <label for="business_phone">Business Phone</label>
                            <div class="field-control">
                                <i class="fas fa-phone"></i>
                                <input id="business_phone" name="business_phone" type="text"
                                       value="{{ old('business_phone', $settings['business_phone']) }}">
                            </div>
                        </div>

                        <div class="field-group col-span-2">
                            <label for="business_address">Business Address</label>
                            <textarea id="business_address" name="business_address" rows="3">{{ old('business_address', $settings['business_address']) }}</textarea>
                        </div>
                    </div>
                </div>

                {{-- BRANDING --}}
                <div class="settings-panel" data-settings-panel="branding">
                    <div class="panel-heading">
                        <span class="panel-icon"><i class="fas fa-palette"></i></span>
                        <div>
                            <h2>Branding & Theme</h2>
                            <p>Upload the official identity assets and control the visual theme across the administration area.</p>
                        </div>
                    </div>

                    <div class="branding-upload-grid">
                        <div class="upload-card">
                            <div class="upload-preview logo-preview" id="logoPreview">
                                @if($logoUrl)
                                    <img src="{{ $logoUrl }}" alt="System logo">
                                @else
                                    <i class="fas fa-image"></i>
                                @endif
                            </div>
                            <div class="upload-card-body">
                                <h3>System Logo</h3>
                                <p>PNG, JPG or WebP. Maximum 3 MB. Transparent PNG is recommended.</p>
                                <label class="file-picker">
                                    <i class="fas fa-cloud-arrow-up"></i>
                                    <span>Choose Logo</span>
                                    <input type="file" name="system_logo" accept=".png,.jpg,.jpeg,.webp"
                                           data-preview-target="logoPreview">
                                </label>
                            </div>
                        </div>

                        <div class="upload-card">
                            <div class="upload-preview favicon-preview" id="faviconPreview">
                                @if($faviconUrl)
                                    <img src="{{ $faviconUrl }}" alt="System favicon">
                                @else
                                    <i class="fas fa-icons"></i>
                                @endif
                            </div>
                            <div class="upload-card-body">
                                <h3>Favicon</h3>
                                <p>PNG, JPG, WebP or ICO. A square 64×64 or 128×128 image works best.</p>
                                <label class="file-picker">
                                    <i class="fas fa-cloud-arrow-up"></i>
                                    <span>Choose Favicon</span>
                                    <input type="file" name="system_favicon" accept=".png,.jpg,.jpeg,.webp,.ico"
                                           data-preview-target="faviconPreview">
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="section-divider">
                        <div>
                            <h3>Theme Colors</h3>
                            <p>Changes apply globally after saving.</p>
                        </div>
                        <button type="button" id="resetColors" class="btn-soft">
                            <i class="fas fa-rotate-left"></i> Reset defaults
                        </button>
                    </div>

                    <div class="color-grid">
                        @php
                            $colors = [
                                'theme_primary' => ['Primary', 'Buttons, links and active navigation', '#0f766e'],
                                'theme_secondary' => ['Secondary', 'Supporting actions and highlights', '#0ea5e9'],
                                'theme_accent' => ['Accent', 'Important indicators and callouts', '#f59e0b'],
                                'theme_sidebar' => ['Sidebar', 'Navigation background', '#0f172a'],
                                'theme_sidebar_text' => ['Sidebar Text', 'Navigation text and icons', '#e2e8f0'],
                                'theme_background' => ['Page Background', 'Main application background', '#f8fafc'],
                                'theme_surface' => ['Surface', 'Cards, forms and header background', '#ffffff'],
                            ];
                        @endphp

                        @foreach($colors as $key => [$label, $help, $default])
                            <div class="color-field" data-default-color="{{ $default }}">
                                <div class="color-field-head">
                                    <div>
                                        <label for="{{ $key }}">{{ $label }}</label>
                                        <small>{{ $help }}</small>
                                    </div>
                                    <input class="native-color" type="color"
                                           value="{{ old($key, $settings[$key]) }}"
                                           data-color-picker="{{ $key }}"
                                           aria-label="{{ $label }} color picker">
                                </div>
                                <div class="hex-input">
                                    <span class="color-dot" style="background: {{ old($key, $settings[$key]) }}"></span>
                                    <input id="{{ $key }}" name="{{ $key }}" type="text"
                                           value="{{ old($key, $settings[$key]) }}"
                                           pattern="^#[0-9A-Fa-f]{6}$" maxlength="7" required
                                           data-color-text="{{ $key }}">
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div class="theme-preview" id="themePreview">
                        <div class="preview-sidebar">
                            <div class="preview-brand"><i class="fas fa-chart-line"></i> Sales</div>
                            <span class="active"><i class="fas fa-gauge-high"></i> Dashboard</span>
                            <span><i class="fas fa-cash-register"></i> Sales / POS</span>
                            <span><i class="fas fa-box"></i> Products</span>
                        </div>
                        <div class="preview-main">
                            <div class="preview-topbar">Theme preview</div>
                            <div class="preview-card">
                                <small>Total Sales</small>
                                <strong>UGX 12,450,000</strong>
                                <button type="button">View report</button>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- TYPOGRAPHY --}}
                <div class="settings-panel" data-settings-panel="typography">
                    <div class="panel-heading">
                        <span class="panel-icon"><i class="fas fa-font"></i></span>
                        <div>
                            <h2>Typography</h2>
                            <p>Keep the interface readable and consistent across desktop, tablet and mobile screens.</p>
                        </div>
                    </div>

                    <div class="settings-grid">
                        <div class="field-group">
                            <label for="font_family">Font Family <span>*</span></label>
                            <div class="field-control">
                                <i class="fas fa-font"></i>
                                <select id="font_family" name="font_family" required>
                                    @foreach($font_options as $value => $label)
                                        <option value="{{ $value }}"
                                            @selected(old('font_family', $settings['font_family']) === $value)>
                                            {{ $label }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <small>Lato is the recommended default for the sales-management interface.</small>
                        </div>

                        <div class="field-group">
                            <label for="font_size">Base Font Size <span>*</span></label>
                            <div class="field-control">
                                <i class="fas fa-text-height"></i>
                                <input id="font_size" name="font_size" type="number" min="12" max="20"
                                       value="{{ old('font_size', $settings['font_size']) }}" required>
                                <span class="field-suffix">px</span>
                            </div>
                            <small>Recommended: 14–16 px.</small>
                        </div>
                    </div>

                    <div class="typography-preview" id="typographyPreview">
                        <span class="eyebrow">Typography preview</span>
                        <h3>Professional sales management</h3>
                        <p>Clear typography helps cashiers, administrators and managers scan information quickly and accurately.</p>
                        <button type="button"><i class="fas fa-check"></i> Sample action</button>
                    </div>
                </div>

                {{-- SALES --}}
                <div class="settings-panel" data-settings-panel="sales">
                    <div class="panel-heading">
                        <span class="panel-icon"><i class="fas fa-receipt"></i></span>
                        <div>
                            <h2>Sales & Storefront</h2>
                            <p>Configure tax, document numbering, stock warnings and customer checkout options.</p>
                        </div>
                    </div>

                    <div class="settings-grid">
                        <div class="field-group">
                            <label for="tax_rate">VAT / Tax Rate (%)</label>
                            <div class="field-control">
                                <i class="fas fa-percent"></i>
                                <input id="tax_rate" name="tax_rate" type="number" step="0.01" min="0" max="100"
                                       value="{{ old('tax_rate', $settings['tax_rate']) }}">
                            </div>
                        </div>

                        <div class="field-group">
                            <label for="low_stock_threshold">Low Stock Threshold</label>
                            <div class="field-control">
                                <i class="fas fa-boxes-stacked"></i>
                                <input id="low_stock_threshold" name="low_stock_threshold" type="number" min="0"
                                       value="{{ old('low_stock_threshold', $settings['low_stock_threshold']) }}">
                            </div>
                        </div>

                        <div class="field-group">
                            <label for="invoice_prefix">Invoice Prefix</label>
                            <div class="field-control">
                                <i class="fas fa-file-invoice"></i>
                                <input id="invoice_prefix" name="invoice_prefix" type="text"
                                       value="{{ old('invoice_prefix', $settings['invoice_prefix']) }}">
                            </div>
                        </div>

                        <div class="field-group">
                            <label for="order_prefix">Order Prefix</label>
                            <div class="field-control">
                                <i class="fas fa-cart-shopping"></i>
                                <input id="order_prefix" name="order_prefix" type="text"
                                       value="{{ old('order_prefix', $settings['order_prefix']) }}">
                            </div>
                        </div>

                        <div class="field-group col-span-2">
                            <label for="receipt_footer_note">Receipt Footer Note</label>
                            <textarea id="receipt_footer_note" name="receipt_footer_note" rows="3">{{ old('receipt_footer_note', $settings['receipt_footer_note']) }}</textarea>
                        </div>

                        <div class="toggle-card">
                            <div class="toggle-icon"><i class="fas fa-user-check"></i></div>
                            <div>
                                <strong>Guest Checkout</strong>
                                <small>Allow customers to place storefront orders without signing in.</small>
                            </div>
                            <label class="switch">
                                <input type="checkbox" name="enable_guest_checkout" value="1"
                                       @checked(old('enable_guest_checkout', $settings['enable_guest_checkout']) == '1')>
                                <span></span>
                            </label>
                        </div>

                        <div class="toggle-card">
                            <div class="toggle-icon"><i class="fas fa-mobile-screen-button"></i></div>
                            <div>
                                <strong>Mobile Money</strong>
                                <small>Allow mobile money as a supported storefront payment option.</small>
                            </div>
                            <label class="switch">
                                <input type="checkbox" name="enable_mobile_money" value="1"
                                       @checked(old('enable_mobile_money', $settings['enable_mobile_money']) == '1')>
                                <span></span>
                            </label>
                        </div>
                    </div>
                </div>

                {{-- ACCESSIBILITY --}}
                <div class="settings-panel" data-settings-panel="accessibility">
                    <div class="panel-heading">
                        <span class="panel-icon"><i class="fas fa-universal-access"></i></span>
                        <div>
                            <h2>Accessibility for PWDs</h2>
                            <p>Enable user-controlled accessibility tools without changing stored business data.</p>
                        </div>
                    </div>

                    <div class="accessibility-card">
                        <div class="accessibility-visual">
                            <i class="fas fa-universal-access"></i>
                        </div>
                        <div class="accessibility-copy">
                            <h3>Accessibility Widget</h3>
                            <p>Provides text resizing, high contrast, grayscale, readable spacing, underlined links and reset controls.</p>
                            <div class="accessibility-features">
                                <span><i class="fas fa-text-height"></i> Text sizing</span>
                                <span><i class="fas fa-circle-half-stroke"></i> Contrast</span>
                                <span><i class="fas fa-link"></i> Link emphasis</span>
                                <span><i class="fas fa-arrows-left-right-to-line"></i> Spacing</span>
                            </div>
                        </div>
                        <label class="switch switch-large">
                            <input type="checkbox" name="accessibility_widget_enabled" value="1"
                                   @checked(old('accessibility_widget_enabled', $settings['accessibility_widget_enabled']) == '1')>
                            <span></span>
                        </label>
                    </div>
                </div>
            </section>
        </div>

        <div class="settings-footer">
            <div>
                <i class="fas fa-circle-info"></i>
                Branding and theme changes apply after the page reloads.
            </div>
            <button type="submit" class="btn-theme-primary">
                <i class="fas fa-floppy-disk"></i> Save Settings
            </button>
        </div>
    </form>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const tabs = document.querySelectorAll('[data-settings-tab]');
    const panels = document.querySelectorAll('[data-settings-panel]');

    function openTab(name) {
        tabs.forEach(tab => tab.classList.toggle('active', tab.dataset.settingsTab === name));
        panels.forEach(panel => panel.classList.toggle('active', panel.dataset.settingsPanel === name));
        sessionStorage.setItem('nsales-settings-tab', name);
    }

    tabs.forEach(tab => tab.addEventListener('click', () => openTab(tab.dataset.settingsTab)));

    const storedTab = sessionStorage.getItem('nsales-settings-tab');
    if (storedTab && document.querySelector(`[data-settings-panel="${storedTab}"]`)) {
        openTab(storedTab);
    }

    document.querySelectorAll('input[type="file"][data-preview-target]').forEach(input => {
        input.addEventListener('change', function () {
            const file = this.files && this.files[0];
            if (!file || !file.type.startsWith('image/')) return;

            const target = document.getElementById(this.dataset.previewTarget);
            const reader = new FileReader();
            reader.onload = event => {
                target.innerHTML = `<img src="${event.target.result}" alt="Selected image preview">`;
            };
            reader.readAsDataURL(file);
        });
    });

    const colorDefaults = {};
    document.querySelectorAll('.color-field').forEach(field => {
        const text = field.querySelector('[data-color-text]');
        const picker = field.querySelector('[data-color-picker]');
        const dot = field.querySelector('.color-dot');
        colorDefaults[text.name] = field.dataset.defaultColor;

        function sync(value) {
            if (/^#[0-9A-Fa-f]{6}$/.test(value)) {
                picker.value = value;
                dot.style.background = value;
                updateThemePreview();
            }
        }

        picker.addEventListener('input', () => {
            text.value = picker.value;
            sync(picker.value);
        });

        text.addEventListener('input', () => sync(text.value.trim()));
    });

    document.getElementById('resetColors')?.addEventListener('click', () => {
        Object.entries(colorDefaults).forEach(([name, value]) => {
            const input = document.querySelector(`[data-color-text="${name}"]`);
            const picker = document.querySelector(`[data-color-picker="${name}"]`);
            const dot = input.closest('.color-field').querySelector('.color-dot');
            input.value = value;
            picker.value = value;
            dot.style.background = value;
        });
        updateThemePreview();
    });

    function color(name, fallback) {
        const value = document.querySelector(`[data-color-text="${name}"]`)?.value;
        return /^#[0-9A-Fa-f]{6}$/.test(value || '') ? value : fallback;
    }

    function updateThemePreview() {
        const preview = document.getElementById('themePreview');
        if (!preview) return;
        preview.style.setProperty('--preview-primary', color('theme_primary', '#0f766e'));
        preview.style.setProperty('--preview-secondary', color('theme_secondary', '#0ea5e9'));
        preview.style.setProperty('--preview-accent', color('theme_accent', '#f59e0b'));
        preview.style.setProperty('--preview-sidebar', color('theme_sidebar', '#0f172a'));
        preview.style.setProperty('--preview-sidebar-text', color('theme_sidebar_text', '#e2e8f0'));
        preview.style.setProperty('--preview-background', color('theme_background', '#f8fafc'));
        preview.style.setProperty('--preview-surface', color('theme_surface', '#ffffff'));
    }

    const fontFamily = document.getElementById('font_family');
    const fontSize = document.getElementById('font_size');
    const typePreview = document.getElementById('typographyPreview');

    function updateTypePreview() {
        if (!typePreview) return;
        typePreview.style.fontFamily = `"${fontFamily.value}", sans-serif`;
        typePreview.style.fontSize = `${fontSize.value || 14}px`;
    }

    fontFamily?.addEventListener('change', updateTypePreview);
    fontSize?.addEventListener('input', updateTypePreview);

    updateThemePreview();
    updateTypePreview();
});
</script>
@endpush
@endsection
