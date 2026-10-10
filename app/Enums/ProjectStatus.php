<?php

namespace App\Enums;

enum ProjectStatus: string
{
    case Idea = 'idea';
    case Recruiting = 'recruiting';
    case InProgress = 'in_progress';
    case Completed = 'completed';
    case Archived = 'archived';

    public function label(): string
    {
        return match ($this) {
            self::Idea => 'Idée',
            self::Recruiting => 'Recrutement',
            self::InProgress => 'En cours',
            self::Completed => 'Terminé',
            self::Archived => 'Archivé',
        };
    }
}
