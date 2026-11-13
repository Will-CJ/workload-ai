<?php

use App\Enums\SummaryScope;
use App\Models\Department;
use App\Services\MetricsService;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Departemen')] class extends Component {
    #[Computed]
    public function rows(): array
    {
        $service = app(MetricsService::class);

        return Department::with(['employees' => fn ($q) => $q->orderBy('name')])->orderBy('name')->get()
            ->map(fn (Department $d) => ['department' => $d]
                + $service->summarize($service->activities(SummaryScope::Department, $d->id), 0))
            ->all();
    }
};
?>

<div class="flex flex-col gap-6">
    <div>
        <flux:heading size="xl">Departemen & Karyawan</flux:heading>
        <flux:subheading>Klik departemen atau karyawan untuk melihat kesimpulan AI</flux:subheading>
    </div>

    <div class="grid gap-4 md:grid-cols-2">
        @forelse ($this->rows as $row)
            <section class="rounded-xl border border-zinc-200 p-5 dark:border-zinc-700" wire:key="dept-{{ $row['department']->id }}">
                <a href="{{ route('departments.show', $row['department']) }}" wire:navigate class="text-lg font-semibold hover:underline">
                    {{ $row['department']->name }}
                </a>
                <div class="mt-2 grid grid-cols-3 gap-2 text-sm">
                    <div><div class="text-xs text-zinc-500">Total FTE</div>{{ number_format($row['total_fte'], 2, ',', '.') }}</div>
                    <div><div class="text-xs text-zinc-500">Saving FTE</div><span class="text-green-600">{{ number_format($row['total_saving'], 2, ',', '.') }}</span></div>
                    <div><div class="text-xs text-zinc-500">% saving</div>{{ number_format($row['saving_percentage'], 1, ',', '.') }}%</div>
                </div>
                <div class="mt-4 flex flex-wrap gap-2">
                    @foreach ($row['department']->employees as $e)
                        <a href="{{ route('employees.show', $e) }}" wire:navigate
                           class="rounded-full bg-zinc-100 px-3 py-1 text-xs hover:bg-zinc-200 dark:bg-zinc-800 dark:hover:bg-zinc-700">{{ $e->name }}</a>
                    @endforeach
                </div>
            </section>
        @empty
            <p class="text-zinc-500">Belum ada data.</p>
        @endforelse
    </div>
</div>