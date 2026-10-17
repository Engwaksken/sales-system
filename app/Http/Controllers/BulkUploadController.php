<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Stock;
use App\Models\Supplier;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class BulkUploadController extends Controller
{
    public function index()
    {
        return view('bulk-upload.index', [
            'branches' => Branch::orderBy('name')->get(),
            'suppliers' => Supplier::orderBy('name')->get(),
            'categories' => Category::orderBy('category_name')->get(),
        ]);
    }

    /**
     * Download a CSV template that can create/update a product and optionally
     * create/update stock in one upload.
     */
    public function productsTemplate(): StreamedResponse
    {
        $filename = 'nsales-products-bulk-template-'.now()->format('Ymd').'.csv';

        return response()->streamDownload(function (): void {
            $out = fopen('php://output', 'w');

            fputcsv($out, [
                'product_name',
                'category_name',
                'sku',
                'branch_name',
                'supplier_name',
                'quantity',
                'unit_type',
                'cost_price',
                'selling_price',
                'expiry_date',
                'stock_date',
                'description',
                'image_url',
            ]);

            // Example row so users can see the expected date and money formats.
            fputcsv($out, [
                'Example Product',
                'Example Category',
                'SKU-001',
                'Main Branch',
                'Example Supplier',
                '100',
                'pcs',
                '5000',
                '7500',
                now()->addYear()->format('Y-m-d'),
                now()->format('Y-m-d'),
                'Replace this example row with your product data',
                '',
            ]);

            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * Customer CSV template.
     */
    public function customersTemplate(): StreamedResponse
    {
        $filename = 'nsales-customers-bulk-template-'.now()->format('Ymd').'.csv';

        return response()->streamDownload(function (): void {
            $out = fopen('php://output', 'w');

            fputcsv($out, ['NSales Customers Bulk Upload Template']);
            fputcsv($out, ['Required: name. Optional: branch_name, phone, email, address.']);
            fputcsv($out, ['Use branch names exactly as they appear in NSales.']);
            fputcsv($out, ['name', 'branch_name', 'phone', 'email', 'address']);

            fputcsv($out, [
                'Example Customer',
                'Main Branch',
                '0700000000',
                'customer@example.com',
                'Kampala',
            ]);

            fputcsv($out, []);
            fputcsv($out, ['Available Branches:']);

            foreach (Branch::orderBy('name')->pluck('name') as $branchName) {
                fputcsv($out, [$branchName]);
            }

            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * Product CSV columns:
     * product_name, category_name, sku, branch_name, supplier_name, quantity,
     * unit_type, cost_price, selling_price, expiry_date, stock_date,
     * description, image_url.
     *
     * stock_date is the date the stock/product was received/uploaded and is
     * used by the monthly supplier statistics on Dashboard.
     */
    public function importProducts(Request $request)
    {
        $request->validate([
            'csv_file' => ['required', 'file', 'mimes:csv,txt'],
        ]);

        $handle = fopen($request->file('csv_file')->getRealPath(), 'r');
        $headers = fgetcsv($handle);

        if (! $headers) {
            fclose($handle);

            return back()->withErrors([
                'csv_file' => 'The CSV file is empty.',
            ]);
        }

        $headers = array_map(
            fn ($header) => strtolower(trim((string) $header)),
            $headers
        );

        foreach (['product_name', 'category_name'] as $required) {
            if (! in_array($required, $headers, true)) {
                fclose($handle);

                return back()->withErrors([
                    'csv_file' => "Invalid CSV format. Missing required column: {$required}. Download the NSales template and try again.",
                ]);
            }
        }

        $branchesMap = Branch::pluck('id', 'name')
            ->mapWithKeys(fn ($id, $name) => [strtolower(trim($name)) => $id]);

        $categoriesMap = Category::pluck('id', 'category_name')
            ->mapWithKeys(fn ($id, $name) => [strtolower(trim($name)) => $id]);

        $suppliersMap = Supplier::pluck('id', 'name')
            ->mapWithKeys(fn ($id, $name) => [strtolower(trim($name)) => $id]);

        $imported = 0;
        $updated = 0;
        $stockCreated = 0;
        $stockUpdated = 0;
        $skipped = 0;
        $errors = [];
        $rowNumber = 1;

        while (($data = fgetcsv($handle)) !== false) {
            $rowNumber++;

            if (empty(array_filter($data, fn ($value) => trim((string) $value) !== ''))) {
                continue;
            }

            if (count($data) < count($headers)) {
                $data = array_pad($data, count($headers), '');
            } elseif (count($data) > count($headers)) {
                $data = array_slice($data, 0, count($headers));
            }

            $row = array_combine($headers, $data);

            $productName = trim((string) ($row['product_name'] ?? ''));
            $categoryName = trim((string) ($row['category_name'] ?? ''));
            $sku = trim((string) ($row['sku'] ?? ''));
            $branchName = trim((string) ($row['branch_name'] ?? ''));
            $supplierName = trim((string) ($row['supplier_name'] ?? ''));
            $quantity = trim((string) ($row['quantity'] ?? ''));
            $unitType = trim((string) ($row['unit_type'] ?? ''));
            $costPrice = trim((string) ($row['cost_price'] ?? ''));
            $sellingPrice = trim((string) ($row['selling_price'] ?? ''));
            $expiryDate = trim((string) ($row['expiry_date'] ?? ''));
            $stockDate = trim((string) ($row['stock_date'] ?? ''));
            $description = trim((string) ($row['description'] ?? ''));
            $imageUrl = trim((string) ($row['image_url'] ?? ''));

            // Skip the example row unchanged.
            if (
                strtolower($productName) === 'example product' &&
                strtolower($sku) === 'sku-001'
            ) {
                continue;
            }

            if ($productName === '') {
                $errors[] = "Row {$rowNumber}: Product name is required.";
                $skipped++;
                continue;
            }

            if ($categoryName === '') {
                $errors[] = "Row {$rowNumber}: Category name is required.";
                $skipped++;
                continue;
            }

            $categoryId = $categoriesMap->get(strtolower($categoryName));

            if (! $categoryId) {
                $errors[] = "Row {$rowNumber}: Category '{$categoryName}' was not found.";
                $skipped++;
                continue;
            }

            if ($branchName === '' || strtolower($branchName) === 'all branches') {
                $branchIds = $branchesMap->values()->all();
            } else {
                // One branch, or several separated by ";" or "," (e.g. "Kampala; Gulu").
                $names = array_values(array_filter(array_map('trim', preg_split('/[;,]/', $branchName))));
                $unknown = array_filter($names, fn ($name) => ! $branchesMap->has(strtolower($name)));

                if ($unknown !== []) {
                    $errors[] = "Row {$rowNumber}: Branch '".implode("', '", $unknown)."' was not found.";
                    $skipped++;
                    continue;
                }

                $branchIds = array_values(array_unique(array_map(fn ($name) => $branchesMap->get(strtolower($name)), $names)));
            }

            if (empty($branchIds)) {
                $errors[] = "Row {$rowNumber}: No branch is available for this product.";
                $skipped++;
                continue;
            }

            $supplierId = null;

            if ($supplierName !== '') {
                $supplierId = $suppliersMap->get(strtolower($supplierName));

                if (! $supplierId) {
                    $errors[] = "Row {$rowNumber}: Supplier '{$supplierName}' was not found.";
                    $skipped++;
                    continue;
                }
            }

            $parsedStockDate = $this->parseDate(
                $stockDate,
                "Row {$rowNumber}: Invalid stock_date '{$stockDate}'. Use YYYY-MM-DD.",
                $errors
            );

            if ($stockDate !== '' && ! $parsedStockDate) {
                $skipped++;
                continue;
            }

            $parsedExpiryDate = $this->parseDate(
                $expiryDate,
                "Row {$rowNumber}: Invalid expiry_date '{$expiryDate}'. Use YYYY-MM-DD.",
                $errors
            );

            if ($expiryDate !== '' && ! $parsedExpiryDate) {
                $skipped++;
                continue;
            }

            $hasStockData =
                $quantity !== '' ||
                $supplierName !== '' ||
                $costPrice !== '' ||
                $sellingPrice !== '' ||
                $stockDate !== '' ||
                $expiryDate !== '';

            if ($quantity !== '' && (! is_numeric($quantity) || (int) $quantity < 0)) {
                $errors[] = "Row {$rowNumber}: Quantity must be zero or a positive whole number.";
                $skipped++;
                continue;
            }

            if ($costPrice !== '' && (! is_numeric($costPrice) || (float) $costPrice < 0)) {
                $errors[] = "Row {$rowNumber}: Cost price must be zero or a positive number.";
                $skipped++;
                continue;
            }

            if ($sellingPrice !== '' && (! is_numeric($sellingPrice) || (float) $sellingPrice < 0)) {
                $errors[] = "Row {$rowNumber}: Selling price must be zero or a positive number.";
                $skipped++;
                continue;
            }

            try {
                DB::transaction(function () use (
                    $sku,
                    $productName,
                    $branchIds,
                    $categoryId,
                    $description,
                    $imageUrl,
                    $supplierId,
                    $quantity,
                    $unitType,
                    $costPrice,
                    $sellingPrice,
                    $parsedExpiryDate,
                    $parsedStockDate,
                    $hasStockData,
                    &$imported,
                    &$updated,
                    &$stockCreated,
                    &$stockUpdated
                ): void {
                    $existing = $sku !== ''
                        ? Product::where('sku', $sku)->first()
                        : null;

                    if ($existing) {
                        $existing->update([
                            'name' => $productName,
                            'branch_id' => $branchIds[0],
                            'category_id' => $categoryId,
                            'description' => $description ?: null,
                            'image_url' => $imageUrl ?: null,
                        ]);

                        $product = $existing;
                        $updated++;
                    } else {
                        $product = Product::create([
                            'name' => $productName,
                            'sku' => $sku ?: null,
                            'branch_id' => $branchIds[0],
                            'category_id' => $categoryId,
                            'description' => $description ?: null,
                            'image_url' => $imageUrl ?: null,
                            'is_active' => true,
                        ]);

                        $imported++;
                    }

                    $product->product_branches()->delete();

                    foreach ($branchIds as $branchId) {
                        $product->product_branches()->create([
                            'branch_id' => $branchId,
                        ]);

                        if (! $hasStockData) {
                            continue;
                        }

                        $stock = Stock::query()
                            ->where('product_id', $product->id)
                            ->where('branch_id', $branchId)
                            ->when(
                                $supplierId,
                                fn ($q) => $q->where('supplier_id', $supplierId),
                                fn ($q) => $q->whereNull('supplier_id')
                            )
                            ->first();

                        $qty = $quantity === '' ? 0 : (int) $quantity;

                        $payload = [
                            'supplier_id' => $supplierId,
                            'product_id' => $product->id,
                            'branch_id' => $branchId,
                            'total_stock' => $qty,
                            'quantity' => $qty,
                            'unit_type' => $unitType ?: null,
                            'cost_price' => $costPrice === '' ? 0 : (float) $costPrice,
                            'selling_price' => $sellingPrice === '' ? 0 : (float) $sellingPrice,
                            'expiry_date' => $parsedExpiryDate,
                            'stock_date' => $parsedStockDate ?: now()->toDateString(),
                        ];

                        if ($stock) {
                            $stock->update($payload);
                            $stockUpdated++;
                        } else {
                            Stock::create($payload);
                            $stockCreated++;
                        }
                    }
                });
            } catch (Throwable $e) {
                report($e);
                $errors[] = "Row {$rowNumber}: ".$e->getMessage();
                $skipped++;
            }
        }

        fclose($handle);

        return back()
            ->with(
                'status',
                "Products import complete: {$imported} products added, {$updated} updated, {$stockCreated} stock rows added, {$stockUpdated} stock rows updated, {$skipped} skipped."
            )
            ->with('importErrors', $errors);
    }

    /**
     * Customer template format:
     * First four rows are instructions/header, followed by customer data.
     */
    public function importCustomers(Request $request)
    {
        $request->validate([
            'csv_file' => ['required', 'file', 'mimes:csv,txt'],
            'default_branch_id' => ['nullable', 'exists:branches,id'],
        ]);

        $defaultBranchId = $request->input('default_branch_id');

        $branchesMap = Branch::pluck('id', 'name')
            ->mapWithKeys(fn ($id, $name) => [strtolower(trim($name)) => $id]);

        $handle = fopen($request->file('csv_file')->getRealPath(), 'r');

        // Template rows:
        // 1 title, 2 instructions, 3 branch instruction, 4 actual column headings.
        for ($i = 0; $i < 4; $i++) {
            fgetcsv($handle);
        }

        $imported = 0;
        $skipped = 0;
        $errors = [];
        $rowNumber = 4;

        while (($data = fgetcsv($handle)) !== false) {
            $rowNumber++;

            if (empty(array_filter($data, fn ($value) => trim((string) $value) !== ''))) {
                continue;
            }

            if (isset($data[0]) && strtolower(trim((string) $data[0])) === 'available branches:') {
                break;
            }

            if (strtolower(trim((string) ($data[0] ?? ''))) === 'example customer') {
                continue;
            }

            $name = trim((string) ($data[0] ?? ''));
            $branchName = trim((string) ($data[1] ?? ''));
            $phone = trim((string) ($data[2] ?? ''));
            $email = trim((string) ($data[3] ?? ''));
            $address = trim((string) ($data[4] ?? ''));

            if ($name === '') {
                $errors[] = "Row {$rowNumber}: Customer name is required.";
                $skipped++;
                continue;
            }

            $branchId = null;

            if ($branchName !== '') {
                $branchId = $branchesMap->get(strtolower($branchName));

                if (! $branchId) {
                    if ($defaultBranchId) {
                        $branchId = $defaultBranchId;
                    } else {
                        $errors[] = "Row {$rowNumber}: Branch '{$branchName}' was not found and no default branch was selected.";
                        $skipped++;
                        continue;
                    }
                }
            } elseif ($defaultBranchId) {
                $branchId = $defaultBranchId;
            }

            if ($email !== '' && ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors[] = "Row {$rowNumber}: Invalid email address.";
                $skipped++;
                continue;
            }

            if ($phone !== '' && Customer::where('phone', $phone)->exists()) {
                $errors[] = "Row {$rowNumber}: A customer with phone '{$phone}' already exists.";
                $skipped++;
                continue;
            }

            Customer::create([
                'branch_id' => $branchId,
                'name' => $name,
                'phone' => $phone ?: null,
                'email' => $email ?: null,
                'address' => $address ?: null,
            ]);

            $imported++;
        }

        fclose($handle);

        return back()
            ->with(
                'status',
                "Customers import complete: {$imported} imported, {$skipped} skipped."
            )
            ->with('importErrors', $errors);
    }

    private function parseDate(
        string $value,
        string $error,
        array &$errors
    ): ?string {
        if ($value === '') {
            return null;
        }

        try {
            return Carbon::createFromFormat('Y-m-d', $value)->toDateString();
        } catch (Throwable) {
            $errors[] = $error;

            return null;
        }
    }
}
