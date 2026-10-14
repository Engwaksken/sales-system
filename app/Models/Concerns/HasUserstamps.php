<?php

namespace App\Models\Concerns;

use Illuminate\Support\Facades\Auth;

/**
 * Auto-fills created_by/updated_by from the authenticated user whenever a model
 * that has those columns is saved. Applied to every generated model — safe to use
 * even on models without these columns (it checks first).
 *
 * Deliberately not tied to $fillable: even if a request smuggled a created_by/
 * updated_by value in, the generic controllers' validation rules exclude both
 * columns entirely, so this trait is the only thing that ever sets them.
 */
trait HasUserstamps
{
    public static function bootHasUserstamps(): void
    {
        static::creating(function ($model) {
            $userId = Auth::id();
            if (! $userId) {
                return;
            }
            if ($model->isFillable('created_by')) {
                $model->created_by = $model->created_by ?? $userId;
            }
            if ($model->isFillable('updated_by')) {
                $model->updated_by = $model->updated_by ?? $userId;
            }
        });

        static::updating(function ($model) {
            $userId = Auth::id();
            if (! $userId) {
                return;
            }
            if ($model->isFillable('updated_by')) {
                $model->updated_by = $userId;
            }
        });
    }
}
