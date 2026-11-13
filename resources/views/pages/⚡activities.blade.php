<?php

use App\Models\Activity;
use App\Models\ActivityGroup;
use App\Models\Department;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Aktivitas')] class extends Component {
    use WithPagination;

    #[Url] public string $search = '';
    #[Url] public string $department = '';
    #[Url] public string $level = '';
    #[Url] public string $group = '';
    #[Url] public string $status = '';

    public function updated($property): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function activities()
    {
        return Activity::query()
            ->with(['employee.department', 'group', 'analysis'])
            ->when($this->search, fn ($q) => $q->where(fn ($q) => $q
                ->where('activity_name', 'like', "%{$this->search}%")
                ->orWhere('activity_code', 'like', "%{$this->search}%")
                ->orWhereHas('employee', fn ($q) => $q->where('name', 'like', "%{$this->search}%"))))
            ->when($this->department, fn ($q) => $q->forDepartment((int) $this->department))
            ->when($this->group, fn ($q) => $q->whereHas('group', fn ($q) => $q->where('code', $this->group)))
            ->when($this->level, fn ($q) => $q->whereHas('analysis', fn ($q) => $q->where('automation_level', $this->level)))
            ->when($this->status, fn ($q) => $q->whereHas('analysis', fn ($q) => $q->where('status', $this->status)))
            ->orderBy('activity_code')
            ->paginate(15);
    }

    #[Computed]
    public function departments()
    {
        return Department::orderBy('name')->get();
    }

    #[Computed]
    public function groups()
    {
        return ActivityGroup::orderBy('label')->get();
    }
};
?>

<div class="flex flex-col gap-6">
    @php
        $select = 'rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-900';
        $fmt = fn ($n) => $n === null ? '—' : number_format((float) $n, 4, ',', '.');
    @endphp

    <div>
        <flux:heading size="xl">Aktivitas</flux:heading>
        <flux:subheading>Klik baris untuk melihat alasan dan rekomendasi AI</flux:subheading>
    </div>

    <div class="flex flex-wrap gap-2">
        <div class="w-64"><flux:input wire:model.live.debounce.300ms="search" placeholder="Cari aktivitas, ID, karyawan..." icon="magnifying-glass" /></div>
        <select wire:model.live="department" class="{{ $select }}">
            <option value="">Semua departemen</option>
            @foreach ($this->departments as $d) <option value="{{ $d->id }}">{{ $d->name }}</option> @endforeach
        </select>
        <select wire:model.live="group" class="{{ $select }}">
            <option value="">Semua grup</option>
            @foreach ($this->groups as $g) <option value="{{ $g->code }}">{{ $g->label }}</option> @endforeach
        </select>
        <select wire:model.live="level" class="{{ $select }}">
            <option value="">Semua level</option>
            <option value="high">High</option><option value="medium">Medium</option><option value="low">Low</option>
        </select>
        <select wire:model.live="status" class="{{ $select }}">
            <option value="">Semua status</option>
            <option value="success">Sukses</option><option value="failed">Gagal</option>
            <option value="pending">Pending</option><option value="processing">Proses</option>
        </select>
    </div>

    <div class="overflow-x-auto rounded-xl border border-zinc-200 dark:border-zinc-700">
        <table class="min-w-full text-sm">
            <thead class="bg-zinc-50 text-left text-xs text-zinc-500 dark:bg-zinc-800">
                <tr>
                    <th class="px-3 py-2">ID</th><th class="px-3 py-2">Aktivitas</th><th class="px-3 py-2">Karyawan</th>
                    <th class="px-3 py-2">Grup</th><th class="px-3 py-2">Level</th>
                    <th class="px-3 py-2 text-right">FTE</th><th class="px-3 py-2 text-right">Saving FTE</th><th class="px-3 py-2">Status</th>
                </tr>
            </thead>
            @forelse ($this->activities as $a)
                <tbody x-data="{ open: false }" wire:key="act-{{ $a->id }}" class="border-t border-zinc-200 dark:border-zinc-700">
                    <tr class="cursor-pointer hover:bg-zinc-50 dark:hover:bg-zinc-800/50" @click="open = !open">
                        <td class="px-3 py-2 font-mono text-xs">{{ $a->activity_code }}</td>
                        <td class="px-3 py-2">{{ $a->activity_name }}</td>
                        <td class="px-3 py-2">
                            {{ $a->employee->name }}
                            <div class="text-xs text-zinc-500">{{ $a->employee->department->name }}</div>
                        </td>
                        <td class="px-3 py-2 text-xs">{{ $a->group?->label ?? '—' }}</td>
                        <td class="px-3 py-2"><x-level-badge :level="$a->analysis?->automation_level" /></td>
                        <td class="px-3 py-2 text-right">{{ $fmt($a->fte) }}</td>
                        <td class="px-3 py-2 text-right text-green-600">{{ $fmt($a->estimated_fte_saving) }}</td>
                        <td class="px-3 py-2">
                            @php $st = $a->analysis?->status?->value ?? 'belum'; @endphp
                            <flux:badge size="sm" :color="match ($st) { 'success' => 'green', 'failed' => 'red', default => 'zinc' }">{{ $st }}</flux:badge>
                        </td>
                    </tr>
                    <tr x-show="open" x-cloak>
                        <td colspan="8" class="bg-zinc-50 px-3 py-3 text-sm dark:bg-zinc-800/50">
                            <p class="text-zinc-600 dark:text-zinc-400">{{ $a->description }}</p>
                            <p class="mt-1 text-xs text-zinc-500">
                                {{ $a->frequency->label() }} · volume {{ $a->volume }} × {{ $a->duration_minutes }} menit = {{ number_format($a->annual_minutes, 0, ',', '.') }} menit/tahun
                            </p>
                            @if ($a->analysis?->reason)
                                <p class="mt-2"><span class="font-medium">Alasan:</span> {{ $a->analysis->reason }}</p>
                                <p class="mt-1"><span class="font-medium">Rekomendasi:</span> {{ $a->analysis->recommendation }}</p>
                            @endif
                            @if ($a->analysis?->error)
                                <p class="mt-2 text-red-600">Error: {{ $a->analysis->error }}</p>
                            @endif
                        </td>
                    </tr>
                </tbody>
            @empty
                <tbody><tr><td colspan="8" class="px-3 py-6 text-center text-zinc-500">Tidak ada aktivitas.</td></tr></tbody>
            @endforelse
        </table>
    </div>

    {{ $this->activities->links() }}
</div>