{{--
    Modifier mon profil développeur.

    Variables : $user, $completion (0–100), $countries, $skillsByCategory,
    $profileTypes, $experienceLevels, $availabilities, $skillLevels.
    Voir docs/front-end/profils.md.

    Noms de champs imposés par le backend. Les cases à cocher sont doublées
    d'un champ caché à 0 : une case non envoyée vaut « désactivée ».
--}}
@php
    // Liens externes, regroupés pour éviter quatre blocs identiques.
    $linkFields = [
        'github_url' => ['label' => 'GitHub', 'icon' => 'github', 'placeholder' => 'https://github.com/pseudo'],
        'linkedin_url' => ['label' => 'LinkedIn', 'icon' => 'linkedin', 'placeholder' => 'https://linkedin.com/in/pseudo'],
        'portfolio_url' => ['label' => 'Portfolio', 'icon' => 'briefcase', 'placeholder' => 'https://mon-portfolio.com'],
        'website_url' => ['label' => 'Site personnel', 'icon' => 'link', 'placeholder' => 'https://mon-site.com'],
    ];

    // Confidentialité et notifications.
    $toggleFields = [
        'is_public' => ['label' => 'Profil public', 'text' => 'Votre profil apparaît dans les recherches et peut être consulté par tous.'],
        'show_email' => ['label' => 'Afficher mon adresse e-mail', 'text' => 'Un bouton « Contacter » apparaît sur votre profil public.'],
        'show_availability' => ['label' => 'Afficher ma disponibilité', 'text' => 'Votre statut (disponible, ouvert, indisponible) devient visible.'],
        'notify_by_email' => ['label' => 'Notifications par e-mail', 'text' => 'Invitations, demandes et réponses vous sont envoyées par e-mail.'],
        'notify_on_platform' => ['label' => 'Notifications sur la plateforme', 'text' => 'Les notifications apparaissent dans votre espace.'],
    ];

    $isProfileComplete = $user->profile_completed_at !== null;
@endphp

<x-app-layout title="Mon profil développeur">
    <div class="mx-auto max-w-3xl px-4 py-8 sm:px-6 lg:px-8 lg:py-10">

        <x-page-header title="Mon profil développeur">
            <x-slot name="actions">
                <a href="{{ route('devs.show', $user) }}" class="btn-secondary btn-sm">
                    <x-icon name="eye" class="h-4 w-4" />
                    Voir mon profil public
                </a>
            </x-slot>
        </x-page-header>

        <x-flash class="mt-6" />

        {{-- ==========  Progression  ========== --}}
        <div class="panel mt-6">
            <div class="flex items-center justify-between gap-4">
                <div class="min-w-0">
                    <p class="font-semibold text-ink-900 dark:text-white">Complétion du profil</p>
                    <p class="muted mt-0.5">
                        @if ($isProfileComplete)
                            Votre profil est complet. Il apparaît dans les recherches.
                        @else
                            Prénom, nom, pays, ville, bio, rôle, disponibilité et au moins une compétence.
                        @endif
                    </p>
                </div>

                <span class="tabular shrink-0 text-2xl font-bold {{ $completion >= 100 ? 'text-emerald-600 dark:text-emerald-400' : 'text-brand-600 dark:text-brand-400' }}">
                    {{ $completion }} %
                </span>
            </div>

            {{-- La barre part de 0 et monte jusqu'à la valeur réelle. --}}
            <div class="progress-track mt-3.5" x-data="animatedScore({{ (int) $completion }})">
                <div
                    class="progress-bar {{ $completion >= 100 ? '!bg-emerald-600 dark:!bg-emerald-500' : '' }}"
                    :style="`width: ${value}%`"
                    style="width: 0%"
                ></div>
            </div>

            @unless ($isProfileComplete)
                <p class="mt-3 flex items-start gap-2 text-xs text-amber-700 dark:text-amber-400">
                    <x-icon name="zap" class="mt-px h-3.5 w-3.5 shrink-0" />
                    Terminez votre profil pour gagner
                    +{{ config('devscore.points.profile_completed') }} points DevScore, une seule fois.
                </p>
            @endunless
        </div>

        {{-- ==========  Informations  ========== --}}
        <form
            method="POST"
            action="{{ route('developer-profile.update') }}"
            enctype="multipart/form-data"
            class="mt-6 space-y-6"
        >
            @csrf
            @method('PUT')

            <div class="panel">
                <x-section-heading title="Identité" />

                <div class="mt-5 space-y-5">
                    {{-- Photo --}}
                    <div x-data="imagePreview(@js($user->avatar_url))">
                        <span class="label">Photo de profil</span>

                        <div class="flex flex-wrap items-center gap-4">
                            {{-- Aperçu ou initiales --}}
                            <template x-if="hasPreview">
                                <img :src="previewUrl" alt="" class="h-20 w-20 shrink-0 rounded-full border border-ink-200 object-cover dark:border-ink-800">
                            </template>

                            <template x-if="! hasPreview">
                                <span class="avatar-fallback h-20 w-20 text-2xl">{{ $user->initials }}</span>
                            </template>

                            <div class="min-w-0 flex-1">
                                <input
                                    id="avatar"
                                    type="file"
                                    name="avatar"
                                    x-ref="fileField"
                                    @change="pick($event)"
                                    accept="image/jpeg,image/png,image/webp"
                                    class="file-field"
                                >
                                <p class="field-hint">jpg, png ou webp · 2 Mo maximum</p>
                            </div>
                        </div>

                        @if ($user->avatar_url)
                            <label class="checkbox-row mt-2">
                                <input type="checkbox" name="remove_avatar" value="1" class="checkbox mt-0.5">
                                <span class="text-sm text-ink-700 dark:text-ink-300">Supprimer la photo actuelle</span>
                            </label>
                        @endif

                        <x-input-error :messages="$errors->get('avatar')" />
                    </div>

                    {{-- Prénom / nom --}}
                    <div class="grid gap-5 sm:grid-cols-2">
                        <div>
                            <label for="first_name" class="label">Prénom</label>
                            <input id="first_name" type="text" name="first_name" maxlength="50" required
                                   value="{{ old('first_name', $user->first_name) }}" class="input">
                            <x-input-error :messages="$errors->get('first_name')" />
                        </div>

                        <div>
                            <label for="last_name" class="label">Nom</label>
                            <input id="last_name" type="text" name="last_name" maxlength="50" required
                                   value="{{ old('last_name', $user->last_name) }}" class="input">
                            <x-input-error :messages="$errors->get('last_name')" />
                        </div>
                    </div>

                    {{-- Pays / ville --}}
                    <div class="grid gap-5 sm:grid-cols-2">
                        <div>
                            <label for="country_id" class="label">Pays</label>
                            <select id="country_id" name="country_id" class="select" required>
                                <option value="">— Choisir un pays —</option>
                                @foreach ($countries as $country)
                                    <option value="{{ $country->id }}" @selected(old('country_id', $user->country_id) == $country->id)>
                                        {{ $country->name }}
                                    </option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('country_id')" />
                        </div>

                        <div>
                            <label for="city" class="label">Ville</label>
                            <input id="city" type="text" name="city" required
                                   value="{{ old('city', $user->city) }}" class="input" placeholder="Conakry">
                            <x-input-error :messages="$errors->get('city')" />
                        </div>
                    </div>

                    {{-- Bio --}}
                    <div x-data="charCounter(@js(old('bio', $user->bio ?? '')), 1000)">
                        <label for="bio" class="label">
                            Bio <span class="label-hint">présentez-vous en quelques lignes</span>
                        </label>
                        <textarea id="bio" name="bio" x-model="value" rows="5" maxlength="1000" class="textarea"
                                  placeholder="Développeur backend Laravel, passionné par les solutions de paiement pour l'Afrique.">{{ old('bio', $user->bio) }}</textarea>
                        <p class="field-hint text-right" :class="isNearLimit && 'text-amber-600 dark:text-amber-400'">
                            <span class="tabular" x-text="length">0</span> / 1000
                        </p>
                        <x-input-error :messages="$errors->get('bio')" />
                    </div>
                </div>
            </div>

            {{-- ==========  Profil professionnel  ========== --}}
            <div class="panel">
                <x-section-heading title="Profil professionnel" />

                <div class="mt-5 grid gap-5 sm:grid-cols-3">
                    <div>
                        <label for="profile_type" class="label">Rôle</label>
                        <select id="profile_type" name="profile_type" class="select" required>
                            @foreach ($profileTypes as $type)
                                <option value="{{ $type->value }}" @selected(old('profile_type', $user->profile_type?->value) === $type->value)>
                                    {{ $type->label() }}
                                </option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('profile_type')" />
                    </div>

                    <div>
                        <label for="experience_level" class="label">
                            Niveau <span class="label-hint">facultatif</span>
                        </label>
                        <select id="experience_level" name="experience_level" class="select">
                            <option value="">— Non précisé —</option>
                            @foreach ($experienceLevels as $level)
                                <option value="{{ $level->value }}" @selected(old('experience_level', $user->experience_level?->value) === $level->value)>
                                    {{ $level->label() }}
                                </option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('experience_level')" />
                    </div>

                    <div>
                        <label for="availability" class="label">Disponibilité</label>
                        <select id="availability" name="availability" class="select" required>
                            @foreach ($availabilities as $availability)
                                <option value="{{ $availability->value }}" @selected(old('availability', $user->availability?->value) === $availability->value)>
                                    {{ $availability->label() }}
                                </option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('availability')" />
                    </div>
                </div>
            </div>

            {{-- ==========  Liens  ========== --}}
            <div class="panel">
                <x-section-heading title="Liens" />

                <div class="mt-5 grid gap-5 sm:grid-cols-2">
                    @foreach ($linkFields as $field => $meta)
                        <div>
                            <label for="{{ $field }}" class="label">
                                <span class="inline-flex items-center gap-1.5">
                                    <x-icon :name="$meta['icon']" class="h-3.5 w-3.5 text-ink-400" />
                                    {{ $meta['label'] }}
                                </span>
                            </label>
                            <input
                                id="{{ $field }}"
                                type="url"
                                name="{{ $field }}"
                                value="{{ old($field, $user->{$field}) }}"
                                class="input"
                                placeholder="{{ $meta['placeholder'] }}"
                            >
                            <x-input-error :messages="$errors->get($field)" />
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- ==========  Confidentialité  ========== --}}
            <div class="panel">
                <x-section-heading title="Confidentialité et notifications" />

                <div class="mt-4 space-y-1">
                    @foreach ($toggleFields as $field => $meta)
                        <label class="checkbox-row">
                            {{-- Champ caché : une case décochée doit valoir 0. --}}
                            <input type="hidden" name="{{ $field }}" value="0">
                            <input type="checkbox" name="{{ $field }}" value="1" class="checkbox mt-0.5"
                                   @checked(old($field, $user->{$field}))>

                            <span class="min-w-0">
                                <span class="block text-sm font-medium text-ink-900 dark:text-white">{{ $meta['label'] }}</span>
                                <span class="mt-0.5 block text-xs text-ink-500 dark:text-ink-400">{{ $meta['text'] }}</span>
                            </span>
                        </label>
                    @endforeach
                </div>
            </div>

            <div class="flex flex-wrap items-center justify-end gap-3">
                <a href="{{ route('devs.show', $user) }}" class="btn-ghost">Annuler</a>

                <button type="submit" class="btn-primary">
                    <x-icon name="check" class="h-4 w-4" />
                    Enregistrer mon profil
                </button>
            </div>
        </form>

        {{-- ==========  Compétences  ========== --}}
        <div class="panel mt-6">
            <x-section-heading title="Mes compétences" :count="$user->skills->count()">
                <x-slot name="action">
                    <span class="subtle">15 maximum</span>
                </x-slot>
            </x-section-heading>

            @if ($user->skills->isNotEmpty())
                <ul class="mt-5 space-y-2">
                    @foreach ($user->skills as $skill)
                        <li class="flex flex-wrap items-center justify-between gap-3 rounded-lg border border-ink-200 p-3 dark:border-ink-800">
                            <span class="min-w-0 flex-1 truncate text-sm font-medium text-ink-900 dark:text-white">
                                {{ $skill->name }}
                            </span>

                            <div class="flex shrink-0 items-center gap-2">
                                {{-- Changer le niveau : envoi au changement de valeur. --}}
                                <form method="POST" action="{{ route('profile.skills.update', $skill) }}">
                                    @csrf
                                    @method('PATCH')
                                    <label for="level-{{ $skill->id }}" class="sr-only">Niveau pour {{ $skill->name }}</label>
                                    <select id="level-{{ $skill->id }}" name="level" class="select h-9 w-auto pr-9 text-xs" onchange="this.form.submit()">
                                        @foreach ($skillLevels as $level)
                                            <option value="{{ $level->value }}" @selected($skill->pivot->level === $level)>
                                                {{ $level->label() }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <noscript><button type="submit" class="btn-secondary btn-xs">OK</button></noscript>
                                </form>

                                <form method="POST" action="{{ route('profile.skills.destroy', $skill) }}" x-data="confirmAction">
                                    @csrf
                                    @method('DELETE')
                                    <button type="button" x-show="! isConfirming" @click="ask()" class="btn-danger-ghost btn-xs" aria-label="Supprimer {{ $skill->name }}">
                                        <x-icon name="trash" class="h-3.5 w-3.5" />
                                    </button>
                                    <span x-show="isConfirming" x-cloak class="inline-flex items-center gap-1">
                                        <button type="submit" class="btn-danger btn-xs">Supprimer</button>
                                        <button type="button" @click="cancel()" class="btn-ghost btn-xs">Non</button>
                                    </span>
                                </form>
                            </div>
                        </li>
                    @endforeach
                </ul>
            @else
                <p class="muted mt-4">
                    Aucune compétence pour l'instant. Ajoutez-en au moins une : c'est l'un
                    des critères pour que votre profil soit considéré comme complet.
                </p>
            @endif

            {{-- Ajouter --}}
            <form method="POST" action="{{ route('profile.skills.store') }}" class="divider mt-5 pt-5">
                @csrf

                <p class="caption mb-2">Ajouter une compétence</p>

                <div class="grid gap-2 sm:grid-cols-[1fr_10rem_auto]">
                    <div>
                        <label for="skill_id" class="sr-only">Compétence</label>
                        <select id="skill_id" name="skill_id" class="select h-10 text-sm" required>
                            <option value="">— Choisir une compétence —</option>
                            @foreach ($skillsByCategory as $category => $skills)
                                <optgroup label="{{ $category }}">
                                    @foreach ($skills as $skill)
                                        <option value="{{ $skill->id }}">{{ $skill->name }}</option>
                                    @endforeach
                                </optgroup>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="level" class="sr-only">Niveau</label>
                        <select id="level" name="level" class="select h-10 text-sm" required>
                            @foreach ($skillLevels as $level)
                                <option value="{{ $level->value }}">{{ $level->label() }}</option>
                            @endforeach
                        </select>
                    </div>

                    <button type="submit" class="btn-primary btn-sm">
                        <x-icon name="plus" class="h-4 w-4" />
                        Ajouter
                    </button>
                </div>

                <x-input-error :messages="$errors->get('skill_id')" />
                <x-input-error :messages="$errors->get('level')" />
            </form>
        </div>
    </div>
</x-app-layout>
