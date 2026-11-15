<?php

namespace App\Http\Controllers\Api\Mobile\Staff;

use App\Http\Controllers\ReportController as WebReportController;
use App\Http\Middleware\CheckPermission;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** The website's reports as JSON tables for the mobile app. */
class ReportController extends StaffController
{
    /** type => [label, permission needed (null: any staff member)] */
    public const TYPES = [
        'overview' => ['Overview', null],
        'sales' => ['Sales', null],
        'salesperson_performance' => ['Salesperson performance', null],
        'expenses' => ['Expenses', null],
        'stock' => ['Stock', null],
        'expiry' => ['Expiry', null],
        'products' => ['Products', null],
        'customers' => ['Customers', null],
        'suppliers' => ['Suppliers', null],
        'purchases' => ['Purchases', null],
        'profit' => ['Profit', 'manage_statements'],
        'income_statement' => ['Income statement', 'manage_statements'],
        'trial_balance' => ['Trial balance', 'manage_statements'],
    ];

    private const MAX_ROWS = 500;

    public function index(Request $request): JsonResponse
    {
        abort_if(($request->user()->role ?: 'customer') === 'customer', 403);

        return response()->json(['data' => collect(self::TYPES)
            ->filter(fn ($type) => $type[1] === null || CheckPermission::allows($request->user(), $type[1]))
            ->map(fn ($type, $key) => ['type' => $key, 'label' => $type[0]])
            ->values()]);
    }

    public function show(Request $request, string $type, WebReportController $reports): JsonResponse
    {
        abort_unless(isset(self::TYPES[$type]), 404);
        abort_if(($request->user()->role ?: 'customer') === 'customer', 403);
        if ($permission = self::TYPES[$type][1]) {
            $this->authorizePermission($request, $permission);
        }

        // Non-admins only report on their own branch.
        if (! $this->isAdmin($request)) {
            $request->query->set('branch_id', $this->ownBranch($request));
        }

        [$from, $to, $data] = $reports->build($request, $type);
        $rows = collect($data['rows'] ?? [])->map(fn ($row) => is_array($row) ? $row : (array) $row);

        return response()->json([
            'type' => $type,
            'label' => self::TYPES[$type][0],
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
            'headers' => $data['headers'] ?? [],
            'keys' => array_keys($rows->first() ?? []),
            'money_columns' => $data['moneyColumns'] ?? [],
            'rows' => $rows->take(self::MAX_ROWS)->values(),
            'total_rows' => $rows->count(),
        ]);
    }
}
