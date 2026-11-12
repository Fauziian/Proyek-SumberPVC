<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * TABEL: barang_keluar
 * Sesuai ERD Proposal: BARANG_KELUAR
 *
 * Mencatat setiap pengeluaran bahan baku untuk keperluan produksi.
 * Setiap insert akan memicu BarangPvcObserver untuk mengurangi
 * stok_saat_ini pada tabel barang_pvc secara atomic (DB transaction).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('barang_keluar', function (Blueprint $table) {
            $table->id();                                                           // id_keluar

            $table->foreignId('id_barang')
                  ->constrained('barang_pvc')
                  ->cascadeOnDelete();

            $table->date('tanggal');                                               // tanggal pengeluaran
            $table->unsignedInteger('jumlah');                                     // jumlah dikeluarkan
            $table->string('tujuan_penggunaan');                                   // lini produksi / tujuan
            $table->text('keterangan')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('barang_keluar');
    }
};
