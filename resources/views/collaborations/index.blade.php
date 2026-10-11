{{--
    Mes collaborations.

    Variables : $receivedInvitations, $receivedApplications,
    $sentApplications, $history. Voir docs/front-end/projets.md.
--}}
@php
    // Total des éléments qui demandent une action de ma part.
    $actionableCount = $receivedInvitations->count() + $receivedApplications->count();
@endphp

<x-app-layout title="Mes collaborations">
    <div class="mx-auto max-w-4xl px-4 py-8 sm:px-6 lg:px-8 lg:py-10">

        <x-page-header
            title="Mes collaborations"
            :subtitle="$actionableCount > 0
                ? $actionableCount.' '.($actionableCount > 1 ? 'demandes attendent' : 'demande attend').' votre réponse'
                : 'Aucune demande en attente de votre part'"
        >
            <x-slot name="actions">
                <a href="{{ route('projects.index') }}" class="btn-secondary btn-sm">
                    <x-icon name="search" class="h-4 w-4" />
                    Trouver un projet
                </a>
            </x-slot>
        </x-page-header>

        <x-flash class="mt-6" :error-keys="['collaboration', 'member']" />

        <div class="mt-6 space-y-6">

            {{-- ==========  Invitations reçues  ========== --}}
            <div class="panel">
                <x-section-heading title="Invitations reçues" :count="$receivedInvitations->count()" />

                @if ($receivedInvitations->isNotEmpty())
                    <ul class="mt-5 space-y-3">
                        @foreach ($receivedInvitations as $request)
                            <li class="rounded-lg border border-ink-200 p-4 dark:border-ink-800">
                                <div class="flex flex-wrap items-start justify-between gap-3">
                                    <div class="min-w-0">
                                        <a href="{{ route('projects.show', $request->project) }}"
                                           class="font-semibold text-ink-900 transition hover:text-brand-600 dark:text-white dark:hover:text-brand-400">
                                            {{ $request->project->title }}
                                        </a>
                                        <p class="mt-1 text-xs text-ink-500 dark:text-ink-400">
                                            Invitation de {{ $request->project->owner->name }}
                                        </p>
                                    </div>

                                    @if ($request->projectRole)
                                        <span class="chip shrink-0">{{ $request->projectRole->title }}</span>
                                    @endif
                                </div>

                                @if ($request->message)
                                    <p class="surface-muted mt-3 p-3 text-sm italic leading-relaxed text-ink-700 dark:text-ink-300">
                                        « {{ $request->message }} »
                                    </p>
                                @endif

                                <div class="mt-3 flex flex-wrap gap-2">
                                    <form method="POST" action="{{ route('collaborations.accept', $request) }}">
                                        @csrf
                                        <button type="submit" class="btn-primary btn-sm">
                                            <x-icon name="check" class="h-4 w-4" />
                                            Accepter
                                        </button>
                                    </form>
                                    <form method="POST" action="{{ route('collaborations.reject', $request) }}">
                                        @csrf
                                        <button type="submit" class="btn-secondary btn-sm">Refuser</button>
                                    </form>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @else
                    <p class="muted mt-4">Aucune invitation pour le moment.</p>
                @endif
            </div>

            {{-- ==========  Demandes reçues sur mes projets  ========== --}}
            <div class="panel">
                <x-section-heading title="Demandes reçues sur mes projets" :count="$receivedApplications->count()" />

                @if ($receivedApplications->isNotEmpty())
                    <ul class="mt-5 space-y-3">
                        @foreach ($receivedApplications as $request)
                            <li class="rounded-lg border border-ink-200 p-4 dark:border-ink-800">
                                <div class="flex flex-wrap items-start justify-between gap-3">
                                    <a href="{{ route('devs.show', $request->user) }}" class="group flex min-w-0 items-center gap-3">
                                        <x-avatar :user="$request->user" size="sm" />
                                        <div class="min-w-0">
                                            <p class="truncate text-sm font-semibold text-ink-900 group-hover:text-brand-600 dark:text-white dark:group-hover:text-brand-400">
                                                {{ $request->user->name }}
                                            </p>
                                            <p class="mt-0.5 truncate text-xs text-ink-500 dark:text-ink-400">
                                                veut rejoindre
                                                <span class="font-medium text-ink-700 dark:text-ink-300">{{ $request->project->title }}</span>
                                            </p>
                                        </div>
                                    </a>

                                    @if ($request->projectRole)
                                        <span class="chip shrink-0">{{ $request->projectRole->title }}</span>
                                    @endif
                                </div>

                                @if ($request->message)
                                    <p class="surface-muted mt-3 p-3 text-sm italic leading-relaxed text-ink-700 dark:text-ink-300">
                                        « {{ $request->message }} »
                                    </p>
                                @endif

                                <div class="mt-3 flex flex-wrap gap-2">
                                    <form method="POST" action="{{ route('collaborations.accept', $request) }}">
                                        @csrf
                                        <button type="submit" class="btn-primary btn-sm">
                                            <x-icon name="check" class="h-4 w-4" />
                                            Accepter
                                        </button>
                                    </form>
                                    <form method="POST" action="{{ route('collaborations.reject', $request) }}">
                                        @csrf
                                        <button type="submit" class="btn-secondary btn-sm">Refuser</button>
                                    </form>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @else
                    <p class="muted mt-4">Aucune demande en attente.</p>
                @endif
            </div>

            {{-- ==========  Mes demandes envoyées  ========== --}}
            <div class="panel">
                <x-section-heading title="Mes demandes envoyées" :count="$sentApplications->count()" />

                @if ($sentApplications->isNotEmpty())
                    <ul class="mt-5 space-y-2">
                        @foreach ($sentApplications as $request)
                            <li class="flex flex-wrap items-center justify-between gap-3 rounded-lg border border-ink-200 p-3.5 dark:border-ink-800">
                                <div class="min-w-0">
                                    <a href="{{ route('projects.show', $request->project) }}"
                                       class="truncate text-sm font-medium text-ink-900 transition hover:text-brand-600 dark:text-white dark:hover:text-brand-400">
                                        {{ $request->project->title }}
                                    </a>
                                    <p class="mt-0.5 text-xs text-ink-500 dark:text-ink-400">
                                        Envoyée {{ $request->created_at->diffForHumans() }}
                                    </p>
                                </div>

                                <div class="flex shrink-0 items-center gap-2">
                                    <span class="badge-warning">
                                        <x-icon name="clock" class="h-3 w-3" />
                                        En attente
                                    </span>

                                    <form method="POST" action="{{ route('collaborations.destroy', $request) }}">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn-ghost btn-xs">Annuler</button>
                                    </form>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @else
                    <p class="muted mt-4">Aucune demande en attente.</p>
                @endif
            </div>

            {{-- ==========  Historique  ========== --}}
            <div class="panel">
                <x-section-heading title="Historique" :count="$history->count()" />

                @if ($history->isNotEmpty())
                    <ul class="mt-5 divide-y divide-ink-200 dark:divide-ink-800">
                        @foreach ($history as $request)
                            @php
                                $isAccepted = $request->status === \App\Enums\RequestStatus::Accepted;
                            @endphp

                            <li class="flex flex-wrap items-center justify-between gap-3 py-3 first:pt-0 last:pb-0">
                                <div class="min-w-0 text-sm">
                                    <p class="truncate text-ink-800 dark:text-ink-200">
                                        <span class="font-medium">{{ $request->user->name }}</span>
                                        <span class="text-ink-400 dark:text-ink-600">→</span>
                                        <span class="font-medium">{{ $request->project->title }}</span>
                                    </p>
                                    <p class="mt-0.5 text-xs text-ink-500 dark:text-ink-400">
                                        {{ $request->direction->label() }}
                                        @if ($request->responded_at)
                                            <span class="text-ink-300 dark:text-ink-700">·</span>
                                            {{ $request->responded_at->diffForHumans() }}
                                        @endif
                                    </p>
                                </div>

                                <span class="shrink-0 {{ $isAccepted ? 'badge-success' : 'badge-neutral' }}">
                                    {{ $request->status->label() }}
                                </span>
                            </li>
                        @endforeach
                    </ul>
                @else
                    <p class="muted mt-4">Rien pour l'instant.</p>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
