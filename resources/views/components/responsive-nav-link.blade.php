@props(['active' => false])

<a {{ $attributes->merge(['class' => 'nav-item-mobile '.($active ? 'nav-item-mobile-active' : '')]) }}>
    {{ $slot }}
</a>
