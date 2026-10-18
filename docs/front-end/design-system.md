# Le design system

Les jetons sont dans `tailwind.config.js`, les 69 classes de composants dans
`resources/css/app.css`. Avant d'aligner des utilitaires Tailwind dans une vue,
cherchez ici : il y a de fortes chances que la classe existe. C'est ce qui fait
qu'une page écrite dans six mois ressemblera encore aux autres.

## Les couleurs

Deux gammes, pas plus.

`brand` est l'indigo. Il est **réservé à l'action** : bouton principal, lien,
état sélectionné, fonctionnalité IA. Les deux valeurs utiles sont `brand-600`
en thème clair et `brand-400` ou `brand-500` en thème sombre. Un élément indigo
sur lequel on ne peut pas cliquer est une erreur.

`ink` est le gris ardoise. Il porte toute l'interface : texte, bordures, fonds,
états au survol. De `ink-50`, le fond des pages, à `ink-950`, le fond en thème
sombre.

Les couleurs Tailwind par défaut ne servent qu'à signaler un état, jamais à
décorer : `emerald` pour un succès, `amber` pour un avertissement, `red` pour un
danger, et trois teintes de médaille sur le podium du classement.

## La typographie

Inter, chargée depuis fonts.bunny.net, avec les variantes `cv05`, `cv11` et
`calt`. Le corps de texte est à **15 px**, pas 16 : c'est le réglage des
produits denses, plus net qu'un 16 px de site vitrine.

Neuf classes couvrent tous les besoins. `.page-title` fait 28 px, 32 px à
partir de `sm` — un seul par page. `.section-title` fait 20 px, pour un titre de
section dans une carte. `.card-title` fait 15 px en demi-gras, pour une carte
dans une liste. `.lead` fait 17 px, pour le paragraphe d'accroche sous un titre
de page. `.body-text` fait 15 px, pour le corps de texte long. `.muted` fait
14 px, pour une information secondaire. `.caption` fait 12 px en demi-gras, pour
une étiquette au-dessus d'une valeur. `.subtle` et `.field-hint` font 12 px, pour
l'aide sous un champ. `.eyebrow` fait 12 px, pour un sur-titre neutre.

`.tabular` force les chiffres à largeur fixe. **À mettre sur tout nombre qui
s'aligne verticalement** — scores, compteurs, colonnes, rangs. Sans elle, une
colonne de chiffres tremble d'une ligne à l'autre.

## Les surfaces

Trois niveaux. La profondeur vient des bordures, pas des ombres : il n'existe
que **deux ombres** dans tout le produit.

`.card` est une bordure, un fond, un rayon de 12 px, sans aucune ombre — c'est
le cas courant. `.card-raised` y ajoute l'ombre `card`, très légère, pour poser
une surface sur le fond. `.card-interactive` est une carte dont seules les
**couleurs** changent au survol : pas de déplacement, pas d'agrandissement.
`.panel` est une carte avec le rembourrage standard, et c'est ce qu'on utilise le
plus. `.surface-muted` est un aplat gris sans bordure, pour détacher un bloc à
l'intérieur d'une carte.

La seconde ombre, `overlay`, est réservée à ce qui flotte réellement au-dessus
du plan : menus déroulants, modales, panneaux surgissants.

Les rayons suivent la taille de la boîte : 6 px pour une pastille, 8 px pour un
bouton ou une ligne de liste, 12 px pour une carte.

## Les boutons

Tous mesurent 40 px de haut et ont un rayon de 8 px, quelle que soit leur
taille. Aucun ne porte d'ombre portée : `.btn-primary` reçoit une arête
supérieure éclairée, ce qui le pose sans le décoller du fond.

`.btn-primary` marque l'action principale — **une seule par page**.
`.btn-secondary` couvre les actions courantes, avec bordure et fond blanc.
`.btn-ghost` convient aux actions discrètes, sans bordure ni fond au repos.
`.btn-danger` sert à une action destructrice confirmée, `.btn-danger-ghost` à la
même avant confirmation.

Quatre modificateurs : `.btn-xs`, `.btn-sm` et `.btn-lg` pour la taille,
`.btn-square` pour un bouton à icône seule, `.btn-block` pour toute la largeur.
Un bouton à icône seule **doit** porter un `aria-label`.

## Les formulaires

`.label` habille un libellé, `.label-hint` le « facultatif » qui le suit.
`.input`, `.select` et `.textarea` habillent les champs, tous à 40 px de haut.
`.input-group` sert à un champ précédé d'une icône, une recherche par exemple.
`.checkbox` et `.checkbox-row` couvrent la case à cocher et sa ligne cliquable,
`.file-field` le sélecteur de fichier, `.field-hint` l'aide en dessous.

Le focus est marqué par un anneau de 3 px à faible opacité, jamais par un
contour épais. **Ne le retirez jamais** : c'est la seule indication pour qui
navigue au clavier.

## Pastilles, puces et messages

Quatre pastilles d'état — `.badge-neutral`, `.badge-success`, `.badge-warning`,
`.badge-danger` — disent un état en un mot, jamais en capitales.

`.chip` est une étiquette non cliquable, une compétence ou un type.
`.chip-strong` est la même, mise en avant par le **poids** et non par la
couleur. `.chip-button` est un filtre cliquable, et `.chip-button-active` son
état actif.

Attention : `.chip-button-active` est un **modificateur**. Seul, il n'a ni
rembourrage ni rayon, et les filtres se chevauchent. Écrivez
`class="chip-button {{ $actif ? 'chip-button-active' : '' }}"`, jamais l'un à la
place de l'autre.

Quatre bandeaux — `.alert-info`, `.alert-success`, `.alert-warning`,
`.alert-danger` — portent un message au-dessus du contenu.

## Le thème sombre

Piloté par la classe `dark` sur `<html>`, posée par un script en ligne dans
`layouts/partials/theme-script.blade.php` **avant le premier rendu** : sans
cela, une page en thème sombre clignoterait en blanc au chargement. Le choix est
conservé dans `localStorage` sous `africommit.theme` ; sans choix explicite, on
suit la préférence du système.

Chaque classe d'`app.css` porte déjà ses variantes `dark:`. Si vous écrivez des
utilitaires à la main, **pensez à la variante sombre** : c'est l'oubli le plus
fréquent, et il ne se voit qu'en basculant le thème.

## Le mouvement

On n'anime que `opacity` et `transform` — les deux seules propriétés qui
passent par le compositeur, donc sans recalcul de mise en page. Rien ne boucle,
rien ne rebondit, rien ne s'agrandit au survol. Les durées restent courtes :
150 ms pour un changement d'état, 500 ms pour une apparition. Les cascades sont
plafonnées, au-delà de six crans le dernier élément arrive trop tard. Et
`prefers-reduced-motion` désactive tout, **sans rien masquer**.

L'apparition au défilement repose sur la classe `.reveal`, activée par
`initScrollReveal()` dans `app.js`, et conditionnée à la classe `js` sur
`<html>` : sans JavaScript, rien n'est masqué. Un chien de garde de 2,5 s retire
cette classe si le bundle n'arrive jamais — autrement la page resterait vide.

## Le piège des couleurs conditionnelles

Les règles `text-ink-*` sont compilées **après** les `text-amber-*`. Sur un même
élément, `ink` gagne donc toujours, quel que soit l'ordre dans l'attribut.

```blade
{{-- Ne marche pas : l'ambre ne s'applique jamais. --}}
<span class="text-ink-300" :class="actif && 'text-amber-500'">

{{-- Correct : les deux états passent par :class. --}}
<span :class="actif ? 'text-amber-500' : 'text-ink-300'">

{{-- Correct aussi : la couleur de repos est portée par le parent. --}}
<span class="text-ink-300"><svg :class="actif && 'text-amber-500'"></span>
```

## Ce qu'on ne fait pas

Pas de dégradé. Pas d'emoji affiché. Pas d'icône enfermée dans un carré coloré :
un titre de section se tient par sa typographie. Pas de `window.confirm()` : la
confirmation se fait en deux temps sur place, avec le composant `confirmAction`.
Et pas d'`opacity` pour griser un élément désactivé — le texte devient
illisible, on change la couleur.
