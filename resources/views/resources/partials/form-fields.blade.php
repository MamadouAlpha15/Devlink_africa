{{--
    Champs communs aux formulaires de publication et de modification d'une
    ressource. `$resource` est null à la création.

    Variables attendues : $types, $countries, $skillsByCategory,
    $fileExtensions. Voir docs/front-end/pages.md.
--}}
@php
    $selectedSkills = collect(old('skills', $resource?->skills->pluck('id')->all() ?? []))
        ->map(fn ($id) => (int) $id);

    $oldTags = old('tags', $resource?->tags->pluck('name')->all() ?? []);
    $tagsValue = is_array($oldTags) ? implode(', ', $oldTags) : $oldTags;
@endphp

{{-- ==========  L'essentiel  ========== --}}
<div class="panel space-y-5">
    <x-section-heading title="La ressource" />

    <div>
        <label for="title" class="label">Titre</label>
        <input
            id="title"
            type="text"
            name="title"
            value="{{ old('title', $resource?->title) }}"
            maxlength="120"
            required
            class="input mt-1.5"
            placeholder="Orange Money pour Laravel"
        >
        <x-input-error :messages="$errors->get('title')" class="mt-2" />
    </div>

    <div x-data="charCounter(@js(old('description', $resource?->description ?? '')), 5000)">
        <label for="description" class="label">Description</label>
        <textarea
            id="description"
            name="description"
            rows="6"
            maxlength="5000"
            required
            class="textarea mt-1.5"
            placeholder="À quoi sert cette ressource, comment l'installer, ce qu'elle suppose."
            x-model="value"
        >{{ old('description', $resource?->description) }}</textarea>

        <div class="mt-1.5 flex items-start justify-between gap-3">
            <p class="field-hint mt-0">Expliquez le problème qu'elle résout : c'est ce qui décide du téléchargement.</p>
            <p
                class="shrink-0 text-xs"
                :class="isNearLimit ? 'font-medium text-amber-600 dark:text-amber-400' : 'text-ink-500'"
            >
                <span class="tabular" x-text="length">0</span> / 5000
            </p>
        </div>

        <x-input-error :messages="$errors->get('description')" class="mt-2" />
    </div>

    <div class="grid gap-5 sm:grid-cols-2">
        <div>
            <label for="type" class="label">Type</label>
            <select id="type" name="type" required class="select mt-1.5">
                @foreach ($types as $type)
                    <option value="{{ $type->value }}" @selected(old('type', $resource?->type?->value) === $type->value)>
                        {{ $type->label() }}
                    </option>
                @endforeach
            </select>
            <x-input-error :messages="$errors->get('type')" class="mt-2" />
        </div>

        <div>
            <label for="country_id" class="label">Pays <span class="label-hint">facultatif</span></label>
            <select id="country_id" name="country_id" class="select mt-1.5">
                <option value="">Toute l'Afrique</option>
                @foreach ($countries as $country)
                    <option value="{{ $country->id }}" @selected(old('country_id', $resource?->country_id ?? auth()->user()->country_id) == $country->id)>
                        {{ $country->name }}
                    </option>
                @endforeach
            </select>
            <x-input-error :messages="$errors->get('country_id')" class="mt-2" />
        </div>
    </div>
</div>

{{-- ==========  Classement  ========== --}}
<div class="panel mt-6 space-y-5">
    <x-section-heading title="Pour la retrouver" />

    {{-- Technologies : cases à cocher filtrables, 10 maximum. --}}
    <div x-data="skillPicker(@js($selectedSkills->all()), 10)">
        <div class="flex flex-wrap items-end justify-between gap-2">
            <label class="label mb-0">Technologies</label>
            <p class="text-xs" :class="isFull ? 'font-semibold text-amber-600 dark:text-amber-400' : 'text-ink-500'">
                <span class="tabular" x-text="count">0</span> / 10 sélectionnées
            </p>
        </div>

        <div class="card mt-2 overflow-hidden">
            <div class="border-b border-ink-200 p-2 dark:border-ink-800">
                <div class="input-group">
                    <x-icon name="search" />
                    <input
                        type="search"
                        x-model="search"
                        class="input h-10 text-sm"
                        placeholder="Filtrer les technologies…"
                        autocomplete="off"
                    >
                </div>
            </div>

            <div class="max-h-64 overflow-y-auto p-1.5">
                @foreach ($skillsByCategory as $category => $skills)
                    @foreach ($skills as $skill)
                        <label
                            x-show="matches(@js($skill->name))"
                            class="checkbox-row !py-2"
                            :class="isDisabled({{ $skill->id }}) && 'opacity-40'"
                        >
                            <input
                                type="checkbox"
                                name="skills[]"
                                value="{{ $skill->id }}"
                                class="checkbox mt-0.5"
                                :checked="isSelected({{ $skill->id }})"
                                :disabled="isDisabled({{ $skill->id }})"
                                @change="toggle({{ $skill->id }})"
                                @checked($selectedSkills->contains($skill->id))
                            >
                            <span class="text-sm text-ink-700 dark:text-ink-300">{{ $skill->name }}</span>
                        </label>
                    @endforeach
                @endforeach
            </div>
        </div>

        <p class="field-hint">10 technologies au maximum.</p>
        <x-input-error :messages="$errors->get('skills')" />
    </div>

    <div>
        <label for="tags" class="label">Tags <span class="label-hint">séparés par des virgules · 8 max</span></label>
        <input
            id="tags"
            type="text"
            name="tags"
            value="{{ $tagsValue }}"
            class="input mt-1.5"
            placeholder="paiement, mobile money, api"
        >
        <p class="field-hint">Des mots que l'on taperait pour chercher cette ressource.</p>
        <x-input-error :messages="array_merge($errors->get('tags'), $errors->get('tags.*'))" class="mt-2" />
    </div>
</div>

{{-- ==========  Liens et fichiers  ========== --}}
<div class="panel mt-6 space-y-5">
    <x-section-heading title="Liens et fichiers" />

    <div class="grid gap-5 sm:grid-cols-2">
        <div>
            <label for="github_url" class="label">Lien GitHub <span class="label-hint">facultatif</span></label>
            <input
                id="github_url"
                type="url"
                name="github_url"
                value="{{ old('github_url', $resource?->github_url) }}"
                class="input mt-1.5"
                placeholder="https://github.com/…"
            >
            <x-input-error :messages="$errors->get('github_url')" class="mt-2" />
        </div>

        <div>
            <label for="demo_url" class="label">Lien de démo <span class="label-hint">facultatif</span></label>
            <input
                id="demo_url"
                type="url"
                name="demo_url"
                value="{{ old('demo_url', $resource?->demo_url) }}"
                class="input mt-1.5"
                placeholder="https://…"
            >
            <x-input-error :messages="$errors->get('demo_url')" class="mt-2" />
        </div>
    </div>

    {{-- Image de présentation, avec aperçu immédiat avant envoi. --}}
    <div x-data="imagePreview(@js($resource?->imageUrl()))">
        <label for="image" class="label">
            Image <span class="label-hint">facultatif · jpg, png ou webp · 2 Mo max</span>
        </label>

        <template x-if="hasPreview">
            <div class="mb-3 overflow-hidden rounded-lg border border-ink-200 dark:border-ink-800">
                <img :src="previewUrl" alt="" class="max-h-44 w-full object-cover">
            </div>
        </template>

        <input
            id="image"
            type="file"
            name="image"
            x-ref="fileField"
            @change="pick($event)"
            accept="image/jpeg,image/png,image/webp"
            class="file-field"
        >

        @if ($resource?->imageUrl())
            <label class="checkbox-row mt-2">
                <input type="checkbox" name="remove_image" value="1" class="checkbox mt-0.5">
                <span class="text-sm text-ink-700 dark:text-ink-300">Supprimer l'image actuelle</span>
            </label>
        @endif

        <x-input-error :messages="$errors->get('image')" class="mt-2" />
    </div>

    <div>
        <label for="file" class="label">
            Fichier à télécharger
            <span class="label-hint">facultatif · {{ implode(', ', $fileExtensions) }} · 10 Mo max</span>
        </label>

        @if ($resource?->hasFile())
            <p class="mb-2 flex items-center gap-2 text-sm text-ink-600 dark:text-ink-400">
                <x-icon name="file" class="h-4 w-4 text-ink-400" />
                Un fichier est déjà joint.
            </p>
        @endif

        <input id="file" type="file" name="file" class="file-field">

        @if ($resource?->hasFile())
            <label class="checkbox-row mt-2">
                <input type="checkbox" name="remove_file" value="1" class="checkbox mt-0.5">
                <span class="text-sm text-ink-700 dark:text-ink-300">Supprimer le fichier actuel</span>
            </label>
        @endif

        <x-input-error :messages="$errors->get('file')" class="mt-2" />
    </div>
</div>
