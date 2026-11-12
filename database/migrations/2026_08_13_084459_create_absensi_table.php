<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * TABEL: absensi
 * Sesuai ERD Proposal: ABSENSI
 *
 * Aturan bisnis durasi kerja (dari proposal):
 *  - durasi_jam < 8  → Setengah Hari → potongan 40% gaji harian
 *  - durasi_jam = 8  → Normal → dibayar penuh
 *  - 8 < durasi_jam <= 12 → Jam Lebih → insentif 5–10% (configurable)
 *  - durasi_jam > 15 → Lembur resmi → insentif 15–20% (configurable)
 *
 * status_kehadiran di-set otomatis oleh PayrollCalculationService berdasarkan durasi_jam.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('absensi', function (Blueprint $table) {
            $table->id();                                                           // id_absensi

            $table->foreignId('id_karyawan')
                  ->constrained('karyawan')
                  ->restrictOnDelete();

            $table->date('tanggal');                                               // tanggal kehadiran
            $table->time('jam_masuk');                                             // jam masuk kerja
            $table->time('jam_keluar');                                            // jam keluar kerja
            $table->decimal('durasi_jam', 5, 2);                                  // total jam kerja (desimal, e.g. 8.50)

            $table->enum('status_kehadiran', [
                'Penuh',         // >= 8 jam normal
                'Setengah Hari', // < 8 jam → potongan 40%
                'Jam Lebih',     // 8–12 jam → insentif 5–10%
                'Lembur',        // > 15 jam → insentif 15–20%
            ])->default('Penuh');

            $table->text('keterangan')->nullable();                               // catatan opsional
            $table->timestamps();

            // Constraint: satu karyawan hanya boleh punya 1 catatan per tanggal
            $table->unique(['id_karyawan', 'tanggal']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('absensi');
    }
};
