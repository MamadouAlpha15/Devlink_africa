# Le design system

Tout le vocabulaire visuel de DevLink Africa tient dans deux fichiers :
`tailwind.config.js` pour les jetons (couleurs, police, ombres) et
`resources/css/app.css` pour les 69 classes de composants.

Avant d'écrire une suite d'utilitaires Tailwind dans une vue, cherchez ici : il
y a de fortes chances que la classe existe déjà. C'est ce qui garantit qu'une
page écrite dans six mois ressemblera encore aux autres.

## Les couleurs

Deux gammes seulement.

**`brand`** — l'indigo. Réservé à l'action : bouton principal, lien, état
sélectionné, fonctionnalité IA. **Un élément indigo qui ne se clique pas est une
erreur.** Les deux valeurs utiles sont `brand-600` (`#4F46E5`) en thème clair et
`brand-400` / `brand-500` en thème sombre.

**`ink`** — le gris ardoise. Il porte toute l'interface : texte, bordures,
fonds, états au survol. De `ink-50` (`#F8FAFC`, le fond des pages) à `ink-950`
(`#0B1120`, le fond en thème sombre).

Les couleurs Tailwind par défaut ne servent qu'à **signaler un état**, jamais à
décorer : `emerald` pour un succès, `amber` pour un avertissement, `red` pour
un danger, et trois teintes de médaille sur le podium du classement.

## La typographie

Inter, chargée depuis fonts.bunny.net, avec les variantes de caractères
`cv05`, `cv11` et `calt` activées. Le corps de texte est à **15 px**, pas 16 :
c'est le réglage des produits denses, plus net qu'un 16 px de site vitrine.

| Classe | Taille | Usage |
|---|---|---|
| `.page-title` | 28 px, 32 px dès `sm` | Le titre d'une page. Un seul par page. |
| `.section-title` | 20 px | Le titre d'une section dans une carte. |
| `.card-title` | 15 px, demi-gras | Le titre d'une carte dans une liste. |
| `.lead` | 17 px | Le paragraphe d'accroche, sous un titre de page. |
| `.body-text` | 15 px | Le corps de texte long. |
| `.muted` | 14 px | Une information secondaire. |
| `.caption` | 12 px, demi-gras | Une étiquette au-dessus d'une valeur. |
| `.subtle`, `.field-hint` | 12 px | Une aide sous un champ. |
| `.eyebrow` | 12 px | Un sur-titre de section. Neutre. |

`.tabular` force les chiffres à largeur fixe. **À mettre sur tout nombre qui
s'aligne verticalement** : scores, compteurs, colonnes de tableau, rangs. Sans
elle, une colonne de chiffres tremble d'une ligne à l'autre.

## Les surfaces

Trois niveaux, pas plus. La profondeur vient des bordures, pas des ombres : il
n'existe que **deux ombres** dans tout le produit.

| Classe | Ce que c'est |
|---|---|
| `.card` | Une bordure, un fond, un rayon de 12 px. Aucune ombre. Le cas courant. |
| `.card-raised` | `.card` plus l'ombre `card`, très légère. Pour poser une surface sur le fond. |
| `.card-interactive` | `.card` dont seules les **couleurs** changent au survol. Pas de déplacement, pas d'agrandissement. |
| `.panel` | `.card` avec le rembourrage standard (`p-5`, `p-6` dès `sm`). C'est ce qu'on utilise le plus. |
| `.surface-muted` | Un aplat gris, sans bordure, pour détacher un bloc à l'intérieur d'une carte. |

L'ombre `overlay` est réservée à ce qui flotte réellement au-dessus du plan :
menus déroulants, modales, panneaux surgissants.

**Les rayons suivent la taille de la boîte** : 6 px pour une pastille, 8 px pour
un bouton ou une ligne de liste, 12 px pour une carte.

## Les boutons

Tous les boutons mesurent **40 px de haut** (`h-10`) et ont un rayon de 8 px,
quelle que soit leur taille. Aucun ne porte d'ombre portée : `.btn-primary`
reçoit une arête supérieure éclairée (`inset 0 1px 0`), ce qui le pose sans le
décoller.

| Classe | Quand |
|---|---|
| `.btn-primary` | L'action principale de la page. **Une seule à la fois.** |
| `.btn-secondary` | Les actions courantes. Bordure, fond blanc. |
| `.btn-ghost` | Les actions discrètes. Ni bordure ni fond au repos. |
| `.btn-danger` | Une action destructrice confirmée. |
| `.btn-danger-ghost` | Une action destructrice avant confirmation. |

Modificateurs : `.btn-xs`, `.btn-sm`, `.btn-lg` pour la taille, `.btn-square`
pour un bouton à icône seule, `.btn-block` pour toute la largeur.

Un bouton à icône seule **doit** porter un `aria-label`.

## Les formulaires

| Classe | Pour |
|---|---|
| `.label` | Le libellé d'un champ. `.label-hint` pour le « facultatif » qui le suit. |
| `.input`, `.select`, `.textarea` | Les champs. Hauteur 40 px, rayon 8 px. |
| `.input-group` | Un champ précédé d'une icône (recherche, lien…). |
| `.checkbox`, `.checkbox-row` | Une case à cocher et sa ligne cliquable. |
| `.file-field` | Un sélecteur de fichier. |
| `.field-hint` | L'aide sous le champ. |

Le focus est marqué par un anneau de 3 px à 12 % d'opacité, jamais par un
contour épais. **Ne retirez jamais l'anneau de focus** : il est la seule
indication pour qui navigue au clavier.

Les erreurs passent par `<x-input-error :messages="$errors->get('champ')" />`.

## Pastilles, puces et messages

| Classe | Pour |
|---|---|
| `.badge-neutral`, `.badge-success`, `.badge-warning`, `.badge-danger` | Un **état**, en un mot. Jamais en capitales. |
| `.chip` | Une étiquette non cliquable : une compétence, un type. |
| `.chip-strong` | La même, mise en avant par le **poids**, pas par la couleur. |
| `.chip-button` | Un filtre cliquable. |
| `.chip-button-active` | Son état actif. **C'est un modificateur** : il s'ajoute à `.chip-button`, il ne le remplace pas. |
| `.alert-info`, `.alert-success`, `.alert-warning`, `.alert-danger` | Un message au-dessus du contenu. |

Le piège le plus fréquent : écrire `class="{{ $actif ? 'chip-button-active' : 'chip-button' }}"`.
Le modificateur seul n'a ni rembourrage ni rayon, et les filtres se chevauchent.
La bonne forme est `class="chip-button {{ $actif ? 'chip-button-active' : '' }}"`.

## Le thème sombre

Piloté par la classe `dark` sur `<html>`, appliquée par un script en ligne dans
`layouts/partials/theme-script.blade.php` **avant le premier rendu** : sans
cela, une page en thème sombre clignoterait en blanc au chargement. Le choix est
conservé dans `localStorage` sous `devlink.theme` ; sans choix explicite, on
suit la préférence du système.

Chaque classe de `app.css` porte déjà ses variantes `dark:`. Si vous écrivez des
utilitaires à la main dans une vue, **pensez à la variante sombre** : c'est
l'oubli le plus courant, et il ne se voit qu'en basculant le thème.

## Le mouvement

Six règles, tenues partout :

1. On n'anime que `opacity` et `transform` — les deux seules propriétés qui
   passent par le compositeur, donc sans recalcul de mise en page.
2. Rien ne boucle, rien ne rebondit, rien ne s'agrandit au survol.
3. Une animation accompagne l'arrivée d'un élément ou transporte une
   information. Sinon elle n'existe pas.
4. Les durées restent courtes : 150 ms pour un changement d'état, 500 ms pour
   une apparition.
5. Les cascades sont plafonnées — au-delà de six crans, le dernier élément
   arrive trop tard.
6. `prefers-reduced-motion` désactive tout, **sans rien masquer**.

L'apparition au défilement repose sur la classe `.reveal`, activée par
`initScrollReveal()` dans `app.js`. Elle est conditionnée à la classe `js` sur
`<html>` : sans JavaScript, rien n'est masqué. Un chien de garde de 2,5 s retire
cette classe si le bundle n'arrive jamais — autrement la page resterait vide.

## Le piège des couleurs conditionnelles

Toutes les règles `text-ink-*` sont compilées **après** les `text-amber-*` dans
la feuille finale. Sur un même élément, `ink` gagne donc toujours, quel que soit
l'ordre dans l'attribut `class`.

```blade
{{-- Ne marche pas : l'ambre ne s'applique jamais. --}}
<span class="text-ink-300" :class="actif && 'text-amber-500'">

{{-- Correct : les deux états passent par :class. --}}
<span :class="actif ? 'text-amber-500' : 'text-ink-300'">

{{-- Correct aussi : la couleur de repos est portée par le parent. --}}
<span class="text-ink-300"><svg :class="actif && 'text-amber-500'"></span>
```

## Ce qu'on ne fait pas

- Pas de dégradé, nulle part.
- Pas d'emoji affiché. Voir [README.md](README.md).
- Pas d'icône enfermée dans un carré coloré : un titre de section se tient par
  sa typographie.
- Pas de `window.confirm()` : la confirmation se fait en deux temps sur place,
  avec le composant Alpine `confirmAction`.
- Pas de `opacity` pour griser un élément désactivé : le texte devient
  illisible. On change la couleur.
