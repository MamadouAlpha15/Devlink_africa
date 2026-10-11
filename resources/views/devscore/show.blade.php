{{--
    Mon DevScore — version fonctionnelle minimale (backend).
    Le front-end la restylera : voir docs/front-end/devscore-classement.md.
--}}
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Mon DevScore</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto px-4 space-y-6">

            <div class="p-6 bg-white shadow rounded">
                <p class="text-3xl font-bold">{{ $user->devscore }} points</p>
                <p class="text-gray-600">
                    Afrique : {{ $ranks['africa'] ? $ranks['africa'].'e' : '—' }}
                    @if ($user->country) · {{ $user->country->flag }} {{ $user->country->name }} : {{ $ranks['country'] ? $ranks['country'].'e' : '—' }} @endif
                    · Ce mois-ci : {{ $ranks['month'] ? $ranks['month'].'e' : '—' }}
                </p>
                @if ($user->profile_completed_at === null || ! $user->is_public)
                    <p class="text-sm text-yellow-700 mt-2">Seuls les profils publics et complétés apparaissent dans le classement.</p>
                @endif
                <a href="{{ route('ranking.index') }}" class="text-sm underline">Voir le classement africain</a>
            </div>

            <div class="p-6 bg-white shadow rounded">
                <h3 class="font-semibold mb-2">Comment j'ai gagné mes points</h3>
                <table class="w-full text-sm">
                    <thead><tr class="text-left text-gray-600"><th>Action</th><th>Barème</th><th>Nombre</th><th class="text-right">Total</th></tr></thead>
                    <tbody>
                        @foreach ($breakdown as $line)
                            <tr>
                                <td>{{ $line['action']->label() }}</td>
                                <td>+{{ $line['points_each'] }}</td>
                                <td>× {{ $line['times'] }}</td>
                                <td class="text-right">{{ $line['total'] }} pts</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="p-6 bg-white shadow rounded">
                <h3 class="font-semibold mb-2">Badges</h3>
                <ul class="grid sm:grid-cols-2 gap-2">
                    @foreach ($badges as $item)
                        <li class="p-2 rounded {{ $item['earned_at'] ? 'bg-yellow-50' : 'bg-gray-50 opacity-60' }}">
                            {{ $item['badge']->icon }} <strong>{{ $item['badge']->name }}</strong>
                            <span class="block text-sm text-gray-600">{{ $item['badge']->description }}</span>
                            <span class="block text-xs text-gray-500">{{ $item['earned_at'] ? 'Obtenu '.$item['earned_at']->diffForHumans() : 'À débloquer' }}</span>
                        </li>
                    @endforeach
                </ul>
            </div>

            <div class="p-6 bg-white shadow rounded">
                <h3 class="font-semibold mb-2">Historique</h3>
                <ul class="text-sm space-y-1">
                    @forelse ($history as $transaction)
                        <li>
                            <strong>+{{ $transaction->points }}</strong> {{ $transaction->action->label() }}
                            @if ($transaction->subject instanceof \App\Models\Project || $transaction->subject instanceof \App\Models\Resource)
                                · {{ $transaction->subject->title }}
                            @endif
                            <span class="text-gray-500">· {{ $transaction->created_at->diffForHumans() }}</span>
                        </li>
                    @empty
                        <li class="text-gray-600">Aucun point pour l'instant. Complétez votre profil pour gagner vos premiers points !</li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>
</x-app-layout>
