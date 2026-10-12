{{--
    Administration — utilisateurs : voir, rechercher, suspendre, supprimer.

    Variables : $users (paginé, avec country, projects_count, resources_count),
    $q, $filter, $filters. Voir docs/front-end/administration.md.

    Un tableau, parce qu'on compare des lignes entre elles. Les actions
    destructrices demandent confirmation sur place, sans fenêtre du navigateur.
--}}
<x-app-layout title="Administration — Utilisateurs">
    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8 lg:py-10">

        <x-page-header
            title="Utilisateurs"
            :subtitle="$users->total().' '.($users->total() > 1 ? 'comptes' : 'compte').' sur la plateforme'"
        />

        <div class="mt-8">
            @include('admin.partials.nav')
        </div>

        <form method="GET" class="card mt-6 flex flex-wrap items-end gap-3 p-4">
            <div class="min-w-[14rem] flex-1">
                <label for="q" class="label">Rechercher</label>
                <div class="input-group">
                    <x-icon name="search" />
                    <input id="q" type="search" name="q" value="{{ $q }}" class="input" placeholder="Nom, email ou pseudo" autocomplete="off">
                </div>
            </div>

            <div class="w-full sm:w-52">
                <label for="filter" class="label">Afficher</label>
                <select id="filter" name="filter" class="select mt-1.5">
                    @foreach ($filters as $value => $label)
                        <option value="{{ $value }}" @selected($filter === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <button type="submit" class="btn-primary">Rechercher</button>

            @if ($q || $filter !== 'all')
                <a href="{{ route('admin.users.index') }}" class="btn-ghost">Effacer</a>
            @endif
        </form>

        @if ($users->isNotEmpty())
            <div class="card mt-6 overflow-x-auto">
                <table class="w-full min-w-[56rem] text-sm">
                    <thead>
                        <tr class="border-b border-ink-200 dark:border-ink-800">
                            <th scope="col" class="caption px-4 py-3 text-left font-semibold">Membre</th>
                            <th scope="col" class="caption px-3 py-3 text-left font-semibold">Pays</th>
                            <th scope="col" class="caption px-3 py-3 text-right font-semibold">Projets</th>
                            <th scope="col" class="caption px-3 py-3 text-right font-semibold">Ressources</th>
                            <th scope="col" class="caption px-3 py-3 text-right font-semibold">DevScore</th>
                            <th scope="col" class="caption px-3 py-3 text-right font-semibold">Inscrit</th>
                            <th scope="col" class="caption px-4 py-3 text-right font-semibold">Actions</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-ink-100 dark:divide-ink-800/70">
                        @foreach ($users as $member)
                            <tr class="{{ $member->isSuspended() ? 'bg-red-50/60 dark:bg-red-950/20' : '' }}">
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-3">
                                        <x-avatar :user="$member" size="sm" class="shrink-0" />

                                        <div class="min-w-0">
                                            <div class="flex flex-wrap items-center gap-1.5">
                                                <a href="{{ route('devs.show', $member) }}" class="font-medium text-ink-900 hover:underline dark:text-white">
                                                    {{ $member->name }}
                                                </a>

                                                @if ($member->is_admin)
                                                    <span class="badge-neutral">Admin</span>
                                                @endif

                                                @if ($member->isSuspended())
                                                    <span class="badge-danger">Suspendu</span>
                                                @endif

                                                @unless ($member->hasVerifiedEmail())
                                                    <span class="badge-warning">Email non vérifié</span>
                                                @endunless
                                            </div>

                                            <p class="mt-0.5 truncate text-xs text-ink-500 dark:text-ink-400">{{ $member->email }}</p>
                                        </div>
                                    </div>
                                </td>

                                <td class="px-3 py-3">
                                    <x-country-label :country="$member->country" class="text-xs" />
                                </td>

                                <td class="tabular px-3 py-3 text-right text-ink-700 dark:text-ink-300">{{ $member->projects_count }}</td>
                                <td class="tabular px-3 py-3 text-right text-ink-700 dark:text-ink-300">{{ $member->resources_count }}</td>
                                <td class="tabular px-3 py-3 text-right font-medium text-ink-900 dark:text-white">{{ $member->devscore }}</td>
                                <td class="tabular px-3 py-3 text-right text-xs text-ink-500 dark:text-ink-400">{{ $member->created_at->format('d/m/Y') }}</td>

                                <td class="whitespace-nowrap px-4 py-3 text-right">
                                    {{-- On ne peut ni suspendre ni supprimer un administrateur. --}}
                                    @unless ($member->is_admin)
                                        <div class="flex items-center justify-end gap-1">
                                            @if ($member->isSuspended())
                                                <form method="POST" action="{{ route('admin.users.unsuspend', $member) }}">
                                                    @csrf
                                                    <button type="submit" class="btn-ghost btn-xs">Réactiver</button>
                                                </form>
                                            @else
                                                <form method="POST" action="{{ route('admin.users.suspend', $member) }}" x-data="confirmAction">
                                                    @csrf

                                                    <button type="button" x-show="! isConfirming" @click="ask()" class="btn-ghost btn-xs">
                                                        Suspendre
                                                    </button>

                                                    <span x-show="isConfirming" x-cloak class="inline-flex items-center gap-1">
                                                        <button type="submit" class="btn-danger btn-xs">Confirmer</button>
                                                        <button type="button" @click="cancel()" class="btn-ghost btn-xs">Non</button>
                                                    </span>
                                                </form>
                                            @endif

                                            {{-- Suppression définitive du compte et de tout son contenu. --}}
                                            <form method="POST" action="{{ route('admin.users.destroy', $member) }}" x-data="confirmAction">
                                                @csrf
                                                @method('DELETE')

                                                <button
                                                    type="button"
                                                    x-show="! isConfirming"
                                                    @click="ask()"
                                                    class="btn btn-ghost btn-square btn-xs text-ink-400 hover:text-red-600 dark:hover:text-red-400"
                                                    aria-label="Supprimer le compte de {{ $member->name }}"
                                                >
                                                    <x-icon name="trash" class="h-3.5 w-3.5" />
                                                </button>

                                                <span x-show="isConfirming" x-cloak class="inline-flex items-center gap-1">
                                                    <button type="submit" class="btn-danger btn-xs">Supprimer</button>
                                                    <button type="button" @click="cancel()" class="btn-ghost btn-xs">Non</button>
                                                </span>
                                            </form>
                                        </div>
                                    @endunless
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-6">{{ $users->links() }}</div>
        @else
            <x-empty-state class="mt-6" icon="users" title="Aucun utilisateur">
                Aucun compte ne correspond à cette recherche.

                @if ($q || $filter !== 'all')
                    <x-slot name="action">
                        <a href="{{ route('admin.users.index') }}" class="btn-secondary btn-sm">Voir tous les comptes</a>
                    </x-slot>
                @endif
            </x-empty-state>
        @endif
    </div>
</x-app-layout>
