<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Model: BarangMasuk (BARANG_MASUK)
 * Sesuai ERD Proposal: id_masuk, id_barang, tanggal, jumlah, pemasok
 */
class BarangMasuk extends Model
{
    use HasFactory;

    protected $table = 'barang_masuk';

    protected $fillable = [
        'id_barang',
        'tanggal',
        'jumlah',
        'pemasok',
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
