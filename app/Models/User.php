<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Traits\HasRoles;

/**
 * Model: User (PENGGUNA)
 * Sesuai ERD Proposal: id_pengguna, nama, username, password_hash, role
 *
 * Role yang valid: admin | hr | warehouse
 */
class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, HasRoles, Notifiable;

    /**
     * Role resmi yang diizinkan pada sistem.
     *
     * @var list<string>
     */
    public const ROLES = ['admin', 'hr', 'warehouse'];

    /**
     * Guard default Spatie Laravel Permission.
     */
    protected string $guard_name = 'web';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'nama',
        'username',
        'password',
        'role',
        'is_active',
        'last_login_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'is_active' => 'boolean',
            'last_login_at' => 'datetime',
        ];
    }

    /**
     * Sinkronkan kolom users.role ke role Spatie setiap kali user dibuat atau role diubah.
     */
    protected static function booted(): void
    {
        static::saved(function (User $user): void {
            if ($user->wasRecentlyCreated || $user->wasChanged('role')) {
                $user->syncSpatieRole();
            }
        });
    }

    /**
     * Sinkronisasi satu tempat dari kolom users.role ke relasi role Spatie.
     */
    public function syncSpatieRole(): void
    {
        if (! $this->role || ! in_array($this->role, self::ROLES, true)) {
            return;
        }

        Role::findOrCreate($this->role, $this->guard_name);
        $this->syncRoles([$this->role]);
    }

    /**
     * Tentukan nama route tujuan redirect setelah login berdasarkan role Spatie.
     */
    public function dashboardRouteName(): string
    {
        if ($this->hasRole('admin')) {
            return 'admin.dashboard';
        }

        if ($this->hasRole('hr')) {
            return 'hr.dashboard';
        }

        if ($this->hasRole('warehouse')) {
            return 'warehouse.dashboard';
        }

        return 'dashboard';
    }

    public function isAdmin(): bool
    {
        return $this->hasRole('admin');
    }

    public function isHr(): bool
    {
        return $this->hasRole('hr');
    }

    public function isWarehouse(): bool
    {
        return $this->hasRole('warehouse');
    }
}
