<?php

namespace Tests\Feature\Admin;

use App\Models\BarangMasuk;
use App\Models\BarangPvc;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * TASK-019 — Pengujian modul Admin: master produk PVC (Tali Jepit, Outsole, Boloni Gunung).
 */
class MasterProductTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Halaman Inertia memuat aset Vite; manifest build tidak diperlukan saat tes.
        $this->withoutVite();
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'kode_barang' => 'TJ-TEST-1',
            'kategori' => 'Tali Jepit',
            'jenis' => 'Pria Dewasa',
            'warna' => 'Putih',
            'stok_minimum' => 10,
            'stok_saat_ini' => 0,
        ], $overrides);
    }

    public function test_only_admin_can_open_master_products(): void
    {
        $this->get(route('admin.master-products'))->assertRedirect('/login');

        foreach (['hr', 'warehouse'] as $role) {
            $this->actingAs(User::factory()->create(['role' => $role]))
                ->get(route('admin.master-products'))
                ->assertForbidden();
        }

        $this->actingAs($this->admin())->get(route('admin.master-products'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Stock/Master'));
    }

    public function test_tali_jepit_requires_jenis_and_warna_and_gets_automatic_name(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('admin.master-products.store'), $this->payload(['warna' => '']))
            ->assertSessionHasErrors('warna');

        $this->actingAs($admin)->post(route('admin.master-products.store'), $this->payload())
            ->assertSessionHas('success');

        $this->assertDatabaseHas('barang_pvc', [
            'kode_barang' => 'TJ-TEST-1',
            'nama_barang' => 'Pria Dewasa - Putih',
            'satuan' => 'kodi',
        ]);
    }

    public function test_outsole_requires_jenis_and_ignores_warna(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('admin.master-products.store'), $this->payload([
            'kode_barang' => 'OS-TEST-1', 'kategori' => 'Outsole', 'jenis' => '', 'warna' => '',
        ]))->assertSessionHasErrors('jenis');

        $this->actingAs($admin)->post(route('admin.master-products.store'), $this->payload([
            'kode_barang' => 'OS-TEST-1', 'kategori' => 'Outsole', 'jenis' => 'Tipe A', 'warna' => 'Merah',
        ]))->assertSessionHas('success');

        $item = BarangPvc::where('kode_barang', 'OS-TEST-1')->firstOrFail();
        $this->assertSame('Tipe A', $item->nama_barang);
        $this->assertNull($item->warna);
    }

    public function test_boloni_gunung_requires_warna_and_ignores_jenis(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('admin.master-products.store'), $this->payload([
            'kode_barang' => 'BG-TEST-1', 'kategori' => 'Boloni Gunung', 'jenis' => 'Abaikan', 'warna' => '',
        ]))->assertSessionHasErrors('warna');

        $this->actingAs($admin)->post(route('admin.master-products.store'), $this->payload([
            'kode_barang' => 'BG-TEST-1', 'kategori' => 'Boloni Gunung', 'jenis' => 'Abaikan', 'warna' => 'Hitam',
        ]))->assertSessionHas('success');

        $item = BarangPvc::where('kode_barang', 'BG-TEST-1')->firstOrFail();
        $this->assertSame('Boloni Gunung - Hitam', $item->nama_barang);
        $this->assertNull($item->jenis);
    }

    public function test_store_rejects_duplicate_code_and_invalid_category(): void
    {
        $admin = $this->admin();
        $awal = BarangPvc::count(); // migration sudah mengisi produk awal
        $this->actingAs($admin)->post(route('admin.master-products.store'), $this->payload());
        $this->assertSame($awal + 1, BarangPvc::count());

        $this->actingAs($admin)->post(route('admin.master-products.store'), $this->payload())
            ->assertSessionHasErrors('kode_barang');

        $this->actingAs($admin)->post(route('admin.master-products.store'), $this->payload([
            'kode_barang' => 'XX-1', 'kategori' => 'Sepatu',
        ]))->assertSessionHasErrors('kategori');

        $this->assertSame($awal + 1, BarangPvc::count());
    }

    public function test_initial_stock_creates_incoming_record_without_doubling_stock(): void
    {
        $this->actingAs($this->admin())->post(route('admin.master-products.store'), $this->payload([
            'stok_saat_ini' => 25,
        ]))->assertSessionHas('success');

        $item = BarangPvc::where('kode_barang', 'TJ-TEST-1')->firstOrFail();

        $this->assertSame(25, $item->stok_saat_ini);
        $this->assertSame(1, BarangMasuk::where('id_barang', $item->id)->count());
        $this->assertSame(25, (int) BarangMasuk::where('id_barang', $item->id)->value('jumlah'));
    }

    public function test_admin_can_update_product_and_name_follows_variant(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin)->post(route('admin.master-products.store'), $this->payload());
        $item = BarangPvc::where('kode_barang', 'TJ-TEST-1')->firstOrFail();

        $this->actingAs($admin)->put(route('admin.master-products.update', $item), [
            'kode_barang' => 'TJ-TEST-1',
            'kategori' => 'Tali Jepit',
            'jenis' => 'Wanita Dewasa',
            'warna' => 'Biru',
            'stok_minimum' => 30,
        ])->assertSessionHas('success');

        $item->refresh();
        $this->assertSame('Wanita Dewasa - Biru', $item->nama_barang);
        $this->assertSame(30, $item->stok_minimum);
    }

    public function test_product_without_transactions_can_be_deleted_but_not_with_history(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin)->post(route('admin.master-products.store'), $this->payload(['kode_barang' => 'KOSONG-1']));
        $this->actingAs($admin)->post(route('admin.master-products.store'), $this->payload(['kode_barang' => 'RIWAYAT-1', 'stok_saat_ini' => 5]));

        $kosong = BarangPvc::where('kode_barang', 'KOSONG-1')->firstOrFail();
        $riwayat = BarangPvc::where('kode_barang', 'RIWAYAT-1')->firstOrFail();

        $this->actingAs($admin)->delete(route('admin.master-products.destroy', $kosong))->assertSessionHas('success');
        $this->assertDatabaseMissing('barang_pvc', ['id' => $kosong->id]);

        $this->actingAs($admin)->delete(route('admin.master-products.destroy', $riwayat))->assertSessionHas('error');
        $this->assertDatabaseHas('barang_pvc', ['id' => $riwayat->id]);
    }

    public function test_non_admin_cannot_modify_master_products(): void
    {
        $warehouse = User::factory()->create(['role' => 'warehouse']);
        $awal = BarangPvc::count();

        $this->actingAs($warehouse)->post(route('admin.master-products.store'), $this->payload())->assertForbidden();

        $this->assertSame($awal, BarangPvc::count());
    }
}
