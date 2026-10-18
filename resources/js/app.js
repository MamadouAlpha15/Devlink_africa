import './echo';
import Alpine from 'alpinejs';
import notificationBell from './notifications';
import { chatThread, conversationList, messagesBadge } from './messages';

/*
|--------------------------------------------------------------------------
| Thème clair / sombre
|--------------------------------------------------------------------------
| Le thème choisi est conservé dans le localStorage. Sans choix explicite,
| on suit la préférence du système. Un script inline dans <head> applique
| déjà la classe `dark` avant le premier rendu pour éviter tout clignotement
| (voir resources/views/layouts/partials/theme-script.blade.php).
*/

const THEME_STORAGE_KEY = 'africommit.theme';

/** Lit le thème enregistré, ou `null` si l'utilisateur n'a jamais choisi. */
function storedTheme() {
    try {
        return window.localStorage.getItem(THEME_STORAGE_KEY);
    } catch {
        // Navigation privée ou stockage bloqué : on retombe sur le système.
        return null;
    }
}

/** Le thème réellement affiché à cet instant. */
function activeTheme() {
    return document.documentElement.classList.contains('dark') ? 'dark' : 'light';
}

/** Applique un thème au document et l'enregistre. */
function applyTheme(theme) {
    document.documentElement.classList.toggle('dark', theme === 'dark');
    document.documentElement.style.colorScheme = theme;

    try {
        window.localStorage.setItem(THEME_STORAGE_KEY, theme);
    } catch {
        // Le thème restera actif jusqu'au rechargement de la page.
    }
}

// Tant que l'utilisateur n'a pas tranché, on suit les changements du système.
window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', (event) => {
    if (storedTheme() === null) {
        document.documentElement.classList.toggle('dark', event.matches);
    }
});

/**
 * Bouton de bascule du thème.
 * Usage : <button x-data="themeToggle" @click="toggle()"> … </button>
 */
Alpine.data('themeToggle', () => ({
    theme: activeTheme(),

    toggle() {
        this.theme = this.theme === 'dark' ? 'light' : 'dark';
        applyTheme(this.theme);
    },

    get isDark() {
        return this.theme === 'dark';
    },

    get label() {
        return this.isDark ? 'Passer en thème clair' : 'Passer en thème sombre';
    },
}));

/*
|--------------------------------------------------------------------------
| AfriCommit AI — formulaire de recherche
|--------------------------------------------------------------------------
| L'appel à l'IA prend quelques secondes : on verrouille le formulaire et on
| affiche des étapes qui défilent pour que l'attente reste lisible.
*/

Alpine.data('aiSearchForm', (initialQuery = '') => ({
    query: initialQuery,
    isSearching: false,
    stepIndex: 0,
    stepTimer: null,

    steps: [
        'Lecture de votre demande…',
        'Identification des compétences…',
        'Repérage du pays et du type de projet…',
        'Comparaison avec les profils disponibles…',
        'Classement par pourcentage de correspondance…',
    ],

    /** Nombre de caractères saisis (le backend exige entre 10 et 500). */
    get length() {
        return this.query.length;
    },

    get isTooShort() {
        return this.length > 0 && this.length < 10;
    },

    get canSubmit() {
        return this.length >= 10 && this.length <= 500 && !this.isSearching;
    },

    /** Remplit le champ avec une phrase d'exemple. */
    useExample(example) {
        this.query = example;
        this.$refs.queryField?.focus();
    },

    /** Lance l'animation d'attente puis laisse le formulaire partir. */
    start() {
        if (this.isSearching) {
            return;
        }

        this.isSearching = true;
        this.stepIndex = 0;

        this.stepTimer = window.setInterval(() => {
            // On s'arrête sur la dernière étape : la page va changer d'elle-même.
            if (this.stepIndex < this.steps.length - 1) {
                this.stepIndex += 1;
            }
        }, 1100);
    },

    /** Si l'utilisateur revient en arrière, le formulaire doit être réutilisable. */
    reset() {
        window.clearInterval(this.stepTimer);
        this.isSearching = false;
    },

    get currentStep() {
        return this.steps[this.stepIndex];
    },
}));

/*
|--------------------------------------------------------------------------
| Sélecteur de compétences
|--------------------------------------------------------------------------
| Remplace un <select multiple> peu pratique par une liste de cases à cocher
| filtrable. Les champs gardent le nom attendu par le backend (`skills[]`).
*/

Alpine.data('skillPicker', (selectedIds = [], maxSkills = 10) => ({
    search: '',
    selected: selectedIds.map(Number),
    max: maxSkills,

    isSelected(id) {
        return this.selected.includes(Number(id));
    },

    toggle(id) {
        const skillId = Number(id);
        const position = this.selected.indexOf(skillId);

        if (position === -1) {
            if (this.isFull) {
                return;
            }
            this.selected.push(skillId);
        } else {
            this.selected.splice(position, 1);
        }
    },

    /** Une compétence déjà cochée reste décochable même quand le quota est atteint. */
    isDisabled(id) {
        return this.isFull && !this.isSelected(id);
    },

    get isFull() {
        return this.selected.length >= this.max;
    },

    get count() {
        return this.selected.length;
    },

    /** Le nom passé en argument correspond-il à la recherche en cours ? */
    matches(name) {
        if (this.search === '') {
            return true;
        }

        return name.toLowerCase().includes(this.search.toLowerCase());
    },
}));

/*
|--------------------------------------------------------------------------
| Panneau de filtres (mobile)
|--------------------------------------------------------------------------
| Sur grand écran les filtres sont une colonne fixe ; sur mobile ils
| s'ouvrent dans un tiroir plein écran.
*/

Alpine.data('filterDrawer', () => ({
    isOpen: false,

    open() {
        this.isOpen = true;
        document.body.classList.add('overflow-hidden');
    },

    close() {
        this.isOpen = false;
        document.body.classList.remove('overflow-hidden');
    },
}));

/*
|--------------------------------------------------------------------------
| Compteur de caractères
|--------------------------------------------------------------------------
| Pour les zones de texte limitées (bio, description, message).
*/

Alpine.data('charCounter', (initialValue = '', maxLength = 1000) => ({
    value: initialValue,
    max: maxLength,

    get length() {
        return this.value.length;
    },

    get remaining() {
        return this.max - this.length;
    },

    /** Passe à l'orange quand il reste moins de 10 % de la place. */
    get isNearLimit() {
        return this.remaining <= this.max * 0.1;
    },
}));

/*
|--------------------------------------------------------------------------
| Aperçu d'image avant envoi
|--------------------------------------------------------------------------
| Photo de profil et image de couverture d'un projet.
*/

Alpine.data('imagePreview', (existingUrl = null) => ({
    previewUrl: existingUrl,
    fileName: null,

    pick(event) {
        const file = event.target.files?.[0];

        if (!file) {
            return;
        }

        this.fileName = file.name;
        this.previewUrl = URL.createObjectURL(file);
    },

    /** Vide le champ fichier et revient à l'état initial. */
    clear() {
        this.previewUrl = existingUrl;
        this.fileName = null;

        if (this.$refs.fileField) {
            this.$refs.fileField.value = '';
        }
    },

    get hasPreview() {
        return this.previewUrl !== null;
    },
}));

/*
|--------------------------------------------------------------------------
| Message éphémère
|--------------------------------------------------------------------------
| Les messages de session disparaissent au bout de quelques secondes.
*/

Alpine.data('dismissible', (autoCloseAfter = 6000) => ({
    isVisible: true,

    init() {
        if (autoCloseAfter > 0) {
            window.setTimeout(() => {
                this.isVisible = false;
            }, autoCloseAfter);
        }
    },

    dismiss() {
        this.isVisible = false;
    },
}));

/*
|--------------------------------------------------------------------------
| Confirmation avant une action destructrice
|--------------------------------------------------------------------------
| Deux temps : un premier clic demande confirmation, le second envoie le
| formulaire. Remplace window.confirm() sans quitter la page.
*/

Alpine.data('confirmAction', () => ({
    isConfirming: false,
    resetTimer: null,

    ask() {
        this.isConfirming = true;

        // Sans réponse au bout de 5 secondes, on revient à l'état normal.
        window.clearTimeout(this.resetTimer);
        this.resetTimer = window.setTimeout(() => {
            this.isConfirming = false;
        }, 5000);
    },

    cancel() {
        window.clearTimeout(this.resetTimer);
        this.isConfirming = false;
    },
}));

/*
|--------------------------------------------------------------------------
| Barre / anneau de score animé
|--------------------------------------------------------------------------
| Le pourcentage part de 0 et monte jusqu'à sa valeur au premier affichage.
*/

Alpine.data('animatedScore', (targetValue = 0, delay = 120) => ({
    value: 0,

    init() {
        // Un seul saut vers la valeur finale : la transition CSS fait le reste.
        window.setTimeout(() => {
            this.value = targetValue;
        }, delay);
    },

    get target() {
        return targetValue;
    },
}));

/*
|--------------------------------------------------------------------------
| Copier dans le presse-papier
|--------------------------------------------------------------------------
| Lien de partage d'un profil ou d'un projet.
*/

Alpine.data('copyToClipboard', (textToCopy = '') => ({
    hasCopied: false,

    async copy() {
        try {
            await navigator.clipboard.writeText(textToCopy || window.location.href);
            this.hasCopied = true;
            window.setTimeout(() => {
                this.hasCopied = false;
            }, 2000);
        } catch {
            // Presse-papier refusé (http, permission) : on ne bloque pas l'interface.
        }
    },
}));

window.Alpine = Alpine;

Alpine.data('notificationBell', notificationBell);
Alpine.data('chatThread', chatThread);
Alpine.data('conversationList', conversationList);
Alpine.data('messagesBadge', messagesBadge);

Alpine.start();

/*
|--------------------------------------------------------------------------
| Apparition au défilement
|--------------------------------------------------------------------------
| Révèle les éléments portant la classe `.reveal` quand ils entrent dans
| la fenêtre, une seule fois chacun.
|
| Trois garde-fous, dans cet ordre :
| - si l'utilisateur a demandé moins d'animations, tout est révélé
|   immédiatement et aucune transition n'est jouée ;
| - si IntersectionObserver n'existe pas (navigateur ancien), idem ;
| - si le script ne s'exécute pas du tout, la classe `js` n'est jamais
|   posée sur <html> et le CSS ne masque donc rien.
|
| Dans les trois cas, le contenu reste lisible. L'animation est un bonus,
| jamais une condition d'accès au contenu.
*/

const REDUCED_MOTION_QUERY = '(prefers-reduced-motion: reduce)';

/** Révèle une liste d'éléments sans transition. */
function revealImmediately(elements) {
    elements.forEach((element) => element.classList.add('is-visible'));
}

function initScrollReveal() {
    const elements = Array.from(document.querySelectorAll('.reveal, .reveal-bar'));

    if (elements.length === 0) {
        return;
    }

    const prefersReducedMotion = window.matchMedia(REDUCED_MOTION_QUERY).matches;

    if (prefersReducedMotion || typeof IntersectionObserver === 'undefined') {
        revealImmediately(elements);
        return;
    }

    const observer = new IntersectionObserver(
        (entries) => {
            entries.forEach((entry) => {
                if (! entry.isIntersecting) {
                    return;
                }

                entry.target.classList.add('is-visible');
                // Une seule fois : on cesse d'observer l'élément révélé.
                observer.unobserve(entry.target);
            });
        },
        {
            // Déclenche un peu avant le bas de la fenêtre, pour que
            // l'élément soit déjà en place quand le regard l'atteint.
            rootMargin: '0px 0px -8% 0px',
            threshold: 0.15,
        }
    );

    elements.forEach((element) => observer.observe(element));
}

/*
|--------------------------------------------------------------------------
| Compteur animé
|--------------------------------------------------------------------------
| Fait monter un nombre jusqu'à sa valeur, une seule fois, à l'apparition.
| Le nombre final est écrit dans le HTML côté serveur : si le script ne
| tourne pas, la bonne valeur est déjà affichée.
*/

function animateCount(element, target, duration) {
    const start = performance.now();

    function step(now) {
        const progress = Math.min((now - start) / duration, 1);
        // Décélération : rapide au début, posé à l'arrivée.
        const eased = 1 - Math.pow(1 - progress, 3);

        element.textContent = String(Math.round(target * eased));

        if (progress < 1) {
            window.requestAnimationFrame(step);
        }
    }

    window.requestAnimationFrame(step);
}

function initCounters() {
    const counters = Array.from(document.querySelectorAll('[data-count-to]'));

    if (counters.length === 0) {
        return;
    }

    const prefersReducedMotion = window.matchMedia(REDUCED_MOTION_QUERY).matches;

    if (prefersReducedMotion || typeof IntersectionObserver === 'undefined') {
        return; // La valeur finale est déjà dans le HTML.
    }

    const observer = new IntersectionObserver(
        (entries) => {
            entries.forEach((entry) => {
                if (! entry.isIntersecting) {
                    return;
                }

                const element = entry.target;
                const target = Number.parseInt(element.dataset.countTo, 10);

                observer.unobserve(element);

                if (Number.isFinite(target)) {
                    animateCount(element, target, 900);
                }
            });
        },
        { threshold: 0.6 }
    );

    counters.forEach((counter) => observer.observe(counter));
}

function initMotion() {
    // Le bundle est arrivé : on désarme le garde-fou posé dans <head>.
    window.clearTimeout(window.__africommitMotionWatchdog);

    initScrollReveal();
    initCounters();
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initMotion, { once: true });
} else {
    initMotion();
}
