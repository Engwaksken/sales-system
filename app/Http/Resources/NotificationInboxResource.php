<?php

namespace App\Http\Resources;

use App\Models\AppNotification;
use App\Models\ChatMessage;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NotificationInboxResource extends JsonResource
{
    public static $wrap = null;

    public function toArray(Request $request): array
    {
        $items = $this->resource['items'];
        $isCustomer = $request->user()->role === 'customer';

        return [
            'ok' => true,
            'counts' => $this->resource['counts'],
            'items' => [
                'system' => $items['system']->map(fn (AppNotification $notification) => [
                    'id' => (int) $notification->getKey(),
                    'title' => $notification->title,
                    'message' => $notification->message,
                    'type' => $notification->type,
                    'is_read' => (bool) $notification->notification_is_read,
                    'read_url' => route('my-notifications.read', ['notification' => $notification->getKey()]),
                    'target_url' => route('dashboard'),
                ])->values()->all(),
                'orders' => $items['orders']->map(fn (Order $order) => [
                    'id' => (int) $order->getKey(),
                    'label' => $order->order_number ?? $order->order_id ?? ('Order #'.$order->getKey()),
                    'status' => $order->status,
                    'notification_version' => (int) $order->notification_version,
                    'read_url' => route('my-notifications.orders.read', ['order' => $order->getKey()]),
                    'target_url' => $isCustomer
                        ? route('my-notifications.orders.show', ['order' => $order->getKey()])
                        : route('orders.index'),
                ])->values()->all(),
                'chats' => $items['chats']->map(fn (ChatMessage $message) => [
                    'id' => (int) $message->getKey(),
                    'conversation_id' => (int) $message->conversation_id,
                    'message' => $message->message,
                    'sender_type' => $message->sender_type,
                    'read_url' => $isCustomer
                        ? route('my-notifications.customer-chats.read', ['conversation' => $message->conversation_id])
                        : null,
                    'target_url' => $isCustomer
                        ? route('shop.index', ['chat_conversation' => $message->conversation_id]).'#chat'
                        : route('chat.show', ['conversation' => $message->conversation_id]),
                ])->values()->all(),
            ],
        ];
    }
}
