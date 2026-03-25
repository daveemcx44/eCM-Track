<?php

namespace App\Enums;

enum ResourceRating: string
{
    case Worse = 'worse';
    case Same = 'same';
    case Better = 'better';

    public function label(): string
    {
        return match ($this) {
            self::Worse => 'Worse',
            self::Same => 'Same',
            self::Better => 'Better',
        };
    }
}
