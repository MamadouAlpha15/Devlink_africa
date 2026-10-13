{{--
    Modifier une ressource.

    Variables : $resource, $types, $countries, $skillsByCategory,
    $fileExtensions. Voir docs/front-end/pages.md.
--}}
<x-app-layout :title="'Modifier '.$resource->title">
    <div class="mx-auto max-w-3xl px-4 py-8 sm:px-6 lg:px-8 lg:py-10">

        <x-page-header
            title="Modifier la ressource"
            :subtitle="$resource->title"
            :back-link="route('resources.show', $resource)"
            back-label="Retour à la ressource"
        />

        <x-flash class="mt-6" />

        <form method="POST" action="{{ route('resources.update', $resource) }}" enctype="multipart/form-data" class="mt-8">
            @csrf
            @method('PUT')

            @include('resources.partials.form-fields', ['resource' => $resource])

            <div class="sticky bottom-0 mt-6 flex flex-wrap items-center justify-end gap-2 border-t border-ink-200 bg-white/95 py-4 backdrop-blur dark:border-ink-800 dark:bg-ink-950/95">
                <a href="{{ route('resources.show', $resource) }}" class="btn-ghost btn-sm">Annuler</a>
                <button type="submit" class="btn-primary">Enregistrer</button>
            </div>
        </form>

        {{--
            Suppression à l'écart du formulaire d'édition, et en deux temps :
            une action irréversible ne doit pas se trouver sous le pouce du
            bouton « Enregistrer ».
        --}}
        <div class="card mt-8 border-red-200 p-4 sm:p-5 dark:border-red-900/60">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="min-w-0">
                    <p class="card-title">Supprimer cette ressource</p>
                    <p class="muted mt-1">
                        Le fichier, les avis et les commentaires seront perdus. Cette action est définitive.
                    </p>
                </div>

                <form
                    method="POST"
                    action="{{ route('resources.destroy', $resource) }}"
                    x-data="confirmAction"
                    class="shrink-0"
                >
                    @csrf
                    @method('DELETE')

                    <button type="button" x-show="! isConfirming" @click="ask()" class="btn-danger btn-sm">
                        <x-icon name="trash" class="h-4 w-4" />
                        Supprimer
                    </button>

                    <span x-show="isConfirming" x-cloak class="inline-flex items-center gap-1.5">
                        <button type="submit" class="btn-danger btn-sm">Confirmer</button>
                        <button type="button" @click="cancel()" class="btn-ghost btn-sm">Annuler</button>
                    </span>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
