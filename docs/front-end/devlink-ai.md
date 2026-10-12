# DevLink AI — guide pour le front-end

> Backend prêt (cahier, section 18). Les vues de ce domaine sont **designées** : avant de
> les retoucher, lisez [design-system.md](design-system.md) et
> [composants.md](composants.md).
>
> Ce document décrit le **contrat avec le backend** : routes, variables et
> champs de formulaire. Ce sont ces noms-là qui ne doivent pas changer d'un
> seul côté.

## Parcours

1. Le membre (connecté) écrit ce qu'il cherche : « Je cherche un développeur Flutter en Guinée pour une application de livraison. »
2. L'IA comprend : compétences, pays, type de projet.
3. Elle recommande jusqu'à 10 profils avec un **pourcentage de correspondance**, et un lien **Voir le profil**.

## Pages

| Page | URL | Route | Vue | Accès |
|---|---|---|---|---|
| Poser la question | `/ai` | `ai.create` | `ai/create.blade.php` | Connecté + email vérifié |
| Résultats | `/ai/{id}` | `ai.show` | `ai/show.blade.php` | L'auteur de la recherche uniquement |

Formulaire : `POST /ai` (route `ai.store`), champ `query` (10 à 500 caractères). Limité à 10 recherches par minute.
La réponse redirige vers la page de résultats. L'appel à l'IA peut prendre quelques secondes : prévoir un loader au submit.

## Variables — `ai/create.blade.php`

| Variable | Contenu |
|---|---|
| `$examples` | 4 phrases d'exemple (boutons qui remplissent le champ) |
| `$recentSearches` | Mes 5 dernières recherches (`query`, `results`, `created_at`) → lien `route('ai.show', $search)` |

## Variables — `ai/show.blade.php`

| Variable | Contenu |
|---|---|
| `$search->query` | La phrase tapée |
| `$criteria['skills']` | Slugs des compétences comprises (`['flutter']`) |
| `$criteria['skill_categories']` | Domaines si aucune compétence précise (`['mobile']`) → `SkillCategory::from($c)->label()` |
| `$criteria['country']` | Slug du pays ou `null` |
| `$criteria['project_type']` | Catégorie de projet ou `null` → `ProjectCategory::from(...)->label()` |
| `$criteria['summary']` | Reformulation en français (seulement avec Claude, sinon `null`) |
| `$criteria['engine']` | `claude` ou `local` (analyseur de secours) |
| `$results` | Liste triée, chaque élément : `score` (0–100), `matched_skills` (noms des compétences qui correspondent), `user` (le développeur, avec `country` et `skills`) |

Lien vers le profil : `route('devs.show', $result['user'])`.

## Comment le pourcentage est calculé (pour l'expliquer au jury)

| Critère | Points |
|---|---|
| Compétences demandées (selon le niveau ; une compétence voisine du même domaine compte à 70 %) | 60 |
| Pays | 20 |
| A déjà travaillé sur un projet du même type | 10 |
| Disponibilité | 10 |

Un critère non demandé donne ses points à tout le monde. Seuls les profils publics, actifs et complétés sont recommandés.

## Navigation

Lien **DevLink AI** (connecté) ajouté dans `layouts/navigation.blade.php`.
