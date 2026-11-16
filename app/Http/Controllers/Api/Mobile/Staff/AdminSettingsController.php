<?php

namespace App\Http\Controllers\Api\Mobile\Staff;

use App\Http\Controllers\RolePermissionsController;
use App\Http\Controllers\SettingsController;
use App\Models\Branch;
use App\Models\Permission;
use App\Models\Role;
use App\Models\RolePermission;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/** System settings and the role permission matrix, for admins in the mobile app. */
class AdminSettingsController extends StaffController
{
    private const FONTS = ['Lato', 'Inter', 'Poppins', 'Open Sans', 'Roboto', 'Nunito', 'Montserrat', 'Outfit', 'Arial', 'Georgia'];

    public function settings(Request $request): JsonResponse
    {
        $this->authorizePermission($request, 'manage_settings');

        return response()->json(['data' => $this->settingsPayload()]);
    }

    /** Same rules and storage as the website's Settings page (multipart for the logo and favicon). */
    public function saveSettings(Request $request, SettingsController $settings): JsonResponse
    {
        $this->authorizePermission($request, 'manage_settings');
        // The app sends every field; keep the stored value for anything it leaves out.
        $request->merge(array_merge(
            array_diff_key(array_merge(SettingsController::DEFAULTS, Setting::query()->pluck('setting_value', 'setting_key')->toArray()), $request->all(), array_flip(['system_logo', 'system_favicon'])),
        ));
        $settings->save($request);

        return response()->json(['message' => 'System settings saved.', 'data' => $this->settingsPayload()]);
    }

    public function rolePermissions(Request $request): JsonResponse
    {
        $this->authorizePermission($request, 'manage_users');

        return response()->json(['data' => $this->matrix()]);
    }

    public function saveRolePermissions(Request $request): JsonResponse
    {
        $this->authorizePermission($request, 'manage_users');
        $data = $request->validate([
            'permissions' => 'array',
            'permissions.*' => 'array',
            'permissions.*.*' => 'string',
        ]);

        RolePermissionsController::sync($data['permissions'] ?? []);

        return response()->json(['message' => 'Role permissions saved.', 'data' => $this->matrix()]);
    }

    public function storeRole(Request $request): JsonResponse
    {
        abort_unless($request->user()?->role === 'admin', 403, 'Only an admin can add roles.');
        $data = $request->validate(['label' => 'required|string|max:100']);
        $name = Str::slug($data['label'], '_');
        abort_if($name === '' || in_array($name, ['admin', 'customer'], true), 422, 'This role name is reserved.');
        Role::firstOrCreate(['name' => $name], ['label' => $data['label']]);
        Cache::flush();

        return response()->json(['message' => 'Role added. Choose what it can do below.', 'data' => $this->matrix()], 201);
    }

    private function settingsPayload(): array
    {
        $values = array_merge(SettingsController::DEFAULTS, Setting::query()->pluck('setting_value', 'setting_key')->toArray());
        $values = array_intersect_key($values, SettingsController::DEFAULTS);
        foreach (['system_logo', 'system_favicon'] as $image) {
            $values[$image.'_url'] = filled($values[$image]) ? asset(ltrim($values[$image], '/')) : null;
        }

        return [
            'values' => $values,
            'branches' => Branch::query()->orderBy('name')->get(['id', 'name'])->map(fn ($b) => ['value' => (string) $b->id, 'label' => $b->name])->values(),
            'fonts' => self::FONTS,
        ];
    }

    private function matrix(): array
    {
        $assigned = RolePermission::all()->groupBy('role')->map(fn ($rows) => $rows->pluck('permission_key')->values()->all());
        $labels = User::roleOptions();

        return [
            'roles' => collect(RolePermissionsController::editableRoles())->map(fn ($r) => ['key' => $r, 'label' => $labels[$r] ?? Str::headline($r)])->values(),
            'groups' => Permission::orderBy('category')->orderBy('label')->get()->groupBy('category')
                ->map(fn ($items, $category) => [
                    'category' => $category ?: 'Other',
                    'permissions' => $items->map(fn ($p) => ['key' => $p->permission_key, 'label' => $p->label, 'description' => $p->description])->values(),
                ])->values(),
            'assigned' => (object) $assigned->all(),
        ];
    }
}
