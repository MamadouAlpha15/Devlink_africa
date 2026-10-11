{{-- Sous-menu de l'administration. --}}
<nav class="flex flex-wrap gap-4 text-sm mb-6">
    @foreach ([
        'admin.dashboard' => 'Statistiques',
        'admin.users.index' => 'Utilisateurs',
        'admin.resources.index' => 'Ressources',
        'admin.projects.index' => 'Projets',
        'admin.reports.index' => 'Signalements',
    ] as $route => $label)
        <a href="{{ route($route) }}" class="{{ request()->routeIs($route) ? 'font-bold underline' : 'text-gray-600' }}">{{ $label }}</a>
    @endforeach
</nav>

@if (session('status'))
    <div class="p-4 mb-4 bg-green-50 text-green-800 rounded">{{ session('status') }}</div>
@endif
@if ($errors->any())
    <div class="p-4 mb-4 bg-red-50 text-red-800 rounded">{{ $errors->first() }}</div>
@endif
