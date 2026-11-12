<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Model: Absensi (ABSENSI)
 * Sesuai ERD Proposal: id_absensi, id_karyawan, tanggal, jam_masuk, jam_keluar,
 *                       durasi_jam, status_kehadiran
 */
class Absensi extends Model
{
    use HasFactory;

    protected $table = 'absensi';

    protected $fillable = [
        'id_karyawan',
        'tanggal',
        'jam_masuk',
        'jam_keluar',
        'durasi_jam',
        'shift',
        'jam_normal',
        'jam_lembur',
        'sudah_pulang',
        'status_kehadiran',
        'keterangan',
    ];

    protected function casts(): array
    {
        return [
            'tanggal'    => 'date:Y-m-d',
            'durasi_jam' => 'decimal:2',
            'jam_normal' => 'decimal:2',
            'jam_lembur' => 'decimal:2',
            'sudah_pulang' => 'boolean',
        ];
    }

    // ─── Relationships ────────────────────────────────────────────────────────────

    public function karyawan(): BelongsTo
    {
        return $this->belongsTo(Karyawan::class, 'id_karyawan');
    }
}
