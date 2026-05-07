<div class="flex h-full w-full flex-1 flex-col gap-6 p-6">

    {{-- Header --}}
    <div class="flex items-center gap-4">
        <flux:button :href="route('deliveries.index')" wire:navigate variant="ghost" icon="arrow-left" />
        <div>
            <flux:heading size="xl">
                {{ __('Delivery') }} {{ $delivery->reference ? "— {$delivery->reference}" : "#{$delivery->id}" }}
            </flux:heading>
            <flux:subheading>{{ $delivery->supplier->name }} · {{ $delivery->created_at->format('d/m/Y') }}</flux:subheading>
        </div>
        @php
            $statusVariant = match($delivery->status->value) {
                'received' => 'success',
                'partial' => 'warning',
                default => 'outline',
            };
        @endphp
        <flux:badge variant="{{ $statusVariant }}" class="ml-2">{{ ucfirst($delivery->status->value) }}</flux:badge>
    </div>

    <div class="flex max-w-3xl flex-col gap-6">

        {{-- Meta --}}
        @if($delivery->notes)
            <flux:card>
                <flux:text class="text-sm text-zinc-500">{{ $delivery->notes }}</flux:text>
            </flux:card>
        @endif

        {{-- Items --}}
        <div class="flex flex-col gap-4 rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:heading size="lg">{{ __('Items') }}</flux:heading>

            <flux:table>
                <flux:table.columns>
                    <flux:table.column>{{ __('Product') }}</flux:table.column>
                    <flux:table.column>{{ __('Location') }}</flux:table.column>
                    <flux:table.column>{{ __('Ordered') }}</flux:table.column>
                    <flux:table.column>{{ __('Received') }}</flux:table.column>
                    @if($delivery->status !== \App\Enums\DeliveryStatus::Received)
                        <flux:table.column>{{ __('Receive now') }}</flux:table.column>
                    @endif
                </flux:table.columns>

                <flux:table.rows>
                    @foreach ($delivery->items as $item)
                        <flux:table.row :key="$item->id">
                            <flux:table.cell variant="strong">
                                {{ $item->product->name }}
                                <div class="text-xs font-normal text-zinc-400">{{ $item->product->sku }}</div>
                            </flux:table.cell>

                            <flux:table.cell class="text-sm">
                                {{ $item->location->warehouse->name }} — {{ $item->location->code }}
                            </flux:table.cell>

                            <flux:table.cell>
                                {{ $item->quantity_ordered }}
                            </flux:table.cell>

                            <flux:table.cell>
                                <span class="{{ $item->quantity_received >= $item->quantity_ordered ? 'text-green-600 dark:text-green-400' : '' }} font-medium">
                                    {{ $item->quantity_received }}
                                </span>
                            </flux:table.cell>

                            @if($delivery->status !== \App\Enums\DeliveryStatus::Received)
                                <flux:table.cell>
                                    @php $remaining = $item->quantity_ordered - $item->quantity_received; @endphp
                                    @if($remaining > 0)
                                        <flux:input
                                            wire:model="receivedQuantities.{{ $item->id }}"
                                            type="number"
                                            min="0"
                                            max="{{ $remaining }}"
                                            class="w-24"
                                        />
                                    @else
                                        <flux:badge variant="success">{{ __('Done') }}</flux:badge>
                                    @endif
                                </flux:table.cell>
                            @endif
                        </flux:table.row>
                    @endforeach
                </flux:table.rows>
            </flux:table>

            @if($delivery->status !== \App\Enums\DeliveryStatus::Received)
                <div class="flex justify-end">
                    <flux:button
                        wire:click="process"
                        wire:confirm="{{ __('Process this delivery and update stock?') }}"
                        variant="primary"
                        icon="check"
                    >
                        {{ __('Process Delivery') }}
                    </flux:button>
                </div>
            @else
                <flux:text class="text-sm text-zinc-400">
                    {{ __('Fully received on') }} {{ $delivery->received_at?->format('d/m/Y H:i') }}
                    {{ __('by') }} {{ $delivery->user->name }}.
                </flux:text>
            @endif
        </div>

    </div>

</div>
