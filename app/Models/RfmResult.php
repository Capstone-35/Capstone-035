<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RfmResult extends Model
{
    protected $fillable = [
        'rfm_analysis_id',
        'member_id',
        'recency_days',
        'frequency',
        'monetary',
        'r_score',
        'f_score',
        'm_score',
        'segment_id',
        'segment_name',
    ];

    protected function casts(): array
    {
        return [
            'recency_days' => 'integer',
            'frequency' => 'integer',
            'monetary' => 'integer',
            'r_score' => 'integer',
            'f_score' => 'integer',
            'm_score' => 'integer',
        ];
    }

    public function analysis(): BelongsTo
    {
        return $this->belongsTo(RfmAnalysis::class, 'rfm_analysis_id');
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function segment(): BelongsTo
    {
        return $this->belongsTo(RfmSegment::class);
    }

    public function compositeScore(): string
    {
        return "{$this->r_score}-{$this->f_score}-{$this->m_score}";
    }
}
