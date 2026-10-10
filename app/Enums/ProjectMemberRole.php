<?php

namespace App\Enums;

enum ProjectMemberRole: string
{
    case Owner = 'owner';
    case CoOwner = 'co_owner';
    case Member = 'member';

    public function label(): string
    {
        return match ($this) {
            self::Owner => 'Propriétaire',
            self::CoOwner => 'Co-responsable',
            self::Member => 'Membre',
        };
    }
}
