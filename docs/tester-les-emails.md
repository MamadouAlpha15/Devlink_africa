# Tester les emails en local (vérification, mot de passe oublié, notifications)

Deux façons de faire. Les deux marchent pour créer de **nouveaux comptes** et les vérifier.

## Option 1 — Sans rien configurer : lire l'email dans le fichier de log

Par défaut (`MAIL_MAILER=log` dans `.env`), Laravel **n'envoie pas** les emails : il les écrit dans
`storage/logs/laravel.log`.

1. Inscris-toi sur `http://localhost:8000/register`.
2. Ouvre `storage/logs/laravel.log` et descends tout en bas.
3. Cherche le lien qui contient `verify-email/` (il ressemble à
   `http://localhost:8000/verify-email/51/3f2a…?expires=…&signature=…`).
4. Copie-le **en entier** dans ton navigateur. ⚠️ Dans le fichier, les `&` peuvent apparaître sous la forme `&amp;` :
   remplace-les par `&` avant de coller.
5. L'adresse est vérifiée : tu arrives sur la page de connexion avec le message
   « Votre adresse email est vérifiée ! ». Pas besoin d'être connecté pour cliquer sur le lien.

Le lien est valable **60 minutes**. S'il a expiré : connecte-toi, puis clique sur le bouton qui renvoie l'email
de vérification ; un nouveau lien apparaît en bas du fichier.

## Option 2 — Recevoir les vrais emails (Brevo)

Le compte Brevo de l'équipe envoie les vrais emails (300 par jour, gratuit).

1. Demande à Alpha **en privé** (WhatsApp, pas sur GitHub) les valeurs `MAIL_USERNAME`, `MAIL_PASSWORD` et
   `MAIL_FROM_ADDRESS`.
2. Dans **ton** `.env` (jamais dans `.env.example`, jamais dans un commit) :

   ```env
   MAIL_MAILER=smtp
   MAIL_SCHEME=null
   MAIL_HOST=smtp-relay.brevo.com
   MAIL_PORT=587
   MAIL_USERNAME=…
   MAIL_PASSWORD=…
   MAIL_FROM_ADDRESS="…"
   MAIL_FROM_NAME="DevLink Africa"
   ```

3. Lance `php artisan config:clear`.
4. Inscris-toi avec une **vraie adresse** : l'email arrive dans ta boîte (regarde aussi dans **Spam / Promotions**).

### Si l'envoi échoue avec « cURL error 60 » ou « SSL certificate problem » (WAMP sous Windows)

PHP de WAMP ne trouve pas les certificats SSL. Dans `C:\wamp64\bin\php\php8.3.xx\php.ini` **et**
`phpForApache.ini`, active ces deux lignes (le fichier de certificats est fourni avec Git pour Windows) :

```ini
curl.cainfo = "C:\Program Files\Git\mingw64\etc\ssl\certs\ca-bundle.crt"
openssl.cafile = "C:\Program Files\Git\mingw64\etc\ssl\certs\ca-bundle.crt"
```

Puis redémarre `php artisan serve` (et les services WAMP).

## Pourquoi le lien ne marche pas sur mon téléphone ?

En local, le lien pointe vers `localhost:8000`, c'est-à-dire **ton ordinateur**. Ouvre l'email sur le même
ordinateur. Une fois le site en ligne, le lien sera `https://…` et marchera partout.
