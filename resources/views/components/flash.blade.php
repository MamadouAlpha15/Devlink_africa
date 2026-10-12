{{--
    Messages de session et erreurs générales, refermables.

    `session('status')` est renseigné par le backend après chaque action.
    `errorKeys` liste les clés d'erreurs métier à afficher ici
    (ex. collaboration, member, project_role_id).

    Usage : <x-flash :error-keys="['collaboration', 'member']" />
--}}
@props([
    'errorKeys' => [],
])

@php
    $businessErrors = collect($errorKeys)
        ->flatMap(fn ($key) => $errors->get($key))
        ->filter()
        ->values();
@endphp

{{-- « verification-link-sent » est un code technique : le bandeau de vérification l'affiche. --}}
@if (session('status') && session('status') !== 'verification-link-sent')
    <div x-data="dismissible" x-show="isVisible" x-transition.opacity class="alert-success">
        <x-icon name="check-circle" class="mt-px h-4 w-4 shrink-0" />
        <p class="flex-1 font-medium">{{ session('status') }}</p>
        <button type="button" @click="dismiss()" class="shrink-0 opacity-60 transition hover:opacity-100" aria-label="Fermer">
            <x-icon name="close" class="h-4 w-4" />
        </button>
    </div>
@endif

@foreach ($businessErrors as $message)
    <div x-data="dismissible(0)" x-show="isVisible" x-transition.opacity class="alert-danger">
        <x-icon name="alert-circle" class="mt-px h-4 w-4 shrink-0" />
        <p class="flex-1 font-medium">{{ $message }}</p>
        <button type="button" @click="dismiss()" class="shrink-0 opacity-60 transition hover:opacity-100" aria-label="Fermer">
            <x-icon name="close" class="h-4 w-4" />
        </button>
    </div>
@endforeach

{{ $slot }}
