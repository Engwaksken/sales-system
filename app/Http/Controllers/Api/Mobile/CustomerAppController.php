<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Models\AppNotification;
use App\Models\ChatConversation;
use App\Models\ChatMessage;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderPaymentReceipt;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Stock;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CustomerAppController extends Controller
{
    public function home(Request $request): JsonResponse
    {
        $customer = $this->customer($request);

        $orders = $this->ordersQuery($request, $customer);

        return response()->json([
            'profile' => $this->profilePayload($request, $customer),
            'counts' => [
                'all_orders' => (clone $orders)->count(),
                'pending_orders' => (clone $orders)
                    ->whereIn('status', ['pending', 'confirmed', 'processing'])
                    ->count(),
                'shipping_orders' => (clone $orders)
                    ->whereIn('status', ['shipped', 'shipping', 'out_for_delivery'])
                    ->count(),
                'delivered_orders' => (clone $orders)
                    ->whereIn('status', ['delivered', 'completed'])
                    ->count(),
                'payments' => $this->paymentsQuery($request, $customer)->count(),
                'unread_notifications' => $this->notificationsQuery($request)
                    ->where('is_read', false)
                    ->count(),
            ],
            'recent_orders' => (clone $orders)
                ->latest('id')
                ->limit(5)
                ->get()
                ->map(fn ($order) => $this->orderSummary($order))
                ->values(),
        ]);
    }

    public function products(Request $request): JsonResponse
    {
        $rows = Product::query()
            ->where('is_active', true)
            ->with('stock')
            ->orderBy('name')
            ->get()
            ->map(function ($product) {
                $stocks = $product->stock;
                $available = (int) $stocks->sum('quantity');
                $price = (float) ($stocks
                    ->where('quantity', '>', 0)
                    ->sortBy('selling_price')
                    ->first()?->selling_price ?? 0);

                $image = trim((string) $product->image_url);

                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'sku' => $product->sku,
                    'description' => $product->description,
                    'image_url' => $image === ''
                        ? null
                        : (str_starts_with($image, 'http://') ||
                           str_starts_with($image, 'https://')
                            ? $image
                            : asset('storage/'.ltrim($image, '/'))),
                    'available' => $available,
                    'selling_price' => $price,
                ];
            });

        return response()->json(['data' => $rows]);
    }

    public function orders(Request $request): JsonResponse
    {
        $customer = $this->customer($request);

        $rows = $this->ordersQuery($request, $customer)
            ->latest('id')
            ->get()
            ->map(fn ($order) => $this->orderSummary($order))
            ->values();

        return response()->json(['data' => $rows]);
    }

    public function order(
        Request $request,
        int $order
    ): JsonResponse {
        $customer = $this->customer($request);

        $record = $this->ordersQuery($request, $customer)
            ->where('orders.id', $order)
            ->with(['order_items', 'order_logs', 'order_payment_receipts'])
            ->firstOrFail();

        $payload = $this->orderSummary($record);
        $payload['items'] = $record->order_items
            ->map(fn ($item) => [
                'id' => $item->id,
                'product_name' => $item->product_name,
                'sku' => $item->sku,
                'quantity' => (int) $item->quantity,
                'price' => (float) $item->price,
                'subtotal' => (float) $item->subtotal,
            ])
            ->values();

        $payload['receipts'] = $record->order_payment_receipts
            ->map(fn ($receipt) => [
                'id' => $receipt->id,
                'receipt_number' => $receipt->receipt_number,
                'payment_method' => $receipt->payment_method,
                'amount_paid' => (float) $receipt->amount_paid,
                'currency' => $receipt->currency ?: 'UGX',
                'created_at' => optional($receipt->created_at)?->toISOString(),
            ])
            ->values();

        $payload['logs'] = $record->order_logs
            ->map(fn ($log) => [
                'action' => $log->action,
                'description' => $log->description,
                'created_at' => optional($log->created_at)?->toISOString(),
            ])
            ->values();

        $payload['timeline'] = $this->timeline($record);

        return response()->json(['data' => $payload]);
    }

    public function payments(Request $request): JsonResponse
    {
        $customer = $this->customer($request);

        $normalPayments = $this->paymentsQuery($request, $customer)
            ->latest('id')
            ->get()
            ->map(fn ($payment) => [
                'id' => 'payment-'.$payment->id,
                'receipt_no' => $payment->receipt_no,
                'invoice_no' => $payment->invoice_no,
                'amount' => (float) $payment->amount,
                'method' => $payment->method,
                'status' => $payment->status,
                'created_at' => optional($payment->created_at)?->toISOString(),
                'source' => 'sale',
            ]);

        $orderReceipts = collect();

        if (Schema::hasTable('order_payment_receipts')) {
            $orderReceipts = OrderPaymentReceipt::query()
                ->when(
                    $customer,
                    fn ($q) => $q->where('customer_id', $customer->id),
                    fn ($q) => $q->where('email', $request->user()->email)
                )
                ->latest('id')
                ->get()
                ->map(fn ($receipt) => [
                    'id' => 'order-'.$receipt->id,
                    'receipt_number' => $receipt->receipt_number,
                    'order_number' => $receipt->order_number,
                    'amount_paid' => (float) $receipt->amount_paid,
                    'payment_method' => $receipt->payment_method,
                    'status' => $receipt->gateway_status ?: 'successful',
                    'created_at' => optional($receipt->created_at)?->toISOString(),
                    'source' => 'order',
                ]);
        }

        return response()->json([
            'data' => $normalPayments
                ->concat($orderReceipts)
                ->sortByDesc('created_at')
                ->values(),
        ]);
    }

    public function notifications(Request $request): JsonResponse
    {
        $rows = $this->notificationsQuery($request)
            ->latest('id')
            ->limit(100)
            ->get()
            ->map(fn ($notification) => [
                'id' => $notification->id,
                'title' => $notification->title,
                'message' => $notification->message,
                'type' => $notification->type,
                'is_read' => (bool) $notification->is_read,
                'read_at' => optional($notification->read_at)?->toISOString(),
                'created_at' => optional($notification->created_at)?->toISOString(),
            ]);

        return response()->json(['data' => $rows]);
    }

    public function unreadCount(Request $request): JsonResponse
    {
        return response()->json([
            'count' => $this->notificationsQuery($request)
                ->where('is_read', false)
                ->count(),
        ]);
    }

    public function readNotification(
        Request $request,
        AppNotification $notification
    ): JsonResponse {
        abort_unless(
            $notification->user_id === null ||
            (int) $notification->user_id === (int) $request->user()->id,
            403
        );

        $notification->forceFill([
            'is_read' => true,
            'read_at' => now(),
        ])->save();

        return response()->json(['ok' => true]);
    }

    public function readAllNotifications(Request $request): JsonResponse
    {
        $this->notificationsQuery($request)
            ->where('is_read', false)
            ->update([
                'is_read' => true,
                'read_at' => now(),
            ]);

        return response()->json(['ok' => true]);
    }

    public function profile(Request $request): JsonResponse
    {
        return response()->json([
            'data' => $this->profilePayload(
                $request,
                $this->customer($request)
            ),
        ]);
    }

    public function updateProfile(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:500'],
        ]);

        DB::transaction(function () use ($request, $data) {
            $user = $request->user();
            $user->name = $data['name'];
            $user->save();

            $customer = $this->customer($request);

            if (! $customer) {
                $customer = new Customer();
                $customer->email = $user->email;
                $customer->branch_id = $user->branch_id;

                if (Schema::hasColumn('customers', 'user_id')) {
                    $customer->user_id = $user->id;
                }
            }

            $customer->name = $data['name'];
            $customer->phone = $data['phone'];
            $customer->address = $data['address'];
            $customer->save();

            // Keep open orders aligned with the customer's latest contact details.
            Order::query()
                ->where('email', $user->email)
                ->whereIn('status', ['pending', 'confirmed', 'processing'])
                ->update([
                    'customer_name' => $data['name'],
                    'phone' => $data['phone'],
                    'address' => $data['address'],
                ]);
        });

        return $this->profile($request);
    }

    public function chat(Request $request): JsonResponse
    {
        if (
            ! Schema::hasTable('chat_conversations') ||
            ! Schema::hasTable('chat_messages')
        ) {
            return response()->json([
                'conversation_id' => null,
                'messages' => [],
            ]);
        }

        $conversation = $this->conversation($request);

        if (! $conversation) {
            return response()->json([
                'conversation_id' => null,
                'messages' => [],
            ]);
        }

        // Staff messages are read once the customer opens the conversation.
        ChatMessage::query()
            ->where('conversation_id', $conversation->id)
            ->where('sender_type', 'staff')
            ->where('is_read', false)
            ->update(['is_read' => true]);

        return response()->json([
            'conversation_id' => $conversation->id,
            'status' => $conversation->status,
            'messages' => ChatMessage::query()
                ->where('conversation_id', $conversation->id)
                ->orderBy('id')
                ->get()
                ->map(fn ($message) => [
                    'id' => $message->id,
                    'sender_type' => $message->sender_type,
                    'message' => $message->message,
                    'created_at' => optional($message->created_at)?->toISOString(),
                ]),
        ]);
    }

    public function sendChat(Request $request): JsonResponse
    {
        $data = $request->validate([
            'message' => ['required', 'string', 'max:5000'],
        ]);

        abort_unless(
            Schema::hasTable('chat_conversations') &&
            Schema::hasTable('chat_messages'),
            503,
            'Chat support is not configured yet.'
        );

        $conversation = $this->conversation($request);

        if (! $conversation) {
            $profile = $this->profilePayload(
                $request,
                $this->customer($request)
            );

            $parts = preg_split(
                '/\s+/',
                trim((string) $profile['name']),
                2
            );

            $conversation = ChatConversation::create([
                'first_name' => $parts[0] ?: 'Customer',
                'last_name' => $parts[1] ?? '',
                'email' => $request->user()->email,
                'phone' => $profile['phone'] ?: 'Not provided',
                'branch_id' => $request->user()->branch_id,
                'status' => 'open',
            ]);
        }

        $message = ChatMessage::create([
            'conversation_id' => $conversation->id,
            'sender_type' => 'customer',
            'message' => $data['message'],
            'is_read' => false,
        ]);

        if ($conversation->status === 'closed') {
            $conversation->update(['status' => 'open']);
        } else {
            $conversation->touch();
        }

        /*
         * The existing web support/AI workflow can answer this message.
         * This endpoint deliberately persists the customer message first so
         * a staff member can continue the same conversation.
         */
        return response()->json([
            'ok' => true,
            'conversation_id' => $conversation->id,
            'message_id' => $message->id,
        ], 201);
    }

    public function history(Request $request): JsonResponse
    {
        $customer = $this->customer($request);

        $orders = $this->ordersQuery($request, $customer)
            ->latest('id')
            ->limit(50)
            ->get()
            ->map(fn ($order) => [
                'type' => 'order',
                'title' => $order->order_number ?: $order->order_id,
                'subtitle' => 'Order · '.ucfirst(str_replace('_', ' ', $order->status)),
                'date' => optional($order->created_at)?->format('d M Y'),
                'sort' => optional($order->created_at)?->timestamp ?? 0,
            ]);

        $payments = $this->paymentsQuery($request, $customer)
            ->latest('id')
            ->limit(50)
            ->get()
            ->map(fn ($payment) => [
                'type' => 'payment',
                'title' => $payment->receipt_no ?: 'Payment',
                'subtitle' => 'UGX '.number_format((float) $payment->amount, 0).' · '.$payment->method,
                'date' => optional($payment->created_at)?->format('d M Y'),
                'sort' => optional($payment->created_at)?->timestamp ?? 0,
            ]);

        return response()->json([
            'data' => $orders
                ->concat($payments)
                ->sortByDesc('sort')
                ->take(100)
                ->map(function ($row) {
                    unset($row['sort']);
                    return $row;
                })
                ->values(),
        ]);
    }

    private function customer(Request $request): ?Customer
    {
        if (! Schema::hasTable('customers')) {
            return null;
        }

        return Customer::query()
            ->when(
                Schema::hasColumn('customers', 'user_id'),
                fn ($q) => $q->where('user_id', $request->user()->id),
                fn ($q) => $q->where('email', $request->user()->email)
            )
            ->first();
    }

    private function ordersQuery(
        Request $request,
        ?Customer $customer
    ) {
        return Order::query()
            ->where(function ($q) use ($request, $customer) {
                if ($customer) {
                    $q->where('customer_id', $customer->id)
                        ->orWhere('email', $request->user()->email);
                } else {
                    $q->where('email', $request->user()->email);
                }
            });
    }

    private function paymentsQuery(
        Request $request,
        ?Customer $customer
    ) {
        $query = Payment::query();

        if ($customer) {
            return $query->where('customer_id', $customer->id);
        }

        if (Schema::hasTable('sales')) {
            return $query->whereIn(
                'sale_id',
                DB::table('sales')
                    ->whereIn(
                        'customer_id',
                        DB::table('customers')
                            ->where('email', $request->user()->email)
                            ->pluck('id')
                    )
                    ->pluck('id')
            );
        }

        return $query->whereRaw('1 = 0');
    }

    private function notificationsQuery(Request $request)
    {
        return AppNotification::query()
            ->where(function ($q) use ($request) {
                $q->whereNull('user_id')
                    ->orWhere('user_id', $request->user()->id);
            });
    }

    private function conversation(Request $request): ?ChatConversation
    {
        return ChatConversation::query()
            ->where('email', $request->user()->email)
            ->latest('id')
            ->first();
    }

    private function profilePayload(
        Request $request,
        ?Customer $customer
    ): array {
        return [
            'id' => $request->user()->id,
            'customer_id' => $customer?->id,
            'name' => $customer?->name ?: $request->user()->name,
            'email' => $request->user()->email,
            'phone' => $customer?->phone,
            'address' => $customer?->address,
            'branch_id' => $customer?->branch_id ?: $request->user()->branch_id,
        ];
    }

    private function orderSummary(Order $order): array
    {
        return [
            'id' => $order->id,
            'order_id' => $order->order_id,
            'order_number' => $order->order_number ?: $order->order_id,
            'status' => $order->status,
            'status_label' => ucfirst(
                str_replace('_', ' ', (string) $order->status)
            ),
            'status_group' => $this->statusGroup((string) $order->status),
            'payment_status' => $order->payment_status,
            'payment_method' => $order->payment_method,
            'total_amount' => (float) $order->total_amount,
            'amount_paid' => (float) $order->amount_paid,
            'balance_amount' => (float) $order->balance_amount,
            'delivery_location' => $order->delivery_location,
            'address' => $order->address,
            'created_at' => optional($order->created_at)?->toISOString(),
            'paid_at' => optional($order->paid_at)?->toISOString(),
        ];
    }

    private function statusGroup(string $status): string
    {
        $status = strtolower($status);

        return match ($status) {
            'shipped', 'shipping', 'out_for_delivery' => 'shipping',
            'delivered', 'completed' => 'delivered',
            default => 'pending',
        };
    }

    private function timeline(Order $order): Collection
    {
        $statuses = [
            ['key' => 'pending', 'label' => 'Order Placed'],
            ['key' => 'confirmed', 'label' => 'Confirmed'],
            ['key' => 'processing', 'label' => 'Processing'],
            ['key' => 'shipped', 'label' => 'Shipped'],
            ['key' => 'delivered', 'label' => 'Delivered'],
        ];

        $rank = [
            'pending' => 0,
            'confirmed' => 1,
            'processing' => 2,
            'shipped' => 3,
            'shipping' => 3,
            'out_for_delivery' => 3,
            'delivered' => 4,
            'completed' => 4,
        ];

        $current = $rank[strtolower((string) $order->status)] ?? 0;

        $logs = Schema::hasTable('order_logs')
            ? DB::table('order_logs')
                ->where('order_id', $order->id)
                ->orderBy('id')
                ->get()
            : collect();

        return collect($statuses)->map(
            function ($status, $index) use ($current, $logs, $order) {
                $log = $logs->first(
                    fn ($row) => strtolower((string) $row->action) === $status['key']
                );

                return [
                    'key' => $status['key'],
                    'label' => $status['label'],
                    'done' => $index < $current,
                    'active' => $index === $current,
                    'date' => $log
                        ? optional($log->created_at
                            ? \Carbon\Carbon::parse($log->created_at)
                            : null)?->format('d M Y H:i')
                        : ($index === 0
                            ? optional($order->created_at)?->format('d M Y H:i')
                            : null),
                ];
            }
        );
    }
}
