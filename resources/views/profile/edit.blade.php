{{--
    Réglages du compte (Breeze) : identifiants de connexion, mot de passe,
    suppression. Le profil public se modifie ailleurs : /profile/developer.
--}}
<x-app-layout title="Mon compte">
    <div class="mx-auto max-w-3xl px-4 py-8 sm:px-6 lg:px-8 lg:py-10">

        <x-page-header
            title="Mon compte"
            subtitle="Adresse e-mail, mot de passe et suppression du compte."
        >
            <x-slot name="actions">
                <a href="{{ route('developer-profile.edit') }}" class="btn-secondary btn-sm">
                    <x-icon name="user" class="h-4 w-4" />
                    Mon profil développeur
                </a>
            </x-slot>
        </x-page-header>

        <div class="mt-8 space-y-6">
            <div class="panel">
                @include('profile.partials.update-profile-information-form')
            </div>

            <div class="panel">
                @include('profile.partials.update-password-form')
            </div>

            <div class="panel border-red-300 dark:border-red-900">
                @include('profile.partials.delete-user-form')
            </div>
        </div>
    </div>
</x-app-layout>
