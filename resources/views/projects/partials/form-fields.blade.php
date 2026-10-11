{{--
    Champs communs aux formulaires de création et de modification d'un projet.
    `$project` vaut null à la création.

    Les noms des champs sont imposés par le backend : title, description,
    category, country_id, status, members_wanted, skills[], cover, remove_cover.
--}}
@php
    $selectedSkills = collect(old('skills', $project?->skills->pluck('id')->all() ?? []))
        ->map(fn ($id) => (int) $id);

    // Liste à plat, triée, pour le sélecteur de compétences filtrable.
    $allSkills = $skillsByCategory->flatten()->sortBy('name')->values();
@endphp

<div class="space-y-5">

    {{-- Titre --}}
    <div>
        <label for="title" class="label">Titre du projet</label>
        <input
            id="title"
            type="text"
            name="title"
            value="{{ old('title', $project?->title) }}"
            maxlength="100"
            required
            class="input"
            placeholder="AfricaDelivery"
        >
        <x-input-error :messages="$errors->get('title')" />
    </div>

    {{-- Description --}}
    <div x-data="charCounter(@js(old('description', $project?->description ?? '')), 5000)">
        <label for="description" class="label">Description</label>
        <textarea
            id="description"
            name="description"
            x-model="value"
            rows="7"
            maxlength="5000"
            required
            class="textarea"
            placeholder="Présentez le problème que résout le projet, où vous en êtes, et ce que vous attendez de l'équipe."
        >{{ old('description', $project?->description) }}</textarea>

        <div class="mt-1.5 flex items-center justify-between gap-3">
            <p class="text-xs text-ink-500 dark:text-ink-500">
                Soyez précis : c'est ce que liront les développeurs avant de postuler.
            </p>
            {{-- Les deux états passent par :class : une couleur statique ici
                 l'emporterait sur l'ambre, compilé plus tôt dans la feuille. --}}
            <p class="shrink-0 text-xs" :class="isNearLimit ? 'text-amber-600 dark:text-amber-400' : 'text-ink-500'">
                <span class="tabular" x-text="length">0</span> / 5000
            </p>
        </div>

        <x-input-error :messages="$errors->get('description')" />
    </div>

    {{-- Catégorie, pays, statut, membres --}}
    <div class="grid gap-5 sm:grid-cols-2">
        <div>
            <label for="category" class="label">Catégorie</label>
            <select id="category" name="category" class="select" required>
                @foreach ($categories as $category)
                    <option value="{{ $category->value }}" @selected(old('category', $project?->category?->value) === $category->value)>
                        {{ $category->label() }}
                    </option>
                @endforeach
            </select>
            <x-input-error :messages="$errors->get('category')" />
        </div>

        <div>
            <label for="country_id" class="label">
                Pays <span class="label-hint">facultatif</span>
            </label>
            <select id="country_id" name="country_id" class="select">
                <option value="">Toute l'Afrique</option>
                @foreach ($countries as $country)
                    <option value="{{ $country->id }}" @selected(old('country_id', $project?->country_id) == $country->id)>
                        {{ $country->name }}
                    </option>
                @endforeach
            </select>
            <x-input-error :messages="$errors->get('country_id')" />
        </div>

        <div>
            <label for="status" class="label">Statut</label>
            <select id="status" name="status" class="select" required>
                @foreach ($statuses as $status)
                    <option value="{{ $status->value }}" @selected(old('status', $project?->status?->value ?? 'recruiting') === $status->value)>
                        {{ $status->label() }}
                    </option>
                @endforeach
            </select>
            <x-input-error :messages="$errors->get('status')" />
        </div>

        <div>
            <label for="members_wanted" class="label">Nombre de membres recherchés</label>
            <input
                id="members_wanted"
                type="number"
                name="members_wanted"
                value="{{ old('members_wanted', $project?->members_wanted ?? 1) }}"
                min="1"
                max="50"
                required
                class="input"
            >
            <x-input-error :messages="$errors->get('members_wanted')" />
        </div>
    </div>

    {{-- Technologies : cases à cocher filtrables, 10 maximum --}}
    <div x-data="skillPicker(@js($selectedSkills->all()), 10)">
        <div class="flex flex-wrap items-end justify-between gap-2">
            <label class="label mb-0">Technologies</label>
            <p class="text-xs" :class="isFull ? 'font-semibold text-amber-600 dark:text-amber-400' : 'text-ink-500'">
                <span class="tabular" x-text="count">0</span> / 10 sélectionnées
            </p>
        </div>

        <div class="mt-2 card overflow-hidden">
            {{-- Recherche dans la liste --}}
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

            {{-- Liste --}}
            <div class="max-h-64 overflow-y-auto p-1.5">
                @foreach ($allSkills as $skill)
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
            </div>
        </div>

        <p class="field-hint">10 technologies au maximum.</p>
        <x-input-error :messages="$errors->get('skills')" />
    </div>

    {{-- Image de couverture --}}
    <div x-data="imagePreview(@js($project?->coverUrl()))">
        <label for="cover" class="label">
            Image du projet <span class="label-hint">facultatif · jpg, png ou webp · 2 Mo max</span>
        </label>

        <template x-if="hasPreview">
            <div class="mb-3 overflow-hidden rounded-lg border border-ink-200 dark:border-ink-800">
                <img :src="previewUrl" alt="" class="max-h-44 w-full object-cover">
            </div>
        </template>

        <input
            id="cover"
            type="file"
            name="cover"
            x-ref="fileField"
            @change="pick($event)"
            accept="image/jpeg,image/png,image/webp"
            class="file-field"
        >

        @if ($project?->coverUrl())
            <label class="checkbox-row mt-2">
                <input type="checkbox" name="remove_cover" value="1" class="checkbox mt-0.5">
                <span class="text-sm text-ink-700 dark:text-ink-300">Supprimer l'image actuelle</span>
            </label>
        @endif

        <x-input-error :messages="$errors->get('cover')" />
    </div>
</div>
