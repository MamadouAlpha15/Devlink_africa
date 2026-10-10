<?php

namespace App\Enums;

enum Availability: string
{
    case Available = 'available';
    case Open = 'open';
    case Busy = 'busy';

    public function label(): string
    {
        return match ($this) {
            self::Available => 'Disponible',
            self::Open => 'Ouvert aux propositions',
            self::Busy => 'Indisponible',
        };
    }
}
