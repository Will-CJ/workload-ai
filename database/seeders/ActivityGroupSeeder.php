<?php

namespace Database\Seeders;

use App\Models\ActivityGroup;
use Illuminate\Database\Seeder;

class ActivityGroupSeeder extends Seeder
{
    public function run(): void
    {
        $groups = [
            ['manual_data_entry', 'Input Data Manual', 'Menyalin/mengetik data dari dokumen, file, atau pesan ke sistem (ERP, HRIS, CRM, inventory).'],
            ['report_generation', 'Pembuatan Laporan', 'Menarik data dari sistem dan menyusun laporan rutin (harian/mingguan/bulanan).'],
            ['reconciliation', 'Rekonsiliasi', 'Mencocokkan dua sumber data (bank vs jurnal, stok fisik vs sistem, payroll vs absensi).'],
            ['document_review_approval', 'Review & Approval', 'Memeriksa dokumen/pengajuan terhadap aturan lalu menyetujui atau menolak.'],
            ['customer_communication', 'Komunikasi Pelanggan/Karyawan', 'Menjawab pertanyaan, follow-up, dan menangani komplain via email, telepon, chat.'],
            ['scheduling_coordination', 'Penjadwalan & Koordinasi', 'Mengatur jadwal interview, pengiriman, maintenance, atau pertemuan.'],
            ['meeting_discussion', 'Rapat & Diskusi', 'Rapat rutin, briefing, dan diskusi koordinasi tim.'],
            ['filing_archiving', 'Arsip Dokumen', 'Memindai, menamai, dan menyimpan dokumen ke folder atau sistem arsip.'],
            ['analysis_evaluation', 'Analisis & Evaluasi', 'Analisis data, screening, inspeksi, dan investigasi yang butuh penilaian.'],
            ['negotiation_relationship', 'Negosiasi & Relasi', 'Negosiasi, kunjungan, dan presentasi kepada vendor atau klien.'],
            ['coaching_training', 'Coaching & Pelatihan', 'Membimbing, melatih, menyusun materi, dan memfasilitasi pelatihan.'],
            ['other', 'Lainnya', 'Aktivitas yang tidak cocok dengan grup mana pun.'],
        ];

        foreach ($groups as [$code, $label, $description]) {
            ActivityGroup::updateOrCreate(
                ['code' => $code],
                ['label' => $label, 'description' => $description],
            );
        }
    }
}
