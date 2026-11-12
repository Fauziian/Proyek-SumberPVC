<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Model: KomponenGaji (KOMPONEN_GAJI)
 * Sesuai ERD Proposal: id_gaji, id_karyawan, periode, gaji_pokok, tunjangan,
 *                       potongan, insentif_lembur, total_gaji
 *
 * CATATAN: Tidak ada perhitungan PPh 21 (sesuai batasan proposal).
 * Tidak terintegrasi payroll pihak ketiga.
 */
class KomponenGaji extends Model
{
    use HasFactory;

    protected $table = 'komponen_gaji';

    protected $fillable = [
        'id_karyawan',
        'periode',
        'gaji_pokok',
        'tunjangan',
        'potongan',
        'insentif_lembur',
        'total_gaji',
        'hari_hadir',
        'hari_setengah',
        'jam_lebih',
        'jam_lembur',
        'tarif_per_jam',
        'total_jam_normal',
        'rincian',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'gaji_pokok'     => 'integer',
            'tunjangan'      => 'integer',
            'potongan'       => 'integer',
            'insentif_lembur'=> 'integer',
            'total_gaji'     => 'integer',
            'tarif_per_jam'  => 'integer',
            'total_jam_normal' => 'decimal:2',
            'jam_lebih'      => 'decimal:2',
            'jam_lembur'     => 'decimal:2',
            'rincian'        => 'array',
        ];
    }

    // ─── Relationships ────────────────────────────────────────────────────────────

    public function karyawan(): BelongsTo
    {
        return $this->belongsTo(Karyawan::class, 'id_karyawan');
    }
}
