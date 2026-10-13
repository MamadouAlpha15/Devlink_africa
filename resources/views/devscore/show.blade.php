{{--
    Mon DevScore.

    Variables : $user, $ranks (africa, country, month — chacun peut être null),
    $breakdown (action, points_each, times, total), $history, $badges.
    Voir docs/front-end/pages.md.
--}}
@php
    // Rangs présentés comme un relevé : trois lignes, pas trois cartes.
    $rankRows = [
        ['label' => 'Afrique', 'value' => $ranks['africa']],
        ['label' => $user->country?->name ?? 'Mon pays', 'value' => $ranks['country']],
        ['label' => 'Ce mois-ci', 'value' => $ranks['month']],
    ];

    $earnedCount = collect($badges)->filter(fn ($item) => $item['earned_at'])->count();
    $isListed = $user->profile_completed_at !== null && $user->is_public;
@endphp

<x-app-layout title="Mon DevScore">
    <div class="mx-auto max-w-4xl px-4 py-8 sm:px-6 lg:px-8 lg:py-10">

        <x-page-header title="Mon DevScore" subtitle="Vos points, votre rang et vos badges.">
            <x-slot name="actions">
                <a href="{{ route('ranking.index') }}" class="btn-secondary btn-sm">
                    <x-icon name="trophy" class="h-4 w-4" />
                    Classement africain
                </a>
            </x-slot>
        </x-page-header>

        <x-flash class="mt-6" />

        {{--
            Le score et les rangs côte à côte : le chiffre seul ne dit rien,
            c'est sa position qui l'éclaire.
        --}}
        <div class="card mt-6 grid gap-6 p-5 sm:p-6 md:grid-cols-[13rem_minmax(0,1fr)] md:gap-8">
            <div>
                <p class="caption">Total</p>
                <p class="tabular mt-1 text-4xl font-semibold tracking-[-0.025em] text-ink-900 dark:text-white">
                    {{ $user->devscore }}
                </p>
                <p class="muted mt-1">points</p>
            </div>

            <dl class="divide-y divide-ink-100 dark:divide-ink-800/70">
                @foreach ($rankRows as $row)
                    <div class="flex items-baseline justify-between gap-4 py-2 first:pt-0 last:pb-0">
                        <dt class="text-sm text-ink-600 dark:text-ink-400">{{ $row['label'] }}</dt>
                        <dd class="tabular text-sm font-semibold text-ink-900 dark:text-white">
                            @if ($row['value'])
                                {{ $row['value'] }}<sup>{{ $row['value'] === 1 ? 'er' : 'e' }}</sup>
                            @else
                                <span class="font-normal text-ink-400 dark:text-ink-500">Non classé</span>
                            @endif
                        </dd>
                    </div>
                @endforeach
            </dl>
        </div>

        {{-- Pourquoi je n'apparais pas dans le classement : dit une fois, clairement. --}}
        @unless ($isListed)
            <div class="alert-warning mt-4">
                <x-icon name="alert-circle" class="mt-0.5 h-4 w-4 shrink-0" />

                <div class="min-w-0 flex-1">
                    <p class="font-medium">Vous n'apparaissez pas encore au classement</p>
                    <p class="mt-0.5">Seuls les profils publics et complétés y sont inscrits.</p>
                </div>

                <a href="{{ route('developer-profile.edit') }}" class="btn-secondary btn-sm shrink-0">Mon profil</a>
            </div>
        @endunless

        {{--
            Le barème sert deux fois : il explique les points déjà gagnés et
            montre ceux qui restent à prendre. Les lignes jamais déclenchées
            restent donc visibles, en gris.
        --}}
        <section class="panel mt-6">
            <x-section-heading title="Comment j'ai gagné mes points" />

            <div class="mt-4 overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-ink-200 dark:border-ink-800">
                            <th scope="col" class="caption pb-2 text-left font-semibold">Action</th>
                            <th scope="col" class="caption pb-2 text-right font-semibold">Barème</th>
                            <th scope="col" class="caption pb-2 text-right font-semibold">Fois</th>
                            <th scope="col" class="caption pb-2 text-right font-semibold">Total</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-ink-100 dark:divide-ink-800/70">
                        @foreach ($breakdown as $line)
                            <tr class="{{ $line['times'] === 0 ? 'text-ink-400 dark:text-ink-500' : '' }}">
                                <td class="py-2.5 pr-4">{{ $line['action']->label() }}</td>
                                <td class="tabular py-2.5 text-right text-ink-500 dark:text-ink-400">+{{ $line['points_each'] }}</td>
                                <td class="tabular py-2.5 text-right">{{ $line['times'] }}</td>
                                <td class="tabular py-2.5 text-right font-semibold {{ $line['times'] === 0 ? '' : 'text-ink-900 dark:text-white' }}">
                                    {{ $line['total'] }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>

                    <tfoot>
                        <tr class="border-t-2 border-ink-200 dark:border-ink-800">
                            <td class="pt-2.5 font-semibold text-ink-900 dark:text-white" colspan="3">Total</td>
                            <td class="tabular pt-2.5 text-right font-semibold text-ink-900 dark:text-white">
                                {{ $user->devscore }}
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </section>

        {{--
            Badges. Les non obtenus ne sont pas masqués : ils indiquent quoi
            viser. Ils se distinguent par la bordure pointillée et le gris,
            pas par un `opacity` qui rendrait le texte illisible.
        --}}
        <section class="panel mt-6">
            <x-section-heading title="Badges" :count="$earnedCount.' / '.count($badges)" />

            <ul class="mt-4 grid gap-3 sm:grid-cols-2">
                @foreach ($badges as $item)
                    @php($earned = (bool) $item['earned_at'])

                    <li class="flex items-start gap-3 rounded-lg border p-3 {{ $earned
                        ? 'border-ink-200 bg-white dark:border-ink-800 dark:bg-ink-900'
                        : 'border-dashed border-ink-200 bg-ink-50/60 dark:border-ink-800 dark:bg-ink-900/40' }}">
                        <x-badge-icon
                            :badge="$item['badge']"
                            class="mt-0.5 h-5 w-5 shrink-0 {{ $earned ? 'text-brand-600 dark:text-brand-400' : 'text-ink-300 dark:text-ink-600' }}"
                        />

                        <div class="min-w-0">
                            <p class="text-sm font-semibold {{ $earned ? 'text-ink-900 dark:text-white' : 'text-ink-500 dark:text-ink-400' }}">
                                {{ $item['badge']->name }}
                            </p>
                            <p class="muted mt-0.5">{{ $item['badge']->description }}</p>
                            <p class="caption mt-1.5 {{ $earned ? '' : 'text-ink-400 dark:text-ink-500' }}">
                                {{ $earned ? 'Obtenu '.$item['earned_at']->diffForHumans() : 'À débloquer' }}
                            </p>
                        </div>
                    </li>
                @endforeach
            </ul>
        </section>

        {{-- Historique : les 20 derniers gains, du plus récent au plus ancien. --}}
        <section class="panel mt-6">
            <x-section-heading title="Historique" />

            @forelse ($history as $transaction)
                @if ($loop->first)
                    <ul class="mt-4 divide-y divide-ink-100 dark:divide-ink-800/70">
                @endif

                <li class="flex items-baseline gap-3 py-2.5 first:pt-0 last:pb-0">
                    <span class="tabular w-10 shrink-0 text-sm font-semibold text-ink-900 dark:text-white">
                        +{{ $transaction->points }}
                    </span>

                    <span class="min-w-0 flex-1">
                        <span class="block truncate text-sm text-ink-700 dark:text-ink-300">
                            {{ $transaction->action->label() }}

                            @if ($transaction->subject instanceof \App\Models\Project || $transaction->subject instanceof \App\Models\Resource)
                                <span class="text-ink-300 dark:text-ink-700" aria-hidden="true">·</span>
                                <span class="font-medium text-ink-900 dark:text-white">{{ $transaction->subject->title }}</span>
                            @endif
                        </span>
                    </span>

                    <span class="shrink-0 text-xs text-ink-500">{{ $transaction->created_at->diffForHumans() }}</span>
                </li>

                @if ($loop->last)
                    </ul>
                @endif
            @empty
                <x-empty-state class="mt-4" title="Aucun point pour l'instant">
                    Complétez votre profil pour gagner vos vingt premiers points.

                    <x-slot name="action">
                        <a href="{{ route('developer-profile.edit') }}" class="btn-primary btn-sm">Compléter mon profil</a>
                    </x-slot>
                </x-empty-state>
            @endforelse
        </section>
    </div>
</x-app-layout>
