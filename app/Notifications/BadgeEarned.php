<?php

namespace App\Notifications;

use App\Models\Badge;

/**
 * Bonus: a new badge was unlocked.
 */
class BadgeEarned extends DevLinkNotification
{
    public function __construct(public Badge $badge) {}

    protected function message(): string
    {
        return 'Nouveau badge débloqué : '.$this->badge->name.' !';
    }

    protected function url(): string
    {
        return route('devscore.show');
    }

    protected function icon(): string
    {
        return $this->badge->icon;
    }

    public function databaseType(object $notifiable): string
    {
        return 'badge_earned';
    }
}
