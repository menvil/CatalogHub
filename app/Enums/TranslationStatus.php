<?php

namespace App\Enums;

enum TranslationStatus: string
{
    case Missing = 'missing';
    case MachineTranslated = 'machine_translated';
    case HumanReviewed = 'human_reviewed';
    case Approved = 'approved';
    case Outdated = 'outdated';

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return [
            self::Missing->value => 'Missing',
            self::MachineTranslated->value => 'Machine translated',
            self::HumanReviewed->value => 'Human reviewed',
            self::Approved->value => 'Approved',
            self::Outdated->value => 'Outdated',
        ];
    }
}
