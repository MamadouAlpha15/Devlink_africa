{{-- Suppression définitive du compte, confirmée dans une fenêtre modale. --}}
<section>
    <h2 class="flex items-center gap-2.5 section-title">
        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-red-50 text-red-600 dark:bg-red-950 dark:text-red-400">
            <x-icon name="alert-triangle" class="h-4 w-4" />
        </span>
        Supprimer mon compte
    </h2>

    <p class="muted mt-3">
        La suppression est définitive. Votre profil, vos projets et vos données
        sont effacés sans possibilité de retour. Pensez à récupérer ce que vous
        souhaitez conserver avant de continuer.
    </p>

    <button
        type="button"
        x-data
        @click.prevent="$dispatch('open-modal', 'confirm-user-deletion')"
        class="btn-danger btn-sm mt-5"
    >
        <x-icon name="trash" class="h-4 w-4" />
        Supprimer mon compte
    </button>

    <x-modal name="confirm-user-deletion" :show="$errors->userDeletion->isNotEmpty()" max-width="lg" focusable>
        <form method="post" action="{{ route('profile.destroy') }}" class="p-6">
            @csrf
            @method('delete')

            <h2 class="section-title">Supprimer définitivement votre compte&nbsp;?</h2>

            <p class="muted mt-2">
                Toutes vos données seront effacées. Saisissez votre mot de passe
                pour confirmer cette suppression définitive.
            </p>

            <div class="mt-5">
                <label for="password" class="label">Mot de passe</label>
                <input id="password" type="password" name="password" class="input" placeholder="••••••••">
                <x-input-error :messages="$errors->userDeletion->get('password')" />
            </div>

            <div class="mt-6 flex flex-wrap justify-end gap-3">
                <button type="button" @click="$dispatch('close')" class="btn-secondary">Annuler</button>

                <button type="submit" class="btn-danger">
                    <x-icon name="trash" class="h-4 w-4" />
                    Supprimer définitivement
                </button>
            </div>
        </form>
    </x-modal>
</section>
