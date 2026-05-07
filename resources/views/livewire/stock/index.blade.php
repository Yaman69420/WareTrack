<div class="flex h-full w-full flex-1 flex-col gap-6 p-6">

    {{-- Header --}}
    <div class="flex items-center justify-between">
        <flux:heading size="xl">{{ __('Stock Overview') }}</flux:heading>
        <flux:button :href="route('stock.movements.create')" wire:navigate variant="primary" icon="plus">
            {{ __('Register Movement') }}
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
            <flux:table.column>{{ __('Product') }}</flux:table.column>
            <flux:table.column>{{ __('SKU') }}</flux:table.column>
            <flux:table.column>{{ __('Category') }}</flux:table.column>
            <flux:table.column>{{ __('Location') }}</flux:table.column>
            <flux:table.column>{{ __('Quantity') }}</flux:table.column>
            <flux:table.column>{{ __('Status') }}</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($this->stockLines as $product)
                @if ($product->stock->isEmpty())
                    <flux:table.row :key="'p-'.$product->id">
                        <flux:table.cell variant="strong">{{ $product->name }}</flux:table.cell>
                        <flux:table.cell><flux:badge>{{ $product->sku }}</flux:badge></flux:table.cell>
                        <flux:table.cell>{{ $product->category?->name ?? '—' }}</flux:table.cell>
                        <flux:table.cell class="text-zinc-400">{{ __('No stock registered') }}</flux:table.cell>
                        <flux:table.cell>0</flux:table.cell>
                        <flux:table.cell>
                            @if($product->min_stock > 0)
                                <flux:badge variant="danger">{{ __('Below minimum') }}</flux:badge>
                            @endif
                        </flux:table.cell>
                    </flux:table.row>
                @else
                    @foreach ($product->stock as $stockLine)
                        <flux:table.row :key="'s-'.$stockLine->id">
                            <flux:table.cell variant="strong">{{ $product->name }}</flux:table.cell>
                            <flux:table.cell><flux:badge>{{ $product->sku }}</flux:badge></flux:table.cell>
                            <flux:table.cell>{{ $product->category?->name ?? '—' }}</flux:table.cell>
                            <flux:table.cell>
                                <span class="text-sm">
                                    {{ $stockLine->location->warehouse->name }} —
                                    <span class="font-medium">{{ $stockLine->location->code }}</span>
                                </span>
                            </flux:table.cell>
                            <flux:table.cell>
                                <span class="font-medium">{{ $stockLine->quantity }}</span>
                            </flux:table.cell>
                            <flux:table.cell>
                                @if ($product->min_stock > 0 && $product->totalStock() < $product->min_stock)
                                    <flux:badge variant="danger">{{ __('Below minimum') }}</flux:badge>
                                @else
                                    <flux:badge variant="success">{{ __('OK') }}</flux:badge>
                                @endif
                            </flux:table.cell>
                        </flux:table.row>
                    @endforeach
                @endif
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="6" class="py-12 text-center">
                        {{ $search || $filterWarehouse ? __('No products match your filters.') : __('No products found.') }}
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>

    <div>
        {{ $this->stockLines->links() }}
    </div>

</div>
