{{-- Champs communs aux formulaires de publication et de modification d'une ressource. $resource peut être null. --}}
@php
    $selectedSkills = collect(old('skills', $resource?->skills->pluck('id')->all() ?? []))->map(fn ($id) => (int) $id);
    $oldTags = old('tags', $resource?->tags->pluck('name')->all() ?? []);
    $tagsValue = is_array($oldTags) ? implode(', ', $oldTags) : $oldTags;
@endphp

<div>
    <x-input-label for="title" value="Titre" />
    <x-text-input id="title" name="title" class="block w-full" :value="old('title', $resource?->title)" placeholder="Orange Money pour Laravel" required />
    <x-input-error :messages="$errors->get('title')" />
</div>

<div>
    <x-input-label for="description" value="Description" />
    <textarea id="description" name="description" rows="5" maxlength="5000" class="block w-full border-gray-300 rounded" required>{{ old('description', $resource?->description) }}</textarea>
    <x-input-error :messages="$errors->get('description')" />
</div>

<div class="grid sm:grid-cols-2 gap-4">
    <div>
        <x-input-label for="type" value="Type (catégorie)" />
        <select id="type" name="type" class="block w-full border-gray-300 rounded" required>
            @foreach ($types as $type)
                <option value="{{ $type->value }}" @selected(old('type', $resource?->type?->value) === $type->value)>{{ $type->label() }}</option>
            @endforeach
        </select>
        <x-input-error :messages="$errors->get('type')" />
    </div>
    <div>
        <x-input-label for="country_id" value="Pays" />
        <select id="country_id" name="country_id" class="block w-full border-gray-300 rounded">
            <option value="">Toute l'Afrique</option>
            @foreach ($countries as $country)
                <option value="{{ $country->id }}" @selected(old('country_id', $resource?->country_id ?? auth()->user()->country_id) == $country->id)>{{ $country->flag }} {{ $country->name }}</option>
            @endforeach
        </select>
        <x-input-error :messages="$errors->get('country_id')" />
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
    <x-input-label for="tags" value="Tags (séparés par des virgules, 8 max)" />
    <x-text-input id="tags" name="tags" class="block w-full" :value="$tagsValue" placeholder="paiement, mobile money, api" />
    <x-input-error :messages="array_merge($errors->get('tags'), $errors->get('tags.*'))" />
</div>

<div class="grid sm:grid-cols-2 gap-4">
    <div>
        <x-input-label for="github_url" value="Lien GitHub" />
        <x-text-input id="github_url" name="github_url" type="url" class="block w-full" :value="old('github_url', $resource?->github_url)" placeholder="https://github.com/…" />
        <x-input-error :messages="$errors->get('github_url')" />
    </div>
    <div>
        <x-input-label for="demo_url" value="Lien de démo" />
        <x-text-input id="demo_url" name="demo_url" type="url" class="block w-full" :value="old('demo_url', $resource?->demo_url)" placeholder="https://" />
        <x-input-error :messages="$errors->get('demo_url')" />
    </div>
</div>

<div>
    <x-input-label for="image" value="Image (jpg, png, webp — 2 Mo max)" />
    @if ($resource?->imageUrl())
        <img src="{{ $resource->imageUrl() }}" alt="" class="h-24 rounded my-2">
        <label class="text-sm"><input type="checkbox" name="remove_image" value="1"> Supprimer l'image</label>
    @endif
    <input id="image" type="file" name="image" accept="image/jpeg,image/png,image/webp" class="block mt-1">
    <x-input-error :messages="$errors->get('image')" />
</div>

<div>
    <x-input-label for="file" value="Fichier à télécharger ({{ implode(', ', $fileExtensions) }} — 10 Mo max)" />
    @if ($resource?->hasFile())
        <p class="text-sm my-1">Un fichier est déjà joint.</p>
        <label class="text-sm"><input type="checkbox" name="remove_file" value="1"> Supprimer le fichier</label>
    @endif
    <input id="file" type="file" name="file" class="block mt-1">
    <x-input-error :messages="$errors->get('file')" />
</div>
