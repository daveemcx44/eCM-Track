<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Member extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name', 'dob', 'member_id', 'organization', 'status',
        'lead_care_manager', 'ji_consent_status',
    ];

    protected function casts(): array
    {
        return [
            'dob' => 'date',
        ];
    }

    public function problems(): HasMany
    {
        return $this->hasMany(Problem::class);
    }

    public function leadCareManager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'lead_care_manager');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function isJiConsentBlocked(): bool
    {
        return $this->ji_consent_status === 'no_consent';
    }
}
