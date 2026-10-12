# DevScore, classement & badges — guide pour le front-end

> Backend prêt (cahier, sections 15, 16 et 17). Les vues de ce domaine sont **designées** : avant de
> les retoucher, lisez [design-system.md](design-system.md) et
> [composants.md](composants.md).
>
> Ce document décrit le **contrat avec le backend** : routes, variables et
> champs de formulaire. Ce sont ces noms-là qui ne doivent pas changer d'un
> seul côté.

## Pages

| Page | URL | Route | Vue | Accès |
|---|---|---|---|---|
| Classement africain | `/ranking` | `ranking.index` | `ranking/index.blade.php` | Tout le monde |
| Mon DevScore | `/devscore` | `devscore.show` | `devscore/show.blade.php` | Connecté + email vérifié |

---

## Classement — `ranking/index.blade.php`

### Paramètres GET (filtres du cahier : Afrique • Pays • Mois • Année)

| Paramètre | Valeurs |
|---|---|
| `country` | vide = toute l'Afrique, sinon slug du pays (`guinee`) |
| `period` | `all` (défaut), `year`, `month` |
| `year` | `2026` (avec `period=year`) |
| `month` | `2026-10` (avec `period=month`) — compatible `<input type="month">` |

Exemple : `/ranking?country=senegal&period=month&month=2026-10`. Une valeur invalide ou future retombe sur la période en cours.

### Variables

| Variable | Contenu |
|---|---|
| `$developers` | Développeurs classés, paginés (25/page). Pour chacun : `points` (points **sur la période**, à caster en `int`), `name`, `country`, `badges`, `avatar_url`, `initials` |
| `$firstRank` | Rang du premier de la page (1, 26, 51…) → rang d'une ligne : `$firstRank + $loop->index` |
| `$filters` | `country`, `period`, `month`, `year` actuels (pour pré-remplir) |
| `$periodLabel` | « Depuis toujours », « 2026 » ou « Octobre 2026 » |
| `$periods` | `['all' => 'Depuis toujours', 'year' => 'Année', 'month' => 'Mois']` |
| `$countries` | Les 54 pays (`slug`, `name`, `flag`) |
| `$myRank` | Mon rang dans ce classement (connecté et classé), sinon `null` |

Seuls les profils publics, actifs et complétés sont classés.

---

## Mon DevScore — `devscore/show.blade.php`

| Variable | Contenu |
|---|---|
| `$user->devscore` | Total de points |
| `$ranks` | `['africa' => 3, 'country' => 1, 'month' => 4]` (chaque valeur peut être `null`) |
| `$breakdown` | Une ligne par action du barème : `action->label()`, `points_each` (+50…), `times` (nombre de fois), `total` |
| `$history` | 20 derniers gains : `points`, `action->label()`, `created_at`, `subject` (projet ou ressource → `->title`) |
| `$badges` | Les 6 badges : `badge` (`icon`, `name`, `description`) et `earned_at` (date ou `null` = à débloquer → afficher grisé) |

## Barème (cahier, section 15 — modifiable dans `config/devscore.php`)

| Action | Points |
|---|---|
| Profil complété | +20 |
| Projet créé | +50 |
| Ressource publiée | +30 |
| Collaboration | +40 |
| Ressource appréciée | +5 |
| Projet terminé | +100 |

## Badges automatiques (cahier, section 17)

Six badges. Le `slug` est stable : c'est **lui** que le front-end utilise pour
choisir l'icône, et non le champ `icon` de la base, qui contient un emoji et
n'est jamais affiché.

| Badge | `slug` | Icône affichée | Condition |
|---|---|---|---|
| Premier projet | `premier-projet` | `rocket` | A créé un projet |
| Collaborateur | `collaborateur` | `handshake` | A rejoint le projet d'un autre membre |
| Builder | `builder` | `hammer` | Un de ses projets est terminé |
| Expert Laravel | `expert-laravel` | `flame` | Niveau « Expert » en Laravel |
| Contributeur actif | `contributeur-actif` | `star` | Au moins 3 ressources publiées |
| Panafricain | `panafricain` | `globe` | Ses équipes réunissent des membres d'au moins 3 pays |

La correspondance vit dans `resources/views/components/badge-icon.blade.php`.
Un badge ajouté plus tard sans y être déclaré retombe sur l'icône `medal` :
jamais de case vide.

Un badge gagné apparaît sur le profil public, dans le classement, et dans le fil
d'activité (`activities`, type `badge_earned`).

Quand un badge est débloqué en changeant une compétence, `session('status')` le
signale — attention, **le message contient l'emoji du badge** (« Nouveau badge :
Expert Laravel ! » précédé de son emoji). Si vous l'affichez ailleurs que dans
le bandeau de message standard, retirez-le.
## Navigation

Lien **Classement** (tout le monde) et **Mon DevScore** (menu du compte) ajoutés dans `layouts/navigation.blade.php`.
