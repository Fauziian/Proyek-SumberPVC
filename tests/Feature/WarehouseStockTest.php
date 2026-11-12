<?php

namespace Tests\Feature;

use App\Models\BarangPvc;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Inertia\Testing\AssertableInertia as Assert;

class WarehouseStockTest extends TestCase
{
    use RefreshDatabase;

    private function warehouse(): User
    {
        return User::create([
            'nama' => 'Staf Gudang Test',
            'username' => 'gudang.test',
            'password' => 'password',
            'role' => 'warehouse',
            'is_active' => true,
        ]);
    }

    private function product(string $code, int $stock = 0): BarangPvc
    {
        return BarangPvc::create([
            'nama_barang' => 'Pria Dewasa - Putih',
            'kode_barang' => $code,
            'kategori' => 'Tali Jepit',
            'jenis' => 'Pria Dewasa',
            'warna' => 'Putih',
            'satuan' => 'kodi',
            'stok_minimum' => 10,
            'stok_saat_ini' => $stock,
        ]);
    }

    public function test_multiple_print_results_are_saved_at_once(): void
    {
        $first = $this->product('TJ-TEST-1');
        $second = $this->product('TJ-TEST-2');

        $this->actingAs($this->warehouse())->post(route('stock.incoming.store'), [
            'items' => [
                ['id_barang' => $first->id, 'tanggal' => '2026-09-04', 'jumlah' => 100, 'keterangan' => null],
                ['id_barang' => $second->id, 'tanggal' => '2026-09-04', 'jumlah' => 50, 'keterangan' => null],
            ],
        ])->assertSessionHasNoErrors();

        $this->assertSame(100, $first->fresh()->stok_saat_ini);
        $this->assertSame(50, $second->fresh()->stok_saat_ini);
        $this->assertDatabaseCount('barang_masuk', 2);
    }

    public function test_customer_shipment_reduces_each_product_stock(): void
    {
        $first = $this->product('TJ-TEST-3', 100);
        $second = $this->product('TJ-TEST-4', 80);

        $this->actingAs($this->warehouse())->post(route('stock.outgoing.store'), [
            'pelanggan' => 'Toko Sandal Jaya',
            'items' => [
                ['id_barang' => $first->id, 'tanggal' => '2026-09-04', 'jumlah' => 25, 'keterangan' => null],
                ['id_barang' => $second->id, 'tanggal' => '2026-09-04', 'jumlah' => 30, 'keterangan' => null],
            ],
        ])->assertSessionHasNoErrors();

        $this->assertSame(75, $first->fresh()->stok_saat_ini);
        $this->assertSame(50, $second->fresh()->stok_saat_ini);
        $this->assertDatabaseHas('barang_keluar', ['tujuan_penggunaan' => 'Toko Sandal Jaya']);
    }

    public function test_warehouse_can_view_weekly_stock_report(): void
    {
        $product = $this->product('TJ-TEST-WEEK', 100);
        $warehouse = $this->warehouse();

        $this->actingAs($warehouse)->post(route('stock.incoming.store'), [
            'items' => [['id_barang' => $product->id, 'tanggal' => '2026-09-02', 'jumlah' => 20, 'keterangan' => null]],
        ]);

        $this->actingAs($warehouse)->post(route('stock.outgoing.store'), [
            'pelanggan' => 'Pelanggan Test',
            'items' => [['id_barang' => $product->id, 'tanggal' => '2026-09-03', 'jumlah' => 5, 'keterangan' => null]],
        ]);

        $this->actingAs($warehouse)
            ->get(route('stock.weekly-report', ['week' => '2026-09-02']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Stock/WeeklyReport')
                ->where('totals.masuk', 20)
                ->where('totals.keluar', 5)
                ->where('totals.selisih', 15)
                ->has('rows', 1)
            );

        $this->actingAs($warehouse)
            ->get(route('stock.report.excel', ['type' => 'weekly', 'date' => '2026-09-02']))
            ->assertOk()
            ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

        $this->actingAs($warehouse)
            ->get(route('stock.report.pdf', ['type' => 'monthly', 'date' => '2026-09-01']))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }
}
