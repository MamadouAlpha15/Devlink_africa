{{--
    Page "Modifier mon profil" — version fonctionnelle minimale (backend).
    Le front-end la restylera : voir docs/front-end/profils.md pour les variables disponibles.
--}}
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Mon profil développeur</h2>
        <p class="text-sm text-gray-600">Profil complété à {{ $completion }} %</p>
    </x-slot>

    <div class="py-8">
        <div class="max-w-3xl mx-auto px-4 space-y-6">

            @if (session('status'))
                <div class="p-4 bg-green-50 text-green-800 rounded">{{ session('status') }}</div>
            @endif

            @if ($user->profile_completed_at === null)
                <div class="p-4 bg-yellow-50 text-yellow-800 rounded">
                    Complétez votre profil (prénom, nom, pays, ville, bio, rôle, disponibilité et au moins une compétence)
                    pour gagner +{{ config('devscore.points.profile_completed') }} points DevScore.
                </div>
            @endif

            <p><a href="{{ route('devs.show', $user) }}" class="underline">Voir mon profil public</a></p>

            {{-- Informations du profil --}}
            <form method="POST" action="{{ route('developer-profile.update') }}" enctype="multipart/form-data" class="p-6 bg-white shadow rounded space-y-4">
                @csrf
                @method('PUT')

                <h3 class="font-semibold">Informations</h3>

                <div class="grid sm:grid-cols-2 gap-4">
                    <div>
                        <x-input-label for="first_name" value="Prénom" />
                        <x-text-input id="first_name" name="first_name" class="block w-full" :value="old('first_name', $user->first_name)" required />
                        <x-input-error :messages="$errors->get('first_name')" />
                    </div>
                    <div>
                        <x-input-label for="last_name" value="Nom" />
                        <x-text-input id="last_name" name="last_name" class="block w-full" :value="old('last_name', $user->last_name)" required />
                        <x-input-error :messages="$errors->get('last_name')" />
                    </div>
                </div>

                <div>
                    <x-input-label for="avatar" value="Photo (jpg, png, webp — 2 Mo max)" />
                    @if ($user->avatar_url)
                        <img src="{{ $user->avatar_url }}" alt="" class="h-16 w-16 rounded-full object-cover my-2">
                        <label class="text-sm"><input type="checkbox" name="remove_avatar" value="1"> Supprimer la photo</label>
                    @endif
                    <input id="avatar" type="file" name="avatar" accept="image/jpeg,image/png,image/webp" class="block mt-1">
                    <x-input-error :messages="$errors->get('avatar')" />
                </div>

                <div class="grid sm:grid-cols-2 gap-4">
                    <div>
                        <x-input-label for="country_id" value="Pays" />
                        <select id="country_id" name="country_id" class="block w-full border-gray-300 rounded" required>
                            <option value="">— Choisir —</option>
                            @foreach ($countries as $country)
                                <option value="{{ $country->id }}" @selected(old('country_id', $user->country_id) == $country->id)>{{ $country->flag }} {{ $country->name }}</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('country_id')" />
                    </div>
                    <div>
                        <x-input-label for="city" value="Ville" />
                        <x-text-input id="city" name="city" class="block w-full" :value="old('city', $user->city)" required />
                        <x-input-error :messages="$errors->get('city')" />
                    </div>
                </div>

                <div>
                    <x-input-label for="bio" value="Bio" />
                    <textarea id="bio" name="bio" rows="4" maxlength="1000" class="block w-full border-gray-300 rounded">{{ old('bio', $user->bio) }}</textarea>
                    <x-input-error :messages="$errors->get('bio')" />
                </div>

                <div class="grid sm:grid-cols-3 gap-4">
                    <div>
                        <x-input-label for="profile_type" value="Rôle" />
                        <select id="profile_type" name="profile_type" class="block w-full border-gray-300 rounded" required>
                            @foreach ($profileTypes as $type)
                                <option value="{{ $type->value }}" @selected(old('profile_type', $user->profile_type?->value) === $type->value)>{{ $type->label() }}</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('profile_type')" />
                    </div>
                    <div>
                        <x-input-label for="experience_level" value="Niveau" />
                        <select id="experience_level" name="experience_level" class="block w-full border-gray-300 rounded">
                            <option value="">—</option>
                            @foreach ($experienceLevels as $level)
                                <option value="{{ $level->value }}" @selected(old('experience_level', $user->experience_level?->value) === $level->value)>{{ $level->label() }}</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('experience_level')" />
                    </div>
                    <div>
                        <x-input-label for="availability" value="Disponibilité" />
                        <select id="availability" name="availability" class="block w-full border-gray-300 rounded" required>
                            @foreach ($availabilities as $availability)
                                <option value="{{ $availability->value }}" @selected(old('availability', $user->availability?->value) === $availability->value)>{{ $availability->label() }}</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('availability')" />
                    </div>
                </div>

                <h3 class="font-semibold pt-2">Liens</h3>
                @foreach (['github_url' => 'GitHub', 'linkedin_url' => 'LinkedIn', 'portfolio_url' => 'Portfolio', 'website_url' => 'Site personnel'] as $field => $label)
                    <div>
                        <x-input-label :for="$field" :value="$label" />
                        <x-text-input :id="$field" :name="$field" type="url" class="block w-full" :value="old($field, $user->{$field})" placeholder="https://" />
                        <x-input-error :messages="$errors->get($field)" />
                    </div>
                @endforeach

                <h3 class="font-semibold pt-2">Confidentialité et notifications</h3>
                @foreach ([
                    'is_public' => 'Profil public',
                    'show_email' => 'Afficher mon email sur mon profil',
                    'show_availability' => 'Afficher ma disponibilité',
                    'notify_by_email' => 'Recevoir les notifications par email',
                    'notify_on_platform' => 'Recevoir les notifications sur la plateforme',
                ] as $field => $label)
                    <label class="block text-sm">
                        <input type="hidden" name="{{ $field }}" value="0">
                        <input type="checkbox" name="{{ $field }}" value="1" @checked(old($field, $user->{$field}))> {{ $label }}
                    </label>
                @endforeach

                <x-primary-button>Enregistrer</x-primary-button>
            </form>

            {{-- Compétences --}}
            <div class="p-6 bg-white shadow rounded space-y-4">
                <h3 class="font-semibold">Compétences</h3>

                @forelse ($user->skills as $skill)
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="w-40">{{ $skill->name }}</span>

                        <form method="POST" action="{{ route('profile.skills.update', $skill) }}" class="flex gap-2">
                            @csrf
                            @method('PATCH')
                            <select name="level" class="border-gray-300 rounded text-sm" onchange="this.form.submit()">
                                @foreach ($skillLevels as $level)
                                    <option value="{{ $level->value }}" @selected($skill->pivot->level === $level)>{{ $level->label() }}</option>
                                @endforeach
                            </select>
                        </form>

                        <form method="POST" action="{{ route('profile.skills.destroy', $skill) }}">
                            @csrf
                            @method('DELETE')
                            <button class="text-sm text-red-600">Supprimer</button>
                        </form>
                    </div>
                @empty
                    <p class="text-sm text-gray-600">Aucune compétence pour l'instant.</p>
                @endforelse

                <form method="POST" action="{{ route('profile.skills.store') }}" class="flex flex-wrap gap-2 items-end pt-2 border-t">
                    @csrf
                    <div>
                        <x-input-label for="skill_id" value="Ajouter une compétence" />
                        <select id="skill_id" name="skill_id" class="border-gray-300 rounded" required>
                            <option value="">— Choisir —</option>
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
                        <x-input-label for="level" value="Niveau" />
                        <select id="level" name="level" class="border-gray-300 rounded" required>
                            @foreach ($skillLevels as $level)
                                <option value="{{ $level->value }}">{{ $level->label() }}</option>
                            @endforeach
                        </select>
                    </div>
                    <x-primary-button>Ajouter</x-primary-button>
                    <x-input-error :messages="$errors->get('skill_id')" class="w-full" />
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
