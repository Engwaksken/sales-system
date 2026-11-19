<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Order;
use App\Models\Product;
use App\Mail\OrderStageUpdatedMail;
use App\Models\Stock;
use App\Models\User;
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

    private function admin(): User
    {
        return User::create([
            'name' => 'Admin', 'email' => 'admin@example.com', 'password' => 'password',
            'role' => 'admin', 'branch_id' => $this->branch->id,
        ]);
    }

    private function placeOrderWithEmail(): array
    {
        return $this->postJson(route('checkout.store'), [
            'customer_name' => 'Jane Buyer',
            'phone' => '0700123456',
            'email' => 'jane@example.com',
            'delivery_location' => 'Kampala Road',
            'branch_id' => $this->branch->id,
            'payment_method' => 'cash-on-delivery',
            'items' => [['id' => $this->product->id, 'qty' => 1]],
        ])->assertOk()->json();
    }

    public function test_staff_stage_change_logs_emails_and_shows_on_tracking_page(): void
    {
        $data = $this->placeOrderWithEmail();
        $order = Order::where('order_number', $data['order_number'])->sole();

        $this->actingAs($this->admin())
            ->from(route('orders.show', $order))
            ->patch(route('orders.stage', $order), ['status' => 'confirmed', 'note' => 'Called customer'])
            ->assertRedirect(route('orders.show', $order))
            ->assertSessionHas('status');

        $this->assertSame('confirmed', $order->fresh()->status);
        $this->assertDatabaseHas('order_logs', ['order_id' => $order->id, 'action' => 'status_changed']);
        Mail::assertSent(OrderStageUpdatedMail::class, fn ($m) => $m->hasTo('jane@example.com') && $m->stageLabel === 'Confirmed');

        $this->get($data['tracking_url'])->assertOk()
            ->assertSee('Confirmed')
            ->assertSee(now()->format('d M Y'));
    }

    public function test_stage_change_rejects_unknown_stages(): void
    {
        $data = $this->placeOrder();
        $order = Order::where('order_number', $data['order_number'])->sole();

        $this->actingAs($this->admin())
            ->patch(route('orders.stage', $order), ['status' => 'teleported'])
            ->assertSessionHasErrors('status');

        $this->assertSame('pending', $order->fresh()->status);
    }

    public function test_any_status_change_emails_the_customer_but_other_edits_do_not(): void
    {
        $data = $this->placeOrderWithEmail();
        $order = Order::where('order_number', $data['order_number'])->sole();

        $order->update(['notes' => 'Gate code 1234']);
        Mail::assertNotSent(OrderStageUpdatedMail::class);

        $order->update(['status' => 'out_for_delivery']);
        Mail::assertSent(OrderStageUpdatedMail::class, 1);
    }

    public function test_admin_order_page_has_stage_control_and_tracking_link(): void
    {
        $data = $this->placeOrder();
        $order = Order::where('order_number', $data['order_number'])->sole();

        $this->actingAs($this->admin())->get(route('orders.show', $order))->assertOk()
            ->assertSee(route('orders.stage', $order), false)
            ->assertSee('Customer tracking link')
            ->assertSee('track-order/'.$order->order_number.'?signature=', false);
    }

    public function test_tracking_pages_and_order_emails_carry_the_store_branding(): void
    {
        \App\Models\Setting::create(['setting_key' => 'site_name', 'setting_value' => 'Acme Hardware']);
        \App\Models\Setting::create(['setting_key' => 'theme_primary', 'setting_value' => '#7C3AED']);
        \App\Models\Setting::create(['setting_key' => 'system_logo', 'setting_value' => 'uploads/settings/logo-acme.png']);
        $this->branch->update(['contact' => '0772000111', 'branch_email' => 'main@acme.test']);
        \Illuminate\Support\Facades\Cache::flush();
        \App\Support\Brand::flush();

        $data = $this->placeOrderWithEmail();
        $order = Order::with(['order_items', 'branch'])->where('order_number', $data['order_number'])->sole();

        $this->get(route('orders.track'))->assertOk()
            ->assertSee('Acme Hardware')
            ->assertSee('uploads/settings/logo-acme.png', false)
            ->assertSee('--shop-primary:#7c3aed', false);

        $this->get($data['tracking_url'])->assertOk()
            ->assertSee('Acme Hardware')
            ->assertSee('0772000111')
            ->assertSee('main@acme.test');

        $confirmation = (new \App\Mail\OrderConfirmationMail($order, $this->branch->fresh()))->render();
        $stage = (new OrderStageUpdatedMail($order, 'Confirmed', 'Confirmed.'))->render();

        foreach ([$confirmation, $stage] as $html) {
            $this->assertStringContainsString('Acme Hardware', $html);
            $this->assertStringContainsString('uploads/settings/logo-acme.png', $html);
            $this->assertStringContainsString('background-color: #7c3aed', $html);
            $this->assertStringContainsString('0772000111', $html);
            $this->assertStringNotContainsString('Laravel', $html);
        }
    }

    public function test_cancelled_orders_show_a_notice_instead_of_the_timeline(): void
    {
        $data = $this->placeOrder();
        Order::where('order_number', $data['order_number'])->sole()->update(['status' => 'cancelled']);

        $this->get($data['tracking_url'])->assertOk()->assertSee('This order was cancelled.');
    }
}
