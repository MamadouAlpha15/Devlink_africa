{{-- Administration — ressources. Version fonctionnelle minimale (backend). --}}
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Administration — Ressources</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto px-4">
            @include('admin.partials.nav')

            <div class="flex gap-3 text-sm mb-4">
                <a href="{{ route('admin.resources.index') }}" class="{{ $status === null ? 'font-bold underline' : '' }}">Toutes</a>
                @foreach ($statuses as $value)
                    <a href="{{ route('admin.resources.index', ['status' => $value->value]) }}" class="{{ $status === $value->value ? 'font-bold underline' : '' }}">{{ $value->label() }}</a>
                @endforeach
            </div>

            <div class="bg-white shadow rounded overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="text-left text-gray-600"><tr><th class="p-2">Ressource</th><th>Auteur</th><th>Type</th><th>Statut</th><th>Signalements</th><th>Publiée</th><th></th></tr></thead>
                    <tbody class="divide-y">
                        @forelse ($resources as $resource)
                            <tr>
                                <td class="p-2"><a href="{{ route('resources.show', $resource) }}" class="underline">{{ $resource->title }}</a></td>
                                <td>{{ $resource->user->name }}</td>
                                <td>{{ $resource->type->label() }}</td>
                                <td>{{ $resource->status->label() }}</td>
                                <td>{{ $resource->reports_count ?: '' }}</td>
                                <td>{{ $resource->created_at->format('d/m/Y') }}</td>
                                <td class="p-2 whitespace-nowrap">
                                    @if ($resource->status !== \App\Enums\ResourceStatus::Approved)
                                        <form method="POST" action="{{ route('admin.resources.approve', $resource) }}" class="inline">@csrf <button class="underline text-green-700">Valider</button></form>
                                    @endif
                                    @if ($resource->status !== \App\Enums\ResourceStatus::Rejected)
                                        <form method="POST" action="{{ route('admin.resources.reject', $resource) }}" class="inline">@csrf <button class="underline text-yellow-700">Refuser</button></form>
                                    @endif
                                    <form method="POST" action="{{ route('admin.resources.destroy', $resource) }}" class="inline" onsubmit="return confirm('Supprimer cette ressource ?')">
                                        @csrf @method('DELETE')
                                        <button class="underline text-red-700">Supprimer</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="p-4 text-gray-600">Aucune ressource.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-4">{{ $resources->links() }}</div>
        </div>
    </div>
</x-app-layout>
