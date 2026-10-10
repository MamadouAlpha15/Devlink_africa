{{--
    "Trouver des développeurs" — version fonctionnelle minimale (backend).
    Le front-end la restylera : voir docs/front-end/recherche.md pour les variables disponibles.
--}}
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Trouver des développeurs</h2>
        <p class="text-sm text-gray-600">{{ $developers->total() }} développeur(s) trouvé(s)</p>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto px-4 grid lg:grid-cols-4 gap-6">

            {{-- Filtres --}}
            <form method="GET" action="{{ route('devs.index') }}" class="p-4 bg-white shadow rounded space-y-3 lg:col-span-1 h-fit">
                <div>
                    <x-input-label for="q" value="Mot-clé" />
                    <x-text-input id="q" name="q" class="block w-full" :value="$filters['q']" placeholder="Laravel, Flutter, Conakry…" />
                </div>

                <div>
                    <x-input-label for="country" value="Pays" />
                    <select id="country" name="country" class="block w-full border-gray-300 rounded">
                        <option value="">Toute l'Afrique</option>
                        @foreach ($countries as $country)
                            <option value="{{ $country->slug }}" @selected($filters['country'] === $country->slug)>{{ $country->flag }} {{ $country->name }} ({{ $country->developers_count }})</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <x-input-label for="city" value="Ville" />
                    <x-text-input id="city" name="city" class="block w-full" :value="$filters['city']" />
                </div>

                <div>
                    <x-input-label for="skill" value="Compétence" />
                    <select id="skill" name="skill" class="block w-full border-gray-300 rounded">
                        <option value="">Toutes</option>
                        @foreach ($skillsByCategory as $category => $skills)
                            <optgroup label="{{ $category }}">
                                @foreach ($skills as $skill)
                                    <option value="{{ $skill->slug }}" @selected($filters['skill'] === $skill->slug)>{{ $skill->name }}</option>
                                @endforeach
                            </optgroup>
                        @endforeach
                    </select>
                </div>

                @foreach ([
                    'level' => ['Niveau', $experienceLevels],
                    'availability' => ['Disponibilité', $availabilities],
                    'type' => ['Type de profil', $profileTypes],
                ] as $name => [$label, $options])
                    <div>
                        <x-input-label :for="$name" :value="$label" />
                        <select id="{{ $name }}" name="{{ $name }}" class="block w-full border-gray-300 rounded">
                            <option value="">Tous</option>
                            @foreach ($options as $option)
                                <option value="{{ $option->value }}" @selected($filters[$name] === $option->value)>{{ $option->label() }}</option>
                            @endforeach
                        </select>
                    </div>
                @endforeach

                <div>
                    <x-input-label for="sort" value="Trier par" />
                    <select id="sort" name="sort" class="block w-full border-gray-300 rounded">
                        @foreach ($sorts as $value => $label)
                            <option value="{{ $value }}" @selected($filters['sort'] === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <x-primary-button class="w-full justify-center">Rechercher</x-primary-button>
                @if ($hasFilters)
                    <a href="{{ route('devs.index') }}" class="block text-center text-sm underline">Effacer les filtres</a>
                @endif
            </form>

            {{-- Résultats --}}
            <div class="lg:col-span-3 space-y-6">
                <div class="grid sm:grid-cols-2 xl:grid-cols-3 gap-4">
                    @forelse ($developers as $developer)
                        <a href="{{ route('devs.show', $developer) }}" class="p-4 bg-white shadow rounded block hover:shadow-md">
                            <div class="flex items-center gap-3">
                                @if ($developer->avatar_url)
                                    <img src="{{ $developer->avatar_url }}" alt="" class="h-12 w-12 rounded-full object-cover">
                                @else
                                    <div class="h-12 w-12 rounded-full bg-gray-200 flex items-center justify-center font-bold">{{ $developer->initials }}</div>
                                @endif
                                <div>
                                    <p class="font-semibold">{{ $developer->name }}</p>
                                    <p class="text-sm text-gray-600">{{ $developer->profile_type->label() }} · {{ $developer->country?->flag }} {{ $developer->city }}</p>
                                </div>
                            </div>
                            <p class="mt-2 text-sm">{{ $developer->skills->take(4)->pluck('name')->join(' · ') }}</p>
                            <p class="mt-2 text-sm text-gray-600">
                                {{ $developer->devscore }} pts
                                @if ($developer->show_availability) · {{ $developer->availability->label() }} @endif
                            </p>
                        </a>
                    @empty
                        <p class="text-gray-600">Aucun développeur ne correspond à votre recherche.</p>
                    @endforelse
                </div>

                {{ $developers->links() }}

                {{-- Développeurs par pays --}}
                <div class="p-4 bg-white shadow rounded">
                    <h3 class="font-semibold mb-2">Développeurs par pays</h3>
                    <ul class="grid sm:grid-cols-2 gap-1 text-sm">
                        @foreach ($topCountries as $country)
                            <li><a href="{{ route('devs.index', ['country' => $country->slug]) }}" class="underline">{{ $country->flag }} {{ $country->name }}</a> : {{ $country->developers_count }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
