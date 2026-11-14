<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RfmSegment extends Model
{
    protected $fillable = [
        'slug',
        'name',
        'description',
        'rule',
        'priority',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'rule' => 'array',
            'priority' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function results(): HasMany
    {
        return $this->hasMany(RfmResult::class, 'segment_id');
    }
}
