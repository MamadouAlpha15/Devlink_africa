{{--
    Modifier une ressource — version fonctionnelle minimale (backend).
    Le front-end la restylera : voir docs/front-end/ressources.md.
--}}
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Modifier « {{ $resource->title }} »</h2>
    </x-slot>

    <div class="py-8 max-w-3xl mx-auto px-4 space-y-6">
        <form method="POST" action="{{ route('resources.update', $resource) }}" enctype="multipart/form-data" class="p-6 bg-white shadow rounded space-y-4">
            @csrf
            @method('PUT')
            @include('resources.partials.form-fields', ['resource' => $resource])
            <div class="flex gap-4 items-center">
                <x-primary-button>Enregistrer</x-primary-button>
                <a href="{{ route('resources.show', $resource) }}" class="underline text-sm">Annuler</a>
            </div>
        </form>

        <form method="POST" action="{{ route('resources.destroy', $resource) }}" onsubmit="return confirm('Supprimer cette ressource ?')">
            @csrf
            @method('DELETE')
            <x-danger-button>Supprimer la ressource</x-danger-button>
        </form>
    </div>
</x-app-layout>
