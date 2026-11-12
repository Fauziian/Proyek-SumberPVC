<?php

namespace App\Http\Controllers;

use App\Models\Karyawan;
use App\Models\KomponenGaji;
use App\Models\SettingGaji;
use App\Services\PayrollCalculationService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class PayrollController extends Controller
{
    protected PayrollCalculationService $payrollService;

    public function __construct(PayrollCalculationService $payrollService)
    {
        $this->payrollService = $payrollService;
    }

    /**
     * Tampilkan riwayat rekap penggajian bulanan.
     */
    public function index(Request $request): Response
    {
        // Ambil daftar periode yang ada
        $periodes = KomponenGaji::select('periode')
            ->groupBy('periode')
            ->orderBy('periode', 'desc')
            ->pluck('periode')
            ->toArray();

        $selectedPeriode = $request->input('periode', !empty($periodes) ? $periodes[0] : now()->format('Y-m'));
        if (!preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $selectedPeriode)) {
            $selectedPeriode = !empty($periodes) ? $periodes[0] : now()->format('Y-m');
        }

        // Query komponen gaji pada periode terpilih
        $payrollList = KomponenGaji::with('karyawan')
            ->where('periode', $selectedPeriode)
            ->get();

        $totalGajiClean = $payrollList->sum('total_gaji');
        $totalInsentif = $payrollList->sum('insentif_lembur');
        $totalPotongan = $payrollList->sum('potongan');
        $setting = SettingGaji::aktif();

        return Inertia::render('Payroll/Index', [
            'periodes' => $periodes,
            'selected_periode' => $selectedPeriode,
            'payroll_list' => $payrollList,
            'setting' => [
                'tarif_kategori_a' => $setting->tarif_per_jam_kategori_a,
                'tarif_kategori_b' => $setting->tarif_per_jam_kategori_b,
            ],
            'stats' => [
                'total_bersih' => $totalGajiClean,
                'total_insentif' => $totalInsentif,
                'total_potongan' => $totalPotongan,
                'count' => $payrollList->count(),
            ],
        ]);
    }

    /**
     * Proses/kalkulasi ulang gaji massal untuk seluruh karyawan pada periode tertentu.
     */
    public function calculatePeriod(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'periode' => ['required', 'date_format:Y-m'],
        ]);

        $periode = $validated['periode'];
        $start = Carbon::createFromFormat('Y-m', $periode)->startOfMonth();
        $end = Carbon::createFromFormat('Y-m', $periode)->endOfMonth();

        // Ambil semua karyawan aktif atau yang memiliki absensi pada periode ini (S-06)
        $karyawans = Karyawan::where('is_active', true)
            ->orWhereHas('absensi', function ($q) use ($start, $end) {
                $q->whereDate('tanggal', '>=', $start->toDateString())
                  ->whereDate('tanggal', '<=', $end->toDateString());
            })
            ->get();

        if ($karyawans->isEmpty()) {
            return redirect()->back()->with('error', 'Tidak ada karyawan aktif atau tercatat hadir pada periode tersebut.');
        }

        $generated = 0;
        $skipped = 0;
        foreach ($karyawans as $emp) {
            $existing = KomponenGaji::where('id_karyawan', $emp->id)->where('periode', $periode)->first();
            if ($existing && $existing->status === 'final') {
                $skipped++;
                continue;
            }
            $this->payrollService->generate($emp, $periode);
            $generated++;
        }

        if ($generated === 0 && $skipped > 0) {
            return redirect()->route('payroll.index', ['periode' => $periode])
                ->with('error', "Seluruh slip gaji periode {$periode} sudah difinalisasi dan tidak dapat digenerate ulang.");
        }

        $msg = "Proses penggajian periode {$periode} berhasil: {$generated} slip digenerate.";
        if ($skipped > 0) {
            $msg .= " ({$skipped} slip berstatus FINAL dilewati dan tidak diubah).";
        }

        return redirect()->route('payroll.index', ['periode' => $periode])
            ->with('success', $msg);
    }

    /**
     * Tampilkan detail preview slip gaji karyawan tertentu.
     */
    public function show(KomponenGaji $payroll): Response
    {
        $payroll->load('karyawan');

        return Inertia::render('Payroll/Show', [
            'payroll' => $payroll,
        ]);
    }

    /**
     * Finalisasi status slip gaji (draft -> final).
     */
    public function finalize(KomponenGaji $payroll): RedirectResponse
    {
        if ($payroll->status === 'final') {
            return redirect()->back()->with('error', 'Status slip gaji sudah FINAL.');
        }

        $payroll->update(['status' => 'final']);

        return redirect()->back()->with('success', 'Status slip gaji berhasil diselesaikan (Final).');
    }

    /**
     * Download Slip Gaji PDF secara live.
     */
    public function downloadPdf(KomponenGaji $payroll)
    {
        $payroll->load('karyawan');
        $setting = SettingGaji::aktif();

        $pdf = Pdf::loadView('pdf.salary-slip', [
            'payroll' => $payroll,
            'setting' => $setting,
            'tanggal_cetak' => now()->isoFormat('D MMMM Y'),
            'petugas' => auth()->user()->nama ?? 'Staff HRD',
        ]);

        // Kustomisasi layout slip kertas kecil landscape
        $pdf->setPaper([0, 0, 612, 396], 'landscape'); // Ukuran kustom slip gaji

        return $pdf->download("Slip-Gaji-{$payroll->karyawan->nama}-{$payroll->periode}.pdf");
    }

    /**
     * Tampilkan halaman Pengaturan Gaji (Konfigurasi).
     */
    public function settings(): Response
    {
        $settings = SettingGaji::orderBy('created_at', 'desc')->get();
        $activeSetting = SettingGaji::aktif();

        return Inertia::render('Payroll/Settings', [
            'settings' => $settings,
            'active_setting' => $activeSetting,
        ]);
    }

    /**
     * Update atau buat konfigurasi setting gaji baru.
     */
    public function updateSettings(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'tarif_per_jam_kategori_a' => ['required', 'integer', 'min:0'],
            'tarif_per_jam_kategori_b' => ['required', 'integer', 'min:0'],
            'keterangan' => ['nullable', 'string', 'max:255'],
        ]);

        // Nonaktifkan config yang lama
        SettingGaji::where('is_active', true)->update(['is_active' => false]);

        // Buat config baru yang aktif
        // Kolom lama dipertahankan demi kompatibilitas data, namun perhitungan
        // pabrik sekarang sepenuhnya memakai tarif per jam.
        $validated += [
            'gaji_pokok_kategori_a' => 0,
            'gaji_pokok_kategori_b' => 0,
            'insentif_jam_lebih_pct' => 0,
            'insentif_lembur_pct' => 0,
            'potongan_setengah_pct' => 0,
            'is_active' => true,
        ];
        SettingGaji::create($validated);

        return redirect()->back()
            ->with('success', 'Konfigurasi parameter penggajian berhasil diperbarui.');
    }
}
