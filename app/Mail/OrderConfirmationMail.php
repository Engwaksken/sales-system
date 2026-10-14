<?php

namespace App\Mail;

use App\Models\Branch;
use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/**
 * Sent to a customer after a successful guest checkout.
 *
 * Note: the original guest_order_api.php called send_order_confirmation($email, ...)
 * guarded by function_exists('send_order_confirmation') — but that function was never
 * actually defined anywhere in the codebase (email_helper.php only has verification
 * code / password reset / welcome emails), so this email was never sent by the
 * original app. This is a genuinely new (but clearly intended) addition.
 */
class OrderConfirmationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Order $order, public Branch $branch)
    {
    }

    public function build()
    {
        return $this->subject("Order Confirmation — {$this->order->order_number}")
            ->markdown('emails.order-confirmation');
    }
}
