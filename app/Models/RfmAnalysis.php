<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RfmAnalysis extends Model
{
    protected $fillable = [
        'analysis_date',
        'period_days',
        'parameters_snapshot',
        'triggered_by',
        'is_demo',
    ];

    protected function casts(): array
    {
        return [
            'analysis_date' => 'date',
            'period_days' => 'integer',
            'parameters_snapshot' => 'array',
            'is_demo' => 'boolean',
        ];
    }

    public function results(): HasMany
    {
        return $this->hasMany(RfmResult::class);
    }

    public function getMembersEvaluatedAttribute(): int
    {
        return $this->relationLoaded('results') ? $this->results->count() : $this->results()->count();
    }
}
