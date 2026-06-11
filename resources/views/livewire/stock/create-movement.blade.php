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

            {{-- Step 1: Type --}}
            <flux:field>
                <flux:label>{{ __('Movement Type') }}</flux:label>
                <flux:select wire:model.live="type" placeholder="{{ __('Select type…') }}">
                    <flux:select.option value="incoming">⬇ {{ __('Incoming') }}</flux:select.option>
                    <flux:select.option value="outgoing">⬆ {{ __('Outgoing') }}</flux:select.option>
                    <flux:select.option value="transfer">⇄ {{ __('Transfer') }}</flux:select.option>
                    <flux:select.option value="correction">✎ {{ __('Correction') }}</flux:select.option>
                </flux:select>
                <flux:error name="type" />
            </flux:field>

            {{-- De rest van het formulier toont pas na de typekeuze (zie empty state onderaan) --}}
            @if ($type)

                {{-- Step 2: Product --}}
                <flux:field>
                    <flux:label>{{ __('Product') }}</flux:label>
                    <flux:select wire:model.live="productId" placeholder="{{ __('Select product…') }}">
                        @foreach ($this->products as $product)
                            <flux:select.option value="{{ $product->id }}">
                                {{ $product->name }} — {{ $product->sku }}
                            </flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:error name="productId" />
                </flux:field>

                {{-- Eén locatie volstaat voor incoming/outgoing/correction;
                     een transfer heeft aparte van- en naar-blokken (zie @else) --}}
                @if ($type !== 'transfer')

                    {{-- Step 3a: Warehouse picker --}}
                    <flux:field>
                        <flux:label>{{ __('Warehouse') }}</flux:label>
                        <flux:select wire:model.live="warehouseId" placeholder="{{ __('Select warehouse…') }}">
                            @foreach ($this->warehouses as $warehouse)
                                <flux:select.option value="{{ $warehouse->id }}">{{ $warehouse->name }}</flux:select.option>
                            @endforeach
                        </flux:select>
                    </flux:field>

                    {{-- Step 4a: Location (filtered) — lijst bevat enkel locaties van het gekozen magazijn --}}
                    @if ($warehouseId)
                        <flux:field>
                            {{-- Label volgt de richting van de beweging: bestemming, bron of neutraal --}}
                            <flux:label>
                                @if ($type === 'incoming') {{ __('Destination Location') }}
                                @elseif ($type === 'outgoing') {{ __('Source Location') }}
                                @else {{ __('Location') }}
                                @endif
                            </flux:label>
                            <flux:select wire:model.live="locationId" placeholder="{{ __('Select location…') }}">
                                @foreach ($this->locations as $location)
                                    <flux:select.option value="{{ $location->id }}">
                                        {{ $location->code }}{{ $location->name ? ' — ' . $location->name : '' }}
                                    </flux:select.option>
                                @endforeach
                            </flux:select>
                            <flux:error name="locationId" />
                        </flux:field>

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
                            <flux:field>
                                <flux:label>{{ __('Warehouse') }}</flux:label>
                                <flux:select wire:model.live="fromWarehouseId" placeholder="{{ __('Select warehouse…') }}">
                                    @foreach ($this->warehouses as $warehouse)
                                        <flux:select.option value="{{ $warehouse->id }}">{{ $warehouse->name }}</flux:select.option>
                                    @endforeach
                                </flux:select>
                            </flux:field>

                            @if ($fromWarehouseId)
                                <flux:field>
                                    <flux:label>{{ __('Location') }}</flux:label>
                                    <flux:select wire:model.live="fromLocationId" placeholder="{{ __('Select location…') }}">
                                        @foreach ($this->fromLocations as $location)
                                            <flux:select.option value="{{ $location->id }}">
                                                {{ $location->code }}{{ $location->name ? ' — ' . $location->name : '' }}
                                            </flux:select.option>
                                        @endforeach
                                    </flux:select>
                                    <flux:error name="fromLocationId" />
                                </flux:field>
                            @endif
                        </div>
                    </div>

                    {{-- Transfer: To — doellocatie, zelfde patroon als het From-blok --}}
                    <div class="rounded-lg border border-white/[.06] bg-white/[.02] p-4">
                        <p class="mb-3 text-xs font-semibold uppercase tracking-widest text-zinc-500">{{ __('To') }}</p>

                        <div class="flex flex-col gap-4">
                            <flux:field>
                                <flux:label>{{ __('Warehouse') }}</flux:label>
                                <flux:select wire:model.live="toWarehouseId" placeholder="{{ __('Select warehouse…') }}">
                                    @foreach ($this->warehouses as $warehouse)
                                        <flux:select.option value="{{ $warehouse->id }}">{{ $warehouse->name }}</flux:select.option>
                                    @endforeach
                                </flux:select>
                            </flux:field>

                            @if ($toWarehouseId)
                                <flux:field>
                                    <flux:label>{{ __('Location') }}</flux:label>
                                    <flux:select wire:model.live="toLocationId" placeholder="{{ __('Select location…') }}">
                                        @foreach ($this->toLocations as $location)
                                            <flux:select.option value="{{ $location->id }}">
                                                {{ $location->code }}{{ $location->name ? ' — ' . $location->name : '' }}
                                            </flux:select.option>
                                        @endforeach
                                    </flux:select>
                                    <flux:error name="toLocationId" />
                                </flux:field>
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
