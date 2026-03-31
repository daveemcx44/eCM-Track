<?php

namespace App\Enums;

enum OutreachMethod: string
{
    case Phone = 'phone';
    case InPerson = 'in_person';
    case Text = 'text';
    case HomeVisit = 'home_visit';
    case DigitalEmail = 'digital_email';
    case StreetOutreach = 'street_outreach';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Phone => 'Phone',
            self::InPerson => 'In-Person',
            self::Text => 'Text',
            self::HomeVisit => 'Home Visit',
            self::DigitalEmail => 'Digital/Email',
            self::StreetOutreach => 'Street Outreach',
            self::Other => 'Other',
        };
    }
}
