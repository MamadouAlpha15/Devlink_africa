{{--
    Dev Resources — version fonctionnelle minimale (backend).
    Le front-end la restylera : voir docs/front-end/ressources.md.
--}}
<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Dev Resources</h2>
                <p class="text-sm text-gray-600">{{ $resources->total() }} ressource(s)</p>
            </div>
            @auth
                <a href="{{ route('resources.create') }}" class="underline">+ Partager une ressource</a>
            @endauth
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto px-4 grid lg:grid-cols-4 gap-6">

            <form method="GET" action="{{ route('resources.index') }}" class="p-4 bg-white shadow rounded space-y-3 h-fit">
                <div>
                    <x-input-label for="q" value="Mot-clé" />
                    <x-text-input id="q" name="q" class="block w-full" :value="$filters['q']" placeholder="paiement, auth, ui kit…" />
                </div>
                <div>
                    <x-input-label for="type" value="Type" />
                    <select id="type" name="type" class="block w-full border-gray-300 rounded">
                        <option value="">Tous</option>
                        @foreach ($types as $type)
                            <option value="{{ $type->value }}" @selected($filters['type'] === $type->value)>{{ $type->label() }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <x-input-label for="skill" value="Technologie" />
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
                <div>
                    <x-input-label for="country" value="Pays" />
                    <select id="country" name="country" class="block w-full border-gray-300 rounded">
                        <option value="">Toute l'Afrique</option>
                        @foreach ($countries as $country)
                            <option value="{{ $country->slug }}" @selected($filters['country'] === $country->slug)>{{ $country->flag }} {{ $country->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <x-input-label for="sort" value="Trier par" />
                    <select id="sort" name="sort" class="block w-full border-gray-300 rounded">
                        @foreach ($sorts as $value => $label)
                            <option value="{{ $value }}" @selected($filters['sort'] === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                @if ($filters['tag'])
                    <input type="hidden" name="tag" value="{{ $filters['tag'] }}">
                    <p class="text-sm">Tag : #{{ $filters['tag'] }}</p>
                @endif
                <x-primary-button class="w-full justify-center">Rechercher</x-primary-button>
                @if ($hasFilters)
                    <a href="{{ route('resources.index') }}" class="block text-center text-sm underline">Effacer les filtres</a>
                @endif

                @if ($popularTags->isNotEmpty())
                    <div class="pt-2 border-t">
                        <p class="text-sm font-semibold mb-1">Tags populaires</p>
                        <div class="flex flex-wrap gap-1">
                            @foreach ($popularTags as $tag)
                                <a href="{{ route('resources.index', ['tag' => $tag->slug]) }}" class="text-xs px-2 py-1 bg-gray-100 rounded">#{{ $tag->name }} ({{ $tag->resources_count }})</a>
                            @endforeach
                        </div>
                    </div>
                @endif
            </form>

            <div class="lg:col-span-3 space-y-6">
                <div class="grid md:grid-cols-2 xl:grid-cols-3 gap-4">
                    @forelse ($resources as $resource)
                        <a href="{{ route('resources.show', $resource) }}" class="p-4 bg-white shadow rounded block hover:shadow-md">
                            @if ($resource->imageUrl())
                                <img src="{{ $resource->imageUrl() }}" alt="" class="h-32 w-full object-cover rounded mb-2">
                            @endif
                            <p class="font-semibold">{{ $resource->title }}</p>
                            <p class="text-sm text-gray-600">{{ $resource->type->label() }} · par {{ $resource->user->name }} @if ($resource->country) · {{ $resource->country->flag }} @endif</p>
                            <p class="mt-2 text-sm">{{ Str::limit($resource->description, 110) }}</p>
                            <p class="mt-2 text-sm">{{ $resource->skills->pluck('name')->join(' · ') }}</p>
                            <p class="mt-2 text-sm text-gray-600">
                                ♥ {{ $resource->likes_count }}
                                · ★ {{ $resource->rating_avg ? number_format($resource->rating_avg, 1, ',', ' ') : '—' }}
                                · ⬇ {{ $resource->downloads_count }}
                            </p>
                        </a>
                    @empty
                        <p class="text-gray-600">Aucune ressource ne correspond à votre recherche.</p>
                    @endforelse
                </div>

                {{ $resources->links() }}
            </div>
        </div>
    </div>
</x-app-layout>
