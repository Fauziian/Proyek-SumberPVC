import React from "react";
import SumberPvcLayout from "@/Layouts/SumberPvcLayout";
import { Head, useForm } from "@inertiajs/react";
import { AlertCircle, Save, Settings } from "lucide-react";

interface SettingRecord {
  id: number;
  tarif_per_jam_kategori_a: number;
  tarif_per_jam_kategori_b: number;
  is_active: boolean;
  keterangan?: string;
  created_at: string;
}
interface Props { settings: SettingRecord[]; active_setting: SettingRecord; }

export default function SettingsPage({ settings, active_setting }: Props) {
  const { data, setData, post, processing, errors } = useForm({
    tarif_per_jam_kategori_a: active_setting?.tarif_per_jam_kategori_a ?? 12000,
    tarif_per_jam_kategori_b: active_setting?.tarif_per_jam_kategori_b ?? 17000,
    keterangan: "",
  });
  const idr = (value: number) => new Intl.NumberFormat("id-ID", { style: "currency", currency: "IDR", minimumFractionDigits: 0 }).format(value);
  const submit = (event: React.FormEvent) => {
    event.preventDefault();
    if (confirm("Aktifkan tarif per jam baru untuk perhitungan gaji berikutnya?")) post(route("payroll.settings.update"));
  };

  return <SumberPvcLayout>
    <Head title="Pengaturan Tarif Gaji" />
    <div><h1 className="text-xl font-bold text-slate-800">Pengaturan Tarif Gaji</h1><p className="text-xs text-slate-500">Tarif per jam mengikuti masa kerja karyawan. Durasi kerja 8–13 jam dihitung reguler, durasi 14–15 jam dicatat sebagai lembur.</p></div>
    <div className="grid grid-cols-1 gap-6 lg:grid-cols-3">
      <form onSubmit={submit} className="space-y-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm lg:col-span-2">
        <div className="flex items-center gap-2 border-b border-slate-100 pb-4"><Settings size={18} className="text-blue-600" /><h2 className="text-sm font-bold text-slate-800">Tarif per Jam Aktif</h2></div>
        <div className="flex gap-3 rounded-xl border border-blue-100 bg-blue-50 p-4 text-xs leading-relaxed text-blue-800"><AlertCircle size={16} className="shrink-0" /><p>Gaji bulanan = total jam normal × tarif + total jam lembur × tarif. Shift tidak mengubah tarif, hanya waktu kerja.</p></div>
        <div className="grid gap-4 sm:grid-cols-2">
          <RateInput label="Masa kerja kurang dari 5 tahun" value={data.tarif_per_jam_kategori_a} onChange={(value) => setData("tarif_per_jam_kategori_a", value)} error={errors.tarif_per_jam_kategori_a} />
          <RateInput label="Masa kerja 5 tahun atau lebih" value={data.tarif_per_jam_kategori_b} onChange={(value) => setData("tarif_per_jam_kategori_b", value)} error={errors.tarif_per_jam_kategori_b} />
        </div>
        <label className="block text-[10px] font-bold uppercase tracking-wider text-slate-400">Catatan perubahan (opsional)<input value={data.keterangan} onChange={(event) => setData("keterangan", event.target.value)} placeholder="Contoh: penyesuaian tarif tahun 2027" className="mt-1.5 w-full rounded-lg border border-slate-200 px-3 py-2 text-xs font-medium text-slate-700 outline-none focus:border-blue-500" /></label>
        <div className="flex justify-end border-t border-slate-100 pt-4"><button disabled={processing} className="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-xs font-bold text-white disabled:opacity-50"><Save size={14} />Simpan Tarif</button></div>
      </form>
      <aside className="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"><h2 className="text-sm font-bold text-slate-800">Riwayat Tarif</h2><div className="mt-4 space-y-3">{settings.map((item) => <div key={item.id} className={`rounded-xl border p-3 text-xs ${item.is_active ? "border-blue-200 bg-blue-50" : "border-slate-100 bg-slate-50"}`}><div className="flex justify-between"><strong>Revisi #{item.id}</strong>{item.is_active && <span className="text-[9px] font-bold text-blue-700">AKTIF</span>}</div><p className="mt-2 text-slate-600">&lt; 5 tahun: <strong>{idr(item.tarif_per_jam_kategori_a)}/jam</strong></p><p className="text-slate-600">≥ 5 tahun: <strong>{idr(item.tarif_per_jam_kategori_b)}/jam</strong></p>{item.keterangan && <p className="mt-2 border-t border-slate-200 pt-2 text-[10px] italic text-slate-400">{item.keterangan}</p>}</div>)}</div></aside>
    </div>
  </SumberPvcLayout>;
}

function RateInput({ label, value, onChange, error }: { label: string; value: number; onChange: (value: number) => void; error?: string }) {
  return <label className="block text-[10px] font-bold uppercase tracking-wider text-slate-400">{label}<div className="relative mt-1.5"><span className="absolute left-3 top-1/2 -translate-y-1/2 text-xs font-bold text-slate-400">Rp</span><input type="number" min="0" required value={value} onChange={(event) => onChange(Number(event.target.value))} className="w-full rounded-lg border border-slate-200 py-2 pl-9 pr-12 text-xs font-bold text-slate-700 outline-none focus:border-blue-500" /><span className="absolute right-3 top-1/2 -translate-y-1/2 text-[10px] font-bold text-slate-400">/ jam</span></div>{error && <p className="mt-1 text-[10px] text-red-600">{error}</p>}</label>;
}
