<div class="flex h-full w-full flex-1 flex-col gap-6 p-4 sm:p-6">

    {{-- Header --}}
    <div class="flex flex-wrap items-center justify-between gap-y-3">
        <div>
            <flux:heading size="xl">{{ __('Activity Log') }}</flux:heading>
            <flux:subheading>{{ __('Full audit trail of all stock movements across all users and locations') }}</flux:subheading>
        </div>
    </div>

    {{-- Stat cards --}}
    <div class="grid grid-cols-2 gap-4 sm:grid-cols-5">
        @php
            $statCards = [
                ['label' => 'Total',       'value' => $this->stats['total'],       'color' => 'text-zinc-100',    'bg' => 'bg-white/[.04]'],
                ['label' => 'Incoming',    'value' => $this->stats['incoming'],    'color' => 'text-emerald-400', 'bg' => 'bg-emerald-500/[.08]'],
                ['label' => 'Outgoing',    'value' => $this->stats['outgoing'],    'color' => 'text-red-400',     'bg' => 'bg-red-500/[.08]'],
                ['label' => 'Transfers',   'value' => $this->stats['transfers'],   'color' => 'text-amber-400',   'bg' => 'bg-amber-500/[.08]'],
                ['label' => 'Corrections', 'value' => $this->stats['corrections'], 'color' => 'text-blue-400',    'bg' => 'bg-blue-500/[.08]'],
            ];
        @endphp

        @foreach ($statCards as $card)
            <div class="{{ $card['bg'] }} rounded-xl border border-white/[.08] px-4 py-3">
                <div class="text-xs font-medium text-zinc-500 uppercase tracking-wide">{{ $card['label'] }}</div>
                <div class="mt-1 text-2xl font-bold {{ $card['color'] }}">{{ number_format($card['value']) }}</div>
            </div>
        @endforeach
    </div>

    {{-- Filters --}}
    <div class="flex flex-wrap items-end gap-3">
        <div class="w-full sm:w-64">
            <flux:input
                wire:model.live.debounce.300ms="search"
                icon="magnifying-glass"
                placeholder="{{ __('Search product, SKU, reference…') }}"
            />
        </div>

        <div class="w-44">
            <flux:select wire:model.live="type" placeholder="{{ __('All types') }}">
                <flux:select.option value="">{{ __('All types') }}</flux:select.option>
                @foreach ($this->types as $t)
                    <flux:select.option value="{{ $t->value }}">{{ ucfirst($t->value) }}</flux:select.option>
                @endforeach
            </flux:select>
        </div>

        <div class="w-44">
            <flux:select wire:model.live="user" placeholder="{{ __('All users') }}">
                <flux:select.option value="">{{ __('All users') }}</flux:select.option>
                @foreach ($this->users as $u)
                    <flux:select.option value="{{ $u->id }}">{{ $u->name }}</flux:select.option>
                @endforeach
            </flux:select>
        </div>

        <div class="flex items-center gap-2">
            <flux:input wire:model.live="from" type="date" class="w-36" />
            <span class="text-xs text-zinc-500">→</span>
            <flux:input wire:model.live="to" type="date" class="w-36" />
        </div>

        @if ($search || $type || $user || $from || $to)
            <flux:button wire:click="clearFilters" variant="ghost" size="sm" icon="x-mark">
                {{ __('Clear') }}
            </flux:button>
        @endif
    </div>

    {{-- Table --}}
    <flux:table>
        <flux:table.columns>
            <flux:table.column>{{ __('Product') }}</flux:table.column>
            <flux:table.column>{{ __('Type') }}</flux:table.column>
            <flux:table.column>{{ __('Location') }}</flux:table.column>
            <flux:table.column>{{ __('Qty') }}</flux:table.column>
            <flux:table.column>{{ __('Reference') }}</flux:table.column>
            <flux:table.column>{{ __('User') }}</flux:table.column>
            <flux:table.column>{{ __('When') }}</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($this->movements as $movement)
                <flux:table.row :key="$movement->id">

                    {{-- Product --}}
                    <flux:table.cell variant="strong">
                        <a href="{{ route('products.show', $movement->product) }}" wire:navigate
                           class="hover:text-blue-400 transition-colors">
                            {{ $movement->product->name }}
                        </a>
                        <div class="font-mono text-xs font-normal text-zinc-500">{{ $movement->product->sku }}</div>
                    </flux:table.cell>

                    {{-- Type badge --}}
                    <flux:table.cell>
                        @php
                            [$variant, $icon] = match ($movement->type->value) {
                                'incoming'   => ['success', 'arrow-down-tray'],
                                'outgoing'   => ['danger', 'arrow-up-tray'],
                                'transfer'   => ['warning', 'arrows-right-left'],
                                'correction' => ['outline', 'pencil-square'],
                                default      => ['outline', 'question-mark-circle'],
                            };
                        @endphp
                        <flux:badge :variant="$variant" :icon="$icon">
                            {{ ucfirst($movement->type->value) }}
                        </flux:badge>
                    </flux:table.cell>

                    {{-- Location --}}
                    <flux:table.cell class="text-sm text-zinc-400">
                        @if ($movement->type === \App\Enums\StockMovementType::Transfer)
                            <div class="flex items-center gap-1">
                                <span class="font-mono text-zinc-300">{{ $movement->fromLocation?->code ?? '?' }}</span>
                                <flux:icon.arrow-right class="size-3 text-zinc-600" />
                                <span class="font-mono text-zinc-300">{{ $movement->toLocation?->code ?? '?' }}</span>
                            </div>
                        @else
                            <span class="font-mono text-zinc-300">{{ $movement->location?->code ?? '—' }}</span>
                        @endif
                    </flux:table.cell>

                    {{-- Quantity --}}
                    <flux:table.cell>
                        <span class="font-bold tabular-nums {{ $movement->quantity >= 0 ? 'text-emerald-400' : 'text-red-400' }}">
                            {{ $movement->quantity >= 0 ? '+' : '' }}{{ $movement->quantity }}
                        </span>
                    </flux:table.cell>

                    {{-- Reference --}}
                    <flux:table.cell class="font-mono text-xs text-zinc-400">
                        {{ $movement->reference ?? '—' }}
                    </flux:table.cell>

                    {{-- User --}}
                    <flux:table.cell>
                        <div class="flex items-center gap-2">
                            <flux:avatar size="xs" :name="$movement->user?->name ?? '?'" :initials="$movement->user?->initials() ?? '?'" />
                            <span class="text-sm text-zinc-300">{{ $movement->user?->name ?? '—' }}</span>
                        </div>
                    </flux:table.cell>

                    {{-- Timestamp --}}
                    <flux:table.cell class="text-sm text-zinc-400">
                        <span title="{{ $movement->created_at->format('d/m/Y H:i:s') }}">
                            {{ $movement->created_at->diffForHumans() }}
                        </span>
                    </flux:table.cell>

                </flux:table.row>

            @empty
                <flux:table.row>
                    <flux:table.cell colspan="7" class="py-16 text-center text-zinc-500">
                        <flux:icon.clipboard-document-list class="mx-auto mb-2 size-8 opacity-30" />
                        {{ __('No activity found.') }}
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>

    {{-- Pagination --}}
    <div>
        {{ $this->movements->links() }}
    </div>

</div>
