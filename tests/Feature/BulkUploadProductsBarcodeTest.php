<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class BulkUploadProductsBarcodeTest extends TestCase
{
    use RefreshDatabase;

    private const WITH_BARCODE_HEADERS = [
        'product_name', 'category_name', 'sku', 'barcode', 'branch_name',
        'supplier_name', 'quantity', 'unit_type', 'cost_price', 'selling_price',
        'expiry_date', 'stock_date', 'description', 'image_url',
    ];

    private const WITHOUT_BARCODE_HEADERS = [
        'product_name', 'category_name', 'sku', 'branch_name',
        'supplier_name', 'quantity', 'unit_type', 'cost_price', 'selling_price',
        'expiry_date', 'stock_date', 'description', 'image_url',
    ];

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

    private function makeBranch(string $name = 'Main Branch'): Branch
    {
        return Branch::create(['name' => $name]);
    }

    private function makeCategory(string $name = 'Test Category'): Category
    {
        return Category::create(['category_name' => $name]);
    }

    private function csvContent(array $headers, array $rows): string
    {
        $lines = [implode(',', $headers)];

        foreach ($rows as $row) {
            $lines[] = implode(',', $row);
        }

        return implode("\n", $lines);
    }

    private function postProductsCsv(array $headers, array $rows): TestResponse
    {
        $file = UploadedFile::fake()->createWithContent(
            'import.csv',
            $this->csvContent($headers, $rows)
        );

        return $this->actingAs($this->makeUser())
            ->post(route('bulk-upload.products'), ['csv_file' => $file]);
    }

    public function test_create_product_with_barcode_sets_it(): void
    {
        $this->makeBranch('Main Branch');
        $this->makeCategory('Test Category');

        $row = ['New Product', 'Test Category', 'SKU-A', 'BC-1', 'Main Branch', '', '', '', '', '', '', '', '', ''];

        $this->postProductsCsv(self::WITH_BARCODE_HEADERS, [$row])
            ->assertRedirect();

        $this->assertDatabaseHas('products', ['name' => 'New Product', 'barcode' => 'BC-1']);
    }

    public function test_duplicate_barcode_on_different_product_is_skipped(): void
    {
        $this->makeBranch('Main Branch');
        $this->makeCategory('Test Category');

        Product::create([
            'name' => 'Owner Product',
            'sku' => 'SKU-OWNER',
            'barcode' => 'DUP-1',
            'branch_id' => null,
            'category_id' => null,
            'is_active' => true,
        ]);

        $row = ['Other Product', 'Test Category', 'SKU-OTHER', 'DUP-1', 'Main Branch', '', '', '', '', '', '', '', '', ''];

        $response = $this->postProductsCsv(self::WITH_BARCODE_HEADERS, [$row]);

        $response->assertRedirect();
        $this->assertDatabaseMissing('products', ['name' => 'Other Product']);
        $response->assertSessionHas('importErrors', function (array $errors) {
            return in_array('Row 2: This barcode is already assigned to a product.', $errors, true)
                && ! str_contains(implode(' ', $errors), 'DUP-1');
        });
    }

    public function test_sku_matched_product_can_be_assigned_a_new_barcode(): void
    {
        $this->makeBranch('Main Branch');
        $this->makeCategory('Test Category');

        $product = Product::create([
            'name' => 'Existing Product',
            'sku' => 'SKU-NEW-BARCODE',
            'barcode' => null,
            'branch_id' => null,
            'category_id' => null,
            'is_active' => true,
        ]);

        $row = ['Updated Product', 'Test Category', 'SKU-NEW-BARCODE', 'NEW-1', 'Main Branch', '', '', '', '', '', '', '', '', ''];

        $this->postProductsCsv(self::WITH_BARCODE_HEADERS, [$row])
            ->assertRedirect();

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'name' => 'Updated Product',
            'barcode' => 'NEW-1',
        ]);
    }

    public function test_sku_matched_update_cannot_take_another_products_barcode(): void
    {
        $this->makeBranch('Main Branch');
        $this->makeCategory('Test Category');

        $owner = Product::create([
            'name' => 'Barcode Owner',
            'sku' => 'SKU-OWNER',
            'barcode' => 'OWNED-1',
            'branch_id' => null,
            'category_id' => null,
            'is_active' => true,
        ]);
        $target = Product::create([
            'name' => 'Target Product',
            'sku' => 'SKU-TARGET',
            'barcode' => 'TARGET-1',
            'branch_id' => null,
            'category_id' => null,
            'is_active' => true,
        ]);

        $row = ['Target Updated', 'Test Category', 'SKU-TARGET', 'OWNED-1', 'Main Branch', '', '', '', '', '', '', '', '', ''];
        $response = $this->postProductsCsv(self::WITH_BARCODE_HEADERS, [$row]);

        $response->assertRedirect();
        $this->assertDatabaseHas('products', ['id' => $owner->id, 'barcode' => 'OWNED-1']);
        $this->assertDatabaseHas('products', ['id' => $target->id, 'barcode' => 'TARGET-1', 'name' => 'Target Product']);
        $response->assertSessionHas('importErrors', function (array $errors) {
            return in_array('Row 2: This barcode is already assigned to a product.', $errors, true)
                && ! str_contains(implode(' ', $errors), 'OWNED-1');
        });
    }

    public function test_self_assignment_of_existing_barcode_succeeds(): void
    {
        $this->makeBranch('Main Branch');
        $this->makeCategory('Test Category');

        $product = Product::create([
            'name' => 'Existing Product',
            'sku' => 'SKU-SELF',
            'barcode' => 'SELF-1',
            'branch_id' => null,
            'category_id' => null,
            'is_active' => true,
        ]);

        $row = ['Existing Product Renamed', 'Test Category', 'SKU-SELF', 'SELF-1', 'Main Branch', '', '', '', '', '', '', '', '', ''];

        $this->postProductsCsv(self::WITH_BARCODE_HEADERS, [$row])
            ->assertRedirect();

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'name' => 'Existing Product Renamed',
            'barcode' => 'SELF-1',
        ]);
    }

    public function test_csv_without_barcode_column_does_not_wipe_barcode_on_update(): void
    {
        $this->makeBranch('Main Branch');
        $this->makeCategory('Test Category');

        $product = Product::create([
            'name' => 'Keep Barcode',
            'sku' => 'SKU-KEEP',
            'barcode' => 'KEEP-1',
            'branch_id' => null,
            'category_id' => null,
            'is_active' => true,
        ]);

        $row = ['Keep Barcode Renamed', 'Test Category', 'SKU-KEEP', 'Main Branch', '', '', '', '', '', '', '', '', ''];

        $this->postProductsCsv(self::WITHOUT_BARCODE_HEADERS, [$row])
            ->assertRedirect();

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'name' => 'Keep Barcode Renamed',
            'barcode' => 'KEEP-1',
        ]);
    }

    public function test_blank_barcode_cell_does_not_wipe_barcode_on_sku_matched_update(): void
    {
        $this->makeBranch('Main Branch');
        $this->makeCategory('Test Category');

        $product = Product::create([
            'name' => 'Keep Barcode',
            'sku' => 'SKU-BLANK',
            'barcode' => 'KEEP-BLANK-1',
            'branch_id' => null,
            'category_id' => null,
            'is_active' => true,
        ]);

        $row = ['Keep Barcode Renamed', 'Test Category', 'SKU-BLANK', '', 'Main Branch', '', '', '', '', '', '', '', '', ''];

        $this->postProductsCsv(self::WITH_BARCODE_HEADERS, [$row])
            ->assertRedirect();

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'name' => 'Keep Barcode Renamed',
            'barcode' => 'KEEP-BLANK-1',
        ]);
    }

    public function test_barcode_longer_than_100_characters_is_skipped_with_error(): void
    {
        $this->makeBranch('Main Branch');
        $this->makeCategory('Test Category');

        $barcode = str_repeat('B', 101);
        $row = ['Long Barcode Product', 'Test Category', 'SKU-LONG', $barcode, 'Main Branch', '', '', '', '', '', '', '', '', ''];

        $response = $this->postProductsCsv(self::WITH_BARCODE_HEADERS, [$row]);

        $response->assertRedirect();
        $this->assertDatabaseMissing('products', ['sku' => 'SKU-LONG']);
        $response->assertSessionHas('importErrors', function (array $errors) {
            return collect($errors)->contains(fn ($error) => str_contains($error, '100 characters or less'));
        });
    }

    public function test_product_import_does_not_expose_unexpected_exception_details(): void
    {
        $this->makeBranch('Main Branch');
        $this->makeCategory('Test Category');
        Product::creating(function () {
            throw new \RuntimeException('SECRET_DATABASE_DETAIL');
        });

        // A branch is required to reach Product::create() and exercise the
        // per-row exception handler rather than the earlier validation skips.
        $row = ['Broken Product', 'Test Category', 'SKU-ERROR', '', 'Main Branch', '', '', '', '', '', '', '', '', ''];
        $response = $this->postProductsCsv(self::WITH_BARCODE_HEADERS, [$row]);

        $response->assertRedirect();
        $response->assertSessionHas('importErrors', function (array $errors) {
            return in_array('Row 2: Unable to import this product due to an unexpected error.', $errors, true)
                && ! str_contains(implode(' ', $errors), 'SECRET_DATABASE_DETAIL');
        });
    }

    public function test_product_import_route_is_limited_to_twenty_requests_per_minute(): void
    {
        $this->assertContains('throttle:20,1', Route::getRoutes()->getByName('bulk-upload.products')->gatherMiddleware());

        $user = $this->makeUser();
        $this->actingAs($user);
        RateLimiter::clear('bulk-upload-throttle-test');

        for ($request = 0; $request < 20; $request++) {
            $file = UploadedFile::fake()->createWithContent('import.csv', "product_name,category_name\n");
            $this->post(route('bulk-upload.products'), ['csv_file' => $file]);
        }

        $file = UploadedFile::fake()->createWithContent('import.csv', "product_name,category_name\n");
        $this->post(route('bulk-upload.products'), ['csv_file' => $file])->assertTooManyRequests();
    }
}
