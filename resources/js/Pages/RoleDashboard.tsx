import React from "react";
import { Head, Link, useForm, usePage } from "@inertiajs/react";
import { LogOut, ShieldCheck } from "lucide-react";

interface RoleDashboardProps {
  section: "general" | "admin" | "hr" | "warehouse";
  title: string;
  role: string;
}

const roleLabels: Record<string, string> = {
  admin: "Administrator",
  hr: "HR",
  warehouse: "Gudang",
};

export default function RoleDashboard({ section, title, role }: RoleDashboardProps) {
  const { auth } = usePage().props;
  const user = auth.user;
  const { post, processing } = useForm({});

  const handleLogout = (e: React.FormEvent) => {
    e.preventDefault();
    post(route("logout"));
  };

  return (
    <div className="min-h-screen bg-slate-950 text-slate-100 flex flex-col justify-center items-center p-6 font-sans">
      <Head title={`${title} - Sumber PVC`} />

      <div className="w-full max-w-xl bg-slate-900 border border-slate-800 rounded-2xl p-6 sm:p-8 shadow-2xl space-y-6">
        <div className="flex items-start justify-between gap-4">
          <div className="space-y-1">
            <span className="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold bg-blue-500/15 text-blue-400 border border-blue-500/30">
              <ShieldCheck size={14} />
              RBAC Spatie: {roleLabels[role] ?? role} ({section})
            </span>
            <h1 className="text-2xl font-extrabold tracking-tight text-white pt-1">
              {title}
            </h1>
            <p className="text-xs text-slate-400">
              Masuk sebagai <strong className="text-slate-200">{user?.nama}</strong> (@{user?.username})
            </p>
          </div>

          <form onSubmit={handleLogout}>
            <button
              type="submit"
              disabled={processing}
              className="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl text-xs font-bold bg-rose-600 hover:bg-rose-700 text-white transition-colors disabled:opacity-50"
            >
              <LogOut size={14} />
              Keluar
            </button>
          </form>
        </div>

        <div className="border-t border-slate-800 pt-4 space-y-2">
          <p className="text-xs font-semibold text-slate-400 uppercase tracking-wider">
            Uji Navigasi Route Terproteksi Role
          </p>
          <div className="grid grid-cols-2 sm:grid-cols-4 gap-2">
            <Link
              href={route("dashboard")}
              className="px-3 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-xs font-bold text-center"
            >
              Dashboard
            </Link>
            <Link
              href={route("admin.dashboard")}
              className="px-3 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-xs font-bold text-center"
            >
              Admin
            </Link>
            <Link
              href={route("hr.dashboard")}
              className="px-3 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-xs font-bold text-center"
            >
              HR
            </Link>
            <Link
              href={route("warehouse.dashboard")}
              className="px-3 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-xs font-bold text-center"
            >
              Gudang
            </Link>
          </div>
        </div>
      </div>
    </div>
  );
}

