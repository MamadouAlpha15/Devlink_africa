{{--
    Inscription. Champs Breeze : name, email, password, password_confirmation.
    Après l'inscription, l'utilisateur est redirigé vers /profile/developer.
--}}
<x-guest-layout title="Créer un compte">
    <x-slot name="heading">Rejoignez DevLink Africa</x-slot>
    <x-slot name="subheading">Créez votre compte, puis complétez votre profil développeur.</x-slot>

    <form method="POST" action="{{ route('register') }}" class="space-y-5">
        @csrf

        <div>
            <label for="name" class="label">Nom complet</label>
            <input
                id="name"
                type="text"
                name="name"
                value="{{ old('name') }}"
                required
                autofocus
                autocomplete="name"
                class="input"
                placeholder="Alpha Keita"
            >
            <x-input-error :messages="$errors->get('name')" />
        </div>

        <div>
            <label for="email" class="label">Adresse e-mail</label>
            <input
                id="email"
                type="email"
                name="email"
                value="{{ old('email') }}"
                required
                autocomplete="username"
                class="input"
                placeholder="vous@exemple.com"
            >
            <x-input-error :messages="$errors->get('email')" />
        </div>

        <div x-data="{ isVisible: false }">
            <label for="password" class="label">Mot de passe</label>

            <div class="relative">
                <input
                    id="password"
                    :type="isVisible ? 'text' : 'password'"
                    type="password"
                    name="password"
                    required
                    autocomplete="new-password"
                    class="input pr-11"
                    placeholder="••••••••"
                >

                <button
                    type="button"
                    @click="isVisible = ! isVisible"
                    class="absolute right-1 top-1/2 flex h-9 w-9 -translate-y-1/2 items-center justify-center rounded-md text-ink-400 transition hover:text-ink-700 dark:hover:text-ink-200"
                    :aria-label="isVisible ? 'Masquer le mot de passe' : 'Afficher le mot de passe'"
                >
                    <x-icon name="eye" class="h-4 w-4" x-show="! isVisible" />
                    <x-icon name="eye-off" class="h-4 w-4" x-show="isVisible" x-cloak />
                </button>
            </div>

            <p class="field-hint">8 caractères minimum.</p>
            <x-input-error :messages="$errors->get('password')" />
        </div>

        <div>
            <label for="password_confirmation" class="label">Confirmer le mot de passe</label>
            <input
                id="password_confirmation"
                type="password"
                name="password_confirmation"
                required
                autocomplete="new-password"
                class="input"
                placeholder="••••••••"
            >
            <x-input-error :messages="$errors->get('password_confirmation')" />
        </div>

        <button type="submit" class="btn-primary btn-block">
            Créer mon compte
            <x-icon name="arrow-right" class="h-4 w-4" />
        </button>
    </form>

    <p class="mt-8 text-center text-sm text-ink-600 dark:text-ink-400">
        Vous avez déjà un compte&nbsp;?
        <a href="{{ route('login') }}" class="link">Se connecter</a>
    </p>
</x-guest-layout>
