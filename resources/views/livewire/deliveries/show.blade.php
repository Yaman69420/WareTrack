<div class="flex h-full w-full flex-1 flex-col gap-6 p-6">

    {{-- Back + Header --}}
    <div>
        <flux:button :href="route('deliveries.index')" wire:navigate variant="ghost" icon="arrow-left" size="sm" class="mb-4">
            {{ __('All Deliveries') }}
        </flux:button>

        <div class="flex flex-wrap items-start justify-between gap-4">
            <div class="flex items-center gap-4">
                @php
                    $statusVariant = match($delivery->status->value) {
                        'received' => 'success',
                        'partial'  => 'warning',
                        default    => 'outline',
                    };
                    $statusIcon = match($delivery->status->value) {
                        'received' => 'check-circle',
                        'partial'  => 'clock',
                        default    => 'inbox',
                    };
                @endphp
                <div class="rounded-xl bg-zinc-100 p-3 dark:bg-zinc-800">
                    <flux:icon.inbox-arrow-down class="size-7 text-zinc-600 dark:text-zinc-300" />
                </div>
                <div>
                    <div class="flex items-center gap-2">
                        <flux:heading size="xl">
                            {{ $delivery->reference ?? __('Delivery') . ' #' . $delivery->id }}
                        </flux:heading>
                        <flux:badge variant="{{ $statusVariant }}" icon="{{ $statusIcon }}">{{ ucfirst($delivery->status->value) }}</flux:badge>
                    </div>
                    <p class="mt-0.5 text-sm text-zinc-500">
                        <span class="font-medium">{{ $delivery->supplier->name }}</span>
                        <span class="mx-1.5 text-zinc-300 dark:text-zinc-600">·</span>
                        {{ $delivery->created_at->format('d/m/Y') }}
                        <span class="mx-1.5 text-zinc-300 dark:text-zinc-600">·</span>
                        {{ __('Created by') }} {{ $delivery->user->name }}
                    </p>
                </div>
            </div>

            {{-- Progress summary --}}
            @php
                $totalOrdered  = $delivery->items->sum('quantity_ordered');
                $totalReceived = $delivery->items->sum('quantity_received');
                $pct = $totalOrdered > 0 ? round(($totalReceived / $totalOrdered) * 100) : 0;
            @endphp
            <div class="flex items-center gap-3 rounded-xl border border-white/[.08] bg-white px-5 py-3 dark:bg-white/[.04]">
                <div>
                    <p class="text-right text-2xl font-bold text-zinc-900 dark:text-zinc-100">{{ $pct }}%</p>
                    <p class="text-xs text-zinc-500">{{ __('received') }}</p>
                </div>
                <div class="h-10 w-px bg-zinc-200 dark:bg-zinc-700"></div>
                <div class="text-sm">
                    <p><span class="font-semibold text-zinc-900 dark:text-zinc-100">{{ $totalReceived }}</span> <span class="text-zinc-500">/ {{ $totalOrdered }}</span></p>
                    <p class="text-zinc-500">{{ __('units') }}</p>
                </div>
            </div>
        </div>
    </div>

    <div class="flex max-w-4xl flex-col gap-6">

        {{-- Notes --}}
        @if($delivery->notes)
            <div class="flex items-start gap-3 rounded-xl border border-amber-200 bg-amber-50 p-4 dark:border-amber-900/40 dark:bg-amber-900/10">
                <flux:icon.information-circle class="mt-0.5 size-5 shrink-0 text-amber-600 dark:text-amber-400" />
                <p class="text-sm text-amber-800 dark:text-amber-300">{{ $delivery->notes }}</p>
            </div>
        @endif

        {{-- Items table --}}
        <div class="flex flex-col gap-4 rounded-xl border border-white/[.08] bg-white p-6 dark:bg-white/[.04]">
            <flux:heading size="lg">{{ __('Delivery Items') }}</flux:heading>

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
                        @php $remaining = $item->quantity_ordered - $item->quantity_received; @endphp
                        <flux:table.row :key="$item->id">
                            <flux:table.cell variant="strong">
                                {{ $item->product->name }}
                                <div class="text-xs font-mono font-normal text-zinc-400">{{ $item->product->sku }}</div>
                            </flux:table.cell>

                            <flux:table.cell class="text-sm">
                                <div class="flex items-center gap-1.5">
                                    <flux:icon.building-office class="size-3.5 text-zinc-400" />
                                    <span class="text-zinc-500">{{ $item->location->warehouse->name }}</span>
                                    <span class="text-zinc-300 dark:text-zinc-600">·</span>
                                    <span class="font-mono font-medium">{{ $item->location->code }}</span>
                                </div>
                            </flux:table.cell>

                            <flux:table.cell>
                                <span class="font-medium">{{ $item->quantity_ordered }}</span>
                            </flux:table.cell>

                            <flux:table.cell>
                                <span class="font-semibold {{ $item->quantity_received >= $item->quantity_ordered ? 'text-emerald-600 dark:text-emerald-400' : '' }}">
                                    {{ $item->quantity_received }}
                                </span>
                            </flux:table.cell>

                            @if($delivery->status !== \App\Enums\DeliveryStatus::Received)
                                <flux:table.cell>
                                    @if($remaining > 0)
                                        <flux:input
                                            wire:model="receivedQuantities.{{ $item->id }}"
                                            type="number"
                                            min="0"
                                            max="{{ $remaining }}"
                                            class="w-24"
                                        />
                                    @else
                                        <flux:badge variant="success" icon="check">{{ __('Done') }}</flux:badge>
                                    @endif
                                </flux:table.cell>
                            @endif
                        </flux:table.row>
                    @endforeach
                </flux:table.rows>
            </flux:table>

            @if($delivery->status !== \App\Enums\DeliveryStatus::Received)
                <div class="flex justify-end pt-2">
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
                <p class="text-sm text-zinc-400">
                    {{ __('Fully received on') }} {{ $delivery->received_at?->format('d/m/Y H:i') }}
                    {{ __('by') }} {{ $delivery->user->name }}.
                </p>
            @endif
        </div>

    </div>

</div>
