# Notifications — guide pour le front-end

> Backend prêt sur la branche `feature/notifications` (cahier, section 14).
> Décision d'équipe : **quasi temps réel** — la cloche interroge le serveur toutes les **10 secondes**
> (marche sur tous les hébergements), avec un **son** à chaque nouvelle notification.

## Ce qui déclenche une notification

| Notification | Reçue par | Icône | Email aussi |
|---|---|---|---|
| Nouvelle demande de collaboration | Propriétaire + co-responsables (demande) / développeur invité (invitation) | 🤝 | ✅ |
| Demande acceptée | Le développeur accepté | 🎉 | ✅ |
| Projet rejoint | L'équipe, quand un invité accepte | 🚀 | ✅ |
| Nouveau follower | La personne suivie | 👋 | — |
| Ressource publiée | Les abonnés de l'auteur | 📦 | — |
| Commentaire | L'auteur de la ressource | 💬 | — |
| Badge obtenu (bonus) | Le membre | icône du badge | — |
| Nouveau message | (avec la messagerie) | | |

Chaque membre choisit dans **Modifier mon profil** : « sur la plateforme » (`notify_on_platform`) et/ou « par email » (`notify_by_email`).
En local, les emails ne partent pas : ils sont écrits dans `storage/logs/laravel.log`.

## La cloche 🔔 (dans `layouts/navigation.blade.php`)

Composant Alpine `notificationBell` (code : `resources/js/notifications.js`), déjà placé dans la navigation, visible aussi sur mobile.

```blade
<div x-data="notificationBell({ pollUrl, readAllUrl, csrf, initialUnread })"> … </div>
```

Variables Alpine utilisables dans le HTML de la cloche :

| Variable / méthode | Rôle |
|---|---|
| `unread` | Nombre de non lues → pastille rouge |
| `items` | 8 dernières notifications : `id`, `message`, `icon`, `url`, `read`, `time` (« il y a 2 minutes ») |
| `open` | Menu ouvert / fermé |
| `muted` / `toggleMute()` | Son coupé ou non (mémorisé dans le navigateur) |
| `markAllRead()` | Tout marquer comme lu |

Comportement déjà géré par le script :
- vérification toutes les 10 s (et immédiatement quand on revient sur l'onglet ; pause quand l'onglet est caché) ;
- **son « ding-dong »** généré par le navigateur (Web Audio, pas de fichier audio) pour chaque nouvelle notification ;
- le titre de l'onglet affiche le nombre : « (3) DevLink Africa » ;
- les navigateurs n'autorisent le son qu'**après un premier clic ou une touche** sur la page : c'est normal.

Tu peux redessiner librement la cloche et le menu (garde `x-data="notificationBell(...)"` et les noms ci-dessus).
⚠️ Après avoir modifié `resources/js` ou `resources/css` : `npm run dev` (ou `npm run build`).

## Routes

| Action | Méthode + URL | Route |
|---|---|---|
| Page « Mes notifications » | `GET /notifications` | `notifications.index` (vue `notifications/index.blade.php`, `$notifications` paginé) |
| Vérification (JSON) | `GET /notifications/poll?since=…` | `notifications.poll` |
| Ouvrir une notification (la marque lue + redirige) | `GET /notifications/{id}` | `notifications.open` |
| Tout marquer comme lu | `POST /notifications/read-all` | `notifications.read-all` |

Dans la page, chaque notification : `$notification->data['icon']`, `$notification->data['message']`, `$notification->read_at` (null = non lue), `$notification->created_at->diffForHumans()`.

Réponse de `/notifications/poll` :

```json
{
  "unread": 2,
  "notifications": [
    { "id": "…", "message": "Ibrahim Sow souhaite rejoindre AfricaDelivery.", "icon": "🤝", "url": "/notifications/…", "read": false, "time": "il y a 5 secondes" }
  ],
  "server_time": "2026-10-12T10:00:20+00:00"
}
```
