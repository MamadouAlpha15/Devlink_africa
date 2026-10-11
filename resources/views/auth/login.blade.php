{{-- Connexion. Champs Breeze : email, password, remember. --}}
<x-guest-layout title="Connexion">
    <x-slot name="heading">Content de vous revoir</x-slot>
    <x-slot name="subheading">Connectez-vous pour retrouver vos projets et vos collaborations.</x-slot>

    <x-auth-session-status class="mb-6" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}" class="space-y-5">
        @csrf

        <div>
            <label for="email" class="label">Adresse e-mail</label>
            <input
                id="email"
                type="email"
                name="email"
                value="{{ old('email') }}"
                required
                autofocus
                autocomplete="username"
                class="input"
                placeholder="vous@exemple.com"
            >
            <x-input-error :messages="$errors->get('email')" />
        </div>

        <div>
            <div class="flex items-baseline justify-between gap-3">
                <label for="password" class="label">Mot de passe</label>

                @if (Route::has('password.request'))
                    <a href="{{ route('password.request') }}" class="link text-xs">Mot de passe oublié&nbsp;?</a>
                @endif
            </div>

            {{-- Afficher / masquer le mot de passe. --}}
            <div class="relative" x-data="{ isVisible: false }">
                <input
                    id="password"
                    :type="isVisible ? 'text' : 'password'"
                    type="password"
                    name="password"
                    required
                    autocomplete="current-password"
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

            <x-input-error :messages="$errors->get('password')" />
        </div>

        <label class="flex cursor-pointer items-center gap-2.5">
            <input id="remember_me" type="checkbox" name="remember" class="checkbox">
            <span class="text-sm text-ink-600 dark:text-ink-400">Rester connecté</span>
        </label>

        <button type="submit" class="btn-primary btn-block">
            Se connecter
            <x-icon name="arrow-right" class="h-4 w-4" />
        </button>
    </form>

    <p class="mt-8 text-center text-sm text-ink-600 dark:text-ink-400">
        Pas encore de compte&nbsp;?
        <a href="{{ route('register') }}" class="link">Créer un compte</a>
    </p>
</x-guest-layout>
