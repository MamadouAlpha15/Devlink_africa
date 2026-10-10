<?php

namespace App\Enums;

enum RequestDirection: string
{
    /** The developer asks to join the project. */
    case Application = 'application';

    /** The project owner invites the developer from their profile. */
    case Invitation = 'invitation';

    public function label(): string
    {
        return match ($this) {
            self::Application => 'Demande',
            self::Invitation => 'Invitation',
        };
    }
}
