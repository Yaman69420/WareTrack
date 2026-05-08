<div class="flex h-full w-full flex-1 flex-col gap-6 p-6">

    {{-- Hero header --}}
    <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-blue-600 to-blue-800 px-6 py-8 text-white shadow-lg dark:from-blue-700 dark:to-blue-900">
        <div class="relative z-10">
            <p class="text-sm font-medium text-blue-200">{{ now()->format('l, j F Y') }}</p>
            <h1 class="mt-1 text-3xl font-bold">{{ __('Welcome back,') }} {{ auth()->user()->name }} 👋</h1>
            <p class="mt-1 text-blue-200">{{ __('Here\'s what\'s happening in your warehouses today.') }}</p>
        </div>
        {{-- Decorative circles --}}
        <div class="absolute -right-8 -top-8 size-40 rounded-full bg-white/5"></div>
        <div class="absolute -bottom-10 right-20 size-28 rounded-full bg-white/5"></div>
    </div>

    {{-- Stats grid --}}
    @php $isAdmin = auth()->user()->isAdmin(); @endphp
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6">

        {{-- Total stock (wide) --}}
        <div class="col-span-2 flex items-center gap-4 rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
            <div class="rounded-xl bg-emerald-50 p-3 dark:bg-emerald-900/30">
                <flux:icon.cube class="size-7 text-emerald-600 dark:text-emerald-400" />
            </div>
            <div>
                <p class="text-3xl font-bold text-zinc-900 dark:text-zinc-100">{{ number_format($this->stats['total_stock']) }}</p>
                <p class="text-sm text-zinc-500">{{ __('Items in stock') }}</p>
            </div>
        </div>

        {{-- Movements today (wide) --}}
        <div class="col-span-2 flex items-center gap-4 rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
            <div class="rounded-xl bg-blue-50 p-3 dark:bg-blue-900/30">
                <flux:icon.arrow-path class="size-7 text-blue-600 dark:text-blue-400" />
            </div>
            <div>
                <p class="text-3xl font-bold text-zinc-900 dark:text-zinc-100">{{ $this->stats['movements_today'] }}</p>
                <p class="text-sm text-zinc-500">{{ __('Movements today') }}</p>
            </div>
        </div>

        {{-- Low stock alert count (wide) --}}
        <div class="col-span-2 flex items-center gap-4 rounded-xl border {{ $this->lowStockProducts->isNotEmpty() ? 'border-red-200 bg-red-50 dark:border-red-900/40 dark:bg-red-900/10' : 'border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900' }} p-5">
            <div class="rounded-xl {{ $this->lowStockProducts->isNotEmpty() ? 'bg-red-100 dark:bg-red-900/30' : 'bg-zinc-100 dark:bg-zinc-800' }} p-3">
                <flux:icon.exclamation-triangle class="size-7 {{ $this->lowStockProducts->isNotEmpty() ? 'text-red-600 dark:text-red-400' : 'text-zinc-400' }}" />
            </div>
            <div>
                <p class="text-3xl font-bold {{ $this->lowStockProducts->isNotEmpty() ? 'text-red-700 dark:text-red-400' : 'text-zinc-900 dark:text-zinc-100' }}">{{ $this->lowStockProducts->count() }}</p>
                <p class="text-sm {{ $this->lowStockProducts->isNotEmpty() ? 'text-red-500' : 'text-zinc-500' }}">{{ __('Low stock alerts') }}</p>
            </div>
        </div>
    </div>

    {{-- Small counters --}}
    <div class="grid gap-4 sm:grid-cols-4">
        @if($isAdmin)
        <a href="{{ route('products.index') }}" wire:navigate class="group flex items-center gap-3 rounded-xl border border-zinc-200 bg-white p-4 transition hover:border-blue-300 hover:shadow-sm dark:border-zinc-700 dark:bg-zinc-900 dark:hover:border-blue-700">
            <div class="rounded-lg bg-blue-50 p-2 dark:bg-blue-900/30">
                <flux:icon.archive-box class="size-5 text-blue-600 dark:text-blue-400" />
            </div>
            <div>
                <p class="text-lg font-bold text-zinc-900 dark:text-zinc-100">{{ $this->stats['products'] }}</p>
                <p class="text-xs text-zinc-500">{{ __('Products') }}</p>
            </div>
        </a>
        <a href="{{ route('categories.index') }}" wire:navigate class="group flex items-center gap-3 rounded-xl border border-zinc-200 bg-white p-4 transition hover:border-violet-300 hover:shadow-sm dark:border-zinc-700 dark:bg-zinc-900 dark:hover:border-violet-700">
            <div class="rounded-lg bg-violet-50 p-2 dark:bg-violet-900/30">
                <flux:icon.tag class="size-5 text-violet-600 dark:text-violet-400" />
            </div>
            <div>
                <p class="text-lg font-bold text-zinc-900 dark:text-zinc-100">{{ $this->stats['categories'] }}</p>
                <p class="text-xs text-zinc-500">{{ __('Categories') }}</p>
            </div>
        </a>
        <a href="{{ route('warehouses.index') }}" wire:navigate class="group flex items-center gap-3 rounded-xl border border-zinc-200 bg-white p-4 transition hover:border-amber-300 hover:shadow-sm dark:border-zinc-700 dark:bg-zinc-900 dark:hover:border-amber-700">
            <div class="rounded-lg bg-amber-50 p-2 dark:bg-amber-900/30">
                <flux:icon.building-office class="size-5 text-amber-600 dark:text-amber-400" />
            </div>
            <div>
                <p class="text-lg font-bold text-zinc-900 dark:text-zinc-100">{{ $this->stats['warehouses'] }}</p>
                <p class="text-xs text-zinc-500">{{ __('Warehouses') }}</p>
            </div>
        </a>
        <a href="{{ route('locations.index') }}" wire:navigate class="group flex items-center gap-3 rounded-xl border border-zinc-200 bg-white p-4 transition hover:border-green-300 hover:shadow-sm dark:border-zinc-700 dark:bg-zinc-900 dark:hover:border-green-700">
            <div class="rounded-lg bg-green-50 p-2 dark:bg-green-900/30">
                <flux:icon.map-pin class="size-5 text-green-600 dark:text-green-400" />
            </div>
            <div>
                <p class="text-lg font-bold text-zinc-900 dark:text-zinc-100">{{ $this->stats['locations'] }}</p>
                <p class="text-xs text-zinc-500">{{ __('Locations') }}</p>
            </div>
        </a>
        @else
        <div class="flex items-center gap-3 rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
            <div class="rounded-lg bg-blue-50 p-2 dark:bg-blue-900/30">
                <flux:icon.archive-box class="size-5 text-blue-600 dark:text-blue-400" />
            </div>
            <div>
                <p class="text-lg font-bold text-zinc-900 dark:text-zinc-100">{{ $this->stats['products'] }}</p>
                <p class="text-xs text-zinc-500">{{ __('Products') }}</p>
            </div>
        </div>
        <div class="flex items-center gap-3 rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
            <div class="rounded-lg bg-violet-50 p-2 dark:bg-violet-900/30">
                <flux:icon.tag class="size-5 text-violet-600 dark:text-violet-400" />
            </div>
            <div>
                <p class="text-lg font-bold text-zinc-900 dark:text-zinc-100">{{ $this->stats['categories'] }}</p>
                <p class="text-xs text-zinc-500">{{ __('Categories') }}</p>
            </div>
        </div>
        <div class="flex items-center gap-3 rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
            <div class="rounded-lg bg-amber-50 p-2 dark:bg-amber-900/30">
                <flux:icon.building-office class="size-5 text-amber-600 dark:text-amber-400" />
            </div>
            <div>
                <p class="text-lg font-bold text-zinc-900 dark:text-zinc-100">{{ $this->stats['warehouses'] }}</p>
                <p class="text-xs text-zinc-500">{{ __('Warehouses') }}</p>
            </div>
        </div>
        <div class="flex items-center gap-3 rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
            <div class="rounded-lg bg-green-50 p-2 dark:bg-green-900/30">
                <flux:icon.map-pin class="size-5 text-green-600 dark:text-green-400" />
            </div>
            <div>
                <p class="text-lg font-bold text-zinc-900 dark:text-zinc-100">{{ $this->stats['locations'] }}</p>
                <p class="text-xs text-zinc-500">{{ __('Locations') }}</p>
            </div>
        </div>
        @endif
    </div>

    {{-- Bottom grid --}}
    <div class="grid gap-6 lg:grid-cols-2">

        {{-- Low Stock Alerts --}}
        <div class="flex flex-col gap-4 rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <flux:icon.exclamation-triangle class="size-5 text-red-500" />
                    <flux:heading>{{ __('Low Stock Alerts') }}</flux:heading>
                </div>
                @if($this->lowStockProducts->isNotEmpty())
                    <flux:badge variant="danger">{{ $this->lowStockProducts->count() }}</flux:badge>
                @endif
            </div>

            @if($this->lowStockProducts->isEmpty())
                <div class="flex flex-1 flex-col items-center justify-center py-10 text-center">
                    <div class="mb-3 rounded-full bg-green-50 p-3 dark:bg-green-900/20">
                        <flux:icon.check-circle class="size-8 text-green-500" />
                    </div>
                    <flux:text class="text-zinc-500">{{ __('All products are sufficiently stocked.') }}</flux:text>
                </div>
            @else
                <div class="flex flex-col gap-2">
                    @foreach($this->lowStockProducts as $product)
                        <div class="flex items-center justify-between rounded-lg border border-red-100 bg-red-50 px-4 py-3 dark:border-red-900/30 dark:bg-red-900/10">
                            <div class="min-w-0">
                                <p class="truncate font-medium text-zinc-900 dark:text-zinc-100">{{ $product->name }}</p>
                                <p class="text-xs text-zinc-400">{{ $product->category->name }} · {{ $product->sku }}</p>
                            </div>
                            <div class="ml-3 shrink-0 text-end">
                                <span class="text-lg font-bold text-red-600 dark:text-red-400">{{ $product->totalStock() }}</span>
                                <span class="text-sm text-zinc-400"> / {{ $product->min_stock }}</span>
                                <p class="text-xs text-zinc-400">{{ __('stock / min') }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>
                @if($isAdmin)
                    <flux:button :href="route('stock.movements.create')" wire:navigate variant="filled" size="sm" icon="plus" class="mt-1 self-start">
                        {{ __('Register Incoming') }}
                    </flux:button>
                @endif
            @endif
        </div>

        {{-- Recent Activity --}}
        <div class="flex flex-col gap-4 rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
            <div class="flex items-center gap-2">
                <flux:icon.clock class="size-5 text-zinc-400" />
                <flux:heading>{{ __('Recent Activity') }}</flux:heading>
            </div>

            @if($this->recentActivity->isEmpty())
                <div class="flex flex-1 flex-col items-center justify-center py-10 text-center">
                    <div class="mb-3 rounded-full bg-zinc-50 p-3 dark:bg-zinc-800">
                        <flux:icon.clock class="size-8 text-zinc-400" />
                    </div>
                    <flux:text class="text-zinc-500">{{ __('No activity yet.') }}</flux:text>
                </div>
            @else
                <div class="flex flex-col divide-y divide-zinc-100 dark:divide-zinc-800">
                    @foreach($this->recentActivity as $activity)
                        <div class="flex items-start gap-3 py-3 first:pt-0 last:pb-0">
                            <flux:avatar
                                size="sm"
                                :name="$activity->causer?->name ?? 'System'"
                                class="mt-0.5 shrink-0"
                            />
                            <div class="min-w-0 flex-1">
                                <p class="text-sm text-zinc-700 dark:text-zinc-300">
                                    <span class="font-semibold">{{ $activity->causer?->name ?? __('System') }}</span>
                                    <span class="text-zinc-500"> {{ $activity->description }} </span>
                                    <span class="font-medium text-zinc-600 dark:text-zinc-400">{{ class_basename($activity->subject_type ?? '') }}</span>
                                </p>
                                <p class="text-xs text-zinc-400">{{ $activity->created_at->diffForHumans() }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

    </div>

</div>
