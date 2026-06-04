<!DOCTYPE html>
<html lang="id" data-bs-theme="{{ session('theme', 'light') }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="Digital Certificate Management System — {{ config('app.name') }}">
    <title>@yield('title', 'Dashboard') — DCMS</title>

    {{-- Bootstrap 5 --}}
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    {{-- DataTables --}}
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css">

    {{-- Google Fonts: Instrument Serif (display) + DM Sans (body) --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Instrument+Serif:ital@0;1&family=DM+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
        /* ── CSS Variables ─────────────────────────────────── */
        :root {
            --dcms-primary:     #1a237e;
            --dcms-primary-lt:  #3949ab;
            --dcms-primary-bg:  #e8eaf6;
            --dcms-accent:      #00bcd4;
            --dcms-success:     #2e7d32;
            --dcms-warning:     #f57f17;
            --dcms-danger:      #c62828;
            --dcms-sidebar-w:   260px;
            --dcms-sidebar-bg:  #0d1b4b;
            --dcms-sidebar-txt: rgba(255,255,255,0.85);
            --dcms-topbar-h:    60px;
            --dcms-radius:      12px;
            --dcms-shadow:      0 2px 16px rgba(26,35,126,0.10);
            --dcms-font-body:   'DM Sans', sans-serif;
            --dcms-font-display:'Instrument Serif', serif;
        }
        [data-bs-theme="dark"] {
            --dcms-sidebar-bg:  #0a1128;
            --dcms-primary-bg:  #1c2757;
        }

        /* ── Reset / Base ──────────────────────────────────── */
        * { box-sizing: border-box; }
        body {
            font-family: var(--dcms-font-body);
            background: #f5f6fa;
            overflow-x: hidden;
        }
        [data-bs-theme="dark"] body { background: #111827; }

        /* ── Sidebar ───────────────────────────────────────── */
        #dcms-sidebar {
            position: fixed;
            top: 0; left: 0; bottom: 0;
            width: var(--dcms-sidebar-w);
            background: var(--dcms-sidebar-bg);
            z-index: 1050;
            overflow-y: auto;
            overflow-x: hidden;
            transition: width 0.3s cubic-bezier(.4,0,.2,1);
            scrollbar-width: thin;
            scrollbar-color: rgba(255,255,255,0.15) transparent;
        }
        #dcms-sidebar.collapsed { width: 68px; }
        #dcms-sidebar.collapsed .sidebar-label,
        #dcms-sidebar.collapsed .sidebar-section-title,
        #dcms-sidebar.collapsed .sidebar-brand-text { display: none; }
        #dcms-sidebar.collapsed .nav-link { justify-content: center; padding: 10px 0; }

        .sidebar-brand {
            height: var(--dcms-topbar-h);
            display: flex;
            align-items: center;
            padding: 0 1.25rem;
            border-bottom: 1px solid rgba(255,255,255,0.08);
            text-decoration: none;
            gap: 10px;
        }
        .sidebar-brand-icon {
            width: 34px; height: 34px;
            background: var(--dcms-accent);
            border-radius: 8px;
            display: flex; align-items: center; justify-content: center;
            flex-shrink: 0;
            color: #fff;
            font-size: 18px;
        }
        .sidebar-brand-text {
            color: #fff;
            font-family: var(--dcms-font-display);
            font-size: 1.05rem;
            line-height: 1.2;
        }
        .sidebar-brand-text small {
            display: block;
            font-family: var(--dcms-font-body);
            font-size: 0.65rem;
            opacity: 0.55;
            font-style: normal;
        }

        .sidebar-section-title {
            font-size: 0.65rem;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            color: rgba(255,255,255,0.35);
            padding: 1.25rem 1.25rem 0.4rem;
            font-weight: 600;
        }

        #dcms-sidebar .nav-link {
            color: var(--dcms-sidebar-txt);
            border-radius: 8px;
            margin: 2px 10px;
            padding: 9px 14px;
            font-size: 0.875rem;
            font-weight: 400;
            display: flex;
            align-items: center;
            gap: 10px;
            transition: background 0.15s, color 0.15s;
        }
        #dcms-sidebar .nav-link:hover {
            background: rgba(255,255,255,0.08);
            color: #fff;
        }
        #dcms-sidebar .nav-link.active {
            background: var(--dcms-primary-lt);
            color: #fff;
            font-weight: 500;
        }
        #dcms-sidebar .nav-link i { font-size: 1rem; flex-shrink: 0; }

        /* Submenu collapse */
        #dcms-sidebar .collapse .nav-link {
            padding-left: 42px;
            font-size: 0.825rem;
        }

        /* ── Main Wrapper ──────────────────────────────────── */
        #dcms-main {
            margin-left: var(--dcms-sidebar-w);
            min-height: 100vh;
            transition: margin-left 0.3s cubic-bezier(.4,0,.2,1);
        }
        #dcms-main.sidebar-collapsed { margin-left: 68px; }

        /* ── Top Bar ───────────────────────────────────────── */
        #dcms-topbar {
            height: var(--dcms-topbar-h);
            background: #fff;
            border-bottom: 1px solid rgba(0,0,0,0.06);
            display: flex;
            align-items: center;
            padding: 0 1.5rem;
            gap: 0.75rem;
            position: sticky;
            top: 0;
            z-index: 999;
            box-shadow: var(--dcms-shadow);
        }
        [data-bs-theme="dark"] #dcms-topbar {
            background: #1e293b;
            border-bottom-color: rgba(255,255,255,0.06);
        }
        .topbar-toggle {
            width: 36px; height: 36px;
            border: none;
            background: transparent;
            border-radius: 8px;
            color: #64748b;
            font-size: 1.2rem;
            cursor: pointer;
            display: flex; align-items: center; justify-content: center;
        }
        .topbar-toggle:hover { background: #f1f5f9; }
        .topbar-breadcrumb {
            flex: 1;
            font-size: 0.8rem;
            color: #94a3b8;
        }
        .topbar-breadcrumb .current { color: var(--dcms-primary); font-weight: 600; }
        .topbar-avatar {
            width: 34px; height: 34px;
            border-radius: 50%;
            background: var(--dcms-primary);
            color: #fff;
            display: flex; align-items: center; justify-content: center;
            font-size: 0.8rem;
            font-weight: 600;
            cursor: pointer;
        }

        /* ── Page Content ──────────────────────────────────── */
        .dcms-content {
            padding: 1.75rem;
        }
        .page-header {
            margin-bottom: 1.5rem;
        }
        .page-title {
            font-family: var(--dcms-font-display);
            font-size: 1.6rem;
            color: var(--dcms-primary);
            margin-bottom: 0.25rem;
        }
        .page-subtitle {
            font-size: 0.85rem;
            color: #94a3b8;
        }

        /* ── Cards ─────────────────────────────────────────── */
        .dcms-card {
            background: #fff;
            border-radius: var(--dcms-radius);
            border: 1px solid rgba(0,0,0,0.05);
            box-shadow: var(--dcms-shadow);
            overflow: hidden;
        }
        [data-bs-theme="dark"] .dcms-card {
            background: #1e293b;
            border-color: rgba(255,255,255,0.06);
        }
        .dcms-card .card-header {
            background: transparent;
            border-bottom: 1px solid rgba(0,0,0,0.06);
            padding: 1rem 1.25rem;
            font-weight: 600;
            font-size: 0.9rem;
        }

        /* ── Stat Cards ────────────────────────────────────── */
        .stat-card {
            padding: 1.25rem 1.5rem;
            border-radius: var(--dcms-radius);
            background: #fff;
            border: 1px solid rgba(0,0,0,0.05);
            box-shadow: var(--dcms-shadow);
            display: flex;
            align-items: center;
            gap: 1rem;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(26,35,126,0.15);
        }
        [data-bs-theme="dark"] .stat-card { background: #1e293b; }

        .stat-icon {
            width: 52px; height: 52px;
            border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.4rem;
            flex-shrink: 0;
        }
        .stat-value {
            font-size: 1.75rem;
            font-weight: 700;
            line-height: 1;
            color: var(--dcms-primary);
        }
        .stat-label {
            font-size: 0.78rem;
            color: #94a3b8;
            margin-top: 3px;
        }

        /* ── Badge variants ────────────────────────────────── */
        .badge-active   { background: #dcfce7; color: #15803d; }
        .badge-revoked  { background: #fee2e2; color: #dc2626; }
        .badge-pending  { background: #fef9c3; color: #b45309; }
        .badge-expired  { background: #f1f5f9; color: #64748b; }

        /* ── DataTables override ───────────────────────────── */
        .dataTables_wrapper .dataTables_filter input {
            border-radius: 8px;
            border: 1px solid #e2e8f0;
            padding: 4px 12px;
            font-size: 0.85rem;
        }
        .dataTables_wrapper .dataTables_length select {
            border-radius: 8px;
            border: 1px solid #e2e8f0;
        }

        /* ── Toast container ───────────────────────────────── */
        #toast-container {
            position: fixed;
            bottom: 1.5rem;
            right: 1.5rem;
            z-index: 9999;
        }

        /* ── Responsive ────────────────────────────────────── */
        @media (max-width: 768px) {
            #dcms-sidebar { transform: translateX(-100%); }
            #dcms-sidebar.mobile-open { transform: translateX(0); }
            #dcms-main { margin-left: 0 !important; }
            .dcms-content { padding: 1rem; }
        }

        /* ── Loading overlay ───────────────────────────────── */
        #loading-overlay {
            position: fixed; inset: 0;
            background: rgba(255,255,255,0.8);
            z-index: 9998;
            display: none;
            align-items: center;
            justify-content: center;
        }
        #loading-overlay.show { display: flex; }
        [data-bs-theme="dark"] #loading-overlay { background: rgba(15,23,42,0.8); }
    </style>
    @stack('styles')
</head>
<body>

{{-- ═══════════════════════════════════════════════ SIDEBAR ═══════════ --}}
<nav id="dcms-sidebar" class="{{ session('sidebar_collapsed') ? 'collapsed' : '' }}">

    {{-- Brand --}}
    <a href="{{ route('admin.dashboard') }}" class="sidebar-brand">
        <div class="sidebar-brand-icon"><i class="bi bi-award-fill"></i></div>
        <div class="sidebar-brand-text">
            DCMS
            <small>Digital Certificate System</small>
        </div>
    </a>

    <div style="padding: 0.75rem 0;">

        {{-- Dashboard --}}
        <div class="sidebar-section-title">Utama</div>
        <a href="{{ route('admin.dashboard') }}"
           class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
            <i class="bi bi-grid-1x2-fill"></i>
            <span class="sidebar-label">Dashboard</span>
        </a>

        {{-- Sertifikat --}}
        <div class="sidebar-section-title">Sertifikat</div>
        <a href="{{ route('admin.certificates.index') }}"
           class="nav-link {{ request()->routeIs('admin.certificates.*') ? 'active' : '' }}">
            <i class="bi bi-patch-check-fill"></i>
            <span class="sidebar-label">Daftar Sertifikat</span>
        </a>
        <a href="{{ route('admin.certificates.create') }}"
           class="nav-link {{ request()->routeIs('admin.certificates.create') ? 'active' : '' }}">
            <i class="bi bi-plus-circle-fill"></i>
            <span class="sidebar-label">Buat Sertifikat</span>
        </a>
        <a href="{{ route('admin.imports.index') }}"
           class="nav-link {{ request()->routeIs('admin.imports.*') ? 'active' : '' }}">
            <i class="bi bi-file-earmark-excel-fill"></i>
            <span class="sidebar-label">Import Excel</span>
        </a>

        {{-- Manajemen --}}
        <div class="sidebar-section-title">Manajemen</div>
        <a href="{{ route('admin.events.index') }}"
           class="nav-link {{ request()->routeIs('admin.events.*') ? 'active' : '' }}">
            <i class="bi bi-calendar-event-fill"></i>
            <span class="sidebar-label">Kegiatan / Event</span>
        </a>
        <a href="{{ route('admin.templates.index') }}"
           class="nav-link {{ request()->routeIs('admin.templates.*') ? 'active' : '' }}">
            <i class="bi bi-layout-text-window-reverse"></i>
            <span class="sidebar-label">Template Sertifikat</span>
        </a>
        <a href="{{ route('admin.signatures.index') }}"
           class="nav-link {{ request()->routeIs('admin.signatures.*') ? 'active' : '' }}">
            <i class="bi bi-pen-fill"></i>
            <span class="sidebar-label">Tanda Tangan</span>
        </a>

        {{-- Laporan --}}
        <div class="sidebar-section-title">Laporan & Analitik</div>
        <a href="{{ route('admin.reports.certificates') }}"
           class="nav-link {{ request()->routeIs('admin.reports.*') ? 'active' : '' }}">
            <i class="bi bi-bar-chart-fill"></i>
            <span class="sidebar-label">Laporan</span>
        </a>

        @if(auth()->user()->hasPermission('users.read'))
        {{-- Pengguna --}}
        <div class="sidebar-section-title">Sistem</div>
        <a href="{{ route('admin.users.index') }}"
           class="nav-link {{ request()->routeIs('admin.users.*') ? 'active' : '' }}">
            <i class="bi bi-people-fill"></i>
            <span class="sidebar-label">Pengguna</span>
        </a>
        @endif

        @if(auth()->user()->isSuperAdmin())
        <a href="{{ route('admin.settings.index') }}"
           class="nav-link {{ request()->routeIs('admin.settings.*') ? 'active' : '' }}">
            <i class="bi bi-gear-fill"></i>
            <span class="sidebar-label">Pengaturan</span>
        </a>
        @endif

        {{-- Verifikasi Publik --}}
        <div class="sidebar-section-title">Publik</div>
        <a href="{{ route('verify.show', 'demo') }}" target="_blank"
           class="nav-link">
            <i class="bi bi-shield-check"></i>
            <span class="sidebar-label">Halaman Verifikasi</span>
        </a>

        {{-- Logout --}}
        <div style="margin: 1rem 10px 0.5rem; border-top: 1px solid rgba(255,255,255,0.08); padding-top: 0.75rem;">
            <form action="{{ route('logout') }}" method="POST">
                @csrf
                <button type="submit" class="nav-link w-100 text-start"
                        style="border: none; background: none; color: var(--dcms-sidebar-txt); cursor: pointer;">
                    <i class="bi bi-box-arrow-right"></i>
                    <span class="sidebar-label">Logout</span>
                </button>
            </form>
        </div>
    </div>
</nav>

{{-- ══════════════════════════════════════════════ MAIN AREA ═════════ --}}
<div id="dcms-main" class="{{ session('sidebar_collapsed') ? 'sidebar-collapsed' : '' }}">

    {{-- ─── TOP BAR ─────────────────────────────────────── --}}
    <div id="dcms-topbar">
        <button class="topbar-toggle" id="sidebar-toggle" title="Toggle Sidebar">
            <i class="bi bi-list"></i>
        </button>

        {{-- Breadcrumb --}}
        <div class="topbar-breadcrumb">
            @yield('breadcrumb', '<span class="current">Dashboard</span>')
        </div>

        {{-- Dark Mode Toggle --}}
        <button class="topbar-toggle" id="theme-toggle" title="Toggle Dark Mode">
            <i class="bi bi-moon-stars-fill"></i>
        </button>

        {{-- Notifikasi --}}
        <div class="dropdown">
            <button class="topbar-toggle position-relative" data-bs-toggle="dropdown">
                <i class="bi bi-bell-fill"></i>
                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger"
                      style="font-size: 0.5rem; padding: 2px 4px;">3</span>
            </button>
            <ul class="dropdown-menu dropdown-menu-end shadow-sm" style="min-width: 280px;">
                <li><h6 class="dropdown-header">Notifikasi Terbaru</h6></li>
                <li><a class="dropdown-item" href="#">
                    <div class="d-flex gap-2 align-items-start py-1">
                        <i class="bi bi-check-circle-fill text-success mt-1"></i>
                        <div>
                            <div style="font-size:0.825rem;">Import Excel selesai</div>
                            <div style="font-size:0.75rem; color:#94a3b8;">2 menit lalu</div>
                        </div>
                    </div>
                </a></li>
                <li><hr class="dropdown-divider my-1"></li>
                <li><a class="dropdown-item text-center" href="#" style="font-size:0.8rem;">Lihat semua</a></li>
            </ul>
        </div>

        {{-- User Profile --}}
        <div class="dropdown">
            <div class="topbar-avatar" data-bs-toggle="dropdown">
                {{ strtoupper(substr(auth()->user()->name, 0, 2)) }}
            </div>
            <ul class="dropdown-menu dropdown-menu-end shadow-sm" style="min-width: 200px;">
                <li>
                    <div class="px-3 py-2">
                        <div style="font-weight:600; font-size:0.875rem;">{{ auth()->user()->name }}</div>
                        <div style="font-size:0.75rem; color:#94a3b8;">{{ auth()->user()->role->display_name }}</div>
                    </div>
                </li>
                <li><hr class="dropdown-divider my-1"></li>
                <li><a class="dropdown-item" href="#"><i class="bi bi-person me-2"></i>Profil Saya</a></li>
                <li><a class="dropdown-item" href="#"><i class="bi bi-shield-lock me-2"></i>Keamanan</a></li>
                <li><hr class="dropdown-divider my-1"></li>
                <li>
                    <form action="{{ route('logout') }}" method="POST">
                        @csrf
                        <button type="submit" class="dropdown-item text-danger">
                            <i class="bi bi-box-arrow-right me-2"></i>Logout
                        </button>
                    </form>
                </li>
            </ul>
        </div>
    </div>

    {{-- ─── FLASH MESSAGES ──────────────────────────────── --}}
    @if(session('success') || session('error') || $errors->any())
    <div class="dcms-content pb-0">
        @if(session('success'))
            <div class="alert alert-success d-flex align-items-center gap-2 alert-dismissible fade show">
                <i class="bi bi-check-circle-fill"></i>
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger d-flex align-items-center gap-2 alert-dismissible fade show">
                <i class="bi bi-exclamation-triangle-fill"></i>
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif
        @if($errors->any())
            <div class="alert alert-warning alert-dismissible fade show">
                <i class="bi bi-exclamation-circle-fill me-2"></i>
                <strong>Terdapat kesalahan:</strong>
                <ul class="mb-0 mt-1">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif
    </div>
    @endif

    {{-- ─── KONTEN UTAMA ─────────────────────────────────── --}}
    <div class="dcms-content">
        @yield('content')
    </div>
</div>

{{-- Loading overlay --}}
<div id="loading-overlay">
    <div class="text-center">
        <div class="spinner-border" style="color:var(--dcms-primary); width:48px; height:48px;"></div>
        <div class="mt-3 fw-600" style="color:var(--dcms-primary);">Memproses...</div>
    </div>
</div>

{{-- Toast container --}}
<div id="toast-container" aria-live="polite" aria-atomic="true"></div>

{{-- ══════════════════════════════════════════════════ SCRIPTS ════════ --}}
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.2/dist/chart.umd.min.js"></script>

<script>
// ── Global AJAX setup: kirim CSRF token di setiap request
$.ajaxSetup({
    headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }
});

// ── Sidebar toggle
const sidebar    = document.getElementById('dcms-sidebar');
const mainArea   = document.getElementById('dcms-main');
const toggleBtn  = document.getElementById('sidebar-toggle');

toggleBtn?.addEventListener('click', function () {
    const isMobile = window.innerWidth < 768;
    if (isMobile) {
        sidebar.classList.toggle('mobile-open');
    } else {
        sidebar.classList.toggle('collapsed');
        mainArea.classList.toggle('sidebar-collapsed');
        // Simpan state ke session via AJAX
        fetch('/admin/ui/sidebar-state', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Content-Type': 'application/json' },
            body: JSON.stringify({ collapsed: sidebar.classList.contains('collapsed') }),
        }).catch(() => {});
    }
});

// ── Dark mode toggle
const themeToggle = document.getElementById('theme-toggle');
themeToggle?.addEventListener('click', function () {
    const html = document.documentElement;
    const isDark = html.getAttribute('data-bs-theme') === 'dark';
    html.setAttribute('data-bs-theme', isDark ? 'light' : 'dark');
    this.querySelector('i').className = isDark ? 'bi bi-moon-stars-fill' : 'bi bi-sun-fill';
    // Simpan preferensi ke server
    fetch('/admin/ui/theme', {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Content-Type': 'application/json' },
        body: JSON.stringify({ theme: isDark ? 'light' : 'dark' }),
    }).catch(() => {});
});

// ── Toast helper — dapat dipanggil dari mana saja: showToast('Pesan', 'success')
function showToast(message, type = 'success') {
    const icons = { success: 'check-circle-fill', danger: 'x-circle-fill', warning: 'exclamation-triangle-fill', info: 'info-circle-fill' };
    const colors = { success: '#15803d', danger: '#dc2626', warning: '#b45309', info: '#1a237e' };
    const toastEl = document.createElement('div');
    toastEl.className = 'toast align-items-center border-0 show mb-2';
    toastEl.style.cssText = `background:#fff; box-shadow:0 4px 20px rgba(0,0,0,0.12); border-radius:10px; min-width:280px;`;
    toastEl.innerHTML = `
        <div class="d-flex align-items-center gap-2 p-3">
            <i class="bi bi-${icons[type]}" style="color:${colors[type]}; font-size:1.1rem;"></i>
            <div style="font-size:0.875rem; flex:1;">${message}</div>
            <button type="button" class="btn-close btn-sm" data-bs-dismiss="toast"></button>
        </div>`;
    document.getElementById('toast-container').appendChild(toastEl);
    setTimeout(() => toastEl.remove(), 5000);
}

// ── SweetAlert2 konfigurasi default bahasa Indonesia
const SwalID = Swal.mixin({
    confirmButtonText: 'Ya, Lanjutkan',
    cancelButtonText: 'Batal',
    confirmButtonColor: '#1a237e',
    cancelButtonColor: '#94a3b8',
    reverseButtons: true,
});

// ── Konfirmasi hapus / revoke generik
document.querySelectorAll('[data-confirm]').forEach(function(el) {
    el.addEventListener('click', async function (e) {
        e.preventDefault();
        const result = await SwalID.fire({
            title: this.dataset.confirmTitle || 'Konfirmasi',
            text: this.dataset.confirm,
            icon: this.dataset.confirmIcon || 'warning',
            showCancelButton: true,
        });
        if (result.isConfirmed) {
            const form = document.getElementById(this.dataset.confirmForm);
            if (form) form.submit();
            else if (this.href) window.location.href = this.href;
        }
    });
});

// ── Loading overlay saat form submit
document.querySelectorAll('form[data-loading]').forEach(function(form) {
    form.addEventListener('submit', function () {
        document.getElementById('loading-overlay').classList.add('show');
    });
});
</script>

@stack('scripts')
</body>
</html>
