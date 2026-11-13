@props(['label', 'value', 'hint' => null])

<div class="rounded-xl border border-zinc-200 p-4 dark:border-zinc-700">
    <div class="text-sm text-zinc-500">{{ $label }}</div>
    <div class="mt-1 text-2xl font-semibold">{{ $value }}</div>
    @if ($hint)
        <div class="mt-1 text-xs text-zinc-500">{{ $hint }}</div>
    @endif
</div>