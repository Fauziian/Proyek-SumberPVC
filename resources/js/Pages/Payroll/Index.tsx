import React, { useState } from "react";
import SumberPvcLayout from "@/Layouts/SumberPvcLayout";
import { Head, Link, useForm, router } from "@inertiajs/react";
import { CreditCard, FileText, ArrowRight, Printer, AlertCircle, Plus, Check } from "lucide-react";

interface PayrollSlip {
  id: number;
  periode: string;
  gaji_pokok: number;
  tunjangan: number;
  insentif_lembur: number;
  potongan: number;
  total_gaji: number;
  tarif_per_jam: number;
  total_jam_normal: number;
  jam_lembur: number;
  status: "draft" | "final";
  karyawan: {
    nama: string;
    jabatan: string;
  };
}

interface IndexProps {
  periodes: string[];
  selected_periode: string;
  payroll_list: PayrollSlip[];
  setting?: {
    tarif_kategori_a: number;
    tarif_kategori_b: number;
  };
  stats: {
    total_bersih: number;
    total_insentif: number;
    total_potongan: number;
    count: number;
  };
}

export default function Index({ periodes, selected_periode, payroll_list, setting, stats }: IndexProps) {
  const [periode, setPeriode] = useState(selected_periode);

  const { data, setData, processing } = useForm({
    periode: selected_periode,
  });

  // Format IDR
  const idr = (val: number) => {
    return new Intl.NumberFormat("id-ID", {
      style: "currency",
      currency: "IDR",
      minimumFractionDigits: 0
    }).format(val);
  };

  const handlePeriodChange = (val: string) => {
    setPeriode(val);
    setData("periode", val);
    router.get(route("payroll.index"), { periode: val }, { preserveState: true });
  };

  const handleGeneratePayroll = (e: React.FormEvent) => {
    e.preventDefault();
    if (confirm(`Apakah Anda yakin ingin memproses dan mengkalkulasi ulang gaji seluruh karyawan untuk periode ${periode}?`)) {
      router.post(route("payroll.calculate"), { periode }, {
        preserveScroll: true,
      });
    }
  };

  return (
    <SumberPvcLayout>
      <Head title="Manajemen Penggajian" />

      {/* Header bar */}
      <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <h1 className="text-xl font-bold text-slate-800">Slip & Rekap Gaji Karyawan</h1>
          <p className="text-xs text-slate-400 font-medium">Gaji bulanan dihitung dari durasi absensi aktual; lembur dimulai pada pilihan 14 jam.</p>
        </div>

        {/* Action Controls */}
        <div className="flex flex-wrap items-center gap-3">
          {/* Period selector */}
          <div className="flex items-center gap-2 bg-white px-3 py-2 border border-slate-200 rounded-lg shadow-sm">
            <label className="text-[10px] font-bold text-slate-400 uppercase">Periode:</label>
            <input
              type="month"
              value={periode}
              onChange={(e) => handlePeriodChange(e.target.value)}
              className="text-xs font-bold text-slate-700 bg-transparent outline-none"
            />
          </div>

          {/* Generate Button */}
          <button
            onClick={handleGeneratePayroll}
            disabled={processing}
            className="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold rounded-lg shadow-lg shadow-blue-500/10 transition-all cursor-pointer disabled:opacity-50"
          >
            <Plus size={14} />
            Generate Slip Gaji
          </button>
        </div>
      </div>

      {/* REKAP KEUANGAN BULANAN */}
      <div className="grid grid-cols-1 md:grid-cols-4 gap-6">
        <RecapCard label="Total Gaji Dibuat" value={idr(stats.total_bersih)} detail="Akumulasi upah jam normal dan lembur" color="text-blue-600 bg-blue-50" />
        <RecapCard label="Total Upah Lembur" value={idr(stats.total_insentif)} detail="Durasi kerja 14–15 jam" color="text-indigo-600 bg-indigo-50" />
        <RecapCard
          label="Tarif Pabrik"
          value={setting ? `Rp${Math.round(setting.tarif_kategori_a / 1000)}rb / Rp${Math.round(setting.tarif_kategori_b / 1000)}rb` : "Rp12rb / Rp17rb"}
          detail="Berdasarkan masa kerja karyawan"
          color="text-amber-600 bg-amber-50"
        />
        <RecapCard label="Jumlah Slip Digenerate" value={`${stats.count} Slip`} detail={`Untuk periode aktif: ${periode}`} color="text-purple-600 bg-purple-50" />
      </div>

      {/* TABLE SLIP GAJI */}
      <div className="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
        <div className="px-6 py-4 bg-slate-50/50 border-b border-slate-100 flex items-center justify-between">
          <h2 className="text-xs font-bold text-slate-700">Daftar Slip Gaji Karyawan</h2>
        </div>

        <div className="overflow-x-auto">
          <table className="w-full text-left border-collapse">
            <thead>
              <tr className="border-b border-slate-100 text-[10px] font-bold text-slate-400 uppercase tracking-wider">
                <th className="px-6 py-4">Karyawan</th>
                <th className="px-6 py-4">Jabatan</th>
                <th className="px-6 py-4">Tarif/Jam</th>
                <th className="px-6 py-4">Jam Normal</th>
                <th className="px-6 py-4">Jam Lembur</th>
                <th className="px-6 py-4">Upah Lembur</th>
                <th className="px-6 py-4">Total Gaji Bersih</th>
                <th className="px-6 py-4">Status</th>
                <th className="px-6 py-4 text-right">Aksi</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-slate-50 text-xs">
              {payroll_list.length === 0 ? (
                <tr>
                  <td colSpan={9} className="px-6 py-8 text-center text-slate-400">
                    Belum ada data slip gaji pada periode {periode}. Tekan tombol <strong>Generate Slip Gaji</strong> di atas untuk memproses data dari absensi.
                  </td>
                </tr>
              ) : (
                payroll_list.map((slip) => (
                  <tr key={slip.id} className="hover:bg-slate-50/50">
                    <td className="px-6 py-4">
                      <p className="font-semibold text-slate-800">{slip.karyawan.nama}</p>
                    </td>
                    <td className="px-6 py-4 text-slate-500 font-medium">{slip.karyawan.jabatan}</td>
                    <td className="px-6 py-4 font-medium text-slate-700">{idr(slip.tarif_per_jam)}</td>
                    <td className="px-6 py-4 text-slate-600">{slip.total_jam_normal} jam</td>
                    <td className="px-6 py-4 text-violet-600 font-bold">{slip.jam_lembur} jam</td>
                    <td className="px-6 py-4 text-slate-600">{idr(slip.insentif_lembur)}</td>
                    <td className="px-6 py-4 font-bold text-slate-800">{idr(slip.total_gaji)}</td>
                    <td className="px-6 py-4">
                      {slip.status === "final" ? (
                        <span className="inline-flex items-center gap-1 text-green-700 font-bold bg-green-50 px-2 py-0.5 rounded border border-green-200 text-[10px]">
                          <Check size={10} /> FINAL
                        </span>
                      ) : (
                        <span className="inline-flex items-center gap-1 text-blue-700 font-bold bg-blue-50 px-2 py-0.5 rounded border border-blue-200 text-[10px]">
                          DRAFT
                        </span>
                      )}
                    </td>
                    <td className="px-6 py-4 text-right">
                      <div className="inline-flex gap-2">
                        <Link
                          href={route("payroll.show", slip.id)}
                          className="inline-flex items-center gap-1 px-2.5 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 text-[10px] font-bold rounded transition-colors"
                        >
                          Rincian <ArrowRight size={12} />
                        </Link>
                        <a
                          href={route("payroll.pdf", slip.id)}
                          className="p-1.5 text-slate-400 hover:text-slate-600 hover:bg-slate-100 rounded transition-colors"
                          title="Unduh Slip PDF"
                        >
                          <Printer size={13} />
                        </a>
                      </div>
                    </td>
                  </tr>
                ))
              )}
            </tbody>
          </table>
        </div>
      </div>

    </SumberPvcLayout>
  );
}

function RecapCard({ label, value, detail, color }: { label: string, value: string, detail: string, color: string }) {
  return (
    <div className="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-sm space-y-2">
      <span className="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">{label}</span>
      <h3 className="text-2xl font-black text-slate-800 tabular-nums">{value}</h3>
      <p className="text-[10px] text-slate-400 font-medium">{detail}</p>
    </div>
  );
}
