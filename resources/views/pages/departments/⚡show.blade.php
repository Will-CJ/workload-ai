<?php

use App\Enums\SummaryScope;
use App\Models\AiSummary;
use App\Models\Department;
use App\Services\MetricsService;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component {
    public Department $department;

    #[Computed]
    public function metrics(): array
    {
        $service = app(MetricsService::class);
        $activities = $service->activities(SummaryScope::Department, $this->department->id);

        $employees = $activities->groupBy('employee_id')->map(function ($items) {
            $fte = round($items->sum(fn ($a) => (float) $a->fte), 4);
            $saving = round($items->sum(fn ($a) => (float) $a->estimated_fte_saving), 4);

            return [
                'employee' => $items->first()->employee,
                'count' => $items->count(),
                'fte' => $fte,
                'saving' => $saving,
                'percentage' => $fte > 0 ? round($saving / $fte * 100, 1) : 0,
            ];
        })->sortByDesc('saving')->values()->all();

        return $service->summarize($activities) + ['employees' => $employees];
    }

    #[Computed]
    public function summary(): ?AiSummary
    {
        return AiSummary::for(SummaryScope::Department, $this->department->id);
    }

    public function render()
    {
        return $this->view()->title('Departemen '.$this->department->name);
    }
};
?>

<div class="flex flex-col gap-6">
    @php $m = $this->metrics; $fmt = fn ($n, $d = 2) => number_format((float) $n, $d, ',', '.'); @endphp

    <div>
        <a href="{{ route('departments.index') }}" wire:navigate class="text-sm text-zinc-500 hover:underline">← Semua departemen</a>
        <flux:heading size="xl" class="mt-1">{{ $department->name }}</flux:heading>
    </div>

    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <x-stat label="Karyawan" :value="count($m['employees'])" />
        <x-stat label="Aktivitas" :value="$m['activity_count']" />
        <x-stat label="Total FTE" :value="$fmt($m['total_fte'])" />
        <x-stat label="Potensi saving" :value="$fmt($m['total_saving']).' FTE'" :hint="$fmt($m['saving_percentage'], 1).'%'" />
    </div>

    <x-summary-card :summary="$this->summary" :priorities="$m['priorities']" :title="'Kesimpulan AI — '.$department->name" />

    <section class="rounded-xl border border-zinc-200 p-5 dark:border-zinc-700">
        <flux:heading>Karyawan</flux:heading>
        <table class="mt-3 w-full text-sm">
            <thead class="text-left text-xs text-zinc-500"><tr><th class="py-2">Nama</th><th class="text-right">Aktivitas</th><th class="text-right">FTE</th><th class="text-right">Saving FTE</th><th class="text-right">%</th></tr></thead>
            <tbody>
                @foreach ($m['employees'] as $row)
                    <tr class="border-t border-zinc-200 dark:border-zinc-700">
                        <td class="py-2"><a href="{{ route('employees.show', $row['employee']) }}" wire:navigate class="hover:underline">{{ $row['employee']->name }}</a></td>
                        <td class="text-right">{{ $row['count'] }}</td>
                        <td class="text-right">{{ $fmt($row['fte']) }}</td>
                        <td class="text-right text-green-600">{{ $fmt($row['saving']) }}</td>
                        <td class="text-right">{{ $fmt($row['percentage'], 1) }}%</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </section>
</div>