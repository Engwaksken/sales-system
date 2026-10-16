<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\URL;

class ChatMessagesResource extends JsonResource
{
    public static $wrap = null;

    public function toArray(Request $request): array
    {
        $conversation = $this->resource['conversation'];

        return [
            'messages' => $this->resource['messages']->map(fn ($message) => [
                'id' => $message->id,
                'sender_type' => $message->sender_type,
                'sender_name' => $message->sender_type === 'staff'
                    ? ($message->user?->name ?? 'Support')
                    : trim($conversation->first_name.' '.$conversation->last_name),
                'message' => $message->message,
                'attachment_url' => $message->attachment_path
                    ? URL::temporarySignedRoute('chat.attachment', now()->addMinutes(30), [
                        'message' => $message->getKey(),
                    ])
                    : null,
                'created_at' => optional($message->created_at)->format('H:i'),
            ])->values()->all(),
            'status' => $conversation->status,
        ];
    }
}
