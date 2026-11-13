<?php

use App\Enums\SummaryScope;
use App\Models\AiSummary;
use App\Models\Employee;
use App\Services\MetricsService;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component {
    public Employee $employee;

    #[Computed]
    public function activities()
    {
        return app(MetricsService::class)->activities(SummaryScope::Employee, $this->employee->id);
    }

    #[Computed]
    public function metrics(): array
    {
        return app(MetricsService::class)->summarize($this->activities);
    }

    #[Computed]
    public function summary(): ?AiSummary
    {
        return AiSummary::for(SummaryScope::Employee, $this->employee->id);
    }

    public function render()
    {
        return $this->view()->title($this->employee->name);
    }
};
?>

<div class="flex flex-col gap-6">
    @php $m = $this->metrics; $fmt = fn ($n, $d = 2) => number_format((float) $n, $d, ',', '.'); @endphp

    <div>
        <a href="{{ route('departments.show', $employee->department) }}" wire:navigate class="text-sm text-zinc-500 hover:underline">← {{ $employee->department->name }}</a>
        <flux:heading size="xl" class="mt-1">{{ $employee->name }}</flux:heading>
        <flux:subheading>{{ $employee->employee_code }} · {{ $employee->department->name }}</flux:subheading>
    </div>

    <div class="grid gap-4 sm:grid-cols-3">
        <x-stat label="Aktivitas" :value="$m['activity_count']" />
        <x-stat label="Total FTE" :value="$fmt($m['total_fte'])" />
        <x-stat label="Potensi saving" :value="$fmt($m['total_saving']).' FTE'" :hint="$fmt($m['saving_percentage'], 1).'%'" />
    </div>

    <x-summary-card :summary="$this->summary" :priorities="$m['priorities']" :title="'Kesimpulan AI — '.$employee->name" />

    <section class="rounded-xl border border-zinc-200 p-5 dark:border-zinc-700">
        <flux:heading>Daftar aktivitas</flux:heading>
        <table class="mt-3 w-full text-sm">
            <thead class="text-left text-xs text-zinc-500"><tr><th class="py-2">ID</th><th>Aktivitas</th><th>Grup</th><th>Level</th><th class="text-right">FTE</th><th class="text-right">Saving FTE</th></tr></thead>
            <tbody>
                @foreach ($this->activities as $a)
                    <tr class="border-t border-zinc-200 align-top dark:border-zinc-700">
                        <td class="py-2 font-mono text-xs">{{ $a->activity_code }}</td>
                        <td>
                            {{ $a->activity_name }}
                            @if ($a->analysis?->recommendation)
                                <div class="text-xs text-zinc-500">{{ $a->analysis->recommendation }}</div>
                            @endif
                        </td>
                        <td class="text-xs">{{ $a->group?->label ?? '—' }}</td>
                        <td><x-level-badge :level="$a->analysis?->automation_level" /></td>
                        <td class="text-right">{{ $fmt($a->fte, 4) }}</td>
                        <td class="text-right text-green-600">{{ $a->estimated_fte_saving === null ? '—' : $fmt($a->estimated_fte_saving, 4) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </section>
</div>