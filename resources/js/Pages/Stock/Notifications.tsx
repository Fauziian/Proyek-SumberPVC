import React from "react";
import SumberPvcLayout from "@/Layouts/SumberPvcLayout";
import { Head, router } from "@inertiajs/react";
import { Bell, Check, Eye, Trash, AlertTriangle } from "lucide-react";

interface Notification {
  id: number;
  pesan: string;
  is_read: boolean;
  created_at: string;
  barang_pvc?: {
    nama_barang: string;
    kode_barang: string;
  };
}

interface NotificationsProps {
  notifications: Notification[];
  unread_count: number;
}

export default function Notifications({ notifications, unread_count }: NotificationsProps) {
  
  const handleMarkAsRead = (id: number) => {
    router.put(route("stock.notifications.read", id));
  };

  const handleReadAll = () => {
    if (confirm("Apakah Anda yakin ingin menandai semua notifikasi stok kritis sebagai sudah dibaca?")) {
      router.post(route("stock.notifications.read-all"));
    }
  };

  return (
    <SumberPvcLayout>
      <Head title="Notifikasi Peringatan Stok" />

      {/* Header bar */}
      <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <h1 className="text-xl font-bold text-slate-800">Notifikasi Peringatan Stok Kritis</h1>
          <p className="text-xs text-slate-400">Daftar peringatan otomatis saat jumlah fisik bahan baku berada di bawah ambang batas minimum.</p>
        </div>

        {unread_count > 0 && (
          <button
            onClick={handleReadAll}
            className="inline-flex items-center gap-2 px-4 py-2 bg-slate-800 hover:bg-slate-900 text-white text-xs font-bold rounded-lg transition-all cursor-pointer self-start"
          >
            <Check size={14} />
            Tandai Semua Dibaca
          </button>
        )}
      </div>

      {/* LIST NOTIFIKASI */}
      <div className="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden p-6 space-y-4">
        
        <div className="flex items-center gap-2 text-slate-800 border-b border-slate-100 pb-3">
          <Bell className="text-blue-600" size={18} />
          <h2 className="text-xs font-bold">Log Peringatan Masuk ({unread_count} belum dibaca)</h2>
        </div>

        <div className="divide-y divide-slate-100 text-xs">
          {notifications.length === 0 ? (
            <div className="text-center py-12 text-slate-400 space-y-2">
              <AlertTriangle className="mx-auto text-slate-300" size={32} />
              <p className="font-semibold">Kotak notifikasi kosong.</p>
              <p className="text-[10px] text-slate-400">Seluruh bahan baku di gudang terpantau berada di atas level minimum.</p>
            </div>
          ) : (
            notifications.map((notif) => (
              <div 
                key={notif.id} 
                className={`py-4 px-3 flex items-start gap-4 transition-colors rounded-xl border mt-2 ${
                  !notif.is_read 
                    ? "bg-rose-50/50 border-rose-100" 
                    : "bg-slate-50/20 border-slate-100 text-slate-500"
                }`}
              >
                <div className={`w-8 h-8 rounded-lg flex items-center justify-center flex-shrink-0 ${
                  !notif.is_read 
                    ? "bg-rose-100 text-rose-600" 
                    : "bg-slate-100 text-slate-400"
                }`}>
                  <AlertTriangle size={15} />
                </div>

                <div className="flex-1 min-w-0">
                  <p className={`leading-relaxed ${!notif.is_read ? "font-bold text-slate-800" : "font-medium text-slate-600"}`}>
                    {notif.pesan}
                  </p>
                  <span className="text-[9px] text-slate-400 font-semibold block mt-1">
                    Waktu: {new Date(notif.created_at).toLocaleString('id-ID')}
                  </span>
                </div>

                {!notif.is_read && (
                  <button
                    onClick={() => handleMarkAsRead(notif.id)}
                    className="inline-flex items-center gap-1 px-2.5 py-1.5 bg-white hover:bg-slate-50 text-slate-700 text-[10px] font-bold rounded border border-slate-200 transition-colors shadow-sm cursor-pointer whitespace-nowrap"
                    title="Tandai dibaca"
                  >
                    <Eye size={12} /> Tandai Dibaca
                  </button>
                )}
              </div>
            ))
          )}
        </div>

      </div>

    </SumberPvcLayout>
  );
}
