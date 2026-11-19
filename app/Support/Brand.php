<?php

namespace App\Support;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * The store's branding (name, logo, favicon, colours) from admin Settings, in one
 * place for customer-facing pages and emails. Every value is sanitised and has a
 * safe fallback, so a missing settings table or a bad colour never breaks a page.
 */
final class Brand
{
    private static ?array $cached = null;

    public static function get(): array
    {
        return self::$cached ??= self::build();
    }

    /** Forget the per-request copy (settings were just saved, or in tests). */
    public static function flush(): void
    {
        self::$cached = null;
    }

    private static function build(): array
    {
        $settings = [];
        try {
            if (Schema::hasTable('settings')) {
                $settings = Cache::remember('app.site_settings', now()->addMinutes(10),
                    fn (): array => Setting::query()->pluck('setting_value', 'setting_key')->toArray());
            }
        } catch (Throwable) {
            // Fall back to defaults below.
        }

        $name = trim((string) ($settings['site_name'] ?? ''))
            ?: (config('mail.from.name') ?: config('app.name', 'Sales System'));

        $primary = self::hex($settings['theme_primary'] ?? null, '#0f766e');

        return [
            'name' => $name,
            'logo' => self::url($settings['system_logo'] ?? null)
                ?? (file_exists(public_path('assets/icons/logo.png')) ? asset('assets/icons/logo.png') : null),
            'favicon' => self::url($settings['system_favicon'] ?? null),
            'primary' => $primary,
            'onPrimary' => self::onColor($primary),
            'primarySoft' => self::mix($primary, 0.1),
            'secondary' => self::hex($settings['theme_secondary'] ?? null, '#0ea5e9'),
            'font' => self::font($settings['font_family'] ?? 'Lato'),
            'url' => url('/shop'),
        ];
    }

    private static function url(?string $path): ?string
    {
        $path = trim((string) $path);
        if ($path === '') {
            return null;
        }

        return str_starts_with($path, 'http') ? $path : asset(ltrim($path, '/'));
    }

    private static function hex(?string $value, string $fallback): string
    {
        $value = trim((string) $value);
        if (preg_match('/^#([0-9a-f])([0-9a-f])([0-9a-f])$/i', $value, $m)) {
            $value = "#{$m[1]}{$m[1]}{$m[2]}{$m[2]}{$m[3]}{$m[3]}";
        }

        return preg_match('/^#[0-9a-f]{6}$/i', $value) ? strtolower($value) : $fallback;
    }

    /** White or near-black text, whichever reads better on the colour (WCAG luminance). */
    private static function onColor(string $hex): string
    {
        $channel = function (int $offset) use ($hex): float {
            $c = hexdec(substr($hex, $offset, 2)) / 255;

            return $c <= 0.03928 ? $c / 12.92 : (($c + 0.055) / 1.055) ** 2.4;
        };
        $luminance = 0.2126 * $channel(1) + 0.7152 * $channel(3) + 0.0722 * $channel(5);

        return (1.05 / ($luminance + 0.05)) < 4.5 ? '#0f172a' : '#ffffff';
    }

    /** The colour blended onto white — a tint that email clients render without color-mix(). */
    private static function mix(string $hex, float $amount): string
    {
        $out = '#';
        foreach ([1, 3, 5] as $offset) {
            $c = hexdec(substr($hex, $offset, 2));
            $out .= str_pad(dechex((int) round(255 - (255 - $c) * $amount)), 2, '0', STR_PAD_LEFT);
        }

        return $out;
    }

    private static function font(string $font): string
    {
        $font = trim(str_replace(['"', "'", ';', '{', '}', '<', '>', '\\'], '', $font));

        return in_array($font, ['Lato', 'Inter', 'Poppins', 'Open Sans', 'Roboto', 'Nunito', 'Montserrat', 'Outfit'], true)
            ? $font : 'Lato';
    }
}
