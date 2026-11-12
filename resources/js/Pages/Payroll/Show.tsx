import React from "react";
import SumberPvcLayout from "@/Layouts/SumberPvcLayout";
import { Head, Link, router } from "@inertiajs/react";
import { ArrowLeft, Printer, CheckCircle, HelpCircle } from "lucide-react";

interface PayrollSlip {
  id: number;
  periode: string;
  gaji_pokok: number;
  tunjangan: number;
  insentif_lembur: number;
  potongan: number;
  total_gaji: number;
  hari_hadir: number;
  hari_setengah: number;
  jam_lebih: number;
  jam_lembur: number;
  tarif_per_jam: number;
  total_jam_normal: number;
  status: "draft" | "final";
  rincian: {
    kategori_masa_kerja: "A" | "B";
    tarif_per_jam: number;
    upah_jam_normal: number;
    upah_lembur: number;
    absensi: {
      tanggal: string;
      durasi: number;
      shift: string;
      jam_normal: number;
      jam_lembur: number;
    }[];
  };
  karyawan: {
    nama: string;
    jabatan: string;
    tanggal_masuk: string;
  };
}

interface ShowProps {
  payroll: PayrollSlip;
}

export default function Show({ payroll }: ShowProps) {
  // Format IDR
  const idr = (val: number) => {
    return new Intl.NumberFormat("id-ID", {
      style: "currency",
      currency: "IDR",
      minimumFractionDigits: 0
    }).format(val);
  };

  const handleFinalize = () => {
    if (confirm("Apakah Anda yakin ingin memfinalisasi slip gaji ini? Status slip gaji tidak dapat dikembalikan menjadi DRAFT setelah diselesaikan.")) {
      router.put(route("payroll.finalize", payroll.id));
    }
  };

  return (
    <SumberPvcLayout>
      <Head title={`Slip Gaji - ${payroll.karyawan.nama}`} />

      {/* Back & Action Buttons */}
      <div className="flex flex-wrap items-center justify-between gap-4">
        <Link
          href={route("payroll.index", { periode: payroll.periode })}
          className="inline-flex items-center gap-2 text-xs font-bold text-slate-500 hover:text-slate-800 transition-colors"
        >
          <ArrowLeft size={14} />
          Kembali ke Rekap Periode
        </Link>

        <div className="flex gap-2">
          {payroll.status === "draft" && (
            <button
              onClick={handleFinalize}
              className="inline-flex items-center gap-1.5 px-3.5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold rounded-lg shadow-lg shadow-emerald-500/10 transition-all cursor-pointer"
            >
              <CheckCircle size={14} />
              Finalisasi Slip
            </button>
          )}
          <a
            href={route("payroll.pdf", payroll.id)}
            className="inline-flex items-center gap-1.5 px-3.5 py-2 border border-slate-200 hover:bg-slate-50 text-slate-700 text-xs font-bold rounded-lg bg-white shadow-sm transition-all"
          >
            <Printer size={14} />
            Cetak PDF
          </a>
        </div>
      </div>

      {/* SLIP CONTENT GRID */}
      <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        {/* DETAIL KALKULASI GAJI (2 Kolom) */}
        <div className="lg:col-span-2 space-y-6">
          <div className="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden p-6 space-y-6">
            
            {/* Header info */}
            <div className="flex flex-col sm:flex-row justify-between border-b border-slate-100 pb-5 gap-4">
              <div>
                <span className="text-[9px] font-bold text-slate-400 uppercase tracking-widest bg-slate-100 px-2 py-0.5 rounded">
                  Sumber PVC Outsole Tali Jepit
                </span>
                <h2 className="text-lg font-black text-slate-800 mt-2">Slip Gaji Karyawan</h2>
                <p className="text-xs text-slate-400 font-semibold mt-0.5">Periode Bulan: {payroll.periode}</p>
              </div>

              <div className="text-left sm:text-right">
                <p className="text-xs font-bold text-slate-800">{payroll.karyawan.nama}</p>
                <p className="text-[11px] text-slate-400 font-semibold mt-0.5">{payroll.karyawan.jabatan}</p>
                <p className="text-[10px] text-slate-400 mt-0.5">
                  Masa Kerja: {payroll.rincian.kategori_masa_kerja === "B" ? "≥ 5 Tahun (Kat B)" : "< 5 Tahun (Kat A)"}
                </p>
              </div>
            </div>

            {/* Rincian Komponen */}
            <div className="grid grid-cols-1 md:grid-cols-2 gap-8">
              {/* PENDAPATAN */}
              <div className="space-y-4">
                <h3 className="text-xs font-black text-slate-800 border-b border-slate-100 pb-2 uppercase tracking-wider text-blue-600">
                  Komponen Pendapatan
                </h3>
                
                <div className="space-y-3 text-xs">
                  <ItemRow label={`Tarif kerja (${payroll.rincian.kategori_masa_kerja === "B" ? "≥5 tahun" : "<5 tahun"})`} val={`${idr(payroll.tarif_per_jam)} / jam`} />
                  <ItemRow label={`Upah kerja reguler (${payroll.total_jam_normal} jam)`} val={idr(payroll.gaji_pokok)} sub="Pilihan durasi 8 sampai 13 jam" />
                  <ItemRow label={`Upah lembur (${payroll.jam_lembur} jam)`} val={idr(payroll.insentif_lembur)} sub="Dimulai pada pilihan 14 jam" />

                  <div className="border-t border-slate-100 pt-3 flex justify-between font-bold text-slate-800">
                    <span>Total Pendapatan Kotor</span>
                    <span>{idr(payroll.gaji_pokok + payroll.insentif_lembur)}</span>
                  </div>
                </div>
              </div>

              <div className="space-y-4">
                <h3 className="text-xs font-black text-slate-800 border-b border-slate-100 pb-2 uppercase tracking-wider text-violet-600">Aturan Perhitungan</h3>
                <p className="text-xs leading-relaxed text-slate-500">Pergantian shift tidak mengubah tarif. Durasi 8–13 jam dicatat sebagai kerja reguler, sedangkan pilihan 14–15 jam menjadi lembur.</p>
              </div>
            </div>

            {/* Total Bersih */}
            <div className="bg-blue-50/60 rounded-xl border border-blue-100 p-4 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3">
              <div>
                <h4 className="text-xs font-extrabold text-blue-900 uppercase tracking-wider">Gaji Bersih Yang Diterima (Netto)</h4>
                <p className="text-[10px] text-slate-400 font-semibold mt-0.5">Sudah disesuaikan dengan rekap log absensi</p>
              </div>
              <span className="text-2xl font-black text-blue-700">{idr(payroll.total_gaji)}</span>
            </div>

          </div>
        </div>

        {/* LOG ABSENSI YANG MEWAKILI (1 Kolom) */}
        <div className="space-y-6">
          <div className="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-5 space-y-4">
            <h3 className="text-sm font-bold text-slate-800">Log Rekap Absensi Terhitung</h3>
            
            <div className="grid grid-cols-2 gap-4 text-center">
              <div className="bg-slate-50 p-2.5 rounded-lg border border-slate-100">
                <span className="text-[9px] font-bold text-slate-400 uppercase">Hari Tercatat</span>
                <span className="text-base font-black text-slate-800 block mt-0.5">{payroll.hari_hadir} Hari</span>
              </div>
              <div className="bg-slate-50 p-2.5 rounded-lg border border-slate-100">
                <span className="text-[9px] font-bold text-slate-400 uppercase">Total Lembur</span>
                <span className="text-base font-black text-violet-600 block mt-0.5">{payroll.jam_lembur} Jam</span>
              </div>
            </div>

            {/* List log absensi detail */}
            <div className="space-y-2 max-h-80 overflow-y-auto pr-1" style={{ scrollbarWidth: "thin" }}>
              {payroll.rincian.absensi.length === 0 ? (
                <p className="text-center text-xs text-slate-400 py-4">Belum ada absensi tercatat pada bulan ini.</p>
              ) : (
                payroll.rincian.absensi.map((abs, idx) => (
                  <div key={idx} className="flex justify-between items-center bg-slate-50/50 hover:bg-slate-50 p-2 rounded border border-slate-100/50 text-xs">
                    <div>
                      <p className="font-semibold text-slate-700">{abs.tanggal}</p>
                      <p className="text-[10px] text-slate-400 font-medium">{abs.shift} · normal {abs.jam_normal}j · lembur {abs.jam_lembur}j</p>
                    </div>
                    <span className="rounded border border-slate-200 bg-white px-1.5 py-0.5 text-[9px] font-bold text-slate-600">{abs.durasi} JAM</span>
                  </div>
                ))
              )}
            </div>
          </div>
        </div>

      </div>

    </SumberPvcLayout>
  );
}

function ItemRow({ label, val, sub }: { label: string, val: string, sub?: string }) {
  return (
    <div className="flex justify-between items-start gap-4">
      <div>
        <p className="font-semibold text-slate-700">{label}</p>
        {sub && <p className="text-[10px] text-slate-400 font-semibold">{sub}</p>}
      </div>
      <span className="font-bold text-slate-800 whitespace-nowrap">{val}</span>
    </div>
  );
}
