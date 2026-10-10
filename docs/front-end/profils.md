# Profils — guide pour le front-end

> Backend prêt sur la branche `feature/profiles`. Les vues actuelles sont **fonctionnelles mais sans design** :
> tu peux les réécrire entièrement, tant que tu gardes les noms des champs de formulaire et les routes.

## Pages

| Page | URL | Route | Vue | Accès |
|---|---|---|---|---|
| Profil public | `/devs/{username}` | `devs.show` | `resources/views/devs/show.blade.php` | Tout le monde (profil privé / suspendu → 404) |
| Modifier mon profil | `/profile/developer` | `developer-profile.edit` | `resources/views/developer-profile/edit.blade.php` | Connecté |
| Compte (email, mot de passe, suppression) | `/profile` | `profile.edit` | `resources/views/profile/edit.blade.php` (Breeze) | Connecté |

Après l'**inscription**, l'utilisateur est redirigé vers `/profile/developer` (cahier, section 1).

Lien vers un profil : `route('devs.show', $user)` → `/devs/alpha-keita`.

---

## Profil public — `devs/show.blade.php`

### Variables

| Variable | Contenu |
|---|---|
| `$user` | Le développeur affiché (voir les champs ci-dessous) |
| `$projects` | Ses 6 derniers projets (`$project->title`, `->status->label()`, `->country`, `->pivot->role->label()` = Propriétaire / Membre) |
| `$resources` | Ses 6 dernières ressources publiées (`->title`, `->type->label()`, `->rating_avg`, `->likes_count`) |
| `$ratingAvg` | Note moyenne de ses ressources (float ou `null`) |
| `$isOwner` | `true` si c'est mon propre profil → afficher « Modifier mon profil » |
| `$canFollow` | `true` si le bouton Suivre doit être affiché |
| `$isFollowing` | `true` si je le suis déjà → afficher « Ne plus suivre » |

### Champs de `$user`

| Champ | Exemple | Remarque |
|---|---|---|
| `name`, `first_name`, `last_name`, `username` | Alpha Keita, `alpha-keita` | |
| `avatar_url` | `/storage/avatars/xxx.jpg` ou `null` | Si `null`, afficher `initials` (« AK ») |
| `country->flag`, `country->name` | 🇬🇳 Guinée | `country` peut être `null` |
| `city`, `bio` | Conakry | peuvent être `null` |
| `profile_type->label()` | Développeur | |
| `experience_level?->label()` | Senior | peut être `null` |
| `availability->label()` | Disponible | **n'afficher que si `show_availability`** |
| `email` | | **n'afficher que si `show_email`** |
| `github_url`, `linkedin_url`, `portfolio_url`, `website_url` | | peuvent être `null` |
| `devscore` | 185 | |
| `projects_count`, `resources_count`, `collaborations_count` | 2, 1, 1 | |
| `followers_count`, `following_count` | 7, 7 | |
| `skills` | liste | `$skill->name`, `$skill->pivot->level->label()` (Débutant → Expert) |
| `badges` | liste | `$badge->icon`, `$badge->name`, `$badge->description` |

### Actions (formulaires)

| Action | Méthode + URL | Route |
|---|---|---|
| Suivre | `POST /devs/{username}/follow` | `devs.follow` |
| Ne plus suivre | `DELETE /devs/{username}/follow` | `devs.unfollow` |

Proposer une collaboration : voir `projets.md` (variable `$invitableProjects`).

À venir (prochaines branches) : Contacter, Signaler.

---

## Modifier mon profil — `developer-profile/edit.blade.php`

### Variables

| Variable | Contenu |
|---|---|
| `$user` | L'utilisateur connecté, avec `skills` |
| `$completion` | Pourcentage de complétion (0–100) → barre de progression |
| `$countries` | Les 54 pays (`id`, `name`, `flag`) triés par nom |
| `$skillsByCategory` | Compétences groupées par catégorie (« Backend » => [...], « Mobile » => [...]) → `<optgroup>` |
| `$profileTypes`, `$experienceLevels`, `$availabilities`, `$skillLevels` | Listes d'enums : `$item->value` (à envoyer) et `$item->label()` (à afficher) |

`$user->profile_completed_at === null` → le profil n'est pas encore complet (afficher l'incitation « +20 points DevScore »).

### Formulaire profil — `PUT /profile/developer` (`developer-profile.update`)

`enctype="multipart/form-data"` obligatoire (photo).

| Champ | Règle |
|---|---|
| `first_name`, `last_name` | obligatoires, 50 caractères max |
| `avatar` | fichier image jpg/png/webp, 2 Mo max (facultatif) |
| `remove_avatar` | `1` pour supprimer la photo |
| `country_id` | obligatoire (id d'un pays) |
| `city` | obligatoire |
| `bio` | 1000 caractères max |
| `profile_type` | obligatoire : `developer`, `designer`, `student`, `data`, `devops`, `product`, `other` |
| `experience_level` | `junior`, `intermediate`, `senior`, `expert` ou vide |
| `availability` | obligatoire : `available`, `open`, `busy` |
| `github_url`, `linkedin_url` | URL en `https://` |
| `portfolio_url`, `website_url` | URL |
| `is_public`, `show_email`, `show_availability`, `notify_by_email`, `notify_on_platform` | cases à cocher (`1` / `0`) — **case non envoyée = désactivée** |

Le profil est « complet » (et donne +20 DevScore, une seule fois) quand : prénom, nom, pays, ville, bio, rôle, disponibilité **et au moins une compétence** sont remplis.

### Compétences

| Action | Méthode + URL | Champs |
|---|---|---|
| Ajouter (ou changer le niveau si déjà présente) | `POST /profile/skills` | `skill_id`, `level` |
| Changer le niveau | `PATCH /profile/skills/{slug}` | `level` |
| Supprimer | `DELETE /profile/skills/{slug}` | — |

`level` : `beginner`, `intermediate`, `advanced`, `expert`. Maximum 15 compétences.

### Messages

Après chaque action, `session('status')` contient un message de succès en français à afficher (toast / alerte).
Les erreurs de validation sont dans `$errors` (composant Breeze `<x-input-error :messages="$errors->get('champ')" />`).

---

## Modification faite dans `layouts/navigation.blade.php`

Pour que les pages publiques fonctionnent sans être connecté :
- visiteur : liens **Connexion** / **Inscription** ;
- connecté : menu avec **Mon profil public**, **Modifier mon profil**, **Compte**, **Déconnexion**.

Tu peux redessiner la navigation librement en gardant ces liens.

## Photos de profil en local

Une seule fois après le clone : `php artisan storage:link`.
