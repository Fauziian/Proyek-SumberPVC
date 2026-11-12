import React, { useState } from "react";
import { Head, router } from "@inertiajs/react";
import { Package, Search } from "lucide-react";
import SumberPvcLayout from "@/Layouts/SumberPvcLayout";

type Product = {
  id: number; kode_barang: string; nama_barang: string; kategori: string;
  jenis: string | null; warna: string | null; satuan: string;
  stok_saat_ini: number; stok_minimum: number; status_stok: "aman" | "menipis" | "kosong";
};

type Props = {
  items: { data: Product[]; links: Array<{ url: string | null; label: string; active: boolean }>; current_page: number; last_page: number; total: number };
  filters: { search?: string; status?: string; kategori?: string; jenis?: string };
  jenis_tali_jepit: string[];
};

const statusStyle = {
  aman: "bg-green-50 text-green-700 border-green-200",
  menipis: "bg-amber-50 text-amber-700 border-amber-200",
  kosong: "bg-red-50 text-red-700 border-red-200",
};

export default function Available({ items, filters, jenis_tali_jepit }: Props) {
  const [search, setSearch] = useState(filters.search ?? "");
  const [status, setStatus] = useState(filters.status ?? "");
  const [kategori, setKategori] = useState(filters.kategori ?? "");
  const [jenis, setJenis] = useState(filters.jenis ?? "");
  const applyFilter = (nextStatus = status, nextKategori = kategori, nextJenis = jenis) => router.get(route("stock.available"), { search, status: nextStatus, kategori: nextKategori, jenis: nextJenis }, { preserveState: true, preserveScroll: true });
  const detail = (item: Product) => [item.jenis, item.warna].filter(Boolean).join(" — ") || item.nama_barang;

  return <SumberPvcLayout>
    <Head title="Stok Barang Tersedia" />
    <div className="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
      <div>
        <div className="flex items-center gap-2"><Package size={22} className="text-blue-600" /><h1 className="text-xl font-bold text-slate-800">Stok Barang Tersedia</h1></div>
        <p className="mt-1 text-xs text-slate-400">Lihat seluruh stok barang jadi di gudang dalam satuan kodi.</p>
      </div>
      <div className="rounded-xl bg-blue-600 px-4 py-3 text-white shadow-sm"><p className="text-[10px] font-bold uppercase tracking-wider text-blue-100">Total varian produk</p><p className="text-xl font-black">{items.total}</p></div>
    </div>

    <div className="flex flex-col gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
      <div className="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
        <form onSubmit={event => { event.preventDefault(); applyFilter(); }} className="relative w-full md:max-w-sm">
          <Search size={14} className="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" />
          <input value={search} onChange={event => setSearch(event.target.value)} placeholder="Cari produk, warna, atau kode..." className="w-full rounded-lg border-slate-200 py-2 pl-9 pr-3 text-xs" />
        </form>
        <div className="flex flex-col gap-2 sm:flex-row">
          <select value={kategori} onChange={event => { const nextKategori = event.target.value; const nextJenis = nextKategori === "Tali Jepit" ? jenis : ""; setKategori(nextKategori); setJenis(nextJenis); applyFilter(status, nextKategori, nextJenis); }} className="rounded-lg border-slate-200 py-2 text-xs sm:w-48">
            <option value="">Semua kategori</option><option value="Tali Jepit">Tali Jepit</option><option value="Boloni Gunung">Boloni Gunung</option><option value="Outsole">Outsole</option>
          </select>
          <select value={status} onChange={event => { setStatus(event.target.value); applyFilter(event.target.value); }} className="rounded-lg border-slate-200 py-2 text-xs sm:w-48">
          <option value="">Semua status stok</option><option value="aman">Stok aman</option><option value="menipis">Stok menipis</option><option value="kosong">Stok kosong</option>
          </select>
        </div>
      </div>
      {kategori === "Tali Jepit" && <label className="flex flex-col gap-1 border-t border-slate-100 pt-3 text-[10px] font-bold uppercase tracking-wide text-slate-400 sm:w-56">
        Jenis Tali Jepit
        <select value={jenis} onChange={event => { setJenis(event.target.value); applyFilter(status, kategori, event.target.value); }} className="rounded-lg border-slate-200 py-2 text-xs font-semibold normal-case text-slate-700">
          <option value="">Semua jenis jepit</option>{jenis_tali_jepit.map(item => <option key={item} value={item}>{item}</option>)}
        </select>
      </label>}
    </div>

    <div className="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
      <div className="overflow-x-auto"><table className="w-full text-left text-xs"><thead className="bg-slate-50 text-[10px] font-bold uppercase tracking-wider text-slate-400"><tr><th className="px-5 py-4">Kode</th><th className="px-5 py-4">Kategori</th><th className="px-5 py-4">Produk</th><th className="px-5 py-4">Stok minimum</th><th className="px-5 py-4">Stok tersedia</th><th className="px-5 py-4">Status</th></tr></thead><tbody className="divide-y divide-slate-100">
        {items.data.length === 0 ? <tr><td colSpan={6} className="px-5 py-10 text-center text-slate-400">Produk tidak ditemukan.</td></tr> : items.data.map(item => <tr key={item.id} className="hover:bg-slate-50/60"><td className="px-5 py-4 font-mono font-bold text-slate-600">{item.kode_barang}</td><td className="px-5 py-4 text-slate-500">{item.kategori}</td><td className="px-5 py-4"><p className="font-bold text-slate-800">{detail(item)}</p><p className="mt-0.5 text-[10px] text-slate-400">{item.nama_barang}</p></td><td className="px-5 py-4 text-slate-500">{item.stok_minimum} {item.satuan}</td><td className="px-5 py-4 text-base font-black text-slate-800">{item.stok_saat_ini} <span className="text-[10px] font-bold text-slate-400">{item.satuan}</span></td><td className="px-5 py-4"><span className={`inline-flex rounded-full border px-2 py-1 text-[10px] font-bold uppercase ${statusStyle[item.status_stok]}`}>{item.status_stok}</span></td></tr>)}
      </tbody></table></div>
      {items.last_page > 1 && <div className="flex items-center justify-between border-t border-slate-100 bg-slate-50 px-5 py-3"><span className="text-[11px] text-slate-400">Halaman {items.current_page} dari {items.last_page}</span><div className="flex gap-1">{items.links.map((link, index) => <button key={index} disabled={!link.url || link.active} onClick={() => link.url && router.get(link.url)} className={`rounded px-2.5 py-1 text-xs font-bold ${link.active ? "bg-blue-600 text-white" : "bg-white text-slate-500 ring-1 ring-slate-200 disabled:opacity-40"}`} dangerouslySetInnerHTML={{ __html: link.label }} />)}</div></div>}
    </div>
  </SumberPvcLayout>;
}
