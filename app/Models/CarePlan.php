<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CarePlan extends Model
{
    use HasFactory;

    protected $fillable = [
        'member_id', 'version_number', 'assessment_type',
        'assessment_date', 'risk_level', 'next_reassessment_date',
    ];

    protected function casts(): array
    {
        return [
            'assessment_date' => 'date',
            'next_reassessment_date' => 'date',
        ];
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function problems(): HasMany
    {
        return $this->hasMany(Problem::class);
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    /**
     * Check if reassessment is overdue.
     */
    public function isReassessmentOverdue(): bool
    {
        return $this->next_reassessment_date && $this->next_reassessment_date->isPast();
    }
}
