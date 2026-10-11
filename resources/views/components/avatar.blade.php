{{--
    Photo de profil d'un développeur, avec repli sur ses initiales.

    Usage : <x-avatar :user="$developer" size="lg" />
--}}
@props([
    'user',
    'size' => 'md',
    'ring' => false,
])

@php
    // Diamètre et taille de police pour chaque gabarit.
    [$box, $font] = match ($size) {
        'xs' => ['h-7 w-7', 'text-xs'],
        'sm' => ['h-9 w-9', 'text-xs'],
        'md' => ['h-12 w-12', 'text-sm'],
        'lg' => ['h-16 w-16', 'text-lg'],
        'xl' => ['h-20 w-20', 'text-2xl'],
        '2xl' => ['h-24 w-24 sm:h-28 sm:w-28', 'text-3xl'],
        default => ['h-12 w-12', 'text-sm'],
    };

    $ringClasses = $ring
        ? 'ring-2 ring-white dark:ring-ink-900'
        : '';
@endphp

@if ($user->avatar_url)
    <img
        src="{{ $user->avatar_url }}"
        alt="Photo de {{ $user->name }}"
        loading="lazy"
        {{ $attributes->merge(['class' => "$box $ringClasses shrink-0 rounded-full border border-ink-200 object-cover dark:border-ink-800"]) }}
    >
@else
    <span
        {{ $attributes->merge(['class' => "avatar-fallback $box $font $ringClasses"]) }}
        aria-label="{{ $user->name }}"
    >{{ $user->initials }}</span>
@endif
