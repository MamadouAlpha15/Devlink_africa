# L'écosystème DevLink Africa — guide pour le front-end

> Backend prêt (cahier, section 5, bonus). La vue
> `ecosystem/index.blade.php` est **déjà designée**. Elle sert de référence
> pour tout graphique du produit : barres horizontales d'une seule couleur,
> triées, la valeur écrite au bout, et un tableau lisible en dessous.

| URL | Route | Vue | Accès |
|---|---|---|---|
| `/ecosysteme` | `ecosystem` | `ecosystem/index.blade.php` | Tout le monde |

L'URL est en français — `/ecosysteme`, sans accent. Le nom de route, lui, est
en anglais comme le reste du code.

## Variables

| Variable | Contenu |
|---|---|
| `$totals` | `developers`, `countries` (ceux qui ont au moins un membre), `projects`, `resources`, `collaborations` |
| `$countries` | **Tous** les pays actifs, avec `developers_count`, `projects_count`, `resources_count`, `top_developer`, `main_skills` |
| `$chartCountries` | Les dix premiers pays ayant au moins un développeur, pour le graphique |
| `$topSkills` | Les compétences les plus présentes, avec `developers_count` |

`$countries` alimente le tableau complet, `$chartCountries` le graphique. Les
deux sont nécessaires : le tableau reste lisible quand le graphique ne dit plus
rien, par exemple pour les pays à une seule personne.

## La grammaire des graphiques

Elle vaut pour cette page comme pour l'administration. Trois règles :

1. **Une seule série, une seule couleur.** Pas de légende à lire, donc.
2. **Trié par valeur**, du plus grand au plus petit.
3. **La valeur écrite au bout de la barre**, en chiffres tabulaires. Une barre
   sans son nombre oblige à estimer.

La structure est une grille de trois colonnes — libellé, barre, valeur :

```blade
<span class="h-2.5 rounded-r-[4px] bg-ink-100 dark:bg-ink-800" aria-hidden="true">
    <span class="block h-full rounded-r-[4px] bg-brand-600 dark:bg-brand-400"
          style="width: {{ $barWidth($valeur, $max) }}"></span>
</span>
```

`$barWidth` garde un minimum de 2 % pour qu'une valeur non nulle reste visible :
une barre invisible se confond avec un zéro.

La barre porte `aria-hidden` et c'est le **lien parent** qui porte le
`aria-label` complet (« Guinée : 12 développeurs, 3 projets »). Sans cela, un
lecteur d'écran annoncerait une image vide.

## Points d'attention

- Les drapeaux passent par `<x-flag>`, jamais par `$country->flag` qui contient
  l'emoji stocké en base.
- Les nombres sont formatés à la française : `number_format($v, 0, ',', ' ')`.
- Chaque ligne du graphique est un lien vers la recherche filtrée sur ce pays.
  Une statistique sur laquelle on ne peut pas cliquer est une impasse.
