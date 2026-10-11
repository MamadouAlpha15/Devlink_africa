{{--
    Barre de navigation principale, collée en haut de la page.

    Répartition : la barre ne porte que les destinations publiques
    (Développeurs · Projets · Ressources · Classement) et DevLink AI, mis en
    avant. Tout ce qui est personnel — mes collaborations, mon DevScore, mes
    favoris — vit dans le menu du compte. Sans cela la barre déborderait dès
    1024 px, et on ne distinguerait plus « explorer » de « chez moi ».
--}}
@php
    // Liens de la barre, dans l'ordre de parcours : on cherche des personnes,
    // puis des projets, puis des ressources, et on se compare au classement.
    $navLinks = [
        [
            'label' => 'Développeurs',
            'icon' => 'users',
            'url' => route('devs.index'),
            'active' => request()->routeIs('devs.*'),
        ],
        [
            'label' => 'Projets',
            'icon' => 'folder',
            'url' => route('projects.index'),
            'active' => request()->routeIs('projects.*'),
        ],
        [
            'label' => 'Ressources',
            'icon' => 'package',
            'url' => route('resources.index'),
            'active' => request()->routeIs('resources.*'),
        ],
        [
            'label' => 'Classement',
            'icon' => 'trophy',
            'url' => route('ranking.index'),
            'active' => request()->routeIs('ranking.*'),
        ],
    ];

    // Liens personnels : menu du compte sur grand écran, menu mobile sinon.
    $accountLinks = auth()->check()
        ? [
            ['label' => 'Mon profil public', 'icon' => 'user', 'url' => route('devs.show', auth()->user())],
            ['label' => 'Modifier mon profil', 'icon' => 'pencil', 'url' => route('developer-profile.edit')],
            ['label' => 'Mon DevScore', 'icon' => 'trending-up', 'url' => route('devscore.show')],
            ['label' => 'Mes favoris', 'icon' => 'bookmark', 'url' => route('favorites.index')],
            ['label' => 'Mes collaborations', 'icon' => 'handshake', 'url' => route('collaborations.index')],
            ...(auth()->user()->is_admin
                ? [['label' => 'Administration', 'icon' => 'settings', 'url' => route('admin.dashboard')]]
                : []),
            ['label' => 'Compte', 'icon' => 'settings', 'url' => route('profile.edit')],
        ]
        : [];
@endphp

<nav
    x-data="{ isMobileMenuOpen: false }"
    class="sticky top-0 z-40 border-b border-ink-200 bg-white/90 backdrop-blur-md dark:border-ink-800 dark:bg-ink-900/90"
>
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="flex h-16 items-center justify-between gap-4">

            {{-- Marque + liens principaux --}}
            <div class="flex min-w-0 items-center gap-2 lg:gap-5">
                <a href="{{ auth()->check() ? route('dashboard') : url('/') }}" class="shrink-0" aria-label="Accueil DevLink Africa">
                    <x-logo class="h-9 w-9" />
                </a>

                <div class="hidden items-center gap-0.5 lg:flex">
                    @foreach ($navLinks as $link)
                        <a href="{{ $link['url'] }}" class="nav-item {{ $link['active'] ? 'nav-item-active' : '' }}">
                            <x-icon :name="$link['icon']" class="h-4 w-4" />
                            {{ $link['label'] }}
                        </a>
                    @endforeach

                    @auth
                        {{-- DevLink AI : la fonctionnalité phare, visuellement détachée. --}}
                        <a
                            href="{{ route('ai.create') }}"
                            class="nav-item ml-1.5 border {{ request()->routeIs('ai.*')
                                ? 'border-brand-600 bg-brand-600 text-white hover:bg-brand-700 hover:text-white dark:border-brand-500 dark:bg-brand-500 dark:hover:bg-brand-400'
                                : 'border-brand-200 bg-brand-50 text-brand-700 hover:bg-brand-100 hover:text-brand-800 dark:border-brand-800 dark:bg-brand-950 dark:text-brand-300 dark:hover:bg-brand-900' }}"
                        >
                            <x-icon name="sparkles" class="h-4 w-4" />
                            DevLink AI
                        </a>
                    @endauth
                </div>
            </div>

            {{-- Actions à droite --}}
            <div class="flex shrink-0 items-center gap-1.5 sm:gap-2">
                <x-theme-toggle class="hidden sm:inline-flex" />

                @guest
                    <a href="{{ route('login') }}" class="btn-ghost hidden sm:inline-flex">Connexion</a>
                    <a href="{{ route('register') }}" class="btn-primary btn-sm">Créer un compte</a>
                @endguest

                @auth
                    {{-- Les `<symbol>` des icônes de notification, déposés une fois. --}}
                    <x-notification-icon sprite />

                    {{--
                        Cloche de notifications. Le composant Alpine vient du
                        backend (resources/js/notifications.js) : on garde son
                        nom et ses propriétés (`unread`, `items`, `open`,
                        `muted`, `toggleMute()`, `markAllRead()`), on ne
                        redessine que l'habillage.
                    --}}
                    <div
                        class="relative"
                        x-data="notificationBell({
                            pollUrl: @js(route('notifications.poll')),
                            readAllUrl: @js(route('notifications.read-all')),
                            csrf: @js(csrf_token()),
                            initialUnread: {{ auth()->user()->unreadNotifications()->count() }},
                        })"
                        @click.outside="open = false"
                        @keydown.escape="open = false"
                    >
                        <button
                            type="button"
                            @click="open = ! open"
                            class="btn btn-ghost btn-square relative"
                            :aria-expanded="open"
                            aria-label="Notifications"
                        >
                            <x-icon name="bell" class="h-5 w-5" />

                            {{--
                                Compteur en indigo et non en rouge : une
                                notification est une nouveauté, pas une erreur.
                                `min-w` plutôt qu'une largeur fixe, pour que
                                « 9 » et « 99+ » restent tous deux centrés.
                            --}}
                            <span
                                x-show="unread > 0"
                                x-cloak
                                x-text="unread > 99 ? '99+' : unread"
                                class="tabular absolute -right-0.5 -top-0.5 inline-flex h-4 min-w-[1rem] items-center justify-center rounded-full border-2 border-white bg-brand-600 px-1 text-[0.625rem] font-semibold leading-none text-white dark:border-ink-900 dark:bg-brand-500"
                            ></span>
                        </button>

                        <div
                            x-show="open"
                            x-cloak
                            x-transition:enter="transition ease-out duration-150"
                            x-transition:enter-start="opacity-0 scale-95"
                            x-transition:enter-end="opacity-100 scale-100"
                            x-transition:leave="transition ease-in duration-100"
                            x-transition:leave-start="opacity-100 scale-100"
                            x-transition:leave-end="opacity-0 scale-95"
                            class="absolute end-0 z-50 mt-2 w-[21rem] max-w-[calc(100vw-2rem)] overflow-hidden rounded-xl border border-ink-200 bg-white shadow-overlay dark:border-ink-800 dark:bg-ink-900"
                        >
                            <div class="flex items-center justify-between gap-2 border-b border-ink-200 px-3 py-2.5 dark:border-ink-800">
                                <p class="text-sm font-semibold text-ink-900 dark:text-white">Notifications</p>

                                <div class="flex items-center gap-1">
                                    <button
                                        type="button"
                                        @click="toggleMute()"
                                        class="btn btn-ghost btn-square btn-xs"
                                        :aria-label="muted ? 'Activer le son' : 'Couper le son'"
                                        :title="muted ? 'Activer le son' : 'Couper le son'"
                                    >
                                        <span x-show="! muted"><x-icon name="volume" class="h-4 w-4" /></span>
                                        <span x-show="muted" x-cloak><x-icon name="volume-off" class="h-4 w-4" /></span>
                                    </button>

                                    <button
                                        type="button"
                                        @click="markAllRead()"
                                        x-show="unread > 0"
                                        x-cloak
                                        class="btn btn-ghost btn-xs"
                                    >
                                        Tout marquer lu
                                    </button>
                                </div>
                            </div>

                            <div class="max-h-[22rem] overflow-y-auto">
                                <template x-for="item in items" :key="item.id">
                                    <a
                                        :href="item.url"
                                        class="flex gap-2.5 border-b border-ink-100 px-3 py-2.5 transition-colors last:border-b-0 hover:bg-ink-50 dark:border-ink-800/70 dark:hover:bg-ink-800/60"
                                        :class="item.read || 'bg-brand-50/70 dark:bg-brand-950/40'"
                                    >
                                        <span
                                            class="mt-px shrink-0"
                                            :class="item.read ? 'text-ink-400 dark:text-ink-500' : 'text-brand-600 dark:text-brand-400'"
                                        >
                                            {{-- L'emoji du backend est traduit en icône via le sprite. --}}
                                            <svg class="h-4 w-4" aria-hidden="true">
                                                <use :href="'#notif-' + ((window.devlinkNotificationIcons || {})[item.icon] || 'bell')"></use>
                                            </svg>
                                        </span>

                                        <span class="min-w-0 flex-1">
                                            <span
                                                class="block text-sm leading-snug"
                                                :class="item.read ? 'text-ink-600 dark:text-ink-400' : 'font-medium text-ink-900 dark:text-white'"
                                                x-text="item.message"
                                            ></span>
                                            <span class="mt-0.5 block text-xs text-ink-500" x-text="item.time"></span>
                                        </span>
                                    </a>
                                </template>

                                <div x-show="items.length === 0" class="px-3 py-8 text-center">
                                    <x-icon name="bell" class="mx-auto h-6 w-6 text-ink-300 dark:text-ink-600" />
                                    <p class="mt-2 text-sm text-ink-500 dark:text-ink-400">Aucune notification pour l'instant.</p>
                                </div>
                            </div>

                            <a
                                href="{{ route('notifications.index') }}"
                                class="block border-t border-ink-200 px-3 py-2.5 text-center text-sm font-medium text-brand-700 transition-colors hover:bg-ink-50 dark:border-ink-800 dark:text-brand-400 dark:hover:bg-ink-800/60"
                            >
                                Voir toutes les notifications
                            </a>
                        </div>
                    </div>

                    <a href="{{ route('projects.create') }}" class="btn-secondary btn-sm hidden xl:inline-flex">
                        <x-icon name="plus" class="h-4 w-4" />
                        Créer un projet
                    </a>

                    <x-dropdown align="right" width="w-60">
                        <x-slot name="trigger">
                            <button type="button" class="flex items-center gap-2 rounded-lg p-1 pr-2 transition hover:bg-ink-100 dark:hover:bg-ink-800">
                                <x-avatar :user="auth()->user()" size="sm" />
                                <span class="hidden max-w-[9rem] truncate text-sm font-medium text-ink-700 sm:block dark:text-ink-200">
                                    {{ auth()->user()->first_name ?? auth()->user()->name }}
                                </span>
                                <x-icon name="chevron-down" class="h-4 w-4 text-ink-400" />
                            </button>
                        </x-slot>

                        <x-slot name="content">
                            {{-- Rappel de l'identité et du score en haut du menu. --}}
                            <div class="border-b border-ink-200 px-3 py-2.5 dark:border-ink-800">
                                <p class="truncate text-sm font-semibold text-ink-900 dark:text-white">
                                    {{ auth()->user()->name }}
                                </p>
                                <p class="mt-0.5 truncate text-xs text-ink-500 dark:text-ink-400">
                                    {{ auth()->user()->email }}
                                </p>
                                <x-devscore :score="auth()->user()->devscore" class="mt-2" />
                            </div>

                            @foreach ($accountLinks as $link)
                                <a href="{{ $link['url'] }}" class="dropdown-item">
                                    <x-icon :name="$link['icon']" class="h-4 w-4 text-ink-400" />
                                    {{ $link['label'] }}
                                </a>
                            @endforeach

                            <div class="my-1.5 border-t border-ink-200 dark:border-ink-800"></div>

                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="dropdown-item text-red-600 hover:bg-red-50 hover:text-red-700 dark:text-red-400 dark:hover:bg-red-950/50 dark:hover:text-red-300">
                                    <x-icon name="logout" class="h-4 w-4" />
                                    Déconnexion
                                </button>
                            </form>
                        </x-slot>
                    </x-dropdown>
                @endauth

                {{-- Bouton du menu mobile --}}
                <button
                    type="button"
                    @click="isMobileMenuOpen = ! isMobileMenuOpen"
                    class="btn btn-ghost btn-square lg:hidden"
                    :aria-expanded="isMobileMenuOpen"
                    aria-label="Ouvrir le menu"
                >
                    <x-icon name="menu" class="h-5 w-5" x-show="! isMobileMenuOpen" />
                    <x-icon name="close" class="h-5 w-5" x-show="isMobileMenuOpen" x-cloak />
                </button>
            </div>
        </div>
    </div>

    {{-- Menu mobile --}}
    <div
        x-show="isMobileMenuOpen"
        x-cloak
        x-transition:enter="transition ease-out duration-150"
        x-transition:enter-start="opacity-0 -translate-y-2"
        x-transition:enter-end="opacity-100 translate-y-0"
        class="border-t border-ink-200 bg-white px-4 py-4 lg:hidden dark:border-ink-800 dark:bg-ink-900"
    >
        <div class="space-y-1">
            @foreach ($navLinks as $link)
                <a href="{{ $link['url'] }}" class="nav-item-mobile {{ $link['active'] ? 'nav-item-mobile-active' : '' }}">
                    <x-icon :name="$link['icon']" class="h-5 w-5 text-ink-400" />
                    {{ $link['label'] }}
                </a>
            @endforeach

            @auth
                <a href="{{ route('ai.create') }}" class="nav-item-mobile {{ request()->routeIs('ai.*') ? 'nav-item-mobile-active' : '' }}">
                    <x-icon name="sparkles" class="h-5 w-5 text-brand-500" />
                    DevLink AI
                </a>
            @endauth
        </div>

        <div class="mt-4 border-t border-ink-200 pt-4 dark:border-ink-800">
            @auth
                <div class="flex items-center gap-3 px-3 pb-3">
                    <x-avatar :user="auth()->user()" size="sm" />
                    <div class="min-w-0">
                        <p class="truncate text-sm font-semibold text-ink-900 dark:text-white">{{ auth()->user()->name }}</p>
                        <p class="truncate text-xs text-ink-500 dark:text-ink-400">{{ auth()->user()->email }}</p>
                    </div>
                </div>

                <div class="space-y-1">
                    <a href="{{ route('projects.create') }}" class="nav-item-mobile">
                        <x-icon name="plus" class="h-5 w-5 text-ink-400" />
                        Créer un projet
                    </a>

                    <a href="{{ route('notifications.index') }}" class="nav-item-mobile">
                        <x-icon name="bell" class="h-5 w-5 text-ink-400" />
                        Mes notifications
                    </a>

                    @foreach ($accountLinks as $link)
                        <a href="{{ $link['url'] }}" class="nav-item-mobile">
                            <x-icon :name="$link['icon']" class="h-5 w-5 text-ink-400" />
                            {{ $link['label'] }}
                        </a>
                    @endforeach

                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="nav-item-mobile w-full text-red-600 hover:bg-red-50 dark:text-red-400 dark:hover:bg-red-950/50">
                            <x-icon name="logout" class="h-5 w-5" />
                            Déconnexion
                        </button>
                    </form>
                </div>
            @else
                <div class="flex flex-col gap-2 px-1">
                    <a href="{{ route('login') }}" class="btn-secondary btn-block">Connexion</a>
                    <a href="{{ route('register') }}" class="btn-primary btn-block">Créer un compte</a>
                </div>
            @endauth

            <div class="mt-4 flex items-center justify-between rounded-lg px-3 py-2">
                <span class="text-sm font-medium text-ink-600 dark:text-ink-300">Thème</span>
                <x-theme-toggle />
            </div>
        </div>
    </div>
</nav>
