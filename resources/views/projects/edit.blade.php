{{--
    Modifier un projet (propriétaire uniquement).
    Les postes se gèrent depuis la fiche du projet, pas ici.
--}}
<x-app-layout :title="'Modifier '.$project->title">
    <div class="mx-auto max-w-3xl px-4 py-8 sm:px-6 lg:px-8 lg:py-10">

        <x-page-header
            :title="'Modifier « '.$project->title.' »'"
            :back-link="route('projects.show', $project)"
            back-label="Retour au projet"
        />

        <x-flash class="mt-6" />

        <form
            method="POST"
            action="{{ route('projects.update', $project) }}"
            enctype="multipart/form-data"
            class="mt-6 space-y-6"
        >
            @csrf
            @method('PUT')

            <div class="panel">
                <x-section-heading title="Le projet" />

                <div class="mt-5">
                    @include('projects.partials.form-fields', ['project' => $project])
                </div>
            </div>

            <div class="flex flex-wrap items-center justify-end gap-3">
                <a href="{{ route('projects.show', $project) }}" class="btn-ghost">Annuler</a>

                <button type="submit" class="btn-primary">
                    <x-icon name="check" class="h-4 w-4" />
                    Enregistrer les modifications
                </button>
            </div>
        </form>

        {{-- Zone dangereuse --}}
        <div class="panel mt-8 border-red-300 dark:border-red-900">
            <h2 class="flex items-center gap-2.5 section-title">
                <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-red-50 text-red-600 dark:bg-red-950 dark:text-red-400">
                    <x-icon name="alert-triangle" class="h-4 w-4" />
                </span>
                Supprimer le projet
            </h2>

            <p class="muted mt-3">
                La suppression est définitive : le projet, ses postes, son équipe et
                toutes les demandes associées disparaissent.
            </p>

            <form
                method="POST"
                action="{{ route('projects.destroy', $project) }}"
                x-data="confirmAction"
                class="mt-4"
            >
                @csrf
                @method('DELETE')

                <button type="button" x-show="! isConfirming" @click="ask()" class="btn-danger btn-sm">
                    <x-icon name="trash" class="h-4 w-4" />
                    Supprimer ce projet
                </button>

                <div x-show="isConfirming" x-cloak class="flex flex-wrap items-center gap-2">
                    <span class="text-sm font-medium text-red-700 dark:text-red-400">
                        Confirmer la suppression définitive&nbsp;?
                    </span>
                    <button type="submit" class="btn-danger btn-sm">Oui, supprimer</button>
                    <button type="button" @click="cancel()" class="btn-ghost btn-sm">Annuler</button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
