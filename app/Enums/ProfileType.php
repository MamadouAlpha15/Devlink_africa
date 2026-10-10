<?php

namespace App\Enums;

enum ProfileType: string
{
    case Developer = 'developer';
    case Designer = 'designer';
    case Student = 'student';
    case Data = 'data';
    case DevOps = 'devops';
    case Product = 'product';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Developer => 'Développeur',
            self::Designer => 'Designer',
            self::Student => 'Étudiant',
            self::Data => 'Data / IA',
            self::DevOps => 'DevOps',
            self::Product => 'Chef de produit',
            self::Other => 'Autre',
        };
    }
}
