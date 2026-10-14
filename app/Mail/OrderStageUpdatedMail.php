<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class OrderStageUpdatedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Order $order,
        public string $stageLabel,
        public string $customerMessage,
    ) {
    }

    public function build()
    {
        return $this->subject("Order {$this->order->order_number} — {$this->stageLabel}")
            ->markdown('emails.order-stage-updated');
    }
}
