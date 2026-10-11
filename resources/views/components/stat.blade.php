{{--
    Compteur (projets, abonnés, collaborations…).

    Sans boîte ni icône : une colonne de chiffres se lit mieux nue, et
    quatre cartes identiques côte à côte aplatissent la hiérarchie.

    Usage : <x-stat :value="$user->projects_count" label="Projets" />
--}}
@props([
    'value',
    'label',
])

<div {{ $attributes->merge(['class' => '']) }}>
    <p class="stat-value">{{ $value }}</p>
    <p class="stat-label">{{ $label }}</p>
</div>
