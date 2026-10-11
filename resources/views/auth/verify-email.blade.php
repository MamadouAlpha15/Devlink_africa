{{-- Vérification de l'adresse e-mail après inscription. --}}
<x-guest-layout title="Vérifier mon e-mail">
    <x-slot name="heading">Vérifiez votre adresse e-mail</x-slot>
    <x-slot name="subheading">
        Nous venons de vous envoyer un lien de vérification. Cliquez dessus pour activer votre compte.
    </x-slot>

    @if (session('status') === 'verification-link-sent')
        <div class="alert-success mb-6">
            <x-icon name="check-circle" class="mt-px h-4 w-4 shrink-0" />
            <p class="flex-1 font-medium">
                Un nouveau lien de vérification vient d'être envoyé à votre adresse e-mail.
            </p>
        </div>
    @endif

    <div class="alert-info">
        <x-icon name="info" class="mt-px h-4 w-4 shrink-0" />
        <p class="flex-1">
            Vous ne trouvez pas l'e-mail&nbsp;? Vérifiez vos courriers indésirables,
            puis demandez un nouvel envoi.
        </p>
    </div>

    <div class="mt-6 space-y-3">
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf
            <button type="submit" class="btn-primary btn-block">
                <x-icon name="mail" class="h-4 w-4" />
                Renvoyer l'e-mail de vérification
            </button>
        </form>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="btn-ghost btn-block">
                <x-icon name="logout" class="h-4 w-4" />
                Se déconnecter
            </button>
        </form>
    </div>
</x-guest-layout>
