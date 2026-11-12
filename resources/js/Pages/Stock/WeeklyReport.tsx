import React from "react";
import { Head, router } from "@inertiajs/react";
import { ArrowDownToLine, ArrowUpFromLine, CalendarDays, Download, FileSpreadsheet, Printer, Scale } from "lucide-react";
import SumberPvcLayout from "@/Layouts/SumberPvcLayout";

interface Row { id:number; kategori:string; jenis:string; warna:string; masuk:number; keluar:number; selisih:number }
interface Props {
  period:{type:"weekly"|"monthly"; date:string; key:string; label:string; start:string; end:string};
  rows:Row[];
  totals:{masuk:number; keluar:number; selisih:number};
}

export default function WeeklyReport({ period, rows, totals }: Props) {
  const changePeriod = (type:string, date:string) => router.get(route("stock.weekly-report"), { type, date }, { preserveState:true, replace:true });
  const exportParams = { type:period.type, date:period.date };
  const cards = [
    {label:"Barang Masuk", value:totals.masuk, icon:ArrowDownToLine, color:"text-green-600 bg-green-50"},
    {label:"Barang Keluar", value:totals.keluar, icon:ArrowUpFromLine, color:"text-red-600 bg-red-50"},
    {label:`Selisih ${period.type === "monthly" ? "Bulanan" : "Mingguan"}`, value:totals.selisih, icon:Scale, color:"text-blue-600 bg-blue-50"},
  ];

  return <SumberPvcLayout>
    <Head title="Laporan Mingguan"/>
    <style>{`@media print {.print-hidden, aside, header {display:none!important} main{overflow:visible!important;padding:0!important}.print-card{box-shadow:none!important}.print-area{max-width:none!important}}`}</style>
    <div className="print-area flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
      <div><h1 className="text-xl font-bold text-slate-800">Laporan Gudang</h1><p className="text-xs text-slate-400">Rekap mingguan atau bulanan barang masuk dan keluar dalam satuan kodi.</p></div>
      <div className="print-hidden flex flex-wrap items-end gap-2">
        <div className="flex rounded-lg bg-slate-200 p-1 text-xs font-bold"><button onClick={()=>{const d=new Date();const localDate=new Date(d.getTime()-d.getTimezoneOffset()*60000).toISOString().slice(0,10);changePeriod("weekly", localDate);}} className={`rounded-md px-3 py-2 ${period.type === "weekly" ? "bg-white text-blue-600 shadow-sm" : "text-slate-500"}`}>Mingguan</button><button onClick={()=>{const d=new Date();const localMonth=new Date(d.getTime()-d.getTimezoneOffset()*60000).toISOString().slice(0,7);changePeriod("monthly", localMonth);}} className={`rounded-md px-3 py-2 ${period.type === "monthly" ? "bg-white text-blue-600 shadow-sm" : "text-slate-500"}`}>Bulanan</button></div>
        <label className="text-[10px] font-bold uppercase text-slate-500">Pilih Periode<div className="relative mt-1"><CalendarDays size={14} className="absolute left-3 top-2.5 text-slate-400"/><input type={period.type === "monthly" ? "month" : "date"} value={period.date} onChange={e=>changePeriod(period.type,e.target.value)} className="rounded-lg border-slate-200 pl-9 text-xs font-normal normal-case"/></div></label>
      </div>
    </div>

    <div className="print-hidden flex flex-wrap justify-end gap-2">
      <button type="button" onClick={()=>window.print()} className="flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs font-bold text-slate-700 hover:bg-slate-50"><Printer size={14}/>Cetak</button>
      <a href={route("stock.report.excel", exportParams)} className="flex items-center gap-2 rounded-lg bg-green-600 px-3 py-2 text-xs font-bold text-white hover:bg-green-700"><FileSpreadsheet size={14}/>Unduh Excel</a>
      <a href={route("stock.report.pdf", exportParams)} className="flex items-center gap-2 rounded-lg bg-red-600 px-3 py-2 text-xs font-bold text-white hover:bg-red-700"><Download size={14}/>Unduh PDF</a>
    </div>

    <div className="rounded-xl border border-blue-100 bg-blue-50/60 px-4 py-3 text-sm font-semibold text-blue-900">Periode: {period.start} – {period.end}</div>
    <div className="grid grid-cols-1 gap-4 sm:grid-cols-3">{cards.map(card=>{const Icon=card.icon;return <div key={card.label} className="print-card rounded-2xl border border-slate-200 bg-white p-4 shadow-sm"><div className={`mb-3 flex h-9 w-9 items-center justify-center rounded-lg ${card.color}`}><Icon size={18}/></div><p className="text-xs text-slate-500">{card.label}</p><p className="mt-1 text-2xl font-bold text-slate-800">{card.value} <span className="text-xs font-medium text-slate-400">kodi</span></p></div>})}</div>

    <div className="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
      <div className="overflow-x-auto"><table className="w-full text-xs"><thead className="bg-slate-50 text-[10px] uppercase text-slate-400"><tr><th className="p-4 text-left">Kategori</th><th className="p-4 text-left">Jenis</th><th className="p-4 text-left">Warna</th><th className="p-4 text-right">Masuk</th><th className="p-4 text-right">Keluar</th><th className="p-4 text-right">Selisih</th></tr></thead>
        <tbody className="divide-y">{rows.length ? rows.map(row=><tr key={row.id}><td className="p-4">{row.kategori}</td><td className="p-4 font-semibold">{row.jenis}</td><td className="p-4">{row.warna}</td><td className="p-4 text-right font-bold text-green-600">+{row.masuk}</td><td className="p-4 text-right font-bold text-red-600">-{row.keluar}</td><td className={`p-4 text-right font-bold ${row.selisih >= 0 ? "text-blue-600" : "text-orange-600"}`}>{row.selisih > 0 ? "+" : ""}{row.selisih} kodi</td></tr>) : <tr><td colSpan={6} className="p-10 text-center text-slate-400">Belum ada transaksi pada minggu ini.</td></tr>}</tbody>
      </table></div>
    </div>
  </SumberPvcLayout>;
}
