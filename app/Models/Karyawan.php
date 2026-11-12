<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Model: Karyawan (KARYAWAN)
 * Sesuai ERD Proposal: id_karyawan, nama, jabatan, tanggal_masuk, kategori_masa_kerja
 *
 * CATATAN: Field 'departemen' TIDAK ada di ERD proposal dan sengaja dihilangkan
 * sesuai instruksi revisi dari pembimbing.
 *
 * Kategori masa kerja:
 *  - 'A' = masa kerja < 5 tahun
 *  - 'B' = masa kerja >= 5 tahun
 */
class Karyawan extends Model
{
    use HasFactory;

    protected $table = 'karyawan';

    protected $fillable = [
        'nama',
        'jabatan',
        'tanggal_masuk',
        'kategori_masa_kerja',
        'is_active',
        'id_pengguna',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_masuk'      => 'date:Y-m-d',
            'is_active'          => 'boolean',
        ];
    }

    // ─── Accessors ────────────────────────────────────────────────────────────────

    /**
     * Hitung masa kerja dalam tahun (dari tanggal_masuk ke hari ini).
     */
    public function getMasaKerjaTahunAttribute(): float
    {
        return $this->tanggal_masuk
            ? round($this->tanggal_masuk->diffInDays(now()) / 365.25, 2)
            : 0;
    }

    /**
     * Tentukan kategori masa kerja otomatis berdasarkan tanggal_masuk.
     * A = < 5 tahun, B = >= 5 tahun
     */
    public function getKategoriOtomatisAttribute(): string
    {
        return $this->masa_kerja_tahun >= 5 ? 'B' : 'A';
    }

    // ─── Relationships ────────────────────────────────────────────────────────────

    public function pengguna(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_pengguna');
    }

    public function absensi(): HasMany
    {
        return $this->hasMany(Absensi::class, 'id_karyawan');
    }

    public function komponenGaji(): HasMany
    {
        return $this->hasMany(KomponenGaji::class, 'id_karyawan');
    }
}
