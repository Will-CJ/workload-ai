@props(['summary' => null, 'priorities' => [], 'title' => 'Kesimpulan AI'])

@php
    $ok = $summary && $summary->status === \App\Enums\AnalysisStatus::Success;
    $items = ($ok && $summary->priorities) ? $summary->priorities : $priorities;
@endphp

<section class="rounded-xl border border-zinc-200 p-5 dark:border-zinc-700">
    <flux:heading size="lg">{{ $title }}</flux:heading>

    @if ($ok)
        <p class="mt-3 text-sm leading-relaxed text-zinc-700 dark:text-zinc-300">{{ $summary->summary }}</p>
        <p class="mt-2 text-xs text-zinc-400">Dibuat {{ $summary->generated_at?->diffForHumans() }} · {{ $summary->model }}</p>
    @elseif ($summary && $summary->status === \App\Enums\AnalysisStatus::Failed)
        <p class="mt-3 text-sm text-red-600">Kesimpulan gagal dibuat: {{ $summary->error }}</p>
    @else
        <p class="mt-3 text-sm text-zinc-500">Kesimpulan belum tersedia. Jalankan analisis di halaman Import & Analisis.</p>
    @endif

    <flux:heading class="mt-6">Prioritas otomasi</flux:heading>
    <p class="text-xs text-zinc-500">Urutan ditentukan sistem berdasarkan potensi saving FTE.</p>

    @forelse ($items as $i => $p)
        <div class="mt-3 rounded-lg bg-zinc-50 p-3 dark:bg-zinc-800/50">
            <div class="flex flex-wrap items-center gap-2">
                <span class="text-sm font-semibold">{{ $i + 1 }}. {{ $p['activity_name'] }}</span>
                <x-level-badge :level="$p['automation_level']" />
                <span class="ml-auto text-sm font-medium text-green-600">
                    {{ number_format($p['estimated_fte_saving'], 2, ',', '.') }} FTE
                </span>
            </div>
            <div class="mt-1 text-xs text-zinc-500">{{ $p['activity_id'] }} · {{ $p['employee'] }} · {{ $p['department'] }}</div>
            @if (! empty($p['rationale']))
                <p class="mt-2 text-sm">{{ $p['rationale'] }}</p>
            @endif
            @if (! empty($p['recommendation']))
                <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400"><span class="font-medium">Rekomendasi:</span> {{ $p['recommendation'] }}</p>
            @endif
        </div>
    @empty
        <p class="mt-3 text-sm text-zinc-500">Belum ada aktivitas yang dianalisis.</p>
    @endforelse
</section>