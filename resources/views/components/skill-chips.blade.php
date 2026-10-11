{{--
    Liste de compétences, avec un « +N » au-delà de la limite.

    Les compétences qui correspondent à la recherche sont marquées par le
    poids de la graisse et un contour plus franc, pas par une couleur :
    une teinte de plus sur une carte qui en compte déjà plusieurs ne
    signalerait plus rien.

    Usage : <x-skill-chips :skills="$developer->skills" :limit="4" />
--}}
@props([
    'skills',
    'limit' => 4,
    'highlight' => [],
    'withLevel' => false,
])

@php
    $visibleSkills = $limit ? $skills->take($limit) : $skills;
    $hiddenCount = $limit ? max(0, $skills->count() - $limit) : 0;
    $highlighted = collect($highlight);
@endphp

<ul {{ $attributes->merge(['class' => 'flex flex-wrap gap-1.5']) }}>
    @foreach ($visibleSkills as $skill)
        <li class="{{ $highlighted->contains($skill->name) ? 'chip-strong' : 'chip' }}">
            {{ $skill->name }}
            @if ($withLevel && $skill->pivot?->level)
                <span class="text-ink-400 dark:text-ink-500">{{ $skill->pivot->level->label() }}</span>
            @endif
        </li>
    @endforeach

    @if ($hiddenCount > 0)
        <li class="chip border-dashed text-ink-500 dark:text-ink-400">+{{ $hiddenCount }}</li>
    @endif
</ul>
