{{--
    Bouton Favori réutilisable.
    @include('partials.favorite-button', ['type' => 'developer' | 'resource' | 'project', 'model' => $model])
--}}
@auth
    @unless ($type === 'developer' && auth()->user()->is($model))
        @if (auth()->user()->hasFavorited($model))
            <form method="POST" action="{{ route('favorites.destroy', [$type, $model->getKey()]) }}">
                @csrf
                @method('DELETE')
                <x-secondary-button type="submit">★ Dans mes favoris</x-secondary-button>
            </form>
        @else
            <form method="POST" action="{{ route('favorites.store', [$type, $model->getKey()]) }}">
                @csrf
                <x-secondary-button type="submit">☆ Ajouter aux favoris</x-secondary-button>
            </form>
        @endif
    @endunless
@endauth
