@props([
    'href'   => '#',
    'active' => false,
    'icon'   => null,
])

<a
    href="{{ $href }}"
    wire:navigate
    @class([
        'group flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition-all duration-100',
        'text-white shadow-sm shadow-indigo-900/50' => $active,
        'text-zinc-400 hover:bg-white/[.06] hover:text-zinc-100' => ! $active,
    ])
    @style(['background:linear-gradient(90deg,#4f46e5,#2563eb)' => $active])
>
    @if($icon)
        <flux:icon
            :icon="$icon"
            class="size-4 shrink-0 {{ $active ? 'text-white' : 'text-zinc-500 group-hover:text-zinc-300' }}"
        />
    @endif
    {{ $slot }}
</a>
