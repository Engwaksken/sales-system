<?php

namespace App\Http\Controllers;

use App\Support\PublicMedia;
use Illuminate\Support\Facades\Storage;

/** Serves product and profile photos from the public disk (see App\Support\PublicMedia). */
class MediaController extends Controller
{
    public function show(string $folder, string $file)
    {
        abort_unless(in_array($folder, PublicMedia::FOLDERS, true), 404);

        $disk = Storage::disk('public');
        $path = $folder.'/'.$file;

        abort_unless($disk->exists($path), 404);

        $mime = $disk->mimeType($path) ?: 'application/octet-stream';
        abort_unless(str_starts_with($mime, 'image/'), 404);

        return response($disk->get($path), 200, [
            'Content-Type' => $mime,
            'Content-Length' => $disk->size($path),
            'Cache-Control' => 'public, max-age=604800',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
