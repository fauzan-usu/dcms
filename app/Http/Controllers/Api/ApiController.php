<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\{Certificate, Event, User, ApiToken};
use App\Services\CertificateService;
use Illuminate\Http\{Request, JsonResponse};
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * ApiController — REST API untuk DCMS
 *
 * Semua endpoint mengembalikan respons JSON yang konsisten menggunakan
 * format: { success: bool, data: mixed, message: string, meta: mixed }
 *
 * Autentikasi menggunakan Bearer Token (API Token model), bukan JWT library
 * eksternal — lebih sederhana dan cukup untuk kebutuhan internal.
 * Untuk implementasi JWT penuh (audience, issuer, dll), integrasikan
 * package tymon/jwt-auth sesuai kebutuhan.
 *
 * Rate limiting dikonfigurasi di RouteServiceProvider.
 */
class ApiController extends Controller
{
    public function __construct(
        private readonly CertificateService $certificateService
    ) {}

    // -----------------------------------------------------------------
    // POST /api/auth/login
    // -----------------------------------------------------------------

    /**
     * Autentikasi pengguna dan mengembalikan API token.
     * Token memiliki masa berlaku 30 hari secara default.
     */
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where('email', strtolower($request->email))
            ->where('is_active', true)
            ->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return $this->error('Kredensial tidak valid.', 401);
        }

        // Hapus token lama yang sudah expired
        $user->apiTokens()
            ->where('expires_at', '<', now())
            ->delete();

        $plainToken = Str::random(64);

        $apiToken = $user->apiTokens()->create([
            'name'       => $request->input('device_name', 'API Client'),
            'token'      => hash('sha256', $plainToken),
            'abilities'  => ['*'],
            'expires_at' => now()->addDays(30),
        ]);

        return $this->success([
            'token'      => $plainToken,
            'token_type' => 'Bearer',
            'expires_at' => $apiToken->expires_at->toIso8601String(),
            'user'       => [
                'id'   => $user->id,
                'name' => $user->name,
                'role' => $user->role->name,
            ],
        ], 'Login berhasil.');
    }

    // -----------------------------------------------------------------
    // POST /api/auth/logout
    // -----------------------------------------------------------------

    public function apiLogout(Request $request): JsonResponse
    {
        $request->user()
            ->currentAccessToken()
            ->delete();

        return $this->success(null, 'Logout berhasil.');
    }

    // -----------------------------------------------------------------
    // GET /api/events
    // -----------------------------------------------------------------

    public function events(Request $request): JsonResponse
    {
        $events = Event::where('is_active', true)
            ->where('is_public', true)
            ->withCount('certificates')
            ->latest('event_date')
            ->paginate(15);

        return $this->success($events);
    }

    // -----------------------------------------------------------------
    // POST /api/certificates/generate
    // -----------------------------------------------------------------

    /**
     * Generate sertifikat baru via API.
     * Memerlukan autentikasi dan permission certificates.create.
     */
    public function generateCertificate(Request $request): JsonResponse
    {
        $request->validate([
            'event_id'      => ['required', 'exists:events,id'],
            'template_id'   => ['required', 'exists:certificate_templates,id'],
            'recipient_name'=> ['required', 'string', 'max:200'],
            'recipient_email' => ['nullable', 'email'],
            'recipient_institution' => ['nullable', 'string'],
        ]);

        $certificate = $this->certificateService->createCertificate(
            $request->validated(),
            $request->user()->id
        );

        // Generate PDF secara asinkron
        dispatch(new \App\Jobs\GenerateCertificatePdfJob(
            $request->validated(),
            $request->user()->id
        ));

        return $this->success([
            'id'                 => $certificate->id,
            'certificate_number' => $certificate->certificate_number,
            'verification_uuid'  => $certificate->verification_uuid,
            'verification_url'   => $certificate->verification_url,
            'recipient_name'     => $certificate->recipient_name,
            'issued_at'          => $certificate->issued_at->toIso8601String(),
        ], 'Sertifikat berhasil dibuat.', 201);
    }

    // -----------------------------------------------------------------
    // GET /api/certificates/verify/{uuid}
    // -----------------------------------------------------------------

    /**
     * Verifikasi keaslian sertifikat — endpoint publik (tanpa auth).
     */
    public function verifyCertificate(string $uuid, Request $request): JsonResponse
    {
        $result = $this->certificateService->verify(
            $uuid,
            $request->ip(),
            $request->userAgent()
        );

        if ($result['status'] === 'invalid') {
            return $this->error('Sertifikat tidak ditemukan.', 404);
        }

        $cert = $result['certificate'];

        return $this->success([
            'status'             => $result['status'],
            'certificate_number' => $cert->certificate_number,
            'recipient_name'     => $cert->recipient_name,
            'recipient_institution' => $cert->recipient_institution,
            'event_name'         => $cert->event?->name,
            'issued_at'          => $cert->issued_at?->toIso8601String(),
            'revoke_reason'      => $cert->revoke_reason,
        ], 'Verifikasi selesai.');
    }

    // -----------------------------------------------------------------
    // HELPERS RESPONS JSON
    // -----------------------------------------------------------------

    private function success(mixed $data, string $message = 'OK', int $status = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data'    => $data,
            'message' => $message,
        ], $status);
    }

    private function error(string $message, int $status = 400, mixed $errors = null): JsonResponse
    {
        return response()->json([
            'success' => false,
            'data'    => null,
            'message' => $message,
            'errors'  => $errors,
        ], $status);
    }
}

// ============================================================
// Middleware: RoleMiddleware.php
// ============================================================
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * RoleMiddleware
 *
 * Memeriksa apakah pengguna yang sudah login memiliki role atau permission
 * yang diizinkan untuk mengakses route tertentu.
 *
 * Penggunaan di routes:
 *   ->middleware('role:admin,super_admin')         — cek role
 *   ->middleware('permission:certificates.create') — cek permission granular
 */
class RoleMiddleware
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (!$user) {
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
            }
            return redirect()->route('login');
        }

        if (!$user->is_active) {
            \Auth::logout();
            return redirect()->route('login')
                ->withErrors(['email' => 'Akun Anda telah dinonaktifkan.']);
        }

        // Super admin selalu lolos
        if ($user->isSuperAdmin()) {
            return $next($request);
        }

        foreach ($roles as $role) {
            if ($user->hasRole($role)) {
                return $next($request);
            }
        }

        if ($request->expectsJson()) {
            return response()->json(['success' => false, 'message' => 'Forbidden'], 403);
        }

        abort(403, 'Anda tidak memiliki akses ke halaman ini.');
    }
}

// ============================================================
// Middleware: PermissionMiddleware.php
// ============================================================
namespace App\Http\Middleware;

class PermissionMiddleware
{
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $user = $request->user();

        if (!$user) {
            abort(401);
        }

        if (!$user->hasPermission($permission)) {
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'Permission ditolak.'], 403);
            }
            abort(403, "Permission '{$permission}' diperlukan untuk akses ini.");
        }

        return $next($request);
    }
}

// ============================================================
// Middleware: SessionTimeoutMiddleware.php
// ============================================================
namespace App\Http\Middleware;

use App\Models\Setting;

class SessionTimeoutMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (\Auth::check()) {
            $timeoutMinutes = (int) Setting::get('session_timeout_minutes', 120);
            $lastActivity   = session('last_activity_at');

            if ($lastActivity && now()->diffInMinutes($lastActivity) >= $timeoutMinutes) {
                \Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect()->route('login')
                    ->withErrors(['session' => 'Sesi Anda telah berakhir karena tidak aktif. Silakan login kembali.']);
            }

            session(['last_activity_at' => now()]);
        }

        return $next($request);
    }
}

// ============================================================
// Middleware: ApiAuthMiddleware.php
// ============================================================
namespace App\Http\Middleware;

use App\Models\{User, ApiToken};
use Illuminate\Support\Facades\Auth;

class ApiAuthMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $bearerToken = $request->bearerToken();

        if (!$bearerToken) {
            return response()->json(['success' => false, 'message' => 'Token tidak ditemukan.'], 401);
        }

        $hashedToken = hash('sha256', $bearerToken);

        $apiToken = ApiToken::with('user')
            ->where('token', $hashedToken)
            ->where(function ($q) {
                $q->whereNull('expires_at')
                  ->orWhere('expires_at', '>', now());
            })
            ->first();

        if (!$apiToken || !$apiToken->user?->is_active) {
            return response()->json(['success' => false, 'message' => 'Token tidak valid atau sudah kadaluarsa.'], 401);
        }

        $apiToken->update(['last_used_at' => now()]);

        // Set user ke request agar bisa diakses via $request->user()
        Auth::setUser($apiToken->user);
        $request->setUserResolver(fn() => $apiToken->user);

        // Simpan token saat ini ke request untuk logout
        $request->merge(['_current_token' => $apiToken]);

        return $next($request);
    }
}
