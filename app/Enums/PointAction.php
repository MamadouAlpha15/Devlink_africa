<?php

namespace App\Enums;

enum PointAction: string
{
    case ProfileCompleted = 'profile_completed';
    case ProjectCreated = 'project_created';
    case ResourcePublished = 'resource_published';
    case Collaboration = 'collaboration';
    case ResourceLiked = 'resource_liked';
    case ProjectCompleted = 'project_completed';

    /**
     * Points awarded for this action (adjustable in config/devscore.php).
     */
    public function points(): int
    {
        return (int) config('devscore.points.'.$this->value, 0);
    }

    public function label(): string
    {
        return match ($this) {
            self::ProfileCompleted => 'Profil complété',
            self::ProjectCreated => 'Projet créé',
            self::ResourcePublished => 'Ressource publiée',
            self::Collaboration => 'Collaboration',
            self::ResourceLiked => 'Ressource appréciée',
            self::ProjectCompleted => 'Projet terminé',
        };
    }
}
