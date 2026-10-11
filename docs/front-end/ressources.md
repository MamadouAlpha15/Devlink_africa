# Dev Resources — guide pour le front-end

> Backend prêt sur la branche `feature/resources` (cahier, sections 6 et 7).
> Les vues actuelles sont **fonctionnelles mais sans design** : tu peux les réécrire entièrement,
> tant que tu gardes les noms des champs, des paramètres et des routes.

## Pages

| Page | URL | Route | Vue | Accès |
|---|---|---|---|---|
| Liste | `/resources` | `resources.index` | `resources/index.blade.php` | Tout le monde |
| Détail | `/resources/{slug}` | `resources.show` | `resources/show.blade.php` | Tout le monde (en attente / refusée : auteur et admins seulement) |
| Publier | `/resources/create` | `resources.create` | `resources/create.blade.php` | Connecté + email vérifié |
| Modifier | `/resources/{slug}/edit` | `resources.edit` | `resources/edit.blade.php` | Auteur |
| Télécharger | `/resources/{slug}/download` | `resources.download` | — (fichier) | Tout le monde, compte les téléchargements |

Champs du formulaire partagés : `resources/partials/form-fields.blade.php`.

---

## Liste — `resources/index.blade.php`

Paramètres GET (facultatifs, combinables) :

| Paramètre | Valeurs |
|---|---|
| `q` | texte (titre, description, technologies, tags) |
| `type` | `api`, `template`, `framework`, `package`, `ui_kit`, `documentation`, `script`, `library`, `open_source` |
| `skill` | slug de technologie (`laravel`…) |
| `country` | slug du pays |
| `tag` | slug du tag (`mobile-money`) |
| `sort` | `recent` (défaut), `popular` (likes), `rating`, `downloads` |

Variables : `$resources` (paginé, 12/page), `$filters`, `$hasFilters`, `$sorts`, `$types`, `$countries`, `$skillsByCategory`, `$popularTags` (15 tags les plus utilisés, avec `resources_count`).

Pour chaque `$resource` : `title`, `description`, `type->label()`, `user`, `country`, `skills`, `tags`, `imageUrl()` (ou `null`), `likes_count`, `rating_avg` (ou `null`), `downloads_count`.

---

## Détail — `resources/show.blade.php`

### Variables

| Variable | Contenu |
|---|---|
| `$resource` | Avec `user`, `country`, `skills`, `tags`. `hasFile()` → afficher le bouton Télécharger. `github_url`, `demo_url` peuvent être `null` |
| `$reviews` | Avis : `rating` (1–5), `comment`, `user`, `created_at` |
| `$myReview` | Mon avis (pour pré-remplir), ou `null` |
| `$comments` | Commentaires : `body`, `user`, `created_at` |
| `$hasLiked` | J'aime déjà → bouton « Je n'aime plus » |
| `$canInteract` | Je peux liker et noter (connecté, pas l'auteur, ressource publiée) |
| `$canComment` | Je peux commenter |
| `$canUpdate` | Je suis l'auteur (ou admin) → lien « Modifier » |

Suppression d'un commentaire : `@can('delete', $comment)` (auteur du commentaire, auteur de la ressource ou admin).

### Formulaires

| Action | Méthode + URL | Champs |
|---|---|---|
| J'aime | `POST /resources/{slug}/like` | — |
| Je n'aime plus | `DELETE /resources/{slug}/like` | — |
| Noter (ou modifier ma note) | `POST /resources/{slug}/reviews` | `rating` (1 à 5), `comment` (facultatif, 1000 max) |
| Supprimer ma note | `DELETE /resources/{slug}/reviews` | — |
| Commenter | `POST /resources/{slug}/comments` | `body` (2000 max) |
| Supprimer un commentaire | `DELETE /comments/{id}` | — |

À venir (prochaines branches) : **Favori** et **Signaler**.

---

## Publier / modifier

`enctype="multipart/form-data"` obligatoire.

| Champ | Règle |
|---|---|
| `title` | obligatoire, 120 max |
| `description` | obligatoire, 5000 max |
| `type` | obligatoire (c'est la « catégorie » du cahier) |
| `country_id` | facultatif (pré-rempli avec le pays de l'auteur) |
| `skills[]` | ids de technologies, 10 max |
| `tags` | **un seul champ texte**, tags séparés par des virgules (« paiement, mobile money »), 8 max, 30 caractères max chacun |
| `image` | jpg/png/webp, 2 Mo max · `remove_image=1` pour la supprimer (modification) |
| `file` | zip, rar, 7z, tar, gz, pdf, md, txt, json — 10 Mo max · `remove_file=1` pour le supprimer |
| `github_url` | URL en `https://` |
| `demo_url` | URL |

Supprimer : `DELETE /resources/{slug}` (route `resources.destroy`).

## DevScore

- Ressource publiée : **+30** pour l'auteur
- Chaque like : **+5** pour l'auteur ; retirer son like reprend les 5 points (impossible de « farmer » des points)
- 3 ressources publiées : badge ⭐ Contributeur actif

## Navigation

Lien **Ressources** ajouté dans `layouts/navigation.blade.php`. Sur le profil public, les titres des projets et ressources sont maintenant des liens.
