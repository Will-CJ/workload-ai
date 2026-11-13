@props(['level' => null])

@php
    $value = $level instanceof \App\Enums\AutomationLevel ? $level->value : $level;
    $color = match ($value) { 'high' => 'green', 'medium' => 'amber', default => 'zinc' };
@endphp

<flux:badge size="sm" :color="$color">{{ $value ? ucfirst($value) : '—' }}</flux:badge>