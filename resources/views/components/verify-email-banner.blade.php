{{--
    Rappel affiché aux membres connectés dont l'e-mail n'est pas vérifié.

    Tant que l'adresse n'est pas confirmée, le profil n'apparaît ni dans
    les recherches ni sur sa page publique, et les fonctionnalités membres
    restent bloquées. Le bouton renvoie le lien de vérification.
--}}
@auth
    @unless (auth()->user()->hasVerifiedEmail())
        <div class="mx-auto max-w-7xl px-4 pt-4 sm:px-6 lg:px-8">
            @if (session('status') === 'verification-link-sent')
                <div class="alert-success" role="status">
                    <x-icon name="check-circle" class="mt-px h-4 w-4 shrink-0" />
                    <p class="flex-1 font-medium">
                        Un nouveau lien de vérification vient d'être envoyé à {{ auth()->user()->email }}.
                    </p>
                </div>
            @else
                <div class="alert-warning flex-col sm:flex-row sm:items-center" role="status">
                    <div class="flex flex-1 items-start gap-3">
                        <x-icon name="mail" class="mt-px h-4 w-4 shrink-0" />
                        <p>
                            <span class="font-medium">Vérifiez votre e-mail pour apparaître sur AfriCommit.</span>
                            Cliquez sur le lien envoyé à {{ auth()->user()->email }}. En attendant, votre profil reste invisible.
                        </p>
                    </div>
                    <form method="POST" action="{{ route('verification.send') }}" class="shrink-0">
                        @csrf
                        <button type="submit" class="btn-secondary btn-sm">Renvoyer le lien</button>
                    </form>
                </div>
            @endif
        </div>
    @endunless
@endauth
