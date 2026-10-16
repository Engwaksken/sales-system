<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CustomerOrderResource extends JsonResource
{
    public static $wrap = null;

    public function toArray(Request $request): array
    {
        return [
            'ok' => true,
            'order' => [
                'id' => (int) $this->getKey(),
                'label' => $this->order_number ?? $this->order_id ?? ('Order #'.$this->getKey()),
                'status' => $this->status,
                'status_label' => $this->stageLabel(),
                'notification_version' => (int) $this->notification_version,
                'payment_status' => $this->payment_status,
                'subtotal' => $this->subtotal,
                'discount' => $this->discount,
                'delivery_fee' => $this->delivery_fee,
                'total_amount' => $this->total_amount,
                'amount_paid' => $this->amount_paid,
                'balance_amount' => $this->balance_amount,
                'created_at' => $this->created_at?->toISOString(),
                'items' => $this->order_items->map(fn ($item) => [
                    'id' => (int) $item->getKey(),
                    'product_name' => $item->product_name,
                    'quantity' => $item->quantity,
                    'price' => $item->price,
                    'subtotal' => $item->subtotal,
                ])->values()->all(),
            ],
        ];
    }
}
