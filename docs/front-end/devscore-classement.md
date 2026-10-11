# DevScore, classement & badges — guide pour le front-end

> Backend prêt sur la branche `feature/devscore` (cahier, sections 15, 16 et 17).
> Les vues actuelles sont **fonctionnelles mais sans design** : podium, médailles, barres de progression… à toi de jouer.

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

| Badge | Condition |
|---|---|
| 🚀 Premier projet | A créé un projet |
| 🤝 Collaborateur | A rejoint le projet d'un autre membre |
| 🏗️ Builder | Un de ses projets est terminé |
| 🔥 Expert Laravel | Niveau « Expert » en Laravel |
| ⭐ Contributeur actif | Au moins 3 ressources publiées |
| 🌍 Panafricain | Ses équipes réunissent des membres d'au moins 3 pays |

Un badge gagné apparaît sur le profil public, dans le classement, et dans le fil d'activité (`activities`, type `badge_earned`).
Quand un badge est débloqué en changeant une compétence, le message `session('status')` le signale (« Nouveau badge : 🔥 Expert Laravel ! »).

## Navigation

Lien **Classement** (tout le monde) et **Mon DevScore** (menu du compte) ajoutés dans `layouts/navigation.blade.php`.
