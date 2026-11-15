<?php

namespace App\Http\Controllers\Api\Mobile\Staff;

use App\Models\Order;
use App\Models\OrderLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** Online orders: list, detail and moving an order through its stages (manage_orders). */
class OrderController extends StaffController
{
    public function index(Request $request): JsonResponse
    {
        $this->authorizePermission($request, 'manage_orders');

        $query = $this->scopeBranch(Order::query(), $request);

        if ($search = trim((string) $request->query('q', ''))) {
            $query->where(fn ($q) => $q->where('order_number', 'like', "%{$search}%")
                ->orWhere('order_id', 'like', "%{$search}%")
                ->orWhere('customer_name', 'like', "%{$search}%")
                ->orWhere('phone', 'like', "%{$search}%"));
        }
        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        $page = $query->latest('id')->paginate($this->perPage($request));

        return response()->json([
            'data' => collect($page->items())->map(fn (Order $order) => $this->summary($order))->all(),
            'meta' => $this->meta($page),
            'stages' => Order::STAGES,
        ]);
    }

    public function show(Request $request, int $order): JsonResponse
    {
        $this->authorizePermission($request, 'manage_orders');

        return response()->json(['data' => $this->detail($this->find($request, $order))]);
    }

    public function updateStatus(Request $request, int $order): JsonResponse
    {
        $this->authorizePermission($request, 'manage_orders');
        $record = $this->find($request, $order);

        $data = $request->validate([
            'status' => ['required', Rule::in(array_keys(Order::STAGES))],
            'note' => 'nullable|string|max:1000',
        ]);

        $from = $record->stageLabel();
        if ($record->status !== $data['status']) {
            $record->update(['status' => $data['status']]);
            OrderLog::create([
                'order_id' => $record->id,
                'action' => 'status_changed',
                'description' => "Stage changed from {$from} to {$record->stageLabel()} by {$request->user()->name} (mobile app)."
                    .(filled($data['note'] ?? null) ? ' Note: '.$data['note'] : ''),
            ]);
        }

        return response()->json([
            'message' => "Order moved to {$record->stageLabel()}.",
            'data' => $this->detail($record->fresh()),
        ]);
    }

    private function find(Request $request, int $id): Order
    {
        return $this->scopeBranch(Order::query(), $request)->whereKey($id)->firstOrFail();
    }

    private function summary(Order $order): array
    {
        return [
            'id' => $order->id,
            'order_number' => $order->order_number ?: $order->order_id,
            'customer_name' => $order->customer_name,
            'phone' => $order->phone,
            'status' => $order->status,
            'status_label' => $order->stageLabel(),
            'payment_status' => $order->payment_status,
            'payment_method' => $order->payment_method,
            'total_amount' => $this->money($order->total_amount),
            'amount_paid' => $this->money($order->amount_paid),
            'balance_amount' => $this->money($order->balance_amount),
            'created_at' => optional($order->created_at)?->toISOString(),
        ];
    }

    private function detail(Order $order): array
    {
        $order->load(['order_items', 'order_logs' => fn ($q) => $q->latest('id'), 'order_payment_receipts', 'branch:id,name']);

        return $this->summary($order) + [
            'email' => $order->email,
            'delivery_location' => $order->delivery_location,
            'delivery_zone' => $order->delivery_zone,
            'address' => $order->address,
            'notes' => $order->notes,
            'branch_name' => $order->branch?->name,
            'subtotal' => $this->money($order->subtotal),
            'delivery_fee' => $this->money($order->delivery_fee),
            'discount' => $this->money($order->discount),
            'sale_id' => $order->sale_id,
            'items' => $order->order_items->map(fn ($item) => [
                'name' => $item->product_name,
                'sku' => $item->sku,
                'quantity' => (float) $item->quantity,
                'price' => $this->money($item->price),
                'total' => $this->money($item->subtotal),
            ])->values(),
            'logs' => $order->order_logs->map(fn ($log) => [
                'action' => $log->action,
                'description' => $log->description,
                'created_at' => optional($log->created_at)?->toISOString(),
            ])->values(),
            'receipts' => $order->order_payment_receipts->map(fn ($r) => [
                'receipt_number' => $r->receipt_number,
                'payment_method' => $r->payment_method,
                'amount_paid' => $this->money($r->amount_paid),
                'created_at' => optional($r->created_at)?->toISOString(),
            ])->values(),
        ];
    }
}
