import React, { useState } from "react";
import { Head, router } from "@inertiajs/react";
import { Plus, Search, Trash2 } from "lucide-react";
import SumberPvcLayout from "@/Layouts/SumberPvcLayout";
import ProductVariantFields, { StockProduct } from "@/Components/ProductVariantFields";

interface Line { id_barang:number|string; tanggal:string; jumlah:number|string; keterangan:string }
interface Props { transactions:any; barang_list:StockProduct[]; filters:{search?:string} }

export default function Incoming({ transactions, barang_list, filters }: Props) {
  const now = new Date();
  const today = new Date(now.getTime() - now.getTimezoneOffset() * 60000).toISOString().slice(0, 10);
  const empty = ():Line => ({ id_barang: barang_list[0]?.id || "", tanggal: today, jumlah: 1, keterangan: "" });
  const [search,setSearch] = useState(filters.search || "");
  const [lines,setLines] = useState<Line[]>([empty()]);
  const [processing,setProcessing] = useState(false);
  const [errors, setErrors] = useState<Record<string, string>>({});

  const update = (i:number,key:keyof Line,value:any) => setLines(v => v.map((x,n) => n===i ? {...x,[key]:value}:x));
  const save = (e:React.FormEvent) => {
    e.preventDefault();
    setProcessing(true);
    setErrors({});
    router.post(route("stock.incoming.store"), {items:lines} as any, {
      onFinish:()=>setProcessing(false),
      onSuccess:()=>{ setLines([empty()]); setErrors({}); },
      onError: (err) => setErrors(err),
    });
  };

  return <SumberPvcLayout>
    <Head title="Barang Masuk" />
    <div><h1 className="text-xl font-bold text-slate-800">Barang Masuk Hasil Cetak</h1><p className="text-xs text-slate-400">Catat produk jadi yang masuk ke gudang. Semua jumlah menggunakan kodi.</p></div>

    {Object.keys(errors).length > 0 && (
      <div className="rounded-xl border border-rose-200 bg-rose-50 p-4 text-xs text-rose-700">
        <div className="font-bold mb-1">Gagal menyimpan barang masuk:</div>
        <ul className="list-disc pl-4 space-y-0.5">
          {Object.entries(errors).map(([k, msg]) => (
            <li key={k}>{msg}</li>
          ))}
        </ul>
      </div>
    )}

    <form onSubmit={save} className="bg-white rounded-2xl border border-slate-200 p-5 shadow-sm space-y-4">
      <div className="flex justify-between items-center gap-3"><h2 className="text-sm font-bold text-slate-800">Daftar Hasil Cetak Hari Ini</h2><button type="button" onClick={()=>setLines(v=>[...v,empty()])} className="flex items-center gap-2 px-3 py-2 bg-slate-800 text-white rounded-lg text-xs font-bold"><Plus size={14}/>Tambah Produk</button></div>
      <div className="space-y-3">{lines.map((line,i)=><div key={i} className="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-[150px_180px_140px_145px_120px_1fr_36px] gap-3 p-3 bg-slate-50 rounded-xl border border-slate-100 items-end">
        <ProductVariantFields products={barang_list} value={line.id_barang} onChange={id=>update(i,"id_barang",id)}/>
        <label className="space-y-1 text-[10px] font-bold uppercase text-slate-500">Tanggal<input required type="date" max={today} value={line.tanggal} onChange={e=>update(i,"tanggal",e.target.value)} className="block w-full rounded-lg border-slate-200 text-xs font-normal normal-case"/></label>
        <label className="space-y-1 text-[10px] font-bold uppercase text-slate-500">Jumlah<div className="relative"><input required min="1" type="number" value={line.jumlah} onChange={e=>update(i,"jumlah",e.target.value === "" ? "" : Number(e.target.value))} className="w-full rounded-lg border-slate-200 pr-12 text-xs font-normal normal-case"/><span className="absolute right-2 top-2.5 text-[10px] text-slate-400">kodi</span></div></label>
        <label className="space-y-1 text-[10px] font-bold uppercase text-slate-500">Keterangan<input value={line.keterangan} onChange={e=>update(i,"keterangan",e.target.value)} placeholder="Opsional" className="block w-full rounded-lg border-slate-200 text-xs font-normal normal-case"/></label>
        <button type="button" onClick={()=>setLines(v=>v.length===1?v:v.filter((_,n)=>n!==i))} className="text-red-500 flex justify-center items-center"><Trash2 size={15}/></button>
      </div>)}</div>
      <div className="flex justify-end"><button disabled={processing||!barang_list.length} className="px-5 py-2.5 bg-blue-600 text-white rounded-lg text-xs font-bold disabled:opacity-50">Simpan Semua ({lines.length} produk)</button></div>
    </form>
    <div className="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
      <form onSubmit={e=>{e.preventDefault();router.get(route("stock.incoming"),{search},{preserveState:true})}} className="p-4 border-b relative max-w-sm"><Search size={14} className="absolute left-7 top-7 text-slate-400"/><input value={search} onChange={e=>setSearch(e.target.value)} placeholder="Cari produk..." className="w-full pl-9 rounded-lg border-slate-200 text-xs"/></form>
      <div className="overflow-x-auto"><table className="w-full text-xs"><thead className="bg-slate-50 text-slate-400 uppercase text-[10px]"><tr><th className="p-4 text-left">Transaksi</th><th className="p-4 text-left">Produk</th><th className="p-4 text-left">Tanggal</th><th className="p-4 text-left">Jumlah</th><th className="p-4 text-left">Keterangan</th></tr></thead><tbody className="divide-y">{transactions.data.map((t:any)=><tr key={t.id}><td className="p-4 font-mono">BM-{String(t.id).padStart(4,"0")}</td><td className="p-4 font-semibold">{t.barang_pvc.kategori} — {t.barang_pvc.jenis} — {t.barang_pvc.warna}</td><td className="p-4">{String(t.tanggal).slice(0,10)}</td><td className="p-4 font-bold text-green-600">+{t.jumlah} kodi</td><td className="p-4 text-slate-500">{t.keterangan||"-"}</td></tr>)}</tbody></table></div>
    </div>
  </SumberPvcLayout>;
}
