# Les composants

35 composants Blade et 14 composants Alpine. **Cherchez ici avant d'écrire du
balisage** : la moitié des pages n'a besoin que d'assembler ce qui existe.

Les treize composants hérités de Breeze (`primary-button`, `text-input`,
`nav-link`…) ne sont plus utilisés nulle part : ils restent pour compatibilité,
n'en ajoutez pas d'usage.

## Afficher une personne

| Composant | Props | Ce que ça fait |
|---|---|---|
| `<x-avatar>` | `user`, `size` (`sm`/`md`/`lg`), `ring` | Photo, ou initiales sur un rond neutre si absente. |
| `<x-dev-card>` | `developer`, `score`, `matchedSkills` | La carte d'un développeur dans une liste. `score` affiche l'anneau de correspondance. |
| `<x-devscore>` | `score`, `size` | La pastille de DevScore. |
| `<x-availability>` | `user`, `showLabel` | Disponible / occupé, avec sa pastille. |
| `<x-country-label>` | `country`, `city` | Drapeau SVG + nom du pays (+ ville). Gère `country = null`. |
| `<x-flag>` | `country` ou `code`, `size` | Le drapeau seul, en SVG. **Jamais l'emoji de la base.** |
| `<x-badge-icon>` | `badge` | L'icône d'un badge, choisie d'après son **slug**. |

## Afficher un contenu

| Composant | Props | Ce que ça fait |
|---|---|---|
| `<x-project-card>` | `project` | La carte d'un projet. Met en avant les postes ouverts. |
| `<x-resource-card>` | `resource` | La carte d'une ressource. Les compteurs disparaissent si la requête ne les a pas chargés. |
| `<x-project-status>` | `status` | Idée / Recrutement / En cours / Terminé, en pastille. |
| `<x-skill-chips>` | `skills`, `limit`, `highlight`, `withLevel` | Les compétences en puces, avec un « +N » au-delà de `limit`. |
| `<x-star-rating>` | `value`, `showValue`, `size` | Une note sur cinq, en étoiles dessinées. Lecture seule. |
| `<x-stat>` | `value`, `label` | Un compteur nu, sans boîte. |

## Structurer une page

| Composant | Props | Ce que ça fait |
|---|---|---|
| `<x-page-header>` | `title`, `subtitle`, `backLink`, `backLabel`, `actions` | L'en-tête standard. Le slot par défaut sert aux sous-titres riches, la prop `subtitle` n'accepte que du texte. |
| `<x-section-heading>` | `title`, `count`, `action` | Le titre d'une section dans une carte. |
| `<x-empty-state>` | `icon`, `title`, `action` | Le message d'une liste vide. Le slot porte l'explication. |
| `<x-flash>` | `errorKeys` | Le message de `session('status')` et les erreurs métier nommées. |
| `<x-icon>` | `name`, `strokeWidth`, `symbol` | 82 icônes. Avec `symbol`, rend un `<symbol>` réutilisable via `<use>`. |
| `<x-notification-icon>` | `emoji`, `sprite` | Traduit l'emoji d'une notification en icône. `sprite` dépose les symboles et la table. |
| `<x-theme-toggle>` | — | La bascule clair / sombre. |
| `<x-logo>` | `withText` | La marque. |

## Les partials réutilisables

Ce ne sont pas des composants : on les appelle avec `@include` et un tableau.

```blade
@include('partials.favorite-button', ['type' => 'resource', 'model' => $resource])

@include('partials.report-button', [
    'type' => 'comment', 'model' => $comment,
    'authorId' => $comment->user_id, 'compact' => true,
])
```

`type` vaut `developer`, `resource`, `project` ou, pour le signalement,
`comment`. Les deux ne rendent rien pour un visiteur ni sur son propre contenu.

## Les composants Alpine

Déclarés dans `resources/js/app.js`, sauf indication contraire.

| Nom | Signature | Rôle |
|---|---|---|
| `themeToggle` | — | Bascule et mémorise le thème. |
| `filterDrawer` | — | `open()`, `close()`, `isOpen` : le tiroir de filtres sur mobile. |
| `skillPicker` | `(selected, max)` | Liste de compétences filtrable et plafonnée. |
| `charCounter` | `(valeur, max)` | `length`, `remaining`, `isNearLimit`. **L'ordre des arguments compte.** |
| `imagePreview` | `(urlActuelle)` | Aperçu d'une image avant envoi. |
| `confirmAction` | — | `ask()`, `cancel()`, `isConfirming` : confirmation en deux temps. |
| `dismissible` | `(délai)` | Un message qu'on peut fermer. |
| `copyToClipboard` | `(texte)` | `copy()`, `hasCopied`. |
| `animatedScore` | `(cible, délai)` | Un score qui monte de 0 à sa valeur. |
| `aiSearchForm` | — | Le formulaire de DevLink AI et son attente. |
| `notificationBell` | `({pollUrl, readAllUrl, csrf, initialUnread})` | La cloche. Fourni par le backend (`notifications.js`). |
| `messagesBadge` | `({unread, meId})` | Le compteur de messages non lus de la barre. |
| `conversationList` | `({items, activeId, meId})` | La liste des conversations. |
| `chatThread` | `({conversationId, meId, messages, …})` | Le fil d'une conversation. |

Les quatre derniers viennent du backend, avec `messages.js` et
`notifications.js`. **Gardez leurs noms de propriétés** : vous pouvez tout
redessiner autour, pas les renommer. Voir [messagerie.md](messagerie.md) et
[notifications.md](notifications.md).

## Écrire un nouveau composant

Trois questions avant de créer un fichier :

1. **Est-ce que ça se répète au moins trois fois ?** Sinon, laissez le balisage
   dans la vue.
2. **Est-ce que ça porte une décision visuelle ?** Un composant qui ne fait que
   passer des classes n'apporte rien : une classe dans `app.css` suffit.
3. **Est-ce que le nom est en anglais ?** Le code l'est, l'interface non.

Et commentez **pourquoi**, pas quoi. `{{-- Carte d'un projet --}}` n'apprend
rien ; « le titre passe avant les étiquettes : on lit le nom du projet, puis sa
catégorie » explique une décision qu'on ne défera pas par accident.
