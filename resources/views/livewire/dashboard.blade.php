<div class="flex h-full w-full flex-1 flex-col gap-6 p-6">

    {{-- Header --}}
    <div>
        <flux:heading size="xl">{{ __('Dashboard') }}</flux:heading>
        <flux:subheading>{{ __('Welcome back,') }} {{ auth()->user()->name }}</flux:subheading>
    </div>

    {{-- Stats Cards --}}
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <a href="{{ route('products.index') }}" wire:navigate>
            <flux:card class="flex items-center gap-4 transition hover:shadow-md">
                <div class="rounded-lg bg-blue-100 p-3 dark:bg-blue-900/30">
                    <flux:icon.archive-box class="size-6 text-blue-600 dark:text-blue-400" />
                </div>
                <div>
                    <flux:text class="text-sm text-zinc-500">{{ __('Products') }}</flux:text>
                    <flux:heading size="xl">{{ $this->stats['products'] }}</flux:heading>
                </div>
            </flux:card>
        </a>

        <a href="{{ route('categories.index') }}" wire:navigate>
            <flux:card class="flex items-center gap-4 transition hover:shadow-md">
                <div class="rounded-lg bg-purple-100 p-3 dark:bg-purple-900/30">
                    <flux:icon.tag class="size-6 text-purple-600 dark:text-purple-400" />
                </div>
                <div>
                    <flux:text class="text-sm text-zinc-500">{{ __('Categories') }}</flux:text>
                    <flux:heading size="xl">{{ $this->stats['categories'] }}</flux:heading>
                </div>
            </flux:card>
        </a>

        <a href="{{ route('warehouses.index') }}" wire:navigate>
            <flux:card class="flex items-center gap-4 transition hover:shadow-md">
                <div class="rounded-lg bg-amber-100 p-3 dark:bg-amber-900/30">
                    <flux:icon.building-office class="size-6 text-amber-600 dark:text-amber-400" />
                </div>
                <div>
                    <flux:text class="text-sm text-zinc-500">{{ __('Warehouses') }}</flux:text>
                    <flux:heading size="xl">{{ $this->stats['warehouses'] }}</flux:heading>
                </div>
            </flux:card>
        </a>

        <a href="{{ route('locations.index') }}" wire:navigate>
            <flux:card class="flex items-center gap-4 transition hover:shadow-md">
                <div class="rounded-lg bg-green-100 p-3 dark:bg-green-900/30">
                    <flux:icon.map-pin class="size-6 text-green-600 dark:text-green-400" />
                </div>
                <div>
                    <flux:text class="text-sm text-zinc-500">{{ __('Locations') }}</flux:text>
                    <flux:heading size="xl">{{ $this->stats['locations'] }}</flux:heading>
                </div>
            </flux:card>
        </a>
    </div>

    {{-- Bottom Grid --}}
    <div class="grid gap-6 lg:grid-cols-2">

        {{-- Low Stock Alerts --}}
        <flux:card class="flex flex-col gap-4">
            <div class="flex items-center justify-between">
                <flux:heading>{{ __('Low Stock Alerts') }}</flux:heading>
                @if($this->lowStockProducts->isNotEmpty())
                    <flux:badge variant="danger">{{ $this->lowStockProducts->count() }}</flux:badge>
                @endif
            </div>

            @if($this->lowStockProducts->isEmpty())
                <div class="flex flex-1 flex-col items-center justify-center py-8 text-center">
                    <flux:icon.check-circle class="mb-2 size-8 text-green-500" />
                    <flux:text class="text-zinc-500">{{ __('All products are sufficiently stocked.') }}</flux:text>
                </div>
            @else
                <div class="flex flex-col gap-2">
                    @foreach($this->lowStockProducts as $product)
                        <div class="flex items-center justify-between rounded-lg border border-red-200 bg-red-50 px-4 py-3 dark:border-red-900/40 dark:bg-red-900/10">
                            <div>
                                <flux:text class="font-medium text-zinc-800 dark:text-zinc-100">{{ $product->name }}</flux:text>
                                <flux:text class="text-xs text-zinc-500">{{ $product->category->name }} · SKU: {{ $product->sku }}</flux:text>
                            </div>
                            <div class="text-end">
                                <flux:badge variant="danger">{{ $product->totalStock() }} / {{ $product->min_stock }}</flux:badge>
                                <flux:text class="mt-0.5 text-xs text-zinc-400">{{ __('stock / min') }}</flux:text>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </flux:card>

        {{-- Recent Activity --}}
        <flux:card class="flex flex-col gap-4">
            <flux:heading>{{ __('Recent Activity') }}</flux:heading>

            @if($this->recentActivity->isEmpty())
                <div class="flex flex-1 flex-col items-center justify-center py-8 text-center">
                    <flux:icon.clock class="mb-2 size-8 text-zinc-400" />
                    <flux:text class="text-zinc-500">{{ __('No activity yet.') }}</flux:text>
                </div>
            @else
                <div class="flex flex-col gap-2">
                    @foreach($this->recentActivity as $activity)
                        <div class="flex items-start gap-3">
                            <flux:avatar
                                size="sm"
                                :name="$activity->causer?->name ?? 'System'"
                                :initials="$activity->causer?->initials() ?? 'SY'"
                                class="mt-0.5 shrink-0"
                            />
                            <div class="min-w-0 flex-1">
                                <flux:text class="text-sm">
                                    <span class="font-medium">{{ $activity->causer?->name ?? __('System') }}</span>
                                    {{ $activity->description }}
                                    <span class="font-medium">{{ class_basename($activity->subject_type ?? '') }}</span>
                                </flux:text>
                                <flux:text class="text-xs text-zinc-400">{{ $activity->created_at->diffForHumans() }}</flux:text>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </flux:card>

    </div>

</div>
