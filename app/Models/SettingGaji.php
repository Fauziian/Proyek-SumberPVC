<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Model: SettingGaji
 * Tabel konfigurasi parameter penggajian yang dapat diubah Admin.
 *
 * NILAI DEFAULT — HANYA CONTOH, BUKAN kebijakan riil perusahaan:
 *   - gaji_pokok_kategori_a : Rp 4.500.000
 *   - gaji_pokok_kategori_b : Rp 6.000.000
 *   - insentif_jam_lebih_pct: 7% (rentang valid: 5–10%)
 *   - insentif_lembur_pct   : 17.5% (rentang valid: 15–20%)
 *   - potongan_setengah_pct : 40%
 */
class SettingGaji extends Model
{
    use HasFactory;

    protected $table = 'setting_gaji';

    protected $fillable = [
        'gaji_pokok_kategori_a',
        'gaji_pokok_kategori_b',
        'tarif_per_jam_kategori_a',
        'tarif_per_jam_kategori_b',
        'insentif_jam_lebih_pct',
        'insentif_lembur_pct',
        'potongan_setengah_pct',
        'is_active',
        'keterangan',
    ];

    protected function casts(): array
    {
        return [
            'gaji_pokok_kategori_a'  => 'integer',
            'gaji_pokok_kategori_b'  => 'integer',
            'tarif_per_jam_kategori_a' => 'integer',
            'tarif_per_jam_kategori_b' => 'integer',
            'insentif_jam_lebih_pct' => 'decimal:2',
            'insentif_lembur_pct'    => 'decimal:2',
            'potongan_setengah_pct'  => 'decimal:2',
            'is_active'              => 'boolean',
        ];
    }

    /**
     * Ambil setting aktif yang sedang berlaku.
     */
    public static function aktif(): self
    {
        return static::where('is_active', true)->first() ?? static::firstOrCreate([
            'is_active' => true,
        ], [
            'gaji_pokok_kategori_a'  => 0,
            'gaji_pokok_kategori_b'  => 0,
            'tarif_per_jam_kategori_a' => 12000,
            'tarif_per_jam_kategori_b' => 17000,
            'insentif_jam_lebih_pct' => 0,
            'insentif_lembur_pct'    => 0,
            'potongan_setengah_pct'  => 0,
            'keterangan'             => 'Pengaturan default sistem',
        ]);
    }
}
