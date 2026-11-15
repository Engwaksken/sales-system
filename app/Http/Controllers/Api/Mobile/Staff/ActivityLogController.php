<?php

namespace App\Http\Controllers\Api\Mobile\Staff;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** The owner's audit trail in the app (view_activity_logs; admins always). */
class ActivityLogController extends StaffController
{
    public function index(Request $request): JsonResponse
    {
        $this->authorizePermission($request, 'view_activity_logs');

        $page = ActivityLog::query()
            ->filter($request->only(['q', 'user_id', 'module', 'action', 'flagged', 'from', 'to', 'branch_id']))
            ->latest('created_at')
            ->latest('id')
            ->paginate($this->perPage($request, 20));

        return response()->json([
            'data' => collect($page->items())->map(fn (ActivityLog $log) => [
                'id' => $log->id,
                'action' => $log->action,
                'description' => $log->description,
                'user_id' => $log->user_id,
                'user_name' => $log->user_name ?? 'System',
                'user_role' => $log->user_role,
                'subject_type' => $log->subject_type,
                'changes' => $log->changes,
                'context' => $log->context,
                'flagged' => $log->flagged,
                'flag_reason' => $log->flag_reason,
                'source' => $log->source,
                'ip_address' => $log->ip_address,
                'created_at' => optional($log->created_at)?->toISOString(),
            ])->all(),
            'meta' => $this->meta($page),
        ]);
    }

    /** Filter values and the week's flagged count for the screen header. */
    public function summary(Request $request): JsonResponse
    {
        $this->authorizePermission($request, 'view_activity_logs');
        $since = now()->subDays(7);

        return response()->json([
            'flagged_this_week' => ActivityLog::query()->where('flagged', true)->where('created_at', '>=', $since)->count(),
            'top_flagged' => ActivityLog::query()
                ->where('flagged', true)->where('created_at', '>=', $since)->whereNotNull('user_id')
                ->selectRaw('user_id, user_name, count(*) as total')
                ->groupBy('user_id', 'user_name')->orderByDesc('total')->limit(5)->get(),
            'users' => User::query()->where('role', '!=', 'customer')->orderBy('name')->get(['id', 'name', 'role']),
            'modules' => collect(ActivityLog::MODULES)->map(fn ($label, $key) => ['key' => $key, 'label' => $label])->values(),
        ]);
    }
}
