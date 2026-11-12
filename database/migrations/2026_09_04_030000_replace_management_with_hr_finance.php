<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Pindahkan akun manajemen lama ke role HR/Keuangan.
     */
    public function up(): void
    {
        DB::table('users')->where('role', 'management')->update(['role' => 'hr']);
    }

    public function down(): void
    {
        // Tidak mengembalikan role lama agar akun yang telah digunakan HR/Keuangan
        // tidak berubah kembali ketika rollback dijalankan.
    }
};
