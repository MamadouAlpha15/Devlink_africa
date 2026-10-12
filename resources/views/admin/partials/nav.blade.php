{{--
    Sous-menu de l'administration, suivi des messages de retour.

    Des onglets soulignés plutôt que des pastilles : on navigue ici entre les
    cinq faces d'un même outil, pas entre des filtres. L'onglet actif est
    neutre — l'indigo reste réservé à l'action.
--}}
@php
    $adminTabs = [
        'admin.dashboard' => 'Statistiques',
        'admin.users.index' => 'Utilisateurs',
        'admin.resources.index' => 'Ressources',
        'admin.projects.index' => 'Projets',
        'admin.reports.index' => 'Signalements',
    ];
@endphp

<nav aria-label="Sections de l'administration" class="-mb-px flex gap-1 overflow-x-auto border-b border-ink-200 dark:border-ink-800">
    @foreach ($adminTabs as $route => $label)
        @php($isActive = request()->routeIs($route))

        <a
            href="{{ route($route) }}"
            @if ($isActive) aria-current="page" @endif
            class="shrink-0 whitespace-nowrap border-b-2 px-3 py-2.5 text-sm font-medium transition-colors {{ $isActive
                ? 'border-ink-900 text-ink-900 dark:border-white dark:text-white'
                : 'border-transparent text-ink-500 hover:border-ink-300 hover:text-ink-900 dark:text-ink-400 dark:hover:border-ink-600 dark:hover:text-white' }}"
        >
            {{ $label }}
        </a>
    @endforeach
</nav>

<x-flash class="mt-6" />

{{-- Les actions d'administration échouent rarement, mais quand elles échouent
     il faut dire pourquoi : refus de se suspendre soi-même, contenu déjà
     supprimé, droits insuffisants. --}}
@if ($errors->any())
    <div class="alert-danger mt-6">
        <x-icon name="alert-circle" class="mt-px h-4 w-4 shrink-0" />
        <p class="flex-1 font-medium">{{ $errors->first() }}</p>
    </div>
@endif
