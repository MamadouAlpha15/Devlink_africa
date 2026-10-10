<?php

namespace Database\Seeders;

use App\Models\Country;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CountrySeeder extends Seeder
{
    /**
     * The 54 African countries (ISO 3166-1 alpha-2 => French name).
     */
    public const COUNTRIES = [
        'ZA' => 'Afrique du Sud', 'DZ' => 'Algérie', 'AO' => 'Angola', 'BJ' => 'Bénin',
        'BW' => 'Botswana', 'BF' => 'Burkina Faso', 'BI' => 'Burundi', 'CM' => 'Cameroun',
        'CV' => 'Cap-Vert', 'CF' => 'Centrafrique', 'KM' => 'Comores', 'CG' => 'Congo',
        'CD' => 'RD Congo', 'CI' => 'Côte d\'Ivoire', 'DJ' => 'Djibouti', 'EG' => 'Égypte',
        'ER' => 'Érythrée', 'SZ' => 'Eswatini', 'ET' => 'Éthiopie', 'GA' => 'Gabon',
        'GM' => 'Gambie', 'GH' => 'Ghana', 'GN' => 'Guinée', 'GW' => 'Guinée-Bissau',
        'GQ' => 'Guinée équatoriale', 'KE' => 'Kenya', 'LS' => 'Lesotho', 'LR' => 'Liberia',
        'LY' => 'Libye', 'MG' => 'Madagascar', 'MW' => 'Malawi', 'ML' => 'Mali',
        'MA' => 'Maroc', 'MU' => 'Maurice', 'MR' => 'Mauritanie', 'MZ' => 'Mozambique',
        'NA' => 'Namibie', 'NE' => 'Niger', 'NG' => 'Nigeria', 'UG' => 'Ouganda',
        'RW' => 'Rwanda', 'ST' => 'Sao Tomé-et-Principe', 'SN' => 'Sénégal', 'SC' => 'Seychelles',
        'SL' => 'Sierra Leone', 'SO' => 'Somalie', 'SD' => 'Soudan', 'SS' => 'Soudan du Sud',
        'TZ' => 'Tanzanie', 'TD' => 'Tchad', 'TG' => 'Togo', 'TN' => 'Tunisie',
        'ZM' => 'Zambie', 'ZW' => 'Zimbabwe',
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $rows = collect(self::COUNTRIES)->map(fn (string $name, string $code) => [
            'code' => $code,
            'name' => $name,
            'flag' => self::flag($code),
            'slug' => Str::slug($name),
        ])->values()->all();

        Country::upsert($rows, ['code'], ['name', 'flag', 'slug']);
    }

    /**
     * Turn an ISO code into its flag emoji ("GN" → 🇬🇳).
     */
    public static function flag(string $code): string
    {
        return implode('', array_map(
            fn (string $letter) => mb_chr(0x1F1E6 + ord($letter) - ord('A')),
            str_split(strtoupper($code)),
        ));
    }
}
