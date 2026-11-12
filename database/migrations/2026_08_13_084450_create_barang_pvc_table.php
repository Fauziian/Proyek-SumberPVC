<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * TABEL: barang_pvc
 * Sesuai ERD Proposal: BARANG_PVC
 *
 * Master data bahan baku PVC milik Sumber PVC Outsole Tali Jepit.
 * stok_saat_ini di-update otomatis oleh BarangPvcObserver setiap ada
 * transaksi barang masuk atau keluar.
 *
 * Notifikasi stok dikirim secara otomatis via StockService ketika
 * stok_saat_ini <= stok_minimum.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('barang_pvc', function (Blueprint $table) {
            $table->id();                                                           // id_barang
            $table->string('nama_barang');                                         // nama bahan baku PVC
            $table->string('kode_barang')->unique();                               // kode unik (e.g. PVC-CW)
            $table->string('satuan', 20);                                          // satuan: kg, ltr, pcs, dll
            $table->unsignedInteger('stok_minimum')->default(0);                  // batas minimum stok
            $table->unsignedInteger('stok_saat_ini')->default(0);                 // stok terkini (auto-updated)
            $table->text('keterangan')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('barang_pvc');
    }
};
