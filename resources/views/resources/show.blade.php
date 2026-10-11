{{--
    Détail d'une ressource — version fonctionnelle minimale (backend).
    Le front-end la restylera : voir docs/front-end/ressources.md.
--}}
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ $resource->title }}</h2>
        <p class="text-gray-600">
            {{ $resource->type->label() }}
            · par <a href="{{ route('devs.show', $resource->user) }}" class="underline">{{ $resource->user->name }}</a>
            @if ($resource->country) · {{ $resource->country->flag }} {{ $resource->country->name }} @endif
            · {{ $resource->created_at->diffForHumans() }}
        </p>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto px-4 space-y-6">

            @if (session('status'))
                <div class="p-4 bg-green-50 text-green-800 rounded">{{ session('status') }}</div>
            @endif
            @if ($resource->status !== \App\Enums\ResourceStatus::Approved)
                <div class="p-4 bg-yellow-50 text-yellow-800 rounded">Statut : {{ $resource->status->label() }} — visible seulement par vous et les administrateurs.</div>
            @endif

            @if ($resource->imageUrl())
                <img src="{{ $resource->imageUrl() }}" alt="" class="w-full max-h-80 object-cover rounded">
            @endif

            {{-- Actions --}}
            <div class="flex flex-wrap gap-3 items-center">
                @if ($resource->hasFile())
                    <a href="{{ route('resources.download', $resource) }}" class="px-4 py-2 bg-gray-800 text-white rounded">Télécharger</a>
                @endif
                @if ($resource->github_url) <a href="{{ $resource->github_url }}" target="_blank" rel="noopener" class="underline">GitHub</a> @endif
                @if ($resource->demo_url) <a href="{{ $resource->demo_url }}" target="_blank" rel="noopener" class="underline">Démo</a> @endif

                @if ($hasLiked)
                    <form method="POST" action="{{ route('resources.unlike', $resource) }}">@csrf @method('DELETE')
                        <x-secondary-button type="submit">♥ Je n'aime plus ({{ $resource->likes_count }})</x-secondary-button>
                    </form>
                @elseif ($canInteract)
                    <form method="POST" action="{{ route('resources.like', $resource) }}">@csrf
                        <x-primary-button>♡ J'aime ({{ $resource->likes_count }})</x-primary-button>
                    </form>
                @else
                    <span>♥ {{ $resource->likes_count }}</span>
                @endif

                <span>★ {{ $resource->rating_avg ? number_format($resource->rating_avg, 1, ',', ' ').' / 5' : 'pas encore noté' }} ({{ $reviews->count() }} avis)</span>
                <span>⬇ {{ $resource->downloads_count }} téléchargements</span>

                @if ($canUpdate)
                    <a href="{{ route('resources.edit', $resource) }}" class="underline">Modifier</a>
                @endif
                @include('partials.favorite-button', ['type' => 'resource', 'model' => $resource])
                {{-- À venir : Signaler --}}
            </div>

            <div class="p-6 bg-white shadow rounded">
                <p class="whitespace-pre-line">{{ $resource->description }}</p>
                <p class="mt-4 text-sm">Technologies : {{ $resource->skills->pluck('name')->join(', ') ?: '—' }}</p>
                <p class="text-sm">
                    @foreach ($resource->tags as $tag)
                        <a href="{{ route('resources.index', ['tag' => $tag->slug]) }}" class="underline">#{{ $tag->name }}</a>
                    @endforeach
                </p>
            </div>

            {{-- Notation (1 à 5 étoiles) --}}
            <div class="p-6 bg-white shadow rounded space-y-3">
                <h3 class="font-semibold">Avis</h3>

                @if ($canInteract)
                    <form method="POST" action="{{ route('resources.reviews.store', $resource) }}" class="space-y-2 border-b pb-3">
                        @csrf
                        <div class="flex gap-3">
                            @foreach (range(1, 5) as $star)
                                <label class="text-sm"><input type="radio" name="rating" value="{{ $star }}" @checked(old('rating', $myReview?->rating) == $star) required> {{ $star }} ★</label>
                            @endforeach
                        </div>
                        <textarea name="comment" rows="2" maxlength="1000" class="block w-full border-gray-300 rounded" placeholder="Très bonne API pour Laravel.">{{ old('comment', $myReview?->comment) }}</textarea>
                        <x-input-error :messages="$errors->get('rating')" />
                        <x-primary-button>{{ $myReview ? 'Modifier ma note' : 'Noter' }}</x-primary-button>
                    </form>
                    @if ($myReview)
                        <form method="POST" action="{{ route('resources.reviews.destroy', $resource) }}">@csrf @method('DELETE')
                            <button class="text-sm text-red-600">Supprimer ma note</button>
                        </form>
                    @endif
                @endif

                @forelse ($reviews as $review)
                    <div class="text-sm">
                        <strong>{{ str_repeat('★', $review->rating) }}{{ str_repeat('☆', 5 - $review->rating) }}</strong>
                        {{ $review->user->name }} · {{ $review->created_at->diffForHumans() }}
                        @if ($review->comment) <p class="italic">« {{ $review->comment }} »</p> @endif
                    </div>
                @empty
                    <p class="text-sm text-gray-600">Pas encore d'avis.</p>
                @endforelse
            </div>

            {{-- Commentaires --}}
            <div class="p-6 bg-white shadow rounded space-y-3">
                <h3 class="font-semibold">Commentaires ({{ $comments->count() }})</h3>
                @foreach ($comments as $comment)
                    <div class="text-sm">
                        <strong>{{ $comment->user->name }}</strong> · {{ $comment->created_at->diffForHumans() }}
                        <p class="whitespace-pre-line">{{ $comment->body }}</p>
                        @can('delete', $comment)
                            <form method="POST" action="{{ route('comments.destroy', $comment) }}">@csrf @method('DELETE')
                                <button class="text-xs text-red-600">Supprimer</button>
                            </form>
                        @endcan
                    </div>
                @endforeach

                @if ($canComment)
                    <form method="POST" action="{{ route('resources.comments.store', $resource) }}" class="space-y-2 pt-2 border-t">
                        @csrf
                        <textarea name="body" rows="2" maxlength="2000" class="block w-full border-gray-300 rounded" placeholder="Votre commentaire…" required>{{ old('body') }}</textarea>
                        <x-input-error :messages="$errors->get('body')" />
                        <x-primary-button>Commenter</x-primary-button>
                    </form>
                @elseif (! auth()->check())
                    <a href="{{ route('login') }}" class="text-sm underline">Connectez-vous pour commenter</a>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
