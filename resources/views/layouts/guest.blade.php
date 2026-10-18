{{--
    Mise en page des pages d'authentification.

    Deux colonnes sur grand écran : le formulaire à gauche, un panneau
    sombre neutre à droite. Le panneau n'est pas en couleur de marque :
    un demi-écran d'indigo saturé face à un formulaire de connexion est
    agressif, et il épuiserait la seule couleur d'accent du produit avant
    même le bouton d'action.
--}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
    <head>
        @include('layouts.partials.head', ['title' => $attributes->get('title')])
    </head>

    <body class="min-h-full font-sans antialiased">
        <div class="flex min-h-full">

            {{-- Colonne du formulaire --}}
            <div class="flex w-full flex-col px-4 py-8 sm:px-6 lg:w-[56%] lg:px-12 xl:px-20">
                <div class="flex items-center justify-between">
                    <a href="{{ url('/') }}" aria-label="Accueil AfriCommit">
                        <x-logo />
                    </a>
                    <x-theme-toggle />
                </div>

                <div class="mx-auto flex w-full max-w-md flex-1 flex-col justify-center py-12">
                    @isset($heading)
                        <div class="mb-8">
                            <h1 class="page-title">{{ $heading }}</h1>
                            @isset($subheading)
                                <p class="mt-2 text-sm text-ink-600 dark:text-ink-400">{{ $subheading }}</p>
                            @endisset
                        </div>
                    @endisset

                    {{ $slot }}
                </div>

                <p class="text-center text-xs text-ink-500 dark:text-ink-400">
                    &copy; {{ now()->year }} AfriCommit
                </p>
            </div>

            {{-- Panneau de présentation (grand écran) --}}
            <div class="hidden w-[44%] shrink-0 border-l border-ink-800 bg-ink-900 p-12 xl:p-16 lg:flex lg:flex-col lg:justify-center">
                <div class="max-w-md">
                    <p class="text-xs font-semibold text-ink-400">
                        Le réseau des développeurs africains
                    </p>

                    <h2 class="mt-5 text-3xl font-semibold leading-[1.15] tracking-[-0.025em] text-white xl:text-[2.5rem]">
                        Trouvez les bonnes personnes pour construire votre projet.
                    </h2>

                    <p class="mt-5 text-[0.9375rem] leading-relaxed text-ink-400">
                        Publiez votre projet, décrivez les profils qui vous manquent,
                        et laissez AfriCommit AI vous proposer les développeurs qui correspondent.
                    </p>

                    {{-- Icônes nues, alignées optiquement sur la première ligne :
                         pas de carré coloré, et l'alignement tient à toutes les
                         tailles de police. --}}
                    <ul class="mt-10 space-y-4">
                        @foreach ([
                            ['icon' => 'users', 'text' => 'Des profils dans les 54 pays du continent'],
                            ['icon' => 'sparkles', 'text' => "Recherche en langage naturel, en français"],
                            ['icon' => 'handshake', 'text' => 'Rejoignez un projet ou recrutez votre équipe'],
                            ['icon' => 'zap', 'text' => 'Un DevScore qui valorise vos contributions'],
                        ] as $highlight)
                            <li class="flex items-start gap-3">
                                <x-icon :name="$highlight['icon']" class="mt-[0.1875rem] h-4 w-4 shrink-0 text-ink-500" />
                                <span class="text-sm text-ink-300">{{ $highlight['text'] }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    </body>
</html>
