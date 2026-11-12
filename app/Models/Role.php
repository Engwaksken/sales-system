<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    protected $fillable = ['name', 'label'];

    public static function options(): array
    {
        return static::query()->orderBy('label')->pluck('label', 'name')->all();
    }
}
