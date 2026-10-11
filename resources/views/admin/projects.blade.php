{{-- Administration — projets. Version fonctionnelle minimale (backend). --}}
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Administration — Projets</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto px-4">
            @include('admin.partials.nav')

            <form method="GET" class="flex gap-2 mb-4">
                <x-text-input name="q" :value="$q" placeholder="Titre du projet" />
                <x-primary-button>Rechercher</x-primary-button>
            </form>

            <div class="bg-white shadow rounded overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="text-left text-gray-600"><tr><th class="p-2">Projet</th><th>Propriétaire</th><th>Catégorie</th><th>Statut</th><th>Membres</th><th>Signalements</th><th>Créé</th><th></th></tr></thead>
                    <tbody class="divide-y">
                        @forelse ($projects as $project)
                            <tr>
                                <td class="p-2"><a href="{{ route('projects.show', $project) }}" class="underline">{{ $project->title }}</a></td>
                                <td>{{ $project->owner->name }}</td>
                                <td>{{ $project->category->label() }}</td>
                                <td>{{ $project->status->label() }}</td>
                                <td>{{ $project->memberships_count }}</td>
                                <td>{{ $project->reports_count ?: '' }}</td>
                                <td>{{ $project->created_at->format('d/m/Y') }}</td>
                                <td class="p-2">
                                    <form method="POST" action="{{ route('admin.projects.destroy', $project) }}" onsubmit="return confirm('Supprimer ce projet ?')">
                                        @csrf @method('DELETE')
                                        <button class="underline text-red-700">Supprimer</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="p-4 text-gray-600">Aucun projet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-4">{{ $projects->links() }}</div>
        </div>
    </div>
</x-app-layout>
