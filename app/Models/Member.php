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
        'member_consent_status', 'bh_consent_status', 'sud_consent_status',
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

    public function carePlans(): HasMany
    {
        return $this->hasMany(CarePlan::class);
    }

    public function outreachLogs(): HasMany
    {
        return $this->hasMany(OutreachLog::class);
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

    public function isMemberConsentBlocked(): bool
    {
        return $this->member_consent_status === 'no_consent';
    }

    public function isBhConsentBlocked(): bool
    {
        return $this->bh_consent_status === 'no_consent';
    }

    public function isSudConsentBlocked(): bool
    {
        return $this->sud_consent_status === 'no_consent';
    }

    /**
     * Check if the entire CM module is blocked (Member or JI consent = No Consent).
     */
    public function isCmModuleBlocked(): bool
    {
        return $this->isMemberConsentBlocked() || $this->isJiConsentBlocked();
    }

    /**
     * Get the reason the CM module is blocked, or null if not blocked.
     */
    public function getCmBlockReason(): ?string
    {
        if ($this->isMemberConsentBlocked()) {
            return 'Member Consent is set to No Consent. All Care Management access is restricted.';
        }

        if ($this->isJiConsentBlocked()) {
            return 'JI Consent is set to No Consent. All Care Management access is restricted.';
        }

        return null;
    }

    /**
     * Get the consent type that caused the block, for audit purposes.
     */
    public function getCmBlockConsentType(): ?string
    {
        if ($this->isMemberConsentBlocked()) {
            return 'member_consent';
        }

        if ($this->isJiConsentBlocked()) {
            return 'ji_consent';
        }

        return null;
    }
}
