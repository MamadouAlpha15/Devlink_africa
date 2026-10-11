{{--
    Script posé avant le premier rendu. Il fait deux choses, et seulement
    deux, pour ne jamais retarder l'affichage :

    1. applique le thème enregistré, ce qui évite le flash blanc au
       chargement d'une page en thème sombre ;
    2. pose la classe `js` sur <html>.

    La classe `js` conditionne l'état masqué des éléments animés : sans
    JavaScript elle n'est jamais posée, donc rien n'est caché et la page
    reste entièrement lisible. C'est le seul ordre sûr — masquer en CSS
    puis révéler en JS rendrait la page vide si le script échouait.
--}}
<script>
    (function () {
        document.documentElement.classList.add('js');

        // Garde-fou : `js` autorise le CSS à masquer les éléments animés, mais
        // c'est le bundle qui les révèle. Si ce bundle n'arrive jamais — réseau
        // coupé, requête bloquée, connexion lente — la page resterait vide.
        // Au bout de 2,5 s sans initialisation, on retire `js` : tout
        // redevient visible, sans animation. Le contenu passe avant l'effet.
        window.__devlinkMotionWatchdog = window.setTimeout(function () {
            document.documentElement.classList.remove('js');
        }, 2500);

        try {
            var stored = window.localStorage.getItem('devlink.theme');
            var prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
            var isDark = stored ? stored === 'dark' : prefersDark;

            document.documentElement.classList.toggle('dark', isDark);
            document.documentElement.style.colorScheme = isDark ? 'dark' : 'light';
        } catch (error) {
            // Stockage indisponible (navigation privée) : on reste en thème clair.
        }
    })();
</script>
