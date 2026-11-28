@props([
    'icon' => 'inbox-arrow-down',
    'heading',
    'description' => null,
])

<div {{ $attributes->merge(['class' => 'flex flex-col items-center justify-center rounded-xl border border-dashed border-zinc-300 px-6 py-16 text-center dark:border-zinc-700']) }}>
    <flux:icon :name="$icon" variant="outline" class="size-10 text-zinc-400 dark:text-zinc-500" />
    <flux:heading size="lg" class="mt-4">{{ $heading }}</flux:heading>
    @if ($description)
        <flux:text class="mt-2 max-w-md">{{ $description }}</flux:text>
    @endif
    @if (isset($action))
        <div class="mt-6">
            {{ $action }}
        </div>
    @endif
</div>
