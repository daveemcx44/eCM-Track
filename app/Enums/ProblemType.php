<?php

namespace App\Enums;

enum ProblemType: string
{
    case Physical = 'physical';
    case Behavioral = 'behavioral';
    case SUD = 'sud';
    case SDOHHousing = 'sdoh_housing';
    case SDOHFood = 'sdoh_food';
    case SDOHTransportation = 'sdoh_transportation';
    case SDOHOther = 'sdoh_other';

    public function label(): string
    {
        return match ($this) {
            self::Physical => 'Physical',
            self::Behavioral => 'Behavioral',
            self::SUD => 'SUD',
            self::SDOHHousing => 'SDOH - Housing',
            self::SDOHFood => 'SDOH - Food',
            self::SDOHTransportation => 'SDOH - Transportation',
            self::SDOHOther => 'SDOH - Other',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Physical => 'blue',
            self::Behavioral => 'purple',
            self::SUD => 'red',
            self::SDOHHousing => 'amber',
            self::SDOHFood => 'green',
            self::SDOHTransportation => 'cyan',
            self::SDOHOther => 'gray',
        };
    }
}
