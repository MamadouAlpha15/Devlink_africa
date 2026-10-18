{{--
    Messagerie (cahier, section 13) : liste des conversations et conversation ouverte.

    Variables : $conversations (lignes de liste, voir App\Services\Messaging::summary)
    et, quand une conversation est ouverte : $conversation, $other, $messages,
    $readUpTo, $canReply.

    Le temps réel vit dans resources/js/messages.js. Sans JavaScript, le
    formulaire d'envoi fonctionne quand même (rechargement de la page).

    Sur téléphone, on montre soit la liste, soit la conversation, jamais les deux.
--}}
@php
    $isOpen = $conversation !== null;
@endphp

<x-app-layout :title="$isOpen ? 'Messages · '.$other->name : 'Messages'">
    <div class="mx-auto max-w-6xl px-4 py-6 sm:px-6 lg:px-8 lg:py-8">

        <div @class(['hidden lg:block' => $isOpen])>
            <x-page-header
                title="Messages"
                subtitle="Vos conversations privées avec les membres d'AfriCommit."
            />
        </div>

        <div @class(["grid gap-4 lg:mt-6 lg:h-[calc(100vh-14rem)] lg:min-h-[32rem] lg:grid-cols-[20rem_minmax(0,1fr)]", "mt-6" => ! $isOpen])>

            {{-- ==========  Liste des conversations  ========== --}}
            <nav
                aria-label="Conversations"
                x-data="conversationList({
                    items: @js($conversations),
                    activeId: @js($conversation?->id),
                    meId: @js(auth()->id()),
                })"
                @class(['card min-h-0 flex-col overflow-hidden', 'hidden lg:flex' => $isOpen, 'flex' => ! $isOpen])
            >
                <p class="border-b border-ink-200 px-4 py-3 text-sm font-semibold text-ink-900 dark:border-ink-800 dark:text-white">
                    Conversations
                </p>

                <ul class="min-h-0 flex-1 divide-y divide-ink-100 overflow-y-auto dark:divide-ink-800/70">
                    <template x-for="item in items" :key="item.id">
                        <li>
                            <a
                                :href="item.url"
                                class="flex items-center gap-3 px-4 py-3 transition-colors hover:bg-ink-50 dark:hover:bg-ink-800/40"
                                :class="isActive(item) && 'bg-brand-50 hover:bg-brand-50 dark:bg-brand-950/60 dark:hover:bg-brand-950/60'"
                                :aria-current="isActive(item) ? 'page' : null"
                            >
                                <template x-if="item.other.avatar_url">
                                    <img :src="item.other.avatar_url" :alt="`Photo de ${item.other.name}`" class="h-10 w-10 shrink-0 rounded-full border border-ink-200 object-cover dark:border-ink-800">
                                </template>
                                <template x-if="! item.other.avatar_url">
                                    <span class="avatar-fallback h-10 w-10 text-xs" x-text="item.other.initials"></span>
                                </template>

                                <span class="min-w-0 flex-1">
                                    <span class="flex items-baseline justify-between gap-2">
                                        <span class="truncate text-sm font-semibold text-ink-900 dark:text-white" x-text="item.other.name"></span>
                                        <span class="tabular shrink-0 text-xs text-ink-500 dark:text-ink-400" x-text="item.last ? formatListTime(item.last.sent_at) : ''"></span>
                                    </span>
                                    <span class="mt-0.5 flex items-center gap-2">
                                        <span
                                            class="truncate text-sm"
                                            :class="item.unread > 0 ? 'font-medium text-ink-900 dark:text-white' : 'text-ink-500 dark:text-ink-400'"
                                            x-text="item.last ? (item.last.mine ? 'Vous : ' : '') + item.last.body : ''"
                                        ></span>
                                        <span
                                            x-show="item.unread > 0"
                                            x-text="item.unread"
                                            class="tabular ml-auto inline-flex h-5 min-w-[1.25rem] shrink-0 items-center justify-center rounded-full bg-brand-600 px-1.5 text-[0.6875rem] font-semibold text-white dark:bg-brand-500"
                                        ></span>
                                    </span>
                                </span>
                            </a>
                        </li>
                    </template>
                </ul>

                <div x-show="items.length === 0" @if ($conversations->isNotEmpty()) x-cloak @endif class="px-4 py-10 text-center">
                    <p class="font-semibold text-ink-900 dark:text-white">Aucune conversation</p>
                    <p class="mt-2 text-sm text-ink-600 dark:text-ink-400">
                        Ouvrez le profil d'un développeur et cliquez sur « Envoyer un message ».
                    </p>
                    <a href="{{ route('devs.index') }}" class="btn-secondary btn-sm mt-4">
                        <x-icon name="users" class="h-4 w-4" />
                        Trouver des développeurs
                    </a>
                </div>
            </nav>

            {{-- ==========  Conversation ouverte  ========== --}}
            @if ($isOpen)
                <section
                    aria-label="Conversation avec {{ $other->name }}"
                    x-data="chatThread({
                        conversationId: @js($conversation->id),
                        meId: @js(auth()->id()),
                        messages: @js($messages),
                        readUpTo: @js($readUpTo),
                        storeUrl: @js(route('messages.store', $conversation)),
                        readUrl: @js(route('messages.read', $conversation)),
                        pollUrl: @js(route('messages.poll', $conversation)),
                        csrf: @js(csrf_token()),
                        canReply: @js($canReply),
                    })"
                    class="card flex h-[calc(100dvh-7rem)] min-h-[26rem] flex-col overflow-hidden lg:h-auto lg:min-h-0"
                >
                    {{-- En-tête : interlocuteur --}}
                    <header class="flex items-center gap-3 border-b border-ink-200 px-4 py-3 dark:border-ink-800">
                        <a href="{{ route('messages.index') }}" class="btn btn-ghost btn-square -ml-2 lg:hidden" aria-label="Retour aux conversations">
                            <x-icon name="arrow-left" class="h-5 w-5" />
                        </a>
                        <a href="{{ route('devs.show', $other) }}" class="flex min-w-0 items-center gap-3">
                            <x-avatar :user="$other" size="sm" />
                            <span class="min-w-0">
                                <span class="block truncate font-semibold text-ink-900 hover:text-brand-600 dark:text-white dark:hover:text-brand-400">{{ $other->name }}</span>
                                <span class="block text-xs text-ink-500 dark:text-ink-400">Voir le profil</span>
                            </span>
                        </a>
                    </header>

                    {{-- Fil des messages --}}
                    <div x-ref="scroller" class="min-h-0 flex-1 space-y-1 overflow-y-auto bg-ink-50/60 px-4 py-4 dark:bg-ink-950/40" aria-live="polite">
                        <template x-for="(message, index) in messages" :key="message.id">
                            <div>
                                <p
                                    x-show="startsNewDay(index)"
                                    class="py-3 text-center text-xs font-medium text-ink-500 dark:text-ink-400"
                                    x-text="formatDay(message.sent_at)"
                                ></p>

                                <div class="flex" :class="isMine(message) ? 'justify-end' : 'justify-start'">
                                    <div class="max-w-[85%] sm:max-w-[70%]">
                                        <p
                                            class="whitespace-pre-line break-words rounded-2xl px-3.5 py-2 text-sm leading-relaxed"
                                            :class="isMine(message)
                                                ? 'rounded-br-md bg-brand-600 text-white dark:bg-brand-500'
                                                : 'rounded-bl-md border border-ink-200 bg-white text-ink-900 dark:border-ink-800 dark:bg-ink-900 dark:text-ink-100'"
                                            x-text="message.body"
                                        ></p>
                                        <p
                                            class="tabular mt-1 px-1 text-[0.6875rem] text-ink-500 dark:text-ink-400"
                                            :class="isMine(message) ? 'text-right' : 'text-left'"
                                        >
                                            <span x-text="formatTime(message.sent_at)"></span>
                                            <span x-show="statusFor(message)" x-text="'· ' + statusFor(message)"></span>
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </template>

                        <div x-show="messages.length === 0" @if ($messages->isNotEmpty()) x-cloak @endif class="flex h-full flex-col items-center justify-center text-center">
                            <x-avatar :user="$other" size="lg" />
                            <p class="mt-3 font-semibold text-ink-900 dark:text-white">Démarrez la conversation</p>
                            <p class="mt-1 max-w-xs text-sm text-ink-600 dark:text-ink-400">
                                Présentez-vous à {{ $other->first_name ?: $other->name }} et dites-lui pourquoi vous lui écrivez.
                            </p>
                        </div>
                    </div>

                    {{-- Saisie --}}
                    @if ($canReply)
                        <form
                            method="POST"
                            action="{{ route('messages.store', $conversation) }}"
                            @submit.prevent="send()"
                            class="border-t border-ink-200 px-3 py-3 dark:border-ink-800"
                        >
                            @csrf
                            <p x-show="error" x-cloak x-text="error" class="mb-2 px-1 text-sm text-red-600 dark:text-red-400" role="alert"></p>

                            <div class="flex items-end gap-2">
                                <label for="message-body" class="sr-only">Votre message</label>
                                <textarea
                                    id="message-body"
                                    name="body"
                                    x-ref="input"
                                    x-model="body"
                                    @input="resizeTextarea()"
                                    @keydown="onKeydown($event)"
                                    rows="1"
                                    maxlength="2000"
                                    required
                                    placeholder="Écrire un message…"
                                    class="textarea max-h-40 resize-none"
                                ></textarea>
                                <button
                                    type="submit"
                                    class="btn-primary btn-square h-10 w-10 shrink-0"
                                    :disabled="isSending || body.trim() === ''"
                                    aria-label="Envoyer"
                                >
                                    <x-icon name="send" class="h-4 w-4" />
                                </button>
                            </div>
                            <p class="mt-1.5 hidden px-1 text-xs text-ink-500 dark:text-ink-400 sm:block">
                                Entrée pour envoyer, Maj + Entrée pour aller à la ligne.
                            </p>
                        </form>
                    @else
                        <p class="border-t border-ink-200 px-4 py-4 text-center text-sm text-ink-600 dark:border-ink-800 dark:text-ink-400">
                            Ce membre n'est plus joignable. Vous pouvez relire la conversation, mais plus y répondre.
                        </p>
                    @endif
                </section>
            @else
                <section class="card hidden flex-col items-center justify-center px-6 text-center lg:flex">
                    <x-icon name="message" class="h-8 w-8 text-ink-300 dark:text-ink-600" />
                    <p class="mt-3 font-semibold text-ink-900 dark:text-white">Choisissez une conversation</p>
                    <p class="mt-1 max-w-sm text-sm text-ink-600 dark:text-ink-400">
                        Vos échanges arrivent ici en temps réel.
                    </p>
                </section>
            @endif
        </div>
    </div>
</x-app-layout>
