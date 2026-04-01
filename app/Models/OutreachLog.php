<?php

namespace App\Models;

use App\Enums\OutreachMethod;
use App\Enums\OutreachOutcome;
use App\Models\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OutreachLog extends Model
{
    use BelongsToTenant, HasFactory;

    const UPDATED_AT = null;

    protected $fillable = [
        'tenant_id', 'member_id', 'method', 'outreach_date', 'outcome',
        'notes', 'staff_id', 'logged_at',
    ];

    protected function casts(): array
    {
        return [
            'method' => OutreachMethod::class,
            'outcome' => OutreachOutcome::class,
            'outreach_date' => 'datetime',
            'logged_at' => 'datetime',
        ];
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(User::class, 'staff_id');
    }

    /**
     * Outreach logs are append-only.
     */
    public function update(array $attributes = [], array $options = []): never
    {
        throw new \LogicException('Outreach logs are append-only and cannot be updated.');
    }

    /**
     * Outreach logs are append-only.
     */
    public function delete(): never
    {
        throw new \LogicException('Outreach logs are append-only and cannot be deleted.');
    }

    /**
     * Maximum outreach attempts per member.
     */
    public const MAX_ATTEMPTS = 3;
}
