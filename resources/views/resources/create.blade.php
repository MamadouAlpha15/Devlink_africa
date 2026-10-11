{{--
    Publier une ressource — version fonctionnelle minimale (backend).
    Le front-end la restylera : voir docs/front-end/ressources.md.
--}}
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Partager une ressource</h2>
    </x-slot>

    <div class="py-8">
        <form method="POST" action="{{ route('resources.store') }}" enctype="multipart/form-data" class="max-w-3xl mx-auto px-4 p-6 bg-white shadow rounded space-y-4">
            @csrf
            @include('resources.partials.form-fields', ['resource' => null])
            <x-primary-button>Publier</x-primary-button>
        </form>
    </div>
</x-app-layout>
