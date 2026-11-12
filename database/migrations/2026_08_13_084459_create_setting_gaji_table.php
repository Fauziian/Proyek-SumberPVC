<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * TABEL: setting_gaji
 * Tabel konfigurasi parameter penggajian yang dapat diubah oleh Admin.
 *
 * NILAI DEFAULT (CONTOH — BUKAN kebijakan riil perusahaan):
 *  - gaji_pokok_kategori_a : Rp 4.500.000 (masa kerja < 5 tahun)
 *  - gaji_pokok_kategori_b : Rp 6.000.000 (masa kerja >= 5 tahun)
 *  - insentif_jam_lebih_pct: 7% (rentang proposal: 5–10%, default: 7%)
 *  - insentif_lembur_pct   : 17.5% (rentang proposal: 15–20%, default: 17.5%)
 *  - potongan_setengah_pct : 40% (sesuai aturan bisnis proposal)
 *
 * Hanya ada 1 baris aktif (is_active = true) yang digunakan sistem.
 * Desain single-row config agar mudah di-update via halaman Pengaturan Sistem.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('setting_gaji', function (Blueprint $table) {
            $table->id();

            // Nominal gaji pokok per kategori masa kerja (Rupiah)
            // DEFAULT: NILAI CONTOH — bukan kebijakan riil perusahaan
            $table->unsignedBigInteger('gaji_pokok_kategori_a')->default(4500000);  // Kategori A: <5 tahun
            $table->unsignedBigInteger('gaji_pokok_kategori_b')->default(6000000);  // Kategori B: >=5 tahun

            // Persentase insentif jam kerja lebih (8–12 jam)
            // Rentang proposal: 5–10%, default contoh: 7.00
            $table->decimal('insentif_jam_lebih_pct', 5, 2)->default(7.00);

            // Persentase insentif lembur resmi (>15 jam)
            // Rentang proposal: 15–20%, default contoh: 17.50
            $table->decimal('insentif_lembur_pct', 5, 2)->default(17.50);

            // Persentase potongan setengah hari (<8 jam): tetap 40% sesuai proposal
            $table->decimal('potongan_setengah_pct', 5, 2)->default(40.00);

            $table->boolean('is_active')->default(true);
            $table->string('keterangan')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('setting_gaji');
    }
};
