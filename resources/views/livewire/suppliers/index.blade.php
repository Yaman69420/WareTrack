<div class="flex h-full w-full flex-1 flex-col gap-6 p-4 sm:p-6">

    {{-- Header --}}
    <div class="flex flex-wrap items-center justify-between gap-y-3">
        <div>
            <flux:heading size="xl">{{ __('Suppliers') }}</flux:heading>
            <flux:subheading>{{ __('Manage your product suppliers and their contact information') }}</flux:subheading>
        </div>
        @if(auth()->user()->isAdmin())
            <flux:button wire:click="openCreate" variant="primary" icon="plus">
                {{ __('New Supplier') }}
            </flux:button>
        @endif
    </div>

    {{-- Search --}}
    <div class="w-64">
        <flux:input
            wire:model.live.debounce.300ms="search"
            icon="magnifying-glass"
            placeholder="{{ __('Search by name or email...') }}"
        />
    </div>

    {{-- Table --}}
    <flux:table>
        <flux:table.columns>
            <flux:table.column>{{ __('Name') }}</flux:table.column>
            <flux:table.column>{{ __('Email') }}</flux:table.column>
            <flux:table.column>{{ __('Phone') }}</flux:table.column>
            <flux:table.column>{{ __('Created') }}</flux:table.column>
            @if(auth()->user()->isAdmin())
                <flux:table.column></flux:table.column>
            @endif
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($this->suppliers as $supplier)
                <flux:table.row :key="$supplier->id">
                    <flux:table.cell variant="strong">
                        <div class="flex items-center gap-3">
                            <flux:avatar size="sm" :name="$supplier->name" />
                            <span>{{ $supplier->name }}</span>
                        </div>
                    </flux:table.cell>

                    <flux:table.cell>
                        @if($supplier->email)
                            <a href="mailto:{{ $supplier->email }}" class="text-blue-600 hover:underline dark:text-blue-400">{{ $supplier->email }}</a>
                        @else
                            <span class="text-zinc-400">—</span>
                        @endif
                    </flux:table.cell>

                    <flux:table.cell>
                        {{ $supplier->phone ?? '—' }}
                    </flux:table.cell>

                    <flux:table.cell>
                        {{ $supplier->created_at->diffForHumans() }}
                    </flux:table.cell>

                    @if(auth()->user()->isAdmin())
                        <flux:table.cell align="end">
                            <flux:dropdown>
                                <flux:button icon="ellipsis-horizontal" variant="ghost" size="sm" />
                                <flux:menu>
                                    <flux:menu.item
                                        icon="pencil"
                                        wire:click="openEdit({{ $supplier->id }})"
                                    >
                                        {{ __('Edit') }}
                                    </flux:menu.item>
                                    <flux:menu.separator />
                                    <flux:menu.item
                                        icon="trash"
                                        variant="danger"
                                        wire:click="delete({{ $supplier->id }})"
                                        wire:confirm="{{ __('Delete this supplier?') }}"
                                    >
                                        {{ __('Delete') }}
                                    </flux:menu.item>
                                </flux:menu>
                            </flux:dropdown>
                        </flux:table.cell>
                    @endif
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="5" class="py-16 text-center">
                        <div class="flex flex-col items-center gap-2">
                            <flux:icon.building-storefront class="size-10 text-zinc-300" />
                            <span class="text-zinc-500">{{ $search ? __('No suppliers match your search.') : __('No suppliers yet.') }}</span>
                        </div>
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>

    {{-- Pagination --}}
    <div>
        {{ $this->suppliers->links() }}
    </div>

    {{-- Create / Edit Modal --}}
    <flux:modal wire:model="showModal" class="max-w-lg">
        <div class="flex flex-col gap-6 p-6">
            <flux:heading size="lg">
                {{ $editingId ? __('Edit Supplier') : __('New Supplier') }}
            </flux:heading>

            <div class="flex flex-col gap-4">
                <flux:field>
                    <flux:label>{{ __('Name') }}</flux:label>
                    <flux:input wire:model="name" placeholder="{{ __('e.g. Acme Corp') }}" />
                    <flux:error name="name" />
                </flux:field>

                <div class="grid grid-cols-2 gap-4">
                    <flux:field>
                        <flux:label>{{ __('Email') }} <span class="text-zinc-400 text-xs font-normal">({{ __('optional') }})</span></flux:label>
                        <flux:input wire:model="email" type="email" placeholder="info@supplier.com" />
                        <flux:error name="email" />
                    </flux:field>

                    <flux:field>
                        <flux:label>{{ __('Phone') }} <span class="text-zinc-400 text-xs font-normal">({{ __('optional') }})</span></flux:label>
                        <flux:input wire:model="phone" placeholder="+32 ..." />
                        <flux:error name="phone" />
                    </flux:field>
                </div>

                <flux:field>
                    <flux:label>{{ __('Address') }} <span class="text-zinc-400 text-xs font-normal">({{ __('optional') }})</span></flux:label>
                    <flux:textarea wire:model="address" rows="2" placeholder="{{ __('Street, City, Country') }}" />
                    <flux:error name="address" />
                </flux:field>

                <flux:field>
                    <flux:label>{{ __('Notes') }} <span class="text-zinc-400 text-xs font-normal">({{ __('optional') }})</span></flux:label>
                    <flux:textarea wire:model="notes" rows="2" placeholder="{{ __('Internal notes...') }}" />
                    <flux:error name="notes" />
                </flux:field>
            </div>

            {{-- Products --}}
            <div>
                <flux:label class="mb-2 block">{{ __('Products supplied') }} <span class="text-zinc-400 text-xs font-normal">({{ __('optional') }})</span></flux:label>
                <div class="max-h-48 overflow-y-auto rounded-lg border border-white/[.08] bg-white/[.02] p-3 flex flex-col gap-1.5">
                    @foreach ($this->allProducts as $product)
                        <label class="flex items-center gap-2 cursor-pointer select-none">
                            <flux:checkbox
                                wire:model.live="selectedProductIds"
                                value="{{ $product->id }}"
                            />
                            <span class="text-sm text-zinc-300">{{ $product->name }}</span>
                            <span class="font-mono text-xs text-zinc-500">{{ $product->sku }}</span>
                        </label>
                    @endforeach
                </div>
            </div>

            <div class="flex justify-end gap-3">
                <flux:button wire:click="$set('showModal', false)" variant="ghost">
                    {{ __('Cancel') }}
                </flux:button>
                <flux:button wire:click="save" variant="primary">
                    {{ $editingId ? __('Update') : __('Create') }}
                </flux:button>
            </div>
        </div>
    </flux:modal>

</div>
