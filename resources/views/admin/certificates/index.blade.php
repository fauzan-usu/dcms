@extends('layouts.admin')

@section('title', 'Daftar Sertifikat')

@section('breadcrumb')
    <a href="{{ route('admin.dashboard') }}" style="color:#94a3b8; text-decoration:none;">Dashboard</a>
    <span class="mx-1" style="color:#cbd5e1;">/</span>
    <span class="current">Sertifikat</span>
@endsection

@section('content')
<div class="page-header d-flex align-items-center justify-content-between flex-wrap gap-2">
    <div>
        <h1 class="page-title">Daftar Sertifikat</h1>
        <p class="page-subtitle">Kelola seluruh sertifikat digital yang telah diterbitkan.</p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        @can('certificates.export')
        <a href="{{ route('admin.certificates.export', request()->query()) }}"
           class="btn btn-sm d-flex align-items-center gap-1"
           style="background:#fff; border:1px solid #e2e8f0; border-radius:9px; color:#374151; font-size:0.83rem; padding:7px 14px;">
            <i class="bi bi-download"></i> Export Excel
        </a>
        @endcan
        @can('certificates.create')
        <a href="{{ route('admin.certificates.create') }}"
           class="btn btn-sm d-flex align-items-center gap-1"
           style="background:var(--dcms-primary); border:none; border-radius:9px; color:#fff; font-size:0.83rem; padding:7px 14px;">
            <i class="bi bi-plus-lg"></i> Buat Sertifikat
        </a>
        @endcan
    </div>
</div>

{{-- Filter Bar --}}
<div class="dcms-card mb-3">
    <div class="p-3">
        <form action="{{ route('admin.certificates.index') }}" method="GET" class="row g-2 align-items-end">
            <div class="col-12 col-md-3">
                <label class="form-label" style="font-size:0.78rem; color:#64748b; font-weight:600;">Pencarian</label>
                <div class="input-group input-group-sm">
                    <span class="input-group-text" style="border-radius:8px 0 0 8px; background:#f8fafc;">
                        <i class="bi bi-search" style="color:#94a3b8;"></i>
                    </span>
                    <input type="text" name="search" class="form-control" value="{{ request('search') }}"
                           placeholder="Nama, email, nomor sertifikat..." style="border-radius:0 8px 8px 0;">
                </div>
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label" style="font-size:0.78rem; color:#64748b; font-weight:600;">Kegiatan</label>
                <select name="event_id" class="form-select form-select-sm" style="border-radius:8px;">
                    <option value="">Semua Kegiatan</option>
                    @foreach($events as $event)
                        <option value="{{ $event->id }}" {{ request('event_id') == $event->id ? 'selected' : '' }}>
                            {{ Str::limit($event->name, 35) }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label" style="font-size:0.78rem; color:#64748b; font-weight:600;">Status</label>
                <select name="status" class="form-select form-select-sm" style="border-radius:8px;">
                    <option value="">Semua Status</option>
                    <option value="active"  {{ request('status') === 'active'  ? 'selected' : '' }}>Aktif</option>
                    <option value="revoked" {{ request('status') === 'revoked' ? 'selected' : '' }}>Dicabut</option>
                    <option value="expired" {{ request('status') === 'expired' ? 'selected' : '' }}>Kadaluarsa</option>
                </select>
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label" style="font-size:0.78rem; color:#64748b; font-weight:600;">Dari Tanggal</label>
                <input type="date" name="date_from" class="form-control form-control-sm"
                       value="{{ request('date_from') }}" style="border-radius:8px;">
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label" style="font-size:0.78rem; color:#64748b; font-weight:600;">Sampai Tanggal</label>
                <input type="date" name="date_to" class="form-control form-control-sm"
                       value="{{ request('date_to') }}" style="border-radius:8px;">
            </div>
            <div class="col-12 col-md-1 d-flex gap-1">
                <button type="submit" class="btn btn-sm flex-fill"
                        style="background:var(--dcms-primary); color:#fff; border-radius:8px; padding:6px 8px;">
                    <i class="bi bi-funnel-fill"></i>
                </button>
                <a href="{{ route('admin.certificates.index') }}" class="btn btn-sm flex-fill"
                   style="background:#f1f5f9; color:#64748b; border-radius:8px; padding:6px 8px;">
                    <i class="bi bi-x-lg"></i>
                </a>
            </div>
        </form>
    </div>
</div>

{{-- Tabel --}}
<div class="dcms-card">
    <div class="card-header d-flex align-items-center justify-content-between">
        <span>
            <i class="bi bi-table me-2" style="color:var(--dcms-primary);"></i>
            Daftar Sertifikat
            <span class="ms-2 badge" style="background:var(--dcms-primary-bg); color:var(--dcms-primary); border-radius:6px; font-size:0.72rem;">
                {{ $certificates->total() }} data
            </span>
        </span>
    </div>
    <div class="p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0" style="font-size:0.825rem;">
                <thead style="background:#f8fafc;">
                    <tr>
                        <th class="px-3 py-2" style="color:#64748b; font-weight:600; width:40px;">#</th>
                        <th class="px-3 py-2" style="color:#64748b; font-weight:600;">Penerima</th>
                        <th class="px-3 py-2" style="color:#64748b; font-weight:600;">Nomor Sertifikat</th>
                        <th class="px-3 py-2" style="color:#64748b; font-weight:600;">Kegiatan</th>
                        <th class="px-3 py-2" style="color:#64748b; font-weight:600;">Terbit</th>
                        <th class="px-3 py-2" style="color:#64748b; font-weight:600;">Status</th>
                        <th class="px-3 py-2" style="color:#64748b; font-weight:600; width:130px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($certificates as $index => $cert)
                    <tr>
                        <td class="px-3 py-2" style="color:#94a3b8;">
                            {{ $certificates->firstItem() + $index }}
                        </td>
                        <td class="px-3 py-2">
                            <div style="font-weight:500;">{{ $cert->recipient_name }}</div>
                            @if($cert->recipient_email)
                            <div style="font-size:0.75rem; color:#94a3b8;">{{ $cert->recipient_email }}</div>
                            @endif
                            @if($cert->recipient_institution)
                            <div style="font-size:0.72rem; color:#cbd5e1;">{{ Str::limit($cert->recipient_institution, 30) }}</div>
                            @endif
                        </td>
                        <td class="px-3 py-2">
                            <code style="background:#f1f5f9; padding:2px 6px; border-radius:4px; font-size:0.78rem; color:var(--dcms-primary);">
                                {{ $cert->certificate_number }}
                            </code>
                        </td>
                        <td class="px-3 py-2" style="color:#374151;">
                            {{ Str::limit($cert->event?->name, 35) }}
                            @if($cert->event?->event_date)
                            <div style="font-size:0.72rem; color:#94a3b8;">
                                {{ $cert->event->event_date->format('d M Y') }}
                            </div>
                            @endif
                        </td>
                        <td class="px-3 py-2" style="color:#64748b; white-space:nowrap;">
                            {{ $cert->issued_at?->format('d M Y') }}
                            <div style="font-size:0.72rem; color:#cbd5e1;">{{ $cert->issued_at?->format('H:i') }}</div>
                        </td>
                        <td class="px-3 py-2">
                            @if($cert->status === 'active')
                                <span class="badge badge-active" style="border-radius:6px; font-size:0.72rem;">
                                    <i class="bi bi-check-circle-fill me-1"></i>Aktif
                                </span>
                            @elseif($cert->status === 'revoked')
                                <span class="badge badge-revoked" style="border-radius:6px; font-size:0.72rem;">
                                    <i class="bi bi-x-circle-fill me-1"></i>Dicabut
                                </span>
                            @else
                                <span class="badge badge-expired" style="border-radius:6px; font-size:0.72rem;">Kadaluarsa</span>
                            @endif
                        </td>
                        <td class="px-3 py-2">
                            <div class="d-flex gap-1">
                                {{-- Detail --}}
                                <a href="{{ route('admin.certificates.show', $cert) }}"
                                   class="btn btn-sm" title="Lihat Detail"
                                   style="background:#f1f5f9; color:#475569; border-radius:7px; padding:4px 8px;">
                                    <i class="bi bi-eye-fill"></i>
                                </a>
                                {{-- Download --}}
                                <a href="{{ route('admin.certificates.download', $cert) }}"
                                   class="btn btn-sm" title="Unduh PDF"
                                   style="background:#e0f2fe; color:#0369a1; border-radius:7px; padding:4px 8px;">
                                    <i class="bi bi-file-pdf-fill"></i>
                                </a>
                                {{-- Verifikasi publik --}}
                                <a href="{{ route('verify.show', $cert->verification_uuid) }}" target="_blank"
                                   class="btn btn-sm" title="Buka Halaman Verifikasi"
                                   style="background:#dcfce7; color:#15803d; border-radius:7px; padding:4px 8px;">
                                    <i class="bi bi-qr-code-scan"></i>
                                </a>
                                {{-- Revoke (hanya aktif) --}}
                                @if($cert->status === 'active')
                                @can('certificates.revoke')
                                <button class="btn btn-sm" title="Cabut Sertifikat"
                                        style="background:#fee2e2; color:#dc2626; border-radius:7px; padding:4px 8px;"
                                        data-bs-toggle="modal"
                                        data-bs-target="#revokeModal"
                                        data-cert-id="{{ $cert->id }}"
                                        data-cert-name="{{ $cert->recipient_name }}"
                                        data-cert-number="{{ $cert->certificate_number }}">
                                    <i class="bi bi-x-circle-fill"></i>
                                </button>
                                @endcan
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center py-5" style="color:#94a3b8;">
                            <i class="bi bi-inbox d-block mb-2" style="font-size:2rem;"></i>
                            Tidak ada sertifikat yang ditemukan.
                            @if(request()->hasAny(['search','event_id','status','date_from','date_to']))
                            <br><a href="{{ route('admin.certificates.index') }}" style="font-size:0.825rem;">Hapus filter</a>
                            @endif
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if($certificates->hasPages())
        <div class="px-3 py-2 d-flex align-items-center justify-content-between" style="border-top:1px solid rgba(0,0,0,0.04);">
            <div style="font-size:0.8rem; color:#94a3b8;">
                Menampilkan {{ $certificates->firstItem() }}–{{ $certificates->lastItem() }}
                dari {{ $certificates->total() }} data
            </div>
            {{ $certificates->links('pagination::bootstrap-5') }}
        </div>
        @endif
    </div>
</div>

{{-- ═══════════════ MODAL REVOKE ════════════════════════════════════ --}}
@can('certificates.revoke')
<div class="modal fade" id="revokeModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius:14px; border:none; box-shadow:0 20px 60px rgba(0,0,0,0.15);">
            <div class="modal-header" style="border-bottom:1px solid rgba(0,0,0,0.06);">
                <h5 class="modal-title d-flex align-items-center gap-2">
                    <i class="bi bi-x-circle-fill text-danger"></i>
                    Cabut Sertifikat
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="revokeForm" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="alert alert-warning d-flex gap-2 align-items-start"
                         style="border-radius:10px; font-size:0.85rem;">
                        <i class="bi bi-exclamation-triangle-fill mt-1"></i>
                        <div>
                            Anda akan mencabut sertifikat milik
                            <strong id="revokeRecipientName">-</strong>
                            (<code id="revokeNumber" style="font-size:0.8rem;">-</code>).
                            Tindakan ini tidak dapat dibatalkan.
                        </div>
                    </div>
                    <div class="mb-0">
                        <label class="form-label" style="font-weight:600; font-size:0.85rem;">
                            Alasan Pencabutan <span class="text-danger">*</span>
                        </label>
                        <textarea name="revoke_reason" class="form-control" rows="3"
                                  placeholder="Contoh: Sertifikat diterbitkan dengan data yang keliru."
                                  style="border-radius:10px; font-size:0.85rem;" required></textarea>
                    </div>
                </div>
                <div class="modal-footer" style="border-top:1px solid rgba(0,0,0,0.06);">
                    <button type="button" class="btn btn-sm" data-bs-dismiss="modal"
                            style="background:#f1f5f9; color:#64748b; border-radius:8px; padding:7px 18px;">
                        Batal
                    </button>
                    <button type="submit" class="btn btn-sm btn-danger"
                            style="border-radius:8px; padding:7px 18px;">
                        <i class="bi bi-x-circle me-1"></i> Ya, Cabut Sertifikat
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endcan
@endsection

@push('scripts')
<script>
// Isi modal revoke dengan data sertifikat yang dipilih
document.getElementById('revokeModal')?.addEventListener('show.bs.modal', function (e) {
    const btn    = e.relatedTarget;
    const certId = btn.dataset.certId;
    document.getElementById('revokeRecipientName').textContent = btn.dataset.certName;
    document.getElementById('revokeNumber').textContent        = btn.dataset.certNumber;
    document.getElementById('revokeForm').action =
        `{{ url('admin/certificates') }}/${certId}/revoke`;
});
</script>
@endpush
