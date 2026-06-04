<?php

namespace App\Jobs;

use App\Models\{Certificate, Import};
use App\Services\CertificateService;
use Illuminate\Bus\{Queueable, Batchable};
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\{InteractsWithQueue, SerializesModels};
use Illuminate\Support\Facades\Log;

/**
 * GenerateCertificatePdfJob
 *
 * Job ini dimasukkan ke antrean (queue) sehingga proses generate PDF
 * sertifikat berjalan di background tanpa memblokir request HTTP.
 * Sangat penting untuk batch generation ratusan hingga ribuan sertifikat.
 *
 * Konfigurasi antrean di .env:
 *   QUEUE_CONNECTION=database   — menggunakan tabel 'jobs' di MySQL
 *   QUEUE_CONNECTION=redis      — menggunakan Redis (lebih cepat)
 *
 * Untuk menjalankan worker: php artisan queue:work --tries=3
 */
class GenerateCertificatePdfJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, Batchable;

    /**
     * Jumlah maksimal percobaan jika job gagal.
     * Setelah 3 kali gagal, job akan masuk ke tabel 'failed_jobs'.
     */
    public int $tries = 3;

    /**
     * Timeout per job dalam detik.
     * Generate satu PDF biasanya memakan waktu 2–5 detik tergantung kompleksitas template.
     */
    public int $timeout = 60;

    /**
     * Backoff strategy: jeda sebelum mencoba ulang (detik).
     * Array berarti: percobaan ke-1 tunggu 10 detik, ke-2 tunggu 30 detik.
     */
    public array $backoff = [10, 30];

    public function __construct(
        private readonly array $participantData,
        private readonly int   $issuedBy
    ) {}

    /**
     * Mengeksekusi job: membuat record sertifikat dan menghasilkan PDF-nya.
     *
     * @param  CertificateService  $certificateService  Di-inject otomatis oleh Laravel Container
     */
    public function handle(CertificateService $certificateService): void
    {
        // Jika job ini bagian dari batch dan batch sudah dibatalkan, skip
        if ($this->batch() && $this->batch()->cancelled()) {
            return;
        }

        try {
            // Buat record sertifikat di database
            $certificate = $certificateService->createCertificate(
                $this->participantData,
                $this->issuedBy
            );

            // Generate file PDF dan simpan ke storage
            $certificateService->generatePdf($certificate);

            // Update statistik import jika sertifikat ini bagian dari batch import
            if (!empty($this->participantData['import_id'])) {
                Import::where('id', $this->participantData['import_id'])
                    ->increment('success_rows');
            }

            Log::info("Sertifikat berhasil digenerate: #{$certificate->certificate_number}");

        } catch (\Throwable $e) {
            Log::error("Gagal generate sertifikat untuk [{$this->participantData['recipient_name']}]: {$e->getMessage()}");

            // Update failed_rows pada import
            if (!empty($this->participantData['import_id'])) {
                Import::where('id', $this->participantData['import_id'])
                    ->increment('failed_rows');
            }

            // Lempar ulang exception agar queue mencatat sebagai kegagalan
            throw $e;
        }
    }

    /**
     * Dipanggil ketika job gagal setelah semua percobaan habis.
     * Di sini kita bisa mengirim notifikasi ke admin.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("Job GenerateCertificatePdf gagal permanent: {$exception->getMessage()}", [
            'participant' => $this->participantData['recipient_name'] ?? 'unknown',
            'import_id'   => $this->participantData['import_id'] ?? null,
        ]);
    }
}
