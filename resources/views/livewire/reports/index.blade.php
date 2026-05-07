<div class="flex h-full w-full flex-1 flex-col gap-6 p-6">

    {{-- Header --}}
    <flux:heading size="xl">{{ __('Reports') }}</flux:heading>

    {{-- Tabs --}}
    <div class="flex gap-1 border-b border-zinc-200 dark:border-zinc-700">
        <button
            wire:click="setTab('low-stock')"
            class="px-4 py-2 text-sm font-medium transition {{ $tab === 'low-stock' ? 'border-b-2 border-zinc-900 text-zinc-900 dark:border-zinc-100 dark:text-zinc-100' : 'text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300' }}"
        >
            {{ __('Low Stock') }}
            @if($tab === 'low-stock' && $this->lowStockProducts->isNotEmpty())
                <flux:badge variant="danger" size="sm" class="ml-1">{{ $this->lowStockProducts->count() }}</flux:badge>
            @endif
        </button>
        <button
            wire:click="setTab('stock-per-location')"
            class="px-4 py-2 text-sm font-medium transition {{ $tab === 'stock-per-location' ? 'border-b-2 border-zinc-900 text-zinc-900 dark:border-zinc-100 dark:text-zinc-100' : 'text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300' }}"
        >
            {{ __('Stock per Location') }}
        </button>
        <button
            wire:click="setTab('movements')"
            class="px-4 py-2 text-sm font-medium transition {{ $tab === 'movements' ? 'border-b-2 border-zinc-900 text-zinc-900 dark:border-zinc-100 dark:text-zinc-100' : 'text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300' }}"
        >
            {{ __('Movements per Period') }}
        </button>
    </div>

    {{-- TAB: Low Stock --}}
    @if($tab === 'low-stock')
        @if($this->lowStockProducts->isEmpty())
            <div class="flex flex-1 flex-col items-center justify-center py-16 text-center">
                <flux:icon.check-circle class="mb-3 size-10 text-green-500" />
                <flux:heading>{{ __('All products are sufficiently stocked.') }}</flux:heading>
                <flux:text class="text-zinc-400">{{ __('No products are currently below their minimum stock level.') }}</flux:text>
            </div>
        @else
            <flux:table>
                <flux:table.columns>
                    <flux:table.column>{{ __('Product') }}</flux:table.column>
                    <flux:table.column>{{ __('Category') }}</flux:table.column>
                    <flux:table.column>{{ __('Current Stock') }}</flux:table.column>
                    <flux:table.column>{{ __('Minimum') }}</flux:table.column>
                    <flux:table.column>{{ __('Shortage') }}</flux:table.column>
                    <flux:table.column>{{ __('Locations') }}</flux:table.column>
                </flux:table.columns>
                <flux:table.rows>
                    @foreach($this->lowStockProducts as $product)
                        <flux:table.row :key="$product->id">
                            <flux:table.cell variant="strong">
                                {{ $product->name }}
                                <div class="text-xs font-normal text-zinc-400">{{ $product->sku }}</div>
                            </flux:table.cell>
                            <flux:table.cell>{{ $product->category?->name ?? '—' }}</flux:table.cell>
                            <flux:table.cell>
                                <span class="font-medium text-red-600 dark:text-red-400">{{ $product->totalStock() }}</span>
                            </flux:table.cell>
                            <flux:table.cell>{{ $product->min_stock }}</flux:table.cell>
                            <flux:table.cell>
                                <flux:badge variant="danger">{{ $product->totalStock() - $product->min_stock }}</flux:badge>
                            </flux:table.cell>
                            <flux:table.cell class="text-sm text-zinc-500">
                                {{ $product->stock->map(fn($s) => $s->location->code)->join(', ') ?: '—' }}
                            </flux:table.cell>
                        </flux:table.row>
                    @endforeach
                </flux:table.rows>
            </flux:table>
        @endif
    @endif

    {{-- TAB: Stock per Location --}}
    @if($tab === 'stock-per-location')
        <div class="flex items-center gap-3">
            <div class="w-56">
                <flux:select wire:model.live="filterWarehouse" placeholder="{{ __('All warehouses') }}">
                    <flux:select.option value="">{{ __('All warehouses') }}</flux:select.option>
                    @foreach($this->warehouses as $warehouse)
                        <flux:select.option value="{{ $warehouse->id }}">{{ $warehouse->name }}</flux:select.option>
                    @endforeach
                </flux:select>
            </div>
            <flux:text class="text-sm text-zinc-400">
                {{ $this->stockPerLocation->count() }} {{ __('lines') }}
            </flux:text>
        </div>

        @if($this->stockPerLocation->isEmpty())
            <flux:text class="py-12 text-center text-zinc-400">{{ __('No stock registered yet.') }}</flux:text>
        @else
            <flux:table>
                <flux:table.columns>
                    <flux:table.column>{{ __('Warehouse') }}</flux:table.column>
                    <flux:table.column>{{ __('Location') }}</flux:table.column>
                    <flux:table.column>{{ __('Product') }}</flux:table.column>
                    <flux:table.column>{{ __('Category') }}</flux:table.column>
                    <flux:table.column>{{ __('Quantity') }}</flux:table.column>
                </flux:table.columns>
                <flux:table.rows>
                    @foreach($this->stockPerLocation as $line)
                        <flux:table.row :key="$line->id">
                            <flux:table.cell>{{ $line->location->warehouse->name }}</flux:table.cell>
                            <flux:table.cell>
                                <flux:badge>{{ $line->location->code }}</flux:badge>
                            </flux:table.cell>
                            <flux:table.cell variant="strong">
                                {{ $line->product->name }}
                                <div class="text-xs font-normal text-zinc-400">{{ $line->product->sku }}</div>
                            </flux:table.cell>
                            <flux:table.cell>{{ $line->product->category?->name ?? '—' }}</flux:table.cell>
                            <flux:table.cell>
                                <span class="font-medium">{{ $line->quantity }}</span>
                            </flux:table.cell>
                        </flux:table.row>
                    @endforeach
                </flux:table.rows>
            </flux:table>
        @endif
    @endif

    {{-- TAB: Movements per Period --}}
    @if($tab === 'movements')
        <div class="flex flex-wrap items-center gap-3">
            <flux:field>
                <flux:label>{{ __('From') }}</flux:label>
                <flux:input wire:model.live="filterFrom" type="date" />
            </flux:field>
            <flux:field>
                <flux:label>{{ __('To') }}</flux:label>
                <flux:input wire:model.live="filterTo" type="date" />
            </flux:field>
            <flux:field>
                <flux:label>{{ __('Type') }}</flux:label>
                <flux:select wire:model.live="filterType" placeholder="{{ __('All types') }}" class="w-40">
                    <flux:select.option value="">{{ __('All types') }}</flux:select.option>
                    @foreach($this->types as $type)
                        <flux:select.option value="{{ $type->value }}">{{ ucfirst($type->value) }}</flux:select.option>
                    @endforeach
                </flux:select>
            </flux:field>
            <flux:text class="mt-5 text-sm text-zinc-400">
                {{ $this->movements->count() }} {{ __('movements') }}
            </flux:text>
        </div>

        @if($this->movements->isEmpty())
            <flux:text class="py-12 text-center text-zinc-400">{{ __('No movements found for this period.') }}</flux:text>
        @else
            <flux:table>
                <flux:table.columns>
                    <flux:table.column>{{ __('Product') }}</flux:table.column>
                    <flux:table.column>{{ __('Type') }}</flux:table.column>
                    <flux:table.column>{{ __('Location') }}</flux:table.column>
                    <flux:table.column>{{ __('Qty') }}</flux:table.column>
                    <flux:table.column>{{ __('Reference') }}</flux:table.column>
                    <flux:table.column>{{ __('By') }}</flux:table.column>
                    <flux:table.column>{{ __('Date') }}</flux:table.column>
                </flux:table.columns>
                <flux:table.rows>
                    @foreach($this->movements as $movement)
                        <flux:table.row :key="$movement->id">
                            <flux:table.cell variant="strong">
                                {{ $movement->product->name }}
                                <div class="text-xs font-normal text-zinc-400">{{ $movement->product->sku }}</div>
                            </flux:table.cell>
                            <flux:table.cell>
                                @php
                                    $v = match($movement->type->value) {
                                        'incoming' => 'success', 'outgoing' => 'danger',
                                        'transfer' => 'warning', default => 'outline',
                                    };
                                @endphp
                                <flux:badge variant="{{ $v }}">{{ ucfirst($movement->type->value) }}</flux:badge>
                            </flux:table.cell>
                            <flux:table.cell class="text-sm">
                                @if($movement->type->value === 'transfer')
                                    {{ $movement->fromLocation?->code }} → {{ $movement->toLocation?->code }}
                                @else
                                    {{ $movement->location?->code ?? '—' }}
                                @endif
                            </flux:table.cell>
                            <flux:table.cell>
                                <span class="{{ $movement->quantity > 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }} font-medium">
                                    {{ $movement->quantity > 0 ? '+' : '' }}{{ $movement->quantity }}
                                </span>
                            </flux:table.cell>
                            <flux:table.cell class="text-sm text-zinc-500">{{ $movement->reference ?? '—' }}</flux:table.cell>
                            <flux:table.cell class="text-sm">{{ $movement->user->name }}</flux:table.cell>
                            <flux:table.cell class="text-sm text-zinc-400">
                                {{ $movement->created_at->format('d/m/Y H:i') }}
                            </flux:table.cell>
                        </flux:table.row>
                    @endforeach
                </flux:table.rows>
            </flux:table>
        @endif
    @endif

</div>
