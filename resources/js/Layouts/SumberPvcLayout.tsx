import React, { useState, useEffect, useRef } from "react";
import { Link, usePage, router } from "@inertiajs/react";
import {
  LayoutDashboard, Users, Shield, Settings, UserCheck, Clock,
  CreditCard, FileText, BarChart2, Package, Bell, Search,
  ChevronDown, ChevronRight, ChevronLeft, LogOut, AlertTriangle, X, Menu
} from "lucide-react";
import { Toaster, toast } from "sonner";

type Role = "admin" | "hr" | "warehouse";
type Screen = string;

interface NavItem {
  id: string;
  label: string;
  icon: React.ElementType;
  route: string;
}

const navByRole: Record<Role, NavItem[]> = {
  admin: [
    { id: "dashboard", label: "Dashboard", icon: LayoutDashboard, route: "dashboard" },
    { id: "users", label: "Manajemen Pengguna", icon: Users, route: "users.index" },
    { id: "employees", label: "Data Karyawan", icon: UserCheck, route: "admin.employees" },
    { id: "master-products", label: "Master Produk", icon: Package, route: "admin.master-products" },
    { id: "settings", label: "Parameter Gaji", icon: Settings, route: "payroll.settings" },
    { id: "reports", label: "Laporan Keseluruhan", icon: FileText, route: "admin.reports" },
  ],
  hr: [
    { id: "dashboard", label: "Dashboard", icon: LayoutDashboard, route: "dashboard" },
    { id: "employees", label: "Data Karyawan", icon: UserCheck, route: "employees.index" },
    { id: "attendance", label: "Kehadiran Karyawan", icon: Clock, route: "attendance.index" },
    { id: "payroll", label: "Slip & Rekap Gaji", icon: CreditCard, route: "payroll.index" },
    { id: "reports", label: "Laporan HR/Keuangan", icon: FileText, route: "reports.index" },
  ],
  warehouse: [
    { id: "dashboard", label: "Dashboard", icon: LayoutDashboard, route: "dashboard" },
    { id: "available", label: "Stok Barang Tersedia", icon: Package, route: "stock.available" },
    { id: "incoming", label: "Barang Masuk", icon: Package, route: "stock.incoming" },
    { id: "outgoing", label: "Barang Keluar", icon: Package, route: "stock.outgoing" },
    { id: "notifications", label: "Notifikasi Kritis", icon: Bell, route: "stock.notifications" },
    { id: "weekly-report", label: "Laporan Mingguan/Bulanan", icon: BarChart2, route: "stock.weekly-report" },
  ],
};

const roleLabels: Record<Role, string> = {
  admin: "Administrator",
  hr: "HR / Keuangan",
  warehouse: "Staf Gudang",
};

export default function SumberPvcLayout({ children }: { children: React.ReactNode }) {
  const { auth, flash, unread_notifications_count } = usePage<any>().props;
  const user = auth.user;
  const role: Role = user.role;

  // Sidebar collapsed state (baca dari localStorage biar konsisten)
  const [collapsed, setCollapsed] = useState(() => {
    if (typeof window !== "undefined") {
      return localStorage.getItem("sidebar_collapsed") === "true";
    }
    return false;
  });

  const [isMobile, setIsMobile] = useState(false);
  const [isMobileOpen, setIsMobileOpen] = useState(false);
  const [showU, setShowU] = useState(false);
  const uRef = useRef<HTMLDivElement>(null);

  // Deteksi ukuran layar untuk mobile responsiveness
  useEffect(() => {
    const handleResize = () => {
      setIsMobile(window.innerWidth < 1024); // Menggunakan breakpoint lg (1024px) untuk kenyamanan tablet
    };
    handleResize();
    window.addEventListener("resize", handleResize);
    return () => window.removeEventListener("resize", handleResize);
  }, []);

  useEffect(() => {
    localStorage.setItem("sidebar_collapsed", String(collapsed));
  }, [collapsed]);

  // Tutup sidebar otomatis di mobile setelah pindah halaman
  useEffect(() => {
    setIsMobileOpen(false);
  }, [usePage().url]);

  // Handle flash messages dari Laravel
  useEffect(() => {
    if (flash?.success) {
      toast.success(flash.success);
    }
    if (flash?.error) {
      toast.error(flash.error);
    }
  }, [flash]);

  // Handle klik di luar user dropdown
  useEffect(() => {
    const handler = (e: MouseEvent) => {
      if (uRef.current && !uRef.current.contains(e.target as Node)) {
        setShowU(false);
      }
    };
    document.addEventListener("mousedown", handler);
    return () => document.removeEventListener("mousedown", handler);
  }, []);

  const handleLogout = () => {
    router.post(route("logout"));
  };

  const navItems = navByRole[role] || [];
  const initials = user.nama ? user.nama.split(" ").slice(0, 2).map((w: string) => w[0]).join("").toUpperCase() : "U";

  return (
    <div className="flex h-screen bg-slate-50 overflow-hidden font-sans">
      <Toaster position="top-right" richColors />

      {/* BACKDROP OVERLAY UNTUK MOBILE */}
      {isMobile && isMobileOpen && (
        <div
          className="fixed inset-0 bg-slate-950/60 backdrop-blur-xs z-30 transition-opacity duration-300"
          onClick={() => setIsMobileOpen(false)}
        />
      )}

      {/* SIDEBAR */}
      <div
        className={`flex flex-col flex-shrink-0 h-screen transition-all duration-300 ease-in-out z-40
          ${isMobile ? "fixed inset-y-0 left-0" : "relative"}
          ${isMobile ? (isMobileOpen ? "translate-x-0" : "-translate-x-full") : "translate-x-0"}
        `}
        style={{
          width: isMobile ? 260 : (collapsed ? 64 : 260),
          background: "#0F172A",
          borderRight: "1px solid #1E293B"
        }}
      >
        {/* Brand Header */}
        <div className="flex items-center h-14 px-4 border-b border-slate-800/80 flex-shrink-0 justify-between">
          <div className="flex items-center">
            <div className="w-8 h-8 rounded-lg bg-blue-600 flex items-center justify-center flex-shrink-0 shadow-lg shadow-blue-500/30">
              <span className="text-white text-sm font-bold">SP</span>
            </div>
            {(!collapsed || isMobile) && (
              <div className="ml-3 overflow-hidden">
                <p className="text-white text-[14px] font-bold whitespace-nowrap leading-tight tracking-wide">Sumber PVC</p>
                <p className="text-slate-500 text-[10px] whitespace-nowrap leading-tight">Outsole Tali Jepit</p>
              </div>
            )}
          </div>
          {isMobile && (
            <button
              onClick={() => setIsMobileOpen(false)}
              className="text-slate-400 hover:text-white p-1 rounded-md hover:bg-slate-800/50"
              title="Tutup Menu"
            >
              <X size={18} />
            </button>
          )}
        </div>

        {/* Navigation Items */}
        <nav className="flex-1 py-4 overflow-y-auto" style={{ scrollbarWidth: "none" }}>
          {(!collapsed || isMobile) && (
            <p className="px-4 mb-2 text-[10px] font-bold text-slate-600 uppercase tracking-widest">
              Menu Utama
            </p>
          )}
          <ul className="space-y-1 px-2">
            {navItems.filter((item) => route().has(item.route)).map((item) => {
              // Cek active route
              const active = route().current(item.route) ||
                             (item.id === "employees" && route().current("employees.*")) ||
                             (item.id === "payroll" && route().current("payroll.*"));
              const Icon = item.icon;
              return (
                <li key={item.id}>
                  <Link
                    href={route(item.route)}
                    className={`w-full flex items-center gap-3 px-3 py-2.5 rounded-lg text-[13px] font-semibold transition-all relative group ${
                      active
                        ? "bg-blue-600 text-white shadow-md shadow-blue-600/10"
                        : "text-slate-400 hover:bg-slate-800/50 hover:text-slate-100"
                    }`}
                  >
                    <Icon size={16} strokeWidth={2} className="flex-shrink-0" />
                    {(!collapsed || isMobile) && <span className="truncate">{item.label}</span>}

                    {/* Badge Notifikasi khusus untuk stok */}
                    {item.id === "notifications" && unread_notifications_count > 0 && (!collapsed || isMobile) && (
                      <span className="ml-auto px-1.5 py-0.5 text-[10px] font-bold bg-red-500 text-white rounded-full">
                        {unread_notifications_count}
                      </span>
                    )}

                    {collapsed && !isMobile && (
                      <span className="absolute left-[70px] top-1/2 -translate-y-1/2 bg-slate-800 border border-slate-700 text-white text-xs px-2.5 py-1.5 rounded-md whitespace-nowrap opacity-0 group-hover:opacity-100 pointer-events-none z-50 transition-all shadow-xl">
                        {item.label}
                        {item.id === "notifications" && unread_notifications_count > 0 && ` (${unread_notifications_count})`}
                      </span>
                    )}
                  </Link>
                </li>
              );
            })}
          </ul>
        </nav>

        {/* Footer Sidebar & Collapse Button */}
        <div className="border-t border-slate-800/80 flex-shrink-0 bg-slate-950/40">
          {(!collapsed || isMobile) && (
            <div className="px-4 py-3.5 flex items-center gap-3">
              <div className="w-8 h-8 rounded-full bg-blue-500/20 text-blue-400 flex items-center justify-center flex-shrink-0 font-bold text-xs border border-blue-500/30">
                {initials}
              </div>
              <div className="flex-1 min-w-0">
                <p className="text-white text-xs font-bold truncate leading-tight">{user.nama}</p>
                <p className="text-slate-500 text-[10px] truncate mt-0.5 leading-tight">{roleLabels[role]}</p>
              </div>
              <button
                onClick={handleLogout}
                className="text-slate-500 hover:text-red-400 transition-colors flex-shrink-0 p-1 rounded-md hover:bg-slate-800/40"
                title="Keluar dari Sistem"
              >
                <LogOut size={14} strokeWidth={2} />
              </button>
            </div>
          )}
          {!isMobile && (
            <button
              onClick={() => setCollapsed(!collapsed)}
              className="w-full h-10 flex items-center justify-center text-slate-500 hover:text-slate-300 hover:bg-slate-800/30 transition-colors"
            >
              {collapsed ? <ChevronRight size={15} strokeWidth={2} /> : <ChevronLeft size={15} strokeWidth={2} />}
            </button>
          )}
        </div>
      </div>

      {/* CONTENT AREA */}
      <div className="flex-1 flex flex-col h-screen overflow-hidden">
        {/* TOPBAR */}
        <header className="h-14 bg-white border-b border-slate-200/80 flex items-center px-4 md:px-6 justify-between flex-shrink-0 z-10">
          <div className="flex items-center gap-2">
            {isMobile && (
              <button
                onClick={() => setIsMobileOpen(true)}
                className="p-1.5 -ml-1 rounded-lg text-slate-500 hover:bg-slate-100 transition-colors"
                title="Buka Menu"
              >
                <Menu size={20} />
              </button>
            )}
            <span className="text-xs font-semibold text-slate-400 hidden sm:inline">Sumber PVC Outsole Tali Jepit</span>
            <ChevronRight size={12} className="text-slate-300 flex-shrink-0 font-bold hidden sm:inline" />
            <span className="text-xs font-bold text-slate-700 capitalize">
              {route().current()?.split(".")[0]?.replace("-", " ") || "Dashboard"}
            </span>
          </div>

          <div className="flex items-center gap-2 md:gap-4">
            {/* Profile Dropdown */}
            <div ref={uRef} className="relative">
              <button
                onClick={() => setShowU(!showU)}
                className="flex items-center gap-1.5 md:gap-2 pl-1.5 pr-2 md:pl-2 md:pr-3 py-1 rounded-lg hover:bg-slate-100 transition-colors border border-transparent hover:border-slate-200/60"
              >
                <div className="w-7 h-7 rounded-full bg-blue-600 text-white flex items-center justify-center text-[10px] font-bold">
                  {initials}
                </div>
                <div className="text-left hidden sm:block">
                  <p className="text-xs font-bold text-slate-800 leading-tight">{user.nama}</p>
                  <p className="text-[10px] text-slate-400 font-semibold leading-tight">{roleLabels[role]}</p>
                </div>
                <ChevronDown size={12} className="text-slate-400 transition-transform duration-200" style={{ transform: showU ? "rotate(180deg)" : "rotate(0)" }} />
              </button>

              {showU && (
                <div className="absolute right-0 top-11 w-48 bg-white border border-slate-200 rounded-xl shadow-xl z-50 overflow-hidden py-1 animate-in fade-in-50 duration-100">
                  <div className="px-4 py-2.5 border-b border-slate-100 bg-slate-50/50">
                    <p className="text-xs font-bold text-slate-900">{user.nama}</p>
                    <p className="text-[10px] text-slate-400 font-medium truncate">@{user.username}</p>
                  </div>
                  <div className="py-1">
                    <button
                      onClick={handleLogout}
                      className="w-full px-4 py-2 text-left text-xs text-red-600 hover:bg-red-50 flex items-center gap-2.5 font-semibold transition-colors"
                    >
                      <LogOut size={13} strokeWidth={2} />
                      Keluar
                    </button>
                  </div>
                </div>
              )}
            </div>
          </div>
        </header>

        {/* Page Content */}
        <main className="flex-1 overflow-y-auto bg-slate-50/30 p-4 md:p-6">
          <div className="max-w-7xl mx-auto space-y-4 md:space-y-6">
            {children}
          </div>
        </main>
      </div>
    </div>
  );
}
