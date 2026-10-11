/**
 * Notification bell (cahier, section 14): checks for news every 10 seconds,
 * plays a short "ding" for each new notification, and can be muted (remembered per browser).
 *
 * Usage: <div x-data="notificationBell({ pollUrl, readAllUrl, csrf, initialUnread })">
 */
const POLL_EVERY_MS = 10_000;
const DROPDOWN_SIZE = 8;
const MUTE_KEY = 'devlink:notifications-muted';

function storage(action, value) {
    try {
        return action === 'get' ? localStorage.getItem(MUTE_KEY) : localStorage.setItem(MUTE_KEY, value);
    } catch {
        return null; // private browsing or blocked storage: sound stays on
    }
}

export default function notificationBell({ pollUrl, readAllUrl, csrf, initialUnread = 0 }) {
    return {
        open: false,
        unread: initialUnread,
        items: [],
        since: null,
        muted: false,
        audio: null,
        timer: null,
        baseTitle: document.title.replace(/^\(\d+\+?\)\s*/, ''),

        init() {
            this.muted = storage('get') === '1';
            this.updateTitle();

            // Browsers only allow sound after the user has interacted with the page.
            const unlock = () => this.unlockAudio();
            document.addEventListener('click', unlock, { once: true });
            document.addEventListener('keydown', unlock, { once: true });

            this.poll();
            this.timer = setInterval(() => this.poll(), POLL_EVERY_MS);
            // Check right away when the member comes back to the tab.
            document.addEventListener('visibilitychange', () => !document.hidden && this.poll());
        },

        async poll() {
            if (document.hidden && this.since) {
                return; // no need to ask while the tab is in the background
            }

            try {
                const url = this.since ? `${pollUrl}?since=${encodeURIComponent(this.since)}` : pollUrl;
                const response = await fetch(url, {
                    headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    credentials: 'same-origin',
                });

                if (response.status === 401 || response.status === 419) {
                    clearInterval(this.timer); // logged out or session expired
                    return;
                }
                if (!response.ok) {
                    return;
                }

                const data = await response.json();
                const known = new Set(this.items.map((item) => item.id));
                const fresh = data.notifications.filter((item) => !known.has(item.id));

                // Sound only for notifications that arrived after the first load.
                if (this.since && fresh.some((item) => !item.read)) {
                    this.ding();
                }

                this.items = [...fresh, ...this.items].slice(0, DROPDOWN_SIZE);
                this.unread = data.unread;
                this.since = data.server_time;
                this.updateTitle();
            } catch {
                // Network hiccup: try again at the next tick.
            }
        },

        async markAllRead() {
            this.unread = 0;
            this.items = this.items.map((item) => ({ ...item, read: true }));
            this.updateTitle();

            await fetch(readAllUrl, {
                method: 'POST',
                headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest' },
                credentials: 'same-origin',
            }).catch(() => {});
        },

        toggleMute() {
            this.muted = !this.muted;
            storage('set', this.muted ? '1' : '0');
            if (!this.muted) {
                this.ding(); // let the member hear the sound they just turned on
            }
        },

        updateTitle() {
            const count = this.unread > 99 ? '99+' : this.unread;
            document.title = this.unread > 0 ? `(${count}) ${this.baseTitle}` : this.baseTitle;
        },

        unlockAudio() {
            const AudioContext = window.AudioContext || window.webkitAudioContext;
            if (!AudioContext) {
                return;
            }
            this.audio ??= new AudioContext();
            if (this.audio.state === 'suspended') {
                this.audio.resume();
            }
        },

        /** Two short notes (A5 then E6), like a soft "ding-dong". */
        ding() {
            if (this.muted) {
                return;
            }
            this.unlockAudio();
            if (!this.audio) {
                return;
            }

            const now = this.audio.currentTime;
            [
                [880, 0],
                [1318.5, 0.13],
            ].forEach(([frequency, offset]) => {
                const oscillator = this.audio.createOscillator();
                const gain = this.audio.createGain();
                oscillator.type = 'sine';
                oscillator.frequency.value = frequency;
                gain.gain.setValueAtTime(0.0001, now + offset);
                gain.gain.exponentialRampToValueAtTime(0.25, now + offset + 0.02);
                gain.gain.exponentialRampToValueAtTime(0.0001, now + offset + 0.4);
                oscillator.connect(gain).connect(this.audio.destination);
                oscillator.start(now + offset);
                oscillator.stop(now + offset + 0.45);
            });
        },
    };
}
