@props(['status'])

@if ($status)
    <div {{ $attributes->merge(['class' => 'alert-success']) }}>
        <x-icon name="check-circle" class="mt-px h-4 w-4 shrink-0" />
        <p class="flex-1 font-medium">{{ $status }}</p>
    </div>
@endif
