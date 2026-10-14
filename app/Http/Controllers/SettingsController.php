<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SettingsController extends Controller
{
    private const DEFAULTS = [
        // General
        'site_name' => 'Kemmytech Sales',
        'currency' => 'UGX',
        'timezone' => 'Africa/Kampala',
        'default_branch_id' => '',

        // Branding
        'system_logo' => '',
        'system_favicon' => '',
        'theme_primary' => '#0f766e',
        'theme_secondary' => '#0ea5e9',
        'theme_accent' => '#f59e0b',
        'theme_sidebar' => '#0f172a',
        'theme_sidebar_text' => '#e2e8f0',
        'theme_background' => '#f8fafc',
        'theme_surface' => '#ffffff',

        // Typography
        'font_family' => 'Lato',
        'font_size' => '14',

        // Business
        'business_email' => '',
        'business_phone' => '',
        'business_address' => '',

        // Sales
        'tax_rate' => '18',
        'invoice_prefix' => 'INV-',
        'order_prefix' => 'ORD-',
        'low_stock_threshold' => '5',
        'receipt_footer_note' => 'Thank you for your business!',

        // Storefront
        'enable_guest_checkout' => '1',
        'enable_mobile_money' => '1',

        // Accessibility
        'accessibility_widget_enabled' => '1',
    ];

    public function index(): View
    {
        $stored = Setting::query()
            ->pluck('setting_value', 'setting_key')
            ->toArray();

        $settings = array_merge(self::DEFAULTS, $stored);

        return view('settings.index', [
            'settings' => $settings,
            'branch_options' => Branch::query()->orderBy('name')->get(),
            'font_options' => [
                'Lato' => 'Lato',
                'Inter' => 'Inter',
                'Poppins' => 'Poppins',
                'Open Sans' => 'Open Sans',
                'Roboto' => 'Roboto',
                'Nunito' => 'Nunito',
                'Montserrat' => 'Montserrat',
                'Outfit' => 'Outfit',
                'Arial' => 'Arial',
                'Georgia' => 'Georgia',
            ],
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'site_name' => ['required', 'string', 'max:100'],
            'currency' => ['required', 'string', 'max:10'],
            'timezone' => ['required', 'timezone'],
            'default_branch_id' => ['nullable', 'integer', 'exists:branches,id'],

            'system_logo' => ['nullable', 'file', 'mimes:png,jpg,jpeg,webp', 'max:3072'],
            'system_favicon' => ['nullable', 'file', 'mimes:png,jpg,jpeg,webp,ico', 'max:1024'],

            'theme_primary' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'theme_secondary' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'theme_accent' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'theme_sidebar' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'theme_sidebar_text' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'theme_background' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'theme_surface' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],

            'font_family' => [
                'required',
                Rule::in([
                    'Lato', 'Inter', 'Poppins', 'Open Sans', 'Roboto',
                    'Nunito', 'Montserrat', 'Outfit', 'Arial', 'Georgia',
                ]),
            ],
            'font_size' => ['required', 'integer', 'min:12', 'max:20'],

            'business_email' => ['nullable', 'email', 'max:150'],
            'business_phone' => ['nullable', 'string', 'max:50'],
            'business_address' => ['nullable', 'string', 'max:1000'],

            'tax_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'invoice_prefix' => ['required', 'string', 'max:20'],
            'order_prefix' => ['required', 'string', 'max:20'],
            'low_stock_threshold' => ['required', 'integer', 'min:0', 'max:1000000'],
            'receipt_footer_note' => ['nullable', 'string', 'max:1000'],

            'enable_guest_checkout' => ['nullable', 'boolean'],
            'enable_mobile_money' => ['nullable', 'boolean'],
            'accessibility_widget_enabled' => ['nullable', 'boolean'],
        ], [
            'theme_primary.regex' => 'Primary theme color must be a valid HEX color, for example #0f766e.',
            'theme_secondary.regex' => 'Secondary theme color must be a valid HEX color.',
            'theme_accent.regex' => 'Accent theme color must be a valid HEX color.',
        ]);

        $validated = $validator->validate();

        $stored = Setting::query()
            ->pluck('setting_value', 'setting_key')
            ->toArray();

        if ($request->hasFile('system_logo')) {
            $validated['system_logo'] = $this->storeBrandAsset(
                $request->file('system_logo'),
                $stored['system_logo'] ?? null,
                'logo'
            );
        } else {
            unset($validated['system_logo']);
        }

        if ($request->hasFile('system_favicon')) {
            $validated['system_favicon'] = $this->storeBrandAsset(
                $request->file('system_favicon'),
                $stored['system_favicon'] ?? null,
                'favicon'
            );
        } else {
            unset($validated['system_favicon']);
        }

        $validated['enable_guest_checkout'] = $request->boolean('enable_guest_checkout') ? '1' : '0';
        $validated['enable_mobile_money'] = $request->boolean('enable_mobile_money') ? '1' : '0';
        $validated['accessibility_widget_enabled'] = $request->boolean('accessibility_widget_enabled') ? '1' : '0';

        foreach ($validated as $key => $value) {
            Setting::query()->updateOrCreate(
                ['setting_key' => $key],
                ['setting_value' => (string) ($value ?? '')]
            );
        }

        Cache::forget('app.site_settings');

        return redirect()
            ->route('settings.index')
            ->with('status', 'System settings updated successfully.');
    }

    private function storeBrandAsset($file, ?string $oldPath, string $prefix): string
    {
        $directory = public_path('uploads/settings');

        if (!File::isDirectory($directory)) {
            File::makeDirectory($directory, 0755, true);
        }

        if ($oldPath && str_starts_with($oldPath, 'uploads/settings/')) {
            $absoluteOldPath = public_path($oldPath);
            if (File::isFile($absoluteOldPath)) {
                File::delete($absoluteOldPath);
            }
        }

        $extension = strtolower($file->getClientOriginalExtension());
        $filename = $prefix . '-' . now()->format('YmdHis') . '-' . bin2hex(random_bytes(4)) . '.' . $extension;
        $file->move($directory, $filename);

        return 'uploads/settings/' . $filename;
    }
}
