{{--
    Titre d'une section à l'intérieur d'une carte.

    Pas d'icône dans un carré coloré : un titre de section se tient par sa
    typographie. Le compteur reste, c'est une information.

    Usage : <x-section-heading title="Équipe" :count="4" />
--}}
@props([
    'title',
    'count' => null,
    'action' => null,
])

<div {{ $attributes->merge(['class' => 'flex items-baseline justify-between gap-4']) }}>
    <h2 class="section-title flex items-baseline gap-2">
        <span>{{ $title }}</span>

        @if (! is_null($count))
            <span class="tabular text-sm font-medium text-ink-400 dark:text-ink-500">{{ $count }}</span>
        @endif
    </h2>

    @if ($action)
        <div class="shrink-0">{{ $action }}</div>
    @endif
</div>
