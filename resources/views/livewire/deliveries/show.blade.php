<div class="flex h-full w-full flex-1 flex-col gap-6 p-4 sm:p-6">

    {{-- Back + Header --}}
    <div>
        <flux:button :href="route('deliveries.index')" wire:navigate variant="ghost" icon="arrow-left" size="sm" class="mb-4">
            {{ __('All Deliveries') }}
        </flux:button>

        <div class="flex flex-wrap items-start justify-between gap-4">
            <div class="flex items-center gap-4">
                {{-- Badge-stijl en icoon afleiden uit de leveringsstatus (enum): groen=ontvangen,
                     oranje=gedeeltelijk, neutraal=in afwachting --}}
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

            {{-- Progress summary: ontvangstpercentage over alle leveringsregels heen --}}
            @php
                $totalOrdered  = $delivery->items->sum('quantity_ordered');
                $totalReceived = $delivery->items->sum('quantity_received');
                // Guard tegen deling door nul bij een levering zonder regels
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

        {{-- ===== STATUS TIMELINE ===== --}}
        {{-- Drie vaste stappen (aangemaakt → verwerking → volledig ontvangen); de 'done'-vlag per
             stap bepaalt kleur, ring en omschrijving, zodat de markup hieronder generiek blijft --}}
        @php
            $isPartialOrReceived = in_array($delivery->status->value, ['partial', 'received']);
            $isReceived          = $delivery->status === \App\Enums\DeliveryStatus::Received;

            $steps = [
                [
                    'label'     => __('Delivery Created'),
                    'desc'      => __('Registered by') . ' ' . $delivery->user->name,
                    'timestamp' => $delivery->created_at,
                    'done'      => true,
                    'icon'      => 'inbox-arrow-down',
                    'color'     => 'bg-blue-500',
                    'ring'      => 'ring-blue-500/30',
                ],
                [
                    'label'     => __('Processing'),
                    'desc'      => $isPartialOrReceived
                                    ? ($delivery->items->sum('quantity_received') . '/' . $delivery->items->sum('quantity_ordered') . ' ' . __('units received'))
                                    : __('Awaiting first receipt'),
                    'timestamp' => $isPartialOrReceived ? $delivery->updated_at : null,
                    'done'      => $isPartialOrReceived,
                    'icon'      => 'arrow-path',
                    'color'     => $isPartialOrReceived ? 'bg-amber-500' : 'bg-zinc-700',
                    'ring'      => $isPartialOrReceived ? 'ring-amber-500/30' : 'ring-zinc-700/30',
                ],
                [
                    'label'     => __('Fully Received'),
                    'desc'      => $isReceived
                                    ? ($delivery->received_at?->format('d/m/Y H:i') ?? $delivery->updated_at->format('d/m/Y H:i'))
                                    : __('Pending'),
                    'timestamp' => $isReceived ? ($delivery->received_at ?? $delivery->updated_at) : null,
                    'done'      => $isReceived,
                    'icon'      => 'check-circle',
                    'color'     => $isReceived ? 'bg-emerald-500' : 'bg-zinc-700',
                    'ring'      => $isReceived ? 'ring-emerald-500/30' : 'ring-zinc-700/30',
                ],
            ];
        @endphp

        <div class="rounded-xl border border-white/[.08] bg-white px-6 py-5 dark:bg-white/[.04]">
            <flux:heading size="sm" class="mb-4 text-zinc-500">{{ __('Status Timeline') }}</flux:heading>

            <ol class="relative flex flex-col gap-0">
                @foreach ($steps as $i => $step)
                    {{-- Laatste stap krijgt geen verbindingslijn en geen onderpadding --}}
                    @php $isLast = $i === count($steps) - 1; @endphp

                    <li class="relative flex gap-4 {{ $isLast ? '' : 'pb-6' }}">

                        {{-- Connector line: vol bij afgewerkte stap, gestreept bij nog te zetten stap --}}
                        @if (! $isLast)
                            <div class="absolute left-[15px] top-8 h-full w-px {{ $step['done'] ? 'bg-white/[.12]' : 'border-l border-dashed border-white/[.08]' }}"></div>
                        @endif

                        {{-- Dot: onvoltooide tussenstap toont een kleine stip, anders het stap-icoon --}}
                        <div class="relative z-10 flex size-8 shrink-0 items-center justify-center rounded-full {{ $step['color'] }} ring-4 {{ $step['ring'] }}">
                            @if (! $step['done'] && ! $isLast)
                                <span class="size-2 rounded-full bg-zinc-500"></span>
                            @else
                                <flux:icon.{{ $step['icon'] }} class="size-4 text-white" />
                            @endif
                        </div>

                        {{-- Content --}}
                        <div class="flex flex-1 items-start justify-between pt-1">
                            <div>
                                <p class="text-sm font-semibold {{ $step['done'] ? 'text-zinc-100' : 'text-zinc-500' }}">
                                    {{ $step['label'] }}
                                </p>
                                <p class="text-xs text-zinc-500">{{ $step['desc'] }}</p>
                            </div>
                            @if ($step['timestamp'])
                                <span class="ml-4 shrink-0 text-xs text-zinc-600" title="{{ $step['timestamp']->format('d/m/Y H:i:s') }}">
                                    {{ $step['timestamp']->diffForHumans() }}
                                </span>
                            @endif
                        </div>
                    </li>
                @endforeach
            </ol>
        </div>

        {{-- Notes: enkel tonen wanneer er effectief een opmerking bij de levering hoort --}}
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
                    {{-- Invoerkolom verdwijnt zodra de levering volledig ontvangen is --}}
                    @if($delivery->status !== \App\Enums\DeliveryStatus::Received)
                        <flux:table.column>{{ __('Receive now') }}</flux:table.column>
                    @endif
                </flux:table.columns>

                <flux:table.rows>
                    @foreach ($delivery->items as $item)
                        {{-- Resterend aantal bepaalt de max van het invoerveld en of de regel al af is --}}
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
                                    {{-- Open regel: invoerveld gebonden aan receivedQuantities[itemId];
                                         volledig geleverde regel krijgt een Done-badge --}}
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

            {{-- Verwerken kan enkel zolang de levering open staat; daarna tonen we de afsluitinfo.
                 wire:confirm vraagt bevestiging omdat process() effectief de voorraad bijwerkt --}}
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
