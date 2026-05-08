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
        <div class="flex items-center gap-3 rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
            <div class="rounded-lg bg-violet-50 p-2 dark:bg-violet-900/30">
                <flux:icon.map-pin class="size-5 text-violet-600 dark:text-violet-400" />
            </div>
            <div>
                <p class="text-2xl font-bold text-zinc-900 dark:text-zinc-100">{{ $this->stats['location_count'] }}</p>
                <p class="text-xs text-zinc-500">{{ __('Locations') }}</p>
            </div>
        </div>

        <div class="flex items-center gap-3 rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
            <div class="rounded-lg bg-emerald-50 p-2 dark:bg-emerald-900/30">
                <flux:icon.cube class="size-5 text-emerald-600 dark:text-emerald-400" />
            </div>
            <div>
                <p class="text-2xl font-bold text-zinc-900 dark:text-zinc-100">{{ $this->stats['total_stock'] }}</p>
                <p class="text-xs text-zinc-500">{{ __('Total items in stock') }}</p>
            </div>
        </div>

        <div class="flex items-center gap-3 rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
            <div class="rounded-lg bg-amber-50 p-2 dark:bg-amber-900/30">
                <flux:icon.tag class="size-5 text-amber-600 dark:text-amber-400" />
            </div>
            <div>
                <p class="text-2xl font-bold text-zinc-900 dark:text-zinc-100">{{ $this->stats['product_count'] }}</p>
                <p class="text-xs text-zinc-500">{{ __('Distinct products') }}</p>
            </div>
        </div>
    </div>

    {{-- Locations --}}
    @if ($this->locations->isEmpty())
        <div class="flex flex-col items-center justify-center rounded-xl border border-dashed border-zinc-300 py-20 text-center dark:border-zinc-700">
            <div class="mb-3 rounded-full bg-zinc-100 p-4 dark:bg-zinc-800">
                <flux:icon.map-pin class="size-8 text-zinc-400" />
            </div>
            <flux:heading size="lg" class="mb-1">{{ __('No locations yet') }}</flux:heading>
            <flux:text class="mb-4 text-zinc-500">{{ __('Add storage locations to this warehouse.') }}</flux:text>
            <flux:button wire:click="openCreateLocation" variant="primary" icon="plus" size="sm">{{ __('New Location') }}</flux:button>
        </div>
    @else
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
            @foreach ($this->locations as $location)
                <div class="flex flex-col gap-3 rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">

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
