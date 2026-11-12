<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Model: BarangKeluar (BARANG_KELUAR)
 * Sesuai ERD Proposal: id_keluar, id_barang, tanggal, jumlah, tujuan_penggunaan
 */
class BarangKeluar extends Model
{
    use HasFactory;

    protected $table = 'barang_keluar';

    protected $fillable = [
        'id_barang',
        'tanggal',
        'jumlah',
        'tujuan_penggunaan',
        'keterangan',
    ];

    protected function casts(): array
    {
        return [
            'tanggal' => 'date:Y-m-d',
            'jumlah'  => 'integer',
        ];
    }

    // ─── Relationships ────────────────────────────────────────────────────────────

    public function barangPvc(): BelongsTo
    {
        return $this->belongsTo(BarangPvc::class, 'id_barang');
    }
}
