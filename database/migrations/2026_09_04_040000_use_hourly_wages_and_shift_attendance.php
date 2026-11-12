<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('setting_gaji', function (Blueprint $table) {
            $table->unsignedInteger('tarif_per_jam_kategori_a')->default(12000);
            $table->unsignedInteger('tarif_per_jam_kategori_b')->default(17000);
        });

        Schema::table('absensi', function (Blueprint $table) {
            $table->string('shift', 20)->nullable();
            $table->decimal('jam_normal', 5, 2)->default(0);
            $table->decimal('jam_lembur', 5, 2)->default(0);
        });

        Schema::table('komponen_gaji', function (Blueprint $table) {
            $table->unsignedInteger('tarif_per_jam')->default(0);
            $table->decimal('total_jam_normal', 7, 2)->default(0);
        });

        DB::table('setting_gaji')->update([
            'tarif_per_jam_kategori_a' => 12000,
            'tarif_per_jam_kategori_b' => 17000,
        ]);

        DB::table('absensi')->orderBy('id')->each(function (object $absensi): void {
            $durasi = (float) $absensi->durasi_jam;

            DB::table('absensi')->where('id', $absensi->id)->update([
                'jam_normal' => min($durasi, 8),
                'jam_lembur' => max($durasi - 8, 0),
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('komponen_gaji', function (Blueprint $table) {
            $table->dropColumn(['tarif_per_jam', 'total_jam_normal']);
        });

        Schema::table('absensi', function (Blueprint $table) {
            $table->dropColumn(['shift', 'jam_normal', 'jam_lembur']);
        });

        Schema::table('setting_gaji', function (Blueprint $table) {
            $table->dropColumn(['tarif_per_jam_kategori_a', 'tarif_per_jam_kategori_b']);
        });
    }
};
