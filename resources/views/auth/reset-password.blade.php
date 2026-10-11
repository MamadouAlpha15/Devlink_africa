{{-- Choix d'un nouveau mot de passe. Champs : token, email, password, password_confirmation. --}}
<x-guest-layout title="Nouveau mot de passe">
    <x-slot name="heading">Nouveau mot de passe</x-slot>
    <x-slot name="subheading">Choisissez un mot de passe que vous n'utilisez nulle part ailleurs.</x-slot>

    <form method="POST" action="{{ route('password.store') }}" class="space-y-5">
        @csrf

        <input type="hidden" name="token" value="{{ $request->route('token') }}">

        <div>
            <label for="email" class="label">Adresse e-mail</label>
            <input
                id="email"
                type="email"
                name="email"
                value="{{ old('email', $request->email) }}"
                required
                autofocus
                autocomplete="username"
                class="input"
            >
            <x-input-error :messages="$errors->get('email')" />
        </div>

        <div x-data="{ isVisible: false }">
            <label for="password" class="label">Nouveau mot de passe</label>

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
            <x-icon name="check" class="h-4 w-4" />
            Changer mon mot de passe
        </button>
    </form>
</x-guest-layout>
