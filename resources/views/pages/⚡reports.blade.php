<?php

use App\Services\AnalysisPipeline;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Laporan & Export')] class extends Component {
    #[Computed]
    public function analysis(): array
    {
        return app(AnalysisPipeline::class)->statusCounts();
    }

    #[Computed]
    public function summaries(): array
    {
        return DB::table('ai_summaries')->selectRaw('status, count(*) as total')
            ->groupBy('status')->pluck('total', 'status')->all();
    }
};
?>

<div class="flex flex-col gap-6">
    @php
        $a = $this->analysis;
        $incomplete = $a['pending'] + $a['processing'] + $a['failed'];
    @endphp

    <div>
        <flux:heading size="xl">Laporan & Export</flux:heading>
        <flux:subheading>Unduh hasil analisis. Export hanya membaca data tersimpan dan tidak memanggil AI.</flux:subheading>
    </div>

    @if ($incomplete > 0)
        <div class="rounded-xl border border-amber-300 bg-amber-50 p-4 text-sm text-amber-800 dark:border-amber-700 dark:bg-amber-900/20 dark:text-amber-200">
            {{ $incomplete }} aktivitas belum berhasil dianalisis. Aktivitas tersebut tetap ikut di export dengan status <code>failed</code>.
            Selesaikan atau retry dulu di halaman <a href="{{ route('import') }}" wire:navigate class="underline">Import & Analisis</a> jika ingin hasil lengkap.
        </div>
    @endif

    <div class="grid gap-4 md:grid-cols-3">
        <section class="rounded-xl border border-zinc-200 p-5 dark:border-zinc-700">
            <flux:heading>Laporan PDF</flux:heading>
            <p class="mt-1 text-sm text-zinc-500">Ringkasan, prioritas, per departemen, grup, per karyawan, dan lampiran aktivitas.</p>
            <flux:button class="mt-4" variant="primary" icon="document-arrow-down" :href="route('exports.pdf')">Unduh PDF</flux:button>
        </section>
        <section class="rounded-xl border border-zinc-200 p-5 dark:border-zinc-700">
            <flux:heading>Laporan Excel</flux:heading>
            <p class="mt-1 text-sm text-zinc-500">5 sheet: Ringkasan, Aktivitas, Grup, Departemen, Karyawan.</p>
            <flux:button class="mt-4" variant="primary" icon="table-cells" :href="route('exports.excel')">Unduh Excel</flux:button>
        </section>
        <section class="rounded-xl border border-zinc-200 p-5 dark:border-zinc-700">
            <flux:heading>results.json</flux:heading>
            <p class="mt-1 text-sm text-zinc-500">Hasil per aktivitas dalam format yang diminta soal.</p>
            <flux:button class="mt-4" variant="primary" icon="code-bracket" :href="route('exports.json')">Unduh JSON</flux:button>
        </section>
    </div>

    <p class="text-xs text-zinc-500">
        Analisis: {{ $a['success'] }} sukses, {{ $a['failed'] }} gagal, {{ $a['pending'] + $a['processing'] }} antri ·
        Kesimpulan: {{ $this->summaries['success'] ?? 0 }} sukses, {{ $this->summaries['failed'] ?? 0 }} gagal
    </p>
</div>