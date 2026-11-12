<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('barang_pvc', function (Blueprint $table) {
            $table->string('kategori')->default('Tali Jepit')->after('kode_barang');
            $table->string('jenis')->nullable()->after('kategori');
            $table->string('warna')->nullable()->after('jenis');
        });

        Schema::table('barang_masuk', function (Blueprint $table) {
            $table->string('pemasok')->nullable()->change();
        });

        DB::table('barang_pvc')->update(['satuan' => 'kodi']);
    }

    public function down(): void
    {
        Schema::table('barang_pvc', function (Blueprint $table) {
            $table->dropColumn(['kategori', 'jenis', 'warna']);
        });
    }
};
