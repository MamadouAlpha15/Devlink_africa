@props(['active' => false])

<a {{ $attributes->merge(['class' => 'nav-item '.($active ? 'nav-item-active' : '')]) }}>
    {{ $slot }}
</a>
