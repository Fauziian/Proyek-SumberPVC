<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * TABEL: barang_masuk
 * Sesuai ERD Proposal: BARANG_MASUK
 *
 * Mencatat setiap penerimaan bahan baku dari pemasok (supplier).
 * Setiap insert ke tabel ini akan memicu BarangPvcObserver untuk
 * menambahkan jumlah ke stok_saat_ini pada tabel barang_pvc.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('barang_masuk', function (Blueprint $table) {
            $table->id();                                                           // id_masuk

            $table->foreignId('id_barang')
                  ->constrained('barang_pvc')
                  ->cascadeOnDelete();

            $table->date('tanggal');                                               // tanggal penerimaan
            $table->unsignedInteger('jumlah');                                     // jumlah diterima
            $table->string('pemasok');                                             // nama supplier/pemasok
            $table->text('keterangan')->nullable();                               // catatan penerimaan
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('barang_masuk');
    }
};
