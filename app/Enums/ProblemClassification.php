<?php

namespace App\Enums;

enum ProblemClassification: string
{
    case AssessmentEntryError = 'assessment_entry_error';
    case ProblemNoLongerConfirmed = 'problem_no_longer_confirmed';
    case ProblemResolved = 'problem_resolved';

    public function label(): string
    {
        return match ($this) {
            self::AssessmentEntryError => 'Assessment Entry Error',
            self::ProblemNoLongerConfirmed => 'Problem No Longer Confirmed',
            self::ProblemResolved => 'Problem Resolved',
        };
    }
}
