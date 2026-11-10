<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Customer;
use App\Models\JournalEntry;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Sale;
use App\Models\Stock;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Tests\TestCase;

class SalePaymentsTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Branch $branch;
    private Product $product;
    private Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();

        $this->user = User::create([
            'name' => 'Cashier',
            'email' => Str::random(10).'@example.com',
            'password' => 'password',
            'role' => 'admin',
            'branch_id' => null,
        ]);
        $this->branch = Branch::create(['name' => 'Main Branch', 'location' => 'Kampala']);
        $this->product = Product::create(['name' => 'Steel Bucket', 'sku' => 'BKT-1', 'is_active' => true]);
        Stock::create([
            'product_id' => $this->product->id,
            'branch_id' => $this->branch->id,
            'quantity' => 10,
            'total_stock' => 10,
            'selling_price' => 10000,
            'cost_price' => 6000,
        ]);
        $this->customer = Customer::create(['name' => 'Jane Buyer', 'phone' => '0700000000']);
    }

    private function sell(array $payments = null, ?int $customerId = null, int $qty = 3, array $extra = [])
    {
        $body = [
            'branch_id' => $this->branch->id,
            'customer_id' => $customerId,
            'items' => [['product_id' => $this->product->id, 'quantity' => $qty]],
        ] + $extra;

        if ($payments !== null) {
            $body['payments'] = $payments;
        }

        return $this->actingAs($this->user)->post(route('sales.pos-store'), $body);
    }

    private function stockLeft(): int
    {
        return (int) Stock::where('product_id', $this->product->id)->value('quantity');
    }

    public function test_walk_in_cash_sale_is_paid_with_one_receipt(): void
    {
        $this->sell([['method' => 'Cash', 'amount' => 30000]])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $sale = Sale::sole();
        $this->assertSame('paid', $sale->payment_status);
        $this->assertSame('Cash', $sale->payment_method);
        $this->assertEquals(0, (float) $sale->balance_amount);
        $this->assertSame(1, Payment::count());
        $this->assertSame(7, $this->stockLeft());
    }

    public function test_split_payment_creates_a_receipt_per_tender(): void
    {
        $this->sell([
            ['method' => 'Cash', 'amount' => 10000],
            ['method' => 'Mobile Money', 'amount' => 20000],
        ])->assertRedirect(route('sales.index'))->assertSessionHasNoErrors();

        $sale = Sale::sole();
        $this->assertSame('paid', $sale->payment_status);
        $this->assertSame('Split (Cash + Mobile Money)', $sale->payment_method);
        $receipts = Payment::orderBy('id')->pluck('receipt_no');
        $this->assertCount(2, $receipts);
        $this->assertNotSame($receipts[0], $receipts[1]);
    }

    public function test_walk_in_cannot_buy_on_part_payment_or_credit(): void
    {
        $this->sell([['method' => 'Cash', 'amount' => 5000]])->assertSessionHasErrors('customer_id');
        $this->sell([])->assertSessionHasErrors('customer_id');

        $this->assertSame(0, Sale::count());
        $this->assertSame(0, Payment::count());
        $this->assertSame(10, $this->stockLeft(), 'stock must roll back with the rejected sale');
    }

    public function test_overpayment_is_rejected(): void
    {
        $this->sell([['method' => 'Cash', 'amount' => 50000]], $this->customer->id)->assertSessionHasErrors('payments');
        $this->assertSame(0, Sale::count());
    }

    public function test_registered_customer_can_part_pay(): void
    {
        $this->sell([['method' => 'Cash', 'amount' => 12000]], $this->customer->id)->assertSessionHasNoErrors();

        $sale = Sale::sole();
        $this->assertSame('partial', $sale->payment_status);
        $this->assertEquals(12000, (float) $sale->partial_amount);
        $this->assertEquals(18000, (float) $sale->balance_amount);
        $this->assertSame(1, Payment::count());
    }

    public function test_registered_customer_can_buy_fully_on_credit(): void
    {
        $this->sell([], $this->customer->id)->assertRedirect(route('sales.index'))->assertSessionHasNoErrors();

        $sale = Sale::sole();
        $this->assertSame('pending', $sale->payment_status);
        $this->assertSame('Credit', $sale->payment_method);
        $this->assertEquals(30000, (float) $sale->balance_amount);
        $this->assertSame(0, Payment::count());
        $this->assertSame(7, $this->stockLeft());
    }

    public function test_credit_is_settled_by_several_later_payments(): void
    {
        $this->sell([], $this->customer->id);
        $sale = Sale::sole();

        $this->actingAs($this->user)
            ->post(route('sales.record-payment', $sale), ['payments' => [['method' => 'Cash', 'amount' => 10000]]])
            ->assertSessionHasNoErrors();
        $this->assertSame('partial', $sale->fresh()->payment_status);

        $this->actingAs($this->user)
            ->post(route('sales.record-payment', $sale), ['payments' => [
                ['method' => 'Mobile Money', 'amount' => 15000],
                ['method' => 'Card', 'amount' => 5000],
            ]])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $sale->refresh();
        $this->assertSame('paid', $sale->payment_status);
        $this->assertEquals(0, (float) $sale->balance_amount);
        $this->assertSame(3, $sale->payments()->count());

        $this->actingAs($this->user)
            ->post(route('sales.record-payment', $sale), ['amount' => 1000, 'method' => 'Cash'])
            ->assertSessionHasErrors('payment');
    }

    public function test_paying_more_than_the_balance_is_rejected(): void
    {
        $this->sell([['method' => 'Cash', 'amount' => 20000]], $this->customer->id);
        $sale = Sale::sole();

        $this->actingAs($this->user)
            ->post(route('sales.record-payment', $sale), ['payments' => [['method' => 'Cash', 'amount' => 10001]]])
            ->assertSessionHasErrors('payment');

        $this->assertSame(1, $sale->payments()->count());
    }

    public function test_deleting_a_payment_reopens_the_balance(): void
    {
        $this->sell([
            ['method' => 'Cash', 'amount' => 10000],
            ['method' => 'Mobile Money', 'amount' => 20000],
        ], $this->customer->id);
        $sale = Sale::sole();

        $this->actingAs($this->user)
            ->delete(route('payments.destroy', $sale->payments()->where('method', 'Mobile Money')->first()));

        $sale->refresh();
        $this->assertSame('partial', $sale->payment_status);
        $this->assertEquals(20000, (float) $sale->balance_amount);
        $this->assertSame('Cash', $sale->payment_method);
    }

    public function test_receivable_ledger_nets_to_the_unpaid_balance(): void
    {
        $this->sell([['method' => 'Mobile Money', 'amount' => 12000]], $this->customer->id);

        $ar = JournalEntry::where('account_code', '1100');
        $this->assertEquals(18000, (float) $ar->sum('debit') - (float) $ar->sum('credit'));
        $this->assertEquals(12000, (float) JournalEntry::where('account_code', '1010')->sum('debit'));
    }

    public function test_legacy_single_method_request_is_paid_in_full(): void
    {
        $this->sell(null, null, 2, ['payment_method' => 'Card'])->assertSessionHasNoErrors();

        $sale = Sale::sole();
        $this->assertSame('paid', $sale->payment_status);
        $this->assertSame('Card', $sale->payment_method);
        $this->assertEquals(20000, (float) Payment::sole()->amount);
    }

    public function test_manual_entry_on_credit_needs_a_customer(): void
    {
        $base = [
            'transaction_type' => 'sale',
            'total_amount' => 50000,
            'discount' => 0,
            'vat' => 0,
            'sale_date' => '2026-11-10',
            'branch_id' => $this->branch->id,
            'payments' => [['method' => 'Cash', 'amount' => 20000]],
        ];

        $this->actingAs($this->user)->post(route('sales.store'), $base)->assertSessionHasErrors('customer_id');

        $this->actingAs($this->user)
            ->post(route('sales.store'), $base + ['customer_id' => $this->customer->id])
            ->assertSessionHasNoErrors();

        $sale = Sale::sole();
        $this->assertSame('partial', $sale->payment_status);
        $this->assertEquals(30000, (float) $sale->balance_amount);
        $this->assertNotEmpty($sale->invoice_no);
    }

    public function test_sales_page_renders_with_credit_sales(): void
    {
        $this->sell([], $this->customer->id);

        $this->actingAs($this->user)
            ->get(route('sales.index'))
            ->assertOk()
            ->assertSee('On Credit')
            ->assertSee('Outstanding Credit')
            ->assertSee('owes UGX 30,000');
    }
}
