@props([
    'title',
    'description',
    'level' => 2,
])

<div class="flex w-full flex-col text-center">
    <flux:heading size="xl" :level="$level">{{ $title }}</flux:heading>
    <flux:subheading>{{ $description }}</flux:subheading>
</div>
