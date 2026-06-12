<div class="flex h-full w-full flex-1 flex-col gap-6 p-4 sm:p-6">

    {{-- Header --}}
    <div class="flex flex-wrap items-center justify-between gap-y-3">
        <div>
            <flux:heading size="xl">{{ __('Stock Movements') }}</flux:heading>
            <flux:subheading>{{ __('Full history of all incoming, outgoing, transfer and correction events') }}</flux:subheading>
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
                placeholder="{{ __('Search by product...') }}"
            />
        </div>
        <div class="w-48">
            {{-- Type filter — native select for reliable Livewire binding --}}
            <select
                wire:model.live="filterType"
                class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 dark:border-white/10 dark:bg-white/5 dark:text-zinc-100 dark:focus:border-blue-400"
            >
                <option value="">{{ __('All types') }}</option>
                @foreach ($this->types as $type)
                    <option value="{{ $type->value }}">{{ ucfirst($type->value) }}</option>
                @endforeach
            </select>
        </div>
    </div>

    {{-- Table --}}
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
            @forelse ($this->movements as $movement)
                <flux:table.row :key="$movement->id">
                    <flux:table.cell variant="strong">
                        {{ $movement->product->name }}
                        <div class="text-xs font-mono font-normal text-zinc-400">{{ $movement->product->sku }}</div>
                    </flux:table.cell>

                    <flux:table.cell>
                        @php
                            [$variant, $icon] = match($movement->type->value) {
                                'incoming'   => ['success', 'arrow-down-tray'],
                                'outgoing'   => ['danger', 'arrow-up-tray'],
                                'transfer'   => ['warning', 'arrows-right-left'],
                                'correction' => ['outline', 'pencil-square'],
                                default      => ['outline', 'question-mark-circle'],
                            };
                        @endphp
                        <flux:badge variant="{{ $variant }}" icon="{{ $icon }}">{{ ucfirst($movement->type->value) }}</flux:badge>
                    </flux:table.cell>

                    <flux:table.cell class="text-sm">
                        @if ($movement->type->value === 'transfer')
                            <span class="flex items-center gap-1">
                                <span class="font-mono font-medium">{{ $movement->fromLocation?->code ?? '—' }}</span>
                                <flux:icon.arrow-right class="size-3 text-zinc-400" />
                                <span class="font-mono font-medium">{{ $movement->toLocation?->code ?? '—' }}</span>
                            </span>
                        @else
                            <span class="font-mono font-medium">{{ $movement->location?->code ?? '—' }}</span>
                        @endif
                    </flux:table.cell>

                    <flux:table.cell>
                        @php
                            $qtyClass = match(true) {
                                $movement->quantity > 0  => 'text-emerald-600 dark:text-emerald-400',
                                $movement->quantity < 0  => 'text-red-600 dark:text-red-400',
                                default                  => 'text-zinc-500',
                            };
                        @endphp
                        <span class="text-base font-bold {{ $qtyClass }}">
                            {{ $movement->quantity > 0 ? '+' : '' }}{{ $movement->quantity }}
                        </span>
                    </flux:table.cell>

                    <flux:table.cell class="text-sm text-zinc-500">
                        {{ $movement->reference ?? '—' }}
                    </flux:table.cell>

                    <flux:table.cell>
                        <div class="flex items-center gap-2">
                            <flux:avatar size="xs" :name="$movement->user->name" />
                            <span class="text-sm">{{ $movement->user->name }}</span>
                        </div>
                    </flux:table.cell>

                    <flux:table.cell class="text-sm text-zinc-400">
                        <span title="{{ $movement->created_at->format('d/m/Y H:i') }}">
                            {{ $movement->created_at->diffForHumans() }}
                        </span>
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="7" class="py-16 text-center">
                        <div class="flex flex-col items-center gap-2">
                            <flux:icon.arrow-path class="size-10 text-zinc-300" />
                            <span class="text-zinc-500">
                                {{ $search || $filterType ? __('No movements match your filters.') : __('No stock movements yet.') }}
                            </span>
                        </div>
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>

    <div>
        {{ $this->movements->links() }}
    </div>

</div>
