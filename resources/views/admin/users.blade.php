{{-- Administration — utilisateurs. Version fonctionnelle minimale (backend). --}}
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Administration — Utilisateurs</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto px-4">
            @include('admin.partials.nav')

            <form method="GET" class="flex flex-wrap gap-2 mb-4">
                <x-text-input name="q" :value="$q" placeholder="Nom, email ou pseudo" />
                <select name="filter" class="border-gray-300 rounded">
                    @foreach ($filters as $value => $label)
                        <option value="{{ $value }}" @selected($filter === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                <x-primary-button>Rechercher</x-primary-button>
            </form>

            <div class="bg-white shadow rounded overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="text-left text-gray-600"><tr><th class="p-2">Membre</th><th>Email</th><th>Pays</th><th>Projets</th><th>Ressources</th><th>DevScore</th><th>Inscrit</th><th></th></tr></thead>
                    <tbody class="divide-y">
                        @forelse ($users as $user)
                            <tr class="{{ $user->isSuspended() ? 'bg-red-50' : '' }}">
                                <td class="p-2">
                                    <a href="{{ route('devs.show', $user) }}" class="underline">{{ $user->name }}</a>
                                    @if ($user->is_admin) <span class="text-xs">(admin)</span> @endif
                                    @if ($user->isSuspended()) <span class="text-xs text-red-700">suspendu</span> @endif
                                </td>
                                <td>{{ $user->email }} @unless ($user->hasVerifiedEmail()) <span class="text-xs text-yellow-700">non vérifié</span> @endunless</td>
                                <td>{{ $user->country?->flag }} {{ $user->country?->name }}</td>
                                <td>{{ $user->projects_count }}</td>
                                <td>{{ $user->resources_count }}</td>
                                <td>{{ $user->devscore }}</td>
                                <td>{{ $user->created_at->format('d/m/Y') }}</td>
                                <td class="p-2 whitespace-nowrap">
                                    @unless ($user->is_admin)
                                        @if ($user->isSuspended())
                                            <form method="POST" action="{{ route('admin.users.unsuspend', $user) }}" class="inline">@csrf <button class="underline">Réactiver</button></form>
                                        @else
                                            <form method="POST" action="{{ route('admin.users.suspend', $user) }}" class="inline">@csrf <button class="underline text-yellow-700">Suspendre</button></form>
                                        @endif
                                        <form method="POST" action="{{ route('admin.users.destroy', $user) }}" class="inline" onsubmit="return confirm('Supprimer définitivement ce compte et tout son contenu ?')">
                                            @csrf @method('DELETE')
                                            <button class="underline text-red-700">Supprimer</button>
                                        </form>
                                    @endunless
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="p-4 text-gray-600">Aucun utilisateur.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-4">{{ $users->links() }}</div>
        </div>
    </div>
</x-app-layout>
