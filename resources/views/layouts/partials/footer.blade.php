{{-- Pied de page : uniquement des liens qui existent réellement. --}}
<footer class="mt-auto border-t border-ink-200 bg-white dark:border-ink-800 dark:bg-ink-900">
    <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
        <div class="grid gap-10 sm:grid-cols-2 lg:grid-cols-4">
            <div class="lg:col-span-2">
                <x-logo />

                <p class="mt-4 max-w-sm text-sm leading-relaxed text-ink-600 dark:text-ink-400">
                    La plateforme qui met en relation les développeurs, designers et porteurs
                    de projets des 54 pays d'Afrique.
                </p>
            </div>

            <div>
                <h3 class="text-xs font-semibold uppercase tracking-wider text-ink-500 dark:text-ink-400">
                    Explorer
                </h3>
                <ul class="mt-4 space-y-2.5 text-sm">
                    <li><a href="{{ route('devs.index') }}" class="link-muted">Développeurs</a></li>
                    <li><a href="{{ route('projects.index') }}" class="link-muted">Projets</a></li>
                    <li><a href="{{ route('ecosystem') }}" class="link-muted">L'écosystème</a></li>
                    @auth
                        <li><a href="{{ route('ai.create') }}" class="link-muted">DevLink AI</a></li>
                        <li><a href="{{ route('collaborations.index') }}" class="link-muted">Mes collaborations</a></li>
                    @endauth
                </ul>
            </div>

            <div>
                <h3 class="text-xs font-semibold uppercase tracking-wider text-ink-500 dark:text-ink-400">
                    Mon compte
                </h3>
                <ul class="mt-4 space-y-2.5 text-sm">
                    @auth
                        <li><a href="{{ route('devs.show', auth()->user()) }}" class="link-muted">Mon profil public</a></li>
                        <li><a href="{{ route('developer-profile.edit') }}" class="link-muted">Modifier mon profil</a></li>
                        <li><a href="{{ route('projects.create') }}" class="link-muted">Créer un projet</a></li>
                    @else
                        <li><a href="{{ route('login') }}" class="link-muted">Connexion</a></li>
                        <li><a href="{{ route('register') }}" class="link-muted">Créer un compte</a></li>
                    @endauth
                </ul>
            </div>
        </div>

        <div class="mt-10 flex flex-col items-center justify-between gap-4 border-t border-ink-200 pt-6 sm:flex-row dark:border-ink-800">
            <p class="text-xs text-ink-500 dark:text-ink-400">
                &copy; {{ now()->year }} DevLink Africa. Tous droits réservés.
            </p>

            <p class="flex items-center gap-1.5 text-xs text-ink-500 dark:text-ink-400">
                <x-icon name="globe" class="h-3.5 w-3.5" />
                Construit pour les développeurs du continent
            </p>
        </div>
    </div>
</footer>
