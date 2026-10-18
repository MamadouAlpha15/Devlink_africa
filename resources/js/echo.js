import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

/*
|--------------------------------------------------------------------------
| Temps réel (Laravel Reverb)
|--------------------------------------------------------------------------
| La connexion WebSocket n'est ouverte que pour un membre connecté : les
| visiteurs n'en ont pas besoin. Sans clé Reverb (ou serveur arrêté), la
| messagerie retombe sur une interrogation régulière du serveur.
*/

const isMember = document.querySelector('meta[name="africommit-user"]') !== null;

if (isMember && import.meta.env.VITE_REVERB_APP_KEY) {
    window.Pusher = Pusher;

    window.Echo = new Echo({
        broadcaster: 'reverb',
        key: import.meta.env.VITE_REVERB_APP_KEY,
        wsHost: import.meta.env.VITE_REVERB_HOST,
        wsPort: import.meta.env.VITE_REVERB_PORT ?? 80,
        wssPort: import.meta.env.VITE_REVERB_PORT ?? 443,
        forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'https') === 'https',
        enabledTransports: ['ws', 'wss'],
    });
}

/** Vrai quand la connexion WebSocket est réellement établie. */
export function isRealtimeConnected() {
    return window.Echo?.connector?.pusher?.connection?.state === 'connected';
}

/** Identifiant de la connexion, pour ne pas se renvoyer ses propres messages. */
export function socketHeaders() {
    const socketId = window.Echo?.socketId?.();

    return socketId ? { 'X-Socket-ID': socketId } : {};
}
