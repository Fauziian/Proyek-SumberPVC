import { useState } from "react";
import SumberPvcLayout from "@/Layouts/SumberPvcLayout";
import { Head, router, useForm } from "@inertiajs/react";
import { Calendar, Clock, LogIn, LogOut, X, Trash2 } from "lucide-react";

interface Attendance {
  id: number;
  jam_masuk: string;
  jam_keluar: string;
  durasi_jam: number;
  jam_normal: number;
  jam_lembur: number;
  sudah_pulang: boolean;
  status_kehadiran: string;
  keterangan?: string;
}
interface Row { id_karyawan: number; nama: string; jabatan: string; absensi?: Attendance | null; }
interface Props {
  rows: Row[];
  stats: { total: number; sudah_masuk: number; penuh: number; jam_lebih: number; lembur: number; setengah_hari: number; absen: number };
  tanggal: string;
  formatted_tanggal: string;
}
const DURASI = [4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15];
const waktuWibSekarang = () => new Intl.DateTimeFormat("en-GB", { timeZone: "Asia/Jakarta", hour: "2-digit", minute: "2-digit", hour12: false }).format(new Date());
const todayWib = () => new Intl.DateTimeFormat("en-CA", { timeZone: "Asia/Jakarta" }).format(new Date());

export default function AttendanceIndex({ rows, stats, tanggal, formatted_tanggal }: Props) {
  const [selectedTanggal, setSelectedTanggal] = useState(tanggal);
  const [active, setActive] = useState<Row | null>(null);
  const [activeCheckIn, setActiveCheckIn] = useState<Row | null>(null);
  const [jamMasuk, setJamMasuk] = useState(waktuWibSekarang());
  const [checkingInId, setCheckingInId] = useState<number | null>(null);
  const { data, setData, put, processing, errors, reset } = useForm({ durasi_jam: 8, keterangan: "" });
  const checkIn = (event: React.FormEvent) => {
    event.preventDefault();
    if (!activeCheckIn) return;
    setCheckingInId(activeCheckIn.id_karyawan);
    router.post(route("attendance.check-in"), { id_karyawan: activeCheckIn.id_karyawan, tanggal: selectedTanggal, jam_masuk: jamMasuk }, {
      onSuccess: () => setActiveCheckIn(null),
      onFinish: () => setCheckingInId(null),
    });
  };
  const checkOut = (event: React.FormEvent) => {
    event.preventDefault();
    if (!active?.absensi) return;
    put(route("attendance.check-out", active.absensi.id), { onSuccess: () => { setActive(null); reset(); } });
  };

  const handleDeleteAbsensi = (id: number) => {
    if (confirm("Apakah Anda yakin ingin membatalkan/menghapus catatan kehadiran ini?")) {
      router.delete(route("attendance.destroy", id));
    }
  };

  return <SumberPvcLayout>
    <Head title="Absensi Karyawan" />
    <div className="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
      <div><h1 className="text-xl font-bold text-slate-800">Absensi Karyawan</h1><p className="text-xs text-slate-500">Klik Masuk saat bekerja, lalu klik Pulang dan pilih durasi kerja. Durasi &lt;8 jam dicatat setengah hari, 14–15 jam otomatis menjadi lembur.</p></div>
      <label className="flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-2 shadow-sm"><Calendar size={14} className="text-slate-400" /><input type="date" max={todayWib()} value={selectedTanggal} onChange={(event) => { setSelectedTanggal(event.target.value); router.get(route("attendance.index"), { tanggal: event.target.value }, { preserveState: true }); }} className="bg-transparent text-xs font-semibold text-slate-700 outline-none" /></label>
    </div>

    <div className="grid grid-cols-2 gap-3 md:grid-cols-5">
      <Card label="Karyawan Aktif" value={stats.total} color="text-blue-600" />
      <Card label="Sudah Masuk" value={stats.sudah_masuk} color="text-emerald-600" />
      <Card label="Sudah Pulang" value={stats.penuh} color="text-slate-700" />
      <Card label="Lembur 14–15 Jam" value={stats.jam_lebih} color="text-violet-600" />
      <Card label="Belum Masuk" value={stats.absen} color="text-amber-600" />
    </div>

    <div className="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
      <div className="border-b border-slate-100 bg-slate-50 px-6 py-4"><p className="text-xs font-bold text-slate-700">Absensi tanggal {formatted_tanggal}</p><p className="mt-1 text-[11px] text-slate-400">Saat Masuk, waktu WIB otomatis terisi dan dapat diubah. Jam pulang dihitung dari durasi yang dipilih.</p></div>
      <div className="overflow-x-auto"><table className="w-full min-w-[820px] text-left text-xs">
        <thead className="border-b border-slate-100 text-[10px] uppercase tracking-wider text-slate-400"><tr><th className="px-6 py-4">Karyawan</th><th className="px-6 py-4">Jam Masuk</th><th className="px-6 py-4">Jam Pulang</th><th className="px-6 py-4">Durasi</th><th className="px-6 py-4">Status</th><th className="px-6 py-4 text-right">Aksi</th></tr></thead>
        <tbody className="divide-y divide-slate-50">{rows.map((row) => {
          const absensi = row.absensi;
          const belumMasuk = !absensi;
          const belumPulang = Boolean(absensi && !absensi.sudah_pulang);
          return <tr key={row.id_karyawan} className="hover:bg-slate-50/70">
            <td className="px-6 py-4"><p className="font-bold text-slate-800">{row.nama}</p><p className="mt-0.5 text-[10px] text-slate-400">{row.jabatan}</p></td>
            <td className="px-6 py-4">{belumMasuk ? <span className="text-slate-400">Belum masuk</span> : <span className="font-bold text-emerald-600">{absensi!.jam_masuk}</span>}</td>
            <td className="px-6 py-4">{belumPulang || belumMasuk ? <span className="text-slate-400">Belum pulang</span> : <span className="font-bold text-slate-700">{absensi!.jam_keluar}</span>}</td>
            <td className="px-6 py-4">{belumPulang || belumMasuk ? <span className="text-slate-400">Pilih saat pulang</span> : <span className="font-bold text-slate-800">{absensi!.durasi_jam} jam</span>}</td>
            <td className="px-6 py-4">{belumMasuk ? <Badge text="BELUM MASUK" type="pending" /> : belumPulang ? <Badge text="HADIR / MASUK" type="working" /> : absensi!.status_kehadiran === "Lembur" ? <Badge text="LEMBUR" type="overtime" /> : absensi!.status_kehadiran === "Setengah Hari" ? <Badge text="SETENGAH HARI" type="pending" /> : <Badge text="SELESAI" type="done" />}</td>
            <td className="px-6 py-4 text-right">{belumMasuk ? (
              <button disabled={checkingInId === row.id_karyawan} onClick={() => { setActiveCheckIn(row); setJamMasuk(waktuWibSekarang()); }} className="inline-flex items-center gap-1.5 rounded-lg bg-emerald-600 px-3 py-1.5 text-[10px] font-bold text-white hover:bg-emerald-700 disabled:opacity-50 cursor-pointer"><LogIn size={13} />Masuk</button>
            ) : belumPulang ? (
              <div className="flex items-center justify-end gap-2">
                <button onClick={() => { setActive(row); setData({ durasi_jam: 8, keterangan: "" }); }} className="inline-flex items-center gap-1.5 rounded-lg bg-blue-600 px-3 py-1.5 text-[10px] font-bold text-white hover:bg-blue-700 cursor-pointer"><LogOut size={13} />Pulang</button>
                <button type="button" onClick={() => handleDeleteAbsensi(absensi!.id)} title="Hapus / Batal Kehadiran" className="p-1.5 text-slate-400 hover:text-red-600 rounded-lg transition-colors cursor-pointer"><Trash2 size={14} /></button>
              </div>
            ) : (
              <div className="flex items-center justify-end gap-2">
                <span className="text-[10px] font-bold text-slate-400">Tercatat</span>
                <button type="button" onClick={() => handleDeleteAbsensi(absensi!.id)} title="Hapus / Batal Kehadiran" className="p-1.5 text-slate-400 hover:text-red-600 rounded-lg transition-colors cursor-pointer"><Trash2 size={14} /></button>
              </div>
            )}</td>
          </tr>;
        })}</tbody>
      </table></div>
    </div>

    {activeCheckIn && <div className="fixed inset-0 z-50 flex items-center justify-center p-4"><button onClick={() => setActiveCheckIn(null)} className="absolute inset-0 bg-slate-900/40" aria-label="Tutup" /><form onSubmit={checkIn} className="relative w-full max-w-sm rounded-2xl bg-white shadow-2xl"><div className="flex items-center justify-between border-b border-slate-100 px-6 py-4"><div><h2 className="text-sm font-bold text-slate-800">Catat Jam Masuk</h2><p className="text-xs text-slate-400">{activeCheckIn.nama} · {formatted_tanggal}</p></div><button type="button" onClick={() => setActiveCheckIn(null)} className="text-slate-400"><X size={18} /></button></div><div className="space-y-4 p-6"><label className="block text-[10px] font-bold uppercase tracking-wider text-slate-400">Jam Masuk (WIB)<input type="time" required value={jamMasuk} onChange={(event) => setJamMasuk(event.target.value)} className="mt-1.5 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm font-bold text-slate-700 outline-none focus:border-emerald-500" /></label><div className="rounded-lg bg-emerald-50 p-3 text-xs leading-relaxed text-emerald-700"><Clock size={13} className="mr-1 inline" />Waktu awal otomatis mengikuti WIB saat ini. Ubah bila absensi dicatat terlambat, misalnya 08.00.</div><div className="flex justify-end gap-2 border-t border-slate-100 pt-4"><button type="button" onClick={() => setActiveCheckIn(null)} className="rounded-lg px-4 py-2 text-xs font-bold text-slate-500">Batal</button><button disabled={checkingInId === activeCheckIn.id_karyawan} className="rounded-lg bg-emerald-600 px-4 py-2 text-xs font-bold text-white disabled:opacity-50">Simpan Masuk</button></div></div></form></div>}
    {active && <div className="fixed inset-0 z-50 flex items-center justify-center p-4"><button onClick={() => setActive(null)} className="absolute inset-0 bg-slate-900/40" aria-label="Tutup" /><form onSubmit={checkOut} className="relative w-full max-w-md rounded-2xl bg-white shadow-2xl"><div className="flex items-center justify-between border-b border-slate-100 px-6 py-4"><div><h2 className="text-sm font-bold text-slate-800">Catat Jam Pulang</h2><p className="text-xs text-slate-400">{active.nama} · masuk {active.absensi?.jam_masuk}</p></div><button type="button" onClick={() => setActive(null)} className="text-slate-400"><X size={18} /></button></div><div className="space-y-4 p-6"><div><p className="text-[10px] font-bold uppercase tracking-wider text-slate-400">Pilih total jam kerja hari ini</p><div className="mt-3 grid grid-cols-4 gap-2">{DURASI.map((jam) => <button type="button" onClick={() => setData("durasi_jam", jam)} key={jam} className={`rounded-lg border px-2 py-3 text-xs font-black cursor-pointer ${data.durasi_jam === jam ? jam >= 14 ? "border-violet-600 bg-violet-600 text-white" : jam < 8 ? "border-amber-600 bg-amber-600 text-white" : "border-blue-600 bg-blue-600 text-white" : jam >= 14 ? "border-violet-200 bg-violet-50 text-violet-700" : jam < 8 ? "border-amber-200 bg-amber-50 text-amber-700" : "border-slate-200 text-slate-600"}`}>{jam} jam</button>)}</div></div><div className={`rounded-lg p-3 text-xs ${data.durasi_jam >= 14 ? "bg-violet-50 text-violet-700" : data.durasi_jam < 8 ? "bg-amber-50 text-amber-700" : "bg-blue-50 text-blue-700"}`}><Clock size={13} className="mr-1 inline" />{data.durasi_jam >= 14 ? `Durasi ${data.durasi_jam} jam akan dicatat sebagai LEMBUR.` : data.durasi_jam < 8 ? `Durasi ${data.durasi_jam} jam akan dicatat sebagai HADIR SETENGAH HARI / PULANG AWAL.` : `Durasi ${data.durasi_jam} jam akan dicatat sebagai kerja reguler.`}</div><label className="block text-[10px] font-bold uppercase tracking-wider text-slate-400">Keterangan (opsional)<textarea value={data.keterangan} onChange={(event) => setData("keterangan", event.target.value)} rows={2} placeholder="Contoh: izin pulang awal / lembur pesanan" className="mt-1.5 w-full rounded-lg border border-slate-200 px-3 py-2 text-xs outline-none focus:border-blue-500" /></label>{errors.durasi_jam && <p className="text-xs text-red-600">{errors.durasi_jam}</p>}<div className="flex justify-end gap-2 border-t border-slate-100 pt-4"><button type="button" onClick={() => setActive(null)} className="rounded-lg px-4 py-2 text-xs font-bold text-slate-500">Batal</button><button disabled={processing} className="rounded-lg bg-blue-600 px-4 py-2 text-xs font-bold text-white disabled:opacity-50">Simpan Pulang</button></div></div></form></div>}
  </SumberPvcLayout>;
}
function Card({ label, value, color }: { label: string; value: string | number; color: string }) { return <div className="rounded-xl border border-slate-200 bg-white p-4 shadow-sm"><p className="text-[10px] font-bold uppercase tracking-wider text-slate-400">{label}</p><p className={`mt-1 text-xl font-black ${color}`}>{value}</p></div>; }
function Badge({ text, type }: { text: string; type: "pending" | "working" | "overtime" | "done" }) { const style = { pending: "border-slate-200 bg-slate-100 text-slate-500", working: "border-blue-200 bg-blue-50 text-blue-700", overtime: "border-violet-200 bg-violet-50 text-violet-700", done: "border-emerald-200 bg-emerald-50 text-emerald-700" }[type]; return <span className={`rounded border px-2 py-1 text-[9px] font-bold ${style}`}>{text}</span>; }
