# Messagerie — guide pour le front-end

> Backend prêt (cahier, section 13). La vue `messages/index.blade.php` est
> **déjà designée** : liste des conversations à gauche, fil à droite, et sur
> téléphone l'un **ou** l'autre, jamais les deux. Vous pouvez la retoucher, à
> condition de garder les noms ci-dessous.

Le temps réel passe par **Laravel Reverb** (`resources/js/echo.js`). Si la
connexion ne s'établit pas, un sondage prend le relais : la messagerie
fonctionne toujours, avec un peu de latence. Et sans JavaScript du tout, le
formulaire d'envoi reste un formulaire HTML ordinaire — la page se recharge.

## Pages et routes

| Action | Méthode + URL | Route |
|---|---|---|
| Mes conversations | `GET /messages` | `messages.index` |
| Une conversation | `GET /messages/{conversation}` | `messages.show` |
| Envoyer | `POST /messages/{conversation}` | `messages.store` |
| Marquer comme lu | `POST /messages/{conversation}/read` | `messages.read` |
| Sondage | `GET /messages/{conversation}/poll` | `messages.poll` |
| Écrire à un membre | `POST /devs/{user}/message` | `messages.start` |

`messages.start` crée la conversation si elle n'existe pas, puis redirige
vers elle. C'est le bouton « Envoyer un message » du profil public.

Champ du formulaire d'envoi : `body`, obligatoire, 2000 caractères maximum.

## Variables de la vue

| Variable | Contenu |
|---|---|
| `$conversations` | Les lignes de la liste, déjà mises en forme (voir ci-dessous) |
| `$conversation` | La conversation ouverte, ou `null` sur `/messages` |
| `$other` | L'interlocuteur (présent seulement si une conversation est ouverte) |
| `$messages` | L'historique, du plus ancien au plus récent |
| `$readUpTo` | L'identifiant du dernier message lu par l'autre — sert à afficher « Lu » |
| `$canReply` | `false` si le membre est suspendu ou supprimé : on relit, on ne répond plus |

**Une ligne de `$conversations`** (`Messaging::summary`) :

```json
{
  "id": 1,
  "url": "/messages/1",
  "other": { "name": "…", "initials": "IS", "avatar_url": null, "profile_url": "/devs/…" },
  "last": { "body": "…", "mine": false, "sent_at": "2026-10-12T10:00:20+00:00" },
  "unread": 2
}
```

**Un message** (`Messaging::present`) : `id`, `sender_id`, `body`, `sent_at`
(ISO 8601). `last` vaut `null` sur une conversation sans message.

## Les composants Alpine

Fournis par `resources/js/messages.js`. **Gardez ces noms** : tout
l'habillage autour est libre.

### `conversationList({ items, activeId, meId })`

| Propriété | Rôle |
|---|---|
| `items` | Les lignes, mises à jour en temps réel |
| `isActive(item)` | La conversation ouverte |
| `formatListTime(sent_at)` | « 10:42 » aujourd'hui, « hier », puis la date |

### `chatThread({ conversationId, meId, messages, readUpTo, storeUrl, readUrl, pollUrl, csrf, canReply })`

| Propriété | Rôle |
|---|---|
| `messages` | Le fil, complété à l'arrivée d'un message |
| `body` | Le texte en cours de saisie |
| `isSending`, `error` | État de l'envoi |
| `send()` | Envoie, et ajoute le message au fil sans attendre le serveur |
| `isMine(message)` | Oriente la bulle à droite ou à gauche |
| `startsNewDay(index)` | Insère le séparateur de date |
| `formatDay()`, `formatTime()` | Mise en forme des dates |
| `statusFor(message)` | « Envoyé » puis « Lu » |
| `onKeydown($event)` | Entrée envoie, Maj + Entrée va à la ligne |
| `resizeTextarea()` | La zone de saisie grandit avec le texte |
| `$refs.scroller` | Le conteneur du fil, défilé vers le bas à l'arrivée d'un message |

### `messagesBadge({ unread, meId })`

Le compteur de la barre de navigation : `unread` et `label` (« 99+ » au-delà
de 99).

## Points d'attention

- **Le fil est en `aria-live="polite"`** : un message qui arrive est annoncé aux
  lecteurs d'écran. Ne retirez pas cet attribut.
- **La zone de saisie a un libellé masqué** (`sr-only`), pas seulement un
  `placeholder` : un placeholder disparaît dès la première frappe.
- Sur téléphone, le bouton de retour vers la liste n'apparaît qu'en dessous de
  `lg`. C'est voulu : à cette largeur, les deux colonnes ne tiennent pas.
- `pusher-js` pèse environ 68 Ko et il est aujourd'hui chargé sur **toutes** les
  pages. Le charger uniquement sur `/messages` est la première optimisation à
  faire si le poids devient un sujet.
