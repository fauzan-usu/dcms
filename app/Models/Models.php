<?php namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\{BelongsTo, HasMany, BelongsToMany};

// ============================================================
// Role.php
// ============================================================
class Role extends Model
{
    protected $fillable = ['name', 'display_name', 'description', 'is_active'];
    protected $casts = ['is_active' => 'boolean'];

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'role_permission');
    }
}

// ============================================================
// Permission.php
// ============================================================
class Permission extends Model
{
    protected $fillable = ['name', 'module', 'action', 'display_name'];

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_permission');
    }
}

// ============================================================
// Event.php
// ============================================================
class Event extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'created_by', 'name', 'slug', 'description', 'logo', 'organizer',
        'location', 'event_date', 'event_end_date', 'category',
        'is_active', 'is_public', 'meta',
    ];

    protected $casts = [
        'event_date'     => 'date',
        'event_end_date' => 'date',
        'is_active'      => 'boolean',
        'is_public'      => 'boolean',
        'meta'           => 'array',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function certificates(): HasMany
    {
        return $this->hasMany(Certificate::class);
    }

    public function imports(): HasMany
    {
        return $this->hasMany(Import::class);
    }

    /** Hitung total sertifikat aktif dalam event ini */
    public function activeCertificatesCount(): int
    {
        return $this->certificates()->where('status', 'active')->count();
    }

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($event) {
            if (empty($event->slug)) {
                $event->slug = \Str::slug($event->name) . '-' . uniqid();
            }
        });
    }
}

// ============================================================
// CertificateTemplate.php
// ============================================================
class CertificateTemplate extends Model
{
    use SoftDeletes;

    protected $table = 'certificate_templates';

    protected $fillable = [
        'created_by', 'name', 'description', 'category', 'orientation',
        'width_px', 'height_px', 'background_file', 'background_type',
        'fields_config', 'signature_config', 'is_active', 'is_system',
        'usage_count', 'thumbnail',
    ];

    protected $casts = [
        'fields_config'    => 'array',
        'signature_config' => 'array',
        'is_active'        => 'boolean',
        'is_system'        => 'boolean',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function certificates(): HasMany
    {
        return $this->hasMany(Certificate::class, 'template_id');
    }

    public function incrementUsage(): void
    {
        $this->increment('usage_count');
    }
}

// ============================================================
// Certificate.php — model utama sertifikat
// ============================================================
class Certificate extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'event_id', 'template_id', 'user_id', 'issued_by', 'import_id',
        'recipient_name', 'recipient_email', 'recipient_institution', 'recipient_identity_number',
        'certificate_number', 'verification_uuid', 'verification_hash', 'verification_token',
        'status', 'revoke_reason', 'revoked_at', 'revoked_by',
        'pdf_file', 'image_file',
        'email_sent', 'email_sent_at', 'whatsapp_sent', 'whatsapp_sent_at',
        'download_count', 'verification_count', 'last_downloaded_at', 'issued_at',
    ];

    protected $casts = [
        'email_sent'      => 'boolean',
        'whatsapp_sent'   => 'boolean',
        'email_sent_at'   => 'datetime',
        'whatsapp_sent_at'=> 'datetime',
        'revoked_at'      => 'datetime',
        'last_downloaded_at' => 'datetime',
        'issued_at'       => 'datetime',
    ];

    // -----------------------------------------------------------------
    // RELASI
    // -----------------------------------------------------------------

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(CertificateTemplate::class, 'template_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function issuer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by');
    }

    public function revoker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'revoked_by');
    }

    public function import(): BelongsTo
    {
        return $this->belongsTo(Import::class);
    }

    public function verificationLogs(): HasMany
    {
        return $this->hasMany(VerificationLog::class);
    }

    public function certificateLogs(): HasMany
    {
        return $this->hasMany(CertificateLog::class);
    }

    public function signatures(): BelongsToMany
    {
        return $this->belongsToMany(Signature::class, 'certificate_signatures')
            ->withPivot(['order', 'position'])
            ->orderBy('order');
    }

    // -----------------------------------------------------------------
    // HELPERS
    // -----------------------------------------------------------------

    /** URL publik untuk verifikasi sertifikat */
    public function getVerificationUrlAttribute(): string
    {
        $baseUrl = \DB::table('settings')
            ->where('key', 'cert_verify_base_url')
            ->value('value') ?? url('/verify');

        return "{$baseUrl}/{$this->verification_uuid}";
    }

    /** Apakah sertifikat masih berlaku (active) */
    public function isValid(): bool
    {
        return $this->status === 'active';
    }

    /** Mencatat satu verifikasi ke tabel verification_logs */
    public function recordVerification(string $method, string $ip, ?string $userAgent = null): void
    {
        $this->verificationLogs()->create([
            'method'      => $method,
            'ip_address'  => $ip,
            'user_agent'  => $userAgent,
            'result'      => $this->isValid() ? 'valid' : $this->status,
            'verified_at' => now(),
        ]);
        $this->increment('verification_count');
    }

    /** Mencatat unduhan sertifikat */
    public function recordDownload(): void
    {
        $this->increment('download_count');
        $this->update(['last_downloaded_at' => now()]);
    }
}

// ============================================================
// Signature.php
// ============================================================
class Signature extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id', 'event_id', 'name', 'title',
        'signature_file', 'stamp_file', 'is_default',
    ];

    protected $casts = ['is_default' => 'boolean'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }
}

// ============================================================
// Import.php
// ============================================================
class Import extends Model
{
    protected $fillable = [
        'user_id', 'event_id', 'original_filename', 'stored_filename',
        'status', 'total_rows', 'success_rows', 'failed_rows',
        'error_report', 'started_at', 'completed_at',
    ];

    protected $casts = [
        'error_report' => 'array',
        'started_at'   => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function certificates(): HasMany
    {
        return $this->hasMany(Certificate::class);
    }
}

// ============================================================
// VerificationLog.php
// ============================================================
class VerificationLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'certificate_id', 'method', 'ip_address', 'user_agent',
        'device_type', 'country', 'city', 'result', 'verified_at',
    ];

    protected $casts = ['verified_at' => 'datetime'];

    public function certificate(): BelongsTo
    {
        return $this->belongsTo(Certificate::class);
    }
}

// ============================================================
// CertificateLog.php
// ============================================================
class CertificateLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'certificate_id', 'user_id', 'action', 'notes', 'metadata', 'created_at',
    ];

    protected $casts = [
        'metadata'   => 'array',
        'created_at' => 'datetime',
    ];

    public function certificate(): BelongsTo
    {
        return $this->belongsTo(Certificate::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

// ============================================================
// LoginHistory.php
// ============================================================
class LoginHistory extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id', 'ip_address', 'user_agent', 'status',
        'failure_reason', 'logged_in_at', 'logged_out_at',
    ];

    protected $casts = [
        'logged_in_at'  => 'datetime',
        'logged_out_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

// ============================================================
// ActivityLog.php
// ============================================================
class ActivityLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id', 'action', 'module', 'subject_id', 'subject_type',
        'old_values', 'new_values', 'ip_address', 'user_agent', 'description', 'created_at',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'created_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Helper statis untuk mencatat aktivitas dengan mudah dari mana saja */
    public static function record(
        string $action,
        string $module,
        ?int $subjectId = null,
        string $subjectType = null,
        array $oldValues = [],
        array $newValues = [],
        string $description = null
    ): void {
        static::create([
            'user_id'      => auth()->id(),
            'action'       => $action,
            'module'       => $module,
            'subject_id'   => $subjectId,
            'subject_type' => $subjectType,
            'old_values'   => $oldValues ?: null,
            'new_values'   => $newValues ?: null,
            'ip_address'   => request()->ip(),
            'user_agent'   => request()->userAgent(),
            'description'  => $description,
            'created_at'   => now(),
        ]);
    }
}

// ============================================================
// Setting.php
// ============================================================
class Setting extends Model
{
    protected $fillable = ['key', 'value', 'type', 'group', 'label', 'description', 'is_public'];
    protected $casts = ['is_public' => 'boolean'];

    /**
     * Mendapatkan nilai setting berdasarkan key.
     * Mengembalikan $default jika key tidak ditemukan.
     */
    public static function get(string $key, $default = null): mixed
    {
        $setting = static::where('key', $key)->first();
        if (!$setting) {
            return $default;
        }

        return match ($setting->type) {
            'boolean' => (bool) $setting->value,
            'integer' => (int) $setting->value,
            'json'    => json_decode($setting->value, true),
            default   => $setting->value,
        };
    }

    /** Menyimpan nilai setting */
    public static function set(string $key, $value): void
    {
        static::updateOrCreate(
            ['key' => $key],
            ['value' => is_array($value) ? json_encode($value) : (string) $value]
        );
    }
}
