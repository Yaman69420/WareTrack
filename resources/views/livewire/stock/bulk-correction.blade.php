<div class="flex h-full w-full flex-1 flex-col gap-6 p-4 sm:p-6">

    {{-- Header --}}
    <div class="flex items-center gap-4">
        <flux:button :href="route('stock.index')" wire:navigate variant="ghost" icon="arrow-left" />
        <div>
            <flux:heading size="xl">{{ __('Bulk Stock Correction') }}</flux:heading>
            <flux:subheading>{{ __('Adjust stock quantities for multiple locations at once.') }}</flux:subheading>
        </div>
    </div>

    <div class="max-w-4xl space-y-6">

        {{-- Step 1: Select warehouse — wire:model.live laadt meteen de voorraadlijnen van dat magazijn --}}
        <div class="rounded-xl border border-white/[.08] bg-white/[.04] p-6">
            <flux:heading size="sm" class="mb-4 text-zinc-400">{{ __('1. Select warehouse') }}</flux:heading>
            <div class="max-w-xs">
                <flux:select wire:model.live="warehouseId" placeholder="{{ __('Choose warehouse…') }}">
                    @foreach ($this->warehouses as $warehouse)
                        <flux:select.option value="{{ $warehouse->id }}">{{ $warehouse->name }}</flux:select.option>
                    @endforeach
                </flux:select>
            </div>
        </div>

        {{-- Stappen 2 en 3 verschijnen pas nadat een magazijn gekozen is --}}
        @if ($warehouseId)

            {{-- Step 2: Edit quantities — per lijn de getelde (correcte) hoeveelheid invullen --}}
            <div class="rounded-xl border border-white/[.08] bg-white/[.04] p-6">
                <div class="mb-4 flex items-center justify-between">
                    <flux:heading size="sm" class="text-zinc-400">{{ __('2. Set correct quantities') }}</flux:heading>
                    <flux:button wire:click="prefillQuantities" variant="ghost" size="sm" icon="arrow-path">
                        {{ __('Reset to current') }}
                    </flux:button>
                </div>

                @if ($this->stockLines->isEmpty())
                    <div class="py-10 text-center text-sm text-zinc-500">
                        {{ __('No stock registered in this warehouse yet.') }}
                    </div>
                @else
                    <flux:table>
                        <flux:table.columns>
                            <flux:table.column>{{ __('Location') }}</flux:table.column>
                            <flux:table.column>{{ __('Product') }}</flux:table.column>
                            <flux:table.column>{{ __('Category') }}</flux:table.column>
                            <flux:table.column>{{ __('Current') }}</flux:table.column>
                            <flux:table.column>{{ __('New quantity') }}</flux:table.column>
                            <flux:table.column>{{ __('Δ') }}</flux:table.column>
                        </flux:table.columns>

                        <flux:table.rows>
                            @foreach ($this->stockLines as $line)
                                {{-- Delta per lijn: een leeg of ontbrekend invoerveld telt als ongewijzigd
                                     (terugvallen op de huidige stand), zodat enkel echte edits meetellen --}}
                                @php
                                    $newQty = isset($quantities[$line->id]) && $quantities[$line->id] !== ''
                                        ? (int) $quantities[$line->id]
                                        : $line->quantity;
                                    $diff = $newQty - $line->quantity;
                                @endphp

                                {{-- Gewijzigde rijen krijgen een amberkleurige achtergrond als visuele marker --}}
                                <flux:table.row :key="$line->id"
                                    class="{{ $diff !== 0 ? 'bg-amber-500/[.04]' : '' }}">

                                    <flux:table.cell>
                                        <span class="font-mono text-sm font-semibold text-zinc-200">
                                            {{ $line->location->code }}
                                        </span>
                                    </flux:table.cell>

                                    <flux:table.cell variant="strong">
                                        {{ $line->product->name }}
                                        <div class="font-mono text-xs font-normal text-zinc-500">{{ $line->product->sku }}</div>
                                    </flux:table.cell>

                                    <flux:table.cell class="text-sm text-zinc-400">
                                        {{ $line->product->category?->name ?? '—' }}
                                    </flux:table.cell>

                                    <flux:table.cell>
                                        <span class="tabular-nums text-zinc-300">{{ $line->quantity }}</span>
                                    </flux:table.cell>

                                    <flux:table.cell>
                                        {{-- Live binding aan quantities[lijnId]; ring markeert een afwijkende waarde --}}
                                        <flux:input
                                            wire:model.live="quantities.{{ $line->id }}"
                                            type="number"
                                            min="0"
                                            class="w-24 {{ $diff !== 0 ? 'ring-1 ring-amber-500/50' : '' }}"
                                        />
                                    </flux:table.cell>

                                    {{-- Δ-kolom: groen bij toename, rood bij afname, streepje bij geen wijziging --}}
                                    <flux:table.cell>
                                        @if ($diff > 0)
                                            <span class="text-sm font-bold text-emerald-400">+{{ $diff }}</span>
                                        @elseif ($diff < 0)
                                            <span class="text-sm font-bold text-red-400">{{ $diff }}</span>
                                        @else
                                            <span class="text-sm text-zinc-600">—</span>
                                        @endif
                                    </flux:table.cell>

                                </flux:table.row>
                            @endforeach
                        </flux:table.rows>
                    </flux:table>
                @endif
            </div>

            {{-- Step 3: Notes + save --}}
            @if ($this->stockLines->isNotEmpty())
                <div class="rounded-xl border border-white/[.08] bg-white/[.04] p-6">
                    <flux:heading size="sm" class="mb-4 text-zinc-400">{{ __('3. Add a note and save') }}</flux:heading>

                    <div class="flex flex-col gap-4">

                        {{-- Summary of changes: teller komt uit de computed property changedLines,
                             zodat view en save() exact dezelfde definitie van 'gewijzigd' hanteren --}}
                        @php $changedCount = $this->changedLines->count(); @endphp
                        @if ($changedCount > 0)
                            <div class="flex items-center gap-2 rounded-lg border border-amber-500/20 bg-amber-500/[.06] px-4 py-2.5 text-sm text-amber-300">
                                <flux:icon.pencil-square class="size-4 shrink-0" />
                                {{ trans_choice('{1} 1 line will be corrected.|[2,*] :count lines will be corrected.', $changedCount, ['count' => $changedCount]) }}
                            </div>
                        @else
                            <div class="flex items-center gap-2 rounded-lg border border-white/[.06] bg-white/[.02] px-4 py-2.5 text-sm text-zinc-500">
                                <flux:icon.check class="size-4 shrink-0" />
                                {{ __('No changes yet — edit quantities above to create corrections.') }}
                            </div>
                        @endif

                        <flux:field>
                            <flux:label>
                                {{ __('Reason / notes') }}
                                <span class="text-xs font-normal text-zinc-400">({{ __('optional') }})</span>
                            </flux:label>
                            <flux:textarea wire:model="notes" rows="2" placeholder="{{ __('e.g. Physical inventory count 09/05/2026') }}" />
                            <flux:error name="notes" />
                        </flux:field>

                        <div class="flex justify-end gap-3">
                            <flux:button :href="route('stock.index')" wire:navigate variant="ghost">
                                {{ __('Cancel') }}
                            </flux:button>
                            {{-- Knop blijft uitgeschakeld zolang er geen enkele afwijkende lijn is --}}
                            <flux:button
                                wire:click="save"
                                variant="primary"
                                icon="check"
                                :disabled="$changedCount === 0"
                            >
                                {{ __('Apply') }} {{ $changedCount > 0 ? "($changedCount)" : '' }} {{ __('Corrections') }}
                            </flux:button>
                        </div>

                    </div>
                </div>
            @endif

        @endif

    </div>

</div>
