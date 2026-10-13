{{--
    Publier une ressource.

    Variables : $types, $countries, $skillsByCategory, $fileExtensions.
    Voir docs/front-end/pages.md.
--}}
<x-app-layout title="Partager une ressource">
    <div class="mx-auto max-w-3xl px-4 py-8 sm:px-6 lg:px-8 lg:py-10">

        <x-page-header
            title="Partager une ressource"
            subtitle="Une API, un template, un package — ce qui vous a fait gagner du temps en fera gagner à d'autres."
            :back-link="route('resources.index')"
            back-label="Toutes les ressources"
        />

        <x-flash class="mt-6" />

        <form method="POST" action="{{ route('resources.store') }}" enctype="multipart/form-data" class="mt-8">
            @csrf

            @include('resources.partials.form-fields', ['resource' => null])

            {{--
                Barre d'envoi collée en bas : le formulaire est long, le bouton
                doit rester à portée sans remonter.
            --}}
            <div class="sticky bottom-0 mt-6 flex flex-wrap items-center justify-between gap-3 border-t border-ink-200 bg-white/95 py-4 backdrop-blur dark:border-ink-800 dark:bg-ink-950/95">
                <p class="muted">Publier une ressource vaut 30 points de DevScore.</p>

                <div class="flex items-center gap-2">
                    <a href="{{ route('resources.index') }}" class="btn-ghost btn-sm">Annuler</a>
                    <button type="submit" class="btn-primary">Publier la ressource</button>
                </div>
            </div>
        </form>
    </div>
</x-app-layout>
