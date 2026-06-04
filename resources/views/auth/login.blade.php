{{-- resources/views/auth/login.blade.php --}}
<!DOCTYPE html>
<html lang="id" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — DCMS</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Instrument+Serif:ital@0;1&family=DM+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --p: #1a237e;
            --p-lt: #3949ab;
            --accent: #00bcd4;
        }
        * { box-sizing: border-box; }
        body {
            font-family: 'DM Sans', sans-serif;
            min-height: 100vh;
            display: flex;
            background: #f0f2fa;
            margin: 0;
        }

        /* ── Left Panel ──────────────────────────────────── */
        .auth-left {
            flex: 1;
            background: linear-gradient(145deg, #0d1b4b 0%, #1a237e 60%, #1565c0 100%);
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: flex-start;
            padding: 3rem 3.5rem;
            position: relative;
            overflow: hidden;
        }
        .auth-left::before {
            content: '';
            position: absolute;
            top: -80px; right: -80px;
            width: 320px; height: 320px;
            border-radius: 50%;
            background: rgba(0,188,212,0.12);
        }
        .auth-left::after {
            content: '';
            position: absolute;
            bottom: -60px; left: -60px;
            width: 240px; height: 240px;
            border-radius: 50%;
            background: rgba(255,255,255,0.05);
        }
        .auth-logo {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 3rem;
        }
        .auth-logo-icon {
            width: 44px; height: 44px;
            background: var(--accent);
            border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            font-size: 22px;
            color: #fff;
        }
        .auth-logo-text {
            color: #fff;
            font-family: 'Instrument Serif', serif;
            font-size: 1.3rem;
            line-height: 1.2;
        }
        .auth-logo-text small {
            display: block;
            font-family: 'DM Sans', sans-serif;
            font-size: 0.65rem;
            opacity: 0.5;
            font-style: normal;
        }
        .auth-headline {
            color: #fff;
            font-family: 'Instrument Serif', serif;
            font-size: 2.4rem;
            line-height: 1.25;
            margin-bottom: 1rem;
            position: relative;
            z-index: 1;
        }
        .auth-desc {
            color: rgba(255,255,255,0.65);
            font-size: 0.9rem;
            line-height: 1.7;
            max-width: 380px;
            position: relative;
            z-index: 1;
        }
        .auth-features {
            margin-top: 2.5rem;
            display: flex;
            flex-direction: column;
            gap: 12px;
            position: relative;
            z-index: 1;
        }
        .auth-feature-item {
            display: flex;
            align-items: center;
            gap: 10px;
            color: rgba(255,255,255,0.8);
            font-size: 0.85rem;
        }
        .auth-feature-icon {
            width: 30px; height: 30px;
            border-radius: 8px;
            background: rgba(255,255,255,0.1);
            display: flex; align-items: center; justify-content: center;
            font-size: 0.875rem;
            flex-shrink: 0;
        }

        /* ── Right Panel ─────────────────────────────────── */
        .auth-right {
            width: 480px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 2.5rem 3rem;
            background: #fff;
            box-shadow: -4px 0 40px rgba(26,35,126,0.08);
        }
        .auth-form-title {
            font-family: 'Instrument Serif', serif;
            font-size: 1.75rem;
            color: var(--p);
            margin-bottom: 0.35rem;
        }
        .auth-form-sub {
            font-size: 0.85rem;
            color: #94a3b8;
            margin-bottom: 2rem;
        }

        /* Input styling */
        .form-floating > .form-control {
            border-radius: 10px;
            border: 1.5px solid #e2e8f0;
            font-size: 0.9rem;
            transition: border-color 0.2s, box-shadow 0.2s;
        }
        .form-floating > .form-control:focus {
            border-color: var(--p-lt);
            box-shadow: 0 0 0 3px rgba(57,73,171,0.1);
        }
        .form-floating > label {
            font-size: 0.875rem;
            color: #94a3b8;
        }
        .input-icon-wrap {
            position: relative;
        }
        .input-icon-wrap .bi {
            position: absolute;
            right: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            cursor: pointer;
            z-index: 5;
            font-size: 1rem;
        }

        /* Submit button */
        .btn-auth {
            background: linear-gradient(135deg, var(--p), var(--p-lt));
            color: #fff;
            border: none;
            border-radius: 10px;
            padding: 12px;
            font-size: 0.9rem;
            font-weight: 600;
            width: 100%;
            transition: transform 0.15s, box-shadow 0.15s;
        }
        .btn-auth:hover {
            transform: translateY(-1px);
            box-shadow: 0 8px 20px rgba(26,35,126,0.25);
            color: #fff;
        }
        .btn-auth:active { transform: translateY(0); }

        /* Responsive */
        @media (max-width: 900px) {
            .auth-left { display: none; }
            .auth-right { width: 100%; padding: 2rem 1.5rem; }
        }
    </style>
</head>
<body>

{{-- LEFT PANEL --}}
<div class="auth-left d-none d-md-flex">
    <div class="auth-logo">
        <div class="auth-logo-icon"><i class="bi bi-award-fill"></i></div>
        <div class="auth-logo-text">
            DCMS
            <small>Digital Certificate Management System</small>
        </div>
    </div>

    <h1 class="auth-headline">Kelola Sertifikat<br><em>Digital</em> dengan Mudah</h1>

    <p class="auth-desc">
        Platform terpusat untuk menerbitkan, mendistribusikan, dan memverifikasi
        sertifikat digital dengan keamanan tinggi dan antarmuka yang intuitif.
    </p>

    <div class="auth-features">
        <div class="auth-feature-item">
            <div class="auth-feature-icon"><i class="bi bi-qr-code-scan"></i></div>
            Verifikasi QR Code real-time
        </div>
        <div class="auth-feature-item">
            <div class="auth-feature-icon"><i class="bi bi-file-earmark-excel-fill"></i></div>
            Import massal via Excel
        </div>
        <div class="auth-feature-item">
            <div class="auth-feature-icon"><i class="bi bi-shield-lock-fill"></i></div>
            Keamanan data terenkripsi
        </div>
        <div class="auth-feature-item">
            <div class="auth-feature-icon"><i class="bi bi-envelope-fill"></i></div>
            Kirim otomatis via email
        </div>
    </div>
</div>

{{-- RIGHT PANEL --}}
<div class="auth-right">
    {{-- Mobile logo --}}
    <div class="d-flex align-items-center gap-2 mb-4 d-md-none">
        <div style="width:36px; height:36px; background:var(--accent); border-radius:9px; display:flex; align-items:center; justify-content:center; color:#fff; font-size:18px;">
            <i class="bi bi-award-fill"></i>
        </div>
        <div style="font-family:'Instrument Serif', serif; font-size:1.1rem; color:var(--p);">DCMS</div>
    </div>

    <h2 class="auth-form-title">Selamat Datang</h2>
    <p class="auth-form-sub">Masuk ke akun Anda untuk melanjutkan</p>

    {{-- Error / Success Messages --}}
    @if(session('success'))
        <div class="alert alert-success d-flex gap-2 align-items-center mb-3" style="border-radius:10px; font-size:0.85rem;">
            <i class="bi bi-check-circle-fill"></i> {{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger d-flex gap-2 align-items-start mb-3" style="border-radius:10px; font-size:0.85rem;">
            <i class="bi bi-exclamation-circle-fill mt-1"></i>
            <div>
                @foreach($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Form Login --}}
    <form action="{{ route('login.post') }}" method="POST">
        @csrf

        {{-- Email --}}
        <div class="mb-3">
            <div class="form-floating">
                <input type="email" name="email" id="email" class="form-control @error('email') is-invalid @enderror"
                       placeholder="email@domain.com" value="{{ old('email') }}" required autocomplete="email">
                <label for="email"><i class="bi bi-envelope me-2"></i>Alamat Email</label>
            </div>
        </div>

        {{-- Password --}}
        <div class="mb-3">
            <div class="form-floating input-icon-wrap">
                <input type="password" name="password" id="password"
                       class="form-control @error('password') is-invalid @enderror"
                       placeholder="Password" required autocomplete="current-password">
                <label for="password"><i class="bi bi-lock me-2"></i>Password</label>
                <i class="bi bi-eye-slash" id="togglePassword"></i>
            </div>
        </div>

        {{-- Remember + Forgot --}}
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div class="form-check mb-0">
                <input type="checkbox" name="remember" id="remember" class="form-check-input">
                <label for="remember" class="form-check-label" style="font-size:0.85rem; color:#64748b;">
                    Ingat saya
                </label>
            </div>
            <a href="{{ route('password.request') }}" style="font-size:0.85rem; color:var(--p-lt); text-decoration:none;">
                Lupa password?
            </a>
        </div>

        <button type="submit" class="btn-auth">
            <i class="bi bi-box-arrow-in-right me-2"></i>Masuk
        </button>
    </form>

    {{-- Register Link --}}
    <div class="text-center mt-4" style="font-size:0.85rem; color:#94a3b8;">
        Belum punya akun?
        <a href="{{ route('register') }}" style="color:var(--p-lt); font-weight:600; text-decoration:none;">Daftar Sekarang</a>
    </div>

    {{-- Verifikasi publik --}}
    <div class="text-center mt-3">
        <a href="{{ route('verify.show', 'check') }}"
           style="font-size:0.8rem; color:#94a3b8; text-decoration:none;">
            <i class="bi bi-qr-code me-1"></i>Verifikasi sertifikat tanpa login
        </a>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Toggle password visibility
document.getElementById('togglePassword')?.addEventListener('click', function () {
    const input = document.getElementById('password');
    const isPass = input.type === 'password';
    input.type = isPass ? 'text' : 'password';
    this.className = isPass ? 'bi bi-eye' : 'bi bi-eye-slash';
});
</script>
</body>
</html>
