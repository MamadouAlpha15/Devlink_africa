{{--
    Administration — signalements : consulter et traiter.

    Variables : $reports (paginé), $status, $statuses, $actions.
    Voir docs/front-end/pages.md.

    Des cartes et non un tableau : chaque signalement porte un texte libre de
    longueur variable et trois décisions possibles, ce qu'une ligne de tableau
    rendrait illisible.
--}}
@php
    $reportController = \App\Http\Controllers\Admin\ReportController::class;

    $badgeFor = fn ($case) => match ($case->value) {
        'resolved' => 'badge-success',
        'dismissed' => 'badge-neutral',
        default => 'badge-warning',
    };
@endphp

<x-app-layout title="Administration — Signalements">
    <div class="mx-auto max-w-4xl px-4 py-8 sm:px-6 lg:px-8 lg:py-10">

        <x-page-header
            title="Signalements"
            subtitle="Ce que les membres ont remonté, et ce que vous en avez fait."
        />

        <div class="mt-8">
            @include('admin.partials.nav')
        </div>

        <div class="mt-6 flex flex-wrap gap-1.5">
            @foreach ($statuses as $value)
                <a
                    href="{{ route('admin.reports.index', ['status' => $value->value]) }}"
                    class="chip-button {{ $status === $value ? 'chip-button-active' : '' }}"
                >
                    {{ $value->label() }}
                </a>
            @endforeach
        </div>

        @if ($reports->isNotEmpty())
            <ul class="mt-6 space-y-3">
                @foreach ($reports as $report)
                    @php($isPending = $report->status === \App\Enums\ReportStatus::Pending)

                    <li class="card p-4 sm:p-5">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="{{ $badgeFor($report->status) }}">{{ $report->reason->label() }}</span>

                                    @if ($link = $reportController::linkFor($report))
                                        <a href="{{ $link }}" class="link text-sm font-medium">
                                            {{ $reportController::describe($report) }}
                                        </a>
                                    @else
                                        <span class="text-sm text-ink-500 dark:text-ink-400">
                                            {{ $reportController::describe($report) }}
                                        </span>
                                    @endif
                                </div>

                                @if ($report->details)
                                    {{-- Les mots du membre, cités tels quels. --}}
                                    <blockquote class="mt-3 border-s-2 border-ink-200 ps-3 text-sm leading-relaxed text-ink-700 dark:border-ink-700 dark:text-ink-300">
                                        {{ $report->details }}
                                    </blockquote>
                                @endif

                                <p class="muted mt-3">
                                    Signalé par {{ $report->reporter?->name ?? 'un membre supprimé' }}
                                    <span class="text-ink-300 dark:text-ink-700" aria-hidden="true">·</span>
                                    {{ $report->created_at->diffForHumans() }}
                                </p>
                            </div>
                        </div>

                        @if ($isPending)
                            {{--
                                Trois décisions. « Supprimer le contenu » est la
                                seule irréversible : elle demande confirmation.
                                Le bouton reste un vrai `submit` pour continuer
                                de fonctionner sans JavaScript — Alpine se
                                contente d'intercepter le premier clic.
                            --}}
                            <form
                                method="POST"
                                action="{{ route('admin.reports.handle', $report) }}"
                                x-data="{ confirming: false }"
                                class="mt-4 border-t border-ink-200 pt-4 dark:border-ink-800"
                            >
                                @csrf

                                <div x-show="! confirming" class="flex flex-wrap items-center gap-2">
                                    @foreach ($actions as $action => $label)
                                        @if ($action === 'remove')
                                            <button
                                                type="submit"
                                                name="action"
                                                value="remove"
                                                @click.prevent="confirming = true"
                                                class="btn-danger-ghost btn-sm"
                                            >
                                                <x-icon name="trash" class="h-4 w-4" />
                                                {{ $label }}
                                            </button>
                                        @else
                                            <button type="submit" name="action" value="{{ $action }}" class="btn-secondary btn-sm">
                                                {{ $label }}
                                            </button>
                                        @endif
                                    @endforeach
                                </div>

                                <div x-show="confirming" x-cloak class="flex flex-wrap items-center gap-2">
                                    <p class="flex-1 text-sm text-ink-700 dark:text-ink-300">
                                        Supprimer le contenu signalé, ou suspendre le compte s'il s'agit d'un profil ?
                                    </p>

                                    <button type="submit" name="action" value="remove" class="btn-danger btn-sm">Confirmer</button>
                                    <button type="button" @click="confirming = false" class="btn-ghost btn-sm">Annuler</button>
                                </div>
                            </form>
                        @else
                            <p class="muted mt-4 border-t border-ink-200 pt-4 dark:border-ink-800">
                                {{ $report->status->label() }}
                                @if ($report->handler)
                                    par {{ $report->handler->name }}
                                @endif
                                @if ($report->handled_at)
                                    <span class="text-ink-300 dark:text-ink-700" aria-hidden="true">·</span>
                                    {{ $report->handled_at->diffForHumans() }}
                                @endif
                            </p>
                        @endif
                    </li>
                @endforeach
            </ul>

            <div class="mt-6">{{ $reports->links() }}</div>
        @else
            <x-empty-state class="mt-6" icon="shield" title="Aucun signalement">
                Rien à traiter pour ce filtre. Les signalements des membres apparaîtront ici.
            </x-empty-state>
        @endif
    </div>
</x-app-layout>
