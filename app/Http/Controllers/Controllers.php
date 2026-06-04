<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\{User, Role, LoginHistory, Setting};
use Illuminate\Http\{Request, RedirectResponse};
use Illuminate\Support\Facades\{Auth, Hash, RateLimiter, Log};
use Illuminate\View\View;
use Illuminate\Validation\Rules\Password;

/**
 * AuthController
 *
 * Menangani seluruh alur autentikasi: login, register, logout,
 * lupa password, dan reset password. Menerapkan rate limiting
 * untuk mencegah brute force attack.
 */
class AuthController extends Controller
{
    public function showLogin(): View
    {
        return view('auth.login');
    }

    /**
     * Proses login dengan rate limiting dan activity logging.
     * Throttle key dibuat dari kombinasi email + IP agar per-akun
     * dan per-IP tidak bisa mencoba lebih dari batas yang dikonfigurasi.
     */
    public function login(Request $request): RedirectResponse
    {
        $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required', 'string'],
            'g-recaptcha-response' => $this->recaptchaRule(),
        ], [
            'email.required'    => 'Email wajib diisi.',
            'password.required' => 'Password wajib diisi.',
        ]);

        $throttleKey = strtolower($request->email) . '|' . $request->ip();
        $maxAttempts = (int) Setting::get('login_max_attempts', 5);
        $lockoutMin  = (int) Setting::get('login_lockout_minutes', 15);

        // Cek apakah sudah melampaui batas percobaan login
        if (RateLimiter::tooManyAttempts($throttleKey, $maxAttempts)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            $this->logLoginAttempt($request->email, $request->ip(), $request->userAgent(), 'blocked');

            return back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => "Terlalu banyak percobaan login. Coba lagi dalam {$seconds} detik."]);
        }

        // Cari user berdasarkan email
        $user = User::where('email', strtolower($request->email))
            ->where('is_active', true)
            ->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            RateLimiter::hit($throttleKey, $lockoutMin * 60);

            $this->logLoginAttempt($request->email, $request->ip(), $request->userAgent(), 'failed');

            return back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => 'Email atau password tidak sesuai.']);
        }

        // Login berhasil — hapus rate limit dan buat session
        RateLimiter::clear($throttleKey);
        Auth::login($user, $request->boolean('remember'));

        // Update informasi login terakhir
        $user->update([
            'last_login_at' => now(),
            'last_login_ip' => $request->ip(),
        ]);

        $this->logLoginAttempt($user->email, $request->ip(), $request->userAgent(), 'success', $user->id);

        return redirect()->intended(route('admin.dashboard'));
    }

    public function showRegister(): View
    {
        return view('auth.register');
    }

    /**
     * Proses pendaftaran pengguna baru.
     * Role default adalah 'user' (peserta) — hanya Super Admin yang bisa
     * mengubah role setelah akun dibuat.
     */
    public function register(Request $request): RedirectResponse
    {
        $request->validate([
            'name'        => ['required', 'string', 'max:150'],
            'email'       => ['required', 'email', 'unique:users,email', 'max:200'],
            'institution' => ['nullable', 'string', 'max:200'],
            'password'    => ['required', 'confirmed', Password::defaults()],
        ]);

        $userRole = Role::where('name', 'user')->firstOrFail();

        $user = User::create([
            'role_id'     => $userRole->id,
            'name'        => $request->name,
            'email'       => strtolower($request->email),
            'institution' => $request->institution,
            'password'    => Hash::make($request->password),
            'is_active'   => true,
        ]);

        Auth::login($user);

        return redirect()->route('user.dashboard')
            ->with('success', 'Akun berhasil dibuat. Selamat datang!');
    }

    public function logout(Request $request): RedirectResponse
    {
        // Update waktu logout di login history
        $userId = Auth::id();
        if ($userId) {
            LoginHistory::where('user_id', $userId)
                ->whereNull('logged_out_at')
                ->latest('logged_in_at')
                ->first()
                ?->update(['logged_out_at' => now()]);
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')
            ->with('success', 'Anda telah berhasil logout.');
    }

    public function showForgotPassword(): View
    {
        return view('auth.forgot-password');
    }

    /** Mengirim link reset password ke email pengguna */
    public function sendResetLink(Request $request): RedirectResponse
    {
        $request->validate(['email' => ['required', 'email']]);

        $user = User::where('email', strtolower($request->email))->first();

        // Selalu tampilkan pesan sukses meski user tidak ada — mencegah user enumeration
        if ($user) {
            $token = \Str::random(64);
            $user->update([
                'password_reset_token'      => hash('sha256', $token),
                'password_reset_expires_at' => now()->addHour(),
            ]);

            \Mail::to($user->email)->send(new \App\Mail\ResetPasswordMail($user, $token));
        }

        return back()->with('status', 'Jika email terdaftar, link reset password telah dikirim.');
    }

    public function showResetPassword(string $token): View
    {
        return view('auth.reset-password', ['token' => $token]);
    }

    /** Mengeksekusi reset password */
    public function resetPassword(Request $request): RedirectResponse
    {
        $request->validate([
            'token'    => ['required', 'string'],
            'email'    => ['required', 'email'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $hashedToken = hash('sha256', $request->token);
        $user = User::where('email', strtolower($request->email))
            ->where('password_reset_token', $hashedToken)
            ->where('password_reset_expires_at', '>', now())
            ->first();

        if (!$user) {
            return back()->withErrors(['token' => 'Token tidak valid atau sudah kadaluarsa.']);
        }

        $user->update([
            'password'                  => Hash::make($request->password),
            'password_reset_token'      => null,
            'password_reset_expires_at' => null,
        ]);

        return redirect()->route('login')
            ->with('success', 'Password berhasil direset. Silakan login dengan password baru.');
    }

    // -----------------------------------------------------------------
    // HELPERS PRIVAT
    // -----------------------------------------------------------------

    private function logLoginAttempt(
        string $email,
        string $ip,
        ?string $userAgent,
        string $status,
        ?int $userId = null
    ): void {
        // Jika user_id tidak diketahui, cari berdasarkan email
        if (!$userId) {
            $userId = User::where('email', strtolower($email))->value('id');
        }

        if ($userId) {
            LoginHistory::create([
                'user_id'        => $userId,
                'ip_address'     => $ip,
                'user_agent'     => $userAgent,
                'status'         => $status,
                'failure_reason' => $status !== 'success' ? $status : null,
                'logged_in_at'   => now(),
            ]);
        }
    }

    private function recaptchaRule(): array
    {
        if (!Setting::get('recaptcha_enabled')) {
            return [];
        }
        return ['required', 'string'];
    }
}

// ============================================================
// DashboardController.php
// ============================================================
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\{Certificate, Event, User, CertificateTemplate, VerificationLog};
use Illuminate\Support\Facades\{DB, Cache};
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Halaman utama dashboard admin.
     * Data statistik di-cache 5 menit agar tidak terlalu membebani DB
     * pada saat banyak admin membuka dashboard secara bersamaan.
     */
    public function index(): View
    {
        $stats = Cache::remember('dashboard_stats', 300, function () {
            $now = now();

            return [
                // Kartu statistik utama
                'total_certificates'     => Certificate::count(),
                'active_certificates'    => Certificate::where('status', 'active')->count(),
                'revoked_certificates'   => Certificate::where('status', 'revoked')->count(),
                'total_events'           => Event::where('is_active', true)->count(),
                'total_users'            => User::count(),
                'total_templates'        => CertificateTemplate::where('is_active', true)->count(),
                'total_verifications'    => VerificationLog::count(),
                'verifications_today'    => VerificationLog::whereDate('verified_at', $now->toDateString())->count(),

                // Data grafik: sertifikat per bulan selama 12 bulan terakhir
                'cert_per_month'         => Certificate::selectRaw(
                    "DATE_FORMAT(created_at, '%Y-%m') as month, COUNT(*) as total"
                )
                ->where('created_at', '>=', $now->copy()->subMonths(11)->startOfMonth())
                ->groupBy('month')
                ->orderBy('month')
                ->pluck('total', 'month')
                ->toArray(),

                // Distribusi sertifikat per event (top 5)
                'cert_by_event'          => Certificate::join('events', 'events.id', '=', 'certificates.event_id')
                ->selectRaw('events.name, COUNT(certificates.id) as total')
                ->groupBy('events.id', 'events.name')
                ->orderByDesc('total')
                ->limit(5)
                ->pluck('total', 'name')
                ->toArray(),

                // Aktivitas terbaru (10 sertifikat terakhir diterbitkan)
                'recent_certificates'    => Certificate::with(['event', 'issuer'])
                ->latest()
                ->limit(10)
                ->get(),

                // Verifikasi terbaru
                'recent_verifications'   => VerificationLog::with('certificate')
                ->latest('verified_at')
                ->limit(5)
                ->get(),
            ];
        });

        return view('admin.dashboard.index', compact('stats'));
    }
}

// ============================================================
// CertificateController.php
// ============================================================
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\{Certificate, Event, CertificateTemplate};
use App\Services\CertificateService;
use Illuminate\Http\{Request, RedirectResponse, JsonResponse};
use Illuminate\View\View;

class CertificateController extends Controller
{
    public function __construct(
        private readonly CertificateService $certificateService
    ) {}

    public function index(Request $request): View
    {
        $query = Certificate::with(['event', 'template', 'issuer'])
            ->latest();

        // Filter berdasarkan parameter URL
        if ($request->filled('event_id')) {
            $query->where('event_id', $request->event_id);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('recipient_name', 'LIKE', "%{$search}%")
                  ->orWhere('recipient_email', 'LIKE', "%{$search}%")
                  ->orWhere('certificate_number', 'LIKE', "%{$search}%");
            });
        }
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $certificates = $query->paginate(20)->withQueryString();
        $events       = Event::where('is_active', true)->get(['id', 'name']);

        return view('admin.certificates.index', compact('certificates', 'events'));
    }

    public function create(): View
    {
        $events    = Event::where('is_active', true)->get();
        $templates = CertificateTemplate::where('is_active', true)->get();

        return view('admin.certificates.create', compact('events', 'templates'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'event_id'                  => ['required', 'exists:events,id'],
            'template_id'               => ['required', 'exists:certificate_templates,id'],
            'recipient_name'            => ['required', 'string', 'max:200'],
            'recipient_email'           => ['nullable', 'email', 'max:200'],
            'recipient_institution'     => ['nullable', 'string', 'max:200'],
            'recipient_identity_number' => ['nullable', 'string', 'max:100'],
        ]);

        $certificate = $this->certificateService->createCertificate($validated, auth()->id());

        // Generate PDF secara sinkron untuk single input
        $this->certificateService->generatePdf($certificate);

        return redirect()->route('admin.certificates.show', $certificate)
            ->with('success', "Sertifikat #{$certificate->certificate_number} berhasil dibuat.");
    }

    public function show(Certificate $certificate): View
    {
        $certificate->load(['event', 'template', 'issuer', 'signatures', 'verificationLogs', 'certificateLogs.user']);
        return view('admin.certificates.show', compact('certificate'));
    }

    /** Revoke sertifikat dengan alasan yang jelas */
    public function revoke(Request $request, Certificate $certificate): RedirectResponse
    {
        $request->validate([
            'revoke_reason' => ['required', 'string', 'max:300'],
        ]);

        if ($certificate->status === 'revoked') {
            return back()->withErrors(['error' => 'Sertifikat ini sudah dicabut sebelumnya.']);
        }

        $this->certificateService->revoke($certificate, $request->revoke_reason, auth()->id());

        return back()->with('success', 'Sertifikat berhasil dicabut.');
    }

    /** Download ulang PDF — regenerate jika file belum ada */
    public function download(Certificate $certificate)
    {
        if (!$certificate->pdf_file || !\Storage::exists($certificate->pdf_file)) {
            $this->certificateService->generatePdf($certificate);
        }

        $certificate->recordDownload();

        return \Storage::download(
            $certificate->pdf_file,
            "Sertifikat_{$certificate->certificate_number}.pdf"
        );
    }

    /** Export data sertifikat ke Excel */
    public function export(Request $request)
    {
        $query = Certificate::with(['event'])->latest();

        if ($request->filled('event_id')) {
            $query->where('event_id', $request->event_id);
        }

        $certificates = $query->get();

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet       = $spreadsheet->getActiveSheet();

        // Header
        $headers = ['No', 'Nomor Sertifikat', 'Nama Penerima', 'Email', 'Instansi', 'Kegiatan', 'Tanggal Terbit', 'Status'];
        foreach (array_values($headers) as $i => $header) {
            $sheet->setCellValueByColumnAndRow($i + 1, 1, $header);
        }

        // Data
        foreach ($certificates as $i => $cert) {
            $row = $i + 2;
            $sheet->setCellValueByColumnAndRow(1, $row, $i + 1);
            $sheet->setCellValueByColumnAndRow(2, $row, $cert->certificate_number);
            $sheet->setCellValueByColumnAndRow(3, $row, $cert->recipient_name);
            $sheet->setCellValueByColumnAndRow(4, $row, $cert->recipient_email);
            $sheet->setCellValueByColumnAndRow(5, $row, $cert->recipient_institution);
            $sheet->setCellValueByColumnAndRow(6, $row, $cert->event?->name);
            $sheet->setCellValueByColumnAndRow(7, $row, $cert->issued_at->format('d/m/Y'));
            $sheet->setCellValueByColumnAndRow(8, $row, strtoupper($cert->status));
        }

        $filename = 'export_sertifikat_' . now()->format('Ymd_His') . '.xlsx';
        $tmpPath  = storage_path("app/tmp/{$filename}");
        @mkdir(dirname($tmpPath), 0755, true);

        $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Xlsx');
        $writer->save($tmpPath);

        return response()->download($tmpPath, $filename)->deleteFileAfterSend();
    }
}
