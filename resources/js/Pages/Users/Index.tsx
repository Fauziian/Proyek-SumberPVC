import React, { useState } from "react";
import SumberPvcLayout from "@/Layouts/SumberPvcLayout";
import { Head, useForm, router, usePage } from "@inertiajs/react";
import { Plus, Edit2, Trash2, Search, X, Check, XCircle, ShieldAlert, Key } from "lucide-react";

interface User {
  id: number;
  nama: string;
  username: string;
  role: "admin" | "hr" | "warehouse";
  is_active: boolean;
  last_login_at?: string;
}

interface IndexProps {
  users: {
    data: User[];
    links: any[];
    current_page: number;
    last_page: number;
    total: number;
  };
  filters: {
    search?: string;
    role?: string;
    status?: string;
  };
}

export default function Index({ users, filters }: IndexProps) {
  const currentUser = usePage<any>().props.auth.user;
  const [search, setSearch] = useState(filters.search || "");
  const [roleFilter, setRoleFilter] = useState(filters.role || "");
  const [statusFilter, setStatusFilter] = useState(filters.status || "");
  const [modalOpen, setModalOpen] = useState(false);
  const [resetModalOpen, setResetModalOpen] = useState(false);
  const [editingUser, setEditingUser] = useState<User | null>(null);
  const [targetUser, setTargetUser] = useState<User | null>(null);

  // Form tambah/edit user
  const { data, setData, post, put, delete: destroy, errors, reset, processing } = useForm({
    nama: "",
    username: "",
    password: "",
    role: "hr" as any,
    is_active: true,
  });

  // Form reset password
  const resetForm = useForm({
    password: "",
  });

  const handleSearchSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    router.get(route("users.index"), { search, role: roleFilter, status: statusFilter }, { preserveState: true });
  };

  const handleRoleFilter = (val: string) => {
    setRoleFilter(val);
    router.get(route("users.index"), { search, role: val, status: statusFilter }, { preserveState: true });
  };

  const handleStatusFilter = (val: string) => {
    setStatusFilter(val);
    router.get(route("users.index"), { search, role: roleFilter, status: val }, { preserveState: true });
  };

  const openAddModal = () => {
    setEditingUser(null);
    reset();
    setModalOpen(true);
  };

  const openEditModal = (user: User) => {
    setEditingUser(user);
    setData({
      nama: user.nama,
      username: user.username,
      password: "", // dikosongkan pada edit
      role: user.role,
      is_active: user.is_active,
    });
    setModalOpen(true);
  };

  const openResetModal = (user: User) => {
    setTargetUser(user);
    resetForm.reset();
    setResetModalOpen(true);
  };

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    if (editingUser) {
      put(route("users.update", editingUser.id), {
        onSuccess: () => {
          setModalOpen(false);
          reset();
        }
      });
    } else {
      post(route("users.store"), {
        onSuccess: () => {
          setModalOpen(false);
          reset();
        }
      });
    }
  };

  const handleResetSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    if (targetUser) {
      resetForm.put(route("users.reset-password", targetUser.id), {
        onSuccess: () => {
          setResetModalOpen(false);
          resetForm.reset();
        }
      });
    }
  };

  const handleDelete = (user: User) => {
    if (user.id === currentUser.id) {
      alert("Anda tidak dapat menghapus akun Anda sendiri!");
      return;
    }
    if (confirm(`Apakah Anda yakin ingin menghapus akun pengguna "${user.nama}" dari sistem? Relasi biodata karyawan akan terlepas.`)) {
      destroy(route("users.destroy", user.id));
    }
  };

  const roleLabels: Record<string, string> = {
    admin: "Administrator",
    hr: "HR / Keuangan",
    warehouse: "Staf Gudang",
  };

  return (
    <SumberPvcLayout>
      <Head title="Manajemen Akun Pengguna" />

      {/* Header bar */}
      <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <h1 className="text-xl font-bold text-slate-800">Manajemen Akun Pengguna</h1>
          <p className="text-xs text-slate-400">Kelola kredensial login, hak akses RBAC, status aktif, dan reset kata sandi staf.</p>
        </div>
        <button
          onClick={openAddModal}
          className="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold rounded-lg shadow-lg shadow-blue-500/10 transition-all cursor-pointer self-start"
        >
          <Plus size={14} />
          Daftarkan Akun Baru
        </button>
      </div>

      {/* SEARCH AND FILTERS */}
      <div className="bg-white rounded-2xl border border-slate-200/80 p-4 shadow-sm flex flex-col md:flex-row gap-4 items-center justify-between">
        <form onSubmit={handleSearchSubmit} className="relative w-full md:w-80">
          <Search size={14} className="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" />
          <input
            type="text"
            placeholder="Cari nama atau username..."
            value={search}
            onChange={(e) => setSearch(e.target.value)}
            className="w-full pl-9 pr-4 py-2 border border-slate-200 rounded-lg text-xs focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-colors"
          />
        </form>

        <div className="flex gap-3 w-full md:w-auto">
          <select
            value={roleFilter}
            onChange={(e) => handleRoleFilter(e.target.value)}
            className="px-3 py-2 border border-slate-200 rounded-lg text-xs bg-white focus:outline-none focus:ring-2 focus:ring-blue-500"
          >
            <option value="">Semua Hak Akses (Role)</option>
            <option value="admin">Administrator</option>
            <option value="hr">HR / Keuangan</option>
            <option value="warehouse">Warehouse Staff</option>
          </select>

          <select
            value={statusFilter}
            onChange={(e) => handleStatusFilter(e.target.value)}
            className="px-3 py-2 border border-slate-200 rounded-lg text-xs bg-white focus:outline-none focus:ring-2 focus:ring-blue-500"
          >
            <option value="">Semua Status</option>
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
                <th className="px-6 py-4">Nama Lengkap</th>
                <th className="px-6 py-4">Username</th>
                <th className="px-6 py-4">Hak Akses (Role)</th>
                <th className="px-6 py-4">Status Akun</th>
                <th className="px-6 py-4">Waktu Login Terakhir</th>
                <th className="px-6 py-4 text-right">Aksi</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-slate-50 text-xs">
              {users.data.length === 0 ? (
                <tr>
                  <td colSpan={6} className="px-6 py-8 text-center text-slate-400">
                    Tidak ada akun pengguna terdaftar.
                  </td>
                </tr>
              ) : (
                users.data.map((user) => (
                  <tr key={user.id} className="hover:bg-slate-50/50">
                    <td className="px-6 py-4">
                      <p className="font-semibold text-slate-800">{user.nama}</p>
                      {user.id === currentUser.id && (
                        <span className="inline-flex px-1.5 py-0.5 text-[8px] font-bold bg-blue-50 text-blue-700 border border-blue-200 rounded mt-0.5">
                          AKUN ANDA
                        </span>
                      )}
                    </td>
                    <td className="px-6 py-4">
                      <span className="font-mono font-semibold text-slate-500">@{user.username}</span>
                    </td>
                    <td className="px-6 py-4">
                      <span className={`px-2 py-0.5 rounded font-bold text-[10px] uppercase border ${
                        user.role === "admin" ? "bg-red-50 text-red-700 border-red-200" :
                        user.role === "hr" ? "bg-emerald-50 text-emerald-700 border-emerald-200" :
                        user.role === "warehouse" ? "bg-indigo-50 text-indigo-700 border-indigo-200" :
                        "bg-purple-50 text-purple-700 border-purple-200"
                      }`}>
                        {roleLabels[user.role]}
                      </span>
                    </td>
                    <td className="px-6 py-4">
                      {user.is_active ? (
                        <span className="inline-flex items-center gap-1 text-green-700 font-bold bg-green-50 px-2 py-0.5 rounded border border-green-200 text-[10px]">
                          <Check size={10} /> AKTIF
                        </span>
                      ) : (
                        <span className="inline-flex items-center gap-1 text-slate-400 font-bold bg-slate-100 px-2 py-0.5 rounded border border-slate-200 text-[10px]">
                          <XCircle size={10} /> NONAKTIF
                        </span>
                      )}
                    </td>
                    <td className="px-6 py-4 text-slate-500 font-medium">
                      {user.last_login_at 
                        ? new Date(user.last_login_at).toLocaleString('id-ID')
                        : <span className="text-slate-300 italic font-normal">Belum pernah login</span>}
                    </td>
                    <td className="px-6 py-4 text-right space-x-1.5 whitespace-nowrap">
                      <button
                        onClick={() => openResetModal(user)}
                        className="p-1.5 hover:bg-amber-50 rounded-lg text-slate-400 hover:text-amber-600 transition-colors"
                        title="Reset Kata Sandi"
                      >
                        <Key size={13} />
                      </button>
                      <button
                        onClick={() => openEditModal(user)}
                        className="p-1.5 hover:bg-blue-50 rounded-lg text-slate-400 hover:text-blue-600 transition-colors"
                        title="Ubah Profil"
                      >
                        <Edit2 size={13} />
                      </button>
                      <button
                        onClick={() => handleDelete(user)}
                        disabled={user.id === currentUser.id}
                        className="p-1.5 hover:bg-red-50 rounded-lg text-slate-400 hover:text-red-500 transition-colors disabled:opacity-30 disabled:hover:bg-transparent"
                        title="Hapus Akun"
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
        {users.total > 10 && (
          <div className="px-6 py-4 border-t border-slate-100 bg-slate-50 flex items-center justify-between">
            <span className="text-xs text-slate-400">
              Menampilkan {users.data.length} dari {users.total} akun pengguna
            </span>
            <div className="flex gap-2">
              {users.links.map((link: any, idx: number) => (
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

      {/* FORM MODAL (TAMBAH/EDIT) */}
      {modalOpen && (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4">
          <div className="absolute inset-0 bg-slate-900/40 backdrop-blur-sm" onClick={() => setModalOpen(false)} />
          <div className="relative bg-white rounded-2xl border border-slate-200 shadow-2xl w-full max-w-sm flex flex-col overflow-hidden animate-in zoom-in-95 duration-150">
            
            <div className="flex items-center justify-between px-6 py-4 border-b border-slate-100">
              <h2 className="text-sm font-bold text-slate-800">
                {editingUser ? "Ubah Profil Akun" : "Daftarkan Pengguna Baru"}
              </h2>
              <button onClick={() => setModalOpen(false)} className="w-7 h-7 flex items-center justify-center rounded-lg text-slate-400 hover:bg-slate-100 transition-colors">
                <X size={15} />
              </button>
            </div>

            <form onSubmit={handleSubmit} className="p-6 space-y-4 text-slate-700">
              {/* Nama */}
              <div className="flex flex-col gap-1">
                <label className="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Nama Lengkap Pengguna</label>
                <input
                  type="text"
                  required
                  placeholder="Masukkan nama lengkap..."
                  value={data.nama}
                  onChange={(e) => setData("nama", e.target.value)}
                  className="w-full px-3 py-2 border border-slate-200 rounded-lg text-xs focus:ring-2 focus:ring-blue-500 outline-none font-semibold"
                />
                {errors.nama && <span className="text-[10px] text-red-500">{errors.nama}</span>}
              </div>

              {/* Username */}
              <div className="flex flex-col gap-1">
                <label className="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Username Login</label>
                <div className="relative">
                  <span className="absolute left-3 top-1/2 -translate-y-1/2 text-xs font-bold text-slate-400">@</span>
                  <input
                    type="text"
                    required
                    placeholder="nama_pilihan..."
                    value={data.username}
                    onChange={(e) => setData("username", e.target.value.toLowerCase().replace(/[^a-z0-9_]/g, ""))}
                    className="w-full pl-7 pr-3 py-2 border border-slate-200 rounded-lg text-xs focus:ring-2 focus:ring-blue-500 outline-none font-mono font-semibold"
                  />
                </div>
                {errors.username && <span className="text-[10px] text-red-500">{errors.username}</span>}
              </div>

              {/* Password - Hanya saat buat baru */}
              {!editingUser && (
                <div className="flex flex-col gap-1">
                  <label className="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Kata Sandi Awal</label>
                  <input
                    type="password"
                    required
                    placeholder="Minimal 6 karakter..."
                    value={data.password}
                    onChange={(e) => setData("password", e.target.value)}
                    className="w-full px-3 py-2 border border-slate-200 rounded-lg text-xs focus:ring-2 focus:ring-blue-500 outline-none font-semibold"
                  />
                  {errors.password && <span className="text-[10px] text-red-500">{errors.password}</span>}
                </div>
              )}

              {/* Role */}
              <div className="flex flex-col gap-1">
                <label className="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Hak Akses (Role)</label>
                <select
                  value={data.role}
                  onChange={(e) => setData("role", e.target.value as any)}
                  disabled={editingUser?.id === currentUser.id}
                  className="w-full px-3 py-2 border border-slate-200 rounded-lg text-xs focus:ring-2 focus:ring-blue-500 outline-none bg-white font-semibold disabled:bg-slate-50 disabled:text-slate-400"
                >
                  <option value="admin">Administrator</option>
                  <option value="hr">HR / Keuangan</option>
                  <option value="warehouse">Staf Gudang (Warehouse)</option>
                </select>
                {editingUser?.id === currentUser.id && (
                  <span className="text-[9px] text-slate-400 italic">Anda tidak dapat mengubah role Anda sendiri.</span>
                )}
              </div>

              {/* Status */}
              <div className="flex items-center gap-2 pt-2">
                <input
                  type="checkbox"
                  id="is_active_usr"
                  checked={data.is_active}
                  disabled={editingUser?.id === currentUser.id}
                  onChange={(e) => setData("is_active", e.target.checked)}
                  className="w-4 h-4 text-blue-600 border-slate-300 rounded focus:ring-blue-500 disabled:opacity-40"
                />
                <label htmlFor="is_active_usr" className="text-xs font-bold text-slate-700 select-none">
                  Akun ini berstatus aktif
                </label>
              </div>
              {editingUser?.id === currentUser.id && (
                <span className="text-[9px] text-slate-400 italic block -mt-2">Anda tidak dapat menonaktifkan akun Anda sendiri.</span>
              )}

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
                  Simpan Akun
                </button>
              </div>
            </form>

          </div>
        </div>
      )}

      {/* RESET PASSWORD MODAL */}
      {resetModalOpen && targetUser && (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4">
          <div className="absolute inset-0 bg-slate-900/40 backdrop-blur-sm" onClick={() => setResetModalOpen(false)} />
          <div className="relative bg-white rounded-2xl border border-slate-200 shadow-2xl w-full max-w-sm flex flex-col overflow-hidden animate-in zoom-in-95 duration-150">
            
            <div className="flex items-center gap-2 px-6 py-4 border-b border-slate-100 text-slate-800">
              <Key className="text-amber-600" size={16} />
              <h2 className="text-xs font-bold">Reset Kata Sandi Pengguna</h2>
              <button onClick={() => setResetModalOpen(false)} className="ml-auto w-7 h-7 flex items-center justify-center rounded-lg text-slate-400 hover:bg-slate-100 transition-colors">
                <X size={15} />
              </button>
            </div>

            <form onSubmit={handleResetSubmit} className="p-6 space-y-4 text-slate-700">
              <div>
                <p className="text-xs text-slate-500 font-semibold leading-relaxed">
                  Anda akan mengatur ulang kata sandi login untuk akun <strong className="text-slate-800">@{targetUser.username}</strong> ({targetUser.nama}).
                </p>
              </div>

              <div className="flex flex-col gap-1">
                <label className="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Kata Sandi Baru</label>
                <input
                  type="password"
                  required
                  placeholder="Masukkan kata sandi baru (min. 6 karakter)..."
                  value={resetForm.data.password}
                  onChange={(e) => resetForm.setData("password", e.target.value)}
                  className="w-full px-3 py-2 border border-slate-200 rounded-lg text-xs focus:ring-2 focus:ring-blue-500 outline-none font-semibold"
                />
                {resetForm.errors.password && <span className="text-[10px] text-red-500">{resetForm.errors.password}</span>}
              </div>

              <div className="flex gap-3 justify-end pt-4 border-t border-slate-100">
                <button
                  type="button"
                  onClick={() => setResetModalOpen(false)}
                  className="px-4 py-2 border border-slate-200 text-slate-600 text-xs font-bold rounded-lg hover:bg-slate-50 transition-colors"
                >
                  Batal
                </button>
                <button
                  type="submit"
                  disabled={resetForm.processing}
                  className="px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white text-xs font-bold rounded-lg transition-colors shadow-lg shadow-amber-600/10"
                >
                  Reset Kata Sandi
                </button>
              </div>
            </form>

          </div>
        </div>
      )}

    </SumberPvcLayout>
  );
}
