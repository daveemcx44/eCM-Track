<?php

namespace App\Enums;

enum OutreachOutcome: string
{
    case SuccessfulContact = 'successful_contact';
    case NoAnswer = 'no_answer';
    case LeftMessage = 'left_message';
    case DeclinedServices = 'declined_services';
    case ScheduledFollowUp = 'scheduled_follow_up';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::SuccessfulContact => 'Successful Contact',
            self::NoAnswer => 'No Answer',
            self::LeftMessage => 'Left Message',
            self::DeclinedServices => 'Declined Services',
            self::ScheduledFollowUp => 'Scheduled Follow-up',
            self::Other => 'Other',
        };
    }
}
