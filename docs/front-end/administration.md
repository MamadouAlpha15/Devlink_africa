# Signalement & administration — guide pour le front-end

> Backend prêt (cahier, sections 21 et 22). Les vues de ce domaine sont **designées** : avant de
> les retoucher, lisez [design-system.md](design-system.md) et
> [composants.md](composants.md).
>
> Ce document décrit le **contrat avec le backend** : routes, variables et
> champs de formulaire. Ce sont ces noms-là qui ne doivent pas changer d'un
> seul côté.

## Signalement (section 22)

Bouton réutilisable : `resources/views/partials/report-button.blade.php` (petit formulaire qui s'ouvre au clic).

```blade
@include('partials.report-button', ['type' => 'developer', 'model' => $user, 'authorId' => $user->id])
@include('partials.report-button', ['type' => 'resource', 'model' => $resource, 'authorId' => $resource->user_id])
@include('partials.report-button', ['type' => 'project', 'model' => $project, 'authorId' => $project->owner_id])
@include('partials.report-button', ['type' => 'comment', 'model' => $comment, 'authorId' => $comment->user_id])
```

Déjà placé sur le profil public, le détail d'une ressource (et chacun de ses commentaires) et le détail d'un projet.
Rien n'est affiché pour un visiteur ni sur son propre contenu.

| Action | Méthode + URL | Champs |
|---|---|---|
| Signaler | `POST /reports/{type}/{id}` (route `reports.store`) | `reason` : `spam`, `inappropriate`, `scam`, `illegal`, `other` · `details` (facultatif, 1000 max) |

`{type}` = `developer`, `resource`, `project` ou `comment`. Un même membre ne peut pas signaler deux fois le même contenu tant que le premier signalement n'est pas traité.

## Administration (section 21)

Réservée aux administrateurs (`is_admin`). Lien **Administration** dans le menu du compte. Compte de démo : `admin@devlink.africa` / `password`.

| Page | URL | Route | Vue |
|---|---|---|---|
| Statistiques | `/admin` | `admin.dashboard` | `admin/dashboard.blade.php` |
| Utilisateurs | `/admin/users` | `admin.users.index` | `admin/users.blade.php` |
| Ressources | `/admin/resources` | `admin.resources.index` | `admin/resources.blade.php` |
| Projets | `/admin/projects` | `admin.projects.index` | `admin/projects.blade.php` |
| Signalements | `/admin/reports` | `admin.reports.index` | `admin/reports.blade.php` |

Sous-menu commun + messages de succès : `admin/partials/nav.blade.php`.

### Statistiques — `admin/dashboard.blade.php`

| Variable | Contenu |
|---|---|
| `$stats` | `users`, `users_this_month`, `suspended`, `projects`, `projects_recruiting`, `projects_completed`, `resources`, `resources_pending`, `countries` (pays représentés), `collaborations`, `reports_pending` |
| `$topCountries` | 10 pays avec le plus de membres (`flag`, `name`, `users_count`) |
| `$signupsPerMonth` | `["Mai 2026" => 12, …]` sur 6 mois → parfait pour un graphique en barres |

### Utilisateurs — voir, rechercher, suspendre, supprimer

- Filtres GET : `q` (nom, email, pseudo), `filter` = `all`, `suspended`, `admins`, `unverified`.
- `POST /admin/users/{username}/suspend` · `POST /admin/users/{username}/unsuspend` · `DELETE /admin/users/{username}`.
- Un compte **suspendu** ne peut plus se connecter (message « Votre compte a été suspendu par un administrateur. »), il est déconnecté à la page suivante s'il était connecté, et son profil disparaît du site.
- On ne peut ni suspendre ni supprimer un administrateur (ni soi-même).

### Ressources — valider, refuser, supprimer

- Filtre GET : `status` = `pending`, `approved`, `rejected` (les ressources en attente s'affichent en premier).
- `POST /admin/resources/{slug}/approve` · `POST /admin/resources/{slug}/reject` · `DELETE /admin/resources/{slug}`.
- `reports_count` = nombre de signalements reçus.

### Projets — voir, supprimer

- Filtre GET : `q` (titre). `DELETE /admin/projects/{slug}` (les demandes en attente sont annulées).

### Signalements — consulter, traiter

- Filtre GET : `status` = `pending` (défaut), `resolved`, `dismissed`.
- Chaque `$report` : `reason->label()`, `details`, `reporter`, `created_at`, `status`, `handler`, `handled_at`.
- Texte et lien du contenu signalé : `ReportController::describe($report)` et `ReportController::linkFor($report)` (classe `App\Http\Controllers\Admin\ReportController`) — « Contenu supprimé » si le contenu n'existe plus.
- Traiter : `POST /admin/reports/{id}` avec `action` =
  - `dismiss` — rejeter (rien à signaler) ;
  - `resolve` — marquer comme traité ;
  - `remove` — **supprimer le contenu** (ressource, projet, commentaire) ou **suspendre le compte** (profil) ; ferme aussi les autres signalements sur ce contenu.
