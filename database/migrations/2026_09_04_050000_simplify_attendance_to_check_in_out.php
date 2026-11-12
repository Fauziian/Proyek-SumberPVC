<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('absensi', function (Blueprint $table) {
            $table->boolean('sudah_pulang')->default(true);
        });

        // Kebijakan terbaru: 8--13 jam adalah kerja reguler berdasarkan
        // durasi yang dipilih saat pulang; lembur mulai pada jam ke-14.
        DB::table('absensi')->orderBy('id')->each(function (object $absensi): void {
            $durasi = (float) $absensi->durasi_jam;
            DB::table('absensi')->where('id', $absensi->id)->update([
                'jam_normal' => min($durasi, 13),
                'jam_lembur' => max($durasi - 13, 0),
                'status_kehadiran' => $durasi >= 14 ? 'Lembur' : 'Penuh',
                'sudah_pulang' => true,
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('absensi', function (Blueprint $table) {
            $table->dropColumn('sudah_pulang');
        });
    }
};
