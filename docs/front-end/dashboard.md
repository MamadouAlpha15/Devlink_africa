# Dashboard & fil d'activité — guide pour le front-end

> Backend prêt sur la branche `feature/dashboard` (cahier, sections 20 et 19).
> **C'est la première page que le jury verra après la connexion** : à soigner en priorité
> (cartes de chiffres, grille responsive, fil d'activité dans une colonne).

| URL | Route | Vue | Accès |
|---|---|---|---|
| `/dashboard` (+ `?feed=all`) | `dashboard` | `dashboard.blade.php` | Connecté + email vérifié |

## Variables

| Variable | Contenu |
|---|---|
| `$user` | Le membre connecté (avec `country`, `skills`, `badges`) → « Bonjour {{ first_name }} » |
| `$completion` | Complétion du profil en % (afficher l'incitation si < 100) |
| `$africaRank` | Rang au classement africain, ou `null` |
| `$stats` | `['projects' => 2, 'resources' => 1, 'collaborations' => 1, 'followers' => 7]` |
| `$pending` | `['invitations' => 0, 'applications' => 1]` → bandeau vers `route('collaborations.index')` |
| `$recommendedDevelopers` | 4 max. Chaque élément : `user` (avec `country`, `skills`), `score` (% de correspondance ou `null`), `reason` (texte : « Pour vos postes : Flutter » ou « Développeur actif en Guinée ») |
| `$interestingProjects` | 4 projets max (avec `owner`, `country`, `roles`). Postes encore ouverts : `$project->roles->reject->isFull()` |
| `$feed` | Fil d'activité (15 événements max), voir ci-dessous |
| `$feedScope` / `$feedScopes` | Onglet actif (`network` ou `all`) / `['network' => 'Mon réseau', 'all' => 'Toute la communauté']` |
| `$activityFeed` | Service pour afficher un événement : `linkFor($activity)` et `subjectTitle($activity)` |

## Comment sont choisies les recommandations

- **Je gère des projets avec des postes ouverts** → les meilleurs profils pour ces postes (même moteur que DevLink AI, `score` = % de correspondance).
- **Sinon** → les développeurs actifs de mon pays que je ne suis pas encore (`score` = `null`).

Projets intéressants : d'abord ceux qui recrutent sur **mes compétences**, complétés par les derniers projets ouverts.

## Fil d'activité (cahier, section 19)

Chaque `$activity` :

| Champ | Exemple |
|---|---|
| `user` | Ibrahim Sow (lien `route('devs.show', $activity->user)`) |
| `type->label()` | « a publié une ressource », « a créé un projet », « a rejoint un projet », « a obtenu un badge » |
| `$activityFeed->subjectTitle($activity)` | « Orange Money pour Laravel », « AfricaDelivery ». **Attention** : pour un badge, cette méthode préfixe l'emoji stocké en base au nom. Les vues détectent donc le cas `Badge` et affichent l'icône avec `<x-badge-icon>` plutôt que d'appeler cette méthode. |
| `$activityFeed->linkFor($activity)` | Lien vers la ressource / le projet, ou `null` pour un badge |
| `created_at->diffForHumans()` | « il y a 2 heures » |

- **Mon réseau** (`?feed=network`, défaut) : moi + les développeurs que je suis.
- **Toute la communauté** (`?feed=all`).

Les événements de profils privés ou suspendus, et ceux dont la ressource / le projet a été supprimé, sont masqués.
« Pas besoin de transformer cela en Facebook » : pas de likes ni de commentaires sur les événements.
