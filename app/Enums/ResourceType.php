<?php

namespace App\Enums;

enum ResourceType: string
{
    case Api = 'api';
    case Template = 'template';
    case Framework = 'framework';
    case Package = 'package';
    case UiKit = 'ui_kit';
    case Documentation = 'documentation';
    case Script = 'script';
    case Library = 'library';
    case OpenSource = 'open_source';

    public function label(): string
    {
        return match ($this) {
            self::Api => 'API',
            self::Template => 'Template',
            self::Framework => 'Framework',
            self::Package => 'Package',
            self::UiKit => 'UI Kit',
            self::Documentation => 'Documentation',
            self::Script => 'Script',
            self::Library => 'Bibliothèque',
            self::OpenSource => 'Projet Open Source',
        };
    }
}
