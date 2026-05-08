@props([
    'title',
    'description',
])

<div class="flex w-full flex-col gap-1">
    <h1 class="text-2xl font-bold tracking-tight text-zinc-900 dark:text-zinc-100">{{ $title }}</h1>
    <p class="text-sm text-zinc-500">{{ $description }}</p>
</div>
