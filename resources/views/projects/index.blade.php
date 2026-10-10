{{--
    Liste des projets — version fonctionnelle minimale (backend).
    Le front-end la restylera : voir docs/front-end/projets.md.
--}}
<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Projets collaboratifs</h2>
                <p class="text-sm text-gray-600">{{ $projects->total() }} projet(s)</p>
            </div>
            @auth
                <a href="{{ route('projects.create') }}" class="underline">+ Créer un projet</a>
            @endauth
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto px-4 grid lg:grid-cols-4 gap-6">

            <form method="GET" action="{{ route('projects.index') }}" class="p-4 bg-white shadow rounded space-y-3 h-fit">
                <div>
                    <x-input-label for="q" value="Mot-clé" />
                    <x-text-input id="q" name="q" class="block w-full" :value="$filters['q']" placeholder="livraison, fintech, Flutter…" />
                </div>
                <div>
                    <x-input-label for="category" value="Catégorie" />
                    <select id="category" name="category" class="block w-full border-gray-300 rounded">
                        <option value="">Toutes</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->value }}" @selected($filters['category'] === $category->value)>{{ $category->label() }}</option>
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
                    <x-input-label for="status" value="Statut" />
                    <select id="status" name="status" class="block w-full border-gray-300 rounded">
                        <option value="">Tous (hors archivés)</option>
                        @foreach ($statuses as $status)
                            <option value="{{ $status->value }}" @selected($filters['status'] === $status->value)>{{ $status->label() }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <x-input-label for="skill" value="Technologie / profil recherché" />
                    <select id="skill" name="skill" class="block w-full border-gray-300 rounded">
                        <option value="">Toutes</option>
                        @foreach ($skillsByCategory as $group => $skills)
                            <optgroup label="{{ $group }}">
                                @foreach ($skills as $skill)
                                    <option value="{{ $skill->slug }}" @selected($filters['skill'] === $skill->slug)>{{ $skill->name }}</option>
                                @endforeach
                            </optgroup>
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
                <x-primary-button class="w-full justify-center">Rechercher</x-primary-button>
                @if ($hasFilters)
                    <a href="{{ route('projects.index') }}" class="block text-center text-sm underline">Effacer les filtres</a>
                @endif
            </form>

            <div class="lg:col-span-3 space-y-6">
                <div class="grid md:grid-cols-2 gap-4">
                    @forelse ($projects as $project)
                        <a href="{{ route('projects.show', $project) }}" class="p-4 bg-white shadow rounded block hover:shadow-md">
                            <p class="font-semibold">{{ $project->title }}</p>
                            <p class="text-sm text-gray-600">
                                {{ $project->category->label() }} · {{ $project->status->label() }}
                                @if ($project->country) · {{ $project->country->flag }} {{ $project->country->name }} @endif
                            </p>
                            <p class="mt-2 text-sm">{{ Str::limit($project->description, 140) }}</p>
                            <p class="mt-2 text-sm">{{ $project->skills->pluck('name')->join(' · ') }}</p>
                            @php($openRoles = $project->roles->reject->isFull())
                            @if ($openRoles->isNotEmpty())
                                <p class="mt-2 text-sm text-green-700">Recherche : {{ $openRoles->map(fn ($role) => $role->title.' ×'.$role->openSlots())->join(', ') }}</p>
                            @endif
                            <p class="mt-2 text-sm text-gray-600">{{ $project->memberships_count }} membre(s) · par {{ $project->owner->name }}</p>
                        </a>
                    @empty
                        <p class="text-gray-600">Aucun projet ne correspond à votre recherche.</p>
                    @endforelse
                </div>

                {{ $projects->links() }}
            </div>
        </div>
    </div>
</x-app-layout>
