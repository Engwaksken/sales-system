<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Branch;
use App\Models\User;
use Illuminate\Http\Request;

/** The owner's audit trail: who did what, with suspicious edits flagged (view_activity_logs). */
class ActivityLogController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->only(['q', 'user_id', 'module', 'action', 'flagged', 'from', 'to', 'branch_id']);

        $logs = ActivityLog::query()
            ->filter($filters)
            ->latest('created_at')
            ->latest('id')
            ->paginate($this->perPage($request, 50))
            ->withQueryString();

        $since = now()->subDays(7);

        return view('activity-logs.index', [
            'logs' => $logs,
            'filters' => $filters,
            'modules' => ActivityLog::MODULES,
            'users' => User::query()->where('role', '!=', 'customer')->orderBy('name')->get(['id', 'name', 'role']),
            'branches' => Branch::query()->orderBy('name')->get(['id', 'name']),
            'flaggedThisWeek' => ActivityLog::query()->where('flagged', true)->where('created_at', '>=', $since)->count(),
            'topFlagged' => ActivityLog::query()
                ->where('flagged', true)->where('created_at', '>=', $since)->whereNotNull('user_id')
                ->selectRaw('user_id, user_name, count(*) as total')
                ->groupBy('user_id', 'user_name')->orderByDesc('total')->limit(5)->get(),
        ]);
    }
}
