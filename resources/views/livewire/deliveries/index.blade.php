<div class="flex h-full w-full flex-1 flex-col gap-6 p-6">

    {{-- Header --}}
    <div class="flex items-center justify-between">
        <flux:heading size="xl">{{ __('Deliveries') }}</flux:heading>
        @if(auth()->user()->isAdmin())
            <flux:button :href="route('deliveries.create')" wire:navigate variant="primary" icon="plus">
                {{ __('New Delivery') }}
            </flux:button>
        @endif
    </div>

    {{-- Filter --}}
    <div class="w-48">
        <flux:select wire:model.live="filterStatus" placeholder="{{ __('All statuses') }}">
            <flux:select.option value="">{{ __('All statuses') }}</flux:select.option>
            @foreach ($this->statuses as $status)
                <flux:select.option value="{{ $status->value }}">{{ ucfirst($status->value) }}</flux:select.option>
            @endforeach
        </flux:select>
    </div>

    {{-- Table --}}
    <flux:table>
        <flux:table.columns>
            <flux:table.column>{{ __('Reference') }}</flux:table.column>
            <flux:table.column>{{ __('Supplier') }}</flux:table.column>
            <flux:table.column>{{ __('Status') }}</flux:table.column>
            <flux:table.column>{{ __('Items') }}</flux:table.column>
            <flux:table.column>{{ __('Created by') }}</flux:table.column>
            <flux:table.column>{{ __('Date') }}</flux:table.column>
            <flux:table.column></flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($this->deliveries as $delivery)
                <flux:table.row :key="$delivery->id">
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

                    <flux:table.cell align="end">
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
                    <flux:table.cell colspan="7" class="py-12 text-center">
                        {{ $filterStatus ? __('No deliveries with this status.') : __('No deliveries yet.') }}
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>

    <div>
        {{ $this->deliveries->links() }}
    </div>

</div>
