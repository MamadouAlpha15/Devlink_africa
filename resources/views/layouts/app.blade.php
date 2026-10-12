{{--
    Mise en page de l'application (membres et pages publiques).

    Les pages peuvent passer un titre d'onglet :
    <x-app-layout title="Trouver des développeurs">

    Le slot `header` reste pris en charge pour compatibilité, mais les pages
    refaites placent plutôt leur <x-page-header> directement dans le contenu.
--}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
    <head>
        @include('layouts.partials.head', ['title' => $attributes->get('title')])
    </head>

    <body class="flex min-h-full flex-col font-sans antialiased">
        @include('layouts.navigation')

        <x-verify-email-banner />

        {{-- En-tête facultatif, sur une bande distincte du contenu. --}}
        @isset($header)
            <header class="border-b border-ink-200 bg-white dark:border-ink-800 dark:bg-ink-900">
                <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
                    {{ $header }}
                </div>
            </header>
        @endisset

        <main class="flex-1">
            {{ $slot }}
        </main>

        @include('layouts.partials.footer')
    </body>
</html>
