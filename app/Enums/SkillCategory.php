<?php

namespace App\Enums;

enum SkillCategory: string
{
    case Backend = 'backend';
    case Frontend = 'frontend';
    case Mobile = 'mobile';
    case Design = 'design';
    case DevOps = 'devops';
    case Data = 'data';
    case Database = 'database';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Backend => 'Backend',
            self::Frontend => 'Frontend',
            self::Mobile => 'Mobile',
            self::Design => 'Design',
            self::DevOps => 'DevOps',
            self::Data => 'Data / IA',
            self::Database => 'Base de données',
            self::Other => 'Autre',
        };
    }
}
