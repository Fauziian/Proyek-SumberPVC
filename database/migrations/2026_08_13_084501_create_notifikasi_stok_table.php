<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * TABEL: notifikasi_stok
 * Sesuai ERD Proposal: NOTIFIKASI_STOK
 *
 * Record notifikasi dibuat secara otomatis oleh StockService ketika
 * stok_saat_ini <= stok_minimum setelah setiap transaksi barang keluar.
 * Notifikasi ditampilkan di dashboard dan halaman notifikasi stok.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifikasi_stok', function (Blueprint $table) {
            $table->id();                                                           // id_notifikasi

            $table->foreignId('id_barang')
                  ->constrained('barang_pvc')
                  ->cascadeOnDelete();

            $table->text('pesan');                                                 // pesan notifikasi otomatis
            $table->date('tanggal');                                               // tanggal notifikasi dibuat
            $table->boolean('is_read')->default(false);                           // status dibaca
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifikasi_stok');
    }
};
