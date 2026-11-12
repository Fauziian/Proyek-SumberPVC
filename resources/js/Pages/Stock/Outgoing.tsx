import React, { useState } from "react";
import { Head, router } from "@inertiajs/react";
import { Plus, Search, Trash2 } from "lucide-react";
import SumberPvcLayout from "@/Layouts/SumberPvcLayout";
import ProductVariantFields, { StockProduct } from "@/Components/ProductVariantFields";

interface Line { id_barang:number|string; tanggal:string; jumlah:number|string; keterangan:string }
interface Props { transactions:any; barang_list:StockProduct[]; filters:{search?:string} }

export default function Outgoing({transactions,barang_list,filters}:Props) {
  const now=new Date(); const today=new Date(now.getTime()-now.getTimezoneOffset()*60000).toISOString().slice(0,10); const empty=():Line=>({id_barang:barang_list[0]?.id||"",tanggal:today,jumlah:1,keterangan:""});
  const [pelanggan,setPelanggan]=useState("");
  const [search,setSearch]=useState(filters.search||"");
  const [lines,setLines]=useState<Line[]>([empty()]);
  const [processing,setProcessing]=useState(false);
  const [errors, setErrors] = useState<Record<string, string>>({});

  const update=(i:number,key:keyof Line,value:any)=>setLines(v=>v.map((x,n)=>n===i?{...x,[key]:value}:x));
  const save=(e:React.FormEvent)=>{
    e.preventDefault();
    setProcessing(true);
    setErrors({});
    router.post(route("stock.outgoing.store"),{pelanggan,items:lines} as any,{
      onFinish:()=>setProcessing(false),
      onSuccess:()=>{setPelanggan("");setLines([empty()]);setErrors({});},
      onError:(err)=>setErrors(err),
    });
  };

  return <SumberPvcLayout><Head title="Barang Keluar"/>
    <div><h1 className="text-xl font-bold text-slate-800">Barang Keluar ke Pelanggan</h1><p className="text-xs text-slate-400">Catat produk jadi yang keluar dari gudang dalam satuan kodi.</p></div>

    {Object.keys(errors).length > 0 && (
      <div className="rounded-xl border border-rose-200 bg-rose-50 p-4 text-xs text-rose-700">
        <div className="font-bold mb-1">Gagal menyimpan pengiriman barang:</div>
        <ul className="list-disc pl-4 space-y-0.5">
          {Object.entries(errors).map(([k, msg]) => (
            <li key={k}>{msg}</li>
          ))}
        </ul>
      </div>
    )}

    <form onSubmit={save} className="bg-white rounded-2xl border border-slate-200 p-5 shadow-sm space-y-4">
      <div className="flex flex-col sm:flex-row gap-3 sm:items-end justify-between"><label className="text-xs font-bold text-slate-600 w-full sm:max-w-md">Nama Pelanggan<input required value={pelanggan} onChange={e=>setPelanggan(e.target.value)} placeholder="Masukkan nama pelanggan" className="block mt-1 w-full rounded-lg border-slate-200 text-xs font-normal"/></label><button type="button" onClick={()=>setLines(v=>[...v,empty()])} className="flex items-center justify-center gap-2 px-3 py-2 bg-slate-800 text-white rounded-lg text-xs font-bold"><Plus size={14}/>Tambah Produk</button></div>
      <div className="space-y-3">{lines.map((line,i)=>{const p=barang_list.find(x=>x.id===Number(line.id_barang));return <div key={i} className="space-y-4 rounded-xl border border-slate-200 bg-slate-50/80 p-4">
        <div className="flex items-center justify-between border-b border-slate-200 pb-2"><p className="text-xs font-bold text-slate-700">Produk {i+1}</p><button type="button" title="Hapus produk" onClick={()=>setLines(v=>v.length===1?v:v.filter((_,n)=>n!==i))} className="flex items-center gap-1 text-[10px] font-semibold text-red-500 hover:text-red-700"><Trash2 size={14}/>Hapus</button></div>
        <div className="grid grid-cols-1 gap-3 md:grid-cols-3">
          <ProductVariantFields products={barang_list} value={line.id_barang} onChange={id=>update(i,"id_barang",id)} showStock/>
        </div>
        <div className="grid grid-cols-1 gap-3 md:grid-cols-[180px_180px_1fr] items-start">
          <label className="space-y-1 text-[10px] font-bold uppercase text-slate-500">Tanggal<input required type="date" max={today} value={line.tanggal} onChange={e=>update(i,"tanggal",e.target.value)} className="block w-full rounded-lg border-slate-200 text-xs font-normal normal-case"/></label>
          <label className="space-y-1 text-[10px] font-bold uppercase text-slate-500">Jumlah Keluar<div className="relative"><input required min="1" max={p?.stok_saat_ini} type="number" value={line.jumlah} onChange={e=>update(i,"jumlah",e.target.value === "" ? "" : Number(e.target.value))} className="block w-full rounded-lg border-slate-200 pr-12 text-xs font-normal normal-case"/><span className="absolute right-3 top-2.5 text-[10px] font-normal normal-case text-slate-400">kodi</span></div><span className={`${(p?.stok_saat_ini ?? 0) > 0 ? "text-green-600" : "text-red-500"} text-[9px] font-semibold normal-case`}>Tersedia {p?.stok_saat_ini||0} kodi</span></label>
          <label className="space-y-1 text-[10px] font-bold uppercase text-slate-500">Keterangan<input value={line.keterangan} onChange={e=>update(i,"keterangan",e.target.value)} placeholder="Keterangan tambahan (opsional)" className="block w-full rounded-lg border-slate-200 text-xs font-normal normal-case"/></label>
        </div>
      </div>})}</div>
      <div className="flex justify-end"><button disabled={processing||!barang_list.length} className="px-5 py-2.5 bg-blue-600 text-white rounded-lg text-xs font-bold disabled:opacity-50">Simpan Pengiriman ({lines.length} produk)</button></div>
    </form>
    <div className="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden"><form onSubmit={e=>{e.preventDefault();router.get(route("stock.outgoing"),{search},{preserveState:true})}} className="p-4 border-b relative max-w-sm"><Search size={14} className="absolute left-7 top-7 text-slate-400"/><input value={search} onChange={e=>setSearch(e.target.value)} placeholder="Cari produk atau pelanggan..." className="w-full pl-9 rounded-lg border-slate-200 text-xs"/></form><div className="overflow-x-auto"><table className="w-full text-xs"><thead className="bg-slate-50 text-slate-400 uppercase text-[10px]"><tr><th className="p-4 text-left">Transaksi</th><th className="p-4 text-left">Produk</th><th className="p-4 text-left">Tanggal</th><th className="p-4 text-left">Jumlah</th><th className="p-4 text-left">Pelanggan</th></tr></thead><tbody className="divide-y">{transactions.data.map((t:any)=><tr key={t.id}><td className="p-4 font-mono">BK-{String(t.id).padStart(4,"0")}</td><td className="p-4 font-semibold">{t.barang_pvc.kategori} — {t.barang_pvc.jenis} — {t.barang_pvc.warna}</td><td className="p-4">{String(t.tanggal).slice(0,10)}</td><td className="p-4 font-bold text-red-600">-{t.jumlah} kodi</td><td className="p-4 font-semibold">{t.tujuan_penggunaan}</td></tr>)}</tbody></table></div></div>
  </SumberPvcLayout>;
}
