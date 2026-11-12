import React, { useState } from "react";
import SumberPvcLayout from "@/Layouts/SumberPvcLayout";
import { Head, Link } from "@inertiajs/react";
import { 
  Users, Package, Clock, CreditCard, AlertTriangle, Shield,
  UserCheck, TrendingUp, RefreshCw, ArrowRight 
} from "lucide-react";

type Role = "admin" | "hr" | "warehouse";

interface DashboardProps {
  role: Role;
  stats: Record<string, any>;
  recent_absensi?: any[];
  notifikasi_kritis?: any[];
  recent_masuk?: any[];
  recent_keluar?: any[];
  stok_tersedia?: any[];
  aktifitas_terbaru: any[];
}

export default function Dashboard({
  role,
  stats,
  recent_absensi = [],
  notifikasi_kritis = [],
  recent_masuk = [],
  recent_keluar = [],
  stok_tersedia = [],
  aktifitas_terbaru = []
}: DashboardProps) {
  
  // Format mata uang Rupiah
  const idr = (val: number) => {
    return new Intl.NumberFormat("id-ID", {
      style: "currency",
      currency: "IDR",
      minimumFractionDigits: 0
    }).format(val);
  };

  const initials = (name: string) => {
    return name.split(" ").slice(0, 2).map(w => w[0]).join("").toUpperCase();
  };
  const [stockCategory, setStockCategory] = useState("Semua");
  const dashboardStock = stok_tersedia
    .filter(barang => stockCategory === "Semua" || barang.kategori === stockCategory)
    .slice(0, 5);

  return (
    <SumberPvcLayout>
      <Head title="Dashboard Utama" />

      {/* Welcome banner */}
      <div className="bg-gradient-to-r from-blue-700 via-indigo-700 to-purple-800 rounded-2xl p-6 text-white shadow-xl shadow-blue-500/10 relative overflow-hidden">
        <div className="relative z-10 space-y-2">
          <span className="bg-white/10 px-3 py-1 rounded-full text-xs font-semibold tracking-wider backdrop-blur-md">
            Sistem Informasi Operasional Sumber PVC Outsole Tali Jepit
          </span>
          <h1 className="text-2xl sm:text-3xl font-extrabold tracking-tight">
            Selamat Datang di Portal Operasional
          </h1>
          <p className="text-blue-100/90 text-xs sm:text-sm max-w-2xl leading-relaxed">
            {role === "warehouse" ? "Pantau stok barang jadi serta catat hasil cetak masuk dan pengiriman barang kepada pelanggan." : "Pantau aktivitas operasional pabrik dalam satu sistem."}
          </p>
        </div>
        <div className="absolute right-0 bottom-0 top-0 opacity-10 pointer-events-none hidden md:block">
          <RefreshCw size={240} className="animate-spin-slow" />
        </div>
      </div>

      {/* STATS CARDS GRID */}
      <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
        {role === "admin" && (
          <>
            <CardStat label="Karyawan Aktif" value={stats.total_karyawan} icon={Users} color="bg-blue-500" />
            <CardStat label="Absensi Hari Ini" value={stats.hadir_hari_ini} icon={UserCheck} color="bg-emerald-500" />
            <CardStat label="Total Stok Tersedia" value={`${stats.total_stok} kodi`} icon={Package} color="bg-indigo-500" />
            <CardStat label="Total Gaji Bulan Ini" value={idr(stats.total_gaji_bulan_ini)} icon={CreditCard} color="bg-purple-500" />
          </>
        )}

        {role === "hr" && (
          <>
            <CardStat label="Karyawan Aktif" value={stats.total_karyawan} icon={Users} color="bg-blue-500" />
            <CardStat label="Karyawan Hadir Hari Ini" value={stats.hadir_hari_ini} icon={Clock} color="bg-emerald-500" />
            <CardStat label="Hadir Setengah Hari" value={stats.setengah_hari_ini} icon={AlertTriangle} color="bg-amber-500" />
            <CardStat label="Gaji Bulan Lalu" value={idr(stats.total_payroll_bulan_lalu)} icon={CreditCard} color="bg-purple-500" />
          </>
        )}

        {role === "warehouse" && (
          <>
            <CardStat label="Varian Produk" value={stats.total_jenis_barang} icon={Package} color="bg-blue-500" />
            <CardStat label="Stok Level Aman" value={stats.stok_aman} icon={UserCheck} color="bg-emerald-500" />
            <CardStat label="Stok Level Menipis" value={stats.stok_menipis} icon={AlertTriangle} color="bg-amber-500" />
            <CardStat label="Stok Kritis (≤ Min)" value={stats.stok_kritis} icon={AlertTriangle} color="bg-rose-500" />
          </>
        )}

      </div>

      {/* DETAILED CONTENT BASED ON ROLE */}
      <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        {/* LEFT & CENTER PANELS (Charts / Tables) */}
        <div className="lg:col-span-2 space-y-6">
          
          {/* HR TABLES (Recent Attendance) */}
          {role === "hr" && (
            <div className="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-sm space-y-4">
              <div className="flex justify-between items-center">
                <h3 className="text-sm font-bold text-slate-800">Log Kehadiran Karyawan Terbaru</h3>
                <Link href={route("attendance.index")} className="text-xs font-bold text-blue-600 hover:underline flex items-center gap-1">
                  Lihat Semua <ArrowRight size={13} />
                </Link>
              </div>
              <div className="overflow-x-auto">
                <table className="w-full text-left border-collapse">
                  <thead>
                    <tr className="border-b border-slate-100 text-[10px] font-bold text-slate-400 uppercase tracking-wider">
                      <th className="pb-3">Karyawan</th>
                      <th className="pb-3">Tanggal</th>
                      <th className="pb-3">Jam Kerja</th>
                      <th className="pb-3">Status</th>
                    </tr>
                  </thead>
                  <tbody className="divide-y divide-slate-50 text-xs">
                    {recent_absensi.length === 0 ? (
                      <tr>
                        <td colSpan={4} className="py-4 text-center text-slate-400">Tidak ada absensi terbaru hari ini.</td>
                      </tr>
                    ) : (
                      recent_absensi.map((abs) => (
                        <tr key={abs.id} className="hover:bg-slate-50/50">
                          <td className="py-3 font-semibold text-slate-800">{abs.karyawan.nama}</td>
                          <td className="py-3 text-slate-500">{abs.tanggal}</td>
                          <td className="py-3 text-slate-600 font-medium">{abs.jam_masuk} - {abs.jam_keluar} ({abs.durasi_jam} jam)</td>
                          <td className="py-3">
                            <span className={`inline-flex px-2 py-0.5 rounded text-[10px] font-bold ${
                              abs.status_kehadiran === "Penuh" ? "bg-green-50 text-green-700 border border-green-200" :
                              abs.status_kehadiran === "Lembur" ? "bg-blue-50 text-blue-700 border border-blue-200" :
                              abs.status_kehadiran === "Jam Lebih" ? "bg-indigo-50 text-indigo-700 border border-indigo-200" :
                              "bg-amber-50 text-amber-700 border border-amber-200"
                            }`}>
                              {abs.status_kehadiran}
                            </span>
                          </td>
                        </tr>
                      ))
                    )}
                  </tbody>
                </table>
              </div>
            </div>
          )}

          {/* WAREHOUSE TABLES (Recent Mutual Transactions) */}
          {role === "warehouse" && (
            <div className="space-y-6">
              <div className="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-sm space-y-4">
                <div className="flex flex-wrap items-center justify-between gap-3">
                  <div>
                    <h3 className="text-sm font-bold text-slate-800">Stok Barang Tersedia</h3>
                    <p className="mt-0.5 text-[10px] text-slate-400">Menampilkan maksimal 5 produk dalam satuan kodi.</p>
                  </div>
                  <div className="flex items-center gap-3">
                    <select value={stockCategory} onChange={event => setStockCategory(event.target.value)} className="rounded-lg border-slate-200 py-1.5 text-xs font-semibold text-slate-600">
                      <option value="Semua">Semua Kategori</option>
                      <option value="Tali Jepit">Tali Jepit</option>
                      <option value="Boloni Gunung">Boloni Gunung</option>
                      <option value="Outsole">Outsole</option>
                    </select>
                    <Link href={route("stock.available")} className="text-xs font-bold text-blue-600 hover:underline flex items-center gap-1">
                      Lihat Semua <ArrowRight size={13} />
                    </Link>
                  </div>
                </div>
                <div className="overflow-x-auto">
                  <table className="w-full text-left border-collapse">
                    <thead>
                      <tr className="border-b border-slate-100 text-[10px] font-bold text-slate-400 uppercase tracking-wider">
                        <th className="pb-2">Kategori</th>
                        <th className="pb-2">Produk</th>
                        <th className="pb-2">Warna</th>
                        <th className="pb-2 text-right">Stok Tersedia</th>
                      </tr>
                    </thead>
                    <tbody className="divide-y divide-slate-50 text-xs">
                      {dashboardStock.length === 0 ? (
                        <tr><td colSpan={4} className="py-4 text-center text-slate-400">Belum ada data stok.</td></tr>
                      ) : dashboardStock.map((barang) => {
                        const isKritis = barang.stok_saat_ini <= barang.stok_minimum;
                        const isMenipis = barang.stok_saat_ini <= (barang.stok_minimum * 1.3);
                        const label = barang.jenis || barang.kategori;
                        const statusColor = isKritis ? "text-rose-600 font-bold" : isMenipis ? "text-amber-600 font-semibold" : "text-emerald-600 font-semibold";
                        return <tr key={barang.id} className="hover:bg-slate-50/50">
                          <td className="py-2.5 text-slate-500">{barang.kategori}</td>
                          <td className="py-2.5 font-semibold text-slate-800">{label}</td>
                          <td className="py-2.5 text-slate-600">{barang.warna || "—"}</td>
                          <td className={`py-2.5 text-right ${statusColor}`}>{barang.stok_saat_ini} {barang.satuan}</td>
                        </tr>;
                      })}
                    </tbody>
                  </table>
                </div>
              </div>

              <div className="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-sm space-y-4">
                <div className="flex justify-between items-center">
                  <h3 className="text-sm font-bold text-slate-800">Hasil Cetak Masuk Terakhir</h3>
                  <Link href={route("stock.incoming")} className="text-xs font-bold text-blue-600 hover:underline flex items-center gap-1">
                    Catat Transaksi <ArrowRight size={13} />
                  </Link>
                </div>
                <div className="overflow-x-auto">
                  <table className="w-full text-left border-collapse">
                    <thead>
                      <tr className="border-b border-slate-100 text-[10px] font-bold text-slate-400 uppercase tracking-wider">
                        <th className="pb-2">Produk</th>
                        <th className="pb-2">Tanggal</th>
                        <th className="pb-2">Kuantitas</th>
                        <th className="pb-2">Warna</th>
                      </tr>
                    </thead>
                    <tbody className="divide-y divide-slate-50 text-xs">
                      {recent_masuk.map((t) => (
                        <tr key={t.id}>
                          <td className="py-2.5 font-semibold text-slate-800">{t.barang_pvc.kategori} — {t.barang_pvc.jenis}</td>
                          <td className="py-2.5 text-slate-500">{t.tanggal}</td>
                          <td className="py-2.5 text-green-600 font-bold">+{t.jumlah} {t.barang_pvc.satuan}</td>
                          <td className="py-2.5 text-slate-600 font-semibold">{t.barang_pvc.warna}</td>
                        </tr>
                      ))}
                    </tbody>
                  </table>
                </div>
              </div>

              <div className="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-sm space-y-4">
                <div className="flex justify-between items-center">
                  <h3 className="text-sm font-bold text-slate-800">Pengiriman ke Pelanggan Terakhir</h3>
                  <Link href={route("stock.outgoing")} className="text-xs font-bold text-blue-600 hover:underline flex items-center gap-1">
                    Catat Transaksi <ArrowRight size={13} />
                  </Link>
                </div>
                <div className="overflow-x-auto">
                  <table className="w-full text-left border-collapse">
                    <thead>
                      <tr className="border-b border-slate-100 text-[10px] font-bold text-slate-400 uppercase tracking-wider">
                        <th className="pb-2">Produk</th>
                        <th className="pb-2">Tanggal</th>
                        <th className="pb-2">Kuantitas</th>
                        <th className="pb-2">Pelanggan</th>
                      </tr>
                    </thead>
                    <tbody className="divide-y divide-slate-50 text-xs">
                      {recent_keluar.map((t) => (
                        <tr key={t.id}>
                          <td className="py-2.5 font-semibold text-slate-800">{t.barang_pvc.kategori} — {t.barang_pvc.jenis} — {t.barang_pvc.warna}</td>
                          <td className="py-2.5 text-slate-500">{t.tanggal}</td>
                          <td className="py-2.5 text-red-500 font-bold">-{t.jumlah} {t.barang_pvc.satuan}</td>
                          <td className="py-2.5 text-slate-600 font-semibold">{t.tujuan_penggunaan}</td>
                        </tr>
                      ))}
                    </tbody>
                  </table>
                </div>
              </div>
            </div>
          )}

          {/* ADMIN LIST (Recent Login logs) */}
          {role === "admin" && (
            <div className="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-sm space-y-4">
              <div className="flex items-center justify-between gap-3">
                <div><h3 className="text-sm font-bold text-slate-800">Log Login Pengguna Sistem Terakhir</h3><p className="mt-0.5 text-[10px] text-slate-400">Kelola akun, role, status aktif, dan kata sandi dari menu pengguna.</p></div>
                <Link href={route("users.index")} className="shrink-0 text-xs font-bold text-blue-600 hover:underline flex items-center gap-1">Kelola Pengguna <ArrowRight size={13} /></Link>
              </div>
              <div className="overflow-x-auto">
                <table className="w-full text-left border-collapse">
                  <thead>
                    <tr className="border-b border-slate-100 text-[10px] font-bold text-slate-400 uppercase tracking-wider">
                      <th className="pb-2">Pengguna</th>
                      <th className="pb-2">Username</th>
                      <th className="pb-2">Hak Akses</th>
                      <th className="pb-2">Waktu Login</th>
                    </tr>
                  </thead>
                  <tbody className="divide-y divide-slate-50 text-xs text-slate-700">
                    {stats.recent_logins.map((lg: any, idx: number) => (
                      <tr key={idx}>
                        <td className="py-2.5 font-semibold text-slate-800">{lg.nama}</td>
                        <td className="py-2.5 text-slate-500">@{lg.username}</td>
                        <td className="py-2.5"><span className="px-2 py-0.5 rounded bg-slate-100 font-semibold">{lg.role}</span></td>
                        <td className="py-2.5 text-slate-600 font-medium">{new Date(lg.last_login_at).toLocaleString('id-ID')}</td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
            </div>
          )}

        </div>

        {/* RIGHT PANEL (Log Aktivitas / Alerts) */}
        <div className="space-y-6">
          
          {/* NOTIFIKASI STOCK KRITIS FOR WAREHOUSE */}
          {role === "warehouse" && notifikasi_kritis.length > 0 && (
            <div className="bg-rose-50 border border-rose-200 rounded-2xl p-5 shadow-sm space-y-3">
              <div className="flex items-center gap-2 text-rose-800 font-bold text-sm">
                <AlertTriangle size={16} />
                Peringatan Stok Kritis!
              </div>
              <ul className="space-y-2 text-xs text-rose-700">
                {notifikasi_kritis.map((n) => (
                  <li key={n.id} className="bg-white/60 p-2.5 rounded-lg border border-rose-200/40">
                    {n.pesan}
                  </li>
                ))}
              </ul>
              <Link href={route("stock.notifications")} className="text-xs font-bold text-rose-800 hover:underline block pt-1">
                Kelola Notifikasi &rarr;
              </Link>
            </div>
          )}

          {/* GLOBAL ACTIVITY FEED */}
          <div className="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-sm space-y-4">
            <h3 className="text-sm font-bold text-slate-800">Aktivitas Operasional Terbaru</h3>
            <div className="space-y-4 relative before:absolute before:left-3.5 before:top-2 before:bottom-2 before:w-0.5 before:bg-slate-100">
              {aktifitas_terbaru.map((act) => (
                <div key={act.id} className="flex gap-4 relative">
                  <div className={`w-7 h-7 rounded-full flex items-center justify-center flex-shrink-0 z-10 border-4 border-white ${
                    act.type === "stock" ? "bg-cyan-100 text-cyan-600" :
                    act.type === "employee" ? "bg-emerald-100 text-emerald-600" :
                    act.type === "alert" ? "bg-rose-100 text-rose-600" :
                    "bg-blue-100 text-blue-600"
                  }`}>
                    {act.type === "stock" && <Package size={10} />}
                    {act.type === "employee" && <Users size={10} />}
                    {act.type === "alert" && <AlertTriangle size={10} />}
                    {act.type === "payroll" && <CreditCard size={10} />}
                  </div>
                  <div>
                    <p className="text-xs text-slate-700 leading-relaxed font-semibold">{act.text}</p>
                    <span className="text-[10px] text-slate-400 font-medium block mt-0.5">{act.time}</span>
                  </div>
                </div>
              ))}
            </div>
          </div>

        </div>

      </div>

    </SumberPvcLayout>
  );
}

/* Helper Components untuk Stats */
function CardStat({ label, value, icon: Icon, color }: { label: string, value: any, icon: any, color: string }) {
  return (
    <div className="bg-white rounded-2xl border border-slate-200/80 p-5 flex items-center justify-between shadow-sm">
      <div className="space-y-1">
        <p className="text-[10px] font-bold text-slate-400 uppercase tracking-wider">{label}</p>
        <p className="text-2xl font-black text-slate-800 tabular-nums">{value}</p>
      </div>
      <div className={`w-10 h-10 rounded-xl ${color} text-white flex items-center justify-center shadow-lg shadow-slate-100`}>
        <Icon size={18} strokeWidth={2.5} />
      </div>
    </div>
  );
}
