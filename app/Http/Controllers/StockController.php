<?php

namespace App\Http\Controllers;

use App\Models\BarangPvc;
use App\Models\BarangMasuk;
use App\Models\BarangKeluar;
use App\Models\NotifikasiStok;
use App\Services\StockService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use Exception;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StockController extends Controller
{
    protected StockService $stockService;

    public function __construct(StockService $stockService)
    {
        $this->stockService = $stockService;
    }

    /** Halaman Admin untuk mengatur master produk jadi dan variannya. */
    public function master(Request $request): Response
    {
        $query = BarangPvc::query();
        if ($search = $request->input('search')) {
            $query->where(function ($builder) use ($search) {
                $builder->where('nama_barang', 'like', "%{$search}%")
                    ->orWhere('kode_barang', 'like', "%{$search}%")
                    ->orWhere('kategori', 'like', "%{$search}%")
                    ->orWhere('jenis', 'like', "%{$search}%")
                    ->orWhere('warna', 'like', "%{$search}%");
            });
        }

        return Inertia::render('Stock/Master', [
            'items' => $query->orderBy('kategori')->orderBy('jenis')->orderBy('warna')->paginate(12)->withQueryString(),
            'filters' => $request->only('search'),
        ]);
    }

    /**
     * Tampilkan katalog dan stok barang jadi.
     */
    public function index(Request $request): Response
    {
        $query = BarangPvc::query();

        // Pencarian
        if ($search = $request->input('search')) {
            $query->where(function($q) use ($search) {
                $q->where('nama_barang', 'like', "%{$search}%")
                  ->orWhere('kode_barang', 'like', "%{$search}%")
                  ->orWhere('jenis', 'like', "%{$search}%")
                  ->orWhere('warna', 'like', "%{$search}%");
            });
        }

        // Filter status stok (kosong, menipis, aman)
        if ($status = $request->input('status')) {
            if ($status === 'kosong') {
                $query->where('stok_saat_ini', 0);
            } elseif ($status === 'menipis') {
                $query->whereRaw('stok_saat_ini > 0 AND stok_saat_ini <= (stok_minimum * 1.3)');
            } elseif ($status === 'aman') {
                $query->whereRaw('stok_saat_ini > (stok_minimum * 1.3)');
            }
        }

        if ($kategori = $request->input('kategori')) {
            $query->where('kategori', $kategori);
        }

        if ($jenis = $request->input('jenis')) {
            $query->where('jenis', $jenis);
        }

        $items = $query
            ->orderByRaw('CASE WHEN stok_saat_ini > 0 THEN 0 ELSE 1 END')
            ->orderByDesc('stok_saat_ini')
            ->orderBy('nama_barang', 'asc')
            ->paginate(10)
            ->withQueryString();

        // Tambahkan computed status_stok ke paginated items
        $items->getCollection()->transform(function($item) {
            $item->status_stok = $item->status_stok; // triggers accessor
            return $item;
        });

        return Inertia::render('Stock/Available', [
            'items' => $items,
            'filters' => $request->only(['search', 'status', 'kategori', 'jenis']),
            'jenis_tali_jepit' => BarangPvc::where('kategori', 'Tali Jepit')
                ->whereNotNull('jenis')
                ->distinct()
                ->orderBy('jenis')
                ->pluck('jenis')
                ->values(),
        ]);
    }

    /**
     * Daftarkan master barang PVC baru.
     */
    public function storeItem(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'nama_barang' => ['nullable', 'string', 'max:255'],
            'kode_barang' => ['required', 'string', 'unique:barang_pvc,kode_barang', 'max:50'],
            'kategori' => ['required', 'in:Tali Jepit,Outsole,Boloni Gunung'],
            'jenis' => ['nullable', 'string', 'max:100'],
            'warna' => ['nullable', 'string', 'max:100'],
            'stok_minimum' => ['required', 'integer', 'min:0'],
            'stok_saat_ini' => ['required', 'integer', 'min:0'],
            'keterangan' => ['nullable', 'string', 'max:500'],
        ]);

        $this->normalizeProductVariant($validated, $request);
        $validated['nama_barang'] = $this->productName($validated);
        $validated['keterangan'] = null;
        $validated['satuan'] = 'kodi';
        $item = BarangPvc::create($validated);

        if ($item->stok_saat_ini > 0) {
            BarangMasuk::create([
                'id_barang' => $item->id,
                'tanggal' => now()->toDateString(),
                'jumlah' => $item->stok_saat_ini,
                'keterangan' => 'Stok awal sistem',
            ]);
        }

        $this->stockService->checkAndNotifyStock($item);

        return redirect()->back()->with('success', 'Produk jadi berhasil didaftarkan.');
    }

    /**
     * Update data master barang PVC.
     */
    public function updateItem(Request $request, BarangPvc $item): RedirectResponse
    {
        $validated = $request->validate([
            'nama_barang' => ['nullable', 'string', 'max:255'],
            'kode_barang' => ['required', 'string', 'max:50', 'unique:barang_pvc,kode_barang,' . $item->id],
            'kategori' => ['required', 'in:Tali Jepit,Outsole,Boloni Gunung'],
            'jenis' => ['nullable', 'string', 'max:100'],
            'warna' => ['nullable', 'string', 'max:100'],
            'stok_minimum' => ['required', 'integer', 'min:0'],
            'keterangan' => ['nullable', 'string', 'max:500'],
        ]);

        $this->normalizeProductVariant($validated, $request);
        $validated['nama_barang'] = $this->productName($validated);
        $validated['keterangan'] = null;
        $item->update($validated);

        $this->stockService->checkAndNotifyStock($item);

        return redirect()->back()->with('success', 'Data produk berhasil diperbarui.');
    }

    /**
     * Hapus barang PVC.
     */
    public function destroyItem(BarangPvc $item): RedirectResponse
    {
        // Cek jika barang sudah memiliki riwayat transaksi
        if ($item->barangMasuk()->exists() || $item->barangKeluar()->exists()) {
            return redirect()->back()->with('error', 'Produk tidak dapat dihapus karena sudah memiliki riwayat transaksi.');
        }

        $item->delete();
        return redirect()->back()->with('success', 'Produk berhasil dihapus.');
    }

    /** Terapkan struktur varian masing-masing kategori barang jadi. */
    private function normalizeProductVariant(array &$validated, Request $request): void
    {
        if ($validated['kategori'] === 'Outsole') {
            $request->validate(['jenis' => ['required', 'string', 'max:100']]);
            $validated['warna'] = null;
        } elseif ($validated['kategori'] === 'Boloni Gunung') {
            $request->validate(['warna' => ['required', 'string', 'max:100']]);
            $validated['jenis'] = null;
        } else {
            $request->validate([
                'jenis' => ['required', 'string', 'max:100'],
                'warna' => ['required', 'string', 'max:100'],
            ]);
        }
    }

    /** Bentuk nama otomatis agar selalu mengikuti kombinasi master produk. */
    private function productName(array $product): string
    {
        if ($product['kategori'] === 'Outsole') {
            return $product['jenis'];
        }

        return $product['kategori'] === 'Boloni Gunung'
            ? "Boloni Gunung - {$product['warna']}"
            : "{$product['jenis']} - {$product['warna']}";
    }

    /**
     * Tampilkan riwayat barang masuk.
     */
    public function incoming(Request $request): Response
    {
        $query = BarangMasuk::with('barangPvc');

        if ($search = $request->input('search')) {
            $query->whereHas('barangPvc', function($q) use ($search) {
                $q->where('nama_barang', 'like', "%{$search}%")
                  ->orWhere('kode_barang', 'like', "%{$search}%");
            });
        }

        $transactions = $query->orderBy('tanggal', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate(10)
            ->withQueryString();

        $barangList = BarangPvc::orderBy('kategori')->orderBy('jenis')->orderBy('warna')
            ->get(['id', 'nama_barang', 'kode_barang', 'kategori', 'jenis', 'warna', 'satuan']);

        return Inertia::render('Stock/Incoming', [
            'transactions' => $transactions,
            'barang_list' => $barangList,
            'filters' => $request->only(['search']),
        ]);
    }

    /**
     * Simpan transaksi barang masuk (Restock).
     */
    public function storeIncoming(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*.id_barang' => ['required', 'distinct', 'exists:barang_pvc,id'],
            'items.*.tanggal' => ['required', 'date', 'before_or_equal:today'],
            'items.*.jumlah' => ['required', 'integer', 'min:1'],
            'items.*.keterangan' => ['nullable', 'string', 'max:500'],
        ], [
            'items.*.id_barang.distinct' => 'Produk tidak boleh dipilih lebih dari satu kali dalam satu transaksi.',
            'items.*.tanggal.before_or_equal' => 'Tanggal transaksi tidak boleh melebihi hari ini.',
        ]);

        $this->stockService->recordIncomingBatch($validated['items']);

        return redirect()->back()->with('success', count($validated['items']).' hasil cetak berhasil disimpan ke stok.');
    }

    /**
     * Tampilkan riwayat barang keluar.
     */
    public function outgoing(Request $request): Response
    {
        $query = BarangKeluar::with('barangPvc');

        if ($search = $request->input('search')) {
            $query->where(function($q) use ($search) {
                $q->whereHas('barangPvc', function($sub) use ($search) {
                    $sub->where('nama_barang', 'like', "%{$search}%")
                        ->orWhere('kode_barang', 'like', "%{$search}%");
                })->orWhere('tujuan_penggunaan', 'like', "%{$search}%");
            });
        }

        $transactions = $query->orderBy('tanggal', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate(10)
            ->withQueryString();

        $barangList = BarangPvc::orderBy('kategori')->orderBy('jenis')->orderBy('warna')
            ->get(['id', 'nama_barang', 'kode_barang', 'kategori', 'jenis', 'warna', 'satuan', 'stok_saat_ini']);

        return Inertia::render('Stock/Outgoing', [
            'transactions' => $transactions,
            'barang_list' => $barangList,
            'filters' => $request->only(['search']),
        ]);
    }

    /**
     * Simpan transaksi barang keluar (Usage / Production).
     */
    public function storeOutgoing(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'pelanggan' => ['required', 'string', 'max:255'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.id_barang' => ['required', 'distinct', 'exists:barang_pvc,id'],
            'items.*.tanggal' => ['required', 'date', 'before_or_equal:today'],
            'items.*.jumlah' => ['required', 'integer', 'min:1'],
            'items.*.keterangan' => ['nullable', 'string', 'max:500'],
        ], [
            'items.*.id_barang.distinct' => 'Produk tidak boleh dipilih lebih dari satu kali dalam satu transaksi.',
            'items.*.tanggal.before_or_equal' => 'Tanggal pengiriman tidak boleh melebihi hari ini.',
        ]);

        try {
            $items = array_map(fn ($item) => [
                ...$item,
                'tujuan_penggunaan' => $validated['pelanggan'],
            ], $validated['items']);
            $this->stockService->recordOutgoingBatch($items);
        } catch (\InvalidArgumentException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal memproses pengiriman produk. Silakan periksa kembali data stok.');
        }

        return redirect()->back()->with('success', count($validated['items']).' produk berhasil dikirim ke pelanggan.');
    }

    /**
     * Ringkasan mutasi stok per minggu untuk staf gudang.
     */
    public function weeklyReport(Request $request): Response
    {
        return Inertia::render('Stock/WeeklyReport', $this->stockReportData($request));
    }

    public function exportStockReportExcel(Request $request): StreamedResponse
    {
        $data = $this->stockReportData($request);
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Laporan Gudang');
        $sheet->setShowGridlines(false);
        $sheet->mergeCells('A1:G1')->setCellValue('A1', 'LAPORAN MUTASI BARANG JADI - SUMBER PVC');
        $sheet->mergeCells('A2:G2')->setCellValue('A2', strtoupper($data['period']['label']).': '.$data['period']['start'].' - '.$data['period']['end']);
        $sheet->fromArray(['No', 'Kategori', 'Jenis', 'Warna', 'Masuk (kodi)', 'Keluar (kodi)', 'Selisih (kodi)'], null, 'A4');

        $row = 5;
        foreach ($data['rows'] as $index => $item) {
            $sheet->fromArray([$index + 1, $item['kategori'], $item['jenis'], $item['warna'], $item['masuk'], $item['keluar'], $item['selisih']], null, 'A'.$row++);
        }
        $sheet->fromArray(['', '', '', 'TOTAL', $data['totals']['masuk'], $data['totals']['keluar'], $data['totals']['selisih']], null, 'A'.$row);

        $sheet->getStyle('A1:G1')->applyFromArray(['font' => ['bold' => true, 'size' => 16, 'color' => ['rgb' => 'FFFFFF']], 'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '0F172A']], 'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]]);
        $sheet->getStyle('A2:G2')->applyFromArray(['font' => ['bold' => true, 'color' => ['rgb' => '1E40AF']], 'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'DBEAFE']], 'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]]);
        $sheet->getStyle('A4:G4')->applyFromArray(['font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']], 'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '2563EB']], 'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]]);
        $sheet->getStyle('A'.$row.':G'.$row)->applyFromArray(['font' => ['bold' => true], 'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E2E8F0']], 'borders' => ['top' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '94A3B8']]]]);
        $sheet->getStyle('E5:G'.$row)->getNumberFormat()->setFormatCode('#,##0');
        $sheet->getStyle('E5:G'.$row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $sheet->freezePane('A5');
        foreach (['A' => 7, 'B' => 18, 'C' => 22, 'D' => 16, 'E' => 17, 'F' => 17, 'G' => 17] as $column => $width) $sheet->getColumnDimension($column)->setWidth($width);
        $sheet->getRowDimension(1)->setRowHeight(28);
        $sheet->setAutoFilter('A4:G'.max(4, $row - 1));

        $writer = new Xlsx($spreadsheet);
        $filename = 'Laporan-Gudang-'.$data['period']['type'].'-'.$data['period']['key'].'.xlsx';
        return new StreamedResponse(fn () => $writer->save('php://output'), 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            'Cache-Control' => 'max-age=0',
        ]);
    }

    public function exportStockReportPdf(Request $request)
    {
        $data = $this->stockReportData($request);
        return Pdf::loadView('pdf.stock-report', $data)
            ->setPaper('a4', 'landscape')
            ->download('Laporan-Gudang-'.$data['period']['type'].'-'.$data['period']['key'].'.pdf');
    }

    private function stockReportData(Request $request): array
    {
        $validated = $request->validate([
            'type' => ['nullable', 'in:weekly,monthly'],
            'date' => ['nullable', 'date'],
            'week' => ['nullable', 'date'],
        ]);
        $type = $validated['type'] ?? 'weekly';
        $requestedDate = $validated['date'] ?? $validated['week'] ?? now()->toDateString();
        $date = Carbon::parse($requestedDate);
        $start = $type === 'monthly' ? $date->copy()->startOfMonth() : $date->copy()->startOfWeek(Carbon::MONDAY);
        $end = $type === 'monthly' ? $date->copy()->endOfMonth() : $date->copy()->endOfWeek(Carbon::SUNDAY);

        $incoming = BarangMasuk::query()
            ->whereDate('tanggal', '>=', $start->toDateString())
            ->whereDate('tanggal', '<=', $end->toDateString())
            ->selectRaw('id_barang, SUM(jumlah) as total')
            ->groupBy('id_barang')
            ->pluck('total', 'id_barang');

        $outgoing = BarangKeluar::query()
            ->whereDate('tanggal', '>=', $start->toDateString())
            ->whereDate('tanggal', '<=', $end->toDateString())
            ->selectRaw('id_barang, SUM(jumlah) as total')
            ->groupBy('id_barang')
            ->pluck('total', 'id_barang');

        $productIds = $incoming->keys()->merge($outgoing->keys())->unique();
        $rows = BarangPvc::query()
            ->whereIn('id', $productIds)
            ->orderBy('kategori')->orderBy('jenis')->orderBy('warna')
            ->get(['id', 'kategori', 'jenis', 'warna'])
            ->map(fn (BarangPvc $product) => [
                'id' => $product->id,
                'kategori' => $product->kategori,
                'jenis' => $product->jenis,
                'warna' => $product->warna,
                'masuk' => (int) ($incoming[$product->id] ?? 0),
                'keluar' => (int) ($outgoing[$product->id] ?? 0),
                'selisih' => (int) ($incoming[$product->id] ?? 0) - (int) ($outgoing[$product->id] ?? 0),
            ])->values();

        return [
            'period' => [
                'type' => $type,
                'date' => $type === 'monthly' ? $start->format('Y-m') : $start->toDateString(),
                'key' => $type === 'monthly' ? $start->format('Y-m') : $start->toDateString(),
                'label' => $type === 'monthly' ? 'Periode Bulanan' : 'Periode Mingguan',
                'start' => $start->translatedFormat('d M Y'),
                'end' => $end->translatedFormat('d M Y'),
            ],
            'rows' => $rows,
            'totals' => [
                'masuk' => (int) $incoming->sum(),
                'keluar' => (int) $outgoing->sum(),
                'selisih' => (int) $incoming->sum() - (int) $outgoing->sum(),
            ],
        ];
    }

    /**
     * Tampilkan daftar notifikasi stok kritis.
     */
    public function notifications(): Response
    {
        $notifications = NotifikasiStok::with('barangPvc')
            ->orderBy('is_read', 'asc')
            ->orderBy('tanggal', 'desc')
            ->get();

        return Inertia::render('Stock/Notifications', [
            'notifications' => $notifications,
            'unread_count' => NotifikasiStok::where('is_read', false)->count(),
        ]);
    }

    /**
     * Tandai notifikasi stok telah dibaca.
     */
    public function markAsRead(NotifikasiStok $notification): RedirectResponse
    {
        $notification->update(['is_read' => true]);
        return redirect()->back()->with('success', 'Notifikasi ditandai dibaca.');
    }

    /**
     * Tandai semua notifikasi dibaca.
     */
    public function readAllNotifications(): RedirectResponse
    {
        NotifikasiStok::where('is_read', false)->update(['is_read' => true]);
        return redirect()->back()->with('success', 'Semua notifikasi ditandai dibaca.');
    }
}
