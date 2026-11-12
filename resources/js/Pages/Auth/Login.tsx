import React from "react";
import { Head, useForm } from "@inertiajs/react";
import { Shield, Key, AlertTriangle } from "lucide-react";

export default function Login({ status }: { status?: string }) {
  const { data, setData, post, processing, errors, reset } = useForm({
    username: "",
    password: "",
    remember: false,
  });

  const submit = (e: React.FormEvent) => {
    e.preventDefault();
    post(route("login"), {
      onFinish: () => reset("password"),
    });
  };

  return (
    <div className="min-h-screen bg-slate-900 flex flex-col justify-center py-12 sm:px-6 lg:px-8 relative overflow-hidden font-sans">
      
      {/* Decorative background shapes */}
      <div className="absolute top-0 left-0 w-96 h-96 bg-blue-600/10 rounded-full blur-3xl pointer-events-none -translate-x-1/2 -translate-y-1/2" />
      <div className="absolute bottom-0 right-0 w-96 h-96 bg-indigo-600/10 rounded-full blur-3xl pointer-events-none translate-x-1/2 translate-y-1/2" />

      <Head title="Log In - Sistem Informasi Sumber PVC Outsole Tali Jepit" />

      <div className="sm:mx-auto sm:w-full sm:max-w-md relative z-10 text-center space-y-3">
        <div className="w-12 h-12 rounded-xl bg-blue-600 flex items-center justify-center mx-auto shadow-lg shadow-blue-500/20">
          <span className="text-white text-base font-black">SP</span>
        </div>
        <h2 className="text-xl sm:text-2xl font-black text-white tracking-tight">
          Sistem Informasi Operasional
        </h2>
        <p className="text-slate-400 text-xs sm:text-sm font-semibold uppercase tracking-widest">
          Sumber PVC Outsole Tali Jepit
        </p>
      </div>

      <div className="mt-8 sm:mx-auto sm:w-full sm:max-w-md relative z-10 px-4 sm:px-0">
        <div className="bg-slate-800/60 backdrop-blur-xl border border-slate-700/65 py-8 px-6 sm:px-10 rounded-2xl shadow-2xl space-y-6">
          
          {status && (
            <div className="text-xs text-green-400 font-bold bg-green-950/30 border border-green-800/40 p-3 rounded-lg text-center">
              {status}
            </div>
          )}

          {errors.username && (
            <div className="text-xs text-rose-400 font-bold bg-rose-950/30 border border-rose-800/40 p-3 rounded-lg flex items-center gap-2">
              <AlertTriangle size={14} className="flex-shrink-0" />
              <span>{errors.username}</span>
            </div>
          )}

          <form onSubmit={submit} className="space-y-4">
            {/* Username */}
            <div className="flex flex-col gap-1.5">
              <label className="text-[10px] font-bold text-slate-400 uppercase tracking-widest flex items-center gap-1.5">
                <Shield size={12} />
                Username Login
              </label>
              <input
                type="text"
                required
                autoFocus
                placeholder="Masukkan username sistem..."
                value={data.username}
                onChange={(e) => setData("username", e.target.value)}
                className="w-full px-3.5 py-2.5 bg-slate-900 border border-slate-700/80 rounded-xl text-xs font-semibold text-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all outline-none"
              />
            </div>

            {/* Password */}
            <div className="flex flex-col gap-1.5">
              <label className="text-[10px] font-bold text-slate-400 uppercase tracking-widest flex items-center gap-1.5">
                <Key size={12} />
                Kata Sandi
              </label>
              <input
                type="password"
                required
                placeholder="Masukkan kata sandi..."
                value={data.password}
                onChange={(e) => setData("password", e.target.value)}
                className="w-full px-3.5 py-2.5 bg-slate-900 border border-slate-700/80 rounded-xl text-xs font-semibold text-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all outline-none"
              />
              {errors.password && <span className="text-[10px] text-rose-500">{errors.password}</span>}
            </div>

            {/* Remember Me */}
            <div className="flex items-center">
              <input
                id="remember_me"
                name="remember"
                type="checkbox"
                checked={data.remember}
                onChange={(e) => setData("remember", e.target.checked)}
                className="h-4 w-4 rounded border-slate-700 bg-slate-900 text-blue-600 focus:ring-blue-500 focus:ring-offset-slate-900"
              />
              <label htmlFor="remember_me" className="ml-2 block text-xs font-semibold text-slate-400 select-none cursor-pointer">
                Ingat Sesi Masuk Saya
              </label>
            </div>

            {/* Submit */}
            <div className="pt-2">
              <button
                type="submit"
                disabled={processing}
                className="w-full flex justify-center py-2.5 px-4 border border-transparent rounded-xl text-xs font-bold text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-slate-900 focus:ring-blue-500 transition-colors shadow-lg shadow-blue-500/20 disabled:opacity-50 cursor-pointer"
              >
                {processing ? "Memproses Otentikasi..." : "Masuk ke Sistem"}
              </button>
            </div>

          </form>

          {/* Quick Demo Info */}
          <div className="text-[10px] text-slate-500 font-semibold border-t border-slate-700/50 pt-4 text-center">
            * Gunakan username (seperti: <strong className="text-slate-400">admin</strong>, <strong className="text-slate-400">hr</strong>, atau <strong className="text-slate-400">warehouse</strong>) dengan password default <strong className="text-slate-400">password</strong> untuk simulasi login role.
          </div>

        </div>
      </div>
    </div>
  );
}
