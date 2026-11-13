<?php

use App\Enums\AnalysisStatus;
use App\Models\Activity;
use App\Services\MetricsService;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Grouping')] class extends Component {
    #[Computed]
    public function data(): array
    {
        $service = app(MetricsService::class);
        $analyzed = $service->activities()
            ->filter(fn (Activity $a) => $a->analysis?->status === AnalysisStatus::Success);

        return [
            'groups' => $service->groups($analyzed),
            'members' => $analyzed->groupBy(fn (Activity $a) => $a->group?->code ?? 'other'),
        ];
    }
};
?>

<div class="flex flex-col gap-6">
    <div>
        <flux:heading size="xl">Grouping aktivitas serupa</flux:heading>
        <flux:subheading>Aktivitas dengan sifat kerja sama dari karyawan/departemen berbeda dikelompokkan oleh AI</flux:subheading>
    </div>

    @forelse ($this->data['groups'] as $g)
        @php
            $members = $this->data['members'][$g['group_id']] ?? collect();
            $levels = $members->map(fn ($a) => $a->analysis->automation_level->value)->unique()->values();
        @endphp
        <section x-data="{ open: false }" wire:key="grp-{{ $g['group_id'] }}" class="rounded-xl border border-zinc-200 dark:border-zinc-700">
            <button type="button" class="flex w-full flex-wrap items-center gap-4 p-5 text-left" @click="open = !open">
                <div class="min-w-48">
                    <div class="font-semibold">{{ $g['label'] }}</div>
                    <div class="font-mono text-xs text-zinc-500">{{ $g['group_id'] }}</div>
                </div>
                <div class="text-sm">{{ $g['activity_count'] }} aktivitas</div>
                <div class="text-sm">{{ $g['employee_count'] }} karyawan · {{ $g['department_count'] }} departemen</div>
                <div class="text-sm">{{ number_format($g['fte'], 2, ',', '.') }} FTE</div>
                <div class="text-sm font-medium text-green-600">saving {{ number_format($g['saving'], 2, ',', '.') }} FTE</div>
                <div class="ml-auto flex gap-1">@foreach ($levels as $l) <x-level-badge :level="$l" /> @endforeach</div>
            </button>

            <div x-show="open" x-cloak class="border-t border-zinc-200 p-5 dark:border-zinc-700">
                <table class="w-full text-sm">
                    <thead class="text-left text-xs text-zinc-500"><tr><th class="py-1">ID</th><th>Aktivitas</th><th>Karyawan</th><th>Departemen</th><th>Level</th><th class="text-right">Saving FTE</th></tr></thead>
                    <tbody>
                        @foreach ($members as $a)
                            <tr class="border-t border-zinc-100 dark:border-zinc-800">
                                <td class="py-1 font-mono text-xs">{{ $a->activity_code }}</td>
                                <td>{{ $a->activity_name }}</td>
                                <td>{{ $a->employee->name }}</td>
                                <td>{{ $a->employee->department->name }}</td>
                                <td><x-level-badge :level="$a->analysis->automation_level" /></td>
                                <td class="text-right">{{ number_format((float) $a->estimated_fte_saving, 4, ',', '.') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>
    @empty
        <p class="text-zinc-500">Belum ada hasil grouping. Jalankan analisis terlebih dahulu.</p>
    @endforelse
</div>