<?php

namespace App\Enums;

enum TaskType: string
{
    case Goal = 'goal';
    case Referrals = 'referrals';
    case CommunitySupportsReferral = 'community_supports_referral';
    case Procedure = 'procedure';
    case DiagnosticStudy = 'diagnostic_study';
    case Medication = 'medication';
    case FollowUp = 'follow_up';
    case Evaluation = 'evaluation';
    case Admission = 'admission';
    case Discharge = 'discharge';
    case Action = 'action';

    public function label(): string
    {
        return match ($this) {
            self::Goal => 'Goal',
            self::Referrals => 'Referrals',
            self::CommunitySupportsReferral => 'Community Supports Referral',
            self::Procedure => 'Procedure',
            self::DiagnosticStudy => 'Diagnostic Study',
            self::Medication => 'Medication',
            self::FollowUp => 'Follow Up',
            self::Evaluation => 'Evaluation',
            self::Admission => 'Admission',
            self::Discharge => 'Discharge',
            self::Action => 'Action',
        };
    }

    public function requiresApproval(): bool
    {
        return match ($this) {
            self::Goal => false,
            default => true,
        };
    }
}
