{{--
    Logo AfriCommit : une marque géométrique (réseau de développeurs)
    suivie du nom. Aucun dégradé, aplat de couleur unique.

    Usage : <x-logo />  ou  <x-logo :with-text="false" class="h-10 w-10" />
--}}
@props([
    'withText' => true,
])

<span class="inline-flex items-center gap-2.5">
    <span {{ $attributes->merge(['class' => 'flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-brand-600 text-white']) }}>
        {{-- Trois nœuds reliés : la mise en relation entre développeurs. --}}
        <svg viewBox="0 0 24 24" fill="none" class="h-5 w-5" aria-hidden="true">
            <path d="M7.5 9.5 12 6.8l4.5 2.7M7.5 14.5 12 17.2l4.5-2.7" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
            <circle cx="12" cy="5" r="2.1" fill="currentColor"/>
            <circle cx="5.6" cy="12" r="2.1" fill="currentColor"/>
            <circle cx="18.4" cy="12" r="2.1" fill="currentColor"/>
            <circle cx="12" cy="19" r="2.1" fill="currentColor"/>
        </svg>
    </span>

    @if ($withText)
        <span class="text-lg font-bold leading-none tracking-tight text-ink-900 dark:text-white">
            Afri<span class="text-brand-600 dark:text-brand-400">Commit</span>
        </span>
    @endif
</span>
