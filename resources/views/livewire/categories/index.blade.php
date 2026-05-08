<div class="flex h-full w-full flex-1 flex-col gap-6 p-6">

    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl">{{ __('Categories') }}</flux:heading>
            <flux:subheading>{{ __('Organise your products into logical groups') }}</flux:subheading>
        </div>
        <flux:button wire:click="openCreate" variant="primary" icon="plus">
            {{ __('New Category') }}
        </flux:button>
    </div>

    {{-- Search --}}
    <div class="max-w-sm">
        <flux:input
            wire:model.live.debounce.300ms="search"
            icon="magnifying-glass"
            placeholder="{{ __('Search categories...') }}"
        />
    </div>

    {{-- Table --}}
    <flux:table>
        <flux:table.columns>
            <flux:table.column>{{ __('Name') }}</flux:table.column>
            <flux:table.column>{{ __('Description') }}</flux:table.column>
            <flux:table.column>{{ __('Products') }}</flux:table.column>
            <flux:table.column>{{ __('Created') }}</flux:table.column>
            <flux:table.column></flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($this->categories as $category)
                <flux:table.row :key="$category->id">
                    <flux:table.cell variant="strong">
                        <div class="flex items-center gap-2.5">
                            <div class="rounded-md bg-violet-50 p-1.5 dark:bg-violet-900/30">
                                <flux:icon.tag class="size-4 text-violet-600 dark:text-violet-400" />
                            </div>
                            {{ $category->name }}
                        </div>
                    </flux:table.cell>

                    <flux:table.cell>
                        <span class="text-zinc-500">{{ $category->description ?? '—' }}</span>
                    </flux:table.cell>

                    <flux:table.cell>
                        <flux:badge variant="{{ $category->products_count > 0 ? 'outline' : 'ghost' }}">
                            {{ $category->products_count }} {{ __('products') }}
                        </flux:badge>
                    </flux:table.cell>

                    <flux:table.cell>
                        {{ $category->created_at->diffForHumans() }}
                    </flux:table.cell>

                    <flux:table.cell align="end">
                        <flux:dropdown>
                            <flux:button icon="ellipsis-horizontal" variant="ghost" size="sm" />
                            <flux:menu>
                                <flux:menu.item
                                    icon="pencil"
                                    wire:click="openEdit({{ $category->id }})"
                                >
                                    {{ __('Edit') }}
                                </flux:menu.item>
                                <flux:menu.separator />
                                <flux:menu.item
                                    icon="trash"
                                    variant="danger"
                                    wire:click="delete({{ $category->id }})"
                                    wire:confirm="{{ __('Delete this category?') }}"
                                >
                                    {{ __('Delete') }}
                                </flux:menu.item>
                            </flux:menu>
                        </flux:dropdown>
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="5" class="py-16 text-center">
                        <div class="flex flex-col items-center gap-2">
                            <flux:icon.tag class="size-10 text-zinc-300" />
                            <span class="text-zinc-500">{{ $search ? __('No categories match your search.') : __('No categories yet.') }}</span>
                        </div>
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>

    {{-- Pagination --}}
    <div>
        {{ $this->categories->links() }}
    </div>

    {{-- Create / Edit Modal --}}
    <flux:modal wire:model="showModal" class="max-w-md">
        <div class="flex flex-col gap-6 p-6">
            <flux:heading size="lg">
                {{ $editingId ? __('Edit Category') : __('New Category') }}
            </flux:heading>

            <flux:field>
                <flux:label>{{ __('Name') }}</flux:label>
                <flux:input wire:model="name" placeholder="{{ __('e.g. Electronics') }}" />
                <flux:error name="name" />
            </flux:field>

            <flux:field>
                <flux:label>{{ __('Description') }} <span class="text-zinc-400 text-xs font-normal">({{ __('optional') }})</span></flux:label>
                <flux:textarea wire:model="description" rows="3" placeholder="{{ __('Short description...') }}" />
                <flux:error name="description" />
            </flux:field>

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
