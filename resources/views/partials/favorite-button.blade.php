{{--
    Bouton Favori réutilisable.

    @include('partials.favorite-button', ['type' => 'developer' | 'resource' | 'project', 'model' => $model])

    Le marque-page plutôt que l'étoile : l'étoile sert déjà à noter les
    ressources, et deux étoiles pour deux sens différents sur la même page
    rendraient la note illisible. Rempli = enregistré, contour = à enregistrer.

    Rien n'est affiché à un visiteur, ni sur son propre profil : un bouton
    qu'on ne peut pas actionner n'a pas à occuper de place.
--}}
@auth
    @unless ($type === 'developer' && auth()->user()->is($model))
        @php($isFavorited = auth()->user()->hasFavorited($model))

        <form method="POST" action="{{ route($isFavorited ? 'favorites.destroy' : 'favorites.store', [$type, $model->getKey()]) }}">
            @csrf

            @if ($isFavorited)
                @method('DELETE')
            @endif

            <button
                type="submit"
                class="btn-secondary btn-sm"
                title="{{ $isFavorited ? 'Retirer de mes favoris' : 'Ajouter à mes favoris' }}"
            >
                <x-icon
                    :name="$isFavorited ? 'bookmark-filled' : 'bookmark'"
                    class="h-4 w-4 {{ $isFavorited ? 'text-brand-600 dark:text-brand-400' : '' }}"
                />
                <span class="hidden sm:inline">{{ $isFavorited ? 'Enregistré' : 'Enregistrer' }}</span>
            </button>
        </form>
    @endunless
@endauth
