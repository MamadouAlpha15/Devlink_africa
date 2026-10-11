{{--
    Mes favoris — version fonctionnelle minimale (backend).
    Le front-end la restylera : voir docs/front-end/favoris.md.
--}}
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Mes favoris</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-5xl mx-auto px-4 space-y-6">
            @if (session('status'))
                <div class="p-4 bg-green-50 text-green-800 rounded">{{ session('status') }}</div>
            @endif

            <div class="p-6 bg-white shadow rounded space-y-2">
                <h3 class="font-semibold">Développeurs ({{ $developers->count() }})</h3>
                @forelse ($developers as $developer)
                    <div class="flex items-center justify-between gap-2">
                        <a href="{{ route('devs.show', $developer) }}" class="underline">{{ $developer->name }}</a>
                        <span class="text-sm text-gray-600 flex-1">{{ $developer->country?->flag }} {{ $developer->skills->take(3)->pluck('name')->join(' · ') }}</span>
                        @include('partials.favorite-button', ['type' => 'developer', 'model' => $developer])
                    </div>
                @empty
                    <p class="text-sm text-gray-600">Aucun développeur sauvegardé. <a href="{{ route('devs.index') }}" class="underline">Trouver des développeurs</a></p>
                @endforelse
            </div>

            <div class="p-6 bg-white shadow rounded space-y-2">
                <h3 class="font-semibold">Ressources ({{ $resources->count() }})</h3>
                @forelse ($resources as $resource)
                    <div class="flex items-center justify-between gap-2">
                        <a href="{{ route('resources.show', $resource) }}" class="underline">{{ $resource->title }}</a>
                        <span class="text-sm text-gray-600 flex-1">{{ $resource->type->label() }} · par {{ $resource->user->name }}</span>
                        @include('partials.favorite-button', ['type' => 'resource', 'model' => $resource])
                    </div>
                @empty
                    <p class="text-sm text-gray-600">Aucune ressource sauvegardée. <a href="{{ route('resources.index') }}" class="underline">Parcourir les ressources</a></p>
                @endforelse
            </div>

            <div class="p-6 bg-white shadow rounded space-y-2">
                <h3 class="font-semibold">Projets ({{ $projects->count() }})</h3>
                @forelse ($projects as $project)
                    <div class="flex items-center justify-between gap-2">
                        <a href="{{ route('projects.show', $project) }}" class="underline">{{ $project->title }}</a>
                        <span class="text-sm text-gray-600 flex-1">{{ $project->status->label() }} · {{ $project->country?->flag }} · par {{ $project->owner->name }}</span>
                        @include('partials.favorite-button', ['type' => 'project', 'model' => $project])
                    </div>
                @empty
                    <p class="text-sm text-gray-600">Aucun projet sauvegardé. <a href="{{ route('projects.index') }}" class="underline">Voir les projets</a></p>
                @endforelse
            </div>
        </div>
    </div>
</x-app-layout>
