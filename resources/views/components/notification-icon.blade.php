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

    Un emoji inconnu retombe sur la cloche : jamais de case vide.
--}}
@props([
    'emoji' => null,
    'sprite' => false,
])

@php
    $iconFor = [
        // Notifications
        '🤝' => 'handshake',      // demande de collaboration
        '🎉' => 'check-circle',   // demande acceptée
        '🚀' => 'rocket',         // projet rejoint
        '👋' => 'user-plus',      // nouveau follower
        '📦' => 'package',        // ressource publiée
        '💬' => 'message',        // commentaire
        // Badges (app/Models/Badge) : une notification de badge reprend son icône
        '🔥' => 'flame',
        '⭐' => 'star',
        '🌍' => 'globe',
        '🏗️' => 'hammer',
        '🏗' => 'hammer',         // même badge sans le sélecteur de variante
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
        window.devlinkNotificationIcons = @js($iconFor);
    </script>
@else
    <svg {{ $attributes->merge(['class' => 'h-4 w-4']) }} aria-hidden="true">
        <use href="#notif-{{ $iconFor[$emoji] ?? $fallback }}" />
    </svg>
@endif
