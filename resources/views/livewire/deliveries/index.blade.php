<div class="flex h-full w-full flex-1 flex-col gap-6 p-4 sm:p-6">

    {{-- Header --}}
    <div class="flex flex-wrap items-center justify-between gap-y-3">
        <div>
            <flux:heading size="xl">{{ __('Deliveries') }}</flux:heading>
            <flux:subheading>{{ __('Incoming deliveries and their processing status') }}</flux:subheading>
        </div>
        @if(auth()->user()->isAdmin())
            <flux:button :href="route('deliveries.create')" wire:navigate variant="primary" icon="plus">
                {{ __('New Delivery') }}
            </flux:button>
        @endif
    </div>

    {{-- Filter --}}
    <div class="w-48">
        {{-- Status filter — native select for reliable Livewire binding --}}
        <select
            wire:model.live="filterStatus"
            class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 dark:border-white/10 dark:bg-white/5 dark:text-zinc-100 dark:focus:border-blue-400"
        >
            <option value="">{{ __('All statuses') }}</option>
            @foreach ($this->statuses as $status)
                <option value="{{ $status->value }}">{{ ucfirst($status->value) }}</option>
            @endforeach
        </select>
    </div>

    {{-- Table --}}
    <flux:table>
        <flux:table.columns>
            <flux:table.column sortable :sorted="$sortBy === 'reference'" :direction="$sortDirection" wire:click="sort('reference')">{{ __('Reference') }}</flux:table.column>
            <flux:table.column>{{ __('Supplier') }}</flux:table.column>
            <flux:table.column sortable :sorted="$sortBy === 'status'" :direction="$sortDirection" wire:click="sort('status')">{{ __('Status') }}</flux:table.column>
            <flux:table.column>{{ __('Items') }}</flux:table.column>
            <flux:table.column>{{ __('Created by') }}</flux:table.column>
            <flux:table.column sortable :sorted="$sortBy === 'created_at'" :direction="$sortDirection" wire:click="sort('created_at')">{{ __('Date') }}</flux:table.column>
            <flux:table.column></flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($this->deliveries as $delivery)
                {{-- Hele rij klikbaar naar de detailpagina; hover + cursor maken dat zichtbaar --}}
                <flux:table.row
                    :key="$delivery->id"
                    wire:click="open({{ $delivery->id }})"
                    class="cursor-pointer hover:bg-zinc-50 dark:hover:bg-white/5"
                >
                    <flux:table.cell variant="strong">
                        {{ $delivery->reference ?? '—' }}
                    </flux:table.cell>

                    <flux:table.cell>
                        {{ $delivery->supplier->name }}
                    </flux:table.cell>

                    <flux:table.cell>
                        @php
                            $variant = match($delivery->status->value) {
                                'received' => 'success',
                                'partial' => 'warning',
                                default => 'outline',
                            };
                        @endphp
                        <flux:badge variant="{{ $variant }}">{{ ucfirst($delivery->status->value) }}</flux:badge>
                    </flux:table.cell>

                    <flux:table.cell>
                        {{ $delivery->items->count() }}
                    </flux:table.cell>

                    <flux:table.cell>
                        {{ $delivery->user->name }}
                    </flux:table.cell>

                    <flux:table.cell class="text-sm text-zinc-400">
                        {{ $delivery->created_at->diffForHumans() }}
                    </flux:table.cell>

                    {{-- .stop: anders bubbelt de klik op het oog-icoon door naar de rij-klik (dubbele navigatie) --}}
                    <flux:table.cell align="end" x-on:click.stop>
                        <flux:button
                            :href="route('deliveries.show', $delivery)"
                            wire:navigate
                            variant="ghost"
                            size="sm"
                            icon="eye"
                        />
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="7" class="py-16 text-center">
                        <div class="flex flex-col items-center gap-2">
                            <flux:icon.inbox-arrow-down class="size-10 text-zinc-300" />
                            <span class="text-zinc-500">{{ $filterStatus ? __('No deliveries with this status.') : __('No deliveries yet.') }}</span>
                        </div>
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>

    <div>
        {{ $this->deliveries->links() }}
    </div>

</div>
