{{--
    Pagination — remplace la vue Tailwind par défaut de Laravel pour
    qu'elle suive la charte et le thème sombre.

    Les filtres de recherche sont conservés : `links()` génère déjà les URL
    avec la chaîne de requête courante.
--}}
@if ($paginator->hasPages())
    <nav role="navigation" aria-label="Pagination" class="flex items-center justify-between gap-4">

        {{-- Résumé (masqué sur mobile pour laisser la place aux boutons). --}}
        <p class="hidden text-sm text-ink-600 sm:block dark:text-ink-400">
            <span class="tabular font-medium text-ink-900 dark:text-white">{{ $paginator->firstItem() }}</span>
            –
            <span class="tabular font-medium text-ink-900 dark:text-white">{{ $paginator->lastItem() }}</span>
            sur
            <span class="tabular font-medium text-ink-900 dark:text-white">{{ $paginator->total() }}</span>
        </p>

        <div class="flex flex-1 items-center justify-between gap-2 sm:flex-none">
            {{-- Précédent --}}
            @if ($paginator->onFirstPage())
                <span class="btn btn-secondary btn-sm cursor-not-allowed opacity-50" aria-disabled="true">
                    <x-icon name="chevron-left" class="h-4 w-4" />
                    <span class="hidden sm:inline">Précédent</span>
                </span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="btn-secondary btn-sm">
                    <x-icon name="chevron-left" class="h-4 w-4" />
                    <span class="hidden sm:inline">Précédent</span>
                </a>
            @endif

            {{-- Numéros de page (grand écran uniquement). --}}
            <div class="hidden items-center gap-1 md:flex">
                @foreach ($elements as $element)
                    {{-- Séparateur « … » --}}
                    @if (is_string($element))
                        <span class="px-2 text-sm text-ink-400 dark:text-ink-600">{{ $element }}</span>
                    @endif

                    @if (is_array($element))
                        @foreach ($element as $page => $url)
                            @if ($page == $paginator->currentPage())
                                <span
                                    aria-current="page"
                                    class="tabular inline-flex h-9 min-w-9 items-center justify-center rounded-lg bg-brand-600 px-3 text-sm font-semibold text-white dark:bg-brand-500"
                                >{{ $page }}</span>
                            @else
                                <a
                                    href="{{ $url }}"
                                    aria-label="Page {{ $page }}"
                                    class="tabular inline-flex h-9 min-w-9 items-center justify-center rounded-lg px-3 text-sm font-medium text-ink-600 transition hover:bg-ink-100 hover:text-ink-900 dark:text-ink-400 dark:hover:bg-ink-800 dark:hover:text-white"
                                >{{ $page }}</a>
                            @endif
                        @endforeach
                    @endif
                @endforeach
            </div>

            {{-- Indicateur compact sur mobile et tablette. --}}
            <span class="tabular text-sm text-ink-600 md:hidden dark:text-ink-400">
                Page {{ $paginator->currentPage() }} / {{ $paginator->lastPage() }}
            </span>

            {{-- Suivant --}}
            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="btn-secondary btn-sm">
                    <span class="hidden sm:inline">Suivant</span>
                    <x-icon name="chevron-right" class="h-4 w-4" />
                </a>
            @else
                <span class="btn btn-secondary btn-sm cursor-not-allowed opacity-50" aria-disabled="true">
                    <span class="hidden sm:inline">Suivant</span>
                    <x-icon name="chevron-right" class="h-4 w-4" />
                </span>
            @endif
        </div>
    </nav>
@endif
