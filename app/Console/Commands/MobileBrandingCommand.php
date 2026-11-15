<?php

namespace App\Console\Commands;

use App\Models\Setting;
use Illuminate\Console\Command;

/**
 * Prints the branding saved under Settings (system name, logo, favicon, theme colour)
 * as JSON, with local file paths, so the Flutter build can bundle the same logo and
 * use the favicon as the launcher icon. Used by nsales_mobile/tool/sync_branding.ps1.
 */
class MobileBrandingCommand extends Command
{
    protected $signature = 'mobile:branding';

    protected $description = 'Print the system name, logo, favicon and theme colour as JSON for the mobile app build';

    public function handle(): int
    {
        $settings = Setting::query()
            ->whereIn('setting_key', ['site_name', 'system_logo', 'system_favicon', 'theme_primary'])
            ->pluck('setting_value', 'setting_key');

        $file = function (?string $path): ?string {
            $path = trim((string) $path);
            if ($path === '' || str_starts_with($path, 'http')) {
                return null;
            }
            $absolute = public_path(ltrim($path, '/'));

            return is_file($absolute) ? $absolute : null;
        };
        $url = function (?string $path): ?string {
            $path = trim((string) $path);

            return $path === '' ? null : (str_starts_with($path, 'http') ? $path : asset(ltrim($path, '/')));
        };

        $this->line(json_encode([
            'site_name' => trim((string) ($settings['site_name'] ?? '')) ?: config('app.name'),
            'theme_primary' => preg_match('/^#[0-9a-fA-F]{6}$/', (string) ($settings['theme_primary'] ?? '')) ? $settings['theme_primary'] : '#0f766e',
            'logo_path' => $file($settings['system_logo'] ?? null),
            'favicon_path' => $file($settings['system_favicon'] ?? null),
            'logo_url' => $url($settings['system_logo'] ?? null),
            'favicon_url' => $url($settings['system_favicon'] ?? null),
        ], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));

        return self::SUCCESS;
    }
}
