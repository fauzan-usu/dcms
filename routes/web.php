<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Admin\{
    DashboardController,
    CertificateController,
    EventController,
    TemplateController,
    UserController,
    ImportController,
    SignatureController,
    SettingController,
    ReportController,
};
use App\Http\Controllers\User\UserDashboardController;
use App\Http\Controllers\Public\VerifyController;
use App\Http\Controllers\Api\ApiController;

// =====================================================================
// RUTE PUBLIK
// =====================================================================

// Halaman verifikasi sertifikat — dapat diakses tanpa login
Route::get('/verify/{uuid}', [VerifyController::class, 'show'])->name('verify.show');
Route::post('/verify/manual', [VerifyController::class, 'manual'])->name('verify.manual');

// Landing page / beranda publik
Route::get('/', fn() => view('public.landing'))->name('home');

// =====================================================================
// AUTENTIKASI
// =====================================================================

Route::middleware('guest')->group(function () {
    Route::get('/login',    [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login',   [AuthController::class, 'login'])->name('login.post');
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register',[AuthController::class, 'register'])->name('register.post');

    // Reset password
    Route::get('/forgot-password',  [AuthController::class, 'showForgotPassword'])->name('password.request');
    Route::post('/forgot-password', [AuthController::class, 'sendResetLink'])->name('password.email');
    Route::get('/reset-password/{token}', [AuthController::class, 'showResetPassword'])->name('password.reset');
    Route::post('/reset-password',  [AuthController::class, 'resetPassword'])->name('password.update');
});

Route::post('/logout', [AuthController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');

// =====================================================================
// AREA ADMIN — Super Admin, Admin, Operator
// =====================================================================

Route::prefix('admin')
    ->name('admin.')
    ->middleware(['auth', 'active', 'session.timeout', 'role:super_admin,admin,operator'])
    ->group(function () {

        // Dashboard
        Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

        // ─── Sertifikat ───────────────────────────────────────────
        Route::prefix('certificates')->name('certificates.')->group(function () {
            Route::get('/',                 [CertificateController::class, 'index'])->name('index');
            Route::get('/create',           [CertificateController::class, 'create'])->name('create')
                ->middleware('permission:certificates.create');
            Route::post('/',                [CertificateController::class, 'store'])->name('store')
                ->middleware('permission:certificates.create');
            Route::get('/{certificate}',    [CertificateController::class, 'show'])->name('show');
            Route::get('/{certificate}/download', [CertificateController::class, 'download'])->name('download');
            Route::post('/{certificate}/revoke', [CertificateController::class, 'revoke'])->name('revoke')
                ->middleware('permission:certificates.revoke');
            Route::post('/{certificate}/send-email', [CertificateController::class, 'sendEmail'])->name('send-email')
                ->middleware('permission:certificates.send');
            Route::get('/export',           [CertificateController::class, 'export'])->name('export')
                ->middleware('permission:certificates.export');
        });

        // ─── Import Excel ─────────────────────────────────────────
        Route::prefix('imports')->name('imports.')->group(function () {
            Route::get('/',                 [ImportController::class, 'index'])->name('index');
            Route::get('/create',           [ImportController::class, 'create'])->name('create');
            Route::post('/upload',          [ImportController::class, 'upload'])->name('upload');
            Route::get('/{import}/preview', [ImportController::class, 'preview'])->name('preview');
            Route::post('/{import}/execute',[ImportController::class, 'execute'])->name('execute');
            Route::get('/template/download',[ImportController::class, 'downloadTemplate'])->name('template.download');
        });

        // ─── Events ───────────────────────────────────────────────
        Route::resource('events', EventController::class)
            ->middleware(['permission:events.read']);

        // ─── Template Sertifikat ──────────────────────────────────
        Route::prefix('templates')->name('templates.')->group(function () {
            Route::get('/',                 [TemplateController::class, 'index'])->name('index');
            Route::get('/create',           [TemplateController::class, 'create'])->name('create');
            Route::post('/',                [TemplateController::class, 'store'])->name('store');
            Route::get('/{template}/edit',  [TemplateController::class, 'edit'])->name('edit');
            Route::put('/{template}',       [TemplateController::class, 'update'])->name('update');
            Route::delete('/{template}',    [TemplateController::class, 'destroy'])->name('destroy');
            Route::get('/{template}/preview',[TemplateController::class, 'preview'])->name('preview');
            // AJAX: simpan konfigurasi posisi field dari editor canvas
            Route::post('/{template}/save-config', [TemplateController::class, 'saveConfig'])->name('save-config');
        });

        // ─── Tanda Tangan Digital ─────────────────────────────────
        Route::resource('signatures', SignatureController::class);

        // ─── Pengguna (Super Admin & Admin) ───────────────────────
        Route::prefix('users')->name('users.')->group(function () {
            Route::get('/',                 [UserController::class, 'index'])->name('index')
                ->middleware('permission:users.read');
            Route::get('/create',           [UserController::class, 'create'])->name('create')
                ->middleware('permission:users.create');
            Route::post('/',                [UserController::class, 'store'])->name('store')
                ->middleware('permission:users.create');
            Route::get('/{user}/edit',      [UserController::class, 'edit'])->name('edit')
                ->middleware('permission:users.update');
            Route::put('/{user}',           [UserController::class, 'update'])->name('update')
                ->middleware('permission:users.update');
            Route::delete('/{user}',        [UserController::class, 'destroy'])->name('destroy')
                ->middleware('permission:users.delete');
            Route::patch('/{user}/toggle',  [UserController::class, 'toggle'])->name('toggle')
                ->middleware('permission:users.update');
        });

        // ─── Laporan ──────────────────────────────────────────────
        Route::prefix('reports')->name('reports.')->middleware('permission:reports.read')->group(function () {
            Route::get('/certificates',     [ReportController::class, 'certificates'])->name('certificates');
            Route::get('/verifications',    [ReportController::class, 'verifications'])->name('verifications');
            Route::get('/events',           [ReportController::class, 'events'])->name('events');
        });

        // ─── Pengaturan (Super Admin Only) ───────────────────────
        Route::prefix('settings')->name('settings.')->middleware('role:super_admin')->group(function () {
            Route::get('/',         [SettingController::class, 'index'])->name('index');
            Route::post('/',        [SettingController::class, 'update'])->name('update');
            Route::post('/test-mail',[SettingController::class, 'testMail'])->name('test-mail');
        });
    });

// =====================================================================
// AREA USER / PESERTA
// =====================================================================

Route::prefix('dashboard')
    ->name('user.')
    ->middleware(['auth', 'active', 'session.timeout', 'role:user'])
    ->group(function () {
        Route::get('/',                       [UserDashboardController::class, 'index'])->name('dashboard');
        Route::get('/certificates',           [UserDashboardController::class, 'certificates'])->name('certificates');
        Route::get('/certificates/{certificate}/download', [UserDashboardController::class, 'download'])
            ->name('certificate.download');
    });

// =====================================================================
// REST API — prefix /api
// =====================================================================

Route::prefix('api')->name('api.')->group(function () {

    // Endpoint publik (tanpa autentikasi)
    Route::post('/auth/login', [ApiController::class, 'login'])->name('auth.login');
    Route::get('/certificates/verify/{uuid}', [ApiController::class, 'verifyCertificate'])->name('certificates.verify');
    Route::get('/events', [ApiController::class, 'events'])->name('events.index');

    // Endpoint terproteksi
    Route::middleware('api.auth')->group(function () {
        Route::post('/auth/logout', [ApiController::class, 'apiLogout'])->name('auth.logout');
        Route::post('/certificates/generate', [ApiController::class, 'generateCertificate'])->name('certificates.generate')
            ->middleware('throttle:30,1');
    });
});
