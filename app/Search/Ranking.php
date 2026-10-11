<?php

namespace App\Search;

use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * "Classement africain" (cahier, section 16): Afrique or one country, all time, one year or one month.
 * Example: /ranking?country=guinee&period=month&month=2026-10
 */
class Ranking
{
    public const PERIODS = [
        'all' => 'Depuis toujours',
        'year' => 'Année',
        'month' => 'Mois',
    ];

    private function __construct(
        public readonly ?string $country,
        public readonly string $period,
        public readonly CarbonImmutable $date,
    ) {}

    public static function fromRequest(Request $request): self
    {
        $period = $request->query('period');
        $period = is_string($period) && array_key_exists($period, self::PERIODS) ? $period : 'all';

        $country = $request->query('country');
        $country = is_string($country) && $country !== '' ? $country : null;

        return new self($country, $period, self::parseDate($period, $request));
    }

    public static function make(?string $country = null, string $period = 'all', ?CarbonImmutable $date = null): self
    {
        return new self($country, $period, $date ?? CarbonImmutable::now());
    }

    /**
     * "month=2026-10" or "year=2026"; anything invalid or in the future falls back to the current period.
     */
    private static function parseDate(string $period, Request $request): CarbonImmutable
    {
        $now = CarbonImmutable::now();
        $month = $request->query('month');
        $year = $request->query('year');

        $date = match (true) {
            $period === 'month' && is_string($month) && preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $month) === 1 => CarbonImmutable::createFromFormat('!Y-m', $month),
            $period === 'year' && is_string($year) && preg_match('/^\d{4}$/', $year) === 1 => CarbonImmutable::create((int) $year),
            default => $now,
        };

        return $date->greaterThan($now) || $date->year < 2020 ? $now : $date;
    }

    /**
     * @return array{0: CarbonImmutable, 1: CarbonImmutable}|null
     */
    public function range(): ?array
    {
        return match ($this->period) {
            'month' => [$this->date->startOfMonth(), $this->date->endOfMonth()],
            'year' => [$this->date->startOfYear(), $this->date->endOfYear()],
            default => null,
        };
    }

    /**
     * "Octobre 2026", "2026" or "Depuis toujours".
     */
    public function periodLabel(): string
    {
        return match ($this->period) {
            'month' => ucfirst($this->date->locale('fr')->translatedFormat('F Y')),
            'year' => (string) $this->date->year,
            default => self::PERIODS['all'],
        };
    }

    /**
     * @return array{country: ?string, period: string, month: string, year: string}
     */
    public function input(): array
    {
        return [
            'country' => $this->country,
            'period' => $this->period,
            'month' => $this->date->format('Y-m'),
            'year' => (string) $this->date->year,
        ];
    }

    /**
     * Listed developers with their points over the period (`points`), best first.
     */
    public function query(): Builder
    {
        $inPeriod = fn ($transactions) => $this->range()
            ? $transactions->whereBetween('point_transactions.created_at', $this->range())
            : $transactions;

        return User::query()
            ->listed()
            ->with(['country', 'badges'])
            ->when($this->country, fn (Builder $q) => $q->whereRelation('country', 'slug', $this->country))
            ->whereHas('pointTransactions', $inPeriod)
            ->withSum(['pointTransactions as points' => $inPeriod], 'points')
            ->orderByDesc('points')
            ->orderBy('name');
    }

    /**
     * Rank of a developer in this ranking (1 = first), or null if they are not in it.
     */
    public function rankOf(User $user): ?int
    {
        $ranked = $this->query()->toBase();
        $mine = DB::query()->fromSub($ranked, 'ranking')->where('id', $user->id)->value('points');

        if ($mine === null) {
            return null;
        }

        return DB::query()->fromSub($this->query()->toBase(), 'ranking')->where('points', '>', $mine)->count() + 1;
    }
}
