<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Model: NotifikasiStok (NOTIFIKASI_STOK)
 * Sesuai ERD Proposal: id_notifikasi, id_barang, pesan, tanggal
 *
 * Record dibuat otomatis oleh App\Services\StockService ketika
 * stok_saat_ini <= stok_minimum setelah transaksi barang keluar.
 */
class NotifikasiStok extends Model
{
    use HasFactory;

    protected $table = 'notifikasi_stok';

    protected $fillable = [
        'id_barang',
        'pesan',
        'tanggal',
        'is_read',
    ];

    protected function casts(): array
    {
        return [
            'tanggal' => 'date:Y-m-d',
            'is_read' => 'boolean',
        ];
    }

    // ─── Relationships ────────────────────────────────────────────────────────────

    public function barangPvc(): BelongsTo
    {
        return $this->belongsTo(BarangPvc::class, 'id_barang');
    }
}
