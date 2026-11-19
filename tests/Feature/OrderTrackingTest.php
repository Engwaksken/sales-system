<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Order;
use App\Models\Product;
use App\Models\Stock;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class OrderTrackingTest extends TestCase
{
    use RefreshDatabase;

    private Branch $branch;
    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        $this->branch = Branch::create(['name' => 'Main Branch', 'location' => 'Kampala']);
        $this->product = Product::create(['name' => 'Steel Bucket', 'is_active' => true]);
        Stock::create([
            'product_id' => $this->product->id, 'branch_id' => $this->branch->id,
            'quantity' => 5, 'total_stock' => 5, 'selling_price' => 10000,
        ]);
    }

    private function placeOrder(): array
    {
        return $this->postJson(route('checkout.store'), [
            'customer_name' => 'Jane Buyer',
            'phone' => '0700123456',
            'delivery_location' => 'Kampala Road',
            'branch_id' => $this->branch->id,
            'payment_method' => 'cash-on-delivery',
            'items' => [['id' => $this->product->id, 'qty' => 2]],
        ])->assertOk()->json();
    }

    public function test_checkout_returns_a_working_tracking_link(): void
    {
        $data = $this->placeOrder();

        $this->assertNotEmpty($data['tracking_url']);
        $this->get($data['tracking_url'])
            ->assertOk()
            ->assertSee($data['order_number'])
            ->assertSee('Order received')
            ->assertSee('Steel Bucket');
    }

    public function test_tracking_page_reflects_stage_changes(): void
    {
        $data = $this->placeOrder();
        Order::where('order_number', $data['order_number'])->update(['status' => 'out_for_delivery']);

        $this->get($data['tracking_url'])->assertOk()->assertSee('Out for Delivery');
    }

    public function test_unsigned_or_tampered_links_are_rejected(): void
    {
        $data = $this->placeOrder();

        $this->get(route('orders.track.show', ['reference' => $data['order_number']]))->assertForbidden();
        $this->get(str_replace($data['order_number'], 'ORD-20260101-AAAAAA', $data['tracking_url']))->assertForbidden();
    }

    public function test_lookup_needs_the_matching_phone_number(): void
    {
        $data = $this->placeOrder();

        $this->post(route('orders.track.lookup'), ['reference' => $data['order_number'], 'phone' => '0700999999'])
            ->assertSessionHasErrors('reference');

        $this->post(route('orders.track.lookup'), ['reference' => strtolower($data['order_number']), 'phone' => '+256 700 123456'])
            ->assertRedirect($data['tracking_url']);
    }

    public function test_shop_links_to_order_tracking(): void
    {
        $this->get(route('shop.index'))->assertOk()
            ->assertSee(route('orders.track'), false)
            ->assertSee('id="orderSuccessTrack"', false);

        $this->get(route('orders.track'))->assertOk()->assertSee('Track your order');
    }
}
