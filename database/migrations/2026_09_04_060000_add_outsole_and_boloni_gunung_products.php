<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();
        $products = [[
            'nama_barang' => 'Outsole',
            'kode_barang' => 'OS-UTAMA',
            'kategori' => 'Outsole',
            'jenis' => 'Outsole',
            'warna' => null,
            'satuan' => 'kodi',
            'stok_minimum' => 25,
            'stok_saat_ini' => 120,
        ]];

        $colors = ['Putih' => 'PTH', 'Hitam' => 'HTM', 'Merah' => 'MRH', 'Biru' => 'BRU', 'Hijau' => 'HJU', 'Kuning' => 'KNG', 'Pink' => 'PNK', 'Ungu' => 'UNG', 'Cokelat' => 'CKL', 'Abu-abu' => 'ABU'];
        foreach ($colors as $color => $code) {
            $products[] = [
                'nama_barang' => "Boloni Gunung - $color",
                'kode_barang' => "BG-$code",
                'kategori' => 'Boloni Gunung',
                'jenis' => null,
                'warna' => $color,
                'satuan' => 'kodi',
                'stok_minimum' => 25,
                'stok_saat_ini' => 0,
            ];
        }

        foreach ($products as $product) {
            $existing = DB::table('barang_pvc')->where('kode_barang', $product['kode_barang'])->first();
            if (!$existing) {
                DB::table('barang_pvc')->insert([
                    ...$product,
                    'stok_saat_ini' => 0,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }

    public function down(): void
    {
        DB::table('barang_pvc')->whereIn('kode_barang', ['OS-UTAMA', 'BG-PTH', 'BG-HTM', 'BG-MRH', 'BG-BRU', 'BG-HJU', 'BG-KNG', 'BG-PNK', 'BG-UNG', 'BG-CKL', 'BG-ABU'])->delete();
    }
};
