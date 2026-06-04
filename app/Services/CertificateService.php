<?php

namespace App\Services;

use App\Models\{Certificate, CertificateTemplate, Event, Setting, ActivityLog};
use App\Jobs\GenerateCertificatePdfJob;
use Illuminate\Support\{Str, Facades\DB, Facades\Storage, Facades\Log};
use Endroid\QrCode\{QrCode, Writer\PngWriter, Color\Color, Encoding\Encoding};
use Endroid\QrCode\ErrorCorrectionLevel\ErrorCorrectionLevelHigh;
use Dompdf\{Dompdf, Options};

/**
 * CertificateService
 *
 * Bertanggung jawab atas seluruh siklus hidup sertifikat:
 * - Generate nomor sertifikat unik
 * - Generate token dan hash verifikasi
 * - Render sertifikat ke PDF menggunakan DomPDF
 * - Generate QR Code menggunakan endroid/qr-code
 * - Dispatch job untuk batch generation (antrean)
 *
 * Pola yang digunakan adalah Service Layer — controller memanggil service ini,
 * bukan langsung memanipulasi model. Ini menjaga controller tetap tipis.
 */
class CertificateService
{
    /**
     * Membuat satu sertifikat baru dari data yang diberikan.
     *
     * Method ini berjalan dalam satu database transaction agar
     * konsisten — jika ada tahap yang gagal, seluruh operasi dibatalkan.
     *
     * @param  array  $data  Data peserta: event_id, template_id, recipient_name, dst.
     * @param  int    $issuedBy  ID user yang menerbitkan sertifikat
     * @return Certificate  Instance sertifikat yang baru dibuat
     */
    public function createCertificate(array $data, int $issuedBy): Certificate
    {
        return DB::transaction(function () use ($data, $issuedBy) {
            $verificationUuid  = Str::uuid()->toString();
            $verificationToken = Str::random(64);
            $verificationHash  = hash('sha256', $verificationUuid . $data['recipient_name'] . now()->timestamp);
            $certificateNumber = $this->generateCertificateNumber($data['event_id']);

            $certificate = Certificate::create([
                'event_id'                  => $data['event_id'],
                'template_id'               => $data['template_id'],
                'user_id'                   => $data['user_id'] ?? null,
                'issued_by'                 => $issuedBy,
                'import_id'                 => $data['import_id'] ?? null,
                'recipient_name'            => trim($data['recipient_name']),
                'recipient_email'           => isset($data['recipient_email']) ? strtolower(trim($data['recipient_email'])) : null,
                'recipient_institution'     => $data['recipient_institution'] ?? null,
                'recipient_identity_number' => $data['recipient_identity_number'] ?? null,
                'certificate_number'        => $certificateNumber,
                'verification_uuid'         => $verificationUuid,
                'verification_hash'         => $verificationHash,
                'verification_token'        => $verificationToken,
                'status'                    => 'active',
                'issued_at'                 => now(),
            ]);

            // Catat ke activity log
            ActivityLog::record(
                action:      'generated',
                module:      'certificate',
                subjectId:   $certificate->id,
                subjectType: Certificate::class,
                description: "Sertifikat diterbitkan untuk {$certificate->recipient_name}"
            );

            // Catat ke certificate log
            $certificate->certificateLogs()->create([
                'user_id'    => $issuedBy,
                'action'     => 'generated',
                'notes'      => 'Sertifikat berhasil dibuat',
                'created_at' => now(),
            ]);

            // Increment usage count pada template
            CertificateTemplate::where('id', $data['template_id'])->increment('usage_count');

            return $certificate;
        });
    }

    /**
     * Batch create sertifikat dari array peserta.
     * Setiap sertifikat di-dispatch sebagai job antrean agar tidak memblokir
     * request HTTP ketika jumlah peserta sangat besar (ratusan hingga ribuan).
     *
     * @param  array  $participants  Array asosiatif data peserta
     * @param  int    $eventId
     * @param  int    $templateId
     * @param  int    $issuedBy
     * @param  int|null  $importId  ID import batch jika berasal dari Excel
     * @return array  Statistik: total, queued, failed
     */
    public function batchCreate(
        array $participants,
        int $eventId,
        int $templateId,
        int $issuedBy,
        ?int $importId = null
    ): array {
        $queued = 0;
        $failed = 0;
        $errors = [];

        foreach ($participants as $index => $participant) {
            try {
                // Validasi minimal: nama wajib ada
                if (empty($participant['recipient_name'])) {
                    throw new \InvalidArgumentException("Nama peserta tidak boleh kosong (baris " . ($index + 2) . ")");
                }

                // Dispatch ke antrean; class job akan memanggil createCertificate
                GenerateCertificatePdfJob::dispatch(
                    array_merge($participant, [
                        'event_id'    => $eventId,
                        'template_id' => $templateId,
                        'import_id'   => $importId,
                    ]),
                    $issuedBy
                );

                $queued++;
            } catch (\Throwable $e) {
                $failed++;
                $errors[] = [
                    'row'     => $index + 2,
                    'name'    => $participant['recipient_name'] ?? '(kosong)',
                    'message' => $e->getMessage(),
                ];
            }
        }

        return [
            'total'  => count($participants),
            'queued' => $queued,
            'failed' => $failed,
            'errors' => $errors,
        ];
    }

    /**
     * Merender sertifikat menjadi file PDF menggunakan DomPDF.
     *
     * Alurnya: ambil konfigurasi template → generate QR Code →
     * susun HTML menggunakan view Blade → render via DomPDF → simpan ke storage.
     *
     * @param  Certificate  $certificate
     * @return string  Path file PDF di storage
     */
    public function generatePdf(Certificate $certificate): string
    {
        $template = $certificate->template;
        $event    = $certificate->event;

        // 1. Generate QR Code dan simpan sementara
        $qrImagePath = $this->generateQrCode($certificate);

        // 2. Render HTML menggunakan view Blade yang sudah disiapkan
        $html = view('certificates.pdf_template', [
            'certificate' => $certificate,
            'template'    => $template,
            'event'       => $event,
            'qrImagePath' => $qrImagePath,
        ])->render();

        // 3. Konfigurasi DomPDF
        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);
        $options->set('defaultFont', 'DejaVu Sans');
        $options->set('isFontSubsettingEnabled', true);
        $options->set('dpi', 150);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);

        // Orientasi dan ukuran kertas berdasarkan template
        $orientation = $template->orientation === 'portrait' ? 'portrait' : 'landscape';
        $dompdf->setPaper('A4', $orientation);
        $dompdf->render();

        // 4. Simpan PDF ke storage
        $pdfPath = "certificates/{$event->slug}/{$certificate->certificate_number}.pdf";
        Storage::put($pdfPath, $dompdf->output());

        // 5. Update path di database
        $certificate->update([
            'pdf_file'   => $pdfPath,
            'image_file' => $qrImagePath, // simpan path QR juga
        ]);

        // Catat ke log
        $certificate->certificateLogs()->create([
            'user_id'    => auth()->id(),
            'action'     => 'pdf_generated',
            'notes'      => "File PDF berhasil digenerate: {$pdfPath}",
            'created_at' => now(),
        ]);

        return $pdfPath;
    }

    /**
     * Melakukan verifikasi sertifikat berdasarkan UUID.
     * Method ini dipanggil dari halaman publik /verify/{uuid}.
     *
     * @param  string  $uuid  Nilai verification_uuid dari QR Code
     * @param  string  $ip    IP address pemverifikasi
     * @param  string|null  $userAgent  Browser/device
     * @return array  ['status' => 'valid'|'invalid'|'revoked', 'certificate' => ?Certificate]
     */
    public function verify(string $uuid, string $ip, ?string $userAgent = null): array
    {
        $certificate = Certificate::with(['event', 'template', 'issuer'])
            ->where('verification_uuid', $uuid)
            ->first();

        if (!$certificate) {
            return ['status' => 'invalid', 'certificate' => null];
        }

        // Catat verifikasi (termasuk status revoked)
        $certificate->recordVerification('qr', $ip, $userAgent);

        return [
            'status'      => $certificate->status,
            'certificate' => $certificate,
        ];
    }

    /**
     * Mencabut (revoke) sertifikat.
     *
     * @param  Certificate  $certificate
     * @param  string  $reason   Alasan pencabutan
     * @param  int     $revokedBy  ID admin yang mencabut
     * @return void
     */
    public function revoke(Certificate $certificate, string $reason, int $revokedBy): void
    {
        $certificate->update([
            'status'       => 'revoked',
            'revoke_reason'=> $reason,
            'revoked_at'   => now(),
            'revoked_by'   => $revokedBy,
        ]);

        $certificate->certificateLogs()->create([
            'user_id'    => $revokedBy,
            'action'     => 'revoked',
            'notes'      => "Dicabut: {$reason}",
            'created_at' => now(),
        ]);

        ActivityLog::record(
            action:      'revoked',
            module:      'certificate',
            subjectId:   $certificate->id,
            description: "Sertifikat {$certificate->certificate_number} dicabut: {$reason}"
        );
    }

    // -----------------------------------------------------------------
    // PRIVATE HELPERS
    // -----------------------------------------------------------------

    /**
     * Menghasilkan nomor sertifikat unik berdasarkan format di Settings.
     * Default format: CERT/YYYY/MM/NNNNN (contoh: CERT/2024/08/00001)
     * Menggunakan pessimistic locking (lockForUpdate) agar tidak ada
     * race condition ketika batch generate berlangsung secara paralel.
     */
    private function generateCertificateNumber(int $eventId): string
    {
        return DB::transaction(function () use ($eventId) {
            $prefix = Setting::get('cert_number_prefix', 'CERT');
            $seqLen = (int) Setting::get('cert_sequence_length', 5);
            $year   = now()->format('Y');
            $month  = now()->format('m');

            // Ambil nomor terakhir untuk event ini pada bulan berjalan
            $last = Certificate::where('event_id', $eventId)
                ->whereYear('created_at', $year)
                ->whereMonth('created_at', $month)
                ->lockForUpdate()
                ->count();

            $sequence = str_pad($last + 1, $seqLen, '0', STR_PAD_LEFT);

            return "{$prefix}/{$year}/{$month}/{$sequence}";
        });
    }

    /**
     * Menghasilkan QR Code PNG untuk URL verifikasi sertifikat.
     * Menggunakan library endroid/qr-code v4+.
     *
     * @param  Certificate  $certificate
     * @return string  Path file QR di storage (relatif terhadap disk 'public')
     */
    private function generateQrCode(Certificate $certificate): string
    {
        $verifyUrl = $certificate->verification_url;
        $qrSize    = (int) Setting::get('cert_qr_size', 200);

        $qrCode = QrCode::create($verifyUrl)
            ->setEncoding(new Encoding('UTF-8'))
            ->setErrorCorrectionLevel(new ErrorCorrectionLevelHigh())
            ->setSize($qrSize)
            ->setMargin(8)
            ->setForegroundColor(new Color(26, 35, 126))   // #1a237e — navy
            ->setBackgroundColor(new Color(255, 255, 255));

        $writer  = new PngWriter();
        $result  = $writer->write($qrCode);

        $qrPath  = "qrcodes/{$certificate->verification_uuid}.png";
        Storage::put($qrPath, $result->getString());

        return $qrPath;
    }
}
