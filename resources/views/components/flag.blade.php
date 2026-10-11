{{--
    Drapeau de pays, en SVG vectoriel.

    Les emoji drapeaux (paires d'indicateurs régionaux) ne sont pas rendus
    par Windows : Chrome et Edge y affichent les deux lettres du code. On
    sert donc de vrais SVG depuis public/flags/, générés une fois pour les
    54 pays d'Afrique à partir de flag-icons (licence MIT).

    Le drapeau est décoratif : le nom du pays l'accompagne toujours en
    texte, donc l'image est masquée aux lecteurs d'écran.

    Usage : <x-flag :country="$user->country" />
            <x-flag code="GN" size="md" />
--}}
@props([
    'country' => null,
    'code' => null,
    'size' => 'sm',
])

@php
    $isoCode = strtolower($code ?? $country?->code ?? '');

    // Rapport 4/3 exact, pour que le drapeau ne soit jamais déformé.
    $dimensions = match ($size) {
        'md' => 'h-[0.9375rem] w-5',
        default => 'h-3 w-4',
    };
@endphp

@if ($isoCode !== '')
    <img
        src="{{ asset('flags/'.$isoCode.'.svg') }}"
        alt=""
        aria-hidden="true"
        loading="lazy"
        width="20"
        height="15"
        {{ $attributes->merge(['class' => "flag $dimensions"]) }}
    >
@endif
