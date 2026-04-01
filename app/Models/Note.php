<?php

namespace App\Models;

use App\Models\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Note extends Model
{
    use BelongsToTenant, HasFactory;

    const UPDATED_AT = null;

    protected $fillable = [
        'tenant_id', 'content', 'created_by', 'notable_type', 'notable_id', 'notify',
    ];

    protected function casts(): array
    {
        return [
            'notify' => 'boolean',
        ];
    }

    public function notable(): MorphTo
    {
        return $this->morphTo();
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function update(array $attributes = [], array $options = []): never
    {
        throw new \LogicException('Notes are append-only and cannot be updated.');
    }

    public function delete(): never
    {
        throw new \LogicException('Notes are append-only and cannot be deleted.');
    }
}
