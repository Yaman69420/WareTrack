<div class="flex h-full w-full flex-1 flex-col gap-6 p-6">

    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl">{{ __('Warehouses') }}</flux:heading>
            <flux:subheading>{{ __('Manage your warehouse locations and storage capacity') }}</flux:subheading>
        </div>
        <flux:button wire:click="openCreate" variant="primary" icon="plus">
            {{ __('New Warehouse') }}
        </flux:button>
    </div>

    {{-- Search --}}
    <div class="max-w-sm">
        <flux:input
            wire:model.live.debounce.300ms="search"
            icon="magnifying-glass"
            placeholder="{{ __('Search by name or location...') }}"
        />
    </div>

    {{-- Card Grid --}}
    @if ($this->warehouses->isEmpty())
        <div class="flex flex-col items-center justify-center rounded-xl border border-dashed border-zinc-300 py-20 text-center dark:border-zinc-700">
            <div class="mb-3 rounded-full bg-zinc-100 p-4 dark:bg-zinc-800">
                <flux:icon.building-office class="size-8 text-zinc-400" />
            </div>
            <flux:heading size="lg" class="mb-1">{{ __('No warehouses yet') }}</flux:heading>
            <flux:text class="mb-4 text-zinc-500">{{ $search ? __('No warehouses match your search.') : __('Create your first warehouse to get started.') }}</flux:text>
            @unless($search)
                <flux:button wire:click="openCreate" variant="primary" icon="plus" size="sm">{{ __('New Warehouse') }}</flux:button>
            @endunless
        </div>
    @else
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($this->warehouses as $warehouse)
                <div class="group relative flex flex-col gap-4 rounded-xl border border-zinc-200 bg-white p-5 transition duration-200 hover:border-blue-300 hover:shadow-lg dark:border-zinc-700 dark:bg-zinc-900 dark:hover:border-blue-700">

                    {{-- Top row --}}
                    <div class="flex items-start justify-between">
                        <div class="flex items-center gap-3">
                            <div class="rounded-lg bg-blue-50 p-2.5 dark:bg-blue-900/30">
                                <flux:icon.building-office class="size-5 text-blue-600 dark:text-blue-400" />
                            </div>
                            <div>
                                <p class="font-semibold text-zinc-900 dark:text-zinc-100">{{ $warehouse->name }}</p>
                                <p class="flex items-center gap-1 text-xs text-zinc-500">
                                    <flux:icon.map-pin class="size-3" />
                                    {{ $warehouse->location }}
                                </p>
                            </div>
                        </div>

                        {{-- Actions (above the link overlay) --}}
                        <div class="relative z-10">
                            <flux:dropdown>
                                <flux:button icon="ellipsis-horizontal" variant="ghost" size="sm" />
                                <flux:menu>
                                    <flux:menu.item icon="eye" :href="route('warehouses.show', $warehouse)" wire:navigate>
                                        {{ __('View') }}
                                    </flux:menu.item>
                                    <flux:menu.item icon="pencil" wire:click="openEdit({{ $warehouse->id }})">
                                        {{ __('Edit') }}
                                    </flux:menu.item>
                                    <flux:menu.separator />
                                    <flux:menu.item
                                        icon="trash"
                                        variant="danger"
                                        wire:click="delete({{ $warehouse->id }})"
                                        wire:confirm="{{ __('Delete this warehouse?') }}"
                                    >
                                        {{ __('Delete') }}
                                    </flux:menu.item>
                                </flux:menu>
                            </flux:dropdown>
                        </div>
                    </div>

                    {{-- Description --}}
                    @if ($warehouse->description)
                        <p class="line-clamp-2 text-sm text-zinc-500">{{ $warehouse->description }}</p>
                    @else
                        <p class="text-sm italic text-zinc-400">{{ __('No description') }}</p>
                    @endif

                    {{-- Stats --}}
                    <div class="flex items-center gap-4 border-t border-zinc-100 pt-4 dark:border-zinc-800">
                        <div class="flex items-center gap-1.5 text-sm">
                            <flux:icon.map-pin class="size-4 text-zinc-400" />
                            <span class="font-semibold text-zinc-800 dark:text-zinc-200">{{ $warehouse->locations_count }}</span>
                            <span class="text-zinc-400">{{ __('locations') }}</span>
                        </div>
                        <div class="flex items-center gap-1.5 text-sm">
                            <flux:icon.cube class="size-4 text-zinc-400" />
                            <span class="font-semibold text-zinc-800 dark:text-zinc-200">{{ $warehouse->total_stock }}</span>
                            <span class="text-zinc-400">{{ __('items') }}</span>
                        </div>
                    </div>

                    {{-- Clickable overlay (behind the dropdown) --}}
                    <a href="{{ route('warehouses.show', $warehouse) }}" wire:navigate class="absolute inset-0 rounded-xl"></a>
                </div>
            @endforeach
        </div>

        {{-- Pagination --}}
        <div>{{ $this->warehouses->links() }}</div>
    @endif

    {{-- Create / Edit Modal --}}
    <flux:modal wire:model="showModal" class="max-w-md">
        <div class="flex flex-col gap-6 p-6">
            <div>
                <flux:heading size="lg">{{ $editingId ? __('Edit Warehouse') : __('New Warehouse') }}</flux:heading>
                <flux:subheading>{{ $editingId ? __('Update warehouse details') : __('Add a new warehouse to your network') }}</flux:subheading>
            </div>

            <flux:field>
                <flux:label>{{ __('Name') }}</flux:label>
                <flux:input wire:model="name" placeholder="{{ __('e.g. Warehouse A') }}" />
                <flux:error name="name" />
            </flux:field>

            <flux:field>
                <flux:label>{{ __('Location') }}</flux:label>
                <flux:input wire:model="location" placeholder="{{ __('e.g. Brussels') }}" icon="map-pin" />
                <flux:error name="location" />
            </flux:field>

            <flux:field>
                <flux:label>{{ __('Description') }} <span class="text-zinc-400 text-xs font-normal">({{ __('optional') }})</span></flux:label>
                <flux:textarea wire:model="description" rows="3" placeholder="{{ __('Short description...') }}" />
                <flux:error name="description" />
            </flux:field>

            <div class="flex justify-end gap-3">
                <flux:button wire:click="$set('showModal', false)" variant="ghost">{{ __('Cancel') }}</flux:button>
                <flux:button wire:click="save" variant="primary">{{ $editingId ? __('Update') : __('Create') }}</flux:button>
            </div>
        </div>
    </flux:modal>

</div>
