<?php

namespace Tests\Feature;

use App\Mail\DeliveryNoteMail;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Tests\TestCase;

class SaleDeliveryNoteTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(string $role = 'admin'): User
    {
        return User::create([
            'name' => 'Test User',
            'email' => Str::random(12).'@example.com',
            'password' => 'password',
            'role' => $role,
            'branch_id' => null,
        ]);
    }

    private function makeSale(?string $customerEmail = 'buyer@example.com'): Sale
    {
        $branch = Branch::create(['name' => 'Main Branch', 'location' => 'Kampala']);

        $customer = Customer::create([
            'name' => 'Jane Buyer',
            'phone' => '0700000000',
            'email' => $customerEmail,
            'address' => 'Plot 1, Kampala Road',
        ]);

        $product = Product::create([
            'name' => 'Steel Bucket',
            'sku' => 'BKT-001',
            'branch_id' => null,
            'category_id' => null,
            'is_active' => true,
        ]);

        $sale = Sale::create([
            'transaction_type' => 'sale',
            'customer_id' => $customer->id,
            'invoice_no' => 'INV-20261027-007',
            'total_amount' => 30000,
            'discount' => 0,
            'vat' => 0,
            'payment_method' => 'cash',
            'sale_date' => '2026-10-27',
            'payment_status' => 'paid',
            'partial_amount' => 0,
            'balance_amount' => 0,
            'branch_id' => $branch->id,
        ]);

        $sale->sale_items()->create([
            'product_id' => $product->id,
            'quantity' => 3,
            'price' => 10000,
            'vat_amount' => 0,
            'total' => 30000,
        ]);

        return $sale;
    }

    public function test_delivery_note_number_follows_the_invoice_sequence(): void
    {
        Mail::fake();

        $this->assertSame('DN-20261027-007', $this->makeSale()->delivery_note_no);
    }

    public function test_download_returns_a_branded_pdf_attachment(): void
    {
        Mail::fake();
        $sale = $this->makeSale();

        $response = $this->actingAs($this->makeUser())
            ->get(route('sales.delivery-note-download', $sale));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/pdf');
        $response->assertHeader('Content-Disposition', 'attachment; filename="DN-20261027-007.pdf"');
        $this->assertStringStartsWith('%PDF', $response->getContent());
    }

    public function test_preview_page_prefills_the_customer_email(): void
    {
        Mail::fake();
        $sale = $this->makeSale();

        $this->actingAs($this->makeUser())
            ->get(route('sales.delivery-note-preview', $sale))
            ->assertOk()
            ->assertSee('DN-20261027-007')
            ->assertSee('buyer@example.com');
    }

    public function test_email_sends_the_delivery_note_with_the_pdf_attached(): void
    {
        Mail::fake();
        $sale = $this->makeSale();

        $this->actingAs($this->makeUser())
            ->post(route('sales.delivery-note-email', $sale), [
                'email' => 'stores@example.com',
                'message' => 'Arriving this afternoon.',
            ])
            ->assertRedirect(route('sales.delivery-note-preview', $sale))
            ->assertSessionHas('status');

        Mail::assertSent(DeliveryNoteMail::class, function (DeliveryNoteMail $mail) use ($sale) {
            return $mail->hasTo('stores@example.com')
                && $mail->sale->is($sale)
                && $mail->fileName === 'DN-20261027-007.pdf'
                && str_starts_with($mail->pdfContent, '%PDF')
                && $mail->note === 'Arriving this afternoon.';
        });
    }

    public function test_email_requires_a_valid_address(): void
    {
        Mail::fake();
        $sale = $this->makeSale(null);

        $this->actingAs($this->makeUser())
            ->post(route('sales.delivery-note-email', $sale), ['email' => 'not-an-email'])
            ->assertSessionHasErrors('email');

        Mail::assertNotSent(DeliveryNoteMail::class);
    }

    public function test_user_without_manage_sales_permission_is_blocked(): void
    {
        Mail::fake();
        $sale = $this->makeSale();
        $user = $this->makeUser('cashier');

        $this->actingAs($user)->get(route('sales.delivery-note-download', $sale))->assertForbidden();
        $this->actingAs($user)
            ->post(route('sales.delivery-note-email', $sale), ['email' => 'x@example.com'])
            ->assertForbidden();

        Mail::assertNotSent(DeliveryNoteMail::class);
    }
}
