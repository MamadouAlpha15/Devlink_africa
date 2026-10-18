# Les pages

Ce document décrit le contrat entre chaque page et le backend : son adresse, sa
route, sa vue, et ce que le contrôleur lui passe. **Ce sont ces noms-là qui ne
doivent pas changer d'un seul côté.** Une route renommée casse la page en
silence, sans erreur PHP.

Quatre conventions valent partout, elles ne sont pas répétées ensuite :

- les messages de succès arrivent dans `session('status')` et sont affichés par
  `<x-flash>` ;
- les erreurs de validation sont dans `$errors`, affichées par
  `<x-input-error :messages="$errors->get('champ')" />` ;
- une liste paginée expose `->total()` pour le nombre de résultats et
  `->links()` pour la pagination, qui conserve les filtres ;
- une énumération expose `->value`, à envoyer au serveur, et `->label()`, à
  afficher.

---

## Trouver des développeurs

`/devs` · route `devs.index` · vue `devs/index.blade.php` · ouverte à tous.

Seuls les profils **publics, non suspendus et complétés** y apparaissent.

Le formulaire de recherche est en `GET`. Tous ses paramètres sont facultatifs et
se combinent : `q` cherche dans le nom, la ville, la bio, les compétences et le
pays — plusieurs mots doivent tous correspondre ; `country` et `skill`
attendent un slug (`guinee`, `laravel`) ; `city` un texte ; `level` vaut
`junior`, `intermediate`, `senior` ou `expert` ; `availability` vaut
`available`, `open` ou `busy` ; `type` vaut `developer`, `designer`, `student`,
`data`, `devops`, `product` ou `other` ; `sort` vaut `devscore` par défaut,
`recent` ou `name`. Une valeur invalide est ignorée sans erreur.

La vue reçoit `$developers` paginé par 12, `$filters` pour pré-remplir le
formulaire, `$hasFilters` pour savoir s'il faut proposer d'effacer,
`$countries` avec leur `developers_count`, `$topCountries` pour le bloc
« développeurs par pays », `$skillsByCategory` pour les `<optgroup>`,
`$experienceLevels`, `$availabilities`, `$profileTypes` et `$sorts`.

Chaque développeur de la boucle porte déjà `name`, `avatar_url` ou `initials`,
`profile_type`, `country`, `city`, `skills` et `devscore`. **N'affichez sa
disponibilité que si `show_availability` est vrai.**

---

## Profils

Le profil public est en `/devs/{username}`, route `devs.show`, vue
`devs/show.blade.php`, ouverte à tous — un profil privé ou suspendu renvoie
404. On modifie le sien en `/profile/developer`, route
`developer-profile.edit` ; le compte, l'email et le mot de passe restent sur
`/profile`, route `profile.edit`. Après l'inscription, le membre est redirigé
vers `/profile/developer`.

La vue publique reçoit `$user`, ses six derniers `$projects` — avec
`$project->pivot->role` qui dit Propriétaire ou Membre — ses six dernières
`$resources`, la note moyenne `$ratingAvg`, puis `$isOwner`, `$canFollow` et
`$isFollowing` qui décident des boutons. `$invitableProjects` liste les projets
que je gère et où ce développeur n'est pas encore membre.

Sur `$user` : `name`, `first_name`, `last_name`, `username`, `avatar_url` ou
`initials`, `country`, `city`, `bio`, `profile_type`, `experience_level`,
`availability`, `email`, `devscore`, les compteurs `projects_count`,
`resources_count`, `collaborations_count`, `followers_count`,
`following_count`, puis `skills` — chacune avec `pivot->level` — et `badges`.
Les quatre liens `github_url`, `linkedin_url`, `portfolio_url` et
`website_url` peuvent être nuls.

**Deux champs conditionnent l'affichage** : `show_email` pour l'adresse,
`show_availability` pour la disponibilité. Les ignorer expose une donnée que le
membre a choisi de cacher.

Suivre et ne plus suivre passent par `POST` et `DELETE` sur
`/devs/{username}/follow`, routes `devs.follow` et `devs.unfollow`.

### Modifier son profil

Le formulaire part en `PUT /profile/developer`, route
`developer-profile.update`, et exige `enctype="multipart/form-data"` à cause de
la photo.

Il envoie `first_name` et `last_name`, obligatoires, 50 caractères ; `avatar`,
une image jpg, png ou webp de 2 Mo au plus, avec `remove_avatar=1` pour
l'effacer ; `country_id` et `city`, obligatoires ; `bio`, 1000 caractères ;
`profile_type` et `availability`, obligatoires ; `experience_level`, facultatif ;
les quatre URL ; et cinq cases à cocher — `is_public`, `show_email`,
`show_availability`, `notify_by_email`, `notify_on_platform`. **Une case non
envoyée vaut désactivée** : c'est l'erreur classique quand on redessine ce
formulaire.

La vue reçoit `$user`, `$completion` de 0 à 100 pour la barre de progression,
`$countries`, `$skillsByCategory`, et les listes `$profileTypes`,
`$experienceLevels`, `$availabilities` et `$skillLevels`. Quand
`profile_completed_at` est nul, le profil n'est pas encore complet : il le
devient — et vaut 20 points, une seule fois — dès que le prénom, le nom, le
pays, la ville, la bio, le rôle, la disponibilité et **au moins une
compétence** sont remplis.

Les compétences se gèrent à part : `POST /profile/skills` avec `skill_id` et
`level`, `PATCH /profile/skills/{slug}` avec `level`, `DELETE
/profile/skills/{slug}`. Le niveau vaut `beginner`, `intermediate`, `advanced`
ou `expert`, quinze compétences au maximum.

Après un clone, `php artisan storage:link` est nécessaire une fois pour que les
photos s'affichent.

---

## Projets et collaboration

Cinq pages : la liste en `/projects`, le détail en `/projects/{slug}`, la
création en `/projects/create`, la modification en `/projects/{slug}/edit`
réservée au propriétaire, et « Mes collaborations » en `/collaborations`. Les
champs du formulaire sont partagés dans `projects/partials/form-fields.blade.php`.

Trois rôles, dans `ProjectMemberRole`. Le **propriétaire** (`owner`) peut tout :
modifier, supprimer, promouvoir, retirer n'importe qui. Le **co-responsable**
(`co_owner`) gère les postes, les demandes et les invitations, et peut retirer
un simple membre. Le **membre** (`member`) peut quitter le projet.

La liste accepte en `GET` : `q`, `category` parmi `fintech`, `healthtech`,
`edtech`, `agritech`, `ecommerce`, `logistics`, `social`, `govtech` et `other` ;
`country` ; `status` parmi `idea`, `recruiting`, `in_progress`, `completed` et
`archived` — les archivés sont masqués par défaut ; `skill`, qui cherche dans
les technologies **et** dans les postes recherchés ; et `sort` valant `recent`,
`team` ou `title`.

Elle reçoit `$projects` paginé par 12, `$filters`, `$hasFilters`, `$sorts`,
`$categories`, `$statuses`, `$countries` et `$skillsByCategory`. Les postes
encore ouverts s'obtiennent par `$project->roles->reject->isFull()`, et les
places libres d'un poste par `$role->openSlots()`.

### Le détail d'un projet

Il reçoit `$project` avec son `owner`, son `country`, ses `skills`, ses `roles`
et ses `memberships`. `$project->coverUrl()` donne l'image ou null.
`$viewerMembership` est mon adhésion si je suis membre, `$viewerPendingRequest`
ma demande ou invitation en attente — son champ `direction` vaut `application`
ou `invitation`.

Quatre drapeaux commandent l'interface : `$canUpdate` pour le propriétaire,
`$canManage` pour lui et les co-responsables, `$canApply` quand je peux
demander à rejoindre. Les responsables reçoivent en plus
`$pendingApplications`, `$pendingInvitations`, `$suggestions` — des
développeurs proposés par poste ouvert — et `$skills` pour ajouter un poste.

Les actions : `POST /projects/{slug}/requests` avec `project_role_id` et
`message` pour demander à rejoindre ; `POST /projects/{slug}/invitations` avec
`user_id` pour inviter ; `POST /collaborations/{id}/accept` et `/reject`, et
`DELETE /collaborations/{id}` pour annuler ; `POST`, `PUT` et `DELETE` sur
`/projects/{slug}/roles` pour les postes, avec `title`, `skill_id` et `slots` ;
`PATCH /projects/{slug}/members/{username}` avec `role` pour promouvoir ;
`DELETE` sur la même adresse pour retirer ; `POST /projects/{slug}/leave` pour
partir.

Les erreurs métier — « le poste est déjà pourvu », « vous avez déjà une demande
en attente » — arrivent dans `$errors` sous les clés `collaboration`,
`project_role_id` ou `member`.

### Créer ou modifier un projet

`enctype="multipart/form-data"`. Le formulaire envoie `title` (100 caractères),
`description` (5000), `category`, `country_id` facultatif, `status` — limité à
`idea`, `recruiting` et `in_progress` à la création —, `members_wanted` de 1 à
50, `skills[]` plafonné à dix, et `cover`, avec `remove_cover=1` pour
l'effacer. À la création seulement, on peut déclarer jusqu'à dix postes par
`roles[i][title]`, `roles[i][skill_id]` et `roles[i][slots]` ; les lignes sans
titre sont ignorées. Suppression par `DELETE /projects/{slug}`.

Côté DevScore : créer un projet vaut 50 points, une collaboration acceptée en
vaut 40 pour le nouveau membre, et passer un projet en « terminé » en vaut 100,
une seule fois.

### Mes collaborations

Quatre listes : `$receivedInvitations`, les projets qui m'invitent, à accepter
ou refuser ; `$receivedApplications`, les demandes reçues sur mes projets, mêmes
actions ; `$sentApplications`, mes demandes en attente, que je peux annuler ; et
`$history`, les vingt dernières demandes traitées.

---

## AfriCommit AI

Le membre écrit ce qu'il cherche en une phrase, l'IA en tire des critères et
recommande jusqu'à dix profils avec un pourcentage de correspondance.

La question se pose en `/ai`, route `ai.create` ; les résultats vivent en
`/ai/{id}`, route `ai.show`, **visibles par leur auteur seulement**. Le
formulaire part en `POST /ai`, route `ai.store`, avec le champ `query` de 10 à
500 caractères, limité à dix recherches par minute. L'appel à l'IA prend
quelques secondes : il faut un indicateur d'attente au moment de l'envoi.

La page de question reçoit `$examples`, quatre phrases toutes faites, et
`$recentSearches`, mes cinq dernières recherches.

La page de résultats reçoit `$search->query`, puis `$criteria` : `skills` donne
les slugs compris, `skill_categories` les domaines quand aucune compétence
précise n'a été reconnue, `country` et `project_type` les deux autres critères,
`summary` une reformulation en français — nulle si l'analyseur de secours a
pris le relais — et `engine`, qui vaut `claude` ou `local`. `$results` est
trié, chaque entrée portant `score` de 0 à 100, `matched_skills` et `user`.

Le score se répartit ainsi : 60 points pour les compétences demandées, pondérés
par le niveau, une compétence voisine du même domaine comptant pour 70 % ; 20
pour le pays ; 10 pour avoir déjà travaillé sur un projet du même type ; 10
pour la disponibilité. Un critère non demandé donne ses points à tout le monde.
Seuls les profils publics, actifs et complétés sont recommandés.

---

## Dev Resources

Cinq pages : la liste en `/resources`, le détail en `/resources/{slug}` — une
ressource en attente ou refusée n'est visible que par son auteur et les
administrateurs —, la publication en `/resources/create`, la modification en
`/resources/{slug}/edit` réservée à l'auteur, et le téléchargement en
`/resources/{slug}/download`, qui incrémente le compteur.

La liste accepte `q`, `type` parmi `api`, `template`, `framework`, `package`,
`ui_kit`, `documentation`, `script`, `library` et `open_source`, puis `skill`,
`country`, `tag` et `sort` valant `recent`, `popular`, `rating` ou `downloads`.
Elle reçoit `$resources` paginé par 12, `$filters`, `$hasFilters`, `$sorts`,
`$types`, `$countries`, `$skillsByCategory` et `$popularTags`, les quinze tags
les plus utilisés avec leur `resources_count`.

Chaque ressource porte `title`, `description`, `type`, `user`, `country`,
`skills`, `tags`, `imageUrl()`, `likes_count`, `rating_avg` et
`downloads_count`.

Le détail reçoit en plus `$reviews`, `$myReview` pour pré-remplir, `$comments`,
et quatre drapeaux : `$hasLiked`, `$canInteract` — connecté, pas l'auteur,
ressource publiée —, `$canComment` et `$canUpdate`. `hasFile()` décide du bouton
de téléchargement.

Les actions : `POST` et `DELETE` sur `/resources/{slug}/like` ; `POST
/resources/{slug}/reviews` avec `rating` de 1 à 5 et `comment` facultatif, et
`DELETE` pour retirer sa note ; `POST /resources/{slug}/comments` avec `body` ;
`DELETE /comments/{id}`, autorisé par `@can('delete', $comment)`.

Le formulaire de publication exige `enctype="multipart/form-data"`. Il envoie
`title` (120 caractères), `description` (5000), `type`, `country_id` facultatif
et pré-rempli avec le pays de l'auteur, `skills[]` plafonné à dix, `tags` — **un
seul champ texte, séparé par des virgules**, huit tags de 30 caractères au
plus —, `image` (2 Mo) et `file` (10 Mo), chacun avec son `remove_image=1` ou
`remove_file=1`, plus `github_url` et `demo_url`.

Publier vaut 30 points, chaque « j'aime » en vaut 5 pour l'auteur — les retirer
les reprend, on ne peut donc pas en accumuler artificiellement —, et trois
ressources publiées donnent le badge Contributeur actif.

---

## DevScore, classement et badges

Le classement est public, en `/ranking`, route `ranking.index`. « Mon
DevScore » est en `/devscore`, route `devscore.show`.

Le classement se filtre par `country` — vide pour toute l'Afrique —, par
`period` valant `all`, `year` ou `month`, et par `year` ou `month` selon la
période, le mois au format `2026-10` que comprend `<input type="month">`. Une
valeur invalide ou future retombe sur la période en cours.

Il reçoit `$developers` paginé par 25, chacun avec ses `points` **sur la
période** — à convertir en entier —, son `name`, son `country` et ses `badges`.
`$firstRank` donne le rang du premier de la page : le rang d'une ligne vaut
`$firstRank + $loop->index`. Viennent ensuite `$filters`, `$periodLabel`,
`$periods`, `$countries` et `$myRank`, mon propre rang ou null. Seuls les
profils publics, actifs et complétés sont classés.

« Mon DevScore » reçoit `$user->devscore`, puis `$ranks` avec ses trois clés
`africa`, `country` et `month`, chacune pouvant être nulle ; `$breakdown`, une
ligne par action du barème avec `action`, `points_each`, `times` et `total` ;
`$history`, les vingt derniers gains ; et `$badges`, les six badges avec leur
`earned_at`, nul tant qu'il reste à débloquer.

Le barème, modifiable dans `config/devscore.php` : profil complété 20 points,
projet créé 50, ressource publiée 30, collaboration 40, ressource appréciée 5,
projet terminé 100.

Six badges, identifiés par un `slug` stable. `premier-projet` s'obtient en
créant un projet, `collaborateur` en rejoignant celui d'un autre, `builder`
quand un de ses projets est terminé, `expert-laravel` au niveau expert en
Laravel, `contributeur-actif` à la troisième ressource publiée, et
`panafricain` quand ses équipes réunissent des membres d'au moins trois pays.

**Le front-end choisit l'icône d'après le slug**, jamais d'après le champ `icon`
de la base, qui contient un emoji. La correspondance vit dans
`components/badge-icon.blade.php` ; un badge inconnu retombe sur la médaille.

Un badge gagné apparaît sur le profil public, dans le classement et dans le fil
d'activité. Quand il se débloque en changeant une compétence, le message de
`session('status')` le signale — **et contient l'emoji du badge**. Si vous
l'affichez ailleurs que dans le bandeau standard, retirez-le.

---

## Favoris

`/favorites`, route `favorites.index`. La page reçoit `$developers`,
`$resources` et `$projects` enregistrés. Les éléments supprimés, privés ou
suspendus en disparaissent d'eux-mêmes.

On ajoute par `POST /favorites/{type}/{id}` et on retire par `DELETE` sur la
même adresse, `type` valant `developer`, `resource` ou `project`. Dans une vue,
`auth()->user()->hasFavorited($model)` dit si c'est déjà enregistré.

Le bouton est réutilisable :

```blade
@include('partials.favorite-button', ['type' => 'resource', 'model' => $resource])
```

Il affiche « Enregistrer » ou « Enregistré » avec une icône de marque-page,
pleine quand c'est fait. Le marque-page plutôt que l'étoile, parce que l'étoile
sert déjà à noter les ressources. Rien ne s'affiche pour un visiteur ni sur son
propre profil. Il est déjà posé sur le profil, le projet et la ressource.

---

## Tableau de bord

`/dashboard`, la première page après la connexion.

Elle reçoit `$user`, `$completion` pour inciter à compléter le profil quand il
est sous 100, `$africaRank` ou null, `$stats` avec ses clés `projects`,
`resources`, `collaborations` et `followers`, et `$pending` avec `invitations`
et `applications`, qui renvoient vers `/collaborations`.

`$recommendedDevelopers` donne quatre profils au plus, chacun avec son `user`,
un `score` en pourcentage — nul hors du cas des postes ouverts — et une
`reason` en clair. Le choix suit une règle simple : si je gère des projets avec
des postes ouverts, ce sont les meilleurs profils pour ces postes, calculés par
le même moteur qu'AfriCommit AI ; sinon, ce sont les développeurs actifs de mon
pays que je ne suis pas encore.

`$interestingProjects` donne quatre projets, d'abord ceux qui recrutent sur mes
compétences.

Le fil reçoit `$feed`, quinze événements au plus, `$feedScope` et `$feedScopes`
pour les deux onglets — mon réseau, ou toute la communauté —, et
`$activityFeed`, le service qui sait présenter un événement. Chaque événement
porte un `user`, un `type`, une date, et un sujet dont
`$activityFeed->linkFor()` donne le lien — nul pour un badge — et
`$activityFeed->subjectTitle()` le titre.

**Attention à `subjectTitle()` : pour un badge, elle préfixe l'emoji au nom.**
La vue détecte donc le cas `Badge` et affiche l'icône elle-même plutôt que
d'appeler cette méthode. Les événements des profils privés ou suspendus, et
ceux dont le contenu a été supprimé, sont déjà masqués.

---

## Notifications

La page est en `/notifications`, route `notifications.index`, et reçoit
`$notifications` paginé. Chaque notification porte `data['icon']`,
`data['message']`, `read_at` — nul si elle n'est pas lue — et `created_at`.

Trois autres routes servent la cloche : `GET /notifications/poll` renvoie du
JSON, `GET /notifications/{id}` marque comme lu puis redirige, et
`POST /notifications/read-all` marque tout.

Huit événements déclenchent une notification : une demande de collaboration
prévient le propriétaire et les co-responsables ou le développeur invité ; une
demande acceptée prévient l'intéressé ; un projet rejoint prévient l'équipe ; un
nouveau follower, une ressource publiée, un commentaire, un badge obtenu et un
nouveau message préviennent la personne concernée. Les trois premiers partent
aussi par email. Chaque membre choisit dans « Modifier mon profil » entre
`notify_on_platform` et `notify_by_email`. En local, les emails ne partent pas :
ils sont écrits dans `storage/logs/laravel.log`.

Le backend stocke l'icône sous forme d'**emoji** dans `data['icon']`.
L'interface ne l'affiche jamais : `<x-notification-icon>` la traduit en icône du
jeu — poignée de main pour une demande, fusée pour un projet rejoint, colis pour
une ressource, bulle pour un commentaire, et ainsi de suite. Un emoji non prévu
retombe sur la cloche. La table de correspondance vit dans
`components/notification-icon.blade.php`, et nulle part ailleurs.

La cloche est le composant Alpine `notificationBell`, fourni par
`resources/js/notifications.js`. Il expose `unread`, `items` — les huit
dernières, chacune avec `id`, `message`, `icon`, `url`, `read` et `time` —,
`open`, `muted`, `toggleMute()` et `markAllRead()`. Il interroge le serveur
toutes les dix secondes, immédiatement au retour sur l'onglet, se met en pause
quand l'onglet est caché, joue un son généré par le navigateur à chaque
nouveauté et affiche le compte dans le titre de l'onglet. **Redessinez tout ce
que vous voulez autour, gardez ces noms.** Les navigateurs n'autorisent le son
qu'après un premier clic ou une frappe : c'est normal.

---

## Messagerie

`/messages` liste mes conversations, `/messages/{conversation}` en ouvre une.
Sur téléphone, on montre l'une **ou** l'autre, jamais les deux. On écrit à
quelqu'un par `POST /devs/{user}/message`, route `messages.start`, qui crée la
conversation si besoin puis y redirige : c'est le bouton du profil public.

Envoyer se fait par `POST /messages/{conversation}` avec le champ `body`,
obligatoire, 2000 caractères. `POST /messages/{conversation}/read` marque comme
lu, `GET /messages/{conversation}/poll` sert de repli quand le temps réel ne
s'établit pas.

La vue reçoit `$conversations`, les lignes déjà mises en forme ;
`$conversation`, nulle sur `/messages` ; `$other`, l'interlocuteur ; `$messages`,
du plus ancien au plus récent ; `$readUpTo`, l'identifiant du dernier message lu
par l'autre, qui permet d'afficher « Lu » ; et `$canReply`, faux quand le membre
est suspendu ou supprimé — on relit alors la conversation sans pouvoir répondre.

Une ligne de conversation porte `id`, `url`, un objet `other` avec `name`,
`initials`, `avatar_url` et `profile_url`, un `last` avec `body`, `mine` et
`sent_at` — nul sur une conversation vide —, et `unread`. Un message porte `id`,
`sender_id`, `body` et `sent_at` en ISO 8601.

Trois composants Alpine viennent de `resources/js/messages.js`.
`conversationList` expose `items`, `isActive()` et `formatListTime()`.
`chatThread` expose `messages`, `body`, `isSending`, `error`, `send()`,
`isMine()`, `startsNewDay()`, `formatDay()`, `formatTime()`, `statusFor()`,
`onKeydown()` et `resizeTextarea()`. `messagesBadge` expose `unread` et `label`
pour le compteur de la barre.

Le temps réel passe par Laravel Reverb. Sans lui, le sondage prend le relais ;
sans JavaScript du tout, le formulaire reste un formulaire HTML et la page se
recharge. Le fil est en `aria-live="polite"` : ne retirez pas cet attribut, il
annonce les messages aux lecteurs d'écran.

---

## Écosystème

`/ecosysteme`, route `ecosystem` — l'adresse est en français, sans accent, le
nom de route en anglais comme le reste du code.

La page reçoit `$totals` avec `developers`, `countries`, `projects`,
`resources` et `collaborations` ; `$countries`, tous les pays actifs avec leurs
`developers_count`, `projects_count`, `resources_count`, `top_developer` et
`main_skills` ; `$chartCountries`, les dix premiers pour le graphique ; et
`$topSkills`.

Les deux sont nécessaires : le tableau complet reste lisible là où le graphique
ne dit plus rien, par exemple pour les pays à une seule personne.

---

## Signalement et administration

Le bouton « Signaler » est réutilisable :

```blade
@include('partials.report-button', [
    'type' => 'comment', 'model' => $comment,
    'authorId' => $comment->user_id, 'compact' => true,
])
```

`type` vaut `developer`, `resource`, `project` ou `comment`. Il est déjà posé
sur le profil public, le détail d'un projet, celui d'une ressource et chacun de
ses commentaires. Rien ne s'affiche pour un visiteur ni sur son propre contenu.
L'option `compact` n'affiche que l'icône, pour une ligne de commentaire.

Le signalement part en `POST /reports/{type}/{id}`, route `reports.store`, avec
`reason` valant `spam`, `inappropriate`, `scam`, `illegal` ou `other`, et
`details` facultatif, 1000 caractères. Un même membre ne peut pas signaler deux
fois le même contenu tant que le premier signalement n'est pas traité.

### Les cinq pages d'administration

Réservées aux comptes `is_admin`, accessibles par le menu du compte. Le compte
de démonstration est `admin@africommit.com`, mot de passe `password`. Le
sous-menu commun vit dans `admin/partials/nav.blade.php`.

**Les statistiques**, en `/admin`, reçoivent `$stats` — `users`,
`users_this_month`, `suspended`, `projects`, `projects_recruiting`,
`projects_completed`, `resources`, `resources_pending`, `countries`,
`collaborations`, `reports_pending` —, `$topCountries`, les dix pays les plus
représentés, et `$signupsPerMonth`, six mois d'inscriptions.

**Les utilisateurs**, en `/admin/users`, se filtrent par `q` et par `filter`
valant `all`, `suspended`, `admins` ou `unverified`. On suspend par
`POST /admin/users/{username}/suspend`, on réactive par `/unsuspend`, on
supprime par `DELETE`. Un compte suspendu ne peut plus se connecter, est
déconnecté à la page suivante et disparaît du site. **On ne peut ni suspendre ni
supprimer un administrateur, ni soi-même.**

**Les ressources**, en `/admin/resources`, se filtrent par `status` valant
`pending`, `approved` ou `rejected` ; les ressources en attente s'affichent en
premier. On valide par `POST /admin/resources/{slug}/approve`, on refuse par
`/reject`, on supprime par `DELETE`. `reports_count` donne le nombre de
signalements reçus.

**Les projets**, en `/admin/projects`, se filtrent par `q` et se suppriment par
`DELETE /admin/projects/{slug}`, ce qui annule les demandes en attente.

**Les signalements**, en `/admin/reports`, se filtrent par `status` valant
`pending` par défaut, `resolved` ou `dismissed`. Chaque signalement porte son
`reason`, ses `details`, son `reporter`, sa date, son `status`, et son `handler`
avec `handled_at` une fois traité. Le texte et le lien du contenu visé
s'obtiennent par `ReportController::describe()` et `::linkFor()`, qui renvoient
« Contenu supprimé » si le contenu n'existe plus.

On traite par `POST /admin/reports/{id}` avec `action` valant `dismiss` pour
rejeter, `resolve` pour marquer traité, ou `remove` pour **supprimer le contenu
ou suspendre le compte** s'il s'agit d'un profil — ce qui ferme aussi les
autres signalements sur ce contenu. Seule cette dernière action est
irréversible : elle demande confirmation.
