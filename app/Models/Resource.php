<?php

namespace App\Models;

use App\Enums\ResourceRating;
use App\Models\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Resource extends Model
{
    use BelongsToTenant, HasFactory;

    protected $fillable = [
        'tenant_id', 'task_id', 'survey_name', 'survey_number', 'at_home', 'at_work', 'at_play',
        'details', 'submitted_by', 'submitted_at',
    ];

    protected function casts(): array
    {
        return [
            'at_home' => ResourceRating::class,
            'at_work' => ResourceRating::class,
            'at_play' => ResourceRating::class,
            'submitted_at' => 'datetime',
        ];
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    public function submittedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function update(array $attributes = [], array $options = []): never
    {
        throw new \LogicException('Resources are immutable and cannot be updated.');
    }

    public function delete(): never
    {
        throw new \LogicException('Resources are immutable and cannot be deleted.');
    }
}
