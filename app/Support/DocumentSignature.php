<?php

namespace App\Support;

use App\Models\Order;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * Staff signatures printed on invoices and receipts.
 *
 * Images live on the private "local" disk (never a public URL) and are embedded
 * into documents and API replies as data URIs.
 */
class DocumentSignature
{
    private const DISK = 'local';
    private const FOLDER = 'signatures';
    private const MAX_BYTES = 512 * 1024;

    /** The salesperson on a sale (falls back to whoever created it). */
    public static function forSale(?Sale $sale): ?array
    {
        if (! $sale) {
            return null;
        }

        $user = $sale->relationLoaded('user') ? $sale->user : $sale->user()->first();
        if (! $user && $sale->created_by) {
            $user = User::find($sale->created_by);
        }

        return self::forUser($user, 'Sold by');
    }

    /** The salesperson for an online order: the linked sale's seller, if any. */
    public static function forOrder(?Order $order): ?array
    {
        return $order?->sale_id ? self::forSale($order->sale) : null;
    }

    /** ['name', 'role', 'label', 'data_uri'] for documents; null when there is no signer. data_uri may be null. */
    public static function forUser(?User $user, string $label = 'Signed by'): ?array
    {
        if (! $user) {
            return null;
        }

        return [
            'name' => $user->name,
            'role' => User::roleLabel($user->role),
            'label' => $label,
            'data_uri' => self::dataUri($user),
        ];
    }

    public static function dataUri(?User $user): ?string
    {
        $path = $user && self::supported() ? trim((string) $user->signature) : '';
        if ($path === '') {
            return null;
        }

        try {
            $disk = Storage::disk(self::DISK);
            if (! $disk->exists($path)) {
                return null;
            }
            $mime = $disk->mimeType($path) ?: 'image/png';

            return 'data:'.$mime.';base64,'.base64_encode($disk->get($path));
        } catch (Throwable) {
            return null;
        }
    }

    /** Saves a drawn (data URL) or uploaded signature, replacing the old one. */
    public static function store(User $user, UploadedFile|string $source): void
    {
        if ($source instanceof UploadedFile) {
            $bytes = (string) file_get_contents($source->getRealPath());
        } else {
            if (! preg_match('#^data:image/(png|jpe?g);base64,(.+)$#s', trim($source), $m)) {
                throw ValidationException::withMessages(['signature' => 'The signature could not be read. Please draw it again.']);
            }
            $bytes = (string) base64_decode($m[2], true);
        }

        $info = $bytes !== '' ? @getimagesizefromstring($bytes) : false;
        if (! $info || ! in_array($info[2], [IMAGETYPE_PNG, IMAGETYPE_JPEG], true)) {
            throw ValidationException::withMessages(['signature' => 'The signature must be a PNG or JPG image.']);
        }
        if (strlen($bytes) > self::MAX_BYTES) {
            throw ValidationException::withMessages(['signature' => 'The signature image is too large. Use an image under 500 KB.']);
        }

        $path = self::FOLDER.'/'.$user->getKey().'-'.Str::random(24).($info[2] === IMAGETYPE_PNG ? '.png' : '.jpg');
        Storage::disk(self::DISK)->put($path, $bytes);

        self::delete($user);
        $user->forceFill(['signature' => $path])->save();
    }

    public static function remove(User $user): void
    {
        self::delete($user);
        $user->forceFill(['signature' => null])->save();
    }

    private static function delete(User $user): void
    {
        $old = trim((string) $user->getOriginal('signature'));
        if ($old !== '') {
            Storage::disk(self::DISK)->delete($old);
        }
    }

    private static function supported(): bool
    {
        static $has = null;

        return $has ??= Schema::hasColumn('users', 'signature');
    }
}
