<div class="flex h-full w-full flex-1 flex-col gap-6 p-4 sm:p-6">

    {{-- Header --}}
    <div class="flex flex-wrap items-center justify-between gap-y-3">
        <div>
            <flux:heading size="xl">{{ __('Locations') }}</flux:heading>
            <flux:subheading>{{ __('Storage positions within your warehouses') }}</flux:subheading>
        </div>
        <flux:button wire:click="openCreate" variant="primary" icon="plus">
            {{ __('New Location') }}
        </flux:button>
    </div>

    {{-- Filters --}}
    <div class="flex flex-wrap gap-3">
        <div class="w-64">
            <flux:input
                wire:model.live.debounce.300ms="search"
                icon="magnifying-glass"
                placeholder="{{ __('Search by code or name...') }}"
            />
        </div>
        <div class="w-56">
            {{-- Warehouse filter — native select for reliable Livewire binding --}}
            <select
                wire:model.live="filterWarehouse"
                class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 dark:border-white/10 dark:bg-white/5 dark:text-zinc-100 dark:focus:border-blue-400"
            >
                <option value="">{{ __('All warehouses') }}</option>
                @foreach ($this->warehouses as $warehouse)
                    <option value="{{ $warehouse->id }}">{{ $warehouse->name }}</option>
                @endforeach
            </select>
        </div>
    </div>

    {{-- Table --}}
    <flux:table>
        <flux:table.columns>
            <flux:table.column sortable :sorted="$sortBy === 'code'" :direction="$sortDirection" wire:click="sort('code')">{{ __('Code') }}</flux:table.column>
            <flux:table.column>{{ __('Name') }}</flux:table.column>
            <flux:table.column>{{ __('Warehouse') }}</flux:table.column>
            <flux:table.column>{{ __('Products') }}</flux:table.column>
            <flux:table.column sortable :sorted="$sortBy === 'created_at'" :direction="$sortDirection" wire:click="sort('created_at')">{{ __('Created') }}</flux:table.column>
            <flux:table.column></flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($this->locations as $location)
                <flux:table.row :key="$location->id">
                    <flux:table.cell variant="strong">
                        <span class="rounded-md bg-zinc-100 px-2.5 py-1 font-mono text-sm font-semibold text-zinc-800 dark:bg-zinc-800 dark:text-zinc-200">{{ $location->code }}</span>
                    </flux:table.cell>

                    <flux:table.cell>
                        {{ $location->name ?? '—' }}
                    </flux:table.cell>

                    <flux:table.cell>
                        {{ $location->warehouse->name }}
                    </flux:table.cell>

                    <flux:table.cell>
                        <flux:badge variant="outline">{{ $location->products_count }}</flux:badge>
                    </flux:table.cell>

                    <flux:table.cell>
                        {{ $location->created_at->diffForHumans() }}
                    </flux:table.cell>

                    <flux:table.cell align="end">
                        <flux:dropdown>
                            <flux:button icon="ellipsis-horizontal" variant="ghost" size="sm" />
                            <flux:menu>
                                <flux:menu.item
                                    icon="pencil"
                                    wire:click="openEdit({{ $location->id }})"
                                >
                                    {{ __('Edit') }}
                                </flux:menu.item>
                                <flux:menu.separator />
                                <flux:menu.item
                                    icon="trash"
                                    variant="danger"
                                    wire:click="delete({{ $location->id }})"
                                    wire:confirm="{{ __('Delete this location?') }}"
                                >
                                    {{ __('Delete') }}
                                </flux:menu.item>
                            </flux:menu>
                        </flux:dropdown>
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="6" class="py-16 text-center">
                        <div class="flex flex-col items-center gap-2">
                            <flux:icon.map-pin class="size-10 text-zinc-300" />
                            <span class="text-zinc-500">{{ $search || $filterWarehouse ? __('No locations match your filters.') : __('No locations yet.') }}</span>
                        </div>
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>

    {{-- Pagination --}}
    <div>
        {{ $this->locations->links() }}
    </div>

    {{-- Create / Edit Modal --}}
    <flux:modal wire:model="showModal" class="max-w-md">
        <div class="flex flex-col gap-6 p-6">
            <flux:heading size="lg">
                {{ $editingId ? __('Edit Location') : __('New Location') }}
            </flux:heading>

            {{-- Warehouse — native select for reliable Livewire binding --}}
            <div class="flex flex-col gap-1">
                <flux:label>{{ __('Warehouse') }}</flux:label>
                <select
                    wire:model="warehouseId"
                    class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 dark:border-white/10 dark:bg-white/5 dark:text-zinc-100 dark:focus:border-blue-400"
                >
                    <option value="">{{ __('Select a warehouse') }}</option>
                    @foreach ($this->warehouses as $warehouse)
                        <option value="{{ $warehouse->id }}">{{ $warehouse->name }}</option>
                    @endforeach
                </select>
                <flux:error name="warehouseId" />
            </div>

            <flux:field>
                <flux:label>{{ __('Code') }}</flux:label>
                <flux:input wire:model="code" placeholder="{{ __('e.g. AA-01') }}" class="uppercase" />
                <flux:error name="code" />
            </flux:field>

            <flux:field>
                <flux:label>{{ __('Name') }} <span class="text-zinc-400 text-xs font-normal">({{ __('optional') }})</span></flux:label>
                <flux:input wire:model="name" placeholder="{{ __('e.g. Shelf A Row 1') }}" />
                <flux:error name="name" />
            </flux:field>

            <div class="flex justify-end gap-3">
                <flux:button wire:click="$set('showModal', false)" variant="ghost">
                    {{ __('Cancel') }}
                </flux:button>
                <flux:button wire:click="save" variant="primary">
                    {{ $editingId ? __('Update') : __('Create') }}
                </flux:button>
            </div>
        </div>
    </flux:modal>

</div>
