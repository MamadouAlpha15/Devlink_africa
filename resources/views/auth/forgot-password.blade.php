{{-- Demande de lien de réinitialisation. Champ : email. --}}
<x-guest-layout title="Mot de passe oublié">
    <x-slot name="heading">Mot de passe oublié</x-slot>
    <x-slot name="subheading">Indiquez votre adresse e-mail : nous vous enverrons un lien pour choisir un nouveau mot de passe.</x-slot>

    <x-auth-session-status class="mb-6" :status="session('status')" />

    <form method="POST" action="{{ route('password.email') }}" class="space-y-5">
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
                class="input"
                placeholder="vous@exemple.com"
            >
            <x-input-error :messages="$errors->get('email')" />
        </div>

        <button type="submit" class="btn-primary btn-block">
            <x-icon name="mail" class="h-4 w-4" />
            Envoyer le lien
        </button>
    </form>

    <p class="mt-8 text-center text-sm text-ink-600 dark:text-ink-400">
        <a href="{{ route('login') }}" class="link">Retour à la connexion</a>
    </p>
</x-guest-layout>
