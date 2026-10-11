{{--
    Message affiché quand une liste est vide.

    Pas d'icône enfermée dans un carré coloré : le vide se signale par le
    texte et par la bordure pointillée du bloc.

    Usage :
    <x-empty-state title="Aucun résultat">
        Essayez d'élargir votre recherche.
        <x-slot name="action"> <a class="btn-secondary btn-sm">Effacer</a> </x-slot>
    </x-empty-state>
--}}
@props([
    'icon' => null,
    'title',
    'action' => null,
])

<div {{ $attributes->merge(['class' => 'empty-state']) }}>
    @if ($icon)
        <x-icon :name="$icon" class="h-6 w-6 text-ink-300 dark:text-ink-600" />
    @endif

    <p class="mt-3 font-semibold text-ink-900 dark:text-white">{{ $title }}</p>

    @if (trim($slot) !== '')
        <p class="mt-2 max-w-sm text-sm leading-relaxed text-ink-600 dark:text-ink-400">{{ $slot }}</p>
    @endif

    @if ($action)
        <div class="mt-5">{{ $action }}</div>
    @endif
</div>
