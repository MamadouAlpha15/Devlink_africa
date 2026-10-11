{{--
    Administration — statistiques. Version fonctionnelle minimale (backend).
    Le front-end la restylera (tableaux, graphiques) : voir docs/front-end/administration.md.
--}}
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Administration</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto px-4">
            @include('admin.partials.nav')

            @if ($stats['reports_pending'] || $stats['resources_pending'])
                <div class="p-4 mb-6 bg-yellow-50 text-yellow-800 rounded">
                    @if ($stats['reports_pending']) <a href="{{ route('admin.reports.index') }}" class="underline">{{ $stats['reports_pending'] }} signalement(s) à traiter</a>. @endif
                    @if ($stats['resources_pending']) <a href="{{ route('admin.resources.index', ['status' => 'pending']) }}" class="underline">{{ $stats['resources_pending'] }} ressource(s) à valider</a>. @endif
                </div>
            @endif

            <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
                @foreach ([
                    ['Utilisateurs', $stats['users'], '+'.$stats['users_this_month'].' ce mois · '.$stats['suspended'].' suspendu(s)'],
                    ['Projets', $stats['projects'], $stats['projects_recruiting'].' en recrutement · '.$stats['projects_completed'].' terminé(s)'],
                    ['Ressources', $stats['resources'], $stats['resources_pending'].' en attente'],
                    ['Pays', $stats['countries'], 'représentés sur 54'],
                    ['Collaborations', $stats['collaborations'], 'membres ayant rejoint un projet'],
                ] as [$label, $value, $detail])
                    <div class="p-4 bg-white shadow rounded">
                        <p class="text-2xl font-bold">{{ $value }}</p>
                        <p class="font-medium">{{ $label }}</p>
                        <p class="text-xs text-gray-500">{{ $detail }}</p>
                    </div>
                @endforeach
            </div>

            <div class="grid md:grid-cols-2 gap-6 mt-6">
                <div class="p-6 bg-white shadow rounded">
                    <h3 class="font-semibold mb-2">Membres par pays</h3>
                    <table class="w-full text-sm">
                        @foreach ($topCountries as $country)
                            <tr><td>{{ $country->flag }} {{ $country->name }}</td><td class="text-right">{{ $country->users_count }}</td></tr>
                        @endforeach
                    </table>
                </div>
                <div class="p-6 bg-white shadow rounded">
                    <h3 class="font-semibold mb-2">Inscriptions (6 derniers mois)</h3>
                    <table class="w-full text-sm">
                        @foreach ($signupsPerMonth as $month => $count)
                            <tr><td>{{ $month }}</td><td class="text-right">{{ $count }}</td></tr>
                        @endforeach
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
