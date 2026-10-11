{{--
    Classement africain — version fonctionnelle minimale (backend).
    Le front-end la restylera : voir docs/front-end/devscore-classement.md.
--}}
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Classement africain</h2>
        <p class="text-sm text-gray-600">
            {{ $filters['country'] ? $countries->firstWhere('slug', $filters['country'])?->name : 'Afrique' }} · {{ $periodLabel }}
            @if ($myRank) · Votre rang : <strong>{{ $myRank }}e</strong> @endif
        </p>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto px-4 space-y-6">

            <form method="GET" action="{{ route('ranking.index') }}" x-data="{ period: @js($filters['period']) }" class="p-4 bg-white shadow rounded flex flex-wrap gap-3 items-end">
                <div>
                    <x-input-label for="country" value="Zone" />
                    <select id="country" name="country" class="border-gray-300 rounded">
                        <option value="">🌍 Afrique</option>
                        @foreach ($countries as $country)
                            <option value="{{ $country->slug }}" @selected($filters['country'] === $country->slug)>{{ $country->flag }} {{ $country->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <x-input-label for="period" value="Période" />
                    <select id="period" name="period" x-model="period" class="border-gray-300 rounded">
                        @foreach ($periods as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div x-show="period === 'month'">
                    <x-input-label for="month" value="Mois" />
                    <input id="month" type="month" name="month" value="{{ $filters['month'] }}" max="{{ now()->format('Y-m') }}" class="border-gray-300 rounded">
                </div>
                <div x-show="period === 'year'">
                    <x-input-label for="year" value="Année" />
                    <input id="year" type="number" name="year" value="{{ $filters['year'] }}" min="2020" max="{{ now()->year }}" class="border-gray-300 rounded w-28">
                </div>
                <x-primary-button>Afficher</x-primary-button>
            </form>

            <div class="bg-white shadow rounded divide-y">
                @forelse ($developers as $developer)
                    <a href="{{ route('devs.show', $developer) }}" class="flex items-center gap-4 p-3 hover:bg-gray-50">
                        <span class="w-10 text-right font-bold">{{ $firstRank + $loop->index }}.</span>
                        <span class="flex-1">
                            {{ $developer->name }}
                            <span class="text-sm text-gray-600">({{ $developer->country?->flag }} {{ $developer->country?->name }})</span>
                            <span class="text-sm">{{ $developer->badges->pluck('icon')->join(' ') }}</span>
                        </span>
                        <span class="font-semibold">{{ (int) $developer->points }} pts</span>
                    </a>
                @empty
                    <p class="p-4 text-gray-600">Personne n'a encore gagné de points sur cette période.</p>
                @endforelse
            </div>

            {{ $developers->links() }}
        </div>
    </div>
</x-app-layout>
