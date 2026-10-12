# Front-end DevLink Africa

Ce dossier réunit tout ce qu'il faut pour travailler sur l'interface sans la
casser : les règles qu'elle suit, les briques déjà disponibles, et le contrat
que chaque page passe avec le backend.

L'interface est écrite en **Blade + Tailwind CSS 3 + Alpine 3**, compilée par
Vite. Aucune bibliothèque de composants : tout le vocabulaire visuel vit dans
`resources/css/app.css` et `resources/views/components/`.

En chiffres : 84 vues, 35 composants Blade, 14 composants Alpine, 69 classes de
composants CSS, 82 icônes et 54 drapeaux SVG.

## Par où commencer

| Ce que vous cherchez | Le document |
|---|---|
| Les règles visuelles : couleurs, typographie, surfaces, boutons, formulaires | [design-system.md](design-system.md) |
| Savoir si un composant existe déjà avant d'en écrire un | [composants.md](composants.md) |
| Brancher une page sur le backend : routes, variables, champs | les guides par domaine, ci-dessous |

## Les guides par domaine

Chacun décrit le **contrat backend** d'un domaine : ses URL, les variables que
le contrôleur passe à la vue, les champs de formulaire attendus. Ce sont ces
noms-là qui ne doivent pas changer d'un côté sans prévenir l'autre.

| Domaine | Document | Pages concernées |
|---|---|---|
| Profils | [profils.md](profils.md) | `devs/show`, `developer-profile/edit` |
| Recherche de développeurs | [recherche.md](recherche.md) | `devs/index` |
| Projets et collaboration | [projets.md](projets.md) | `projects/*`, `collaborations/index` |
| DevLink AI | [devlink-ai.md](devlink-ai.md) | `ai/create`, `ai/show` |
| Dev Resources | [ressources.md](ressources.md) | `resources/*` |
| DevScore, classement, badges | [devscore-classement.md](devscore-classement.md) | `devscore/show`, `ranking/index` |
| Favoris | [favoris.md](favoris.md) | `favorites/index`, bouton réutilisable |
| Tableau de bord | [dashboard.md](dashboard.md) | `dashboard` |
| Notifications | [notifications.md](notifications.md) | `notifications/index`, la cloche |
| Messagerie | [messagerie.md](messagerie.md) | `messages/index`, le fil et la liste |
| Écosystème | [ecosysteme.md](ecosysteme.md) | `ecosystem/index` |
| Signalement et administration | [administration.md](administration.md) | `admin/*`, bouton « Signaler » |

## Les cinq règles de l'interface

Elles ne sont pas négociables : une page qui les enfreint détonne immédiatement
au milieu des autres.

1. **Aucun dégradé.** Les fonds sont plats. La profondeur vient des bordures et
   d'une ombre discrète, jamais d'un fondu de couleurs.
2. **Aucun emoji affiché.** Les icônes sont des SVG du jeu maison. Le backend
   stocke pourtant des emojis — pour les pays, les badges et les notifications —
   et ils sont traduits à l'affichage (voir la section suivante).
3. **Code en anglais, interface en français.** Les noms de variables, de
   composants et de fichiers sont anglais ; tout ce que lit un membre est
   français.
4. **L'indigo est réservé à l'action.** Un bouton principal, un lien, un état
   sélectionné. Le reste de l'interface est en gris ardoise. Un élément indigo
   qui ne se clique pas est une erreur.
5. **Le mouvement sert le contenu.** Pas de boucle, pas de rebond, pas de zoom
   au survol. Une animation accompagne l'arrivée d'un élément ou transporte une
   information, sinon elle n'existe pas.

## Les emojis venus de la base

Trois champs de la base contiennent des emojis. Aucun n'arrive à l'écran.

| Champ | Ce que contient la base | Ce que l'interface affiche |
|---|---|---|
| `countries.flag` | un drapeau emoji | un SVG de `public/flags/`, via `<x-flag>` |
| `badges.icon` | un emoji | une icône du jeu, via `<x-badge-icon>`, choisie d'après le **slug** du badge |
| `notifications.data['icon']` | un emoji | une icône du jeu, via `<x-notification-icon>` |

La table de correspondance vit à un seul endroit :
`resources/views/components/notification-icon.blade.php`. Ses clés y sont
écrites en points de code Unicode (`"\u{1F91D}"`) et non en caractères, pour
qu'aucun emoji n'apparaisse dans le code. Un emoji inconnu retombe sur l'icône
cloche — jamais sur une case vide.

Le choix du **slug** plutôt que de l'emoji pour les badges est volontaire : un
emoji peut être changé en base sans prévenir, un slug non.

Deux pièges à connaître si vous ajoutez des données :

- `ActivityFeed::subjectTitle()` **préfixe l'emoji du badge** au nom. Les vues
  qui l'utilisent détectent donc le cas `Badge` et affichent l'icône elles-mêmes
  plutôt que d'appeler cette méthode.
- La réponse JSON de `/notifications/poll` transporte l'emoji brut. C'est la
  cloche, côté navigateur, qui le traduit via le sprite déposé par
  `<x-notification-icon sprite />`.

## Où vivent les choses

```
resources/
  css/app.css                      le design system : 69 classes de composants
  js/app.js                        thème clair/sombre + 10 composants Alpine
  js/notifications.js              la cloche        (livré avec le backend)
  js/messages.js                   la messagerie    (livré avec le backend)
  js/echo.js                       connexion temps réel (Reverb)
  views/
    components/                    35 composants Blade
    layouts/
      app.blade.php                pages connectées et publiques
      guest.blade.php              connexion et inscription
      navigation.blade.php         barre du haut, menu du compte, cloche, messages
      partials/
        head.blade.php             métadonnées, police, politique de referrer
        theme-script.blade.php     applique le thème avant le premier rendu
        footer.blade.php
    admin/                         les cinq pages d'administration
    partials/
      favorite-button.blade.php    bouton Favori réutilisable
      report-button.blade.php      bouton Signaler réutilisable
    vendor/pagination/             la pagination Laravel réhabillée
public/flags/                      54 drapeaux SVG (flag-icons, licence MIT)
tailwind.config.js                 les jetons : couleurs, police, ombres
```

## Lancer le front-end

```bash
composer install
npm install
npm run dev        # serveur Vite, rechargement à chaud
php artisan serve  # http://localhost:8000
```

Pour un rendu de production : `npm run build`.

## Trois pièges qui font perdre une heure

**1. Le CSS compilé n'est pas versionné.** `public/build/` est dans le
`.gitignore`. Après chaque `git pull` ou `git checkout` qui touche
`resources/`, **relancez `npm run build`** — sinon vous regardez le CSS d'une
autre version du projet. Aucun message d'erreur ne vous préviendra : la page
s'affiche simplement sans style.

**2. `public/hot` traîne après un Vite arrêté brutalement.** Tant que ce fichier
existe, Laravel va chercher le CSS et le JS sur `localhost:5173` au lieu des
fichiers compilés. Si Vite n'y répond plus, l'interface perd tout son style.
Supprimez `public/hot` et relancez.

**3. Une couleur statique et une couleur conditionnelle sur le même élément ne
cohabitent pas.** Toutes les règles `text-ink-*` sont compilées **après** les
`text-amber-*` : sur un même élément, `ink` gagne toujours, quel que soit
l'ordre dans l'attribut `class`. Mettez les deux états dans le `:class`
d'Alpine, ou portez la couleur de repos sur l'élément parent.

## Ce qui ne doit pas changer d'un seul côté

Le front-end et le backend se tiennent par des noms. Changer l'un sans l'autre
casse la page en silence, sans erreur PHP.

- les **noms de routes** (`route('resources.show', …)`) ;
- les **noms de champs** de formulaire (`name="rating"`, `name="skills[]"`) ;
- les **paramètres GET** de recherche et de filtre (`?q=`, `?sort=`, `?period=`) ;
- les **clés des variables** passées aux vues (`$filters['country']`, `$stats['projects']`) ;
- les **propriétés des composants Alpine** fournis par le backend : pour la
  cloche, `unread`, `items`, `open`, `muted`, `toggleMute()`, `markAllRead()` ;
  pour la messagerie, voir [messagerie.md](messagerie.md).

Chaque guide par domaine liste ces noms pour son périmètre.
