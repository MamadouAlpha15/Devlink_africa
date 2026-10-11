{{--
    Créer un projet.

    Les postes recherchés ne sont définissables qu'à la création
    (`roles[i][title|skill_id|slots]`, 10 maximum). Les lignes laissées
    vides sont ignorées par le backend : on en affiche 3, l'utilisateur
    peut en ajouter jusqu'à 10.
--}}
@php
    $allSkills = $skillsByCategory->flatten()->sortBy('name')->values();

    // Nombre de lignes affichées au départ : 3, ou davantage si une
    // soumission précédente en contenait plus.
    $filledRoleRows = collect(old('roles', []))
        ->filter(fn ($role) => filled($role['title'] ?? null))
        ->count();

    $initialRoleRows = max(3, $filledRoleRows);
@endphp

<x-app-layout title="Créer un projet">
    <div class="mx-auto max-w-3xl px-4 py-8 sm:px-6 lg:px-8 lg:py-10">

        <x-page-header
            title="Créer un projet"
            subtitle="Décrivez votre projet et les profils qui vous manquent. Vous gagnez +50 points DevScore."
            :back-link="route('projects.index')"
            back-label="Tous les projets"
        />

        <form
            method="POST"
            action="{{ route('projects.store') }}"
            enctype="multipart/form-data"
            class="mt-8 space-y-6"
        >
            @csrf

            <div class="panel">
                <x-section-heading title="Le projet" />

                <div class="mt-5">
                    @include('projects.partials.form-fields', ['project' => null])
                </div>
            </div>

            {{-- Postes recherchés --}}
            <div class="panel" x-data="{ visibleRows: {{ $initialRoleRows }} }">
                <x-section-heading title="Profils recherchés">
                    <x-slot name="action">
                        <span class="subtle">facultatif</span>
                    </x-slot>
                </x-section-heading>

                <p class="muted mt-3">
                    Définissez les postes à pourvoir et le nombre de places. Ils apparaîtront
                    sur la fiche du projet et serviront à vous suggérer des développeurs.
                </p>

                <div class="mt-5 space-y-2.5">
                    {{-- En-têtes de colonnes (grand écran) --}}
                    <div class="hidden gap-2 px-1 sm:grid sm:grid-cols-[1fr_1fr_5rem]">
                        <span class="caption">Intitulé du poste</span>
                        <span class="caption">Compétence</span>
                        <span class="caption">Places</span>
                    </div>

                    @foreach (range(0, 9) as $index)
                        <div
                            class="grid gap-2 sm:grid-cols-[1fr_1fr_5rem]"
                            x-show="visibleRows > {{ $index }}"
                            @if ($index >= $initialRoleRows) x-cloak @endif
                        >
                            <input
                                type="text"
                                name="roles[{{ $index }}][title]"
                                value="{{ old('roles.'.$index.'.title') }}"
                                class="input h-10 text-sm"
                                placeholder="ex. Développeur Laravel"
                                aria-label="Intitulé du poste {{ $index + 1 }}"
                            >

                            <select
                                name="roles[{{ $index }}][skill_id]"
                                class="select h-10 text-sm"
                                aria-label="Compétence du poste {{ $index + 1 }}"
                            >
                                <option value="">Compétence</option>
                                @foreach ($allSkills as $skill)
                                    <option value="{{ $skill->id }}" @selected(old('roles.'.$index.'.skill_id') == $skill->id)>
                                        {{ $skill->name }}
                                    </option>
                                @endforeach
                            </select>

                            <input
                                type="number"
                                name="roles[{{ $index }}][slots]"
                                value="{{ old('roles.'.$index.'.slots', 1) }}"
                                min="1"
                                max="10"
                                class="input h-10 text-sm"
                                aria-label="Nombre de places du poste {{ $index + 1 }}"
                            >
                        </div>
                    @endforeach
                </div>

                <button
                    type="button"
                    @click="visibleRows = Math.min(visibleRows + 1, 10)"
                    x-show="visibleRows < 10"
                    class="btn-secondary btn-sm mt-3"
                >
                    <x-icon name="plus" class="h-4 w-4" />
                    Ajouter un poste
                </button>

                <x-input-error :messages="$errors->get('roles.*')" class="mt-3" />
            </div>

            <div class="flex flex-wrap items-center justify-end gap-3">
                <a href="{{ route('projects.index') }}" class="btn-ghost">Annuler</a>

                <button type="submit" class="btn-primary">
                    <x-icon name="rocket" class="h-4 w-4" />
                    Publier le projet
                </button>
            </div>
        </form>
    </div>
</x-app-layout>
