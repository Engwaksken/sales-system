<?php

namespace App\Http\Controllers\Api\Mobile\Staff;

use App\Http\Middleware\CheckPermission;
use App\Support\AdminResources;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * The website's admin modules for the mobile app (see App\Support\AdminResources):
 * list, view, create, edit and delete with the website's rules and permissions.
 */
class AdminController extends StaffController
{
    /** Columns never sent to the app, whatever the module. */
    private const HIDDEN = ['password', 'remember_token', 'verification_code', 'client_secret', 'smtp_password',
        'portal_password_enc', 'private_key_pem', 'public_key_pem', 'ura_public_key_pem', 'signature'];

    /** The modules (and the Settings / Role permissions screens) this user may open. */
    public function modules(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_if(($user?->role ?: 'customer') === 'customer', 403);

        $modules = [];
        foreach (AdminResources::all() as $key => $r) {
            if (CheckPermission::allows($user, $r['permission']) && Schema::hasTable((new $r['model'])->getTable())) {
                $modules[] = ['key' => $key, 'label' => $r['label'], 'group' => $r['group'], 'icon' => $r['icon'], 'read_only' => ! empty($r['read_only'])];
            }
        }
        if (CheckPermission::allows($user, 'manage_settings')) {
            $modules[] = ['key' => 'settings', 'label' => 'System Settings', 'group' => 'Settings', 'icon' => 'settings', 'read_only' => false, 'screen' => 'settings'];
        }
        if (CheckPermission::allows($user, 'manage_users')) {
            $modules[] = ['key' => 'role-permissions', 'label' => 'Role Permissions', 'group' => 'Admin', 'icon' => 'admin_panel_settings', 'read_only' => false, 'screen' => 'role-permissions'];
        }

        return response()->json(['data' => $modules]);
    }

    public function schema(Request $request, string $resource): JsonResponse
    {
        $r = $this->resource($request, $resource);

        return response()->json(['data' => [
            'key' => $resource,
            'label' => $r['label'],
            'read_only' => ! empty($r['read_only']),
            'fields' => array_map(fn ($f) => $this->fieldSchema($f), $r['fields']),
        ]]);
    }

    public function index(Request $request, string $resource): JsonResponse
    {
        $r = $this->resource($request, $resource);
        $query = $r['model']::query();
        $columns = Schema::getColumnListing($query->getModel()->getTable());

        if ($search = trim((string) $request->query('q', ''))) {
            $searchable = array_values(array_intersect($r['search'] ?: $this->guessSearch($columns), $columns));
            $query->where(function ($q) use ($searchable, $search) {
                foreach ($searchable as $column) {
                    $q->orWhere($column, 'like', "%{$search}%");
                }
            });
        }

        $page = $query->orderByDesc($query->getModel()->getKeyName())->paginate(min(50, max(5, $request->integer('per_page', 20))));

        return response()->json([
            'data' => collect($page->items())->map(fn (Model $m) => $this->row($r, $m, $columns))->values(),
            'meta' => $this->meta($page),
        ]);
    }

    public function show(Request $request, string $resource, int $id): JsonResponse
    {
        $r = $this->resource($request, $resource);
        $model = $r['model']::query()->findOrFail($id);

        return response()->json(['data' => $this->detail($r, $model)]);
    }

    public function store(Request $request, string $resource): JsonResponse
    {
        $r = $this->resource($request, $resource, write: true);
        $data = $this->validated($request, $r, null);

        $model = DB::transaction(fn () => $r['model']::create($data));

        return response()->json(['message' => Str::singular($r['label']).' created.', 'data' => $this->detail($r, $model->fresh())], 201);
    }

    public function update(Request $request, string $resource, int $id): JsonResponse
    {
        $r = $this->resource($request, $resource, write: true);
        $model = $r['model']::query()->findOrFail($id);
        $data = $this->validated($request, $r, $model);

        DB::transaction(fn () => $model->update($data));

        return response()->json(['message' => Str::singular($r['label']).' saved.', 'data' => $this->detail($r, $model->fresh())]);
    }

    public function destroy(Request $request, string $resource, int $id): JsonResponse
    {
        $r = $this->resource($request, $resource, write: true);
        $model = $r['model']::query()->findOrFail($id);

        if ($resource === 'users' && (int) $model->getKey() === (int) $request->user()->getKey()) {
            abort(422, 'You cannot delete your own account.');
        }

        try {
            $model->delete();
        } catch (\Illuminate\Database\QueryException) {
            abort(422, 'This record is in use elsewhere and cannot be deleted.');
        }

        return response()->json(['message' => Str::singular($r['label']).' deleted.']);
    }

    // ---------------------------------------------------------------------

    private function resource(Request $request, string $key, bool $write = false): array
    {
        $r = AdminResources::get($key);
        abort_unless($r, 404);
        $this->authorizePermission($request, $r['permission']);
        abort_if($write && ! empty($r['read_only']), 403, 'This module is view only.');
        abort_unless(Schema::hasTable((new $r['model'])->getTable()), 404);

        return $r;
    }

    private function validated(Request $request, array $r, ?Model $model): array
    {
        $rules = [];
        foreach ($r['fields'] as $f) {
            $rule = $model && isset($f['update_rules']) ? $f['update_rules'] : $f['rules'];
            if (is_string($rule)) {
                $rule = str_replace('{id}', (string) ($model?->getKey() ?? 'NULL'), $rule);
            }
            $rules[$f['name']] = $rule;
        }

        // Unticked switches arrive as false/0, or not at all.
        foreach ($r['fields'] as $f) {
            if ($f['type'] === 'bool') {
                $request->merge([$f['name'] => $request->boolean($f['name'])]);
            }
        }

        $data = $request->validate($rules);

        foreach ($r['fields'] as $f) {
            $name = $f['name'];
            // Blank secrets and passwords on edit keep the stored value.
            if (in_array($f['type'], ['secret', 'password'], true) && $model && blank($data[$name] ?? null)) {
                unset($data[$name]);
                continue;
            }
            if ($f['type'] === 'password' && filled($data[$name] ?? null)) {
                $data[$name] = Hash::make($data[$name]);
            }
        }

        return $data;
    }

    private function fieldSchema(array $f): array
    {
        $rules = is_array($f['rules']) ? implode('|', array_filter($f['rules'], 'is_string')) : $f['rules'];
        $out = [
            'name' => $f['name'],
            'type' => $f['type'],
            'label' => $f['label'],
            'required' => str_contains($rules, 'required'),
            'required_on_edit' => str_contains($f['update_rules'] ?? $rules, 'required'),
            'help' => $f['help'] ?? ($f['type'] === 'secret' ? 'Hidden for security. Leave blank to keep the saved value.' : null),
            'default' => $f['default'] ?? ($f['type'] === 'bool' ? false : null),
            'multiline' => $f['multiline'] ?? false,
            'allow_custom' => $f['allow_custom'] ?? false,
            'empty_label' => $f['empty'] ?? null,
        ];
        if (isset($f['options'])) {
            $out['options'] = collect($f['options'])->map(fn ($label, $value) => ['value' => (string) $value, 'label' => (string) $label])->values();
        }
        if (isset($f['source'])) {
            $out['options'] = $this->options($f['source']);
        }

        return $out;
    }

    /** Choices for relation fields, labelled the way people know them. */
    private function options(string $source): array
    {
        $q = fn (string $table) => Schema::hasTable($table) ? DB::table($table) : null;
        $rows = match ($source) {
            'branches' => $q('branches')?->orderBy('name')->get(['id', 'name'])->map(fn ($r) => [$r->id, $r->name ?: 'Branch #'.$r->id]),
            'users' => $q('users')?->where('role', '!=', 'customer')->orderBy('name')->get(['id', 'name', 'email'])->map(fn ($r) => [$r->id, "{$r->name} ({$r->email})"]),
            'customers' => $q('customers')?->orderBy('name')->limit(500)->get(['id', 'name'])->map(fn ($r) => [$r->id, $r->name]),
            'orders' => $q('orders')?->orderByDesc('id')->limit(300)->get()->map(fn ($r) => [$r->id, trim(($r->order_number ?? $r->order_id ?? '#'.$r->id).' | '.($r->customer_name ?? ''), ' |')]),
            'journals' => $q('accounting_journals')?->orderByDesc('journal_date')->limit(300)->get(['id', 'journal_no', 'description'])->map(fn ($r) => [$r->id, "{$r->journal_no} | ".Str::limit((string) $r->description, 40)]),
            'accounts' => $q('chart_of_accounts')?->orderBy('account_code')->get(['account_code', 'account_name'])->map(fn ($r) => [$r->account_code, "{$r->account_code} | {$r->account_name}"]),
            'efris-config' => $q('efris_config')?->get(['id', 'taxpayer_name', 'tin'])->map(fn ($r) => [$r->id, "{$r->taxpayer_name} ({$r->tin})"]),
            'nav-pages' => $q('nav_pages')?->orderBy('label')->get(['id', 'label'])->map(fn ($r) => [$r->id, $r->label]),
            'permission-keys' => $q('permissions')?->orderBy('label')->get(['permission_key', 'label'])->map(fn ($r) => [$r->permission_key, "{$r->label} ({$r->permission_key})"]),
            default => collect(),
        } ?? collect();

        return $rows->map(fn ($p) => ['value' => (string) $p[0], 'label' => (string) $p[1]])->values()->all();
    }

    private function row(array $r, Model $m, array $columns): array
    {
        $attrs = $m->getAttributes();
        $title = $r['title'] ? ($attrs[$r['title']] ?? null) : null;
        if ($title === null || $title === '') {
            $title = collect(['name', 'title', 'invoice_no', 'fdn', 'reference', 'endpoint', 'action'])->map(fn ($c) => $attrs[$c] ?? null)->first(fn ($v) => filled($v))
                ?? Str::singular($r['label']).' #'.$m->getKey();
        }
        $subtitle = collect($r['subtitle'] ?: array_slice(array_diff($columns, ['id', ...self::HIDDEN]), 1, 3))
            ->map(fn ($c) => $this->display($attrs[$c] ?? null, $c))
            ->filter(fn ($v) => $v !== '')
            ->implode(' | ');

        return ['id' => $m->getKey(), 'title' => (string) $title, 'subtitle' => $subtitle, 'active' => array_key_exists('is_active', $attrs) ? (bool) $attrs['is_active'] : null];
    }

    private function detail(array $r, Model $m): array
    {
        $attrs = $m->getAttributes();
        $values = [];
        foreach ($r['fields'] as $f) {
            $name = $f['name'];
            if (in_array($f['type'], ['secret', 'password'], true)) {
                $values[$name] = null;
                $values[$name.'__set'] = filled($attrs[$name] ?? null);
                continue;
            }
            $value = $attrs[$name] ?? null;
            $values[$name] = $f['type'] === 'bool' ? (bool) $value : $value;
        }

        // Every other column, read-only, for the record's details panel.
        $details = [];
        foreach ($attrs as $column => $value) {
            if (in_array($column, self::HIDDEN, true) || Str::endsWith($column, ['_pem', '_secret', 'password'])) {
                continue;
            }
            $details[] = ['label' => Str::headline($column), 'value' => $this->display($value, $column)];
        }

        return ['id' => $m->getKey(), 'title' => $this->row($r, $m, array_keys($attrs))['title'], 'values' => $values, 'details' => $details];
    }

    private function display(mixed $value, string $column): string
    {
        if ($value === null) {
            return '';
        }
        if (Str::startsWith($column, 'is_') || Str::startsWith($column, 'supports_') || in_array($column, ['sandbox_mode', 'vat_registered'], true)) {
            return $value ? 'Yes' : 'No';
        }

        return Str::limit(is_scalar($value) ? (string) $value : json_encode($value), 300);
    }

    private function guessSearch(array $columns): array
    {
        return array_values(array_intersect($columns, ['name', 'title', 'reference', 'invoice_no', 'fdn', 'endpoint', 'status', 'action']));
    }
}
