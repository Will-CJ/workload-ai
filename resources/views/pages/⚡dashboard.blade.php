<?php

use App\Enums\SummaryScope;
use App\Models\AiSummary;
use App\Services\AnalysisPipeline;
use App\Services\MetricsService;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Dashboard')] class extends Component {
    #[Computed]
    public function metrics(): array
    {
        $service = app(MetricsService::class);
        $activities = $service->activities();

        return $service->summarize($activities) + ['departments' => $service->breakdown($activities, 'department')];
    }

    #[Computed]
    public function summary(): ?AiSummary
    {
        return AiSummary::for(SummaryScope::Global);
    }

    #[Computed]
    public function status(): array
    {
        return app(AnalysisPipeline::class)->statusCounts();
    }
};
?>

<div class="flex flex-col gap-6">
    @php
        $m = $this->metrics;
        $s = $this->status;
        $fmt = fn ($n, $d = 2) => number_format((float) $n, $d, ',', '.');

        $levelChart = [
            'type' => 'doughnut',
            'data' => [
                'labels' => ['High', 'Medium', 'Low'],
                'datasets' => [[
                    'data' => [$m['levels']['high']['count'], $m['levels']['medium']['count'], $m['levels']['low']['count']],
                    'backgroundColor' => ['#16a34a', '#f59e0b', '#a1a1aa'],
                ]],
            ],
            'options' => ['maintainAspectRatio' => false, 'plugins' => ['legend' => ['position' => 'bottom']]],
        ];

        $deptChart = [
            'type' => 'bar',
            'data' => [
                'labels' => array_column($m['departments'], 'name'),
                'datasets' => [
                    ['label' => 'Total FTE', 'data' => array_column($m['departments'], 'fte'), 'backgroundColor' => '#6366f1'],
                    ['label' => 'Potensi saving FTE', 'data' => array_column($m['departments'], 'saving'), 'backgroundColor' => '#16a34a'],
                ],
            ],
            'options' => ['maintainAspectRatio' => false, 'plugins' => ['legend' => ['position' => 'bottom']]],
        ];
    @endphp

    <div>
        <flux:heading size="xl">Dashboard</flux:heading>
        <flux:subheading>Ringkasan workload assessment dan potensi otomasi PT ABC</flux:subheading>
    </div>

    @if ($m['activity_count'] === 0)
        <div class="rounded-xl border border-dashed border-zinc-300 p-10 text-center dark:border-zinc-700">
            <p class="text-zinc-500">Belum ada data.</p>
            <flux:button class="mt-4" variant="primary" :href="route('import')" wire:navigate>Import dataset</flux:button>
        </div>
    @else
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <x-stat label="Total aktivitas" :value="$m['activity_count']" :hint="$m['analyzed_count'].' sudah dianalisis'" />
            <x-stat label="Total beban kerja" :value="$fmt($m['total_fte']).' FTE'" />
            <x-stat label="Potensi penghematan" :value="$fmt($m['total_saving']).' FTE'" :hint="$fmt($m['saving_percentage'], 1).'% dari total FTE'" />
            <x-stat label="Status analisis AI" :value="$s['success'].' sukses'" :hint="$s['failed'].' gagal · '.($s['pending'] + $s['processing']).' antri'" />
        </div>

        <div class="grid gap-4 lg:grid-cols-3">
            <section class="rounded-xl border border-zinc-200 p-5 dark:border-zinc-700">
                <flux:heading>Distribusi level otomasi</flux:heading>
                <div class="mt-4"><x-chart :config="$levelChart" /></div>
            </section>
            <section class="rounded-xl border border-zinc-200 p-5 lg:col-span-2 dark:border-zinc-700">
                <flux:heading>Perbandingan departemen</flux:heading>
                <div class="mt-4"><x-chart :config="$deptChart" /></div>
            </section>
        </div>

        <x-summary-card :summary="$this->summary" :priorities="$m['priorities']" title="Kesimpulan AI — PT ABC" />
    @endif
</div>