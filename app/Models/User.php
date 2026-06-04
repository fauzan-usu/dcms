<?php
// ============================================================
// app/Models/User.php
// ============================================================
namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\{SoftDeletes, Builder};
use Illuminate\Database\Eloquent\Relations\{BelongsTo, HasMany};
use Illuminate\Support\Facades\Cache;

class User extends Authenticatable
{
    use Notifiable, SoftDeletes;

    protected $fillable = [
        'role_id', 'name', 'email', 'phone', 'institution', 'avatar',
        'password', 'is_active', 'two_factor_enabled', 'two_factor_secret',
        'last_login_at', 'last_login_ip', 'password_reset_token', 'password_reset_expires_at',
    ];

    protected $hidden = [
        'password', 'remember_token', 'two_factor_secret',
        'password_reset_token', 'verification_token',
    ];

    protected $casts = [
        'email_verified_at'        => 'datetime',
        'last_login_at'            => 'datetime',
        'password_reset_expires_at'=> 'datetime',
        'is_active'                => 'boolean',
        'two_factor_enabled'       => 'boolean',
    ];

    // -----------------------------------------------------------------
    // RELASI
    // -----------------------------------------------------------------

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function loginHistories(): HasMany
    {
        return $this->hasMany(LoginHistory::class);
    }

    public function certificates(): HasMany
    {
        return $this->hasMany(Certificate::class, 'user_id');
    }

    public function issuedCertificates(): HasMany
    {
        return $this->hasMany(Certificate::class, 'issued_by');
    }

    // -----------------------------------------------------------------
    // HELPERS: Role & Permission
    // -----------------------------------------------------------------

    /** Mengecek apakah user memiliki role tertentu */
    public function hasRole(string $roleName): bool
    {
        return $this->role?->name === $roleName;
    }

    /** Mengecek apakah user adalah Super Admin */
    public function isSuperAdmin(): bool
    {
        return $this->hasRole('super_admin');
    }

    /**
     * Mengecek permission dengan caching agar tidak membebani DB.
     * Permission di-cache per-user selama 10 menit.
     */
    public function hasPermission(string $permissionName): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        $permissions = Cache::remember(
            "user_permissions_{$this->id}",
            600,
            fn() => $this->role
                ->permissions()
                ->pluck('name')
                ->toArray()
        );

        return in_array($permissionName, $permissions, true);
    }

    /** Membersihkan cache permission ketika role berubah */
    public function clearPermissionCache(): void
    {
        Cache::forget("user_permissions_{$this->id}");
    }

    // -----------------------------------------------------------------
    // SCOPE
    // -----------------------------------------------------------------

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
