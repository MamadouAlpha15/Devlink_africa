{{-- Confirmation du mot de passe avant une action sensible. --}}
<x-guest-layout title="Confirmer le mot de passe">
    <x-slot name="heading">Confirmez votre mot de passe</x-slot>
    <x-slot name="subheading">Vous entrez dans une zone sécurisée. Confirmez votre mot de passe pour continuer.</x-slot>

    <form method="POST" action="{{ route('password.confirm') }}" class="space-y-5">
        @csrf

        <div>
            <label for="password" class="label">Mot de passe</label>
            <input
                id="password"
                type="password"
                name="password"
                required
                autocomplete="current-password"
                autofocus
                class="input"
                placeholder="••••••••"
            >
            <x-input-error :messages="$errors->get('password')" />
        </div>

        <button type="submit" class="btn-primary btn-block">
            <x-icon name="lock" class="h-4 w-4" />
            Confirmer
        </button>
    </form>
</x-guest-layout>
