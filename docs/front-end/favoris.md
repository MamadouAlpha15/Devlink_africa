# Favoris — guide pour le front-end

> Backend prêt sur la branche `feature/favorites` (cahier, section 8).
> Les « Opportunités » ne sont pas dans le MVP (décision d'équipe).

## Page « Mes favoris »

| URL | Route | Vue | Accès |
|---|---|---|---|
| `/favorites` | `favorites.index` | `favorites/index.blade.php` | Connecté + email vérifié |

| Variable | Contenu |
|---|---|
| `$developers` | Développeurs sauvegardés (avec `country`, `skills`) |
| `$resources` | Ressources sauvegardées (avec `user`) |
| `$projects` | Projets sauvegardés (avec `owner`, `country`) |

Les éléments supprimés, privés ou suspendus disparaissent automatiquement de la page.

## Bouton Favori

Partial prêt à l'emploi : `resources/views/partials/favorite-button.blade.php`

```blade
@include('partials.favorite-button', ['type' => 'developer', 'model' => $user])
@include('partials.favorite-button', ['type' => 'resource', 'model' => $resource])
@include('partials.favorite-button', ['type' => 'project', 'model' => $project])
```

Il affiche « Enregistrer » ou « Enregistré » selon l'état, avec une icône de marque-page (pleine quand c'est enregistré), rien pour un visiteur, et rien sur son propre profil. Le marque-page plutôt que l'étoile : l'étoile sert déjà à noter les ressources.
Déjà placé sur les pages détail d'un développeur, d'une ressource et d'un projet. Tu peux le redessiner (icône cœur / étoile…).

Pour savoir dans une vue si un élément est en favori : `auth()->user()->hasFavorited($model)`.

| Action | Méthode + URL |
|---|---|
| Ajouter | `POST /favorites/{type}/{id}` (route `favorites.store`) |
| Retirer | `DELETE /favorites/{type}/{id}` (route `favorites.destroy`) |

`{type}` = `developer`, `resource` ou `project` ; `{id}` = l'id numérique de l'élément.

## Navigation

Lien **Mes favoris** ajouté dans le menu du compte (`layouts/navigation.blade.php`).
