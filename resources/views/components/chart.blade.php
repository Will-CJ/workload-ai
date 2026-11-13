@props(['config', 'height' => 260])

<div wire:ignore style="height: {{ $height }}px">
    <canvas x-data x-init="new Chart($el, @js($config))"></canvas>
</div>