{{--
    Disponibilité d'un développeur (point + libellé).
    N'affiche rien si le développeur a choisi de la masquer.

    Le point ne clignote pas : un état stable ne doit pas bouger, surtout
    répété sur vingt cartes d'une même grille.

    Usage : <x-availability :user="$developer" />
--}}
@props([
    'user',
    'showLabel' => true,
])

@if ($user->show_availability && $user->availability)
    @php
        [$dotColor, $textClass] = match ($user->availability) {
            \App\Enums\Availability::Available => ['bg-emerald-500', 'text-emerald-700 dark:text-emerald-400'],
            \App\Enums\Availability::Open => ['bg-amber-500', 'text-ink-600 dark:text-ink-400'],
            \App\Enums\Availability::Busy => ['bg-ink-400', 'text-ink-500 dark:text-ink-500'],
        };
    @endphp

    <span {{ $attributes->merge(['class' => "inline-flex items-center gap-1.5 text-xs font-medium $textClass"]) }}>
        <span class="dot {{ $dotColor }}"></span>
        @if ($showLabel)
            {{ $user->availability->label() }}
        @endif
    </span>
@endif
