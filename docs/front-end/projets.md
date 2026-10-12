# Projets & collaboration — guide pour le front-end

> Backend prêt (cahier, sections 9 à 12). Les vues de ce domaine sont **designées** : avant de
> les retoucher, lisez [design-system.md](design-system.md) et
> [composants.md](composants.md).
>
> Ce document décrit le **contrat avec le backend** : routes, variables et
> champs de formulaire. Ce sont ces noms-là qui ne doivent pas changer d'un
> seul côté.

## Pages

| Page | URL | Route | Vue | Accès |
|---|---|---|---|---|
| Liste des projets | `/projects` | `projects.index` | `projects/index.blade.php` | Tout le monde |
| Détail d'un projet | `/projects/{slug}` | `projects.show` | `projects/show.blade.php` | Tout le monde |
| Créer un projet | `/projects/create` | `projects.create` | `projects/create.blade.php` | Connecté + email vérifié |
| Modifier un projet | `/projects/{slug}/edit` | `projects.edit` | `projects/edit.blade.php` | Propriétaire |
| Mes collaborations | `/collaborations` | `collaborations.index` | `collaborations/index.blade.php` | Connecté |

Champs du formulaire projet partagés : `projects/partials/form-fields.blade.php`.

## Rôles dans un projet

| Rôle | `ProjectMemberRole` | Peut |
|---|---|---|
| Propriétaire | `owner` | Tout : modifier / supprimer le projet, promouvoir, retirer n'importe quel membre |
| Co-responsable | `co_owner` | Gérer les postes, demandes, invitations ; retirer les simples membres |
| Membre | `member` | Quitter le projet |

---

## Liste — `projects/index.blade.php`

Paramètres GET (facultatifs, combinables) :

| Paramètre | Valeurs |
|---|---|
| `q` | texte (titre, description, technologies, postes) |
| `category` | `fintech`, `healthtech`, `edtech`, `agritech`, `ecommerce`, `logistics`, `social`, `govtech`, `other` |
| `country` | slug du pays (`guinee`…) |
| `status` | `idea`, `recruiting`, `in_progress`, `completed`, `archived` (les archivés sont masqués par défaut) |
| `skill` | slug de compétence : technologies du projet **ou** postes recherchés |
| `sort` | `recent` (défaut), `team`, `title` |

Variables : `$projects` (paginé, 12/page), `$filters`, `$hasFilters`, `$sorts`, `$categories`, `$statuses`, `$countries`, `$skillsByCategory`.

Pour chaque `$project` (déjà chargés) : `title`, `description`, `category->label()`, `status->label()`, `country`, `owner`, `skills`, `roles`, `memberships_count`.
Postes encore ouverts : `$project->roles->reject->isFull()` ; places libres d'un poste : `$role->openSlots()`.

---

## Détail — `projects/show.blade.php`

### Variables

| Variable | Contenu |
|---|---|
| `$project` | Avec `owner`, `country`, `skills`, `roles` (+ `skill`, `members_count`), `memberships` (+ `user`, `projectRole`) |
| `$project->coverUrl()` | URL de l'image ou `null` |
| `$viewerMembership` | Mon adhésion si je suis membre (`->role`), sinon `null` |
| `$viewerPendingRequest` | Ma demande / invitation en attente pour ce projet, sinon `null` (`->direction` = `application` ou `invitation`) |
| `$canUpdate` | Je suis propriétaire → bouton « Modifier », promouvoir |
| `$canManage` | Propriétaire ou co-responsable → gérer postes, demandes, membres |
| `$canApply` | Je peux demander à rejoindre → formulaire « Rejoindre » |
| `$pendingApplications` | (managers) demandes reçues : `user`, `projectRole`, `message` |
| `$pendingInvitations` | (managers) invitations envoyées en attente |
| `$suggestions` | (managers) « Chercher des collaborateurs » : liste de `['role' => $role, 'developers' => [...]]`, 3 devs max par poste ouvert |
| `$skills` | (managers) toutes les compétences, pour le formulaire « Ajouter un poste » |

### Formulaires

| Action | Méthode + URL | Champs |
|---|---|---|
| Demander à rejoindre | `POST /projects/{slug}/requests` | `project_role_id` (facultatif), `message` |
| Inviter un développeur | `POST /projects/{slug}/invitations` | `user_id`, `project_role_id` (facultatif), `message` |
| Accepter une demande / invitation | `POST /collaborations/{id}/accept` | — |
| Refuser | `POST /collaborations/{id}/reject` | — |
| Annuler (ma demande, ou une invitation envoyée) | `DELETE /collaborations/{id}` | — |
| Ajouter un poste | `POST /projects/{slug}/roles` | `title`, `skill_id`, `slots` |
| Modifier un poste | `PUT /projects/{slug}/roles/{id}` | `title`, `skill_id`, `slots` |
| Supprimer un poste | `DELETE /projects/{slug}/roles/{id}` | — |
| Promouvoir / rétrograder | `PATCH /projects/{slug}/members/{username}` | `role` = `co_owner` ou `member` |
| Retirer un membre | `DELETE /projects/{slug}/members/{username}` | — |
| Quitter le projet | `POST /projects/{slug}/leave` | — |

Les erreurs métier (« Le poste est déjà pourvu », « Vous avez déjà une demande en attente »…) arrivent dans `$errors` sous les clés `collaboration`, `project_role_id` ou `member`. Les succès dans `session('status')`.

---

## Créer / modifier — `projects/create.blade.php`, `projects/edit.blade.php`

`enctype="multipart/form-data"` (image).

| Champ | Règle |
|---|---|
| `title` | obligatoire, 100 max |
| `description` | obligatoire, 5000 max |
| `category` | obligatoire (voir liste ci-dessus) |
| `country_id` | facultatif |
| `status` | création : `idea`, `recruiting`, `in_progress` · modification : tous |
| `members_wanted` | 1 à 50 |
| `skills[]` | ids de compétences, 10 max |
| `cover` | image jpg/png/webp, 2 Mo max · `remove_cover=1` pour la supprimer (modification) |
| `roles[i][title]`, `roles[i][skill_id]`, `roles[i][slots]` | **création uniquement**, 10 postes max, les lignes sans titre sont ignorées |

Supprimer : `DELETE /projects/{slug}` (route `projects.destroy`).

DevScore : projet créé +50, collaboration acceptée +40 (pour le nouveau membre), projet passé en « Terminé » +100 (une seule fois).

---

## Mes collaborations — `collaborations/index.blade.php`

| Variable | Contenu | Actions |
|---|---|---|
| `$receivedInvitations` | Projets qui m'invitent | Accepter / Refuser |
| `$receivedApplications` | Demandes reçues sur les projets que je gère | Accepter / Refuser |
| `$sentApplications` | Mes demandes en attente | Annuler |
| `$history` | 20 dernières demandes traitées (`direction->label()`, `status->label()`, `responded_at`) | — |

---

## Profil public — « Proposer une collaboration »

Sur `devs/show.blade.php`, nouvelle variable `$invitableProjects` : les projets que je gère, ouverts, dont le développeur n'est pas membre.
Formulaire → `POST /projects/{slug}/invitations` avec `user_id` = le développeur affiché.

## Navigation

Liens **Projets** (tout le monde) et **Mes collaborations** (connecté) ajoutés dans `layouts/navigation.blade.php`.
