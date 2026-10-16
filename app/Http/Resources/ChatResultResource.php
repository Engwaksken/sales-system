<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Arr;

class ChatResultResource extends JsonResource
{
    public static $wrap = null;

    public function toArray(Request $request): array
    {
        return Arr::only($this->resource, ['conversation_id', 'status']);
    }
}
