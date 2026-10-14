<?php

namespace App\Support;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Throwable;

class DocumentBranding
{
    public static function get(): array
    {
        $defaults = [
            'site_name' => config('app.name', 'NSales'),
            'system_logo' => '',
            'theme_primary' => '#0f766e',
            'theme_secondary' => '#0ea5e9',
            'theme_accent' => '#f59e0b',
            'business_email' => '',
            'business_phone' => '',
            'business_address' => '',
            'currency' => 'UGX',
            'receipt_footer_note' => 'Thank you for your business!',
        ];

        try {
            if (! Schema::hasTable('settings')) {
                return self::withLogo($defaults);
            }

            $stored = Cache::remember(
                'app.document_branding',
                now()->addMinutes(10),
                function (): array {
                    return Setting::query()
                        ->whereIn('setting_key', [
                            'site_name',
                            'system_logo',
                            'theme_primary',
                            'theme_secondary',
                            'theme_accent',
                            'business_email',
                            'business_phone',
                            'business_address',
                            'currency',
                            'receipt_footer_note',
                        ])
                        ->pluck('setting_value', 'setting_key')
                        ->toArray();
                }
            );

            return self::withLogo(array_merge($defaults, $stored));
        } catch (Throwable $e) {
            report($e);

            return self::withLogo($defaults);
        }
    }

    private static function withLogo(array $branding): array
    {
        $branding['logo_data_uri'] = null;

        $candidates = [];

        $configured = trim((string) ($branding['system_logo'] ?? ''));
        if ($configured !== '') {
            $candidates[] = public_path(ltrim($configured, '/'));
        }

        // Fallback requested for the system/mobile branding.
        $candidates[] = public_path('assets/icons/logo.png');

        // Common legacy locations.
        $candidates[] = public_path('images/logo.png');
        $candidates[] = public_path('logo.png');

        foreach ($candidates as $absolute) {
            if (! File::isFile($absolute)) {
                continue;
            }

            try {
                $mime = File::mimeType($absolute) ?: 'image/png';

                $branding['logo_data_uri'] =
                    'data:'.$mime.';base64,'.base64_encode(File::get($absolute));

                break;
            } catch (Throwable $e) {
                report($e);
            }
        }

        return $branding;
    }
}
