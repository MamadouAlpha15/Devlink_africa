<?php

namespace App\Enums;

enum ReportReason: string
{
    case Spam = 'spam';
    case Inappropriate = 'inappropriate';
    case Scam = 'scam';
    case Illegal = 'illegal';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Spam => 'Spam',
            self::Inappropriate => 'Contenu inapproprié',
            self::Scam => 'Arnaque',
            self::Illegal => 'Contenu illégal',
            self::Other => 'Autre',
        };
    }
}
