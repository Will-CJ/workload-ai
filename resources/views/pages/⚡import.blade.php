<?php

use App\Models\ImportBatch;
use App\Services\AnalysisPipeline;
use App\Services\WorkloadImportService;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithFileUploads;

new #[Title('Import & Analisis')] class extends Component {
    use WithFileUploads;

    #[Validate('required|file|mimes:xlsx,xls|max:5120')]
    public $file;

    public ?int $lastBatchId = null;
    public ?string $notice = null;

    public function import(): void
    {
        $this->validate();

        $path = $this->file->store('imports');
        $batch = app(WorkloadImportService::class)->import(
            Storage::path($path),
            $this->file->getClientOriginalName(),
            auth()->id(),
            $path,
        );

        $this->lastBatchId = $batch->id;
        $this->reset('file');
        $this->notice = null;
    }

    public function analyze(): void
    {
        $this->runPipeline(false);
    }

    public function reanalyze(): void
    {
        $this->runPipeline(true);
    }

    private function runPipeline(bool $force): void
    {
        $pipeline = app(AnalysisPipeline::class);

        if ($pipeline->isRunning()) {
            $this->notice = 'Analisis masih berjalan, tunggu sampai selesai.';

            return;
        }

        $result = $pipeline->run($force);

        $this->notice = $result['queued'] > 0
            ? "{$result['queued']} aktivitas masuk antrian analisis, {$result['cached']} diambil dari cache."
            : "Semua {$result['cached']} aktivitas sudah dianalisis (dari cache). Kesimpulan diperbarui.";
    }

    #[Computed]
    public function lastBatch(): ?ImportBatch
    {
        return $this->lastBatchId ? ImportBatch::find($this->lastBatchId) : null;
    }

    #[Computed]
    public function batches()
    {
        return ImportBatch::with('user')->latest()->limit(5)->get();
    }

    #[Computed]
    public function status(): array
    {
        return app(AnalysisPipeline::class)->statusCounts();
    }

    #[Computed]
    public function running(): bool
    {
        return app(AnalysisPipeline::class)->isRunning();
    }
};
?>

<div class="flex flex-col gap-6">
    <div>
        <flux:heading size="xl">Import & Analisis</flux:heading>
        <flux:subheading>Upload hasil workload assessment (Excel), lalu jalankan analisis AI</flux:subheading>
    </div>

    <div class="grid gap-6 lg:grid-cols-2">
        {{-- Upload --}}
        <section class="rounded-xl border border-zinc-200 p-5 dark:border-zinc-700">
            <flux:heading>1. Upload dataset</flux:heading>
            <p class="mt-1 text-xs text-zinc-500">
                Kolom wajib: activity_id, employee_id, employee_name, department, activity_name, description, frequency, volume, duration_minutes
            </p>

            <form wire:submit="import" class="mt-4 space-y-3">
                <input type="file" wire:model="file" accept=".xlsx,.xls"
                       class="block w-full text-sm file:mr-4 file:rounded-md file:border-0 file:bg-zinc-100 file:px-4 file:py-2 dark:file:bg-zinc-800">
                @error('file') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
                <div wire:loading wire:target="file" class="text-sm text-zinc-500">Mengunggah file...</div>

                <flux:button type="submit" variant="primary" wire:loading.attr="disabled" wire:target="import,file">Import</flux:button>
            </form>

            @if ($batch = $this->lastBatch)
                <div class="mt-5 rounded-lg bg-zinc-50 p-4 text-sm dark:bg-zinc-800/50">
                    <div class="font-medium">{{ $batch->original_name }} — {{ str_replace('_', ' ', $batch->status->value) }}</div>
                    <div class="mt-1 text-zinc-600 dark:text-zinc-400">
                        {{ $batch->total_rows }} baris · <span class="text-green-600">{{ $batch->success_rows }} sukses</span> ·
                        <span class="text-red-600">{{ $batch->failed_rows }} gagal</span>
                    </div>

                    @if ($batch->errors)
                        <table class="mt-3 w-full text-xs">
                            <thead class="text-left text-zinc-500"><tr><th class="py-1">Baris</th><th>activity_id</th><th>Error</th></tr></thead>
                            <tbody>
                                @foreach ($batch->errors as $error)
                                    <tr class="border-t border-zinc-200 align-top dark:border-zinc-700">
                                        <td class="py-1">{{ $error['row'] ?? '-' }}</td>
                                        <td>{{ $error['activity_id'] ?? '-' }}</td>
                                        <td class="text-red-600">{{ implode(' ', $error['messages']) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endif
                </div>
            @endif
        </section>

        {{-- Analisis --}}
        <section class="rounded-xl border border-zinc-200 p-5 dark:border-zinc-700" @if ($this->running) wire:poll.3s @endif>
            <flux:heading>2. Analisis AI</flux:heading>
            @php
                $s = $this->status;
                $total = array_sum($s);
                $percent = $total > 0 ? round($s['success'] / $total * 100) : 0;
            @endphp

            <div class="mt-4 grid grid-cols-4 gap-2 text-center text-sm">
                <div class="rounded-lg bg-zinc-50 p-2 dark:bg-zinc-800/50"><div class="text-lg font-semibold">{{ $s['pending'] }}</div>pending</div>
                <div class="rounded-lg bg-zinc-50 p-2 dark:bg-zinc-800/50"><div class="text-lg font-semibold">{{ $s['processing'] }}</div>proses</div>
                <div class="rounded-lg bg-green-50 p-2 text-green-700 dark:bg-green-900/20"><div class="text-lg font-semibold">{{ $s['success'] }}</div>sukses</div>
                <div class="rounded-lg bg-red-50 p-2 text-red-700 dark:bg-red-900/20"><div class="text-lg font-semibold">{{ $s['failed'] }}</div>gagal</div>
            </div>

            <div class="mt-4 h-2 w-full overflow-hidden rounded-full bg-zinc-200 dark:bg-zinc-700">
                <div class="h-2 bg-green-600 transition-all" style="width: {{ $percent }}%"></div>
            </div>
            <div class="mt-1 text-xs text-zinc-500">{{ $percent }}% selesai @if ($this->running) · memperbarui otomatis... @endif</div>

            <div class="mt-4 flex flex-wrap gap-2">
                <flux:button variant="primary" wire:click="analyze" :disabled="$this->running">
                    {{ $s['failed'] > 0 ? 'Retry yang gagal' : 'Jalankan analisis' }}
                </flux:button>
                <flux:button wire:click="reanalyze" :disabled="$this->running"
                             wire:confirm="Analisis ulang semua aktivitas tanpa cache? Ini memakai kuota API.">
                    Analisis ulang (abaikan cache)
                </flux:button>
            </div>

            @if ($notice)
                <p class="mt-3 text-sm text-zinc-600 dark:text-zinc-400">{{ $notice }}</p>
            @endif
            <p class="mt-3 text-xs text-zinc-500">Pastikan queue worker berjalan: <code>php artisan queue:work</code></p>
        </section>
    </div>

    {{-- Riwayat --}}
    <section class="rounded-xl border border-zinc-200 p-5 dark:border-zinc-700">
        <flux:heading>Riwayat import</flux:heading>
        <table class="mt-3 w-full text-sm">
            <thead class="text-left text-xs text-zinc-500"><tr><th class="py-2">Waktu</th><th>File</th><th>Oleh</th><th>Status</th><th class="text-right">Sukses / Total</th></tr></thead>
            <tbody>
                @forelse ($this->batches as $b)
                    <tr class="border-t border-zinc-200 dark:border-zinc-700">
                        <td class="py-2">{{ $b->created_at->format('d M Y H:i') }}</td>
                        <td>{{ $b->original_name }}</td>
                        <td>{{ $b->user?->name ?? 'CLI' }}</td>
                        <td>{{ str_replace('_', ' ', $b->status->value) }}</td>
                        <td class="text-right">{{ $b->success_rows }} / {{ $b->total_rows }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="py-3 text-zinc-500">Belum ada import.</td></tr>
                @endforelse
            </tbody>
        </table>
    </section>
</div>