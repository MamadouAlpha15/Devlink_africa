# Le front-end d'AfriCommit

L'interface est écrite en Blade, Tailwind CSS 3 et Alpine 3, compilée par Vite.
Aucune bibliothèque de composants : tout le vocabulaire visuel est maison, et
tient dans deux endroits — `resources/css/app.css` pour les classes,
`resources/views/components/` pour les briques.

Quatre documents, et c'est tout :

- **[design-system.md](design-system.md)** — les règles visuelles : couleurs,
  typographie, surfaces, boutons, formulaires, thème sombre, mouvement.
- **[composants.md](composants.md)** — les briques disponibles, Blade et
  Alpine. À lire avant d'écrire du balisage.
- **[pages.md](pages.md)** — le contrat de chaque page avec le backend :
  adresses, routes, variables, champs de formulaire.
- Ce fichier — comment c'est organisé, et ce qu'il ne faut pas faire.

## Les cinq règles

Elles ne se discutent pas : une page qui les enfreint détonne immédiatement au
milieu des autres.

**Aucun dégradé.** Les fonds sont plats. La profondeur vient des bordures et
d'une ombre discrète, jamais d'un fondu de couleurs.

**Aucun emoji affiché.** Les icônes sont des SVG. Le backend en stocke pourtant
— pour les pays, les badges et les notifications — et ils sont traduits à
l'affichage. Voir plus bas.

**Code en anglais, interface en français.** Les noms de variables, de composants
et de fichiers sont anglais ; tout ce que lit un membre est français.

**L'indigo est réservé à l'action.** Un bouton principal, un lien, un état
sélectionné. Le reste est en gris ardoise. Un élément indigo qui ne se clique
pas est une erreur.

**Le mouvement sert le contenu.** Pas de boucle, pas de rebond, pas de zoom au
survol. Une animation accompagne l'arrivée d'un élément ou transporte une
information, sinon elle n'existe pas.

## Les emojis venus de la base

Trois champs en contiennent, et aucun n'arrive à l'écran.

`countries.flag` stocke un drapeau emoji ; l'interface affiche un SVG de
`public/flags/` par `<x-flag>`. `badges.icon` stocke un emoji ; l'interface
choisit une icône d'après le **slug** du badge, par `<x-badge-icon>`.
`notifications.data['icon']` stocke un emoji ; `<x-notification-icon>` le
traduit.

La table de correspondance vit à un seul endroit,
`components/notification-icon.blade.php`. Ses clés y sont écrites en points de
code Unicode plutôt qu'en caractères, pour qu'aucun emoji n'apparaisse dans le
code. Un emoji inconnu retombe sur l'icône cloche, jamais sur une case vide.

Le choix du slug plutôt que de l'emoji pour les badges est délibéré : un emoji
peut changer en base sans prévenir, un slug non.

## Où vit le code

```
resources/
  css/app.css              le design system : 69 classes de composants
  js/app.js                thème clair/sombre, animations, 10 composants Alpine
  js/notifications.js      la cloche          (livré avec le backend)
  js/messages.js           la messagerie      (livré avec le backend)
  js/echo.js               le temps réel      (Reverb)
  views/
    layouts/               le squelette commun : app, guest, navigation
      partials/            head, footer, et le script qui pose le thème
    components/            35 briques réutilisables
    partials/              les boutons Favori et Signaler
    admin/                 les cinq pages d'administration
    …                      un dossier par domaine : devs, projects, resources…
public/flags/              54 drapeaux SVG (flag-icons, licence MIT)
tailwind.config.js         les jetons : couleurs, police, ombres
```

84 vues, 35 composants Blade, 14 composants Alpine, 69 classes CSS, 82 icônes.

## Lancer le projet

```bash
composer install
npm install
npm run dev        # serveur Vite, rechargement à chaud
php artisan serve  # http://localhost:8000
```

`npm run build` produit le rendu de production.

## Trois pièges qui coûtent une heure

**Le CSS compilé n'est pas versionné.** `public/build/` est dans le
`.gitignore`. Après un `git pull` ou un `git checkout` qui touche
`resources/`, relancez `npm run build` — sinon vous regardez le CSS d'une autre
version du projet. Rien ne vous préviendra : la page s'affiche simplement sans
style.

**`public/hot` traîne après un Vite arrêté brutalement.** Tant que ce fichier
existe, Laravel cherche les assets sur le serveur de développement. S'il n'y
répond plus, l'interface perd tout son style. Supprimez le fichier et relancez.

**Une couleur statique et une couleur conditionnelle ne cohabitent pas sur le
même élément.** Les règles `text-ink-*` sont compilées après les `text-amber-*` :
`ink` gagne toujours, quel que soit l'ordre dans l'attribut `class`. Mettez les
deux états dans le `:class` d'Alpine, ou portez la couleur de repos sur le
parent.

## Ce qui ne doit pas changer d'un seul côté

Le front-end et le backend se tiennent par des noms : les routes, les champs de
formulaire, les paramètres de recherche, les clés des variables passées aux
vues, et les propriétés des composants Alpine livrés avec le backend. Renommer
l'un sans l'autre casse la page en silence, sans erreur PHP.

[pages.md](pages.md) liste ces noms, domaine par domaine.
