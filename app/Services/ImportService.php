<?php

namespace App\Services;

use App\Models\{Import, Certificate, Event};
use App\Services\CertificateService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\{DB, Storage, Log};
use PhpOffice\PhpSpreadsheet\{IOFactory, Spreadsheet};
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * ImportService
 *
 * Menangani seluruh proses impor data peserta dari file Excel/CSV.
 * Alur kerja:
 * 1. Upload file ke storage
 * 2. Parsing file menggunakan PhpSpreadsheet
 * 3. Validasi setiap baris data
 * 4. Preview data sebelum konfirmasi import
 * 5. Eksekusi import dengan laporan error per baris
 *
 * Library: PhpOffice\PhpSpreadsheet (mendukung XLSX, XLS, CSV)
 */
class ImportService
{
    // Kolom wajib dalam file Excel yang diupload
    private const REQUIRED_COLUMNS = ['recipient_name'];

    // Pemetaan header Excel (case-insensitive) ke field database
    private const COLUMN_MAP = [
        'nama'                    => 'recipient_name',
        'nama lengkap'            => 'recipient_name',
        'recipient_name'          => 'recipient_name',
        'name'                    => 'recipient_name',
        'email'                   => 'recipient_email',
        'email peserta'           => 'recipient_email',
        'instansi'                => 'recipient_institution',
        'institusi'               => 'recipient_institution',
        'institution'             => 'recipient_institution',
        'no identitas'            => 'recipient_identity_number',
        'nomor identitas'         => 'recipient_identity_number',
        'nip'                     => 'recipient_identity_number',
        'nim'                     => 'recipient_identity_number',
        'recipient_identity_number' => 'recipient_identity_number',
    ];

    public function __construct(
        private readonly CertificateService $certificateService
    ) {}

    /**
     * Menyimpan file upload dan membuat record import di database.
     * File disimpan di storage/app/imports/{event_slug}/.
     *
     * @return Import  Record import yang baru dibuat
     */
    public function storeFile(UploadedFile $file, int $eventId, int $userId): Import
    {
        $event        = Event::findOrFail($eventId);
        $originalName = $file->getClientOriginalName();
        $storedName   = uniqid('import_') . '.' . $file->getClientOriginalExtension();
        $storedPath   = "imports/{$event->slug}/{$storedName}";

        Storage::put($storedPath, file_get_contents($file->getRealPath()));

        return Import::create([
            'user_id'           => $userId,
            'event_id'          => $eventId,
            'original_filename' => $originalName,
            'stored_filename'   => $storedPath,
            'status'            => 'pending',
        ]);
    }

    /**
     * Mem-parsing file Excel/CSV dan mengembalikan data preview.
     * Digunakan untuk halaman konfirmasi sebelum import benar-benar dieksekusi.
     *
     * @return array{headers: array, rows: array, total: int, errors: array}
     */
    public function preview(Import $import): array
    {
        [$spreadsheet, $worksheet] = $this->loadSpreadsheet($import->stored_filename);

        $headerRow   = $this->extractHeaderRow($worksheet);
        $mappedCols  = $this->mapColumns($headerRow);

        $rows   = [];
        $errors = [];

        // Iterasi baris data mulai dari baris ke-2 (baris pertama = header)
        $highestRow = $worksheet->getHighestRow();
        for ($rowIdx = 2; $rowIdx <= $highestRow; $rowIdx++) {
            $rowData = $this->extractRow($worksheet, $rowIdx, $mappedCols);

            if ($this->isEmptyRow($rowData)) {
                continue;
            }

            $rowErrors = $this->validateRow($rowData, $rowIdx);
            if (!empty($rowErrors)) {
                $errors[] = $rowErrors;
            }

            $rows[] = $rowData;
        }

        return [
            'headers' => array_values($mappedCols),
            'rows'    => $rows,
            'total'   => count($rows),
            'errors'  => $errors,
        ];
    }

    /**
     * Mengeksekusi proses import setelah pengguna mengkonfirmasi preview.
     * Sertifikat dibuat melalui CertificateService::batchCreate agar
     * setiap sertifikat diproses via antrean (Queue).
     *
     * @return array  Statistik hasil import
     */
    public function execute(Import $import, int $templateId, int $issuedBy): array
    {
        $import->update(['status' => 'processing', 'started_at' => now()]);

        try {
            $previewData  = $this->preview($import);
            $participants = $previewData['rows'];

            $import->update(['total_rows' => $previewData['total']]);

            $result = $this->certificateService->batchCreate(
                participants: $participants,
                eventId:      $import->event_id,
                templateId:   $templateId,
                issuedBy:     $issuedBy,
                importId:     $import->id,
            );

            $import->update([
                'status'       => 'completed',
                'success_rows' => $result['queued'],
                'failed_rows'  => $result['failed'],
                'error_report' => $result['errors'] ?: null,
                'completed_at' => now(),
            ]);

            return $result;

        } catch (\Throwable $e) {
            $import->update([
                'status'       => 'failed',
                'error_report' => [['message' => $e->getMessage()]],
                'completed_at' => now(),
            ]);

            Log::error("Import gagal [{$import->id}]: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Mendownload template Excel kosong dengan header yang benar.
     * Berguna agar pengguna tahu format yang diharapkan sistem.
     *
     * @return string  Path file Excel template yang siap diunduh
     */
    public function downloadTemplate(): string
    {
        $spreadsheet = new Spreadsheet();
        $sheet       = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Data Peserta');

        // Header row
        $headers = [
            'A1' => 'Nama Lengkap',
            'B1' => 'Email',
            'C1' => 'Instansi',
            'D1' => 'No Identitas (NIP/NIM/NIK)',
        ];
        foreach ($headers as $cell => $value) {
            $sheet->setCellValue($cell, $value);
        }

        // Style header
        $headerStyle = [
            'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill'      => ['fillType' => 'solid', 'startColor' => ['rgb' => '1a237e']],
            'alignment' => ['horizontal' => 'center'],
        ];
        $sheet->getStyle('A1:D1')->applyFromArray($headerStyle);

        // Lebar kolom
        foreach (['A' => 35, 'B' => 30, 'C' => 30, 'D' => 30] as $col => $width) {
            $sheet->getColumnDimension($col)->setWidth($width);
        }

        // Contoh data baris ke-2
        $sheet->fromArray(['Dr. Ahmad Santoso, M.Kom.', 'ahmad@usu.ac.id', 'Universitas Sumatera Utara', '198501012010011001'], 0, 'A2');

        $tmpPath = storage_path('app/tmp/template_peserta.xlsx');
        @mkdir(dirname($tmpPath), 0755, true);

        $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Xlsx');
        $writer->save($tmpPath);

        return $tmpPath;
    }

    // -----------------------------------------------------------------
    // PRIVATE HELPERS
    // -----------------------------------------------------------------

    private function loadSpreadsheet(string $storedPath): array
    {
        $fullPath    = Storage::path($storedPath);
        $reader      = IOFactory::createReaderForFile($fullPath);
        $reader->setReadDataOnly(true);
        $spreadsheet = $reader->load($fullPath);
        $worksheet   = $spreadsheet->getActiveSheet();

        return [$spreadsheet, $worksheet];
    }

    private function extractHeaderRow(Worksheet $sheet): array
    {
        $headers = [];
        $highestCol = $sheet->getHighestColumn();
        $highestColIdx = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestCol);

        for ($col = 1; $col <= $highestColIdx; $col++) {
            $cellVal = $sheet->getCellByColumnAndRow($col, 1)->getValue();
            $headers[$col] = strtolower(trim((string) $cellVal));
        }

        return $headers;
    }

    private function mapColumns(array $headerRow): array
    {
        $mapped = [];
        foreach ($headerRow as $colIdx => $header) {
            $dbField = self::COLUMN_MAP[$header] ?? null;
            if ($dbField) {
                $mapped[$colIdx] = $dbField;
            }
        }
        return $mapped;
    }

    private function extractRow(Worksheet $sheet, int $rowIdx, array $mappedCols): array
    {
        $data = [];
        foreach ($mappedCols as $colIdx => $fieldName) {
            $cellVal = $sheet->getCellByColumnAndRow($colIdx, $rowIdx)->getValue();
            $data[$fieldName] = trim((string) $cellVal);
        }
        return $data;
    }

    private function isEmptyRow(array $rowData): bool
    {
        return empty(array_filter($rowData, fn($v) => $v !== ''));
    }

    private function validateRow(array $rowData, int $rowIdx): ?array
    {
        $errors = [];

        if (empty($rowData['recipient_name'])) {
            $errors[] = "Nama lengkap wajib diisi";
        }

        if (!empty($rowData['recipient_email']) && !filter_var($rowData['recipient_email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Format email tidak valid: {$rowData['recipient_email']}";
        }

        // Cek duplikat email dalam sistem (opsional; bisa dinonaktifkan via setting)
        if (!empty($rowData['recipient_email'])) {
            $exists = Certificate::where('recipient_email', strtolower($rowData['recipient_email']))->exists();
            if ($exists) {
                $errors[] = "Email {$rowData['recipient_email']} sudah memiliki sertifikat di sistem";
            }
        }

        return $errors ? ['row' => $rowIdx, 'data' => $rowData, 'errors' => $errors] : null;
    }
}
