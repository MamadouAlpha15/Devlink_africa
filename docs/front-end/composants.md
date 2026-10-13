# Les composants

35 briques Blade et 14 composants Alpine. **Cherchez ici avant d'écrire du
balisage** : la moitié des pages n'a besoin que d'assembler ce qui existe.

Les treize composants hérités de Breeze — `primary-button`, `text-input`,
`nav-link` et les autres — ne sont plus utilisés nulle part. Ils restent pour
compatibilité ; n'en ajoutez pas d'usage.

## Afficher une personne

`<x-avatar>` prend un `user`, une `size` parmi `sm`, `md` et `lg`, et un `ring`
facultatif. Il affiche la photo, ou les initiales sur un rond neutre si elle
manque.

`<x-dev-card>` prend un `developer`, et facultativement un `score` — qui fait
apparaître l'anneau de correspondance — et des `matchedSkills` à mettre en
avant. C'est la carte d'un développeur dans une liste.

`<x-devscore>` prend un `score` et une `size`, et rend la pastille de score.
`<x-availability>` prend un `user` et dit s'il est disponible ou occupé.

`<x-country-label>` prend un `country` et une `city` facultative, et rend le
drapeau SVG suivi du nom. Il gère `country = null` sans broncher. `<x-flag>`
rend le drapeau seul, à partir d'un `country` ou d'un `code` — **jamais l'emoji
stocké en base**.

`<x-badge-icon>` prend un `badge` et rend son icône, choisie d'après son slug.

## Afficher un contenu

`<x-project-card>` prend un `project` et met en avant les postes ouverts, parce
que c'est l'information qui donne envie de cliquer. `<x-resource-card>` prend
une `resource` ; ses compteurs disparaissent d'eux-mêmes si la requête ne les a
pas chargés.

`<x-project-status>` prend un `status` et rend la pastille Idée, Recrutement,
En cours ou Terminé.

`<x-skill-chips>` prend des `skills`, une `limit`, des compétences à
`highlight` et un `withLevel` facultatif ; au-delà de la limite, il affiche un
« +N ». `<x-star-rating>` prend une `value`, un `showValue` et une `size`, et
rend une note sur cinq en étoiles dessinées, en lecture seule. `<x-stat>` prend
une `value` et un `label`, et rend un compteur nu, sans boîte.

## Structurer une page

`<x-page-header>` prend un `title`, un `subtitle`, un `backLink` avec son
`backLabel`, et un slot `actions`. Son slot par défaut sert aux sous-titres
riches : la prop `subtitle` n'accepte que du texte.

`<x-section-heading>` prend un `title`, un `count` et un slot `action`, pour un
titre de section dans une carte.

`<x-empty-state>` prend un `icon`, un `title` et un slot `action` ; son slot par
défaut porte l'explication. `<x-flash>` affiche le message de `session('status')`
et les erreurs métier dont on lui donne les clés.

`<x-icon>` prend un `name` parmi 82, un `strokeWidth` facultatif, et un `symbol`
qui le fait rendre un `<symbol>` réutilisable par `<use>`.
`<x-notification-icon>` prend un `emoji` et le traduit ; avec `sprite`, il
dépose les symboles et la table pour la cloche.

`<x-theme-toggle>` rend la bascule clair-sombre, `<x-logo>` la marque.

## Les boutons partagés

Ce ne sont pas des composants : on les appelle par `@include` avec un tableau.

```blade
@include('partials.favorite-button', ['type' => 'resource', 'model' => $resource])

@include('partials.report-button', [
    'type' => 'comment', 'model' => $comment,
    'authorId' => $comment->user_id, 'compact' => true,
])
```

`type` vaut `developer`, `resource`, `project` ou, pour le signalement,
`comment`. Ni l'un ni l'autre ne rend quoi que ce soit pour un visiteur, ou sur
son propre contenu.

## Les composants Alpine

Dix sont définis dans `resources/js/app.js`.

`themeToggle` bascule et mémorise le thème. `filterDrawer` expose `open()`,
`close()` et `isOpen` pour le tiroir de filtres sur mobile. `skillPicker` prend
une sélection et un maximum, et rend la liste filtrable et plafonnée.
`charCounter` prend **une valeur puis un maximum, dans cet ordre**, et expose
`length`, `remaining` et `isNearLimit`. `imagePreview` prend l'URL actuelle et
montre l'aperçu avant envoi. `confirmAction` expose `ask()`, `cancel()` et
`isConfirming` pour la confirmation en deux temps. `dismissible` ferme un
message, `copyToClipboard` expose `copy()` et `hasCopied`, `animatedScore` fait
monter un score de zéro à sa valeur, et `aiSearchForm` gère l'attente de
DevLink AI.

Quatre autres viennent du backend, avec `notifications.js` et `messages.js` :
`notificationBell` pour la cloche, `messagesBadge` pour le compteur de la barre,
`conversationList` et `chatThread` pour la messagerie. **Gardez leurs noms de
propriétés** : tout l'habillage autour est libre, les noms non.
[pages.md](pages.md) les détaille.

## Avant d'en écrire un nouveau

Trois questions. Est-ce que ça se répète au moins trois fois — sinon, laissez le
balisage dans la vue. Est-ce que ça porte une décision visuelle — un composant
qui ne fait que passer des classes n'apporte rien, une classe dans `app.css`
suffit. Et est-ce que le nom est en anglais.

Commentez **pourquoi**, pas quoi. « Carte d'un projet » n'apprend rien ; « le
titre passe avant les étiquettes : on lit le nom du projet, puis sa catégorie »
explique une décision qu'on ne défera pas par accident.
