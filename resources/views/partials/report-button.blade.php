{{--
    Bouton « Signaler » réutilisable (cahier, section 22).

    @include('partials.report-button', [
        'type' => 'developer' | 'resource' | 'project' | 'comment',
        'model' => $model,
        'authorId' => …,
        'compact' => true,   // facultatif : icône seule, pour une ligne de commentaire
    ])

    Signaler est une action rare et sérieuse : le bouton reste discret et le
    formulaire s'ouvre en panneau au-dessus du contenu, sans pousser la page.
    Rien ne s'affiche pour un visiteur ni sur son propre contenu.
--}}
@auth
    @if (auth()->id() !== $authorId)
        @php($isCompact = $compact ?? false)

        <div
            x-data="{ open: {{ $errors->hasAny(['reason', 'details']) ? 'true' : 'false' }} }"
            @click.outside="open = false"
            @keydown.escape="open = false"
            class="relative inline-block"
        >
            <button
                type="button"
                @click="open = ! open"
                class="btn-ghost {{ $isCompact ? 'btn-square btn-xs' : 'btn-sm' }} text-ink-500 hover:text-ink-700 dark:text-ink-400 dark:hover:text-ink-200"
                :aria-expanded="open"
                aria-label="Signaler ce contenu"
            >
                <x-icon name="alert-triangle" class="{{ $isCompact ? 'h-3.5 w-3.5' : 'h-4 w-4' }}" />
                @unless ($isCompact)
                    <span class="hidden sm:inline">Signaler</span>
                @endunless
            </button>

            <div
                x-show="open"
                x-cloak
                x-transition:enter="transition ease-out duration-150"
                x-transition:enter-start="opacity-0 scale-95"
                x-transition:enter-end="opacity-100 scale-100"
                class="absolute end-0 z-40 mt-2 w-72 max-w-[calc(100vw-2rem)] rounded-xl border border-ink-200 bg-white p-3 shadow-overlay dark:border-ink-800 dark:bg-ink-900"
            >
                <form method="POST" action="{{ route('reports.store', [$type, $model->getKey()]) }}" class="space-y-3">
                    @csrf

                    <p class="caption">Signaler ce contenu</p>

                    <div>
                        <label for="reason-{{ $type }}-{{ $model->getKey() }}" class="sr-only">Motif</label>
                        <select id="reason-{{ $type }}-{{ $model->getKey() }}" name="reason" required class="select">
                            <option value="">Choisir un motif…</option>
                            @foreach (\App\Enums\ReportReason::cases() as $reason)
                                <option value="{{ $reason->value }}" @selected(old('reason') === $reason->value)>
                                    {{ $reason->label() }}
                                </option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('reason')" class="mt-1.5" />
                    </div>

                    <div>
                        <label for="details-{{ $type }}-{{ $model->getKey() }}" class="sr-only">Précisions</label>
                        <textarea
                            id="details-{{ $type }}-{{ $model->getKey() }}"
                            name="details"
                            rows="2"
                            maxlength="1000"
                            class="textarea"
                            placeholder="Précisions (facultatif)"
                        >{{ old('details') }}</textarea>
                        <x-input-error :messages="$errors->get('details')" class="mt-1.5" />
                    </div>

                    <div class="flex items-center gap-2">
                        <button type="submit" class="btn-danger btn-sm">Envoyer</button>
                        <button type="button" @click="open = false" class="btn-ghost btn-sm">Annuler</button>
                    </div>
                </form>
            </div>
        </div>
    @endif
@endauth
