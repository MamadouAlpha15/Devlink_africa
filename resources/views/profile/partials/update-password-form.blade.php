{{-- Changement de mot de passe (PUT /password, erreurs dans le sac `updatePassword`). --}}
<section>
    <x-section-heading title="Mot de passe" />

    <p class="muted mt-3">
        Choisissez un mot de passe long et unique pour garder votre compte en sécurité.
    </p>

    <form method="post" action="{{ route('password.update') }}" class="mt-5 space-y-5">
        @csrf
        @method('put')

        <div>
            <label for="update_password_current_password" class="label">Mot de passe actuel</label>
            <input id="update_password_current_password" type="password" name="current_password"
                   autocomplete="current-password" class="input" placeholder="••••••••">
            <x-input-error :messages="$errors->updatePassword->get('current_password')" />
        </div>

        <div class="grid gap-5 sm:grid-cols-2">
            <div>
                <label for="update_password_password" class="label">Nouveau mot de passe</label>
                <input id="update_password_password" type="password" name="password"
                       autocomplete="new-password" class="input" placeholder="••••••••">
                <p class="field-hint">8 caractères minimum.</p>
                <x-input-error :messages="$errors->updatePassword->get('password')" />
            </div>

            <div>
                <label for="update_password_password_confirmation" class="label">Confirmer</label>
                <input id="update_password_password_confirmation" type="password" name="password_confirmation"
                       autocomplete="new-password" class="input" placeholder="••••••••">
                <x-input-error :messages="$errors->updatePassword->get('password_confirmation')" />
            </div>
        </div>

        <div class="flex items-center gap-4">
            <button type="submit" class="btn-primary">
                <x-icon name="check" class="h-4 w-4" />
                Changer le mot de passe
            </button>

            @if (session('status') === 'password-updated')
                <p
                    x-data="dismissible(3000)"
                    x-show="isVisible"
                    x-transition.opacity
                    class="inline-flex items-center gap-1.5 text-sm font-medium text-emerald-700 dark:text-emerald-400"
                >
                    <x-icon name="check-circle" class="h-4 w-4" />
                    Enregistré
                </p>
            @endif
        </div>
    </form>
</section>
