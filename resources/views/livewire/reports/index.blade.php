<div class="flex h-full w-full flex-1 flex-col gap-6 p-4 sm:p-6">

    {{-- Header --}}
    <div>
        <flux:heading size="xl">{{ __('Reports') }}</flux:heading>
        <flux:subheading>{{ __('Insights into stock levels, locations and movement history') }}</flux:subheading>
    </div>

    {{-- Tabs --}}
    <div class="flex gap-1 border-b border-white/[.08]">
        <button
            wire:click="setTab('low-stock')"
            class="flex items-center gap-1.5 px-4 py-2.5 text-sm font-medium transition {{ $tab === 'low-stock' ? 'border-b-2 border-blue-600 text-blue-600 dark:border-blue-400 dark:text-blue-400' : 'text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300' }}"
        >
            <flux:icon.exclamation-triangle class="size-4" />
            {{ __('Low Stock') }}
            @if($this->lowStockProducts->isNotEmpty())
                <flux:badge variant="danger" size="sm">{{ $this->lowStockProducts->count() }}</flux:badge>
            @endif
        </button>
        <button
            wire:click="setTab('stock-per-location')"
            class="flex items-center gap-1.5 px-4 py-2.5 text-sm font-medium transition {{ $tab === 'stock-per-location' ? 'border-b-2 border-blue-600 text-blue-600 dark:border-blue-400 dark:text-blue-400' : 'text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300' }}"
        >
            <flux:icon.map-pin class="size-4" />
            {{ __('Stock per Location') }}
        </button>
        <button
            wire:click="setTab('movements')"
            class="flex items-center gap-1.5 px-4 py-2.5 text-sm font-medium transition {{ $tab === 'movements' ? 'border-b-2 border-blue-600 text-blue-600 dark:border-blue-400 dark:text-blue-400' : 'text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300' }}"
        >
            <flux:icon.arrow-path class="size-4" />
            {{ __('Movements per Period') }}
        </button>
    </div>

    {{-- TAB: Low Stock --}}
    @if($tab === 'low-stock')
        @if($this->lowStockProducts->isEmpty())
            <div class="flex flex-1 flex-col items-center justify-center py-20 text-center">
                <div class="mb-3 rounded-full bg-green-50 p-4 dark:bg-green-900/20">
                    <flux:icon.check-circle class="size-10 text-green-500" />
                </div>
                <flux:heading>{{ __('All products are sufficiently stocked.') }}</flux:heading>
                <flux:text class="text-zinc-400">{{ __('No products are currently below their minimum stock level.') }}</flux:text>
            </div>
        @else
            <div class="flex flex-col gap-3">
                @foreach($this->lowStockProducts as $product)
                    <div class="flex items-center justify-between rounded-xl border border-red-200 bg-red-50 px-5 py-4 dark:border-red-900/40 dark:bg-red-900/10">
                        <div class="flex items-center gap-4">
                            <div class="rounded-lg bg-red-100 p-2 dark:bg-red-900/30">
                                <flux:icon.exclamation-triangle class="size-5 text-red-600 dark:text-red-400" />
                            </div>
                            <div>
                                <p class="font-semibold text-zinc-900 dark:text-zinc-100">{{ $product->name }}</p>
                                <p class="text-xs text-zinc-500">{{ $product->category?->name ?? '—' }} · <span class="font-mono">{{ $product->sku }}</span></p>
                                @if($product->stock->isNotEmpty())
                                    <p class="mt-0.5 text-xs text-zinc-400">
                                        {{ __('Locations:') }} {{ $product->stock->map(fn($s) => $s->location->code)->join(', ') }}
                                    </p>
                                @endif
                            </div>
                        </div>
                        <div class="text-right">
                            <p class="text-2xl font-bold text-red-600 dark:text-red-400">{{ $product->totalStock() }}<span class="text-sm font-normal text-zinc-400"> / {{ $product->min_stock }}</span></p>
                            <p class="text-xs text-zinc-500">{{ __('stock / minimum') }}</p>
                            <flux:badge variant="danger" class="mt-1">{{ __('shortage:') }} {{ $product->totalStock() - $product->min_stock }}</flux:badge>
                        </div>
                    </div>
                @endforeach
            </div>
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
            <span class="text-sm text-zinc-400">{{ $this->stockPerLocation->count() }} {{ __('lines') }}</span>
            <flux:button
                wire:click="exportStockPerLocation"
                icon="arrow-down-tray"
                variant="ghost"
                size="sm"
                class="ml-auto"
            >
                {{ __('Export CSV') }}
            </flux:button>
        </div>

        @if($this->stockPerLocation->isEmpty())
            <div class="flex flex-col items-center justify-center py-16 text-center">
                <flux:icon.cube class="mb-2 size-10 text-zinc-300" />
                <flux:text class="text-zinc-400">{{ __('No stock registered yet.') }}</flux:text>
            </div>
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
                            <flux:table.cell>
                                <div class="flex items-center gap-1.5 text-sm">
                                    <flux:icon.building-office class="size-4 text-zinc-400" />
                                    {{ $line->location->warehouse->name }}
                                </div>
                            </flux:table.cell>
                            <flux:table.cell>
                                <span class="rounded bg-zinc-100 px-2 py-0.5 font-mono text-xs font-semibold dark:bg-zinc-800">{{ $line->location->code }}</span>
                            </flux:table.cell>
                            <flux:table.cell variant="strong">
                                {{ $line->product->name }}
                                <div class="font-mono text-xs font-normal text-zinc-400">{{ $line->product->sku }}</div>
                            </flux:table.cell>
                            <flux:table.cell>{{ $line->product->category?->name ?? '—' }}</flux:table.cell>
                            <flux:table.cell>
                                <span class="text-lg font-bold text-zinc-900 dark:text-zinc-100">{{ $line->quantity }}</span>
                            </flux:table.cell>
                        </flux:table.row>
                    @endforeach
                </flux:table.rows>
            </flux:table>
        @endif
    @endif

    {{-- TAB: Movements per Period --}}
    @if($tab === 'movements')
        <div class="flex flex-wrap items-end gap-3">
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
            <p class="mb-2 text-sm text-zinc-400">{{ $this->movements->count() }} {{ __('movements') }}</p>
            <flux:button
                wire:click="exportMovements"
                icon="arrow-down-tray"
                variant="ghost"
                size="sm"
                class="mb-2 ml-auto"
            >
                {{ __('Export CSV') }}
            </flux:button>
        </div>

        @if($this->movements->isEmpty())
            <div class="flex flex-col items-center justify-center py-16 text-center">
                <flux:icon.arrow-path class="mb-2 size-10 text-zinc-300" />
                <flux:text class="text-zinc-400">{{ __('No movements found for this period.') }}</flux:text>
            </div>
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
                                <div class="font-mono text-xs font-normal text-zinc-400">{{ $movement->product->sku }}</div>
                            </flux:table.cell>
                            <flux:table.cell>
                                @php
                                    [$v, $ico] = match($movement->type->value) {
                                        'incoming'   => ['success', 'arrow-down-tray'],
                                        'outgoing'   => ['danger', 'arrow-up-tray'],
                                        'transfer'   => ['warning', 'arrows-right-left'],
                                        default      => ['outline', 'pencil-square'],
                                    };
                                @endphp
                                <flux:badge variant="{{ $v }}" icon="{{ $ico }}">{{ ucfirst($movement->type->value) }}</flux:badge>
                            </flux:table.cell>
                            <flux:table.cell class="font-mono text-sm">
                                @if($movement->type->value === 'transfer')
                                    {{ $movement->fromLocation?->code }} → {{ $movement->toLocation?->code }}
                                @else
                                    {{ $movement->location?->code ?? '—' }}
                                @endif
                            </flux:table.cell>
                            <flux:table.cell>
                                <span class="font-bold {{ $movement->quantity > 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400' }}">
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
