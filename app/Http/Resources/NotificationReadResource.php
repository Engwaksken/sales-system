<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Arr;

class NotificationReadResource extends JsonResource
{
    public static $wrap = null;

    public function toArray(Request $request): array
    {
        return ['ok' => (bool) ($this->resource['success'] ?? true)] + Arr::only(
            $this->resource,
            ['type', 'id', 'notification_version', 'read_at', 'conversation_id',
                'scope', 'updated', 'counts', 'code']
        );
    }
}
