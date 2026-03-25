<?php

namespace App\Models;

use App\Enums\EncounterSetting;
use App\Enums\TaskCompletionType;
use App\Enums\TaskState;
use App\Enums\TaskType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Task extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'problem_id', 'name', 'type', 'code', 'encounter_setting',
        'provider', 'task_date', 'due_date', 'state', 'completion_type',
        'submitted_by', 'submitted_at', 'approved_by', 'approved_at',
        'started_by', 'started_at', 'completed_by', 'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'type' => TaskType::class,
            'state' => TaskState::class,
            'completion_type' => TaskCompletionType::class,
            'encounter_setting' => EncounterSetting::class,
            'task_date' => 'date',
            'due_date' => 'date',
            'submitted_at' => 'datetime',
            'approved_at' => 'datetime',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function problem(): BelongsTo
    {
        return $this->belongsTo(Problem::class);
    }

    public function resources(): HasMany
    {
        return $this->hasMany(Resource::class);
    }

    public function notes(): MorphMany
    {
        return $this->morphMany(Note::class, 'notable');
    }

    public function stateHistory(): MorphMany
    {
        return $this->morphMany(StateChangeHistory::class, 'trackable');
    }

    public function submittedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function approvedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function startedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'started_by');
    }

    public function completedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by');
    }

    public function isGoal(): bool
    {
        return $this->type === TaskType::Goal;
    }

    public function isStarted(): bool
    {
        return $this->state === TaskState::Started;
    }

    public function isCompleted(): bool
    {
        return $this->state === TaskState::Completed;
    }
}
