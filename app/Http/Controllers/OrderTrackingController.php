<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;

/**
 * Public order tracking for storefront (guest) customers.
 *
 * Customers reach an order either through the signed link handed out after checkout
 * and in order emails, or by entering the order reference together with the phone
 * number used at checkout. The reference alone is never enough to see an order.
 */
class OrderTrackingController extends Controller
{
    /** Stages in the order they happen, for the progress timeline. */
    private const FLOW = [
        'pending' => 'Order received',
        'confirmed' => 'Confirmed',
        'processing' => 'Being prepared',
        'ready' => 'Ready',
        'out_for_delivery' => 'Out for delivery',
        'completed' => 'Completed',
    ];

    public function form(Request $request)
    {
        return view('shop.track-order', [
            'reference' => (string) $request->query('ref', ''),
        ]);
    }

    public function lookup(Request $request)
    {
        $data = $request->validate([
            'reference' => ['required', 'string', 'max:50'],
            'phone' => ['required', 'string', 'max:30'],
        ], [
            'reference.required' => 'Please enter your order reference.',
            'phone.required' => 'Please enter the phone number you used at checkout.',
        ]);

        $reference = strtoupper(trim($data['reference']));
        $order = Order::where('order_number', $reference)->orWhere('order_id', $reference)->first();

        if (! $order || ! $this->phonesMatch($order->phone, $data['phone'])) {
            return back()->withInput()->withErrors([
                'reference' => 'We could not find an order with that reference and phone number.',
            ]);
        }

        return redirect()->to($order->trackingUrl());
    }

    public function show(string $reference)
    {
        $order = Order::with(['order_items', 'branch'])
            ->where('order_number', $reference)
            ->orWhere('order_id', $reference)
            ->firstOrFail();

        return response()
            ->view('shop.track-order-show', [
                'order' => $order,
                'steps' => $this->steps($order),
                'cancelled' => $order->status === 'cancelled',
            ])
            ->header('Cache-Control', 'no-store, private')
            ->header('X-Robots-Tag', 'noindex');
    }

    /**
     * @return array<int, array{label: string, state: string}>
     */
    private function steps(Order $order): array
    {
        $status = (string) $order->status;
        $isPickup = $status === 'ready_for_pickup';

        $flow = self::FLOW;
        $flow['ready'] = $isPickup ? 'Ready for pickup' : 'Ready for delivery';
        if ($isPickup) {
            unset($flow['out_for_delivery']);
        }

        $currentKey = match ($status) {
            'ready_for_pickup', 'ready_for_delivery' => 'ready',
            'delivered' => 'completed',
            default => array_key_exists($status, $flow) ? $status : 'pending',
        };

        $keys = array_keys($flow);
        $currentIndex = array_search($currentKey, $keys, true);

        $steps = [];
        foreach ($keys as $i => $key) {
            $steps[] = [
                'label' => $flow[$key],
                'state' => $status === 'completed' || $i < $currentIndex ? 'done' : ($i === $currentIndex ? 'current' : 'upcoming'),
            ];
        }

        return $steps;
    }

    /** Compare phone numbers by their last nine digits so 07…, 2567… and +2567… all match. */
    private function phonesMatch(?string $stored, string $given): bool
    {
        $a = substr(preg_replace('/\D/', '', (string) $stored), -9);
        $b = substr(preg_replace('/\D/', '', $given), -9);

        return strlen($a) >= 7 && hash_equals($a, $b);
    }
}
