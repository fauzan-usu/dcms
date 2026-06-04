{{-- resources/views/public/verify.blade.php --}}
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verifikasi Sertifikat — DCMS</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Instrument+Serif:ital@0;1&family=DM+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root { --p: #1a237e; --accent: #00bcd4; }
        body {
            font-family: 'DM Sans', sans-serif;
            background: linear-gradient(160deg, #e8eaf6 0%, #f0f4ff 60%, #e0f7fa 100%);
            min-height: 100vh;
        }

        .verify-navbar {
            background: rgba(255,255,255,0.85);
            backdrop-filter: blur(12px);
            border-bottom: 1px solid rgba(26,35,126,0.08);
            padding: 12px 0;
        }
        .verify-container {
            max-width: 680px;
            margin: 0 auto;
            padding: 2rem 1rem 4rem;
        }

        .verify-hero {
            text-align: center;
            margin-bottom: 2rem;
        }
        .verify-hero .hero-icon {
            width: 72px; height: 72px;
            background: linear-gradient(135deg, var(--p), #3949ab);
            border-radius: 18px;
            display: inline-flex; align-items: center; justify-content: center;
            font-size: 32px;
            color: #fff;
            margin-bottom: 1.25rem;
            box-shadow: 0 8px 24px rgba(26,35,126,0.25);
        }
        .verify-hero h1 {
            font-family: 'Instrument Serif', serif;
            font-size: 2rem;
            color: var(--p);
            margin-bottom: 0.5rem;
        }
        .verify-hero p {
            color: #64748b;
            font-size: 0.9rem;
        }

        .verify-card {
            background: #fff;
            border-radius: 16px;
            border: 1px solid rgba(26,35,126,0.07);
            box-shadow: 0 4px 24px rgba(26,35,126,0.08);
            overflow: hidden;
        }
        .verify-tabs {
            display: flex;
            border-bottom: 1px solid #f1f5f9;
        }
        .verify-tab {
            flex: 1;
            padding: 14px 16px;
            text-align: center;
            cursor: pointer;
            font-size: 0.875rem;
            font-weight: 500;
            color: #94a3b8;
            border: none;
            background: transparent;
            transition: all 0.2s;
            border-bottom: 2px solid transparent;
        }
        .verify-tab.active {
            color: var(--p);
            border-bottom-color: var(--p);
            background: #f8f9ff;
        }

        /* Result area */
        .result-valid {
            background: linear-gradient(135deg, #f0fdf4, #dcfce7);
            border: 2px solid #86efac;
            border-radius: 14px;
            padding: 1.5rem;
        }
        .result-revoked {
            background: linear-gradient(135deg, #fff1f2, #fee2e2);
            border: 2px solid #fca5a5;
            border-radius: 14px;
            padding: 1.5rem;
        }
        .result-invalid {
            background: #fafafa;
            border: 2px solid #e2e8f0;
            border-radius: 14px;
            padding: 1.5rem;
            text-align: center;
        }

        .cert-field {
            display: flex;
            flex-direction: column;
            gap: 2px;
            padding: 10px 0;
            border-bottom: 1px solid rgba(0,0,0,0.05);
        }
        .cert-field:last-child { border-bottom: none; }
        .cert-field-label {
            font-size: 0.72rem;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            color: #94a3b8;
            font-weight: 600;
        }
        .cert-field-value {
            font-size: 0.95rem;
            font-weight: 500;
            color: #1e293b;
        }

        /* Scanner area */
        #reader { border-radius: 12px; overflow: hidden; }
        #reader video { border-radius: 12px; }

        .form-control-verify {
            border: 1.5px solid #e2e8f0;
            border-radius: 10px;
            font-size: 0.9rem;
            padding: 12px 14px;
            transition: border-color 0.2s, box-shadow 0.2s;
        }
        .form-control-verify:focus {
            border-color: var(--p);
            box-shadow: 0 0 0 3px rgba(26,35,126,0.08);
            outline: none;
        }
        .btn-verify {
            background: linear-gradient(135deg, var(--p), #3949ab);
            color: #fff;
            border: none;
            border-radius: 10px;
            padding: 12px 20px;
            font-size: 0.9rem;
            font-weight: 600;
            width: 100%;
            transition: transform 0.15s, box-shadow 0.15s;
        }
        .btn-verify:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 18px rgba(26,35,126,0.2);
            color: #fff;
        }
    </style>
</head>
<body>

{{-- Navbar --}}
<nav class="verify-navbar">
    <div class="container d-flex align-items-center justify-content-between">
        <a href="{{ route('home') }}" class="d-flex align-items-center gap-2 text-decoration-none">
            <div style="width:32px; height:32px; background:var(--accent); border-radius:8px; display:flex; align-items:center; justify-content:center; color:#fff;">
                <i class="bi bi-award-fill"></i>
            </div>
            <span style="font-family:'Instrument Serif', serif; font-size:1rem; color:var(--p);">DCMS</span>
        </a>
        <a href="{{ route('login') }}" class="btn btn-sm"
           style="background:var(--p); color:#fff; border-radius:8px; font-size:0.8rem; padding:6px 14px;">
            <i class="bi bi-box-arrow-in-right me-1"></i>Login Admin
        </a>
    </div>
</nav>

{{-- Main Content --}}
<div class="verify-container">

    {{-- Hero --}}
    <div class="verify-hero">
        <div class="hero-icon"><i class="bi bi-shield-check"></i></div>
        <h1>Verifikasi Sertifikat Digital</h1>
        <p>Pastikan keaslian sertifikat Anda melalui sistem verifikasi resmi kami.</p>
    </div>

    {{-- Verification Card --}}
    <div class="verify-card mb-3">
        <div class="verify-tabs">
            <button class="verify-tab active" onclick="switchTab('manual', this)">
                <i class="bi bi-keyboard me-2"></i>Input Manual
            </button>
            <button class="verify-tab" onclick="switchTab('scanner', this)">
                <i class="bi bi-qr-code-scan me-2"></i>Scan QR Code
            </button>
        </div>

        {{-- Tab: Input Manual --}}
        <div id="tab-manual" class="p-4">
            <div class="mb-3">
                <label class="form-label" style="font-weight:600; font-size:0.875rem;">
                    Nomor Sertifikat atau ID Unik
                </label>
                <input type="text" id="manualInput" class="form-control-verify w-100"
                       placeholder="Contoh: CERT/2024/08/00001 atau UUID dari QR Code..."
                       value="{{ request('uuid') !== 'check' ? request('uuid') : '' }}">
                <div style="font-size:0.78rem; color:#94a3b8; margin-top:6px;">
                    Nomor sertifikat dapat ditemukan di bagian bawah dokumen sertifikat Anda.
                </div>
            </div>
            <button onclick="doVerify()" class="btn-verify">
                <i class="bi bi-search me-2"></i>Verifikasi Sekarang
            </button>
        </div>

        {{-- Tab: QR Scanner --}}
        <div id="tab-scanner" class="p-4" style="display:none;">
            <div style="font-size:0.875rem; color:#64748b; margin-bottom:1rem; text-align:center;">
                Arahkan kamera ke QR Code yang tertera pada sertifikat Anda.
            </div>
            <div id="reader"></div>
            <button onclick="stopScanner()" id="stopScanBtn"
                    class="btn btn-sm w-100 mt-3" style="display:none;
                    background:#fee2e2; color:#dc2626; border-radius:8px;">
                <i class="bi bi-stop-circle me-1"></i>Hentikan Kamera
            </button>
        </div>
    </div>

    {{-- Result Area --}}
    <div id="resultArea"></div>

    {{-- Info Box --}}
    <div class="mt-4 p-3" style="background:rgba(26,35,126,0.04); border-radius:12px; border:1px solid rgba(26,35,126,0.08);">
        <div style="font-size:0.8rem; color:#64748b; line-height:1.8;">
            <strong style="color:var(--p);">Cara Verifikasi:</strong> Scan QR Code yang tertera pada sertifikat, atau masukkan nomor sertifikat
            secara manual. Sistem akan memverifikasi keaslian dan menampilkan informasi penerima secara real-time.
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
{{-- Html5-QRCode library --}}
<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>

<script>
let html5QrCode = null;

// ─── Tab switching ─────────────────────────────────────────────────
function switchTab(tab, el) {
    document.querySelectorAll('.verify-tab').forEach(t => t.classList.remove('active'));
    el.classList.add('active');
    document.getElementById('tab-manual').style.display = tab === 'manual' ? 'block' : 'none';
    document.getElementById('tab-scanner').style.display = tab === 'scanner' ? 'block' : 'none';
    if (tab === 'scanner') {
        startScanner();
    } else {
        stopScanner();
    }
}

// ─── QR Scanner ────────────────────────────────────────────────────
function startScanner() {
    html5QrCode = new Html5Qrcode('reader');
    html5QrCode.start(
        { facingMode: 'environment' },
        { fps: 10, qrbox: { width: 260, height: 260 } },
        (decodedText) => {
            stopScanner();
            // Ekstrak UUID dari URL atau gunakan langsung
            const match = decodedText.match(/verify\/([a-f0-9-]{36})/i);
            const uuid  = match ? match[1] : decodedText;
            document.getElementById('manualInput').value = uuid;
            switchTab('manual', document.querySelector('.verify-tab'));
            doVerify();
        },
        () => {} // Error per frame — diabaikan
    ).then(() => {
        document.getElementById('stopScanBtn').style.display = 'block';
    }).catch(err => {
        showResult(`<div class="result-invalid"><i class="bi bi-exclamation-triangle-fill text-warning d-block mb-2" style="font-size:2rem;"></i><p style="color:#64748b; margin:0;">Kamera tidak dapat diakses. Pastikan izin kamera diberikan di browser Anda.</p></div>`);
    });
}

function stopScanner() {
    if (html5QrCode?.isScanning) {
        html5QrCode.stop().catch(() => {});
    }
    document.getElementById('stopScanBtn').style.display = 'none';
}

// ─── Proses Verifikasi ─────────────────────────────────────────────
async function doVerify() {
    const input = document.getElementById('manualInput').value.trim();
    if (!input) {
        showResult(`<div class="result-invalid"><p style="color:#94a3b8; margin:0; font-size:0.9rem;">Silakan masukkan nomor sertifikat atau scan QR Code terlebih dahulu.</p></div>`);
        return;
    }

    // Loading state
    showResult(`
        <div class="result-invalid">
            <div class="d-flex align-items-center justify-content-center gap-3">
                <div class="spinner-border spinner-border-sm" style="color:var(--p);"></div>
                <span style="color:#64748b; font-size:0.9rem;">Memverifikasi sertifikat...</span>
            </div>
        </div>`);

    try {
        const res  = await fetch(`/api/certificates/verify/${encodeURIComponent(input)}`);
        const json = await res.json();

        if (!json.success || res.status === 404) {
            showResult(`
                <div class="result-invalid">
                    <div class="text-center">
                        <i class="bi bi-x-octagon-fill d-block mb-3" style="font-size:2.5rem; color:#dc2626;"></i>
                        <h5 style="color:#dc2626; font-family:'Instrument Serif', serif;">Sertifikat Tidak Ditemukan</h5>
                        <p style="color:#64748b; font-size:0.875rem; margin-bottom:0;">
                            Nomor atau kode yang Anda masukkan tidak terdaftar dalam sistem kami.
                            Pastikan Anda memasukkan kode yang benar.
                        </p>
                    </div>
                </div>`);
            return;
        }

        const cert = json.data;

        if (cert.status === 'active') {
            showResult(`
                <div class="result-valid">
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <div style="width:48px; height:48px; background:#15803d; border-radius:12px; display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                            <i class="bi bi-check-lg text-white" style="font-size:1.5rem;"></i>
                        </div>
                        <div>
                            <div style="font-family:'Instrument Serif',serif; font-size:1.25rem; color:#15803d;">Sertifikat Valid</div>
                            <div style="font-size:0.8rem; color:#4ade80;">Dokumen ini asli dan terdaftar dalam sistem</div>
                        </div>
                    </div>
                    <div>
                        ${certField('Nama Penerima', cert.recipient_name)}
                        ${certField('Nomor Sertifikat', cert.certificate_number)}
                        ${certField('Nama Kegiatan', cert.event_name || '-')}
                        ${certField('Institusi', cert.recipient_institution || '-')}
                        ${certField('Tanggal Terbit', formatDate(cert.issued_at))}
                    </div>
                </div>`);
        } else {
            showResult(`
                <div class="result-revoked">
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <div style="width:48px; height:48px; background:#dc2626; border-radius:12px; display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                            <i class="bi bi-x-lg text-white" style="font-size:1.5rem;"></i>
                        </div>
                        <div>
                            <div style="font-family:'Instrument Serif',serif; font-size:1.25rem; color:#dc2626;">Sertifikat Dicabut</div>
                            <div style="font-size:0.8rem; color:#f87171;">Sertifikat ini telah dicabut dan tidak berlaku lagi</div>
                        </div>
                    </div>
                    <div>
                        ${certField('Nama Penerima', cert.recipient_name)}
                        ${certField('Nomor Sertifikat', cert.certificate_number)}
                        ${certField('Alasan Pencabutan', cert.revoke_reason || 'Tidak disebutkan')}
                    </div>
                </div>`);
        }
    } catch {
        showResult(`<div class="result-invalid"><p style="color:#dc2626; text-align:center; margin:0; font-size:0.9rem;">Terjadi kesalahan koneksi. Silakan coba lagi.</p></div>`);
    }
}

// ─── Helper: render satu field informasi sertifikat ────────────────
function certField(label, value) {
    return `<div class="cert-field">
        <span class="cert-field-label">${label}</span>
        <span class="cert-field-value">${value}</span>
    </div>`;
}

function showResult(html) {
    const area = document.getElementById('resultArea');
    area.innerHTML = html;
    area.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
}

function formatDate(iso) {
    if (!iso) return '-';
    return new Date(iso).toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' });
}

// ─── Auto-trigger jika UUID sudah ada di URL ───────────────────────
const initUuid = '{{ request("uuid") !== "check" ? request("uuid") : "" }}';
if (initUuid) {
    window.addEventListener('DOMContentLoaded', () => setTimeout(doVerify, 300));
}
</script>
</body>
</html>
