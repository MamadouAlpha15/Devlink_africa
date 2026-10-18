{{--
    Le backend envoie un emoji comme icône de notification (`data['icon']`,
    voir app/Notifications/). L'interface, elle, n'affiche pas d'emoji : on
    traduit chaque emoji en icône du jeu de l'application.

    La table de correspondance vit ici et nulle part ailleurs. Deux usages :

      <x-notification-icon sprite />        une fois par page (navigation) :
                                            dépose les `<symbol>` réutilisables
                                            et la table pour la cloche Alpine,
                                            qui ne connaît l'emoji qu'au moment
                                            du rendu côté navigateur.

      <x-notification-icon :emoji="..." />  rendu serveur d'une notification.

    Les clés sont écrites en points de code Unicode et non en caractères :
    aucun emoji n'apparaît dans le code, et une clé reste lisible même dans
    un éditeur ou un terminal qui ne sait pas les afficher.

    Un emoji inconnu retombe sur la cloche : jamais de case vide.
--}}
@props([
    'emoji' => null,
    'sprite' => false,
])

@php
    $iconFor = [
        // --- Notifications -------------------------------------------
        "\u{1F91D}" => 'handshake',     // poignée de main : demande de collaboration
        "\u{1F389}" => 'check-circle',  // confettis : demande acceptée
        "\u{1F680}" => 'rocket',        // fusée : projet rejoint
        "\u{1F44B}" => 'user-plus',     // main qui salue : nouveau follower
        "\u{1F4E6}" => 'package',       // colis : ressource publiée
        "\u{1F4AC}" => 'message',       // bulle : commentaire

        // --- Badges (app/Models/Badge) -------------------------------
        // Une notification de badge reprend l'icône du badge.
        "\u{1F525}" => 'flame',         // flamme : Expert Laravel
        "\u{2B50}" => 'star',           // étoile : Contributeur actif
        "\u{1F30D}" => 'globe',         // globe : Panafricain
        "\u{1F3D7}\u{FE0F}" => 'hammer', // grue : Builder
        "\u{1F3D7}" => 'hammer',        // la même, sans le sélecteur de variante
    ];

    $fallback = 'bell';
    $spriteNames = array_values(array_unique(array_merge(array_values($iconFor), [$fallback])));
@endphp

@if ($sprite)
    {{--
        Sorti du flux plutôt que `hidden` : un `display: none` sur la racine
        empêche `<use>` de résoudre la référence dans certains navigateurs.
    --}}
    <svg aria-hidden="true" style="position: absolute; width: 0; height: 0; overflow: hidden;">
        @foreach ($spriteNames as $name)
            <x-icon :name="$name" :symbol="'notif-'.$name" />
        @endforeach
    </svg>

    <script>
        window.africommitNotificationIcons = @js($iconFor);
    </script>
@else
    <svg {{ $attributes->merge(['class' => 'h-4 w-4']) }} aria-hidden="true">
        <use href="#notif-{{ $iconFor[$emoji] ?? $fallback }}" />
    </svg>
@endif
