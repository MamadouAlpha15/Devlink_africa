{{--
    Modifier un projet — version fonctionnelle minimale (backend).
    Le front-end la restylera : voir docs/front-end/projets.md.
--}}
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Modifier « {{ $project->title }} »</h2>
    </x-slot>

    <div class="py-8 max-w-3xl mx-auto px-4 space-y-6">
        <form method="POST" action="{{ route('projects.update', $project) }}" enctype="multipart/form-data" class="p-6 bg-white shadow rounded space-y-4">
            @csrf
            @method('PUT')

            @include('projects.partials.form-fields', ['project' => $project])

            <div class="flex gap-4 items-center">
                <x-primary-button>Enregistrer</x-primary-button>
                <a href="{{ route('projects.show', $project) }}" class="underline text-sm">Annuler</a>
            </div>
        </form>

        <form method="POST" action="{{ route('projects.destroy', $project) }}" onsubmit="return confirm('Supprimer définitivement ce projet ?')">
            @csrf
            @method('DELETE')
            <x-danger-button>Supprimer le projet</x-danger-button>
        </form>
    </div>
</x-app-layout>
