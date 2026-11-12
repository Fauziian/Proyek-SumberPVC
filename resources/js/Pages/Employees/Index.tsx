import React, { useState } from "react";
import SumberPvcLayout from "@/Layouts/SumberPvcLayout";
import { Head, useForm, router } from "@inertiajs/react";
import { Plus, Edit2, Trash2, Search, X, Check, XCircle } from "lucide-react";

interface Employee {
  id: number;
  nama: string;
  jabatan: string;
  tanggal_masuk: string;
  kategori_masa_kerja: "A" | "B";
  is_active: boolean;
  id_pengguna?: number;
  pengguna?: {
    nama: string;
    username: string;
    role: string;
  };
}

interface IndexProps {
  karyawan: {
    data: Employee[];
    links: any[];
    current_page: number;
    last_page: number;
    total: number;
  };
  filters: {
    search?: string;
    status?: string;
  };
  availableUsers: {
    id: number;
    nama: string;
    username: string;
    role: string;
  }[];
  readOnly?: boolean;
  indexRoute?: string;
}

export default function Index({ karyawan, filters, availableUsers, readOnly = false, indexRoute = "employees.index" }: IndexProps) {
  const [search, setSearch] = useState(filters.search || "");
  const [status, setStatus] = useState(filters.status || "");
  const [modalOpen, setModalOpen] = useState(false);
  const [editingEmployee, setEditingEmployee] = useState<Employee | null>(null);

  // Form setup using Inertia
  const { data, setData, post, put, delete: destroy, errors, reset, processing } = useForm({
    nama: "",
    jabatan: "",
    tanggal_masuk: "",
    kategori_masa_kerja: "A" as "A" | "B",
    is_active: true,
    id_pengguna: "" as string | number,
  });

  const handleSearchSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    router.get(route(indexRoute), { search, status }, { preserveState: true });
  };

  const handleStatusFilterChange = (val: string) => {
    setStatus(val);
    router.get(route(indexRoute), { search, status: val }, { preserveState: true });
  };

  const openAddModal = () => {
    setEditingEmployee(null);
    reset();
    setModalOpen(true);
  };

  const openEditModal = (emp: Employee) => {
    setEditingEmployee(emp);
    setData({
      nama: emp.nama,
      jabatan: emp.jabatan,
      tanggal_masuk: emp.tanggal_masuk ? emp.tanggal_masuk.slice(0, 10) : "",
      kategori_masa_kerja: emp.kategori_masa_kerja,
      is_active: emp.is_active,
      id_pengguna: emp.id_pengguna || "",
    });
    setModalOpen(true);
  };

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    if (editingEmployee) {
      put(route("employees.update", editingEmployee.id), {
        onSuccess: () => {
          setModalOpen(false);
          reset();
        }
      });
    } else {
      post(route("employees.store"), {
        onSuccess: () => {
          setModalOpen(false);
          reset();
        }
      });
    }
  };

  const handleDelete = (id: number) => {
    if (confirm("Apakah Anda yakin ingin menghapus data karyawan ini dari sistem?")) {
      destroy(route("employees.destroy", id));
    }
  };

  return (
    <SumberPvcLayout>
      <Head title="Manajemen Karyawan" />

      {/* Header bar */}
      <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <h1 className="text-xl font-bold text-slate-800">Data Karyawan</h1>
          <p className="text-xs text-slate-400">{readOnly ? "Tampilan baca-saja seluruh data karyawan. Absensi dan penggajian dikelola oleh HR." : "Kelola biodata profil, jabatan, masa kerja, dan status akun terhubung karyawan."}</p>
        </div>
        {!readOnly && <button
          onClick={openAddModal}
          className="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold rounded-lg shadow-lg shadow-blue-500/10 transition-all cursor-pointer self-start"
        >
          <Plus size={14} />
          Tambah Karyawan
        </button>}
      </div>

      {/* SEARCH AND FILTERS */}
      <div className="bg-white rounded-2xl border border-slate-200/80 p-4 shadow-sm flex flex-col md:flex-row gap-4 items-center justify-between">
        <form onSubmit={handleSearchSubmit} className="relative w-full md:w-80">
          <Search size={14} className="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" />
          <input
            type="text"
            placeholder="Cari nama atau jabatan..."
            value={search}
            onChange={(e) => setSearch(e.target.value)}
            className="w-full pl-9 pr-4 py-2 border border-slate-200 rounded-lg text-xs focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-colors"
          />
        </form>

        <div className="flex gap-3 w-full md:w-auto">
          <select
            value={status}
            onChange={(e) => handleStatusFilterChange(e.target.value)}
            className="w-full md:w-40 px-3 py-2 border border-slate-200 rounded-lg text-xs bg-white focus:outline-none focus:ring-2 focus:ring-blue-500"
          >
            <option value="">Semua Status Keaktifan</option>
            <option value="active">Aktif</option>
            <option value="inactive">Nonaktif</option>
          </select>
        </div>
      </div>

      {/* TABLE */}
      <div className="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
        <div className="overflow-x-auto">
          <table className="w-full text-left border-collapse">
            <thead>
              <tr className="border-b border-slate-100 bg-slate-50/50 text-[10px] font-bold text-slate-400 uppercase tracking-wider">
                <th className="px-6 py-4">Nama</th>
                <th className="px-6 py-4">Jabatan</th>
                <th className="px-6 py-4">Tanggal Masuk</th>
                <th className="px-6 py-4">Masa Kerja</th>
                <th className="px-6 py-4">Status</th>
                <th className="px-6 py-4">Akun Sistem</th>
                {!readOnly && <th className="px-6 py-4 text-right">Aksi</th>}
              </tr>
            </thead>
            <tbody className="divide-y divide-slate-50 text-xs">
              {karyawan.data.length === 0 ? (
                <tr>
                  <td colSpan={7} className="px-6 py-8 text-center text-slate-400">
                    Tidak ada data karyawan yang ditemukan.
                  </td>
                </tr>
              ) : (
                karyawan.data.map((emp) => (
                  <tr key={emp.id} className="hover:bg-slate-50/50">
                    <td className="px-6 py-4">
                      <p className="font-semibold text-slate-800">{emp.nama}</p>
                    </td>
                    <td className="px-6 py-4 text-slate-600 font-medium">{emp.jabatan}</td>
                    <td className="px-6 py-4 text-slate-500">
                      {emp.tanggal_masuk ? new Date(emp.tanggal_masuk.slice(0, 10)).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' }) : '-'}
                    </td>
                    <td className="px-6 py-4">
                      <span className={`inline-flex px-2 py-0.5 rounded text-[10px] font-bold ${
                        emp.kategori_masa_kerja === "B" 
                          ? "bg-blue-50 text-blue-700 border border-blue-100" 
                          : "bg-slate-100 text-slate-600"
                      }`}>
                        {emp.kategori_masa_kerja === "B" ? "≥ 5 Tahun (Kat B)" : "< 5 Tahun (Kat A)"}
                      </span>
                    </td>
                    <td className="px-6 py-4">
                      {emp.is_active ? (
                        <span className="inline-flex items-center gap-1 text-green-700 font-bold bg-green-50 px-2 py-0.5 rounded border border-green-200 text-[10px]">
                          <Check size={10} /> AKTIF
                        </span>
                      ) : (
                        <span className="inline-flex items-center gap-1 text-slate-400 font-bold bg-slate-100 px-2 py-0.5 rounded border border-slate-200 text-[10px]">
                          <XCircle size={10} /> NONAKTIF
                        </span>
                      )}
                    </td>
                    <td className="px-6 py-4">
                      {emp.pengguna ? (
                        <div>
                          <p className="font-semibold text-slate-700">@{emp.pengguna.username}</p>
                          <p className="text-[10px] text-slate-400 font-medium capitalize">{emp.pengguna.role}</p>
                        </div>
                      ) : (
                        <span className="text-[10px] text-slate-400 italic">Belum dikaitkan</span>
                      )}
                    </td>
                    {!readOnly && <td className="px-6 py-4 text-right space-x-1.5 whitespace-nowrap">
                      <button
                        onClick={() => openEditModal(emp)}
                        className="p-1.5 hover:bg-blue-50 rounded-lg text-slate-400 hover:text-blue-600 transition-colors"
                        title="Ubah data"
                      >
                        <Edit2 size={13} />
                      </button>
                      <button
                        onClick={() => handleDelete(emp.id)}
                        className="p-1.5 hover:bg-red-50 rounded-lg text-slate-400 hover:text-red-500 transition-colors"
                        title="Hapus data"
                      >
                        <Trash2 size={13} />
                      </button>
                    </td>}
                  </tr>
                ))
              )}
            </tbody>
          </table>
        </div>

        {/* PAGINATION */}
        {karyawan.total > 10 && (
          <div className="px-6 py-4 border-t border-slate-100 bg-slate-50 flex items-center justify-between">
            <span className="text-xs text-slate-400">
              Menampilkan {karyawan.data.length} dari {karyawan.total} karyawan
            </span>
            <div className="flex gap-2">
              {karyawan.links.map((link: any, idx: number) => (
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
                {editingEmployee ? "Ubah Data Karyawan" : "Tambah Karyawan Baru"}
              </h2>
              <button 
                onClick={() => setModalOpen(false)}
                className="w-7 h-7 flex items-center justify-center rounded-lg text-slate-400 hover:bg-slate-100 transition-colors"
              >
                <X size={15} />
              </button>
            </div>

            <form onSubmit={handleSubmit} className="p-6 space-y-4">
              {/* Nama */}
              <div className="flex flex-col gap-1">
                <label className="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Nama Lengkap</label>
                <input
                  type="text"
                  required
                  placeholder="Masukkan nama lengkap..."
                  value={data.nama}
                  onChange={(e) => setData("nama", e.target.value)}
                  className="w-full px-3 py-2 border border-slate-200 rounded-lg text-xs focus:ring-2 focus:ring-blue-500 outline-none"
                />
                {errors.nama && <span className="text-[10px] text-red-500">{errors.nama}</span>}
              </div>

              {/* Jabatan */}
              <div className="flex flex-col gap-1">
                <label className="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Jabatan</label>
                <input
                  type="text"
                  required
                  placeholder="Contoh: Operator Produksi, Akuntan..."
                  value={data.jabatan}
                  onChange={(e) => setData("jabatan", e.target.value)}
                  className="w-full px-3 py-2 border border-slate-200 rounded-lg text-xs focus:ring-2 focus:ring-blue-500 outline-none"
                />
                {errors.jabatan && <span className="text-[10px] text-red-500">{errors.jabatan}</span>}
              </div>

              <div className="grid grid-cols-2 gap-4">
                {/* Tanggal Masuk */}
                <div className="flex flex-col gap-1">
                  <label className="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Tanggal Masuk</label>
                  <input
                    type="date"
                    required
                    value={data.tanggal_masuk}
                    onChange={(e) => setData("tanggal_masuk", e.target.value)}
                    className="w-full px-3 py-2 border border-slate-200 rounded-lg text-xs focus:ring-2 focus:ring-blue-500 outline-none"
                  />
                  {errors.tanggal_masuk && <span className="text-[10px] text-red-500">{errors.tanggal_masuk}</span>}
                </div>

                {/* Kategori Masa Kerja */}
                <div className="flex flex-col gap-1">
                  <label className="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Kategori Gaji</label>
                  <select
                    value={data.kategori_masa_kerja}
                    onChange={(e) => setData("kategori_masa_kerja", e.target.value as "A" | "B")}
                    className="w-full px-3 py-2 border border-slate-200 rounded-lg text-xs focus:ring-2 focus:ring-blue-500 outline-none bg-white"
                  >
                    <option value="A">Kategori A (&lt; 5 thn)</option>
                    <option value="B">Kategori B (&ge; 5 thn)</option>
                  </select>
                </div>
              </div>

              {/* Hubungkan ke User */}
              <div className="flex flex-col gap-1">
                <label className="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Akun Pengguna Terkait</label>
                <select
                  value={data.id_pengguna}
                  onChange={(e) => setData("id_pengguna", e.target.value)}
                  className="w-full px-3 py-2 border border-slate-200 rounded-lg text-xs focus:ring-2 focus:ring-blue-500 outline-none bg-white"
                >
                  <option value="">Jangan hubungkan ke akun sistem</option>
                  {/* Tampilkan akun saat ini jika sedang mengedit */}
                  {editingEmployee?.pengguna && (
                    <option value={editingEmployee.id_pengguna}>
                      {editingEmployee.pengguna.nama} (@{editingEmployee.pengguna.username}) - Saat ini
                    </option>
                  )}
                  {availableUsers.map((u) => (
                    <option key={u.id} value={u.id}>
                      {u.nama} (@{u.username}) - {u.role}
                    </option>
                  ))}
                </select>
                {errors.id_pengguna && <span className="text-[10px] text-red-500">{errors.id_pengguna}</span>}
              </div>

              {/* Status */}
              <div className="flex items-center gap-2 pt-2">
                <input
                  type="checkbox"
                  id="is_active_emp"
                  checked={data.is_active}
                  onChange={(e) => setData("is_active", e.target.checked)}
                  className="w-4 h-4 text-blue-600 border-slate-300 rounded focus:ring-blue-500"
                />
                <label htmlFor="is_active_emp" className="text-xs font-bold text-slate-700 select-none">
                  Karyawan ini masih aktif bekerja
                </label>
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
                  Simpan Data
                </button>
              </div>
            </form>

          </div>
        </div>
      )}

    </SumberPvcLayout>
  );
}
