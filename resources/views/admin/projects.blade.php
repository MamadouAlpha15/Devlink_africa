{{--
    Administration — projets : consulter et supprimer.

    Variables : $projects (paginé, avec owner, memberships_count,
    reports_count) et $q. Voir docs/front-end/administration.md.

    Supprimer un projet annule aussi les demandes en attente : la confirmation
    se fait donc en deux temps, sur place.
--}}
<x-app-layout title="Administration — Projets">
    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8 lg:py-10">

        <x-page-header
            title="Projets"
            :subtitle="$projects->total().' '.($projects->total() > 1 ? 'projets collaboratifs' : 'projet collaboratif')"
        />

        <div class="mt-8">
            @include('admin.partials.nav')
        </div>

        <form method="GET" class="card mt-6 flex flex-wrap items-end gap-3 p-4">
            <div class="min-w-[14rem] flex-1">
                <label for="q" class="label">Rechercher</label>
                <div class="input-group">
                    <x-icon name="search" />
                    <input id="q" type="search" name="q" value="{{ $q }}" class="input" placeholder="Titre du projet" autocomplete="off">
                </div>
            </div>

            <button type="submit" class="btn-primary">Rechercher</button>

            @if ($q)
                <a href="{{ route('admin.projects.index') }}" class="btn-ghost">Effacer</a>
            @endif
        </form>

        @if ($projects->isNotEmpty())
            <div class="card mt-6 overflow-x-auto">
                <table class="w-full min-w-[52rem] text-sm">
                    <thead>
                        <tr class="border-b border-ink-200 dark:border-ink-800">
                            <th scope="col" class="caption px-4 py-3 text-left font-semibold">Projet</th>
                            <th scope="col" class="caption px-3 py-3 text-left font-semibold">Statut</th>
                            <th scope="col" class="caption px-3 py-3 text-right font-semibold">Membres</th>
                            <th scope="col" class="caption px-3 py-3 text-right font-semibold">Signalements</th>
                            <th scope="col" class="caption px-3 py-3 text-right font-semibold">Créé</th>
                            <th scope="col" class="caption px-4 py-3 text-right font-semibold">Actions</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-ink-100 dark:divide-ink-800/70">
                        @foreach ($projects as $project)
                            <tr>
                                <td class="px-4 py-3">
                                    <a href="{{ route('projects.show', $project) }}" class="font-medium text-ink-900 hover:underline dark:text-white">
                                        {{ $project->title }}
                                    </a>
                                    <p class="mt-0.5 truncate text-xs text-ink-500 dark:text-ink-400">
                                        {{ $project->category->label() }}
                                        <span class="text-ink-300 dark:text-ink-700" aria-hidden="true">·</span>
                                        par {{ $project->owner->name }}
                                    </p>
                                </td>

                                <td class="px-3 py-3">
                                    <x-project-status :status="$project->status" />
                                </td>

                                <td class="tabular px-3 py-3 text-right text-ink-700 dark:text-ink-300">{{ $project->memberships_count }}</td>

                                <td class="tabular px-3 py-3 text-right">
                                    @if ($project->reports_count)
                                        <span class="badge-danger">{{ $project->reports_count }}</span>
                                    @else
                                        <span class="text-ink-300 dark:text-ink-600">—</span>
                                    @endif
                                </td>

                                <td class="tabular px-3 py-3 text-right text-xs text-ink-500 dark:text-ink-400">
                                    {{ $project->created_at->format('d/m/Y') }}
                                </td>

                                <td class="whitespace-nowrap px-4 py-3 text-right">
                                    <form method="POST" action="{{ route('admin.projects.destroy', $project) }}" x-data="confirmAction">
                                        @csrf
                                        @method('DELETE')

                                        <button
                                            type="button"
                                            x-show="! isConfirming"
                                            @click="ask()"
                                            class="btn btn-ghost btn-square btn-xs text-ink-400 hover:text-red-600 dark:hover:text-red-400"
                                            aria-label="Supprimer le projet {{ $project->title }}"
                                        >
                                            <x-icon name="trash" class="h-3.5 w-3.5" />
                                        </button>

                                        <span x-show="isConfirming" x-cloak class="inline-flex items-center gap-1">
                                            <button type="submit" class="btn-danger btn-xs">Supprimer</button>
                                            <button type="button" @click="cancel()" class="btn-ghost btn-xs">Non</button>
                                        </span>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-6">{{ $projects->links() }}</div>
        @else
            <x-empty-state class="mt-6" icon="folder" title="Aucun projet">
                Aucun projet ne correspond à cette recherche.

                @if ($q)
                    <x-slot name="action">
                        <a href="{{ route('admin.projects.index') }}" class="btn-secondary btn-sm">Voir tous les projets</a>
                    </x-slot>
                @endif
            </x-empty-state>
        @endif
    </div>
</x-app-layout>
