<div class="flex h-full w-full flex-1 flex-col gap-6 p-4 sm:p-6">

    {{-- Header --}}
    <div class="flex items-center gap-4">
        <flux:button :href="route('stock.movements')" wire:navigate variant="ghost" icon="arrow-left" />
        <flux:heading size="xl">{{ __('Register Stock Movement') }}</flux:heading>
    </div>

    {{-- Stapsgewijs formulier: elk veld verschijnt pas zodra de vorige keuze gemaakt is.
         wire:model.live op de selects zorgt dat afhankelijke lijsten meteen meefilteren --}}
    <div class="max-w-xl">
        <div class="flex flex-col gap-6 rounded-xl border border-white/[.08] bg-white p-6 dark:bg-white/[.04]">

            {{-- Step 1: Type — native select for reliable Livewire binding --}}
            <div class="flex flex-col gap-1">
                <flux:label>{{ __('Movement Type') }}</flux:label>
                <select
                    wire:model.live="type"
                    class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 dark:border-white/10 dark:bg-white/5 dark:text-zinc-100 dark:focus:border-blue-400"
                >
                    <option value="">{{ __('Select type…') }}</option>
                    <option value="incoming">⬇ {{ __('Incoming') }}</option>
                    <option value="outgoing">⬆ {{ __('Outgoing') }}</option>
                    <option value="transfer">⇄ {{ __('Transfer') }}</option>
                    <option value="correction">✎ {{ __('Correction') }}</option>
                </select>
                <flux:error name="type" />
            </div>

            {{-- De rest van het formulier toont pas na de typekeuze (zie empty state onderaan) --}}
            @if ($type)

                {{-- Step 2: Product — native select for reliable Livewire binding --}}
                <div class="flex flex-col gap-1">
                    <flux:label>{{ __('Product') }}</flux:label>
                    <select
                        wire:model.live="productId"
                        class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 dark:border-white/10 dark:bg-white/5 dark:text-zinc-100 dark:focus:border-blue-400"
                    >
                        <option value="">{{ __('Select product…') }}</option>
                        @foreach ($this->products as $product)
                            <option value="{{ $product->id }}">
                                {{ $product->name }} — {{ $product->sku }}
                            </option>
                        @endforeach
                    </select>
                    <flux:error name="productId" />
                </div>

                {{-- Eén locatie volstaat voor incoming/outgoing/correction;
                     een transfer heeft aparte van- en naar-blokken (zie @else) --}}
                @if ($type !== 'transfer')

                    {{-- Step 3a: Warehouse picker — native select for reliable Livewire binding --}}
                    <div class="flex flex-col gap-1">
                        <flux:label>{{ __('Warehouse') }}</flux:label>
                        <select
                            wire:model.live="warehouseId"
                            class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 dark:border-white/10 dark:bg-white/5 dark:text-zinc-100 dark:focus:border-blue-400"
                        >
                            <option value="">{{ __('Select warehouse…') }}</option>
                            @foreach ($this->warehouses as $warehouse)
                                <option value="{{ $warehouse->id }}">{{ $warehouse->name }}</option>
                            @endforeach
                        </select>
                        <flux:error name="warehouseId" />
                    </div>

                    {{-- Step 4a: Location (filtered) — native select for reliable Livewire binding;
                         lijst bevat enkel locaties van het gekozen magazijn --}}
                    @if ($warehouseId)
                        <div class="flex flex-col gap-1">
                            {{-- Label volgt de richting van de beweging: bestemming, bron of neutraal --}}
                            <flux:label>
                                @if ($type === 'incoming') {{ __('Destination Location') }}
                                @elseif ($type === 'outgoing') {{ __('Source Location') }}
                                @else {{ __('Location') }}
                                @endif
                            </flux:label>
                            <select
                                wire:model.live="locationId"
                                class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 dark:border-white/10 dark:bg-white/5 dark:text-zinc-100 dark:focus:border-blue-400"
                            >
                                <option value="">{{ __('Select location…') }}</option>
                                @foreach ($this->locations as $location)
                                    <option value="{{ $location->id }}">
                                        {{ $location->code }}{{ $location->name ? ' — ' . $location->name : '' }}
                                    </option>
                                @endforeach
                            </select>
                            <flux:error name="locationId" />
                        </div>

                        {{-- Current stock hint: helpt fouten voorkomen vóór het opslaan;
                             strikte null-check omdat een stand van 0 ook getoond moet worden --}}
                        @if ($productId && $locationId && $this->currentStock !== null)
                            <div class="flex items-center gap-2 rounded-lg border border-white/[.06] bg-white/[.03] px-4 py-2.5 text-sm">
                                <flux:icon.cube class="size-4 text-zinc-500" />
                                <span class="text-zinc-400">{{ __('Current stock at this location:') }}</span>
                                <span class="ml-auto font-bold {{ $this->currentStock > 0 ? 'text-emerald-400' : 'text-red-400' }}">
                                    {{ $this->currentStock }} {{ __('units') }}
                                </span>
                            </div>
                        @endif
                    @endif

                @else

                    {{-- Transfer: From — bronlocatie; locatielijst volgt het gekozen bronmagazijn --}}
                    <div class="rounded-lg border border-white/[.06] bg-white/[.02] p-4">
                        <p class="mb-3 text-xs font-semibold uppercase tracking-widest text-zinc-500">{{ __('From') }}</p>

                        <div class="flex flex-col gap-4">
                            {{-- From warehouse — native select for reliable Livewire binding --}}
                            <div class="flex flex-col gap-1">
                                <flux:label>{{ __('Warehouse') }}</flux:label>
                                <select
                                    wire:model.live="fromWarehouseId"
                                    class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 dark:border-white/10 dark:bg-white/5 dark:text-zinc-100 dark:focus:border-blue-400"
                                >
                                    <option value="">{{ __('Select warehouse…') }}</option>
                                    @foreach ($this->warehouses as $warehouse)
                                        <option value="{{ $warehouse->id }}">{{ $warehouse->name }}</option>
                                    @endforeach
                                </select>
                                <flux:error name="fromWarehouseId" />
                            </div>

                            @if ($fromWarehouseId)
                                {{-- From location — native select for reliable Livewire binding --}}
                                <div class="flex flex-col gap-1">
                                    <flux:label>{{ __('Location') }}</flux:label>
                                    <select
                                        wire:model.live="fromLocationId"
                                        class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 dark:border-white/10 dark:bg-white/5 dark:text-zinc-100 dark:focus:border-blue-400"
                                    >
                                        <option value="">{{ __('Select location…') }}</option>
                                        @foreach ($this->fromLocations as $location)
                                            <option value="{{ $location->id }}">
                                                {{ $location->code }}{{ $location->name ? ' — ' . $location->name : '' }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <flux:error name="fromLocationId" />
                                </div>
                            @endif
                        </div>
                    </div>

                    {{-- Transfer: To — doellocatie, zelfde patroon als het From-blok --}}
                    <div class="rounded-lg border border-white/[.06] bg-white/[.02] p-4">
                        <p class="mb-3 text-xs font-semibold uppercase tracking-widest text-zinc-500">{{ __('To') }}</p>

                        <div class="flex flex-col gap-4">
                            {{-- To warehouse — native select for reliable Livewire binding --}}
                            <div class="flex flex-col gap-1">
                                <flux:label>{{ __('Warehouse') }}</flux:label>
                                <select
                                    wire:model.live="toWarehouseId"
                                    class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 dark:border-white/10 dark:bg-white/5 dark:text-zinc-100 dark:focus:border-blue-400"
                                >
                                    <option value="">{{ __('Select warehouse…') }}</option>
                                    @foreach ($this->warehouses as $warehouse)
                                        <option value="{{ $warehouse->id }}">{{ $warehouse->name }}</option>
                                    @endforeach
                                </select>
                                <flux:error name="toWarehouseId" />
                            </div>

                            @if ($toWarehouseId)
                                {{-- To location — native select for reliable Livewire binding --}}
                                <div class="flex flex-col gap-1">
                                    <flux:label>{{ __('Location') }}</flux:label>
                                    <select
                                        wire:model.live="toLocationId"
                                        class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 dark:border-white/10 dark:bg-white/5 dark:text-zinc-100 dark:focus:border-blue-400"
                                    >
                                        <option value="">{{ __('Select location…') }}</option>
                                        @foreach ($this->toLocations as $location)
                                            <option value="{{ $location->id }}">
                                                {{ $location->code }}{{ $location->name ? ' — ' . $location->name : '' }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <flux:error name="toLocationId" />
                                </div>
                            @endif
                        </div>
                    </div>

                @endif

                {{-- Quantity: een correctie vraagt de absolute nieuwe stand (mag 0 zijn),
                     de andere types een verplaatst aantal (minstens 1) --}}
                <flux:field>
                    <flux:label>
                        {{ $type === 'correction' ? __('New Stock Quantity') : __('Quantity') }}
                    </flux:label>
                    <flux:input
                        wire:model="quantity"
                        type="number"
                        min="{{ $type === 'correction' ? '0' : '1' }}"
                        placeholder="{{ $type === 'correction' ? '0' : '1' }}"
                    />
                    <flux:error name="quantity" />
                </flux:field>

                {{-- Reference (incoming / outgoing only): bv. bestelbonnummer; bij transfer
                     of correctie is er geen extern document om naar te verwijzen --}}
                @if ($type === 'incoming' || $type === 'outgoing')
                    <flux:field>
                        <flux:label>
                            {{ __('Reference') }}
                            <span class="text-xs font-normal text-zinc-400">({{ __('optional') }})</span>
                        </flux:label>
                        <flux:input wire:model="reference" placeholder="{{ __('e.g. PO-2026-001') }}" />
                        <flux:error name="reference" />
                    </flux:field>
                @endif

                {{-- Notes --}}
                <flux:field>
                    <flux:label>
                        {{ __('Notes') }}
                        <span class="text-xs font-normal text-zinc-400">({{ __('optional') }})</span>
                    </flux:label>
                    <flux:textarea wire:model="notes" rows="2" placeholder="{{ __('Internal notes…') }}" />
                    <flux:error name="notes" />
                </flux:field>

                <div class="flex justify-end gap-3">
                    <flux:button :href="route('stock.movements')" wire:navigate variant="ghost">
                        {{ __('Cancel') }}
                    </flux:button>
                    <flux:button wire:click="save" variant="primary">
                        {{ __('Register') }}
                    </flux:button>
                </div>

            @else

                {{-- Empty state when no type selected yet --}}
                <div class="py-6 text-center text-sm text-zinc-500">
                    {{ __('Select a movement type to get started.') }}
                </div>

            @endif

        </div>
    </div>

</div>
