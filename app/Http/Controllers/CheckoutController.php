<?php

namespace App\Http\Controllers;

use App\Mail\OrderConfirmationMail;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\DeliveryZone;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\PaymentGateway;
use App\Models\Stock;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Throwable;

/**
 * Guest checkout — faithful port of guest_order_api.php's create_guest_order action.
 * Same validation rules, same customer-matching-by-email-or-phone logic, same
 * transactional stock decrement, same order reference format (ORD-YYYYMMDD-XXXXXX).
 */
class CheckoutController extends Controller
{
    public function store(Request $request)
    {
        try {
            $data = $request->validate([
                'customer_name' => ['required', 'string', 'max:150'],
                'phone' => ['required', 'string', 'max:30'],
                'email' => ['nullable', 'email', 'max:150'],
                'delivery_location' => ['required', 'string', 'max:1000'],
                'notes' => ['nullable', 'string', 'max:2000'],
                'branch_id' => ['required', 'integer', 'exists:branches,id'],
                'payment_method' => ['required', 'string', 'in:mobile-money,cash-on-delivery,card,bank-transfer'],
                'delivery_zone_id' => ['nullable', 'integer', 'exists:delivery_zones,id'],
                'mm_phone' => ['nullable', 'string', 'max:30'],
                'mm_network' => ['nullable', 'string', 'in:MTN,AIRTEL,mtn,airtel'],
                'gateway_code' => ['nullable', 'string', 'max:100'],
                'items' => ['required', 'array', 'min:1'],
                'items.*.id' => ['required', 'integer', 'exists:products,id'],
                'items.*.qty' => ['required', 'integer', 'min:1', 'max:100000'],
            ], [
                'customer_name.required' => 'Please enter your full name.',
                'phone.required' => 'Please enter your phone number.',
                'delivery_location.required' => 'Please enter your delivery address or pickup instructions.',
                'payment_method.required' => 'Please select a payment method.',
                'items.required' => 'Your shopping cart is empty.',
                'items.array' => 'The shopping cart data is invalid. Please refresh the page and try again.',
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'msg' => collect($e->errors())->flatten()->first() ?: 'Please check the order details and try again.',
                'errors' => $e->errors(),
            ], 422);
        }

        $phone = $this->normalizePhone($data['phone']);
        $email = $data['email'] ?? null;
        $paymentMethod = strtolower($data['payment_method']);
        $mmNetwork = strtoupper($data['mm_network'] ?? '');
        $gatewayCode = strtolower($data['gateway_code'] ?? '');
        $mmPhone = $paymentMethod === 'mobile-money'
            ? $this->normalizeUgPhone($data['mm_phone'] ?? '')
            : $this->normalizePhone($data['mm_phone'] ?? '');

        if (! preg_match('/^\+?\d{7,15}$/', $phone)) {
            return $this->fail('Phone number format is invalid.');
        }

        $gateway = null;
        if ($paymentMethod === 'mobile-money') {
            if (! preg_match('/^2567\d{8}$/', $mmPhone)) {
                return $this->fail('Mobile money phone number must be a valid Ugandan number in format 07XXXXXXXX or 2567XXXXXXXX.');
            }
            if (! in_array($mmNetwork, ['MTN', 'AIRTEL'], true)) {
                return $this->fail('Please select MTN or Airtel as your mobile money network.');
            }
            if ($gatewayCode === '') {
                return $this->fail('Please select a payment gateway for mobile money.');
            }

            $gateway = PaymentGateway::where('gateway_code', $gatewayCode)->where('is_active', true)->first();
            if (! $gateway || ! $gateway->supports_collection) {
                return $this->fail('Selected payment gateway is invalid or not active. Please refresh and try again.');
            }
            if ($mmNetwork === 'MTN' && ! $gateway->supports_mtn) {
                return $this->fail('Selected gateway does not support MTN. Please choose a different gateway.');
            }
            if ($mmNetwork === 'AIRTEL' && ! $gateway->supports_airtel) {
                return $this->fail('Selected gateway does not support Airtel. Please choose a different gateway.');
            }
        }

        $branch = Branch::find($data['branch_id']);

        $deliveryZoneId = (int) ($data['delivery_zone_id'] ?? 0);
        $deliveryZone = '';
        $deliveryFee = 0.0;
        if ($deliveryZoneId > 0) {
            $zone = DeliveryZone::where('id', $deliveryZoneId)->where('is_active', true)->first();
            if ($zone) {
                $deliveryZone = $zone->zone_name;
                $deliveryFee = (float) $zone->fee;
            } else {
                $deliveryZoneId = 0;
            }
        }

        // Validate cart items against current stock/pricing at this branch
        $validatedItems = [];
        $subtotal = 0.0;

        foreach ($data['items'] as $item) {
            $productId = (int) $item['id'];
            $qty = (int) $item['qty'];

            $stock = Stock::where('product_id', $productId)->where('branch_id', $branch->id)->first();
            if (! $stock || ! $stock->product) {
                return $this->fail("Product #{$productId} is not available at this branch.");
            }

            if ($stock->quantity < $qty) {
                return $this->fail(
                    "Insufficient stock for \"{$stock->product->name}\". Available: {$stock->quantity}, requested: {$qty}. Please update your cart."
                );
            }

            $price = (float) $stock->selling_price;
            $lineSubtotal = $price * $qty;
            $subtotal += $lineSubtotal;

            $validatedItems[] = [
                'product_id' => $productId,
                'name' => $stock->product->name,
                'sku' => $stock->product->sku ?? '',
                'qty' => $qty,
                'price' => $price,
                'subtotal' => $lineSubtotal,
            ];
        }

        $totalAmount = $subtotal + $deliveryFee;
        $paymentStatus = $paymentMethod === 'cash-on-delivery' ? 'unpaid' : 'pending';

        try {
            [$order, $customer] = DB::transaction(function () use (
                $data, $phone, $email, $branch, $deliveryZoneId, $deliveryZone, $deliveryFee,
                $subtotal, $totalAmount, $paymentMethod, $mmPhone, $mmNetwork, $gateway,
                $gatewayCode, $paymentStatus, $validatedItems
            ) {
                $customer = $this->findOrUpdateCustomer($branch->id, $data['customer_name'], $phone, $email, $data['delivery_location']);

                $ordersCount = Order::where('customer_id', $customer->id)->orWhere('phone', $phone)->count() + 1;
                $orderRef = $this->generateOrderRef();

                $order = Order::create([
                    'order_id' => $orderRef,
                    'order_number' => $orderRef,
                    'customer_id' => $customer->id,
                    'customer_name' => $data['customer_name'],
                    'phone' => $phone,
                    'email' => $email,
                    'address' => $data['delivery_location'],
                    'delivery_location' => $data['delivery_location'],
                    'delivery_zone_id' => $deliveryZoneId ?: null,
                    'delivery_zone' => $deliveryZone,
                    'delivery_fee' => $deliveryFee,
                    'subtotal' => $subtotal,
                    'total_amount' => $totalAmount,
                    'branch_id' => $branch->id,
                    'payment_method' => $paymentMethod,
                    'mm_phone' => $mmPhone ?: null,
                    'mm_network' => $mmNetwork ?: null,
                    'gateway_id' => $gateway?->id,
                    'gateway_code' => $gateway?->gateway_code,
                    'gateway_status' => $gateway ? 'PENDING' : null,
                    'gateway_message' => $gateway ? 'Awaiting payment collection. PIN prompt will be sent to phone.' : null,
                    'payment_status' => $paymentStatus,
                    'orders_count' => $ordersCount,
                    'notes' => $data['notes'] ?? null,
                    'status' => 'pending',
                    'is_guest_order' => true,
                ]);

                foreach ($validatedItems as $item) {
                    OrderItem::create([
                        'order_id' => $order->id,
                        'product_id' => $item['product_id'],
                        'product_name' => $item['name'],
                        'sku' => $item['sku'],
                        'quantity' => $item['qty'],
                        'price' => $item['price'],
                        'subtotal' => $item['subtotal'],
                    ]);

                    $affected = Stock::where('product_id', $item['product_id'])
                        ->where('branch_id', $branch->id)
                        ->where('quantity', '>=', $item['qty'])
                        ->decrement('quantity', $item['qty']);

                    if (! $affected) {
                        throw new RuntimeException("Stock for \"{$item['name']}\" was exhausted during checkout. Please refresh and try again.");
                    }
                }

                return [$order, $customer];
            });
        } catch (Throwable $e) {
            report($e);

            return $this->fail($e->getMessage() ?: 'An unexpected error occurred. Please try again.');
        }

        if ($email) {
            try {
                Mail::to($email)->send(new OrderConfirmationMail($order->load('order_items'), $branch));
            } catch (Throwable $e) {
                report($e);
            }
        }

        return response()->json([
            'status' => 'ok',
            'order_id' => $order->order_number,
            'order_number' => $order->order_number,
            'db_order_id' => $order->id,
            'customer_id' => $customer->id,
            'customer_name' => $order->customer_name,
            'branch' => $branch->name,
            'delivery_location' => $order->delivery_location,
            'delivery_zone' => $order->delivery_zone,
            'delivery_fee_raw' => (float) $order->delivery_fee,
            'subtotal_raw' => (float) $order->subtotal,
            'total_raw' => (float) $order->total_amount,
            'payment_method' => $order->payment_method,
            'payment_status' => $order->payment_status,
            'gateway_code' => $order->gateway_code,
            'items_count' => count($validatedItems),
            'items' => $validatedItems,
        ]);
    }

    protected function findOrUpdateCustomer(int $branchId, string $name, string $phone, ?string $email, string $address): Customer
    {
        $emailCustomer = $email ? Customer::where('email', $email)->first() : null;
        $phoneCustomer = Customer::where('phone', $phone)->first();

        if ($emailCustomer && $phoneCustomer && $emailCustomer->id !== $phoneCustomer->id) {
            throw new RuntimeException(
                'This email address belongs to a different customer record than the provided phone number. '.
                'Please use the correct email/phone combination or clean up duplicate customer records.'
            );
        }

        $matched = $emailCustomer ?: $phoneCustomer;

        if ($matched) {
            $matched->update([
                'branch_id' => $branchId,
                'name' => $name,
                'phone' => $phone,
                'email' => $matched->email ?: $email,
                'address' => $address,
            ]);

            return $matched;
        }

        return Customer::create([
            'branch_id' => $branchId,
            'name' => $name,
            'phone' => $phone,
            'email' => $email,
            'address' => $address,
        ]);
    }

    protected function generateOrderRef(): string
    {
        $prefix = 'ORD-'.now()->format('Ymd').'-';

        for ($i = 0; $i < 12; $i++) {
            $candidate = $prefix.strtoupper(substr(bin2hex(random_bytes(3)), 0, 6));
            if (! Order::where('order_id', $candidate)->orWhere('order_number', $candidate)->exists()) {
                return $candidate;
            }
        }

        return $prefix.strtoupper(substr(md5(uniqid('', true)), 0, 6));
    }

    protected function normalizePhone(string $phone): string
    {
        return trim(preg_replace('/\s+/', '', $phone));
    }

    protected function normalizeUgPhone(string $phone): string
    {
        $digits = preg_replace('/\D/', '', $phone);
        if (str_starts_with($digits, '0') && strlen($digits) === 10) {
            $digits = '256'.substr($digits, 1);
        }

        return $digits;
    }

    protected function fail(string $message, int $status = 422)
    {
        return response()->json([
            'status' => 'error',
            'msg' => $message,
        ], $status);
    }
}
