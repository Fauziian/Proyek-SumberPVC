<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * TABEL: komponen_gaji
 * Sesuai ERD Proposal: KOMPONEN_GAJI
 *
 * Menyimpan hasil kalkulasi gaji per karyawan per periode.
 * Kalkulasi dilakukan oleh App\Services\PayrollCalculationService.
 *
 * Komponen:
 *  - gaji_pokok    : dari setting_gaji berdasarkan kategori_masa_kerja karyawan
 *  - tunjangan     : total tunjangan (jabatan, transport, makan, dll)
 *  - potongan      : total potongan (setengah hari, dll) — TIDAK termasuk PPh 21
 *  - insentif_lembur: insentif dari jam lebih / lembur
 *  - total_gaji    : gaji_pokok + tunjangan + insentif_lembur - potongan
 *
 * CATATAN: Sesuai batasan proposal, modul ini TIDAK menghitung PPh 21
 * dan TIDAK terintegrasi dengan payroll pihak ketiga.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('komponen_gaji', function (Blueprint $table) {
            $table->id();                                                           // id_gaji

            $table->foreignId('id_karyawan')
                  ->constrained('karyawan')
                  ->restrictOnDelete();

            $table->string('periode');                                             // e.g. "2025-08" (YYYY-MM)

            $table->unsignedBigInteger('gaji_pokok')->default(0);                 // dari setting_gaji
            $table->unsignedBigInteger('tunjangan')->default(0);                  // total tunjangan
            $table->unsignedBigInteger('potongan')->default(0);                   // total potongan
            $table->unsignedBigInteger('insentif_lembur')->default(0);            // insentif jam lebih/lembur
            $table->unsignedBigInteger('total_gaji')->default(0);                 // total bersih

            // Metadata kalkulasi (untuk audit dan debugging)
            $table->integer('hari_hadir')->default(0);
            $table->integer('hari_setengah')->default(0);
            $table->decimal('jam_lebih', 6, 2)->default(0);
            $table->decimal('jam_lembur', 6, 2)->default(0);
            $table->json('rincian')->nullable();                                   // JSON breakdown detail

            $table->enum('status', ['draft', 'final'])->default('draft');
            $table->timestamps();

            // Constraint: satu karyawan hanya boleh punya 1 record per periode
            $table->unique(['id_karyawan', 'periode']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('komponen_gaji');
    }
};
