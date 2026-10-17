<?php

namespace App\Support\Imports;

use App\Models\Branch;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductBranch;
use App\Models\Stock;
use App\Models\Supplier;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use RuntimeException;

/**
 * Reads a CSV for one ImportModules page, validates every row with that page's
 * rules and saves all rows in a single transaction (all or nothing).
 */
class Importer
{
    public const MAX_ROWS = 2000;

    private array $lookups = [];

    public function __construct(private readonly array $module)
    {
    }

    /** @return array<int, array<string, string>> rows keyed by column name, with their file line number */
    public function read(UploadedFile $file): array
    {
        $handle = fopen($file->getRealPath(), 'r');
        if (! $handle) {
            throw new RuntimeException('The file could not be read.');
        }

        $first = fgets($handle);
        $delimiter = substr_count((string) $first, ';') > substr_count((string) $first, ',') ? ';' : ',';
        rewind($handle);

        $headers = null;
        $rows = [];
        $line = 0;
        while (($data = fgetcsv($handle, 0, $delimiter)) !== false) {
            $line++;
            if ($headers === null) {
                $headers = array_map(fn ($h) => $this->headerKey((string) $h), $data);
                $missing = array_diff($this->requiredHeaders(), $headers);
                if ($missing !== []) {
                    fclose($handle);
                    throw new RuntimeException('Missing column(s): '.implode(', ', $missing).'. Download the template and keep its header row.');
                }
                continue;
            }
            if (count(array_filter($data, fn ($v) => trim((string) $v) !== '')) === 0) {
                continue;
            }
            $row = [];
            foreach ($headers as $i => $key) {
                if ($key !== '' && array_key_exists($key, $this->module['columns'])) {
                    $row[$key] = trim((string) ($data[$i] ?? ''));
                }
            }
            if ($this->isExampleRow($row)) {
                continue;
            }
            $rows[] = ['line' => $line, 'values' => $row];
            if (count($rows) > self::MAX_ROWS) {
                fclose($handle);
                throw new RuntimeException('The file has more than '.self::MAX_ROWS.' rows. Split it into smaller files.');
            }
        }
        fclose($handle);

        if ($rows === []) {
            throw new RuntimeException('The file has no data rows below the header.');
        }

        return $rows;
    }

    /**
     * Validate every row without saving.
     *
     * @return array<int, array{line: int, values: array, data: array, action: string, errors: array<int, string>}>
     */
    public function check(array $rows): array
    {
        $checked = [];
        $seen = [];
        foreach ($rows as $row) {
            [$data, $errors] = $this->prepare($row['values']);
            $action = 'create';

            if ($errors === []) {
                [$action, $extra] = $this->plan($data);
                $errors = array_merge($errors, $extra);
            }

            // The same record twice in one file would silently overwrite itself.
            if ($errors === [] && ! empty($this->module['match'])) {
                $key = strtolower(implode('|', array_map(fn ($f) => (string) ($data[$f] ?? ''), $this->module['match'])));
                if (isset($seen[$key])) {
                    $errors[] = 'Duplicate of line '.$seen[$key].' in this file.';
                }
                $seen[$key] = $row['line'];
            }

            $checked[] = $row + ['data' => $data, 'action' => $errors ? 'error' : $action, 'errors' => $errors];
        }

        return $checked;
    }

    /** Save all rows. Re-validates first, so nothing is saved if the data changed since the preview. */
    public function commit(array $rows, ?int $userId): array
    {
        $checked = $this->check($rows);
        if (collect($checked)->contains('action', 'error')) {
            throw new RuntimeException('Some rows are no longer valid. Review the preview and upload the file again.');
        }

        $counts = ['created' => 0, 'updated' => 0];
        DB::transaction(function () use ($checked, &$counts): void {
            foreach ($checked as $row) {
                $result = match ($this->module['handler'] ?? null) {
                    'stock' => $this->saveStock($row['data']),
                    'product_branches' => $this->saveProductBranches($row['data']),
                    default => $this->saveRecord($row['data']),
                };
                $counts['created'] += $result['created'];
                $counts['updated'] += $result['updated'];
            }
        });

        return $counts;
    }

    public function templateRows(): array
    {
        $columns = $this->module['columns'];

        return [array_keys($columns), array_map(fn ($c) => (string) ($c['example'] ?? ''), $columns)];
    }

    // ------------------------------------------------------------------

    private function prepare(array $values): array
    {
        $data = [];
        $errors = [];
        $rules = [];

        foreach ($this->module['columns'] as $column => $def) {
            $raw = $values[$column] ?? '';
            if ($raw === '' && isset($def['default'])) {
                $raw = (string) $def['default'];
            }
            $field = $def['field'] ?? $column;

            if (isset($def['lookup'])) {
                if ($raw === '') {
                    if (str_contains($def['rules'], 'required')) {
                        $errors[] = $this->label($column).' is required.';
                    }
                    $data[$field] = $def['lookup'] === 'branches' ? [] : null;
                    continue;
                }
                [$resolved, $error] = $this->resolve($def['lookup'], $raw);
                if ($error) {
                    $errors[] = $error;
                }
                $data[$field] = $resolved;
                continue;
            }

            $data[$field] = $this->cast($raw, $def);
            $rules[$field] = $def['rules'];
        }

        $validator = Validator::make($data, $rules, [], collect($rules)->keys()->mapWithKeys(fn ($f) => [$f => $this->label($f)])->all());
        $errors = array_merge($errors, $validator->errors()->all());

        return [$data, $errors];
    }

    private function cast(string $raw, array $def): mixed
    {
        if ($raw === '') {
            return null;
        }
        $type = $def['type'] ?? null;
        if ($type === 'boolean') {
            $lower = strtolower($raw);
            return match (true) {
                in_array($lower, ['1', 'yes', 'y', 'true', 'active'], true) => 1,
                in_array($lower, ['0', 'no', 'n', 'false', 'inactive'], true) => 0,
                default => $raw,
            };
        }
        if ($type === 'date') {
            foreach (['Y-m-d', 'd/m/Y', 'd-m-Y', 'd.m.Y', 'm/d/Y'] as $format) {
                try {
                    $date = Carbon::createFromFormat('!'.$format, $raw);
                } catch (\Throwable) {
                    continue;
                }
                if ($date && $date->format($format) === $raw) {
                    return $date->toDateString();
                }
            }
            return $raw; // left for the "date" rule to reject
        }
        if (str_contains($def['rules'], 'numeric')) {
            $number = str_replace([',', ' '], '', $raw);
            return is_numeric($number) ? $number : $raw;
        }
        if (str_contains($def['rules'], 'in:')) {
            // Accept "Active" for "active" and "expense" for "Expense".
            preg_match('/in:([^|]+)/', $def['rules'], $m);
            foreach (explode(',', $m[1]) as $option) {
                if (strcasecmp($option, $raw) === 0) {
                    return $option;
                }
            }
        }

        return $raw;
    }

    private function resolve(string $type, string $raw): array
    {
        if ($type === 'branches') {
            $map = $this->map('branch');
            if (strcasecmp($raw, 'all branches') === 0 || strcasecmp($raw, 'all') === 0) {
                return [array_values(array_unique($map->values()->all())), null];
            }
            $names = array_values(array_filter(array_map('trim', preg_split('/[;,]/', $raw))));
            $unknown = array_filter($names, fn ($n) => ! $map->has(strtolower($n)));
            if ($unknown !== []) {
                return [[], "Branch '".implode("', '", $unknown)."' was not found."];
            }

            return [array_values(array_unique(array_map(fn ($n) => $map->get(strtolower($n)), $names))), null];
        }

        $id = $this->map($type)->get(strtolower($raw));
        if (! $id) {
            return [null, ucfirst($type)." '{$raw}' was not found."];
        }

        return [$id, null];
    }

    private function map(string $type): Collection
    {
        return $this->lookups[$type] ??= match ($type) {
            'branch' => Branch::query()->get(['id', 'name'])->mapWithKeys(fn ($b) => [strtolower(trim($b->name)) => $b->id]),
            'supplier' => Supplier::query()->get(['id', 'name'])->mapWithKeys(fn ($s) => [strtolower(trim($s->name)) => $s->id]),
            'category' => Category::query()->get(['id', 'category_name'])->mapWithKeys(fn ($c) => [strtolower(trim($c->category_name)) => $c->id]),
            // SKUs win over names when a value matches both.
            'product' => Product::query()->get(['id', 'name', 'sku'])->reduce(function (Collection $map, $p) {
                $map->put(strtolower(trim($p->name)), $map->get(strtolower(trim($p->name)), $p->id));
                return $p->sku ? $map->put(strtolower(trim($p->sku)), $p->id) : $map;
            }, collect()),
        };
    }

    /** Decide create vs update and catch rule breaks that need the database. */
    private function plan(array $data): array
    {
        $handler = $this->module['handler'] ?? null;

        if ($handler === 'stock') {
            $missingPrices = $data['cost_price'] === null || $data['selling_price'] === null;
            $new = 0;
            foreach ($data['branch_ids'] as $branchId) {
                if (! $this->stockRow($data, $branchId)) {
                    $new++;
                }
            }
            if ($new > 0 && $missingPrices) {
                return ['error', ['Cost price and selling price are required for branches that have no stock of this product yet.']];
            }

            return [$new === count($data['branch_ids']) ? 'create' : 'update', []];
        }

        if ($handler === 'product_branches') {
            $existing = ProductBranch::query()->where('product_id', $data['product_id'])->whereIn('branch_id', $data['branch_ids'])->count();

            return [$existing === count($data['branch_ids']) ? 'unchanged' : 'create', []];
        }

        return [$this->existing($data) ? 'update' : 'create', []];
    }

    private function existing(array $data)
    {
        if (empty($this->module['match'])) {
            return null;
        }
        $query = $this->module['model']::query();
        foreach ($this->module['match'] as $field) {
            $query->whereRaw('LOWER(TRIM('.$field.')) = ?', [strtolower(trim((string) $data[$field]))]);
        }

        return $query->first();
    }

    private function saveRecord(array $data): array
    {
        if ($record = $this->existing($data)) {
            // Matching ignores case and spaces, so keep the stored spelling of the matched columns.
            $record->update(array_diff_key($data, array_flip($this->module['match'])));

            return ['created' => 0, 'updated' => 1];
        }
        $this->module['model']::create($data);

        return ['created' => 1, 'updated' => 0];
    }

    private function stockRow(array $data, int $branchId): ?Stock
    {
        return Stock::query()
            ->where('product_id', $data['product_id'])
            ->where('branch_id', $branchId)
            ->when($data['supplier_id'], fn ($q) => $q->where('supplier_id', $data['supplier_id']), fn ($q) => $q->whereNull('supplier_id'))
            ->lockForUpdate()
            ->first();
    }

    private function saveStock(array $data): array
    {
        $counts = ['created' => 0, 'updated' => 0];
        $quantity = (float) $data['quantity'];
        $changes = array_filter([
            'cost_price' => $data['cost_price'],
            'selling_price' => $data['selling_price'],
            'unit_type' => $data['unit_type'],
            'expiry_date' => $data['expiry_date'],
            'stock_date' => $data['stock_date'],
        ], fn ($v) => $v !== null);

        foreach ($data['branch_ids'] as $branchId) {
            if ($stock = $this->stockRow($data, $branchId)) {
                $stock->fill($changes);
                $stock->quantity = (float) $stock->quantity + $quantity;
                $stock->total_stock = (float) $stock->total_stock + $quantity;
                $stock->save();
                $counts['updated']++;
            } else {
                Stock::create($changes + [
                    'product_id' => $data['product_id'],
                    'branch_id' => $branchId,
                    'supplier_id' => $data['supplier_id'],
                    'quantity' => $quantity,
                    'total_stock' => $quantity,
                    'stock_date' => $data['stock_date'] ?? now()->toDateString(),
                ]);
                $counts['created']++;
            }
        }

        return $counts;
    }

    private function saveProductBranches(array $data): array
    {
        $created = 0;
        foreach ($data['branch_ids'] as $branchId) {
            $link = ProductBranch::query()->firstOrCreate(['product_id' => $data['product_id'], 'branch_id' => $branchId]);
            $created += $link->wasRecentlyCreated ? 1 : 0;
        }

        return ['created' => $created, 'updated' => 0];
    }

    private function requiredHeaders(): array
    {
        return array_keys(array_filter($this->module['columns'], fn ($def) => str_contains($def['rules'], 'required') && ! isset($def['default'])));
    }

    private function isExampleRow(array $row): bool
    {
        $example = array_map(fn ($c) => (string) ($c['example'] ?? ''), $this->module['columns']);

        return $row !== [] && array_intersect_key($example, $row) == $row;
    }

    private function headerKey(string $header): string
    {
        $header = preg_replace('/^\xEF\xBB\xBF/', '', $header); // Excel's UTF-8 marker

        return trim(preg_replace('/[^a-z0-9]+/', '_', strtolower(trim($header))), '_');
    }

    private function label(string $field): string
    {
        return ucfirst(str_replace(['_ids', '_id', '_'], ['', '', ' '], $field));
    }
}
