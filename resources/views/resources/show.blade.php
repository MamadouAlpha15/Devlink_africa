{{--
    Détail d'une ressource.

    Variables : $resource, $reviews, $myReview, $comments, $hasLiked,
    $canInteract, $canComment, $canUpdate. Voir docs/front-end/ressources.md.

    Deux colonnes : à gauche ce qu'on lit (description, avis, commentaires),
    à droite ce sur quoi on agit (télécharger, liens, auteur).
--}}
@php
    // `github_url` et `demo_url` sont saisis librement par l'auteur. Rendus
    // tels quels dans un `href`, un `javascript:` s'exécuterait au clic sur
    // notre domaine. On n'accepte donc que http et https — la validation
    // serveur porte sur la forme de l'URL, pas sur son schéma.
    $allowedSchemes = ['http', 'https'];

    $isSafeUrl = function (?string $url) use ($allowedSchemes): bool {
        if (blank($url)) {
            return false;
        }

        $scheme = parse_url($url, PHP_URL_SCHEME);

        return is_string($scheme) && in_array(strtolower($scheme), $allowedSchemes, true);
    };

    $externalLinks = collect([
        ['label' => 'Code source', 'icon' => 'github', 'url' => $resource->github_url],
        ['label' => 'Voir la démo', 'icon' => 'external-link', 'url' => $resource->demo_url],
    ])->filter(fn ($link) => $isSafeUrl($link['url']));

    $isPublished = $resource->status === \App\Enums\ResourceStatus::Approved;
@endphp

<x-app-layout :title="$resource->title">
    <div class="mx-auto max-w-5xl px-4 py-8 sm:px-6 lg:px-8 lg:py-10">

        <x-page-header
            :title="$resource->title"
            :back-link="route('resources.index')"
            back-label="Toutes les ressources"
        >
            {{-- Sous-titre riche : passé par le slot par défaut, car la prop
                 `subtitle` du composant ne reçoit que du texte. --}}
            <p class="mt-1.5 flex flex-wrap items-center gap-x-2 gap-y-1 text-sm text-ink-600 dark:text-ink-400">
                <span class="badge-neutral">{{ $resource->type->label() }}</span>
                <span>par</span>
                <a href="{{ route('devs.show', $resource->user) }}" class="link">{{ $resource->user->name }}</a>
                @if ($resource->country)
                    <span class="text-ink-300 dark:text-ink-700" aria-hidden="true">·</span>
                    <x-country-label :country="$resource->country" class="text-sm" />
                @endif
                <span class="text-ink-300 dark:text-ink-700" aria-hidden="true">·</span>
                <span>{{ $resource->created_at->diffForHumans() }}</span>
            </p>

            <x-slot name="actions">
                @if ($resource->hasFile())
                    <a href="{{ route('resources.download', $resource) }}" class="btn-primary btn-sm">
                        <x-icon name="download" class="h-4 w-4" />
                        Télécharger
                    </a>
                @endif

                {{--
                    J'aime : trois états distincts. Déjà aimé → on peut retirer.
                    Autorisé mais pas encore aimé → on peut aimer. Ni l'un ni
                    l'autre (visiteur, ou auteur de la ressource) → le compteur
                    seul, sans bouton mort.
                --}}
                @if ($hasLiked)
                    <form method="POST" action="{{ route('resources.unlike', $resource) }}">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn-secondary btn-sm" title="Retirer mon j'aime">
                            <x-icon name="heart-filled" class="h-4 w-4 text-red-500" />
                            <span class="tabular">{{ $resource->likes_count }}</span>
                        </button>
                    </form>
                @elseif ($canInteract)
                    <form method="POST" action="{{ route('resources.like', $resource) }}">
                        @csrf
                        <button type="submit" class="btn-secondary btn-sm">
                            <x-icon name="heart" class="h-4 w-4" />
                            J'aime
                            <span class="tabular text-ink-500 dark:text-ink-400">{{ $resource->likes_count }}</span>
                        </button>
                    </form>
                @endif

                @if ($canUpdate)
                    <a href="{{ route('resources.edit', $resource) }}" class="btn-secondary btn-sm">
                        <x-icon name="pencil" class="h-4 w-4" />
                        Modifier
                    </a>
                @endif

                @include('partials.favorite-button', ['type' => 'resource', 'model' => $resource])
            </x-slot>
        </x-page-header>

        <x-flash class="mt-6" />

        {{-- Ressource non publiée : le dire franchement, et à qui elle est visible. --}}
        @unless ($isPublished)
            <div class="alert-warning mt-4">
                <x-icon name="eye-off" class="mt-0.5 h-4 w-4 shrink-0" />
                <div>
                    <p class="font-medium">{{ $resource->status->label() }}</p>
                    <p class="mt-0.5">Cette ressource n'est visible que par vous et les administrateurs.</p>
                </div>
            </div>
        @endunless

        <div class="mt-8 grid gap-6 lg:grid-cols-[minmax(0,1fr)_17rem] lg:gap-8">

            {{-- ==========  Contenu  ========== --}}
            <div class="min-w-0 space-y-6">

                @if ($resource->imageUrl())
                    <div class="overflow-hidden rounded-xl border border-ink-200 bg-ink-100 dark:border-ink-800 dark:bg-ink-800">
                        <img src="{{ $resource->imageUrl() }}" alt="" class="max-h-80 w-full object-cover">
                    </div>
                @endif

                <section class="panel">
                    <x-section-heading title="Description" />

                    <p class="body-text mt-4 whitespace-pre-line">{{ $resource->description }}</p>

                    @if ($resource->skills->isNotEmpty() || $resource->tags->isNotEmpty())
                        <div class="mt-5 space-y-4 border-t border-ink-200 pt-5 dark:border-ink-800">
                            @if ($resource->skills->isNotEmpty())
                                <div>
                                    <p class="caption">Technologies</p>
                                    <x-skill-chips :skills="$resource->skills" :limit="99" class="mt-2" />
                                </div>
                            @endif

                            @if ($resource->tags->isNotEmpty())
                                <div>
                                    <p class="caption">Tags</p>
                                    <div class="mt-2 flex flex-wrap gap-1.5">
                                        @foreach ($resource->tags as $tag)
                                            <a href="{{ route('resources.index', ['tag' => $tag->slug]) }}" class="chip-button">
                                                #{{ $tag->name }}
                                            </a>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        </div>
                    @endif
                </section>

                {{-- ==========  Avis  ========== --}}
                <section class="panel">
                    <x-section-heading title="Avis" :count="$reviews->count()">
                        @if ($resource->rating_avg)
                            <x-slot name="action">
                                <x-star-rating :value="$resource->rating_avg" show-value />
                            </x-slot>
                        @endif
                    </x-section-heading>

                    @if ($canInteract)
                        {{--
                            Notation : cinq vrais boutons radio, masqués
                            visuellement mais bien présents. Le clavier
                            fonctionne, et sans JavaScript on coche une étoile
                            comme on coche une case. Alpine ne sert qu'à
                            l'aperçu au survol.
                        --}}
                        <form
                            method="POST"
                            action="{{ route('resources.reviews.store', $resource) }}"
                            class="mt-5 space-y-3 border-b border-ink-200 pb-5 dark:border-ink-800"
                        >
                            @csrf

                            <fieldset x-data="{ rating: {{ (int) old('rating', $myReview?->rating ?? 0) }}, preview: 0 }">
                                <legend class="label">Votre note</legend>

                                <div class="mt-1.5 flex items-center gap-0.5" @mouseleave="preview = 0">
                                    @foreach (range(1, 5) as $star)
                                        <label class="cursor-pointer p-0.5" @mouseenter="preview = {{ $star }}">
                                            <input
                                                type="radio"
                                                name="rating"
                                                value="{{ $star }}"
                                                x-model.number="rating"
                                                class="peer sr-only"
                                                required
                                                @checked(old('rating', $myReview?->rating) == $star)
                                            >
                                            <span class="sr-only">{{ $star }} {{ $star > 1 ? 'étoiles' : 'étoile' }}</span>

                                            {{-- La couleur de repos est portée par ce parent : posée
                                                 sur le SVG, elle entrerait en conflit avec
                                                 `text-amber-500`, et c'est alors l'ordre dans la
                                                 feuille CSS qui tranche — pas celui de l'attribut. --}}
                                            <span class="block rounded text-ink-300 transition peer-focus-visible:ring-2 peer-focus-visible:ring-brand-600/40 dark:text-ink-600">
                                                <x-icon
                                                    name="star-filled"
                                                    class="h-7 w-7 transition-colors"
                                                    ::class="(preview || rating) >= {{ $star }} ? 'text-amber-500' : ''"
                                                />
                                            </span>
                                        </label>
                                    @endforeach
                                </div>

                                <x-input-error :messages="$errors->get('rating')" class="mt-2" />
                            </fieldset>

                            <div>
                                <label for="review-comment" class="label">Commentaire <span class="label-hint">facultatif</span></label>
                                <textarea
                                    id="review-comment"
                                    name="comment"
                                    rows="2"
                                    maxlength="1000"
                                    class="textarea mt-1.5"
                                    placeholder="Très bonne API, documentation claire."
                                >{{ old('comment', $myReview?->comment) }}</textarea>
                                <x-input-error :messages="$errors->get('comment')" class="mt-2" />
                            </div>

                            <div class="flex flex-wrap items-center gap-2">
                                <button type="submit" class="btn-primary btn-sm">
                                    {{ $myReview ? 'Modifier ma note' : 'Publier ma note' }}
                                </button>

                                @if ($myReview)
                                    <button
                                        type="submit"
                                        form="delete-my-review"
                                        class="btn-danger-ghost btn-sm"
                                    >
                                        Supprimer ma note
                                    </button>
                                @endif
                            </div>
                        </form>

                        @if ($myReview)
                            {{-- Hors du formulaire de notation : deux formulaires
                                 imbriqués ne sont pas valides en HTML. --}}
                            <form id="delete-my-review" method="POST" action="{{ route('resources.reviews.destroy', $resource) }}" class="hidden">
                                @csrf
                                @method('DELETE')
                            </form>
                        @endif
                    @endif

                    @forelse ($reviews as $review)
                        @if ($loop->first)
                            <ul class="divide-y divide-ink-100 dark:divide-ink-800/70">
                        @endif

                        <li class="flex gap-3 py-4 last:pb-0">
                            <x-avatar :user="$review->user" size="sm" class="shrink-0" />

                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-center gap-x-2 gap-y-1">
                                    <a href="{{ route('devs.show', $review->user) }}" class="text-sm font-medium text-ink-900 hover:underline dark:text-white">
                                        {{ $review->user->name }}
                                    </a>
                                    <x-star-rating :value="$review->rating" size="h-3.5 w-3.5" />
                                    <span class="text-xs text-ink-500">{{ $review->created_at->diffForHumans() }}</span>
                                </div>

                                @if ($review->comment)
                                    <p class="mt-1.5 whitespace-pre-line text-sm leading-relaxed text-ink-700 dark:text-ink-300">
                                        {{ $review->comment }}
                                    </p>
                                @endif
                            </div>
                        </li>

                        @if ($loop->last)
                            </ul>
                        @endif
                    @empty
                        <p class="muted mt-5">Pas encore d'avis sur cette ressource.</p>
                    @endforelse
                </section>

                {{-- ==========  Commentaires  ========== --}}
                <section class="panel">
                    <x-section-heading title="Commentaires" :count="$comments->count()" />

                    @forelse ($comments as $comment)
                        @if ($loop->first)
                            <ul class="mt-5 divide-y divide-ink-100 dark:divide-ink-800/70">
                        @endif

                        <li class="flex gap-3 py-4 first:pt-0 last:pb-0">
                            <x-avatar :user="$comment->user" size="sm" class="shrink-0" />

                            <div class="min-w-0 flex-1">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="flex flex-wrap items-center gap-x-2 gap-y-1">
                                        <a href="{{ route('devs.show', $comment->user) }}" class="text-sm font-medium text-ink-900 hover:underline dark:text-white">
                                            {{ $comment->user->name }}
                                        </a>
                                        <span class="text-xs text-ink-500">{{ $comment->created_at->diffForHumans() }}</span>
                                    </div>

                                    @can('delete', $comment)
                                        <form
                                            method="POST"
                                            action="{{ route('comments.destroy', $comment) }}"
                                            x-data="confirmAction"
                                        >
                                            @csrf
                                            @method('DELETE')
                                            {{-- Confirmation en deux temps, comme ailleurs
                                                 dans l'application : pas de window.confirm. --}}
                                            <button
                                                type="button"
                                                x-show="! isConfirming"
                                                @click="ask()"
                                                class="btn btn-ghost btn-square btn-xs text-ink-400 hover:text-red-600 dark:hover:text-red-400"
                                                aria-label="Supprimer le commentaire"
                                            >
                                                <x-icon name="trash" class="h-3.5 w-3.5" />
                                            </button>

                                            <span x-show="isConfirming" x-cloak class="inline-flex items-center gap-1">
                                                <button type="submit" class="btn-danger btn-xs">Supprimer</button>
                                                <button type="button" @click="cancel()" class="btn-ghost btn-xs">Non</button>
                                            </span>
                                        </form>
                                    @endcan
                                </div>

                                <p class="mt-1 whitespace-pre-line text-sm leading-relaxed text-ink-700 dark:text-ink-300">
                                    {{ $comment->body }}
                                </p>
                            </div>
                        </li>

                        @if ($loop->last)
                            </ul>
                        @endif
                    @empty
                        <p class="muted mt-5">Aucun commentaire pour l'instant.</p>
                    @endforelse

                    @if ($canComment)
                        <form
                            method="POST"
                            action="{{ route('resources.comments.store', $resource) }}"
                            class="mt-5 space-y-3 border-t border-ink-200 pt-5 dark:border-ink-800"
                            x-data="charCounter(@js(old('body', '')), 2000)"
                        >
                            @csrf

                            <div>
                                <label for="comment-body" class="label">Votre commentaire</label>
                                <textarea
                                    id="comment-body"
                                    name="body"
                                    rows="3"
                                    maxlength="2000"
                                    required
                                    class="textarea mt-1.5"
                                    placeholder="Une question, un retour d'usage…"
                                    x-model="value"
                                >{{ old('body') }}</textarea>

                                <div class="mt-1.5 flex items-start justify-between gap-3">
                                    <x-input-error :messages="$errors->get('body')" />
                                    <p
                                        class="shrink-0 text-xs"
                                        :class="isNearLimit ? 'font-medium text-amber-600 dark:text-amber-400' : 'text-ink-500'"
                                    >
                                        <span class="tabular" x-text="length">0</span> / 2000
                                    </p>
                                </div>
                            </div>

                            <button type="submit" class="btn-primary btn-sm">Commenter</button>
                        </form>
                    @elseif (! auth()->check())
                        <p class="muted mt-5 border-t border-ink-200 pt-5 dark:border-ink-800">
                            <a href="{{ route('login') }}" class="link">Connectez-vous</a> pour commenter cette ressource.
                        </p>
                    @endif
                </section>
            </div>

            {{-- ==========  Colonne latérale  ========== --}}
            <aside class="space-y-4 lg:sticky lg:top-24 lg:h-fit">

                {{-- Les trois chiffres, en relevé : plus lisibles alignés qu'en ligne. --}}
                <div class="card p-4">
                    <p class="caption">En chiffres</p>

                    <dl class="mt-3 divide-y divide-ink-100 dark:divide-ink-800/70">
                        <div class="flex items-center justify-between gap-3 py-2 first:pt-0">
                            <dt class="flex items-center gap-2 text-sm text-ink-600 dark:text-ink-400">
                                <x-icon name="download" class="h-4 w-4 text-ink-400" />
                                Téléchargements
                            </dt>
                            <dd class="tabular text-sm font-semibold text-ink-900 dark:text-white">{{ $resource->downloads_count }}</dd>
                        </div>

                        <div class="flex items-center justify-between gap-3 py-2">
                            <dt class="flex items-center gap-2 text-sm text-ink-600 dark:text-ink-400">
                                <x-icon name="heart" class="h-4 w-4 text-ink-400" />
                                J'aime
                            </dt>
                            <dd class="tabular text-sm font-semibold text-ink-900 dark:text-white">{{ $resource->likes_count }}</dd>
                        </div>

                        <div class="flex items-center justify-between gap-3 py-2 last:pb-0">
                            <dt class="flex items-center gap-2 text-sm text-ink-600 dark:text-ink-400">
                                <x-icon name="star" class="h-4 w-4 text-ink-400" />
                                Note
                            </dt>
                            <dd class="tabular text-sm font-semibold text-ink-900 dark:text-white">
                                {{ $resource->rating_avg ? number_format($resource->rating_avg, 1, ',', ' ').' / 5' : '—' }}
                            </dd>
                        </div>
                    </dl>
                </div>

                {{--
                    Liens sortants. `noreferrer` en plus de `noopener` : sans
                    lui, le site visité reçoit l'URL de la page d'où l'on vient.
                --}}
                @if ($externalLinks->isNotEmpty())
                    <div class="card p-4">
                        <p class="caption">Liens</p>

                        <div class="mt-3 space-y-1">
                            @foreach ($externalLinks as $link)
                                <a
                                    href="{{ $link['url'] }}"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="-mx-2 flex items-center gap-2.5 rounded-lg px-2 py-2 text-sm font-medium text-ink-700 transition-colors hover:bg-ink-50 hover:text-ink-900 dark:text-ink-300 dark:hover:bg-ink-800 dark:hover:text-white"
                                >
                                    <x-icon :name="$link['icon']" class="h-4 w-4 shrink-0 text-ink-400" />
                                    <span class="min-w-0 flex-1 truncate">{{ $link['label'] }}</span>
                                    <x-icon name="arrow-up-right" class="h-3.5 w-3.5 shrink-0 text-ink-400" />
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- L'auteur : on vient souvent voir qui a publié. --}}
                <div class="card p-4">
                    <p class="caption">Publié par</p>

                    <a
                        href="{{ route('devs.show', $resource->user) }}"
                        class="-mx-2 mt-3 flex items-center gap-3 rounded-lg px-2 py-2 transition-colors hover:bg-ink-50 dark:hover:bg-ink-800/60"
                    >
                        <x-avatar :user="$resource->user" size="md" class="shrink-0" />

                        <div class="min-w-0">
                            <p class="truncate text-sm font-medium text-ink-900 dark:text-white">{{ $resource->user->name }}</p>
                            <x-country-label :country="$resource->user->country" class="mt-0.5 text-xs" />
                        </div>
                    </a>
                </div>
            </aside>
        </div>
    </div>
</x-app-layout>
