<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Customer;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Sale;
use App\Models\Stock;
use App\Models\User;
use App\Support\DocumentSignature;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DocumentSignatureTest extends TestCase
{
    use RefreshDatabase;

    private User $seller;
    private Branch $branch;
    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        Storage::fake('local');

        $this->seller = User::create([
            'name' => 'Grace Seller', 'email' => Str::random(10).'@example.com',
            'password' => 'password', 'role' => 'admin', 'branch_id' => null,
        ]);
        $this->branch = Branch::create(['name' => 'Main Branch', 'location' => 'Kampala']);
        $this->product = Product::create(['name' => 'Steel Bucket', 'sku' => 'BKT-1', 'is_active' => true]);
        Stock::create([
            'product_id' => $this->product->id, 'branch_id' => $this->branch->id,
            'quantity' => 10, 'total_stock' => 10, 'selling_price' => 10000, 'cost_price' => 6000,
        ]);
    }

    private function pngDataUrl(): string
    {
        return 'data:image/png;base64,'.base64_encode(UploadedFile::fake()->image('s.png', 200, 60)->getContent());
    }

    private function sell(): Sale
    {
        $this->actingAs($this->seller)->post(route('sales.pos-store'), [
            'branch_id' => $this->branch->id,
            'items' => [['product_id' => $this->product->id, 'quantity' => 1]],
            'payments' => [['method' => 'Cash', 'amount' => 10000]],
        ])->assertSessionHasNoErrors();

        return Sale::sole();
    }

    public function test_staff_save_a_drawn_signature_from_the_app_and_it_stays_private(): void
    {
        Sanctum::actingAs($this->seller);

        $this->postJson('/api/mobile/profile/signature', ['signature_data' => $this->pngDataUrl()])
            ->assertOk()
            ->assertJsonPath('message', 'Signature saved. It will appear on your invoices and receipts.');

        $path = $this->seller->fresh()->signature;
        $this->assertStringStartsWith('signatures/', $path);
        Storage::disk('local')->assertExists($path);
        $this->assertStringStartsWith('data:image/png;base64,', $this->getJson('/api/mobile/profile')->json('data.signature'));

        $this->deleteJson('/api/mobile/profile/signature')->assertOk()->assertJsonPath('data.signature', null);
        Storage::disk('local')->assertMissing($path);
    }

    public function test_bad_signature_data_is_rejected(): void
    {
        Sanctum::actingAs($this->seller);

        $this->postJson('/api/mobile/profile/signature', ['signature_data' => 'data:text/plain;base64,aGVsbG8='])
            ->assertUnprocessable()->assertJsonValidationErrors('signature');
        $this->postJson('/api/mobile/profile/signature', [])->assertUnprocessable();
    }

    public function test_sale_detail_carries_the_sellers_signature(): void
    {
        DocumentSignature::store($this->seller, $this->pngDataUrl());
        $sale = $this->sell();
        Sanctum::actingAs($this->seller);

        $this->getJson('/api/mobile/staff/sales/'.$sale->id)
            ->assertOk()
            ->assertJsonPath('data.sold_by', 'Grace Seller')
            ->assertJson(fn ($json) => $json->where('data.sold_by_signature', fn ($v) => str_starts_with((string) $v, 'data:image/png'))->etc());
    }

    public function test_invoices_and_receipts_show_the_sellers_signature(): void
    {
        DocumentSignature::store($this->seller, $this->pngDataUrl());
        $sale = $this->sell();
        $payment = Payment::sole();

        $this->actingAs($this->seller)->get(route('sales.invoice-print', ['sale' => $sale, 'paper' => 'a4']))
            ->assertOk()->assertSee('Grace Seller')->assertSee('Sold by')->assertSee('alt="Signature"', false);

        $this->actingAs($this->seller)->get(route('payments.receipt-print', ['payment' => $payment, 'paper' => 'a4']))
            ->assertOk()->assertSee('Grace Seller')->assertSee('alt="Signature"', false);

        $this->actingAs($this->seller)->get(route('sales.invoice-pdf', $sale))
            ->assertOk()->assertHeader('content-type', 'application/pdf');
        $this->actingAs($this->seller)->get(route('payments.receipt-pdf', $payment))
            ->assertOk()->assertHeader('content-type', 'application/pdf');
    }

    public function test_documents_still_render_without_a_signature(): void
    {
        $sale = $this->sell();

        $this->actingAs($this->seller)->get(route('sales.invoice-print', ['sale' => $sale, 'paper' => '80mm']))
            ->assertOk()->assertSee('Grace Seller')->assertDontSee('alt="Signature"', false);
    }

    public function test_website_signature_page_saves_and_removes(): void
    {
        $this->actingAs($this->seller)->get(route('signature.edit'))->assertOk()->assertSee('My signature');

        $this->actingAs($this->seller)->put(route('signature.update'), ['signature_data' => $this->pngDataUrl()])
            ->assertRedirect(route('signature.edit'))->assertSessionHas('status');
        $this->assertNotNull($this->seller->fresh()->signature);

        $this->actingAs($this->seller)->put(route('signature.update'), [
            'signature' => UploadedFile::fake()->image('sig.jpg', 300, 100),
        ])->assertSessionHasNoErrors();

        $this->actingAs($this->seller)->delete(route('signature.destroy'))->assertRedirect(route('signature.edit'));
        $this->assertNull($this->seller->fresh()->signature);
    }
}
