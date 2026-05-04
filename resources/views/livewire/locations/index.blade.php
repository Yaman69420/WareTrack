<div class="flex h-full w-full flex-1 flex-col gap-6 p-6">

    {{-- Header --}}
    <div class="flex items-center justify-between">
        <flux:heading size="xl">{{ __('Locations') }}</flux:heading>
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
            <flux:select wire:model.live="filterWarehouse" placeholder="{{ __('All warehouses') }}">
                <flux:select.option value="">{{ __('All warehouses') }}</flux:select.option>
                @foreach ($this->warehouses as $warehouse)
                    <flux:select.option value="{{ $warehouse->id }}">{{ $warehouse->name }}</flux:select.option>
                @endforeach
            </flux:select>
        </div>
    </div>

    {{-- Table --}}
    <flux:table>
        <flux:table.columns>
            <flux:table.column>{{ __('Code') }}</flux:table.column>
            <flux:table.column>{{ __('Name') }}</flux:table.column>
            <flux:table.column>{{ __('Warehouse') }}</flux:table.column>
            <flux:table.column>{{ __('Products') }}</flux:table.column>
            <flux:table.column>{{ __('Created') }}</flux:table.column>
            <flux:table.column></flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($this->locations as $location)
                <flux:table.row :key="$location->id">
                    <flux:table.cell variant="strong">
                        <flux:badge>{{ $location->code }}</flux:badge>
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
                    <flux:table.cell colspan="6" class="py-12 text-center">
                        {{ $search || $filterWarehouse ? __('No locations match your filters.') : __('No locations yet.') }}
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

            <flux:field>
                <flux:label>{{ __('Warehouse') }}</flux:label>
                <flux:select wire:model="warehouseId" placeholder="{{ __('Select a warehouse') }}">
                    @foreach ($this->warehouses as $warehouse)
                        <flux:select.option value="{{ $warehouse->id }}">{{ $warehouse->name }}</flux:select.option>
                    @endforeach
                </flux:select>
                <flux:error name="warehouseId" />
            </flux:field>

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
