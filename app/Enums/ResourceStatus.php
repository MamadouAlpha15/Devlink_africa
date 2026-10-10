<?php

namespace App\Enums;

enum ResourceStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'En attente',
            self::Approved => 'Publiée',
            self::Rejected => 'Refusée',
        };
    }
}
