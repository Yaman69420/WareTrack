<div class="flex h-full w-full flex-1 flex-col gap-6 p-6">

    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl">{{ __('Stock Overview') }}</flux:heading>
            <flux:subheading>{{ __('Current stock levels per product and location') }}</flux:subheading>
        </div>
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
            <flux:table.column></flux:table.column>
            <flux:table.column>{{ __('Product') }}</flux:table.column>
            <flux:table.column>{{ __('SKU') }}</flux:table.column>
            <flux:table.column>{{ __('Category') }}</flux:table.column>
            <flux:table.column>{{ __('Location') }}</flux:table.column>
            <flux:table.column>{{ __('Quantity') }}</flux:table.column>
            <flux:table.column>{{ __('Status') }}</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($this->stockLines as $product)
                @php
                    $totalQty   = $product->stock->sum('quantity');
                    $belowMin   = $product->min_stock > 0 && $totalQty < $product->min_stock;
                    $rowClass   = $belowMin ? 'bg-red-50/60 dark:bg-red-900/5' : '';
                @endphp

                @if ($product->stock->isEmpty())
                    <flux:table.row :key="'p-'.$product->id" class="{{ $rowClass }}">
                        {{-- Image --}}
                        <flux:table.cell>
                            @if ($product->image_path)
                                <img src="{{ Storage::url($product->image_path) }}" alt="{{ $product->name }}" class="size-9 rounded-lg object-cover" />
                            @else
                                <div class="flex size-9 items-center justify-center rounded-lg bg-zinc-100 dark:bg-zinc-800">
                                    <flux:icon.photo class="size-4 text-zinc-400" />
                                </div>
                            @endif
                        </flux:table.cell>
                        <flux:table.cell variant="strong">{{ $product->name }}</flux:table.cell>
                        <flux:table.cell><flux:badge>{{ $product->sku }}</flux:badge></flux:table.cell>
                        <flux:table.cell>{{ $product->category?->name ?? '—' }}</flux:table.cell>
                        <flux:table.cell class="text-zinc-400 italic">{{ __('No stock registered') }}</flux:table.cell>
                        <flux:table.cell>
                            <span class="font-bold text-zinc-400">0</span>
                        </flux:table.cell>
                        <flux:table.cell>
                            @if($product->min_stock > 0)
                                <flux:badge variant="danger" icon="exclamation-triangle">{{ __('Below minimum') }}</flux:badge>
                            @else
                                <flux:badge variant="outline">{{ __('No minimum') }}</flux:badge>
                            @endif
                        </flux:table.cell>
                    </flux:table.row>
                @else
                    @foreach ($product->stock as $i => $stockLine)
                        <flux:table.row :key="'s-'.$stockLine->id" class="{{ $rowClass }}">
                            {{-- Image only on first row --}}
                            <flux:table.cell>
                                @if ($i === 0)
                                    @if ($product->image_path)
                                        <img src="{{ Storage::url($product->image_path) }}" alt="{{ $product->name }}" class="size-9 rounded-lg object-cover" />
                                    @else
                                        <div class="flex size-9 items-center justify-center rounded-lg bg-zinc-100 dark:bg-zinc-800">
                                            <flux:icon.photo class="size-4 text-zinc-400" />
                                        </div>
                                    @endif
                                @endif
                            </flux:table.cell>

                            <flux:table.cell variant="strong">
                                @if ($i === 0)
                                    {{ $product->name }}
                                    @if ($product->stock->count() > 1)
                                        <span class="ml-1 text-xs font-normal text-zinc-400">({{ $product->stock->count() }} {{ __('locations') }})</span>
                                    @endif
                                @endif
                            </flux:table.cell>

                            <flux:table.cell>
                                @if ($i === 0)
                                    <flux:badge>{{ $product->sku }}</flux:badge>
                                @endif
                            </flux:table.cell>

                            <flux:table.cell>
                                @if ($i === 0)
                                    {{ $product->category?->name ?? '—' }}
                                @endif
                            </flux:table.cell>

                            <flux:table.cell>
                                <div class="flex items-center gap-1.5 text-sm">
                                    <flux:icon.building-office class="size-3.5 text-zinc-400" />
                                    <span class="text-zinc-500">{{ $stockLine->location->warehouse->name }}</span>
                                    <span class="text-zinc-300 dark:text-zinc-600">·</span>
                                    <span class="font-medium font-mono">{{ $stockLine->location->code }}</span>
                                </div>
                            </flux:table.cell>

                            <flux:table.cell>
                                <span class="text-lg font-bold {{ $belowMin && $i === 0 ? 'text-red-600 dark:text-red-400' : 'text-zinc-900 dark:text-zinc-100' }}">
                                    {{ $stockLine->quantity }}
                                </span>
                            </flux:table.cell>

                            <flux:table.cell>
                                @if ($i === 0)
                                    @if ($belowMin)
                                        <flux:badge variant="danger" icon="exclamation-triangle">{{ __('Below minimum') }}</flux:badge>
                                    @else
                                        <flux:badge variant="success" icon="check">{{ __('OK') }}</flux:badge>
                                    @endif
                                @endif
                            </flux:table.cell>
                        </flux:table.row>
                    @endforeach
                @endif
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="7" class="py-16 text-center">
                        <div class="flex flex-col items-center gap-2">
                            <flux:icon.cube class="size-10 text-zinc-300" />
                            <span class="text-zinc-500">
                                {{ $search || $filterWarehouse ? __('No products match your filters.') : __('No products found.') }}
                            </span>
                        </div>
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>

    <div>
        {{ $this->stockLines->links() }}
    </div>

</div>
