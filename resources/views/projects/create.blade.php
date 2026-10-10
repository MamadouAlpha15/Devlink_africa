{{--
    Créer un projet — version fonctionnelle minimale (backend).
    Le front-end la restylera : voir docs/front-end/projets.md.
--}}
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Créer un projet</h2>
    </x-slot>

    <div class="py-8">
        <form method="POST" action="{{ route('projects.store') }}" enctype="multipart/form-data" class="max-w-3xl mx-auto px-4 p-6 bg-white shadow rounded space-y-4">
            @csrf

            @include('projects.partials.form-fields', ['project' => null])

            {{-- Recruter une équipe : jusqu'à 10 postes, les lignes vides sont ignorées --}}
            <h3 class="font-semibold pt-2">Profils recherchés</h3>
            @php($skills = $skillsByCategory->flatten()->sortBy('name'))
            @foreach (range(0, 2) as $i)
                <div class="grid grid-cols-6 gap-2">
                    <x-text-input name="roles[{{ $i }}][title]" class="col-span-3" :value="old('roles.'.$i.'.title')" placeholder="ex. Laravel Developer" />
                    <select name="roles[{{ $i }}][skill_id]" class="col-span-2 border-gray-300 rounded">
                        <option value="">Compétence</option>
                        @foreach ($skills as $skill)
                            <option value="{{ $skill->id }}" @selected(old('roles.'.$i.'.skill_id') == $skill->id)>{{ $skill->name }}</option>
                        @endforeach
                    </select>
                    <x-text-input name="roles[{{ $i }}][slots]" type="number" min="1" max="10" :value="old('roles.'.$i.'.slots', 1)" />
                </div>
            @endforeach
            <x-input-error :messages="$errors->get('roles.*')" />

            <x-primary-button>Créer le projet</x-primary-button>
        </form>
    </div>
</x-app-layout>
