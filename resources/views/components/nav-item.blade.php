@props([
    'href'   => '#',
    'active' => false,
    'icon'   => null,
])

<a
    href="{{ $href }}"
    wire:navigate
    @class([
        'group flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition-colors duration-100',
        'bg-blue-600 text-white shadow-sm shadow-blue-900/40' => $active,
        'text-zinc-400 hover:bg-zinc-800/60 hover:text-zinc-100' => ! $active,
    ])
>
    @if($icon)
        <flux:icon
            :icon="$icon"
            class="size-4 shrink-0 {{ $active ? 'text-white' : 'text-zinc-500 group-hover:text-zinc-300' }}"
        />
    @endif
    {{ $slot }}
</a>
