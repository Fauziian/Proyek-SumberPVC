<?php

namespace App\Services;

use App\Models\BarangPvc;
use App\Models\BarangMasuk;
use App\Models\BarangKeluar;
use App\Models\NotifikasiStok;
use Illuminate\Support\Facades\DB;
use Exception;

class StockService
{
    public function recordIncomingBatch(array $items): void
    {
        DB::transaction(function () use ($items) {
            foreach ($items as $data) {
                $this->recordIncoming($data);
            }
        });
    }

    public function recordOutgoingBatch(array $items): void
    {
        DB::transaction(function () use ($items) {
            foreach ($items as $data) {
                $this->recordOutgoing($data);
            }
        });
    }

    /**
     * Catat barang masuk dan tambahkan ke stok saat ini.
     */
    public function recordIncoming(array $data): BarangMasuk
    {
        return DB::transaction(function () use ($data) {
            $barang = BarangPvc::findOrFail($data['id_barang']);

            // Buat record transaksi barang masuk
            $masuk = BarangMasuk::create([
                'id_barang' => $barang->id,
                'tanggal' => $data['tanggal'],
                'jumlah' => $data['jumlah'],
                'pemasok' => $data['pemasok'] ?? null,
                'keterangan' => $data['keterangan'] ?? null,
            ]);

            // Update stok fisik (increment)
            $barang->increment('stok_saat_ini', $data['jumlah']);

            return $masuk;
        });
    }

    /**
     * Catat barang keluar dan kurangi dari stok saat ini.
     * Validasi kecukupan stok sebelum memproses.
     */
    public function recordOutgoing(array $data): BarangKeluar
    {
        return DB::transaction(function () use ($data) {
            $barang = BarangPvc::lockForUpdate()->findOrFail($data['id_barang']);

            // Validasi apakah stok mencukupi
            if ($barang->stok_saat_ini < $data['jumlah']) {
                throw new Exception("Stok tidak mencukupi. Stok saat ini: {$barang->stok_saat_ini} {$barang->satuan}, diminta: {$data['jumlah']} {$barang->satuan}.");
            }

            // Buat record transaksi barang keluar
            $keluar = BarangKeluar::create([
                'id_barang' => $barang->id,
                'tanggal' => $data['tanggal'],
                'jumlah' => $data['jumlah'],
                'tujuan_penggunaan' => $data['tujuan_penggunaan'],
                'keterangan' => $data['keterangan'] ?? null,
            ]);

            // Update stok fisik (decrement)
            $barang->decrement('stok_saat_ini', $data['jumlah']);

            // Refresh model setelah update
            $barang->refresh();

            // Cek apakah stok di bawah minimum dan butuh notifikasi
            $this->checkAndNotifyStock($barang);

            return $keluar;
        });
    }

    /**
     * Memeriksa apakah stok saat ini berada di bawah batas minimum.
     * Jika ya, buat notifikasi stok baru secara otomatis.
     */
    public function checkAndNotifyStock(BarangPvc $barang): ?NotifikasiStok
    {
        if ($barang->stok_saat_ini <= $barang->stok_minimum) {
            // Cek apakah hari ini sudah ada notifikasi yang serupa untuk menghindari duplikasi
            $existingNotif = NotifikasiStok::where('id_barang', $barang->id)
                ->whereDate('tanggal', now()->toDateString())
                ->where('is_read', false)
                ->first();

            if (!$existingNotif) {
                return NotifikasiStok::create([
                    'id_barang' => $barang->id,
                    'pesan' => "Stok {$barang->nama_barang} ({$barang->kode_barang}) kritis: {$barang->stok_saat_ini} {$barang->satuan} (Batas minimum: {$barang->stok_minimum} {$barang->satuan})",
                    'tanggal' => now()->toDateString(),
                    'is_read' => false,
                ]);
            }
        }

        return null;
    }
}
