<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * TABEL: karyawan
 * Sesuai ERD Proposal: KARYAWAN
 *
 * Fields:
 *  - id_karyawan      : primary key
 *  - nama             : nama lengkap karyawan
 *  - jabatan          : posisi/jabatan karyawan
 *  - tanggal_masuk    : tanggal mulai bekerja (menentukan masa kerja)
 *  - kategori_masa_kerja : enum('A','B') — A = < 5 tahun, B = >= 5 tahun
 *                          Dihitung otomatis dari tanggal_masuk, juga bisa di-override manual
 *  - id_pengguna      : (opsional) relasi ke tabel users jika karyawan punya akun sistem
 *
 * CATATAN: Field 'departemen' TIDAK ada di ERD proposal dan sengaja dihilangkan
 * sesuai instruksi revisi dari pembimbing.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('karyawan', function (Blueprint $table) {
            $table->id();                                                           // id_karyawan
            $table->string('nama');                                                // nama lengkap
            $table->string('jabatan');                                             // posisi / jabatan
            $table->date('tanggal_masuk');                                         // tanggal mulai kerja
            $table->enum('kategori_masa_kerja', ['A', 'B'])->default('A');        // A=<5thn, B>=5thn
            $table->boolean('is_active')->default(true);                          // status aktif

            // Relasi opsional ke pengguna sistem (nullable)
            $table->foreignId('id_pengguna')
                  ->nullable()
                  ->constrained('users')
                  ->nullOnDelete();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('karyawan');
    }
};
