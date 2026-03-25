<?php

namespace App\Enums;

enum EncounterSetting: string
{
    case Clinic = 'clinic';
    case MedicalOffice = 'medical_office';
    case UrgentCare = 'urgent_care';
    case EmergencyRoom = 'emergency_room';
    case Inpatient = 'inpatient';
    case Telehealth = 'telehealth';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Clinic => 'Clinic',
            self::MedicalOffice => 'Medical Office',
            self::UrgentCare => 'Urgent Care',
            self::EmergencyRoom => 'Emergency Room',
            self::Inpatient => 'Inpatient',
            self::Telehealth => 'Telehealth',
            self::Other => 'Other',
        };
    }
}
