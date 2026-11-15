<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * Public branding for the app's sign-in screen and header: the name, logo and
 * favicon an admin uploads under Settings, plus the theme colour and currency.
 */
class BrandingController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $settings = [];
        try {
            if (Schema::hasTable('settings')) {
                $settings = Cache::remember('app.site_settings', now()->addMinutes(10), fn (): array => Setting::query()
                    ->pluck('setting_value', 'setting_key')
                    ->toArray());
            }
        } catch (Throwable $e) {
            report($e);
        }

        $updated = null;
        try {
            $updated = Setting::query()
                ->whereIn('setting_key', ['site_name', 'system_logo', 'system_favicon', 'theme_primary'])
                ->max('updated_at');
        } catch (Throwable) {
            // Version is only a cache hint for the app.
        }

        return response()->json([
            'site_name' => trim((string) ($settings['site_name'] ?? '')) ?: config('app.name', 'NSales'),
            'logo_url' => $this->url($settings['system_logo'] ?? null),
            'favicon_url' => $this->url($settings['system_favicon'] ?? null),
            'theme_primary' => preg_match('/^#[0-9a-fA-F]{6}$/', (string) ($settings['theme_primary'] ?? ''))
                ? $settings['theme_primary'] : '#0f766e',
            'currency' => trim((string) ($settings['currency'] ?? '')) ?: 'UGX',
            'version' => $updated ? (string) $updated : null,
        ])->header('Cache-Control', 'public, max-age=300');
    }

    /** Settings store paths relative to public/ (uploads/settings/...), or a full URL. */
    private function url(?string $path): ?string
    {
        $path = trim((string) $path);

        return match (true) {
            $path === '' => null,
            str_starts_with($path, 'http://'), str_starts_with($path, 'https://') => $path,
            default => asset(ltrim($path, '/')),
        };
    }
}
