{{--
    Mes notifications — version fonctionnelle minimale (backend).
    Le front-end la restylera : voir docs/front-end/notifications.md.
--}}
<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Mes notifications</h2>
            <form method="POST" action="{{ route('notifications.read-all') }}">
                @csrf
                <button class="text-sm underline">Tout marquer comme lu</button>
            </form>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-3xl mx-auto px-4 space-y-4">
            @if (session('status'))
                <div class="p-4 bg-green-50 text-green-800 rounded">{{ session('status') }}</div>
            @endif

            <div class="bg-white shadow rounded divide-y">
                @forelse ($notifications as $notification)
                    <a href="{{ route('notifications.open', $notification->id) }}"
                       class="flex gap-3 p-4 hover:bg-gray-50 {{ $notification->read_at ? 'text-gray-500' : 'bg-blue-50 font-medium' }}">
                        <span class="text-xl">{{ $notification->data['icon'] ?? '🔔' }}</span>
                        <span class="flex-1">
                            {{ $notification->data['message'] ?? '' }}
                            <span class="block text-xs text-gray-500 font-normal">{{ $notification->created_at->diffForHumans() }}</span>
                        </span>
                    </a>
                @empty
                    <p class="p-4 text-gray-600">Aucune notification pour l'instant.</p>
                @endforelse
            </div>

            {{ $notifications->links() }}

            <p class="text-sm text-gray-600">
                Choisissez de recevoir les notifications sur la plateforme et/ou par email dans
                <a href="{{ route('developer-profile.edit') }}" class="underline">les paramètres de votre profil</a>.
            </p>
        </div>
    </div>
</x-app-layout>
