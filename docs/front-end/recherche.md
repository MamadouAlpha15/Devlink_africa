# Trouver des développeurs — guide pour le front-end

> Backend prêt. Les vues de ce domaine sont **designées** : avant de
> les retoucher, lisez [design-system.md](design-system.md) et
> [composants.md](composants.md).
>
> Ce document décrit le **contrat avec le backend** : routes, variables et
> champs de formulaire. Ce sont ces noms-là qui ne doivent pas changer d'un
> seul côté.

## Page

| URL | Route | Vue | Accès |
|---|---|---|---|
| `/devs` | `devs.index` | `resources/views/devs/index.blade.php` | Tout le monde |

Seuls les profils **publics, non suspendus et complétés** apparaissent.

## Paramètres de recherche (formulaire `GET`)

Tous facultatifs et combinables. Exemple du cahier : `/devs?skill=laravel&country=guinee&availability=available`

| Paramètre | Valeurs | Effet |
|---|---|---|
| `q` | texte libre | Cherche dans le nom, la ville, la bio, les compétences et le pays. Plusieurs mots = tous doivent correspondre (`laravel conakry`) |
| `country` | slug du pays (`guinee`, `senegal`, `cote-divoire`…) | Pays |
| `city` | texte | Ville (contient) |
| `skill` | slug de compétence (`laravel`, `flutter`, `ui-ux`, `node-js`…) | Compétence |
| `level` | `junior`, `intermediate`, `senior`, `expert` | Niveau du profil |
| `availability` | `available`, `open`, `busy` | Disponibilité (ignore les profils qui la masquent) |
| `type` | `developer`, `designer`, `student`, `data`, `devops`, `product`, `other` | Type de profil |
| `sort` | `devscore` (défaut), `recent`, `name` | Tri |

Une valeur invalide est simplement ignorée (pas d'erreur).

## Variables de la vue

| Variable | Contenu |
|---|---|
| `$developers` | Résultats paginés (12 par page). `$developers->total()` = nombre de résultats, `$developers->links()` = pagination (garde les filtres) |
| `$filters` | Valeurs actuelles des filtres (`$filters['q']`, `$filters['country']`…) pour pré-remplir le formulaire |
| `$hasFilters` | `true` si au moins un filtre est actif → afficher « Effacer les filtres » |
| `$countries` | Les 54 pays avec `developers_count` → menu déroulant « 🇬🇳 Guinée (6) » |
| `$topCountries` | Les 10 pays qui ont le plus de développeurs (`flag`, `name`, `slug`, `developers_count`) → bloc « Développeurs par pays » (cahier, section 5) |
| `$skillsByCategory` | Compétences groupées par catégorie → `<optgroup>` |
| `$experienceLevels`, `$availabilities`, `$profileTypes` | Listes d'enums : `->value` (à envoyer), `->label()` (à afficher) |
| `$sorts` | `['devscore' => 'Meilleur DevScore', …]` |

## Carte d'un développeur (`$developer` dans la boucle)

Mêmes champs que sur le profil public (voir `profils.md`), déjà chargés : `name`, `avatar_url` / `initials`, `profile_type->label()`, `country->flag`, `city`, `skills`, `devscore`.
**Disponibilité** : n'afficher `availability->label()` que si `show_availability` est vrai.

Lien vers le profil : `route('devs.show', $developer)`.

## Navigation

Lien **Développeurs** ajouté dans `layouts/navigation.blade.php` (desktop + mobile).
