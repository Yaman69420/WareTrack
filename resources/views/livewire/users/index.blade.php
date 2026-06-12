<div class="flex h-full w-full flex-1 flex-col gap-6 p-4 sm:p-6">

    {{-- Header --}}
    <div class="flex flex-wrap items-center justify-between gap-y-3">
        <flux:heading size="xl">{{ __('Users') }}</flux:heading>
        <flux:button wire:click="openCreate" variant="primary" icon="plus">
            {{ __('New User') }}
        </flux:button>
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
            <flux:table.column>{{ __('Role') }}</flux:table.column>
            <flux:table.column>{{ __('Created') }}</flux:table.column>
            <flux:table.column></flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($this->users as $user)
                <flux:table.row :key="$user->id">
                    <flux:table.cell>
                        <div class="flex items-center gap-3">
                            <flux:avatar size="sm" :name="$user->name" :initials="$user->initials()" />
                            <span class="font-medium">{{ $user->name }}</span>
                            @if($user->id === auth()->id())
                                <flux:badge size="sm" variant="outline">{{ __('You') }}</flux:badge>
                            @endif
                        </div>
                    </flux:table.cell>

                    <flux:table.cell>
                        {{ $user->email }}
                    </flux:table.cell>

                    <flux:table.cell>
                        <flux:badge variant="{{ $user->isAdmin() ? 'primary' : 'outline' }}">
                            {{ $user->isAdmin() ? __('Admin') : __('Warehouse Worker') }}
                        </flux:badge>
                    </flux:table.cell>

                    <flux:table.cell class="text-sm text-zinc-400">
                        {{ $user->created_at->diffForHumans() }}
                    </flux:table.cell>

                    <flux:table.cell align="end">
                        <flux:dropdown>
                            <flux:button icon="ellipsis-horizontal" variant="ghost" size="sm" />
                            <flux:menu>
                                <flux:menu.item icon="pencil" wire:click="openEdit({{ $user->id }})">
                                    {{ __('Edit') }}
                                </flux:menu.item>
                                @if($user->id !== auth()->id())
                                    <flux:menu.separator />
                                    <flux:menu.item
                                        icon="trash"
                                        variant="danger"
                                        wire:click="delete({{ $user->id }})"
                                        wire:confirm="{{ __('Delete this user?') }}"
                                    >
                                        {{ __('Delete') }}
                                    </flux:menu.item>
                                @endif
                            </flux:menu>
                        </flux:dropdown>
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="5" class="py-12 text-center">
                        {{ $search ? __('No users match your search.') : __('No users found.') }}
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>

    <div>{{ $this->users->links() }}</div>

    {{-- Create / Edit Modal --}}
    <flux:modal wire:model="showModal" class="max-w-lg">
        <div class="flex flex-col gap-6 p-6">
            <flux:heading size="lg">
                {{ $editingId ? __('Edit User') : __('New User') }}
            </flux:heading>

            <div class="flex flex-col gap-4">
                <flux:field>
                    <flux:label>{{ __('Name') }}</flux:label>
                    <flux:input wire:model="name" placeholder="Jan Janssen" />
                    <flux:error name="name" />
                </flux:field>

                <flux:field>
                    <flux:label>{{ __('Email') }}</flux:label>
                    <flux:input wire:model="email" type="email" placeholder="jan@waretrack.test" />
                    <flux:error name="email" />
                </flux:field>

                <flux:field>
                    <flux:label>
                        {{ __('Password') }}
                        @if($editingId)
                            <span class="text-zinc-400 text-xs font-normal">({{ __('leave blank to keep current') }})</span>
                        @endif
                    </flux:label>
                    <flux:input wire:model="password" type="password" placeholder="••••••••" />
                    <flux:error name="password" />
                </flux:field>

                {{-- Role — native select for reliable Livewire binding --}}
                <div class="flex flex-col gap-1">
                    <flux:label>{{ __('Role') }}</flux:label>
                    <select
                        wire:model="role"
                        class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 dark:border-white/10 dark:bg-white/5 dark:text-zinc-100 dark:focus:border-blue-400"
                    >
                        <option value="">{{ __('Select role…') }}</option>
                        @foreach ($this->roles as $roleOption)
                            <option value="{{ $roleOption->value }}">
                                {{ $roleOption === \App\Enums\UserRole::Admin ? __('Admin') : __('Warehouse Worker') }}
                            </option>
                        @endforeach
                    </select>
                    <flux:error name="role" />
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
