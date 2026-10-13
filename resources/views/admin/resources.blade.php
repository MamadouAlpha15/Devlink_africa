{{--
    Administration — ressources : valider, refuser, supprimer.

    Variables : $resources (paginé, avec user et reports_count), $status,
    $statuses. Voir docs/front-end/pages.md.

    Les ressources en attente arrivent en premier : c'est la file de travail
    de l'administrateur, pas un catalogue.
--}}
@php
    // Le statut porte une décision : sa couleur l'exprime.
    $badgeFor = fn ($case) => match ($case->value) {
        'approved' => 'badge-success',
        'rejected' => 'badge-danger',
        default => 'badge-warning',
    };
@endphp

<x-app-layout title="Administration — Ressources">
    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8 lg:py-10">

        <x-page-header
            title="Ressources"
            :subtitle="$resources->total().' '.($resources->total() > 1 ? 'ressources' : 'ressource').' publiées par la communauté'"
        />

        <div class="mt-8">
            @include('admin.partials.nav')
        </div>

        {{-- Filtre par statut --}}
        <div class="mt-6 flex flex-wrap gap-1.5">
            <a href="{{ route('admin.resources.index') }}" class="chip-button {{ $status === null ? 'chip-button-active' : '' }}">
                Toutes
            </a>

            @foreach ($statuses as $value)
                <a
                    href="{{ route('admin.resources.index', ['status' => $value->value]) }}"
                    class="chip-button {{ $status === $value->value ? 'chip-button-active' : '' }}"
                >
                    {{ $value->label() }}
                </a>
            @endforeach
        </div>

        @if ($resources->isNotEmpty())
            <div class="card mt-6 overflow-x-auto">
                <table class="w-full min-w-[52rem] text-sm">
                    <thead>
                        <tr class="border-b border-ink-200 dark:border-ink-800">
                            <th scope="col" class="caption px-4 py-3 text-left font-semibold">Ressource</th>
                            <th scope="col" class="caption px-3 py-3 text-left font-semibold">Statut</th>
                            <th scope="col" class="caption px-3 py-3 text-right font-semibold">Signalements</th>
                            <th scope="col" class="caption px-3 py-3 text-right font-semibold">Publiée</th>
                            <th scope="col" class="caption px-4 py-3 text-right font-semibold">Actions</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-ink-100 dark:divide-ink-800/70">
                        @foreach ($resources as $resource)
                            <tr>
                                <td class="px-4 py-3">
                                    <a href="{{ route('resources.show', $resource) }}" class="font-medium text-ink-900 hover:underline dark:text-white">
                                        {{ $resource->title }}
                                    </a>
                                    <p class="mt-0.5 truncate text-xs text-ink-500 dark:text-ink-400">
                                        {{ $resource->type->label() }}
                                        <span class="text-ink-300 dark:text-ink-700" aria-hidden="true">·</span>
                                        par {{ $resource->user->name }}
                                    </p>
                                </td>

                                <td class="px-3 py-3">
                                    <span class="{{ $badgeFor($resource->status) }}">{{ $resource->status->label() }}</span>
                                </td>

                                <td class="tabular px-3 py-3 text-right">
                                    @if ($resource->reports_count)
                                        <span class="badge-danger">{{ $resource->reports_count }}</span>
                                    @else
                                        <span class="text-ink-300 dark:text-ink-600">—</span>
                                    @endif
                                </td>

                                <td class="tabular px-3 py-3 text-right text-xs text-ink-500 dark:text-ink-400">
                                    {{ $resource->created_at->format('d/m/Y') }}
                                </td>

                                <td class="whitespace-nowrap px-4 py-3 text-right">
                                    <div class="flex items-center justify-end gap-1">
                                        @if ($resource->status !== \App\Enums\ResourceStatus::Approved)
                                            <form method="POST" action="{{ route('admin.resources.approve', $resource) }}">
                                                @csrf
                                                <button type="submit" class="btn-secondary btn-xs">
                                                    <x-icon name="check" class="h-3.5 w-3.5" />
                                                    Valider
                                                </button>
                                            </form>
                                        @endif

                                        @if ($resource->status !== \App\Enums\ResourceStatus::Rejected)
                                            <form method="POST" action="{{ route('admin.resources.reject', $resource) }}">
                                                @csrf
                                                <button type="submit" class="btn-ghost btn-xs">Refuser</button>
                                            </form>
                                        @endif

                                        <form method="POST" action="{{ route('admin.resources.destroy', $resource) }}" x-data="confirmAction">
                                            @csrf
                                            @method('DELETE')

                                            <button
                                                type="button"
                                                x-show="! isConfirming"
                                                @click="ask()"
                                                class="btn btn-ghost btn-square btn-xs text-ink-400 hover:text-red-600 dark:hover:text-red-400"
                                                aria-label="Supprimer la ressource {{ $resource->title }}"
                                            >
                                                <x-icon name="trash" class="h-3.5 w-3.5" />
                                            </button>

                                            <span x-show="isConfirming" x-cloak class="inline-flex items-center gap-1">
                                                <button type="submit" class="btn-danger btn-xs">Supprimer</button>
                                                <button type="button" @click="cancel()" class="btn-ghost btn-xs">Non</button>
                                            </span>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-6">{{ $resources->links() }}</div>
        @else
            <x-empty-state class="mt-6" icon="package" title="Aucune ressource">
                Aucune ressource ne correspond à ce filtre.

                @if ($status !== null)
                    <x-slot name="action">
                        <a href="{{ route('admin.resources.index') }}" class="btn-secondary btn-sm">Voir toutes les ressources</a>
                    </x-slot>
                @endif
            </x-empty-state>
        @endif
    </div>
</x-app-layout>
