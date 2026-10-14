<?php

namespace App\Http\Controllers;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

abstract class Controller
{
    /**
     * Apply a preset period to a query.
     */
    protected function applyPeriod(
        Builder $query,
        string $period,
        string $column
    ): Builder {
        return match ($period) {
            'today' => $query->whereDate($column, today()),

            'week' => $query->whereBetween(
                $column,
                [now()->startOfWeek(), now()->endOfWeek()]
            ),

            'month' => $query
                ->whereMonth($column, now()->month)
                ->whereYear($column, now()->year),

            'year' => $query->whereYear($column, now()->year),

            default => $query,
        };
    }

    /**
     * Apply custom From/To dates or a preset period.
     *
     * Custom From/To takes priority over the preset period.
     * This helper is used by Sales, Products, Expenses, Payments and other
     * recently updated listing controllers.
     */
    protected function applyRequestedDateFilter(
        Builder $query,
        Request $request,
        string $column
    ): Builder {
        if ($request->filled('from')) {
            $query->whereDate(
                $column,
                '>=',
                $request->date('from')->toDateString()
            );
        }

        if ($request->filled('to')) {
            $query->whereDate(
                $column,
                '<=',
                $request->date('to')->toDateString()
            );
        }

        // Explicit From/To is the final date filter.
        if ($request->filled('from') || $request->filled('to')) {
            return $query;
        }

        $period = (string) $request->query('period', 'all');

        if ($period !== '' && ! in_array($period, ['all', 'range'], true)) {
            return $this->applyPeriod($query, $period, $column);
        }

        return $query;
    }

    /**
     * Today / This Week / This Month / This Year statistics.
     *
     * @return array{today:mixed,week:mixed,month:mixed,year:mixed}
     */
    protected function periodStats(
        string $modelClass,
        string $column,
        ?string $sumColumn = null
    ): array {
        $base = fn (string $period) =>
            $this->applyPeriod($modelClass::query(), $period, $column);

        if ($sumColumn) {
            return [
                'today' => (float) $base('today')->sum($sumColumn),
                'week' => (float) $base('week')->sum($sumColumn),
                'month' => (float) $base('month')->sum($sumColumn),
                'year' => (float) $base('year')->sum($sumColumn),
            ];
        }

        return [
            'today' => $base('today')->count(),
            'week' => $base('week')->count(),
            'month' => $base('month')->count(),
            'year' => $base('year')->count(),
        ];
    }

    /**
     * Standard NSales page size.
     */
    protected function perPage(
        Request $request,
        int $default = 20
    ): int {
        $allowed = [10, 20, 50, 100];
        $requested = (int) $request->query('per_page', $default);

        return in_array($requested, $allowed, true)
            ? $requested
            : $default;
    }

    /**
     * Apply free-text search across multiple columns.
     */
    protected function applySearch(
        Builder $query,
        string $search,
        array $columns
    ): Builder {
        $search = trim($search);

        if ($search === '' || empty($columns)) {
            return $query;
        }

        return $query->where(
            function (Builder $q) use ($search, $columns): void {
                foreach ($columns as $column) {
                    $q->orWhere(
                        $column,
                        'like',
                        "%{$search}%"
                    );
                }
            }
        );
    }
}
