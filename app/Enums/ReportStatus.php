<?php

namespace App\Enums;

enum ReportStatus: string
{
    case Pending = 'pending';
    case Resolved = 'resolved';
    case Dismissed = 'dismissed';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'À traiter',
            self::Resolved => 'Traité',
            self::Dismissed => 'Rejeté',
        };
    }
}
