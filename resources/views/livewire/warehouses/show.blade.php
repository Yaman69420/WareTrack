<div class="flex h-full w-full flex-1 flex-col gap-6 p-6">

    {{-- Back + Header --}}
    <div>
        <flux:button :href="route('warehouses.index')" wire:navigate variant="ghost" icon="arrow-left" size="sm" class="mb-4">
            {{ __('All Warehouses') }}
        </flux:button>

        <div class="flex items-start justify-between">
            <div class="flex items-center gap-4">
                <div class="rounded-xl bg-blue-50 p-3 dark:bg-blue-900/30">
                    <flux:icon.building-office class="size-7 text-blue-600 dark:text-blue-400" />
                </div>
                <div>
                    <flux:heading size="xl">{{ $warehouse->name }}</flux:heading>
                    <div class="mt-0.5 flex items-center gap-1.5 text-sm text-zinc-500">
                        <flux:icon.map-pin class="size-4" />
                        {{ $warehouse->location }}
                    </div>
                </div>
            </div>
            <flux:button wire:click="openCreateLocation" variant="primary" icon="plus">
                {{ __('New Location') }}
            </flux:button>
        </div>

        @if ($warehouse->description)
            <p class="mt-3 max-w-2xl text-sm text-zinc-500">{{ $warehouse->description }}</p>
        @endif
    </div>

    {{-- Stats bar --}}
    <div class="grid grid-cols-3 gap-4">
        <div class="flex items-center gap-3 rounded-xl border border-white/[.08] bg-white p-4 dark:bg-white/[.04]">
            <div class="rounded-lg bg-violet-50 p-2 dark:bg-violet-900/30">
                <flux:icon.map-pin class="size-5 text-violet-600 dark:text-violet-400" />
            </div>
            <div>
                <p class="text-2xl font-bold text-zinc-900 dark:text-zinc-100">{{ $this->stats['location_count'] }}</p>
                <p class="text-xs text-zinc-500">{{ __('Locations') }}</p>
            </div>
        </div>

        <div class="flex items-center gap-3 rounded-xl border border-white/[.08] bg-white p-4 dark:bg-white/[.04]">
            <div class="rounded-lg bg-emerald-50 p-2 dark:bg-emerald-900/30">
                <flux:icon.cube class="size-5 text-emerald-600 dark:text-emerald-400" />
            </div>
            <div>
                <p class="text-2xl font-bold text-zinc-900 dark:text-zinc-100">{{ $this->stats['total_stock'] }}</p>
                <p class="text-xs text-zinc-500">{{ __('Total items in stock') }}</p>
            </div>
        </div>

        <div class="flex items-center gap-3 rounded-xl border border-white/[.08] bg-white p-4 dark:bg-white/[.04]">
            <div class="rounded-lg bg-amber-50 p-2 dark:bg-amber-900/30">
                <flux:icon.tag class="size-5 text-amber-600 dark:text-amber-400" />
            </div>
            <div>
                <p class="text-2xl font-bold text-zinc-900 dark:text-zinc-100">{{ $this->stats['product_count'] }}</p>
                <p class="text-xs text-zinc-500">{{ __('Distinct products') }}</p>
            </div>
        </div>
    </div>

    {{-- Locations header + view toggle --}}
    <div class="flex items-center justify-between">
        <flux:heading size="lg">{{ __('Locations') }}</flux:heading>
        @if ($this->locations->isNotEmpty())
            <div class="flex items-center gap-1 rounded-lg border border-white/[.08] bg-white/[.03] p-1">
                <button
                    wire:click="$set('viewMode', 'grid')"
                    class="flex items-center gap-1.5 rounded-md px-3 py-1.5 text-xs font-medium transition
                           {{ $viewMode === 'grid' ? 'bg-white/[.08] text-white' : 'text-zinc-500 hover:text-zinc-300' }}"
                >
                    <flux:icon.squares-2x2 class="size-3.5" />
                    {{ __('Cards') }}
                </button>
                <button
                    wire:click="$set('viewMode', 'heatmap')"
                    class="flex items-center gap-1.5 rounded-md px-3 py-1.5 text-xs font-medium transition
                           {{ $viewMode === 'heatmap' ? 'bg-white/[.08] text-white' : 'text-zinc-500 hover:text-zinc-300' }}"
                >
                    <flux:icon.map class="size-3.5" />
                    {{ __('Heatmap') }}
                </button>
            </div>
        @endif
    </div>

    {{-- Locations --}}
    @if ($this->locations->isEmpty())
        <div class="flex flex-col items-center justify-center rounded-xl border border-dashed border-white/[.10] py-20 text-center">
            <div class="mb-3 rounded-full bg-zinc-100 p-4 dark:bg-zinc-800">
                <flux:icon.map-pin class="size-8 text-zinc-400" />
            </div>
            <flux:heading size="lg" class="mb-1">{{ __('No locations yet') }}</flux:heading>
            <flux:text class="mb-4 text-zinc-500">{{ __('Add storage locations to this warehouse.') }}</flux:text>
            <flux:button wire:click="openCreateLocation" variant="primary" icon="plus" size="sm">{{ __('New Location') }}</flux:button>
        </div>

    @elseif ($viewMode === 'grid')
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
            @foreach ($this->locations as $location)
                <div class="flex flex-col gap-3 rounded-xl border border-white/[.08] bg-white p-4 dark:bg-white/[.04]">

                    {{-- Code + actions --}}
                    <div class="flex items-start justify-between">
                        <div>
                            <span class="inline-flex items-center rounded-lg bg-zinc-100 px-2.5 py-1 text-sm font-mono font-semibold text-zinc-800 dark:bg-zinc-800 dark:text-zinc-200">
                                {{ $location->code }}
                            </span>
                            @if ($location->name)
                                <p class="mt-1 text-xs text-zinc-500">{{ $location->name }}</p>
                            @endif
                        </div>
                        <flux:dropdown>
                            <flux:button icon="ellipsis-horizontal" variant="ghost" size="sm" />
                            <flux:menu>
                                <flux:menu.item icon="pencil" wire:click="openEditLocation({{ $location->id }})">
                                    {{ __('Edit') }}
                                </flux:menu.item>
                                <flux:menu.separator />
                                <flux:menu.item
                                    icon="trash"
                                    variant="danger"
                                    wire:click="deleteLocation({{ $location->id }})"
                                    wire:confirm="{{ __('Delete this location?') }}"
                                >
                                    {{ __('Delete') }}
                                </flux:menu.item>
                            </flux:menu>
                        </flux:dropdown>
                    </div>

                    {{-- Stock stats --}}
                    <div class="flex items-center gap-4 border-t border-zinc-100 pt-3 dark:border-zinc-800">
                        <div class="flex items-center gap-1.5 text-sm">
                            <flux:icon.cube class="size-4 text-zinc-400" />
                            <span class="font-semibold text-zinc-800 dark:text-zinc-200">{{ $location->total_stock }}</span>
                            <span class="text-zinc-400">{{ __('items') }}</span>
                        </div>
                        <div class="flex items-center gap-1.5 text-sm">
                            <flux:icon.tag class="size-4 text-zinc-400" />
                            <span class="font-semibold text-zinc-800 dark:text-zinc-200">{{ $location->product_count }}</span>
                            <span class="text-zinc-400">{{ __('SKUs') }}</span>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

    @else
        {{-- ===== HEATMAP VIEW ===== --}}
        @php
            $maxStock = $this->maxLocationStock;
        @endphp

        {{-- Legend --}}
        <div class="flex items-center gap-3 text-xs text-zinc-500">
            <span>{{ __('Empty') }}</span>
            <div class="flex h-3 flex-1 max-w-48 overflow-hidden rounded-full"
                 style="background: linear-gradient(to right, rgba(39,39,42,1), rgba(59,130,246,.3), rgba(16,185,129,1))">
            </div>
            <span>{{ __('Full') }}</span>
            <span class="ml-4 text-zinc-600">{{ __('Relative to max at this warehouse (') }}{{ $maxStock }}{{ __(')') }}</span>
        </div>

        {{-- Grid --}}
        <div class="grid gap-2" style="grid-template-columns: repeat(auto-fill, minmax(80px, 1fr))">
            @foreach ($this->locations as $location)
                @php
                    $pct = $maxStock > 0 ? ($location->total_stock / $maxStock) : 0;
                    // Interpolate colour: empty=zinc-800 → mid=blue-500 → full=emerald-500
                    if ($pct === 0) {
                        $bg = 'rgba(39,39,42,0.8)';
                        $border = 'rgba(255,255,255,0.06)';
                    } elseif ($pct < 0.5) {
                        $alpha = 0.15 + $pct * 0.7;
                        $bg = "rgba(59,130,246,{$alpha})";
                        $border = "rgba(59,130,246,0.4)";
                    } else {
                        $alpha = 0.35 + ($pct - 0.5) * 1.3;
                        $bg = "rgba(16,185,129,{$alpha})";
                        $border = "rgba(16,185,129,0.5)";
                    }
                    $pctLabel = round($pct * 100);
                @endphp

                <div
                    class="group relative flex flex-col items-center justify-center rounded-lg p-2 text-center transition hover:scale-105 hover:z-10 cursor-default"
                    style="background:{{ $bg }}; border: 1px solid {{ $border }}; aspect-ratio: 1"
                    title="{{ $location->code }}{{ $location->name ? ' — ' . $location->name : '' }}: {{ $location->total_stock }} items ({{ $pctLabel }}%)"
                >
                    <span class="text-xs font-mono font-semibold text-white/90 leading-none">{{ $location->code }}</span>
                    @if ($location->total_stock > 0)
                        <span class="mt-0.5 text-[10px] tabular-nums text-white/60 leading-none">{{ $location->total_stock }}</span>
                    @endif

                    {{-- Hover tooltip --}}
                    <div class="pointer-events-none absolute bottom-full left-1/2 z-20 mb-2 -translate-x-1/2 whitespace-nowrap
                                rounded-lg border border-white/[.10] bg-zinc-900 px-3 py-2 text-xs shadow-xl
                                opacity-0 group-hover:opacity-100 transition-opacity">
                        <p class="font-mono font-semibold text-zinc-100">{{ $location->code }}</p>
                        @if ($location->name)
                            <p class="text-zinc-400">{{ $location->name }}</p>
                        @endif
                        <p class="mt-1 text-zinc-300">{{ $location->total_stock }} {{ __('items') }} · {{ $location->product_count }} {{ __('SKUs') }}</p>
                        <p class="text-zinc-500">{{ $pctLabel }}% {{ __('of max') }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    {{-- Add / Edit Location Modal --}}
    <flux:modal wire:model="showModal" class="max-w-sm">
        <div class="flex flex-col gap-6 p-6">
            <div>
                <flux:heading size="lg">{{ $editingLocationId ? __('Edit Location') : __('New Location') }}</flux:heading>
                <flux:subheading>{{ $warehouse->name }}</flux:subheading>
            </div>

            <flux:field>
                <flux:label>{{ __('Code') }}</flux:label>
                <flux:input wire:model="code" placeholder="{{ __('e.g. A1') }}" class="uppercase" />
                <flux:error name="code" />
            </flux:field>

            <flux:field>
                <flux:label>{{ __('Name') }} <span class="text-xs font-normal text-zinc-400">({{ __('optional') }})</span></flux:label>
                <flux:input wire:model="locationName" placeholder="{{ __('e.g. Ground shelf') }}" />
                <flux:error name="locationName" />
            </flux:field>

            <div class="flex justify-end gap-3">
                <flux:button wire:click="$set('showModal', false)" variant="ghost">{{ __('Cancel') }}</flux:button>
                <flux:button wire:click="saveLocation" variant="primary">{{ $editingLocationId ? __('Update') : __('Create') }}</flux:button>
            </div>
        </div>
    </flux:modal>

</div>
