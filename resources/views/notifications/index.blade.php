{{--
    Mes notifications.

    Variable : $notifications (paginé). Chaque élément porte
    `data['icon']` (un emoji côté backend, traduit en icône ici),
    `data['message']` et `read_at`. Voir docs/front-end/notifications.md.
--}}
@php
    $unreadCount = auth()->user()->unreadNotifications()->count();
@endphp

<x-app-layout title="Mes notifications">
    <div class="mx-auto max-w-3xl px-4 py-8 sm:px-6 lg:px-8 lg:py-10">

        <x-page-header
            title="Mes notifications"
            :subtitle="$unreadCount > 0
                ? $unreadCount.' '.($unreadCount > 1 ? 'non lues' : 'non lue')
                : 'Tout est lu'"
        >
            @if ($unreadCount > 0)
                <x-slot name="actions">
                    <form method="POST" action="{{ route('notifications.read-all') }}">
                        @csrf
                        <button type="submit" class="btn-secondary btn-sm">
                            <x-icon name="check" class="h-4 w-4" />
                            Tout marquer comme lu
                        </button>
                    </form>
                </x-slot>
            @endif
        </x-page-header>

        <x-flash class="mt-6" />

        @if ($notifications->isNotEmpty())
            <div class="card mt-8 divide-y divide-ink-100 dark:divide-ink-800/70">
                @foreach ($notifications as $notification)
                    @php($isUnread = is_null($notification->read_at))

                    <a
                        href="{{ route('notifications.open', $notification->id) }}"
                        class="flex gap-3 px-4 py-3.5 transition-colors first:rounded-t-xl last:rounded-b-xl hover:bg-ink-50 dark:hover:bg-ink-800/60 {{ $isUnread ? 'bg-brand-50/70 dark:bg-brand-950/40' : '' }}"
                    >
                        <span class="mt-0.5 shrink-0 {{ $isUnread ? 'text-brand-600 dark:text-brand-400' : 'text-ink-400 dark:text-ink-500' }}">
                            <x-notification-icon :emoji="$notification->data['icon'] ?? null" class="h-4 w-4" />
                        </span>

                        <span class="min-w-0 flex-1">
                            <span class="block text-sm leading-snug {{ $isUnread ? 'font-medium text-ink-900 dark:text-white' : 'text-ink-600 dark:text-ink-400' }}">
                                {{ $notification->data['message'] ?? '' }}
                            </span>
                            <span class="mt-0.5 block text-xs text-ink-500">
                                {{ $notification->created_at->diffForHumans() }}
                            </span>
                        </span>

                        {{-- Pastille : la seule marque du non-lu qui reste
                             visible si l'on ne distingue pas les fonds. --}}
                        @if ($isUnread)
                            <span class="mt-2 h-1.5 w-1.5 shrink-0 rounded-full bg-brand-600 dark:bg-brand-400" aria-label="Non lue"></span>
                        @endif
                    </a>
                @endforeach
            </div>

            <div class="mt-6">{{ $notifications->links() }}</div>
        @else
            <x-empty-state class="mt-8" icon="bell" title="Aucune notification">
                Les demandes de collaboration, les nouveaux abonnés et les commentaires sur vos
                ressources apparaîtront ici.
            </x-empty-state>
        @endif

        <p class="muted mt-6">
            Vous choisissez de les recevoir sur la plateforme ou par email dans
            <a href="{{ route('developer-profile.edit') }}" class="link">les paramètres de votre profil</a>.
        </p>
    </div>
</x-app-layout>
