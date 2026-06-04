@extends('layouts.admin')

@section('title', 'Dashboard')

@section('breadcrumb')
    <span class="current">Dashboard</span>
@endsection

@push('styles')
<style>
    .chart-container { position: relative; height: 260px; }
    .activity-item {
        display: flex;
        align-items: flex-start;
        gap: 12px;
        padding: 10px 0;
        border-bottom: 1px solid rgba(0,0,0,0.04);
    }
    .activity-item:last-child { border-bottom: none; }
    .activity-dot {
        width: 32px; height: 32px;
        border-radius: 8px;
        display: flex; align-items: center; justify-content: center;
        flex-shrink: 0;
        font-size: 0.875rem;
    }
    .verify-item {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 8px 0;
        border-bottom: 1px solid rgba(0,0,0,0.04);
        font-size: 0.825rem;
    }
    .verify-item:last-child { border-bottom: none; }
</style>
@endpush

@section('content')
<div class="page-header d-flex align-items-center justify-content-between">
    <div>
        <h1 class="page-title">Selamat datang, {{ explode(' ', auth()->user()->name)[0] }} 👋</h1>
        <p class="page-subtitle">
            {{ now()->isoFormat('dddd, D MMMM YYYY') }} &mdash; Ringkasan aktivitas sistem sertifikat digital Anda.
        </p>
    </div>
    <a href="{{ route('admin.certificates.create') }}" class="btn btn-primary d-flex align-items-center gap-2"
       style="background:var(--dcms-primary); border:none; border-radius:10px; padding:9px 18px; font-size:0.875rem;">
        <i class="bi bi-plus-lg"></i> Buat Sertifikat
    </a>
</div>

{{-- ═══════════════ STAT CARDS ═══════════════════════════════════════ --}}
<div class="row g-3 mb-4">

    <div class="col-6 col-md-3">
        <div class="stat-card">
            <div class="stat-icon" style="background:#e8eaf6;">
                <i class="bi bi-patch-check-fill" style="color:var(--dcms-primary);"></i>
            </div>
            <div>
                <div class="stat-value">{{ number_format($stats['total_certificates']) }}</div>
                <div class="stat-label">Total Sertifikat</div>
            </div>
        </div>
    </div>

    <div class="col-6 col-md-3">
        <div class="stat-card">
            <div class="stat-icon" style="background:#dcfce7;">
                <i class="bi bi-check-circle-fill" style="color:#15803d;"></i>
            </div>
            <div>
                <div class="stat-value" style="color:#15803d;">{{ number_format($stats['active_certificates']) }}</div>
                <div class="stat-label">Sertifikat Aktif</div>
            </div>
        </div>
    </div>

    <div class="col-6 col-md-3">
        <div class="stat-card">
            <div class="stat-icon" style="background:#fff7ed;">
                <i class="bi bi-calendar-event-fill" style="color:#c2410c;"></i>
            </div>
            <div>
                <div class="stat-value" style="color:#c2410c;">{{ number_format($stats['total_events']) }}</div>
                <div class="stat-label">Kegiatan Aktif</div>
            </div>
        </div>
    </div>

    <div class="col-6 col-md-3">
        <div class="stat-card">
            <div class="stat-icon" style="background:#f0f9ff;">
                <i class="bi bi-shield-check" style="color:#0369a1;"></i>
            </div>
            <div>
                <div class="stat-value" style="color:#0369a1;">{{ number_format($stats['verifications_today']) }}</div>
                <div class="stat-label">Verifikasi Hari Ini</div>
            </div>
        </div>
    </div>

    <div class="col-6 col-md-3">
        <div class="stat-card">
            <div class="stat-icon" style="background:#fdf4ff;">
                <i class="bi bi-people-fill" style="color:#7e22ce;"></i>
            </div>
            <div>
                <div class="stat-value" style="color:#7e22ce;">{{ number_format($stats['total_users']) }}</div>
                <div class="stat-label">Total Pengguna</div>
            </div>
        </div>
    </div>

    <div class="col-6 col-md-3">
        <div class="stat-card">
            <div class="stat-icon" style="background:#fefce8;">
                <i class="bi bi-layout-text-window-reverse" style="color:#a16207;"></i>
            </div>
            <div>
                <div class="stat-value" style="color:#a16207;">{{ number_format($stats['total_templates']) }}</div>
                <div class="stat-label">Template Aktif</div>
            </div>
        </div>
    </div>

    <div class="col-6 col-md-3">
        <div class="stat-card">
            <div class="stat-icon" style="background:#fee2e2;">
                <i class="bi bi-x-circle-fill" style="color:#dc2626;"></i>
            </div>
            <div>
                <div class="stat-value" style="color:#dc2626;">{{ number_format($stats['revoked_certificates']) }}</div>
                <div class="stat-label">Sertifikat Dicabut</div>
            </div>
        </div>
    </div>

    <div class="col-6 col-md-3">
        <div class="stat-card">
            <div class="stat-icon" style="background:#f0fdf4;">
                <i class="bi bi-qr-code-scan" style="color:#166534;"></i>
            </div>
            <div>
                <div class="stat-value" style="color:#166534;">{{ number_format($stats['total_verifications']) }}</div>
                <div class="stat-label">Total Verifikasi</div>
            </div>
        </div>
    </div>
</div>

{{-- ═══════════════ GRAFIK + PIE CHART ═══════════════════════════════ --}}
<div class="row g-3 mb-4">

    {{-- Sertifikat per Bulan --}}
    <div class="col-12 col-lg-8">
        <div class="dcms-card h-100">
            <div class="card-header d-flex align-items-center justify-content-between">
                <span><i class="bi bi-bar-chart-fill me-2" style="color:var(--dcms-primary);"></i>Sertifikat Diterbitkan per Bulan</span>
                <span class="badge" style="background:var(--dcms-primary-bg); color:var(--dcms-primary); border-radius:6px; font-size:0.72rem;">12 Bulan Terakhir</span>
            </div>
            <div class="p-3">
                <div class="chart-container">
                    <canvas id="chartCertPerMonth"></canvas>
                </div>
            </div>
        </div>
    </div>

    {{-- Top Event --}}
    <div class="col-12 col-lg-4">
        <div class="dcms-card h-100">
            <div class="card-header">
                <i class="bi bi-pie-chart-fill me-2" style="color:var(--dcms-accent);"></i>Distribusi per Kegiatan
            </div>
            <div class="p-3">
                <div class="chart-container">
                    <canvas id="chartCertByEvent"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ═══════════════ RECENT CERTIFICATES + VERIFICATIONS ══════════════ --}}
<div class="row g-3">

    {{-- Sertifikat Terbaru --}}
    <div class="col-12 col-lg-7">
        <div class="dcms-card">
            <div class="card-header d-flex align-items-center justify-content-between">
                <span><i class="bi bi-clock-history me-2" style="color:var(--dcms-primary);"></i>Sertifikat Terbaru Diterbitkan</span>
                <a href="{{ route('admin.certificates.index') }}" class="btn btn-sm"
                   style="background:var(--dcms-primary-bg); color:var(--dcms-primary); border-radius:8px; font-size:0.78rem; padding:4px 12px;">
                    Lihat Semua
                </a>
            </div>
            <div class="p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0" style="font-size:0.825rem;">
                        <thead style="background:#f8fafc;">
                            <tr>
                                <th class="px-3 py-2 fw-600" style="color:#64748b; font-weight:600;">Penerima</th>
                                <th class="px-3 py-2 fw-600" style="color:#64748b; font-weight:600;">Kegiatan</th>
                                <th class="px-3 py-2 fw-600" style="color:#64748b; font-weight:600;">Status</th>
                                <th class="px-3 py-2 fw-600" style="color:#64748b; font-weight:600;">Tanggal</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($stats['recent_certificates'] as $cert)
                            <tr>
                                <td class="px-3 py-2">
                                    <div style="font-weight:500;">{{ $cert->recipient_name }}</div>
                                    <div style="font-size:0.75rem; color:#94a3b8;">{{ $cert->certificate_number }}</div>
                                </td>
                                <td class="px-3 py-2" style="color:#64748b;">
                                    {{ Str::limit($cert->event?->name, 30) }}
                                </td>
                                <td class="px-3 py-2">
                                    @if($cert->status === 'active')
                                        <span class="badge badge-active" style="border-radius:6px; font-size:0.72rem;">Aktif</span>
                                    @elseif($cert->status === 'revoked')
                                        <span class="badge badge-revoked" style="border-radius:6px; font-size:0.72rem;">Dicabut</span>
                                    @else
                                        <span class="badge badge-expired" style="border-radius:6px; font-size:0.72rem;">Kadaluarsa</span>
                                    @endif
                                </td>
                                <td class="px-3 py-2" style="color:#94a3b8; white-space:nowrap;">
                                    {{ $cert->issued_at?->format('d M Y') }}
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="4" class="text-center py-4" style="color:#94a3b8;">
                                    <i class="bi bi-inbox d-block mb-1" style="font-size:1.5rem;"></i>
                                    Belum ada sertifikat diterbitkan
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- Verifikasi Terbaru + Quick Verify --}}
    <div class="col-12 col-lg-5">
        <div class="row g-3">

            {{-- Quick Verify --}}
            <div class="col-12">
                <div class="dcms-card">
                    <div class="card-header">
                        <i class="bi bi-search me-2" style="color:var(--dcms-accent);"></i>Verifikasi Cepat
                    </div>
                    <div class="p-3">
                        <div class="d-flex gap-2">
                            <input type="text" id="quickVerifyInput" class="form-control form-control-sm"
                                   placeholder="Masukkan nomor sertifikat atau UUID..."
                                   style="border-radius:8px; font-size:0.85rem;">
                            <button id="quickVerifyBtn" class="btn btn-sm"
                                    style="background:var(--dcms-primary); color:#fff; border-radius:8px; white-space:nowrap; padding: 4px 14px;">
                                <i class="bi bi-search"></i>
                            </button>
                        </div>
                        <div id="quickVerifyResult" class="mt-2" style="display:none;"></div>
                    </div>
                </div>
            </div>

            {{-- Verifikasi Terbaru --}}
            <div class="col-12">
                <div class="dcms-card">
                    <div class="card-header">
                        <i class="bi bi-shield-check me-2" style="color:#15803d;"></i>Verifikasi Terbaru
                    </div>
                    <div class="p-3">
                        @forelse($stats['recent_verifications'] as $vlog)
                        <div class="verify-item">
                            <div style="width:8px; height:8px; border-radius:50%; flex-shrink:0; margin-top:5px;
                                background: {{ $vlog->result === 'valid' ? '#15803d' : '#dc2626' }};">
                            </div>
                            <div style="flex:1; min-width:0;">
                                <div style="font-weight:500; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">
                                    {{ $vlog->certificate?->recipient_name ?? 'N/A' }}
                                </div>
                                <div style="color:#94a3b8; font-size:0.75rem;">
                                    {{ $vlog->ip_address }} &bull; {{ $vlog->verified_at->diffForHumans() }}
                                </div>
                            </div>
                            <span style="font-size:0.72rem; padding:2px 8px; border-radius:6px;
                                {{ $vlog->result === 'valid' ? 'background:#dcfce7; color:#15803d;' : 'background:#fee2e2; color:#dc2626;' }}">
                                {{ strtoupper($vlog->result) }}
                            </span>
                        </div>
                        @empty
                        <div class="text-center py-3" style="color:#94a3b8; font-size:0.825rem;">
                            <i class="bi bi-shield d-block mb-1" style="font-size:1.5rem;"></i>
                            Belum ada aktivitas verifikasi
                        </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
// ─── Data dari PHP ke JavaScript ─────────────────────────────
const certPerMonthData = @json($stats['cert_per_month']);
const certByEventData  = @json($stats['cert_by_event']);

// ─── Grafik: Sertifikat per Bulan ────────────────────────────
(function () {
    const labels = Object.keys(certPerMonthData).map(m => {
        const [y, mo] = m.split('-');
        const d = new Date(y, parseInt(mo) - 1);
        return d.toLocaleDateString('id-ID', { month: 'short', year: '2-digit' });
    });
    const values = Object.values(certPerMonthData);

    new Chart(document.getElementById('chartCertPerMonth'), {
        type: 'bar',
        data: {
            labels,
            datasets: [{
                label: 'Sertifikat Diterbitkan',
                data: values,
                backgroundColor: 'rgba(26,35,126,0.8)',
                borderRadius: 6,
                borderSkipped: false,
                hoverBackgroundColor: 'rgba(0,188,212,0.85)',
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: '#1a237e',
                    cornerRadius: 8,
                    callbacks: {
                        label: ctx => ` ${ctx.parsed.y} sertifikat`,
                    }
                }
            },
            scales: {
                x: { grid: { display: false }, ticks: { font: { size: 11 } } },
                y: {
                    beginAtZero: true,
                    ticks: { stepSize: 1, font: { size: 11 } },
                    grid: { color: 'rgba(0,0,0,0.04)' }
                }
            }
        }
    });
})();

// ─── Grafik: Distribusi per Event (Doughnut) ─────────────────
(function () {
    const labels = Object.keys(certByEventData);
    const values = Object.values(certByEventData);
    const colors = ['#1a237e','#3949ab','#00bcd4','#4caf50','#ff9800'];

    new Chart(document.getElementById('chartCertByEvent'), {
        type: 'doughnut',
        data: {
            labels,
            datasets: [{
                data: values,
                backgroundColor: colors.slice(0, labels.length),
                borderWidth: 0,
                hoverOffset: 6,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '65%',
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        boxWidth: 10,
                        borderRadius: 3,
                        font: { size: 11 },
                        padding: 10,
                        generateLabels(chart) {
                            return chart.data.labels.map((label, i) => ({
                                text: label.length > 18 ? label.slice(0,18)+'…' : label,
                                fillStyle: colors[i],
                                strokeStyle: 'transparent',
                                index: i,
                            }));
                        }
                    }
                }
            }
        }
    });
})();

// ─── Quick Verify ─────────────────────────────────────────────
document.getElementById('quickVerifyBtn')?.addEventListener('click', async function () {
    const input  = document.getElementById('quickVerifyInput').value.trim();
    const result = document.getElementById('quickVerifyResult');
    if (!input) return;

    this.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';
    this.disabled  = true;

    try {
        const res  = await fetch(`/api/certificates/verify/${input}`);
        const json = await res.json();
        const cert = json.data;

        if (res.status === 404 || !json.success) {
            result.innerHTML = `
                <div class="alert alert-danger d-flex gap-2 align-items-center py-2 mb-0" style="border-radius:8px; font-size:0.825rem;">
                    <i class="bi bi-x-circle-fill"></i> Sertifikat tidak ditemukan atau tidak valid.
                </div>`;
        } else {
            const statusMap = { active: ['VALID','badge-active'], revoked: ['DICABUT','badge-revoked'] };
            const [statusLabel, statusClass] = statusMap[cert.status] ?? ['TIDAK DIKENAL','badge-expired'];
            result.innerHTML = `
                <div class="p-3" style="background:#f8fafc; border-radius:8px; border:1px solid #e2e8f0; font-size:0.825rem;">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <strong style="font-size:0.9rem;">${cert.recipient_name}</strong>
                        <span class="badge ${statusClass}" style="border-radius:6px;">${statusLabel}</span>
                    </div>
                    <div style="color:#64748b;">${cert.certificate_number}</div>
                    <div style="color:#64748b; margin-top:4px;">${cert.event_name ?? ''}</div>
                </div>`;
        }
    } catch {
        result.innerHTML = `<div class="alert alert-warning py-2 mb-0" style="border-radius:8px; font-size:0.825rem;">Gagal menghubungi server.</div>`;
    } finally {
        this.innerHTML = '<i class="bi bi-search"></i>';
        this.disabled  = false;
        result.style.display = 'block';
    }
});

document.getElementById('quickVerifyInput')?.addEventListener('keydown', function (e) {
    if (e.key === 'Enter') document.getElementById('quickVerifyBtn').click();
});
</script>
@endpush
