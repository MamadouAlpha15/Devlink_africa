{{--
    En-tête de page : fil d'Ariane facultatif, titre, sous-titre et actions.

    Usage :
    <x-page-header title="Trouver des développeurs" subtitle="128 profils">
        <x-slot name="actions"> <a class="btn-primary">…</a> </x-slot>
    </x-page-header>
--}}
@props([
    'title',
    'subtitle' => null,
    'backLink' => null,
    'backLabel' => 'Retour',
    'actions' => null,
])

<div {{ $attributes->merge(['class' => 'flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between']) }}>
    <div class="min-w-0">
        @if ($backLink)
            <a href="{{ $backLink }}" class="mb-2 inline-flex items-center gap-1.5 text-sm font-medium text-ink-500 transition hover:text-brand-600 dark:text-ink-400 dark:hover:text-brand-400">
                <x-icon name="arrow-left" class="h-4 w-4" />
                {{ $backLabel }}
            </a>
        @endif

        <h1 class="page-title">{{ $title }}</h1>

        @if ($subtitle)
            <p class="mt-1.5 text-sm text-ink-600 dark:text-ink-400">{{ $subtitle }}</p>
        @endif

        {{ $slot }}
    </div>

    @if ($actions)
        <div class="flex shrink-0 flex-wrap items-center gap-2">
            {{ $actions }}
        </div>
    @endif
</div>
