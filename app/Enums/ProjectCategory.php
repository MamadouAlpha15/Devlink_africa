<?php

namespace App\Enums;

enum ProjectCategory: string
{
    case Fintech = 'fintech';
    case Healthtech = 'healthtech';
    case Edtech = 'edtech';
    case Agritech = 'agritech';
    case Ecommerce = 'ecommerce';
    case Logistics = 'logistics';
    case Social = 'social';
    case Govtech = 'govtech';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Fintech => 'FinTech',
            self::Healthtech => 'HealthTech',
            self::Edtech => 'EdTech',
            self::Agritech => 'AgriTech',
            self::Ecommerce => 'E-commerce',
            self::Logistics => 'Logistique & livraison',
            self::Social => 'Impact social',
            self::Govtech => 'GovTech',
            self::Other => 'Autre',
        };
    }
}
