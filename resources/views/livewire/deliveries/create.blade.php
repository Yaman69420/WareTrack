<div class="flex h-full w-full flex-1 flex-col gap-6 p-4 sm:p-6">

    {{-- Header --}}
    <div class="flex items-center gap-4">
        <flux:button :href="route('deliveries.index')" wire:navigate variant="ghost" icon="arrow-left" />
        <flux:heading size="xl">{{ __('New Delivery') }}</flux:heading>
    </div>

    <div class="flex max-w-2xl flex-col gap-6">

        {{-- Delivery info --}}
        <div class="flex flex-col gap-4 rounded-xl border border-white/[.08] bg-white p-6 dark:bg-white/[.04]">
            <flux:heading size="lg">{{ __('Delivery Details') }}</flux:heading>

            <div class="grid grid-cols-2 gap-4">

                {{-- Supplier — native select for reliable Livewire binding --}}
                <div class="col-span-2 flex flex-col gap-1">
                    <flux:label>{{ __('Supplier') }}</flux:label>
                    <select
                        wire:model.live="supplierId"
                        class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 dark:border-white/10 dark:bg-white/5 dark:text-zinc-100 dark:focus:border-blue-400"
                    >
                        <option value="">{{ __('Select supplier...') }}</option>
                        @foreach ($this->suppliers as $supplier)
                            <option value="{{ $supplier->id }}">{{ $supplier->name }}</option>
                        @endforeach
                    </select>
                    <flux:error name="supplierId" />
                </div>

                <flux:field>
                    <flux:label>{{ __('Reference') }} <span class="text-zinc-400 text-xs font-normal">({{ __('optional') }})</span></flux:label>
                    <flux:input wire:model="reference" placeholder="PO-2026-001" />
                    <flux:error name="reference" />
                </flux:field>

                <flux:field class="col-span-2">
                    <flux:label>{{ __('Notes') }} <span class="text-zinc-400 text-xs font-normal">({{ __('optional') }})</span></flux:label>
                    <flux:textarea wire:model="notes" rows="2" placeholder="{{ __('Internal notes...') }}" />
                    <flux:error name="notes" />
                </flux:field>
            </div>
        </div>

        {{-- Items --}}
        <div class="flex flex-col gap-4 rounded-xl border border-white/[.08] bg-white p-6 dark:bg-white/[.04]">
            <div class="flex items-center justify-between">
                <flux:heading size="lg">{{ __('Items') }}</flux:heading>
                @if($supplierId)
                    <flux:button wire:click="addItem" variant="ghost" size="sm" icon="plus">
                        {{ __('Add item') }}
                    </flux:button>
                @endif
            </div>

            @if(! $supplierId)
                <div class="flex items-center gap-2 rounded-lg border border-white/[.06] bg-white/[.02] px-4 py-3 text-sm text-zinc-500">
                    <flux:icon.arrow-up class="size-4 shrink-0" />
                    {{ __('Select a supplier first to see their products.') }}
                </div>
            @else
                <flux:error name="items" />

                @foreach ($items as $index => $item)
                    <div class="grid grid-cols-[1fr_1fr_auto_auto] items-end gap-3">

                        {{-- Product --}}
                        <div class="flex flex-col gap-1">
                            @if($index === 0)
                                <flux:label>{{ __('Product') }}</flux:label>
                            @endif
                            <select
                                wire:model.live="items.{{ $index }}.product_id"
                                class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 dark:border-white/10 dark:bg-white/5 dark:text-zinc-100 dark:focus:border-blue-400"
                            >
                                <option value="">{{ __('Select product...') }}</option>
                                @foreach ($this->products as $product)
                                    <option value="{{ $product->id }}">{{ $product->name }} ({{ $product->sku }})</option>
                                @endforeach
                            </select>
                            <flux:error name="items.{{ $index }}.product_id" />
                        </div>

                        {{-- Location --}}
                        <div class="flex flex-col gap-1">
                            @if($index === 0)
                                <flux:label>{{ __('Location') }}</flux:label>
                            @endif
                            <select
                                wire:model.live="items.{{ $index }}.location_id"
                                class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 dark:border-white/10 dark:bg-white/5 dark:text-zinc-100 dark:focus:border-blue-400"
                            >
                                <option value="">{{ __('Select location...') }}</option>
                                @foreach ($this->locations as $location)
                                    <option value="{{ $location->id }}">{{ $location->warehouse->name }} — {{ $location->code }}</option>
                                @endforeach
                            </select>
                            <flux:error name="items.{{ $index }}.location_id" />
                        </div>

                        {{-- Qty --}}
                        <flux:field class="w-24">
                            @if($index === 0)
                                <flux:label>{{ __('Qty') }}</flux:label>
                            @endif
                            <flux:input wire:model="items.{{ $index }}.quantity_ordered" type="number" min="1" />
                            <flux:error name="items.{{ $index }}.quantity_ordered" />
                        </flux:field>

                        <flux:button
                            wire:click="removeItem({{ $index }})"
                            variant="ghost"
                            size="sm"
                            icon="trash"
                            class="{{ $index === 0 ? 'mt-6' : '' }} text-red-500"
                        />
                    </div>
                @endforeach

                @if(empty($items))
                    <flux:text class="text-sm text-zinc-400">{{ __('Add at least one item.') }}</flux:text>
                @endif
            @endif
        </div>

        <div class="flex justify-end gap-3">
            <flux:button :href="route('deliveries.index')" wire:navigate variant="ghost">
                {{ __('Cancel') }}
            </flux:button>
            <flux:button wire:click="save" variant="primary">
                {{ __('Create Delivery') }}
            </flux:button>
        </div>

    </div>

</div>
