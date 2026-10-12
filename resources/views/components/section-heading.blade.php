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

{{-- `flex-wrap` : sur un téléphone, un titre long et une action longue ne
     tiennent pas sur une ligne — sans lui, l'action marquée `shrink-0`
     poussait la page et créait un débordement horizontal. --}}
<div {{ $attributes->merge(['class' => 'flex flex-wrap items-baseline justify-between gap-x-4 gap-y-1']) }}>
    <h2 class="section-title flex min-w-0 items-baseline gap-2">
        <span>{{ $title }}</span>

        @if (! is_null($count))
            <span class="tabular text-sm font-medium text-ink-400 dark:text-ink-500">{{ $count }}</span>
        @endif
    </h2>

    @if ($action)
        <div class="shrink-0">{{ $action }}</div>
    @endif
</div>
