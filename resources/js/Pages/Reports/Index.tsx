import React, { useState } from "react";
import SumberPvcLayout from "@/Layouts/SumberPvcLayout";
import { Head } from "@inertiajs/react";
import { FileText, Printer, Download, Calendar } from "lucide-react";

interface ReportsProps {
  periodes: string[];
  role: "admin" | "hr" | "warehouse";
}

export default function Index({ periodes, role }: ReportsProps) {
  const getLocalMonth = () => {
    const d = new Date();
    return new Date(d.getTime() - d.getTimezoneOffset() * 60000).toISOString().slice(0, 7);
  };

  const [selectedBulan, setSelectedBulan] = useState(getLocalMonth);
  const [selectedPeriodePayroll, setSelectedPeriodePayroll] = useState(() => {
    return periodes[0] || getLocalMonth();
  });

  return (
    <SumberPvcLayout>
      <Head title="Pusat Rekapitulasi Laporan" />

      {/* Header bar */}
      <div>
        <h1 className="text-xl font-bold text-slate-800">Pusat Rekapitulasi & Laporan</h1>
        <p className="text-xs text-slate-400">Ekspor mutasi stok gudang, kehadiran harian, dan pembayaran gaji ke format Excel / PDF.</p>
      </div>

      <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
        
        {/* MODUL HR: LAPORAN KEHADIRAN (PDF) */}
        {(role === "hr" || role === "admin") && (
          <div className="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-6 flex flex-col justify-between space-y-6">
            <div className="space-y-3">
              <div className="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center shadow-sm">
                <FileText size={18} strokeWidth={2.5} />
              </div>
              <div>
                <h3 className="text-sm font-bold text-slate-800">Laporan Kehadiran Karyawan</h3>
                <p className="text-xs text-slate-400 mt-1 leading-relaxed">
                  Unduh ringkasan kehadiran bulanan (Jumlah Hadir Penuh, Lembur, dan Hadir Setengah Hari) karyawan.
                </p>
              </div>
            </div>

            <div className="space-y-3 pt-4 border-t border-slate-100">
              <div className="flex flex-col gap-1">
                <label className="text-[9px] font-bold text-slate-400 uppercase tracking-widest">Pilih Bulan Kehadiran</label>
                <div className="relative">
                  <Calendar size={13} className="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" />
                  <input
                    type="month"
                    value={selectedBulan}
                    onChange={(e) => setSelectedBulan(e.target.value)}
                    className="w-full pl-8 pr-3 py-2 border border-slate-200 rounded-lg text-xs font-semibold text-slate-700 outline-none"
                  />
                </div>
              </div>

              <a
                href={route(role === "admin" ? "admin.reports.attendance.pdf" : "reports.attendance.pdf", { bulan: selectedBulan })}
                className="w-full inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold rounded-lg shadow-lg shadow-blue-500/10 transition-all text-center"
              >
                <Printer size={13} />
                Unduh PDF Laporan
              </a>
            </div>
          </div>
        )}

        {/* MODUL HR/ADMIN: LAPORAN PENGGAJIAN (EXCEL) */}
        {(role === "hr" || role === "admin") && (
          <div className="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-6 flex flex-col justify-between space-y-6">
            <div className="space-y-3">
              <div className="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center shadow-sm">
                <FileText size={18} strokeWidth={2.5} />
              </div>
              <div>
                <h3 className="text-sm font-bold text-slate-800">Laporan Rekapitulasi Gaji</h3>
                <p className="text-xs text-slate-400 mt-1 leading-relaxed">
                  Ekspor data rekapitulasi gaji bulanan berdasarkan jam kerja normal, upah lembur, dan total gaji bersih karyawan.
                </p>
              </div>
            </div>

            <div className="space-y-3 pt-4 border-t border-slate-100">
              <div className="flex flex-col gap-1">
                <label className="text-[9px] font-bold text-slate-400 uppercase tracking-widest">Pilih Periode Penggajian</label>
                <div className="relative">
                  <Calendar size={13} className="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" />
                  <input
                    type="month"
                    value={selectedPeriodePayroll}
                    onChange={(e) => setSelectedPeriodePayroll(e.target.value)}
                    className="w-full pl-8 pr-3 py-2 border border-slate-200 rounded-lg text-xs font-semibold text-slate-700 outline-none"
                  />
                </div>
              </div>

              <a
                href={route(role === "admin" ? "admin.reports.payroll.excel" : "reports.payroll.excel", { periode: selectedPeriodePayroll })}
                className="w-full inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold rounded-lg shadow-lg shadow-emerald-500/10 transition-all text-center"
              >
                <Download size={13} />
                Unduh Rekap Excel
              </a>
            </div>
          </div>
        )}

        {/* MODUL GUDANG: MUTASI STOK PRODUK JADI PVC (EXCEL) */}
        {(role === "warehouse" || role === "admin") && (
          <div className="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-6 flex flex-col justify-between space-y-6">
            <div className="space-y-3">
              <div className="w-10 h-10 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center shadow-sm">
                <FileText size={18} strokeWidth={2.5} />
              </div>
              <div>
                <h3 className="text-sm font-bold text-slate-800">Laporan Mutasi Stok Gudang</h3>
                <p className="text-xs text-slate-400 mt-1 leading-relaxed">
                  Ekspor ringkasan mutasi produk jadi PVC Outsole & Tali Jepit (kuantitas masuk hasil cetak, kuantitas pengiriman keluar, dan sisa stok).
                </p>
              </div>
            </div>

            <div className="space-y-3 pt-4 border-t border-slate-100">
              <div className="flex flex-col gap-1">
                <label className="text-[9px] font-bold text-slate-400 uppercase tracking-widest">Pilih Bulan Mutasi</label>
                <div className="relative">
                  <Calendar size={13} className="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" />
                  <input
                    type="month"
                    value={selectedBulan}
                    onChange={(e) => setSelectedBulan(e.target.value)}
                    className="w-full pl-8 pr-3 py-2 border border-slate-200 rounded-lg text-xs font-semibold text-slate-700 outline-none"
                  />
                </div>
              </div>

              <a
                href={route(role === "admin" ? "admin.reports.stock.excel" : "reports.stock.excel", { bulan: selectedBulan })}
                className="w-full inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold rounded-lg shadow-lg shadow-indigo-500/10 transition-all text-center"
              >
                <Download size={13} />
                Unduh Rekap Mutasi
              </a>
            </div>
          </div>
        )}

      </div>

    </SumberPvcLayout>
  );
}
