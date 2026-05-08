<div class="flex h-full w-full flex-1 flex-col gap-6 p-6">

    {{-- Header --}}
    <div class="flex items-center gap-4">
        <flux:button :href="route('stock.movements')" wire:navigate variant="ghost" icon="arrow-left" />
        <flux:heading size="xl">{{ __('Register Stock Movement') }}</flux:heading>
    </div>

    <div class="max-w-xl">
        <div class="flex flex-col gap-6 rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">

            {{-- Type --}}
            <flux:field>
                <flux:label>{{ __('Movement Type') }}</flux:label>
                <flux:select wire:model.live="type" placeholder="{{ __('Select type...') }}">
                    <flux:select.option value="incoming">{{ __('Incoming') }}</flux:select.option>
                    <flux:select.option value="outgoing">{{ __('Outgoing') }}</flux:select.option>
                    <flux:select.option value="transfer">{{ __('Transfer') }}</flux:select.option>
                    <flux:select.option value="correction">{{ __('Correction') }}</flux:select.option>
                </flux:select>
                <flux:error name="type" />
            </flux:field>

            {{-- Product --}}
            <flux:field>
                <flux:label>{{ __('Product') }}</flux:label>
                <flux:select wire:model="productId" placeholder="{{ __('Select product...') }}">
                    @foreach ($this->products as $product)
                        <flux:select.option value="{{ $product->id }}">
                            {{ $product->name }} ({{ $product->sku }})
                        </flux:select.option>
                    @endforeach
                </flux:select>
                <flux:error name="productId" />
            </flux:field>

            {{-- Location (incoming / outgoing / correction) --}}
            @if ($type && $type !== 'transfer')
                <flux:field>
                    <flux:label>
                        {{ $type === 'correction' ? __('Location') : ($type === 'incoming' ? __('Destination Location') : __('Source Location')) }}
                    </flux:label>
                    <flux:select wire:model="locationId" placeholder="{{ __('Select location...') }}">
                        @foreach ($this->locations as $location)
                            <flux:select.option value="{{ $location->id }}">
                                {{ $location->warehouse->name }} — {{ $location->code }}
                                @if($location->name) ({{ $location->name }}) @endif
                            </flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:error name="locationId" />
                </flux:field>
            @endif

            {{-- From / To (transfer) --}}
            @if ($type === 'transfer')
                <flux:field>
                    <flux:label>{{ __('From Location') }}</flux:label>
                    <flux:select wire:model="fromLocationId" placeholder="{{ __('Select source...') }}">
                        @foreach ($this->locations as $location)
                            <flux:select.option value="{{ $location->id }}">
                                {{ $location->warehouse->name }} — {{ $location->code }}
                            </flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:error name="fromLocationId" />
                </flux:field>

                <flux:field>
                    <flux:label>{{ __('To Location') }}</flux:label>
                    <flux:select wire:model="toLocationId" placeholder="{{ __('Select destination...') }}">
                        @foreach ($this->locations as $location)
                            <flux:select.option value="{{ $location->id }}">
                                {{ $location->warehouse->name }} — {{ $location->code }}
                            </flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:error name="toLocationId" />
                </flux:field>
            @endif

            {{-- Quantity --}}
            <flux:field>
                <flux:label>
                    {{ $type === 'correction' ? __('New Stock Quantity') : __('Quantity') }}
                </flux:label>
                <flux:input wire:model="quantity" type="number" min="{{ $type === 'correction' ? '0' : '1' }}" placeholder="{{ $type === 'correction' ? '0' : '1' }}" />
                <flux:error name="quantity" />
            </flux:field>

            {{-- Reference (not for correction/transfer) --}}
            @if ($type === 'incoming' || $type === 'outgoing')
                <flux:field>
                    <flux:label>{{ __('Reference') }} <span class="text-zinc-400 text-xs font-normal">({{ __('optional') }})</span></flux:label>
                    <flux:input wire:model="reference" placeholder="{{ __('e.g. PO-2026-001') }}" />
                    <flux:error name="reference" />
                </flux:field>
            @endif

            {{-- Notes --}}
            <flux:field>
                <flux:label>{{ __('Notes') }} <span class="text-zinc-400 text-xs font-normal">({{ __('optional') }})</span></flux:label>
                <flux:textarea wire:model="notes" rows="2" placeholder="{{ __('Internal notes...') }}" />
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

        </div>
    </div>

</div>
