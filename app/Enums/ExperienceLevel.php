<?php

namespace App\Enums;

enum ExperienceLevel: string
{
    case Junior = 'junior';
    case Intermediate = 'intermediate';
    case Senior = 'senior';
    case Expert = 'expert';

    public function label(): string
    {
        return match ($this) {
            self::Junior => 'Junior',
            self::Intermediate => 'Intermédiaire',
            self::Senior => 'Senior',
            self::Expert => 'Expert',
        };
    }
}
