<div class="flex h-full w-full flex-1 flex-col gap-6 p-6">

    {{-- Header --}}
    <div class="flex items-center justify-between">
        <flux:heading size="xl">{{ __('Products') }}</flux:heading>
        <flux:button wire:click="openCreate" variant="primary" icon="plus">
            {{ __('New Product') }}
        </flux:button>
    </div>

    {{-- Filters --}}
    <div class="flex flex-wrap gap-3">
        <div class="w-64">
            <flux:input
                wire:model.live.debounce.300ms="search"
                icon="magnifying-glass"
                placeholder="{{ __('Search by name or SKU...') }}"
            />
        </div>
        <div class="w-56">
            <flux:select wire:model.live="filterCategory" placeholder="{{ __('All categories') }}">
                <flux:select.option value="">{{ __('All categories') }}</flux:select.option>
                @foreach ($this->categories as $category)
                    <flux:select.option value="{{ $category->id }}">{{ $category->name }}</flux:select.option>
                @endforeach
            </flux:select>
        </div>
    </div>

    {{-- Table --}}
    <flux:table>
        <flux:table.columns>
            <flux:table.column>{{ __('Name') }}</flux:table.column>
            <flux:table.column>{{ __('SKU') }}</flux:table.column>
            <flux:table.column>{{ __('Category') }}</flux:table.column>
            <flux:table.column>{{ __('Min. Stock') }}</flux:table.column>
            <flux:table.column>{{ __('Created') }}</flux:table.column>
            <flux:table.column></flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($this->products as $product)
                <flux:table.row :key="$product->id">
                    <flux:table.cell variant="strong">
                        {{ $product->name }}
                    </flux:table.cell>

                    <flux:table.cell>
                        <flux:badge>{{ $product->sku }}</flux:badge>
                    </flux:table.cell>

                    <flux:table.cell>
                        {{ $product->category->name }}
                    </flux:table.cell>

                    <flux:table.cell>
                        {{ $product->min_stock }}
                    </flux:table.cell>

                    <flux:table.cell>
                        {{ $product->created_at->diffForHumans() }}
                    </flux:table.cell>

                    <flux:table.cell align="end">
                        <flux:dropdown>
                            <flux:button icon="ellipsis-horizontal" variant="ghost" size="sm" />
                            <flux:menu>
                                <flux:menu.item
                                    icon="pencil"
                                    wire:click="openEdit({{ $product->id }})"
                                >
                                    {{ __('Edit') }}
                                </flux:menu.item>
                                <flux:menu.item
                                    icon="map-pin"
                                    wire:click="openLocations({{ $product->id }})"
                                >
                                    {{ __('Manage Locations') }}
                                </flux:menu.item>
                                <flux:menu.separator />
                                <flux:menu.item
                                    icon="trash"
                                    variant="danger"
                                    wire:click="delete({{ $product->id }})"
                                    wire:confirm="{{ __('Delete this product?') }}"
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
                        {{ $search || $filterCategory ? __('No products match your filters.') : __('No products yet.') }}
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>

    {{-- Pagination --}}
    <div>
        {{ $this->products->links() }}
    </div>

    {{-- Create / Edit Modal --}}
    <flux:modal wire:model="showModal" class="max-w-lg">
        <div class="flex flex-col gap-6 p-6">
            <flux:heading size="lg">
                {{ $editingId ? __('Edit Product') : __('New Product') }}
            </flux:heading>

            <div class="grid grid-cols-2 gap-4">
                <flux:field class="col-span-2">
                    <flux:label>{{ __('Name') }}</flux:label>
                    <flux:input wire:model="name" placeholder="{{ __('e.g. Wireless Mouse') }}" />
                    <flux:error name="name" />
                </flux:field>

                <flux:field>
                    <flux:label>{{ __('SKU') }}</flux:label>
                    <flux:input wire:model="sku" placeholder="{{ __('e.g. WM-1234') }}" class="uppercase" />
                    <flux:error name="sku" />
                </flux:field>

                <flux:field>
                    <flux:label>{{ __('Category') }}</flux:label>
                    <flux:select wire:model="categoryId" placeholder="{{ __('Select category') }}">
                        @foreach ($this->categories as $category)
                            <flux:select.option value="{{ $category->id }}">{{ $category->name }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:error name="categoryId" />
                </flux:field>

                <flux:field>
                    <flux:label>{{ __('Min. Stock') }}</flux:label>
                    <flux:input wire:model="minStock" type="number" min="0" placeholder="0" />
                    <flux:error name="minStock" />
                </flux:field>

                <flux:field class="col-span-2">
                    <flux:label>{{ __('Description') }} <span class="text-zinc-400 text-xs font-normal">({{ __('optional') }})</span></flux:label>
                    <flux:textarea wire:model="description" rows="3" placeholder="{{ __('Short description...') }}" />
                    <flux:error name="description" />
                </flux:field>
            </div>

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

    {{-- Manage Locations Modal --}}
    <flux:modal wire:model="showLocationsModal" class="max-w-lg">
        <div class="flex flex-col gap-6 p-6">
            <div>
                <flux:heading size="lg">{{ __('Manage Locations') }}</flux:heading>
                @if($this->managingProduct)
                    <flux:subheading>{{ $this->managingProduct->name }} ({{ $this->managingProduct->sku }})</flux:subheading>
                @endif
            </div>

            @if($this->warehousesWithLocations->isEmpty())
                <flux:text class="text-zinc-500">{{ __('No warehouses or locations available. Create them first.') }}</flux:text>
            @else
                <div class="flex flex-col gap-4 max-h-80 overflow-y-auto">
                    @foreach($this->warehousesWithLocations as $warehouse)
                        @if($warehouse->locations->isNotEmpty())
                            <div>
                                <flux:text class="mb-2 text-xs font-semibold uppercase tracking-wide text-zinc-500">
                                    {{ $warehouse->name }} — {{ $warehouse->location }}
                                </flux:text>
                                <div class="flex flex-col gap-1">
                                    @foreach($warehouse->locations as $location)
                                        <label class="flex cursor-pointer items-center gap-3 rounded-lg px-3 py-2 hover:bg-zinc-50 dark:hover:bg-zinc-800">
                                            <flux:checkbox
                                                wire:model="selectedLocations"
                                                value="{{ $location->id }}"
                                            />
                                            <span class="text-sm">
                                                <span class="font-medium">{{ $location->code }}</span>
                                                @if($location->name)
                                                    <span class="text-zinc-400"> — {{ $location->name }}</span>
                                                @endif
                                            </span>
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    @endforeach
                </div>
            @endif

            <div class="flex items-center justify-between">
                <flux:text class="text-sm text-zinc-400">
                    {{ count($selectedLocations) }} {{ __('selected') }}
                </flux:text>
                <div class="flex gap-3">
                    <flux:button wire:click="$set('showLocationsModal', false)" variant="ghost">
                        {{ __('Cancel') }}
                    </flux:button>
                    <flux:button wire:click="saveLocations" variant="primary">
                        {{ __('Save') }}
                    </flux:button>
                </div>
            </div>
        </div>
    </flux:modal>

</div>
