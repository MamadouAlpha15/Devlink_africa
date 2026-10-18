/**
 * Messagerie (cahier, section 13) en temps réel.
 *
 * Les messages arrivent par WebSocket (Laravel Reverb, voir echo.js). Si la
 * connexion n'est pas établie, la conversation ouverte interroge le serveur
 * toutes les 3 secondes : un message n'est jamais perdu, seulement retardé.
 *
 * Trois composants Alpine :
 *   chatThread        la conversation ouverte (envoi, réception, « Lu »)
 *   conversationList  la liste des conversations (dernier message, non lus)
 *   messagesBadge     le compteur de la barre de navigation
 *
 * Événements partagés sur `window` :
 *   africommit:unread-messages  { total }                  nouveau total de non lus
 *   africommit:thread-message   { conversationId, message } message ajouté à la conversation ouverte
 */
import { isRealtimeConnected, socketHeaders } from './echo';

const POLL_EVERY_MS = 3_000;
const MAX_TEXTAREA_HEIGHT = 160;

function jsonHeaders(csrf) {
    return {
        Accept: 'application/json',
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': csrf,
        'X-Requested-With': 'XMLHttpRequest',
        ...socketHeaders(),
    };
}

function sameDay(a, b) {
    return a.getFullYear() === b.getFullYear() && a.getMonth() === b.getMonth() && a.getDate() === b.getDate();
}

/** « 14:05 » dans le fuseau du navigateur. */
export function formatTime(iso) {
    return new Date(iso).toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' });
}

/** « Aujourd'hui », « Hier » ou « 12 octobre ». */
export function formatDay(iso) {
    const date = new Date(iso);
    const today = new Date();
    const yesterday = new Date();
    yesterday.setDate(today.getDate() - 1);

    if (sameDay(date, today)) {
        return "Aujourd'hui";
    }
    if (sameDay(date, yesterday)) {
        return 'Hier';
    }

    return date.toLocaleDateString('fr-FR', {
        day: 'numeric',
        month: 'long',
        ...(date.getFullYear() !== today.getFullYear() ? { year: 'numeric' } : {}),
    });
}

/** Heure si c'est aujourd'hui, sinon date courte : pour la liste des conversations. */
export function formatListTime(iso) {
    const date = new Date(iso);

    return sameDay(date, new Date())
        ? formatTime(iso)
        : date.toLocaleDateString('fr-FR', { day: 'numeric', month: 'short' });
}

function shareUnreadTotal(total) {
    window.dispatchEvent(new CustomEvent('africommit:unread-messages', { detail: { total } }));
}

/**
 * Usage : <section x-data="chatThread({ conversationId, meId, messages, readUpTo, storeUrl, readUrl, pollUrl, csrf, canReply })">
 */
export function chatThread(config) {
    return {
        messages: config.messages ?? [],
        readUpTo: config.readUpTo ?? 0,
        body: '',
        isSending: false,
        error: '',
        hasPendingRead: false,
        formatTime,
        formatDay,

        init() {
            this.$nextTick(() => this.scrollToBottom());

            if (window.Echo) {
                window.Echo.private(`conversations.${config.conversationId}`)
                    .listen('.message.sent', (event) => this.receive(event.message))
                    .listen('.messages.read', (event) => {
                        if (event.reader_id !== config.meId) {
                            this.readUpTo = Math.max(this.readUpTo, event.read_up_to);
                        }
                    });

                // Après une coupure, on rattrape ce qui a pu être envoyé entre-temps.
                window.Echo.connector?.pusher?.connection?.bind('connected', () => this.poll());
            }

            setInterval(() => {
                if (!isRealtimeConnected() && !document.hidden) {
                    this.poll();
                }
            }, POLL_EVERY_MS);

            document.addEventListener('visibilitychange', () => {
                if (!document.hidden && this.hasPendingRead) {
                    this.markAsRead();
                }
            });
        },

        get lastId() {
            return this.messages.length ? this.messages[this.messages.length - 1].id : 0;
        },

        isMine(message) {
            return message.sender_id === config.meId;
        },

        /** Séparateur de jour avant le premier message de chaque journée. */
        startsNewDay(index) {
            if (index === 0) {
                return true;
            }

            return !sameDay(new Date(this.messages[index - 1].sent_at), new Date(this.messages[index].sent_at));
        },

        /** « Lu » ou « Envoyé », sous mon dernier message seulement. */
        statusFor(message) {
            const mine = this.messages.filter((item) => this.isMine(item));
            if (!mine.length || mine[mine.length - 1].id !== message.id) {
                return '';
            }

            return message.id <= this.readUpTo ? 'Lu' : 'Envoyé';
        },

        receive(message) {
            if (this.messages.some((item) => item.id === message.id)) {
                return;
            }

            const wasAtBottom = this.isNearBottom();
            this.messages.push(message);
            this.messages.sort((a, b) => a.id - b.id);
            window.dispatchEvent(new CustomEvent('africommit:thread-message', {
                detail: { conversationId: config.conversationId, message },
            }));

            if (wasAtBottom || this.isMine(message)) {
                this.$nextTick(() => this.scrollToBottom());
            }

            if (!this.isMine(message)) {
                this.markAsRead();
            }
        },

        async send() {
            const body = this.body.trim();
            if (!body || this.isSending || !config.canReply) {
                return;
            }

            this.isSending = true;
            this.error = '';

            try {
                const response = await fetch(config.storeUrl, {
                    method: 'POST',
                    headers: jsonHeaders(config.csrf),
                    credentials: 'same-origin',
                    body: JSON.stringify({ body }),
                });
                const data = await response.json().catch(() => ({}));

                if (!response.ok) {
                    this.error = this.errorFor(response.status, data);
                    return;
                }

                this.body = '';
                this.resizeTextarea();
                this.receive(data.message);
            } catch {
                this.error = "Connexion perdue. Le message n'a pas été envoyé, réessayez.";
            } finally {
                this.isSending = false;
                this.$nextTick(() => this.$refs.input?.focus());
            }
        },

        errorFor(status, data) {
            if (status === 422) {
                return data.errors?.body?.[0] ?? 'Ce message ne peut pas être envoyé.';
            }
            if (status === 429) {
                return 'Vous envoyez beaucoup de messages. Patientez quelques secondes.';
            }
            if (status === 403) {
                return 'Vous ne pouvez plus écrire à ce membre.';
            }
            if (status === 419) {
                return 'Votre session a expiré. Rechargez la page.';
            }

            return "Le message n'a pas pu être envoyé. Réessayez.";
        },

        /** Entrée envoie, Maj + Entrée passe à la ligne. */
        onKeydown(event) {
            if (event.key === 'Enter' && !event.shiftKey && !event.isComposing) {
                event.preventDefault();
                this.send();
            }
        },

        resizeTextarea() {
            const input = this.$refs.input;
            if (!input) {
                return;
            }
            input.style.height = 'auto';
            input.style.height = `${Math.min(input.scrollHeight, MAX_TEXTAREA_HEIGHT)}px`;
        },

        async markAsRead() {
            if (document.hidden) {
                this.hasPendingRead = true;
                return;
            }
            this.hasPendingRead = false;

            try {
                const response = await fetch(config.readUrl, {
                    method: 'POST',
                    headers: jsonHeaders(config.csrf),
                    credentials: 'same-origin',
                });
                if (response.ok) {
                    shareUnreadTotal((await response.json()).unread_total);
                }
            } catch {
                // Sera marqué comme lu à la prochaine ouverture.
            }
        },

        async poll() {
            try {
                const response = await fetch(`${config.pollUrl}?after=${this.lastId}`, {
                    headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    credentials: 'same-origin',
                });
                if (!response.ok) {
                    return;
                }

                const data = await response.json();
                data.messages.forEach((message) => this.receive(message));
                this.readUpTo = Math.max(this.readUpTo, data.read_up_to);
            } catch {
                // Réseau instable : on réessaiera au prochain tour.
            }
        },

        isNearBottom() {
            const scroller = this.$refs.scroller;

            return !scroller || scroller.scrollHeight - scroller.scrollTop - scroller.clientHeight < 120;
        },

        scrollToBottom() {
            const scroller = this.$refs.scroller;
            if (scroller) {
                scroller.scrollTop = scroller.scrollHeight;
            }
        },
    };
}

/**
 * Usage : <nav x-data="conversationList({ items, activeId, meId })">
 */
export function conversationList(config) {
    return {
        items: config.items ?? [],
        formatListTime,

        init() {
            if (window.Echo) {
                window.Echo.private(`App.Models.User.${config.meId}`)
                    .listen('.message.sent', (event) => this.upsert({
                        ...event.conversation,
                        unread: event.conversation.id === config.activeId ? 0 : event.conversation.unread,
                    }));
            }

            window.addEventListener('africommit:thread-message', (event) => {
                const item = this.items.find((entry) => entry.id === event.detail.conversationId);
                if (item) {
                    const { message } = event.detail;
                    this.upsert({
                        ...item,
                        last: { body: message.body, mine: message.sender_id === config.meId, sent_at: message.sent_at },
                        unread: 0,
                    });
                }
            });
        },

        /** Remplace ou ajoute la conversation, puis la remonte en tête. */
        upsert(conversation) {
            this.items = [conversation, ...this.items.filter((item) => item.id !== conversation.id)];
        },

        isActive(item) {
            return item.id === config.activeId;
        },
    };
}

/**
 * Compteur de la barre de navigation.
 * Usage : <a x-data="messagesBadge({ unread, meId })">
 */
export function messagesBadge(config) {
    return {
        unread: config.unread ?? 0,

        init() {
            window.addEventListener('africommit:unread-messages', (event) => {
                this.unread = event.detail.total;
            });

            if (window.Echo) {
                window.Echo.private(`App.Models.User.${config.meId}`)
                    .listen('.message.sent', (event) => {
                        this.unread = event.unread_total;
                    });
            }
        },

        get label() {
            return this.unread > 99 ? '99+' : String(this.unread);
        },
    };
}
