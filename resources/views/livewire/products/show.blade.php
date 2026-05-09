<div class="flex h-full w-full flex-1 flex-col gap-6 p-6">

    {{-- Back + header --}}
    <div class="flex items-start justify-between gap-4">
        <div class="flex items-center gap-4">
            <flux:button :href="route('products.index')" wire:navigate icon="arrow-left" variant="ghost" size="sm" />
            <div>
                <div class="flex items-center gap-3">
                    <flux:heading size="xl">{{ $product->name }}</flux:heading>
                    @if($this->isBelowMinStock)
                        <flux:badge variant="danger" icon="exclamation-triangle" size="sm">
                            {{ __('Low Stock') }}
                        </flux:badge>
                    @endif
                </div>
                <flux:subheading>
                    {{ $product->category?->name ?? __('No category') }} ·
                    <span class="font-mono tracking-tight">{{ $product->sku }}</span>
                </flux:subheading>
            </div>
        </div>

        <div class="flex shrink-0 gap-2">
            <flux:button
                :href="route('stock.movements.create')"
                wire:navigate
                icon="plus"
                variant="primary"
                size="sm"
            >
                {{ __('Register Movement') }}
            </flux:button>
        </div>
    </div>

    {{-- Top info row --}}
    <div class="grid gap-6 lg:grid-cols-3">

        {{-- Product card --}}
        <div class="flex flex-col gap-5 rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            {{-- Image --}}
            @if($this->imageUrl())
                <img
                    src="{{ $this->imageUrl() }}"
                    alt="{{ $product->name }}"
                    class="h-36 w-full rounded-lg object-cover"
                />
            @else
                <div class="flex h-36 w-full items-center justify-center rounded-lg bg-zinc-100 dark:bg-zinc-800">
                    <flux:icon.photo class="size-12 text-zinc-300 dark:text-zinc-600" />
                </div>
            @endif

            {{-- Details --}}
            <dl class="flex flex-col gap-2 text-sm">
                <div class="flex justify-between">
                    <dt class="text-zinc-500">{{ __('SKU') }}</dt>
                    <dd class="font-mono font-medium">{{ $product->sku }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-zinc-500">{{ __('Category') }}</dt>
                    <dd class="font-medium">{{ $product->category?->name ?? '—' }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-zinc-500">{{ __('Min. Stock') }}</dt>
                    <dd class="font-medium">{{ $product->min_stock }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-zinc-500">{{ __('Added') }}</dt>
                    <dd class="font-medium">{{ $product->created_at->format('d M Y') }}</dd>
                </div>
            </dl>

            @if($product->description)
                <p class="text-sm leading-relaxed text-zinc-500">{{ $product->description }}</p>
            @endif
        </div>

        {{-- Stock summary cards --}}
        <div class="lg:col-span-2 flex flex-col gap-4">

            {{-- Total stock + min stock --}}
            <div class="grid gap-4 sm:grid-cols-2">
                <div class="flex items-center gap-4 rounded-xl border
                    {{ $this->isBelowMinStock ? 'border-red-200 bg-red-50 dark:border-red-900/40 dark:bg-red-900/10' : 'border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900' }}
                    p-5">
                    <div class="rounded-xl {{ $this->isBelowMinStock ? 'bg-red-100 dark:bg-red-900/30' : 'bg-emerald-50 dark:bg-emerald-900/30' }} p-3">
                        <flux:icon.cube class="size-7 {{ $this->isBelowMinStock ? 'text-red-600 dark:text-red-400' : 'text-emerald-600 dark:text-emerald-400' }}" />
                    </div>
                    <div>
                        <p class="text-3xl font-bold {{ $this->isBelowMinStock ? 'text-red-700 dark:text-red-400' : 'text-zinc-900 dark:text-zinc-100' }}">
                            {{ number_format($this->totalStock) }}
                        </p>
                        <p class="text-sm {{ $this->isBelowMinStock ? 'text-red-500' : 'text-zinc-500' }}">{{ __('Total in stock') }}</p>
                    </div>
                </div>

                <div class="flex items-center gap-4 rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
                    <div class="rounded-xl bg-blue-50 p-3 dark:bg-blue-900/30">
                        <flux:icon.map-pin class="size-7 text-blue-600 dark:text-blue-400" />
                    </div>
                    <div>
                        <p class="text-3xl font-bold text-zinc-900 dark:text-zinc-100">
                            {{ $this->stockLines->count() }}
                        </p>
                        <p class="text-sm text-zinc-500">{{ __('Active locations') }}</p>
                    </div>
                </div>
            </div>

            {{-- Stock per location table --}}
            <div class="flex-1 rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
                <div class="flex items-center gap-2 border-b border-zinc-100 px-5 py-4 dark:border-zinc-800">
                    <flux:icon.map-pin class="size-4 text-zinc-400" />
                    <flux:heading>{{ __('Stock per Location') }}</flux:heading>
                </div>

                @if($this->stockLines->isEmpty())
                    <div class="flex flex-col items-center justify-center px-5 py-10 text-center">
                        <flux:icon.cube class="mb-2 size-8 text-zinc-300 dark:text-zinc-600" />
                        <p class="text-sm text-zinc-400">{{ __('No stock recorded for this product.') }}</p>
                    </div>
                @else
                    <div class="divide-y divide-zinc-100 dark:divide-zinc-800">
                        @foreach($this->stockLines as $stock)
                            @php $pct = $product->min_stock > 0 ? min(100, round($stock->quantity / $product->min_stock * 100)) : 100; @endphp
                            <div class="flex items-center gap-4 px-5 py-3">
                                <div class="min-w-0 flex-1">
                                    <p class="text-sm font-medium text-zinc-900 dark:text-zinc-100">
                                        {{ $stock->location->code }}
                                        @if($stock->location->name)
                                            <span class="text-zinc-400"> — {{ $stock->location->name }}</span>
                                        @endif
                                    </p>
                                    <p class="text-xs text-zinc-400">{{ $stock->location->warehouse->name }}</p>
                                </div>
                                <div class="w-32">
                                    <div class="h-1.5 overflow-hidden rounded-full bg-zinc-100 dark:bg-zinc-800">
                                        <div
                                            class="h-full rounded-full {{ $pct < 100 ? 'bg-red-400' : 'bg-emerald-400' }}"
                                            style="width: {{ $pct }}%"
                                        ></div>
                                    </div>
                                </div>
                                <div class="w-16 text-right">
                                    <span class="text-sm font-bold {{ $stock->quantity === 0 ? 'text-zinc-400' : 'text-zinc-900 dark:text-zinc-100' }}">
                                        {{ number_format($stock->quantity) }}
                                    </span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Movement history --}}
    <div class="rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
        <div class="flex items-center justify-between border-b border-zinc-100 px-6 py-4 dark:border-zinc-800">
            <div class="flex items-center gap-2">
                <flux:icon.clock class="size-4 text-zinc-400" />
                <flux:heading>{{ __('Recent Movements') }}</flux:heading>
            </div>
            <a href="{{ route('stock.movements') }}" wire:navigate
               class="text-xs text-zinc-400 hover:text-zinc-200 transition-colors">
                {{ __('View all') }} →
            </a>
        </div>

        @if($this->movements->isEmpty())
            <div class="flex flex-col items-center justify-center px-6 py-10 text-center">
                <flux:icon.clock class="mb-2 size-8 text-zinc-300 dark:text-zinc-600" />
                <p class="text-sm text-zinc-400">{{ __('No movements recorded yet.') }}</p>
            </div>
        @else
            <flux:table>
                <flux:table.columns>
                    <flux:table.column>{{ __('Type') }}</flux:table.column>
                    <flux:table.column>{{ __('Location') }}</flux:table.column>
                    <flux:table.column>{{ __('Qty') }}</flux:table.column>
                    <flux:table.column>{{ __('Reference') }}</flux:table.column>
                    <flux:table.column>{{ __('By') }}</flux:table.column>
                    <flux:table.column>{{ __('When') }}</flux:table.column>
                </flux:table.columns>
                <flux:table.rows>
                    @foreach($this->movements as $movement)
                        @php
                            $typeColors = [
                                'incoming'   => 'success',
                                'outgoing'   => 'danger',
                                'transfer'   => 'blue',
                                'correction' => 'warning',
                            ];
                            $typeColor = $typeColors[$movement->type->value] ?? 'zinc';
                        @endphp
                        <flux:table.row :key="$movement->id">
                            <flux:table.cell>
                                <flux:badge variant="{{ $typeColor }}" size="sm">
                                    {{ ucfirst($movement->type->value) }}
                                </flux:badge>
                            </flux:table.cell>
                            <flux:table.cell>
                                @if($movement->type->value === 'transfer')
                                    <span class="text-xs">
                                        {{ $movement->fromLocation?->code ?? '?' }}
                                        → {{ $movement->toLocation?->code ?? '?' }}
                                    </span>
                                @else
                                    <span class="text-xs">{{ $movement->location?->code ?? '—' }}</span>
                                @endif
                            </flux:table.cell>
                            <flux:table.cell>
                                <span class="font-mono font-semibold {{ $movement->quantity > 0 ? 'text-emerald-500' : ($movement->quantity < 0 ? 'text-red-500' : 'text-zinc-400') }}">
                                    {{ $movement->quantity > 0 ? '+' : '' }}{{ $movement->quantity }}
                                </span>
                            </flux:table.cell>
                            <flux:table.cell>
                                <span class="text-xs text-zinc-400">{{ $movement->reference ?? '—' }}</span>
                            </flux:table.cell>
                            <flux:table.cell>
                                <span class="text-xs">{{ $movement->user?->name ?? '—' }}</span>
                            </flux:table.cell>
                            <flux:table.cell>
                                <span class="text-xs text-zinc-400">{{ $movement->created_at->diffForHumans() }}</span>
                            </flux:table.cell>
                        </flux:table.row>
                    @endforeach
                </flux:table.rows>
            </flux:table>
        @endif
    </div>

</div>
