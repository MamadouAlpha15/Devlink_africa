{{-- Champs communs aux formulaires de création et de modification d'un projet. $project peut être null. --}}
@php($selectedSkills = collect(old('skills', $project?->skills->pluck('id')->all() ?? []))->map(fn ($id) => (int) $id))

<div>
    <x-input-label for="title" value="Titre du projet" />
    <x-text-input id="title" name="title" class="block w-full" :value="old('title', $project?->title)" placeholder="AfricaDelivery" required />
    <x-input-error :messages="$errors->get('title')" />
</div>

<div>
    <x-input-label for="description" value="Description" />
    <textarea id="description" name="description" rows="5" maxlength="5000" class="block w-full border-gray-300 rounded" required>{{ old('description', $project?->description) }}</textarea>
    <x-input-error :messages="$errors->get('description')" />
</div>

<div class="grid sm:grid-cols-2 gap-4">
    <div>
        <x-input-label for="category" value="Catégorie" />
        <select id="category" name="category" class="block w-full border-gray-300 rounded" required>
            @foreach ($categories as $category)
                <option value="{{ $category->value }}" @selected(old('category', $project?->category?->value) === $category->value)>{{ $category->label() }}</option>
            @endforeach
        </select>
        <x-input-error :messages="$errors->get('category')" />
    </div>
    <div>
        <x-input-label for="country_id" value="Pays" />
        <select id="country_id" name="country_id" class="block w-full border-gray-300 rounded">
            <option value="">Toute l'Afrique</option>
            @foreach ($countries as $country)
                <option value="{{ $country->id }}" @selected(old('country_id', $project?->country_id) == $country->id)>{{ $country->flag }} {{ $country->name }}</option>
            @endforeach
        </select>
        <x-input-error :messages="$errors->get('country_id')" />
    </div>
    <div>
        <x-input-label for="status" value="Statut" />
        <select id="status" name="status" class="block w-full border-gray-300 rounded" required>
            @foreach ($statuses as $status)
                <option value="{{ $status->value }}" @selected(old('status', $project?->status?->value ?? 'recruiting') === $status->value)>{{ $status->label() }}</option>
            @endforeach
        </select>
        <x-input-error :messages="$errors->get('status')" />
    </div>
    <div>
        <x-input-label for="members_wanted" value="Nombre de membres recherchés" />
        <x-text-input id="members_wanted" name="members_wanted" type="number" min="1" max="50" class="block w-full" :value="old('members_wanted', $project?->members_wanted ?? 1)" required />
        <x-input-error :messages="$errors->get('members_wanted')" />
    </div>
</div>

<div>
    <x-input-label for="skills" value="Technologies (Ctrl + clic pour en choisir plusieurs, 10 max)" />
    <select id="skills" name="skills[]" multiple size="8" class="block w-full border-gray-300 rounded">
        @foreach ($skillsByCategory as $category => $skills)
            <optgroup label="{{ $category }}">
                @foreach ($skills as $skill)
                    <option value="{{ $skill->id }}" @selected($selectedSkills->contains($skill->id))>{{ $skill->name }}</option>
                @endforeach
            </optgroup>
        @endforeach
    </select>
    <x-input-error :messages="$errors->get('skills')" />
</div>

<div>
    <x-input-label for="cover" value="Image du projet (facultatif, 2 Mo max)" />
    @if ($project?->coverUrl())
        <img src="{{ $project->coverUrl() }}" alt="" class="h-24 rounded my-2">
        <label class="text-sm"><input type="checkbox" name="remove_cover" value="1"> Supprimer l'image</label>
    @endif
    <input id="cover" type="file" name="cover" accept="image/jpeg,image/png,image/webp" class="block mt-1">
    <x-input-error :messages="$errors->get('cover')" />
</div>
