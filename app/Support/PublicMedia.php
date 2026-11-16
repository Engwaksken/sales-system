<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Links to uploaded product and profile photos.
 *
 * Files are served through the /media route instead of /storage, because the
 * public/storage symlink is missing or stale on some hosts (images then 404
 * in the mobile app). A version query busts phone caches after a photo change.
 */
class PublicMedia
{
    /** Folders on the public disk that may be served without signing in. */
    public const FOLDERS = ['products', 'avatars'];

    public static function url(?string $path): ?string
    {
        $path = trim((string) $path);

        if ($path === '') {
            return null;
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        $path = self::normalize($path);
        $parts = explode('/', $path, 2);

        if (count($parts) !== 2 || ! in_array($parts[0], self::FOLDERS, true) || str_contains($parts[1], '/')) {
            return asset('storage/'.$path);
        }

        $params = ['folder' => $parts[0], 'file' => $parts[1]];

        try {
            $params['v'] = Storage::disk('public')->lastModified($path);
        } catch (Throwable) {
            // Missing file: the route answers 404 and the app shows its placeholder.
        }

        return route('media.show', $params);
    }

    /** Strips legacy prefixes such as "/storage/products/x.jpg" or "public/avatars/x.png". */
    public static function normalize(string $path): string
    {
        $path = ltrim(str_replace('\\', '/', $path), '/');

        foreach (['storage/', 'public/'] as $prefix) {
            if (str_starts_with($path, $prefix)) {
                return substr($path, strlen($prefix));
            }
        }

        return $path;
    }
}
