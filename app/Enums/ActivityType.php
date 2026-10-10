<?php

namespace App\Enums;

enum ActivityType: string
{
    case ResourcePublished = 'resource_published';
    case ProjectCreated = 'project_created';
    case ProjectJoined = 'project_joined';
    case BadgeEarned = 'badge_earned';

    public function label(): string
    {
        return match ($this) {
            self::ResourcePublished => 'a publié une ressource',
            self::ProjectCreated => 'a créé un projet',
            self::ProjectJoined => 'a rejoint un projet',
            self::BadgeEarned => 'a obtenu un badge',
        };
    }
}
