{{--
    Statut d'un projet.

    Un point coloré et un libellé en casse normale : la couleur ne vit que
    dans le point, ce qui évite une pastille pleine de plus dans une
    interface qui en compte déjà beaucoup.

    Usage : <x-project-status :status="$project->status" />
--}}
@props(['status'])

@php
    $dotColor = match ($status) {
        \App\Enums\ProjectStatus::Recruiting => 'bg-emerald-500',
        \App\Enums\ProjectStatus::InProgress => 'bg-amber-500',
        \App\Enums\ProjectStatus::Completed => 'bg-brand-500',
        default => 'bg-ink-400',
    };
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-1.5 text-xs font-medium text-ink-600 dark:text-ink-400']) }}>
    <span class="dot {{ $dotColor }}"></span>
    {{ $status->label() }}
</span>
