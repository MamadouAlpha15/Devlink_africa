{{--
    Page d'accueil publique.

    Parti pris de mise en page : alignement à gauche, une seule couleur
    d'accent (le bouton principal), pas de carte décorative, et un rythme
    vertical à deux temps — large à l'ouverture d'un chapitre, resserré
    quand une section prolonge la précédente.

    La route `/` ne transmet aucune donnée : les seuls chiffres affichés
    sont ceux qui sont vrais par construction (54 pays, 9 catégories de
    projets, barème DevScore lu dans la configuration).
--}}
@php
    // Compétences les plus demandées : slugs réels de la table `skills`.
    $popularSkills = [
        'Laravel' => 'laravel',
        'Flutter' => 'flutter',
        'React' => 'react',
        'UI/UX' => 'ui-ux',
        'Node.js' => 'node-js',
        'Python' => 'python',
    ];

    // Pays mis en avant : codes ISO et slugs réels de la table `countries`.
    $featuredCountries = [
        ['code' => 'GN', 'name' => 'Guinée', 'slug' => 'guinee'],
        ['code' => 'SN', 'name' => 'Sénégal', 'slug' => 'senegal'],
        ['code' => 'CI', 'name' => "Côte d'Ivoire", 'slug' => 'cote-divoire'],
        ['code' => 'NG', 'name' => 'Nigeria', 'slug' => 'nigeria'],
        ['code' => 'GH', 'name' => 'Ghana', 'slug' => 'ghana'],
        ['code' => 'KE', 'name' => 'Kenya', 'slug' => 'kenya'],
        ['code' => 'CM', 'name' => 'Cameroun', 'slug' => 'cameroun'],
        ['code' => 'MA', 'name' => 'Maroc', 'slug' => 'maroc'],
        ['code' => 'TN', 'name' => 'Tunisie', 'slug' => 'tunisie'],
        ['code' => 'RW', 'name' => 'Rwanda', 'slug' => 'rwanda'],
        ['code' => 'CD', 'name' => 'RD Congo', 'slug' => 'rd-congo'],
        ['code' => 'BF', 'name' => 'Burkina Faso', 'slug' => 'burkina-faso'],
    ];

    // Ce que la plateforme sait faire. Le premier élément porte le poids.
    $capabilities = [
        [
            'title' => 'Chercher par compétence et par pays',
            'text' => "Compétence, pays, ville, niveau, disponibilité, type de profil : les filtres se combinent dans une URL que vous pouvez envoyer telle quelle à votre équipe.",
        ],
        [
            'title' => 'Publier un projet et ses postes',
            'text' => "Décrivez le projet, listez les postes à pourvoir avec le nombre de places, puis traitez demandes et invitations depuis la fiche du projet.",
        ],
        [
            'title' => 'Constituer une équipe',
            'text' => "Un développeur demande à rejoindre, vous acceptez : il devient membre. Vous pouvez promouvoir un co-responsable pour partager la gestion.",
        ],
        [
            'title' => 'Construire une réputation',
            'text' => "Chaque contribution rapporte des points DevScore. Le score apparaît sur votre profil et remonte votre position dans les résultats de recherche.",
        ],
    ];

    // Points réels, lus dans config/devscore.php.
    $scorePoints = config('devscore.points');

    $scoreActions = [
        ['key' => 'project_completed', 'label' => 'Projet mené à son terme'],
        ['key' => 'project_created', 'label' => 'Projet publié'],
        ['key' => 'collaboration', 'label' => 'Collaboration acceptée'],
        ['key' => 'resource_published', 'label' => 'Ressource publiée'],
        ['key' => 'profile_completed', 'label' => 'Profil complété'],
        ['key' => 'resource_liked', 'label' => 'Ressource aimée'],
    ];
@endphp

<x-app-layout>

    {{-- ===================  Ouverture  =================== --}}
    <section class="border-b border-ink-200 bg-white dark:border-ink-800 dark:bg-ink-900">
        <div class="mx-auto max-w-7xl px-4 py-20 sm:px-6 lg:px-8 lg:py-28">
            <div class="grid gap-14 lg:grid-cols-[minmax(0,1fr)_26rem] lg:gap-20">

                <div class="max-w-2xl">
                    <h1 class="reveal text-[2.25rem] font-semibold leading-[1.06] tracking-[-0.03em] text-ink-900 sm:text-[3rem] dark:text-white">
                        Trouvez les développeurs qu'il vous manque, partout en Afrique.
                    </h1>

                    <p class="reveal lead mt-6" style="--reveal-delay: 70ms">
                        AfriCommit réunit les développeurs, designers et porteurs de projets
                        des 54 pays du continent. Cherchez par compétence et par pays, ou
                        décrivez en une phrase le profil dont vous avez besoin.
                    </p>

                    {{-- La recherche est l'action principale : elle est le CTA. --}}
                    <form method="GET" action="{{ route('devs.index') }}" class="reveal mt-10 max-w-xl" style="--reveal-delay: 140ms">
                        <label for="hero-search" class="sr-only">Compétence, ville ou technologie</label>

                        <div class="flex flex-col gap-2 sm:flex-row">
                            <div class="input-group flex-1">
                                <x-icon name="search" />
                                <input
                                    id="hero-search"
                                    type="search"
                                    name="q"
                                    class="input h-12"
                                    placeholder="Laravel, Flutter, Conakry…"
                                    autocomplete="off"
                                >
                            </div>

                            <button type="submit" class="btn-primary btn-lg">Rechercher</button>
                        </div>
                    </form>

                    <div class="reveal mt-4 flex flex-wrap items-center gap-2" style="--reveal-delay: 210ms">
                        <span class="text-xs text-ink-500 dark:text-ink-400">Souvent recherché</span>
                        @foreach ($popularSkills as $label => $slug)
                            <a href="{{ route('devs.index', ['skill' => $slug]) }}" class="chip-button">{{ $label }}</a>
                        @endforeach
                    </div>

                    @guest
                        <p class="reveal mt-10 text-sm text-ink-600 dark:text-ink-400" style="--reveal-delay: 280ms">
                            Vous cherchez plutôt à rejoindre un projet&nbsp;?
                            <a href="{{ route('register') }}" class="link">Créez votre profil</a>
                            — c'est gratuit.
                        </p>
                    @endguest
                </div>

                {{-- Démonstration d'AfriCommit AI : la mécanique réelle, pas
                     de faux profils inventés. --}}
                <div class="reveal card p-5 lg:mt-2" style="--reveal-delay: 180ms">
                    <div class="flex items-center gap-2">
                        <x-icon name="sparkles" class="h-4 w-4 text-brand-600 dark:text-brand-400" />
                        <span class="text-sm font-semibold text-ink-900 dark:text-white">AfriCommit AI</span>
                    </div>

                    <p class="mt-3 text-sm leading-relaxed text-ink-600 dark:text-ink-400">
                        Écrivez votre besoin en français. L'IA en extrait les critères,
                        puis classe les profils de 0 à 100&nbsp;%.
                    </p>

                    <div class="surface-muted mt-4 p-3">
                        <p class="text-sm italic leading-relaxed text-ink-700 dark:text-ink-200">
                            « Je cherche un développeur Flutter en Guinée pour une application de livraison. »
                        </p>
                    </div>

                    <dl class="mt-4 space-y-2.5 text-sm">
                        @foreach ([
                            ['label' => 'Compétence', 'value' => 'Flutter'],
                            ['label' => 'Pays', 'value' => 'Guinée', 'code' => 'GN'],
                            ['label' => 'Type de projet', 'value' => 'Logistique'],
                        ] as $criterion)
                            <div class="flex items-center justify-between gap-4">
                                <dt class="text-ink-500 dark:text-ink-400">{{ $criterion['label'] }}</dt>
                                <dd class="inline-flex items-center gap-1.5 font-medium text-ink-900 dark:text-white">
                                    @isset($criterion['code'])
                                        <x-flag :code="$criterion['code']" />
                                    @endisset
                                    {{ $criterion['value'] }}
                                </dd>
                            </div>
                        @endforeach
                    </dl>

                    <div class="divider mt-4 pt-4">
                        <p class="caption">Barème du pourcentage</p>

                        <div class="mt-3 space-y-2.5">
                            @foreach ([
                                ['label' => 'Compétences demandées', 'points' => 60],
                                ['label' => 'Pays', 'points' => 20],
                                ['label' => 'Projet du même type', 'points' => 10],
                                ['label' => 'Disponibilité', 'points' => 10],
                            ] as $criterion)
                                <div class="flex items-center gap-3">
                                    <span class="w-44 shrink-0 text-xs text-ink-600 dark:text-ink-400">{{ $criterion['label'] }}</span>
                                    <span class="progress-track reveal-bar flex-1" style="--bar-width: {{ $criterion['points'] }}%; --reveal-delay: {{ $loop->index * 90 }}ms">
                                        <span class="progress-bar block" style="width: {{ $criterion['points'] }}%"></span>
                                    </span>
                                    <span class="tabular w-10 shrink-0 text-right text-xs font-medium text-ink-900 dark:text-white">{{ $criterion['points'] }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- ===================  Deux usages  ===================
         Remplace le « comment ça marche » en trois étapes numérotées :
         ce produit a deux publics, c'est cela qu'il faut dire. --}}
    <section class="mx-auto max-w-7xl px-4 py-16 sm:px-6 lg:px-8 lg:py-20">
        <div class="grid gap-12 md:grid-cols-2 md:gap-16">

            <div class="reveal">
                <p class="eyebrow">Vous portez un projet</p>
                <h2 class="section-title mt-3 text-2xl">Recrutez l'équipe qui vous manque</h2>
                <p class="body-text mt-4">
                    Publiez votre projet, listez les postes à pourvoir, et recevez des
                    candidatures. La plateforme vous suggère aussi des développeurs dont
                    les compétences correspondent aux postes que vous avez ouverts.
                </p>

                {{-- La création de projet exige un compte vérifié : on envoie
                     le visiteur vers l'inscription plutôt que vers un refus. --}}
                <a href="{{ auth()->check() ? route('projects.create') : route('register') }}" class="link mt-5 inline-flex items-center gap-1.5 text-sm">
                    Publier un projet
                    <x-icon name="arrow-right" class="h-4 w-4" />
                </a>
            </div>

            <div class="reveal" style="--reveal-delay: 90ms">
                <p class="eyebrow">Vous êtes développeur</p>
                <h2 class="section-title mt-3 text-2xl">Rejoignez un projet qui vous intéresse</h2>
                <p class="body-text mt-4">
                    Complétez votre profil, ajoutez vos compétences et votre disponibilité,
                    puis parcourez les projets ouverts. Chaque collaboration acceptée
                    augmente votre DevScore et votre visibilité.
                </p>

                <a href="{{ route('projects.index') }}" class="link mt-5 inline-flex items-center gap-1.5 text-sm">
                    Parcourir les projets
                    <x-icon name="arrow-right" class="h-4 w-4" />
                </a>
            </div>
        </div>
    </section>

    {{-- ===================  Ce que fait la plateforme  ===================
         Une liste, pas une grille de cartes identiques. --}}
    <section class="border-t border-ink-200 dark:border-ink-800">
        <div class="mx-auto max-w-7xl px-4 py-16 sm:px-6 lg:px-8 lg:py-20">
            <h2 class="max-w-xl text-2xl font-semibold tracking-[-0.02em] sm:text-3xl">
                Ce que vous pouvez faire ici
            </h2>

            <dl class="mt-10 grid gap-x-16 gap-y-8 md:grid-cols-2">
                @foreach ($capabilities as $capability)
                    <div class="reveal border-t border-ink-200 pt-5 dark:border-ink-800" style="--reveal-delay: {{ ($loop->index % 2) * 80 }}ms">
                        <dt class="font-semibold text-ink-900 dark:text-white">{{ $capability['title'] }}</dt>
                        <dd class="body-text mt-2 text-sm">{{ $capability['text'] }}</dd>
                    </div>
                @endforeach
            </dl>
        </div>
    </section>

    {{-- ===================  DevScore  =================== --}}
    <section class="border-t border-ink-200 dark:border-ink-800">
        <div class="mx-auto max-w-7xl px-4 py-16 sm:px-6 lg:px-8 lg:py-20">
            <div class="grid gap-12 lg:grid-cols-[22rem_minmax(0,1fr)] lg:gap-20">

                <div class="reveal">
                    <h2 class="text-2xl font-semibold tracking-[-0.02em] sm:text-3xl">
                        Une réputation qui se construit
                    </h2>
                    <p class="body-text mt-4">
                        Le DevScore mesure ce que vous apportez à la communauté. Il est
                        public, il figure sur votre profil, et il détermine votre position
                        par défaut dans les résultats de recherche.
                    </p>
                </div>

                {{-- Un tableau de valeurs, plus lisible que six cartes. --}}
                <dl class="reveal divide-y divide-ink-200 border-y border-ink-200 dark:divide-ink-800 dark:border-ink-800" style="--reveal-delay: 90ms">
                    @foreach ($scoreActions as $action)
                        <div class="flex items-baseline justify-between gap-6 py-3">
                            <dt class="text-sm text-ink-700 dark:text-ink-300">{{ $action['label'] }}</dt>
                            <dd class="tabular shrink-0 text-sm font-semibold text-ink-900 dark:text-white">
                                +{{ $scorePoints[$action['key']] }}
                            </dd>
                        </div>
                    @endforeach
                </dl>
            </div>
        </div>
    </section>

    {{-- ===================  Pays  ===================
         S'ouvre sur le chiffre, pas sur un sur-titre de plus. --}}
    <section class="border-t border-ink-200 dark:border-ink-800">
        <div class="mx-auto max-w-7xl px-4 py-16 sm:px-6 lg:px-8 lg:py-20">
            <div class="flex flex-col gap-6 sm:flex-row sm:items-end sm:gap-10">
                <p class="reveal tabular text-[3.5rem] font-semibold leading-none tracking-[-0.04em] text-ink-900 sm:text-[4.5rem] dark:text-white" data-count-to="54">54
                </p>

                <div class="max-w-md pb-1">
                    <h2 class="text-2xl font-semibold tracking-[-0.02em]">pays couverts</h2>
                    <p class="body-text mt-2 text-sm">
                        Tout le continent, de la Tunisie à l'Afrique du Sud.
                        Choisissez un pays pour voir les profils disponibles.
                    </p>
                </div>
            </div>

            <div class="mt-10 flex flex-wrap gap-2">
                @foreach ($featuredCountries as $country)
                    <a
                        href="{{ route('devs.index', ['country' => $country['slug']]) }}"
                        class="reveal inline-flex items-center gap-2 rounded-lg border border-ink-200 bg-white px-3 py-2 text-sm font-medium text-ink-700 transition-colors hover:border-ink-300 hover:bg-ink-50 dark:border-ink-800 dark:bg-ink-900 dark:text-ink-200 dark:hover:border-ink-700 dark:hover:bg-ink-800"
                        style="--reveal-delay: {{ min($loop->index, 5) * 50 }}ms"
                    >
                        <x-flag :code="$country['code']" />
                        {{ $country['name'] }}
                    </a>
                @endforeach

                <a href="{{ route('devs.index') }}" class="inline-flex items-center gap-1.5 rounded-lg px-3 py-2 text-sm font-medium text-ink-500 transition-colors hover:text-ink-900 dark:text-ink-400 dark:hover:text-white">
                    Tous les pays
                    <x-icon name="arrow-right" class="h-4 w-4" />
                </a>
            </div>
        </div>
    </section>

    {{-- ===================  Appel final  =================== --}}
    <section class="border-t border-ink-200 bg-ink-900 dark:border-ink-800 dark:bg-ink-900">
        <div class="mx-auto max-w-7xl px-4 py-16 sm:px-6 lg:px-8 lg:py-20">
            <div class="flex flex-col gap-8 lg:flex-row lg:items-center lg:justify-between">
                <div class="max-w-xl">
                    <h2 class="text-2xl font-semibold tracking-[-0.02em] text-white sm:text-3xl">
                        @guest
                            Créez votre profil en quelques minutes
                        @else
                            Continuez là où vous vous êtes arrêté
                        @endguest
                    </h2>
                    <p class="mt-3 text-[0.9375rem] leading-relaxed text-ink-400">
                        @guest
                            Renseignez vos compétences, votre pays et votre disponibilité.
                            Un profil complet apparaît dans les recherches et vous rapporte
                            vos premiers points DevScore.
                        @else
                            Retrouvez vos projets, vos collaborations et vos recherches.
                        @endguest
                    </p>
                </div>

                <div class="reveal flex shrink-0 flex-wrap items-center gap-3" style="--reveal-delay: 90ms">
                    @guest
                        <a href="{{ route('register') }}" class="btn btn-lg bg-white font-semibold text-ink-900 hover:bg-ink-100">
                            Créer mon compte
                        </a>
                        <a href="{{ route('login') }}" class="btn btn-lg border border-ink-700 text-ink-200 hover:bg-ink-800 hover:text-white">
                            Se connecter
                        </a>
                    @else
                        <a href="{{ route('dashboard') }}" class="btn btn-lg bg-white font-semibold text-ink-900 hover:bg-ink-100">
                            Mon tableau de bord
                        </a>
                    @endguest
                </div>
            </div>
        </div>
    </section>
</x-app-layout>
