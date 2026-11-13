@php
    $f = fn ($n, $d = 2) => number_format((float) $n, $d, ',', '.');
    $ok = fn ($s) => $s && $s->status === \App\Enums\AnalysisStatus::Success;
    $g = $global;
    $priorities = ($ok($global_summary) && $global_summary->priorities) ? $global_summary->priorities : $g['priorities'];
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Laporan Workload Assessment</title>
    <style>
        body { font-family: "DejaVu Sans", sans-serif; font-size: 10px; color: #222; }
        h1 { font-size: 18px; margin: 0 0 4px; }
        h2 { font-size: 13px; margin: 18px 0 6px; border-bottom: 1px solid #ccc; padding-bottom: 3px; }
        h3 { font-size: 11px; margin: 10px 0 3px; }
        table { width: 100%; border-collapse: collapse; margin-top: 4px; }
        th, td { border: 1px solid #ddd; padding: 4px 5px; vertical-align: top; text-align: left; }
        th { background: #f2f2f2; }
        .r { text-align: right; }
        .muted { color: #777; }
        .box { border: 1px solid #ddd; padding: 8px; margin-top: 6px; }
        .page-break { page-break-before: always; }
        .stats td { width: 25%; border: 1px solid #ddd; }
        .big { font-size: 15px; font-weight: bold; }
    </style>
</head>
<body>
    <h1>Laporan Workload Assessment & Potensi Otomasi</h1>
    <div class="muted">PT ABC · dibuat {{ $generated_at->format('d M Y H:i') }}</div>

    <h2>1. Ringkasan</h2>
    <table class="stats">
        <tr>
            <td>Total aktivitas<br><span class="big">{{ $g['activity_count'] }}</span></td>
            <td>Total beban kerja<br><span class="big">{{ $f($g['total_fte']) }} FTE</span></td>
            <td>Potensi penghematan<br><span class="big">{{ $f($g['total_saving']) }} FTE</span></td>
            <td>% penghematan<br><span class="big">{{ $f($g['saving_percentage'], 1) }}%</span></td>
        </tr>
    </table>
    <table>
        <tr><th>Level</th><th class="r">Aktivitas</th><th class="r">FTE</th><th class="r">Saving FTE</th></tr>
        @foreach (['high' => 'High', 'medium' => 'Medium', 'low' => 'Low'] as $key => $label)
            <tr>
                <td>{{ $label }}</td>
                <td class="r">{{ $g['levels'][$key]['count'] }}</td>
                <td class="r">{{ $f($g['levels'][$key]['fte']) }}</td>
                <td class="r">{{ $f($g['levels'][$key]['saving']) }}</td>
            </tr>
        @endforeach
    </table>

    <h3>Kesimpulan AI</h3>
    <div class="box">{{ $ok($global_summary) ? $global_summary->summary : 'Kesimpulan belum tersedia.' }}</div>

    <h2>2. Prioritas otomasi</h2>
    <table>
        <tr><th>#</th><th>Aktivitas</th><th>Karyawan / Departemen</th><th>Level</th><th class="r">Saving FTE</th><th>Alasan & rekomendasi</th></tr>
        @foreach ($priorities as $i => $p)
            <tr>
                <td>{{ $i + 1 }}</td>
                <td>{{ $p['activity_id'] }}<br>{{ $p['activity_name'] }}</td>
                <td>{{ $p['employee'] }}<br><span class="muted">{{ $p['department'] }}</span></td>
                <td>{{ ucfirst($p['automation_level']) }}</td>
                <td class="r">{{ $f($p['estimated_fte_saving'], 4) }}</td>
                <td>{{ $p['rationale'] ?? '' }} <span class="muted">{{ $p['recommendation'] ?? '' }}</span></td>
            </tr>
        @endforeach
    </table>

    <h2>3. Per departemen</h2>
    <table>
        <tr><th>Departemen</th><th class="r">Aktivitas</th><th class="r">FTE</th><th class="r">Saving FTE</th><th class="r">%</th></tr>
        @foreach ($departments as $d)
            <tr>
                <td>{{ $d['name'] }}</td><td class="r">{{ $d['activity_count'] }}</td>
                <td class="r">{{ $f($d['total_fte']) }}</td><td class="r">{{ $f($d['total_saving']) }}</td>
                <td class="r">{{ $f($d['saving_percentage'], 1) }}%</td>
            </tr>
        @endforeach
    </table>
    @foreach ($departments as $d)
        <h3>{{ $d['name'] }}</h3>
        <div class="box">{{ $ok($d['summary']) ? $d['summary']->summary : 'Kesimpulan belum tersedia.' }}</div>
    @endforeach

    <h2>4. Grup aktivitas serupa</h2>
    <table>
        <tr><th>Grup</th><th class="r">Aktivitas</th><th class="r">Karyawan</th><th class="r">Departemen</th><th class="r">FTE</th><th class="r">Saving FTE</th></tr>
        @foreach ($groups as $gr)
            <tr>
                <td>{{ $gr['label'] }}<br><span class="muted">{{ $gr['group_id'] }}</span></td>
                <td class="r">{{ $gr['activity_count'] }}</td><td class="r">{{ $gr['employee_count'] }}</td>
                <td class="r">{{ $gr['department_count'] }}</td><td class="r">{{ $f($gr['fte']) }}</td>
                <td class="r">{{ $f($gr['saving']) }}</td>
            </tr>
        @endforeach
    </table>

    <div class="page-break"></div>
    <h2>5. Per karyawan</h2>
    <table>
        <tr><th>Karyawan</th><th class="r">FTE</th><th class="r">Saving</th><th class="r">%</th><th>Kesimpulan AI</th></tr>
        @foreach ($employees as $e)
            <tr>
                <td>{{ $e['name'] }}<br><span class="muted">{{ $e['code'] }} · {{ $e['department'] }}</span></td>
                <td class="r">{{ $f($e['total_fte']) }}</td><td class="r">{{ $f($e['total_saving']) }}</td>
                <td class="r">{{ $f($e['saving_percentage'], 1) }}%</td>
                <td>{{ $ok($e['summary']) ? $e['summary']->summary : '-' }}</td>
            </tr>
        @endforeach
    </table>

    <div class="page-break"></div>
    <h2>Lampiran: daftar aktivitas</h2>
    <table>
        <tr><th>ID</th><th>Aktivitas</th><th>Karyawan</th><th>Grup</th><th>Level</th><th class="r">FTE</th><th class="r">Saving</th><th>Status</th></tr>
        @foreach ($activities as $x)
            <tr>
                <td>{{ $x->activity_code }}</td><td>{{ $x->activity_name }}</td><td>{{ $x->employee->name }}</td>
                <td>{{ $x->group?->code ?? '-' }}</td><td>{{ $x->analysis?->automation_level?->value ?? '-' }}</td>
                <td class="r">{{ $f($x->fte, 4) }}</td>
                <td class="r">{{ $x->estimated_fte_saving === null ? '-' : $f($x->estimated_fte_saving, 4) }}</td>
                <td>{{ $x->analysis?->status?->value ?? 'pending' }}</td>
            </tr>
        @endforeach
    </table>

    <h2>Asumsi perhitungan</h2>
    <p>
        1 FTE = {{ $assumptions['work_days_per_year'] }} hari × {{ $assumptions['minutes_per_day'] }} menit
        = {{ number_format($assumptions['work_days_per_year'] * $assumptions['minutes_per_day'], 0, ',', '.') }} menit/tahun.
        FTE aktivitas = durasi × volume × periode per tahun ÷ menit per FTE.
        Saving = FTE × saving rate (high {{ $assumptions['saving_rate']['high'] * 100 }}%,
        medium {{ $assumptions['saving_rate']['medium'] * 100 }}%, low {{ $assumptions['saving_rate']['low'] * 100 }}%).
        Level otomasi, alasan, rekomendasi, dan narasi kesimpulan dihasilkan AI ({{ $assumptions['ai_model'] }},
        prompt {{ $assumptions['prompt_version'] }}). Semua angka dihitung oleh sistem.
    </p>
</body>
</html>