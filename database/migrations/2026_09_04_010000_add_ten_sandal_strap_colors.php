<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $types = [
            'Pria Dewasa' => 'PD',
            'Wanita Dewasa' => 'WD',
            'Upin' => 'UP',
            'Ipin' => 'IP',
        ];

        $colors = [
            'Putih' => 'PTH',
            'Hitam' => 'HTM',
            'Merah' => 'MRH',
            'Biru' => 'BRU',
            'Hijau' => 'HJU',
            'Kuning' => 'KNG',
            'Pink' => 'PNK',
            'Ungu' => 'UNG',
            'Cokelat' => 'CKL',
            'Abu-abu' => 'ABU',
        ];

        foreach ($types as $type => $typeCode) {
            foreach ($colors as $color => $colorCode) {
                $exists = DB::table('barang_pvc')->where([
                    'kategori' => 'Tali Jepit',
                    'jenis' => $type,
                    'warna' => $color,
                ])->exists();

                if (! $exists) {
                    DB::table('barang_pvc')->insert([
                        'nama_barang' => "$type - $color",
                        'kode_barang' => "TJ-$typeCode-$colorCode",
                        'kategori' => 'Tali Jepit',
                        'jenis' => $type,
                        'warna' => $color,
                        'satuan' => 'kodi',
                        'stok_minimum' => 25,
                        'stok_saat_ini' => 0,
                        'updated_at' => now(),
                        'created_at' => now(),
                    ]);
                }
            }
        }
    }

    public function down(): void
    {
        // Product master data is intentionally preserved to protect stock history.
    }
};
