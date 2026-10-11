{{-- Nom affiché et adresse e-mail du compte (PATCH /profile). --}}
<section>
    <x-section-heading title="Identifiants de connexion" />

    <p class="muted mt-3">
        Le nom et l'adresse e-mail de votre compte. Votre profil public se modifie
        depuis <a href="{{ route('developer-profile.edit') }}" class="link">Mon profil développeur</a>.
    </p>

    {{-- Formulaire séparé, déclenché par le bouton « renvoyer l'e-mail ». --}}
    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
        @csrf
    </form>

    <form method="post" action="{{ route('profile.update') }}" class="mt-5 space-y-5">
        @csrf
        @method('patch')

        <div>
            <label for="name" class="label">Nom</label>
            <input id="name" type="text" name="name" value="{{ old('name', $user->name) }}"
                   required autofocus autocomplete="name" class="input">
            <x-input-error :messages="$errors->get('name')" />
        </div>

        <div>
            <label for="email" class="label">Adresse e-mail</label>
            <input id="email" type="email" name="email" value="{{ old('email', $user->email) }}"
                   required autocomplete="username" class="input">
            <x-input-error :messages="$errors->get('email')" />

            @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                <div class="alert-warning mt-3">
                    <x-icon name="alert-circle" class="mt-px h-4 w-4 shrink-0" />
                    <div class="flex-1">
                        <p class="font-medium">Votre adresse e-mail n'est pas vérifiée.</p>

                        <button form="send-verification" class="mt-1 text-sm font-semibold underline underline-offset-2">
                            Renvoyer l'e-mail de vérification
                        </button>

                        @if (session('status') === 'verification-link-sent')
                            <p class="mt-2 text-sm font-medium text-emerald-700 dark:text-emerald-400">
                                Un nouveau lien vient d'être envoyé à votre adresse.
                            </p>
                        @endif
                    </div>
                </div>
            @endif
        </div>

        <div class="flex items-center gap-4">
            <button type="submit" class="btn-primary">
                <x-icon name="check" class="h-4 w-4" />
                Enregistrer
            </button>

            @if (session('status') === 'profile-updated')
                <p
                    x-data="dismissible(3000)"
                    x-show="isVisible"
                    x-transition.opacity
                    class="inline-flex items-center gap-1.5 text-sm font-medium text-emerald-700 dark:text-emerald-400"
                >
                    <x-icon name="check-circle" class="h-4 w-4" />
                    Enregistré
                </p>
            @endif
        </div>
    </form>
</section>
