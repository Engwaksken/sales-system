<?php

namespace App\Observers;

use App\Mail\OrderStageUpdatedMail;
use App\Models\Order;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Emails the customer whenever an order moves to a new stage, from any path:
 * the web admin, the staff mobile app or a direct model update. Runs after the
 * transaction commits so a rolled-back change never emails anyone.
 */
class OrderObserver implements ShouldHandleEventsAfterCommit
{
    private const MESSAGES = [
        'pending' => 'We have received your order and will confirm it shortly.',
        'confirmed' => 'Good news! Your order has been confirmed.',
        'processing' => 'Your order is now being prepared.',
        'ready_for_pickup' => 'Your order is ready for pickup.',
        'ready_for_delivery' => 'Your order is packed and ready for delivery.',
        'out_for_delivery' => 'Your order is on its way.',
        'completed' => 'Your order is complete.',
        'cancelled' => 'Your order has been cancelled. If you did not expect this, please contact us with your order reference.',
    ];

    public function updated(Order $order): void
    {
        if (! $order->wasChanged('status') || ! filled($order->email)) {
            return;
        }

        try {
            Mail::to($order->email)->send(new OrderStageUpdatedMail(
                $order,
                $order->stageLabel(),
                self::MESSAGES[$order->status] ?? 'The status of your order has changed.',
            ));
        } catch (Throwable $e) {
            report($e);
        }
    }
}
