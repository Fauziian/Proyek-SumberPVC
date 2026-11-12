import React, { useState } from "react";
import SumberPvcLayout from "@/Layouts/SumberPvcLayout";
import { Head, useForm, router } from "@inertiajs/react";
import { Plus, Edit2, Trash2, Search, X, AlertTriangle } from "lucide-react";

interface BarangPvc {
  id: number;
  nama_barang: string;
  kode_barang: string;
  satuan: string;
  stok_minimum: number;
  stok_saat_ini: number;
  status_stok: "aman" | "menipis" | "kritis";
  keterangan?: string;
  updated_at: string;
}

interface IndexProps {
  items: {
    data: BarangPvc[];
    links: any[];
    current_page: number;
    last_page: number;
    total: number;
  };
  filters: {
    search?: string;
    status?: string;
  };
}

export default function Index({ items, filters }: IndexProps) {
  const [search, setSearch] = useState(filters.search || "");
  const [status, setStatus] = useState(filters.status || "");
  const [modalOpen, setModalOpen] = useState(false);
  const [editingItem, setEditingItem] = useState<BarangPvc | null>(null);

  const { data, setData, post, put, delete: destroy, errors, reset, processing } = useForm({
    nama_barang: "",
    kode_barang: "",
    satuan: "kg",
    stok_minimum: 1000,
    stok_saat_ini: 0,
    keterangan: "",
  });

  const handleSearchSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    router.get(route("stock.index"), { search, status }, { preserveState: true });
  };

  const handleStatusFilter = (val: string) => {
    setStatus(val);
    router.get(route("stock.index"), { search, status: val }, { preserveState: true });
  };

  const openAddModal = () => {
    setEditingItem(null);
    reset();
    setModalOpen(true);
  };

  const openEditModal = (item: BarangPvc) => {
    setEditingItem(item);
    setData({
      nama_barang: item.nama_barang,
      kode_barang: item.kode_barang,
      satuan: item.satuan,
      stok_minimum: item.stok_minimum,
      stok_saat_ini: item.stok_saat_ini, // disabled on edit
      keterangan: item.keterangan || "",
    });
    setModalOpen(true);
  };

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    if (editingItem) {
      put(route("stock.update", editingItem.id), {
        onSuccess: () => {
          setModalOpen(false);
          reset();
        }
      });
    } else {
      post(route("stock.store"), {
        onSuccess: () => {
          setModalOpen(false);
          reset();
        }
      });
    }
  };

  const handleDelete = (id: number) => {
    if (confirm("Apakah Anda yakin ingin menghapus bahan baku PVC ini dari sistem?")) {
      destroy(route("stock.destroy", id));
    }
  };

  return (
    <SumberPvcLayout>
      <Head title="Stok Bahan Baku PVC" />

      {/* Header bar */}
      <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <h1 className="text-xl font-bold text-slate-800">Overview Stok Bahan Baku PVC</h1>
          <p className="text-xs text-slate-400">Monitoring real-time ketersediaan PVC Compound, pewarna powder, dan bahan liquid pembantu produksi outsole.</p>
        </div>
        <button
          onClick={openAddModal}
          className="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold rounded-lg shadow-lg shadow-blue-500/10 transition-all cursor-pointer self-start"
        >
          <Plus size={14} />
          Daftarkan Bahan Baku
        </button>
      </div>

      {/* SEARCH AND FILTER */}
      <div className="bg-white rounded-2xl border border-slate-200/80 p-4 shadow-sm flex flex-col md:flex-row gap-4 items-center justify-between">
        <form onSubmit={handleSearchSubmit} className="relative w-full md:w-80">
          <Search size={14} className="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" />
          <input
            type="text"
            placeholder="Cari nama atau kode bahan..."
            value={search}
            onChange={(e) => setSearch(e.target.value)}
            className="w-full pl-9 pr-4 py-2 border border-slate-200 rounded-lg text-xs focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-colors"
          />
        </form>

        <div className="flex gap-3 w-full md:w-auto">
          <select
            value={status}
            onChange={(e) => handleStatusFilter(e.target.value)}
            className="w-full md:w-48 px-3 py-2 border border-slate-200 rounded-lg text-xs bg-white focus:outline-none focus:ring-2 focus:ring-blue-500"
          >
            <option value="">Semua Level Stok</option>
            <option value="aman">Aman</option>
            <option value="menipis">Menipis (&le; 1.3x Min)</option>
            <option value="kritis">Kritis (&le; Min)</option>
          </select>
        </div>
      </div>

      {/* TABLE */}
      <div className="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
        <div className="overflow-x-auto">
          <table className="w-full text-left border-collapse">
            <thead>
              <tr className="border-b border-slate-100 bg-slate-50/50 text-[10px] font-bold text-slate-400 uppercase tracking-wider">
                <th className="px-6 py-4">Kode</th>
                <th className="px-6 py-4">Nama Bahan Baku</th>
                <th className="px-6 py-4">Stok Minimum</th>
                <th className="px-6 py-4">Stok Saat Ini</th>
                <th className="px-6 py-4">Status</th>
                <th className="px-6 py-4">Keterangan</th>
                <th className="px-6 py-4 text-right">Aksi</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-slate-50 text-xs">
              {items.data.length === 0 ? (
                <tr>
                  <td colSpan={7} className="px-6 py-8 text-center text-slate-400">
                    Tidak ada data bahan baku PVC terdaftar.
                  </td>
                </tr>
              ) : (
                items.data.map((item) => (
                  <tr key={item.id} className="hover:bg-slate-50/50">
                    <td className="px-6 py-4">
                      <span className="font-mono font-bold text-slate-900 bg-slate-100 px-2 py-0.5 rounded text-[10px]">
                        {item.kode_barang}
                      </span>
                    </td>
                    <td className="px-6 py-4">
                      <p className="font-semibold text-slate-800">{item.nama_barang}</p>
                    </td>
                    <td className="px-6 py-4 text-slate-500 font-medium">{item.stok_minimum} {item.satuan}</td>
                    <td className="px-6 py-4 font-bold text-slate-800">{item.stok_saat_ini} {item.satuan}</td>
                    <td className="px-6 py-4">
                      <span className={`inline-flex px-2 py-0.5 rounded text-[10px] font-bold border ${
                        item.status_stok === "aman" ? "bg-green-50 text-green-700 border-green-200" :
                        item.status_stok === "menipis" ? "bg-amber-50 text-amber-700 border-amber-200" :
                        "bg-red-50 text-red-700 border-red-200"
                      }`}>
                        {item.status_stok.toUpperCase()}
                      </span>
                    </td>
                    <td className="px-6 py-4 text-slate-400 max-w-xs truncate">{item.keterangan || "-"}</td>
                    <td className="px-6 py-4 text-right space-x-1.5 whitespace-nowrap">
                      <button
                        onClick={() => openEditModal(item)}
                        className="p-1.5 hover:bg-blue-50 rounded-lg text-slate-400 hover:text-blue-600 transition-colors"
                        title="Edit detail"
                      >
                        <Edit2 size={13} />
                      </button>
                      <button
                        onClick={() => handleDelete(item.id)}
                        className="p-1.5 hover:bg-red-50 rounded-lg text-slate-400 hover:text-red-500 transition-colors"
                        title="Hapus"
                      >
                        <Trash2 size={13} />
                      </button>
                    </td>
                  </tr>
                ))
              )}
            </tbody>
          </table>
        </div>

        {/* PAGINATION */}
        {items.total > 10 && (
          <div className="px-6 py-4 border-t border-slate-100 bg-slate-50 flex items-center justify-between">
            <span className="text-xs text-slate-400">
              Menampilkan {items.data.length} dari {items.total} jenis barang
            </span>
            <div className="flex gap-2">
              {items.links.map((link: any, idx: number) => (
                <button
                  key={idx}
                  onClick={() => link.url && router.get(link.url)}
                  disabled={!link.url || link.active}
                  className={`px-3 py-1.5 text-xs font-bold rounded ${
                    link.active 
                      ? "bg-blue-600 text-white" 
                      : "border border-slate-200 text-slate-500 bg-white hover:bg-slate-50 disabled:opacity-40"
                  }`}
                  dangerouslySetInnerHTML={{ __html: link.label }}
                />
              ))}
            </div>
          </div>
        )}
      </div>

      {/* FORM MODAL */}
      {modalOpen && (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4">
          <div className="absolute inset-0 bg-slate-900/40 backdrop-blur-sm" onClick={() => setModalOpen(false)} />
          <div className="relative bg-white rounded-2xl border border-slate-200 shadow-2xl w-full max-w-md flex flex-col overflow-hidden animate-in zoom-in-95 duration-150">
            
            <div className="flex items-center justify-between px-6 py-4 border-b border-slate-100">
              <h2 className="text-sm font-bold text-slate-800">
                {editingItem ? "Edit Data Bahan Baku" : "Daftarkan Bahan Baku Baru"}
              </h2>
              <button onClick={() => setModalOpen(false)} className="w-7 h-7 flex items-center justify-center rounded-lg text-slate-400 hover:bg-slate-100 transition-colors">
                <X size={15} />
              </button>
            </div>

            <form onSubmit={handleSubmit} className="p-6 space-y-4 text-slate-700">
              
              <div className="grid grid-cols-2 gap-4">
                {/* Kode */}
                <div className="flex flex-col gap-1">
                  <label className="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Kode Barang</label>
                  <input
                    type="text"
                    required
                    disabled={!!editingItem}
                    placeholder="Contoh: PVC-CW"
                    value={data.kode_barang}
                    onChange={(e) => setData("kode_barang", e.target.value.toUpperCase())}
                    className="w-full px-3 py-2 border border-slate-200 rounded-lg text-xs focus:ring-2 focus:ring-blue-500 outline-none uppercase disabled:bg-slate-50 disabled:text-slate-400 font-semibold"
                  />
                  {errors.kode_barang && <span className="text-[10px] text-red-500">{errors.kode_barang}</span>}
                </div>

                {/* Satuan */}
                <div className="flex flex-col gap-1">
                  <label className="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Satuan Ukuran</label>
                  <select
                    value={data.satuan}
                    onChange={(e) => setData("satuan", e.target.value)}
                    className="w-full px-3 py-2 border border-slate-200 rounded-lg text-xs focus:ring-2 focus:ring-blue-500 outline-none bg-white font-semibold"
                  >
                    <option value="kg">kilogram (kg)</option>
                    <option value="ltr">liter (ltr)</option>
                    <option value="pcs">pieces (pcs)</option>
                  </select>
                </div>
              </div>

              {/* Nama */}
              <div className="flex flex-col gap-1">
                <label className="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Nama Bahan Baku</label>
                <input
                  type="text"
                  required
                  placeholder="Masukkan nama bahan baku..."
                  value={data.nama_barang}
                  onChange={(e) => setData("nama_barang", e.target.value)}
                  className="w-full px-3 py-2 border border-slate-200 rounded-lg text-xs focus:ring-2 focus:ring-blue-500 outline-none font-semibold"
                />
                {errors.nama_barang && <span className="text-[10px] text-red-500">{errors.nama_barang}</span>}
              </div>

              <div className="grid grid-cols-2 gap-4">
                {/* Stok Minimum */}
                <div className="flex flex-col gap-1">
                  <label className="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Batas Stok Minimum</label>
                  <input
                    type="number"
                    required
                    value={data.stok_minimum}
                    onChange={(e) => setData("stok_minimum", Number(e.target.value))}
                    className="w-full px-3 py-2 border border-slate-200 rounded-lg text-xs focus:ring-2 focus:ring-blue-500 outline-none font-semibold"
                  />
                  {errors.stok_minimum && <span className="text-[10px] text-red-500">{errors.stok_minimum}</span>}
                </div>

                {/* Stok Awal - hanya pada saat daftar baru */}
                <div className="flex flex-col gap-1">
                  <label className="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Stok Fisik Awal</label>
                  <input
                    type="number"
                    required
                    disabled={!!editingItem}
                    value={data.stok_saat_ini}
                    onChange={(e) => setData("stok_saat_ini", Number(e.target.value))}
                    className="w-full px-3 py-2 border border-slate-200 rounded-lg text-xs focus:ring-2 focus:ring-blue-500 outline-none disabled:bg-slate-50 disabled:text-slate-400 font-semibold"
                  />
                </div>
              </div>

              {/* Keterangan */}
              <div className="flex flex-col gap-1">
                <label className="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Keterangan Tambahan</label>
                <textarea
                  placeholder="Detail lokasi rak gudang, tipe formula..."
                  value={data.keterangan}
                  onChange={(e) => setData("keterangan", e.target.value)}
                  rows={2}
                  className="w-full px-3 py-2 border border-slate-200 rounded-lg text-xs focus:ring-2 focus:ring-blue-500 outline-none resize-none font-medium"
                />
              </div>

              {/* Submit */}
              <div className="flex gap-3 justify-end pt-4 border-t border-slate-100">
                <button
                  type="button"
                  onClick={() => setModalOpen(false)}
                  className="px-4 py-2 border border-slate-200 text-slate-600 text-xs font-bold rounded-lg hover:bg-slate-50 transition-colors"
                >
                  Batal
                </button>
                <button
                  type="submit"
                  disabled={processing}
                  className="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold rounded-lg transition-colors shadow-lg shadow-blue-600/10"
                >
                  Simpan Bahan
                </button>
              </div>
            </form>

          </div>
        </div>
      )}

    </SumberPvcLayout>
  );
}
