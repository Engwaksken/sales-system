<?php

namespace App\Models;

use App\Models\Concerns\HasUserstamps;
use Illuminate\Database\Eloquent\Model;

class Slide extends Model
{
    use HasUserstamps;

    protected $table = 'slides';

    protected $fillable = [
        'title',
        'subtitle',
        'badge_text',
        'image_url',
        'cta_label',
        'cta_link',
        'bg_color',
        'sort_order',
        'is_active',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

}
