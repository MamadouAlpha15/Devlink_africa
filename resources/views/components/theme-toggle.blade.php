{{--
    Bascule clair / sombre. Le choix est conservé dans le localStorage par
    le composant Alpine `themeToggle` (resources/js/app.js).
--}}
<button
    type="button"
    x-data="themeToggle"
    @click="toggle()"
    :aria-label="label"
    :title="label"
    {{ $attributes->merge(['class' => 'btn btn-ghost btn-square']) }}
>
    {{-- Soleil en thème sombre (pour revenir au clair), lune en thème clair. --}}
    <x-icon name="sun" class="h-5 w-5" x-show="isDark" x-cloak />
    <x-icon name="moon" class="h-5 w-5" x-show="! isDark" />
</button>
