<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Events, Templates, Certificates, Verifications
 * Digital Certificate Management System (DCMS)
 * Urutan eksekusi: 2 — setelah users
 */
return new class extends Migration
{
    public function up(): void
    {
        // =====================================================================
        // TABEL EVENTS — manajemen kegiatan/acara
        // =====================================================================
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->string('name', 200);
            $table->string('slug', 250)->unique();
            $table->text('description')->nullable();
            $table->string('logo')->nullable();
            $table->string('organizer', 200)->nullable();
            $table->string('location', 300)->nullable();
            $table->date('event_date');
            $table->date('event_end_date')->nullable();
            $table->string('category', 100)->default('seminar'); // seminar, workshop, training, webinar
            $table->boolean('is_active')->default(true);
            $table->boolean('is_public')->default(false);       // apakah validasi publik tanpa login
            $table->json('meta')->nullable();                   // data tambahan fleksibel
            $table->timestamps();
            $table->softDeletes();

            $table->index(['is_active', 'event_date']);
            $table->index('created_by');
        });

        // =====================================================================
        // TABEL CERTIFICATE TEMPLATES — desain template sertifikat
        // =====================================================================
        Schema::create('certificate_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->string('name', 200);
            $table->text('description')->nullable();
            $table->string('category', 50)->default('general');  // general, seminar, training, award
            $table->string('orientation', 10)->default('landscape'); // landscape, portrait
            $table->unsignedSmallInteger('width_px')->default(2480);
            $table->unsignedSmallInteger('height_px')->default(1748);
            $table->string('background_file');                   // path file gambar/PDF background
            $table->string('background_type', 10)->default('image'); // image, pdf
            $table->json('fields_config');                       // konfigurasi posisi semua field
            /*
             * Contoh fields_config:
             * {
             *   "recipient_name": { "x": 960, "y": 620, "font": "Montserrat", "size": 52, "color": "#1a1a1a", "align": "center", "bold": true },
             *   "certificate_number": { "x": 960, "y": 720, "font": "Source Serif", "size": 18, "color": "#555", "align": "center" },
             *   "event_name": { "x": 960, "y": 480, "font": "Playfair Display", "size": 28, "color": "#2c3e50", "align": "center" },
             *   "event_date": { "x": 960, "y": 760, "font": "Montserrat", "size": 16, "color": "#777", "align": "center" },
             *   "qr_code": { "x": 1850, "y": 1200, "size": 200 }
             * }
             */
            $table->json('signature_config')->nullable();        // konfigurasi tanda tangan
            $table->boolean('is_active')->default(true);
            $table->boolean('is_system')->default(false);        // template bawaan sistem, tidak bisa dihapus
            $table->unsignedInteger('usage_count')->default(0);
            $table->string('thumbnail')->nullable();             // preview thumbnail
            $table->timestamps();
            $table->softDeletes();

            $table->index(['is_active', 'category']);
        });

        // =====================================================================
        // TABEL SIGNATURES — tanda tangan digital
        // =====================================================================
        Schema::create('signatures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('event_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name', 150);                        // nama pemilik tanda tangan
            $table->string('title', 200)->nullable();           // jabatan
            $table->string('signature_file');                   // path file tanda tangan
            $table->string('stamp_file')->nullable();           // path file stempel
            $table->boolean('is_default')->default(false);
            $table->timestamps();
            $table->softDeletes();
        });

        // =====================================================================
        // TABEL IMPORTS — rekam jejak import data peserta
        // =====================================================================
        Schema::create('imports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->foreignId('event_id')->constrained()->restrictOnDelete();
            $table->string('original_filename', 300);
            $table->string('stored_filename', 300);
            $table->string('status', 20)->default('pending');  // pending, processing, completed, failed
            $table->unsignedInteger('total_rows')->default(0);
            $table->unsignedInteger('success_rows')->default(0);
            $table->unsignedInteger('failed_rows')->default(0);
            $table->json('error_report')->nullable();           // detail baris yang gagal beserta alasan
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['event_id', 'status']);
        });

        // =====================================================================
        // TABEL CERTIFICATES — master data sertifikat
        // =====================================================================
        Schema::create('certificates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->restrictOnDelete();
            $table->foreignId('template_id')->constrained('certificate_templates')->restrictOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete(); // link ke akun jika ada
            $table->foreignId('issued_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('import_id')->nullable()->constrained()->nullOnDelete();

            // Data penerima sertifikat
            $table->string('recipient_name', 200);
            $table->string('recipient_email', 200)->nullable();
            $table->string('recipient_institution', 200)->nullable();
            $table->string('recipient_identity_number', 100)->nullable();   // NIP, NIM, NIK, dll

            // Identitas sertifikat
            $table->string('certificate_number', 100)->unique();
            $table->uuid('verification_uuid')->unique();                   // untuk URL verifikasi
            $table->string('verification_hash', 64)->unique();             // SHA-256 hash
            $table->string('verification_token', 64)->unique();            // token anti-tampering

            // Status dan metadata
            $table->string('status', 20)->default('active');   // active, revoked, expired
            $table->string('revoke_reason', 300)->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->foreignId('revoked_by')->nullable()->constrained('users')->nullOnDelete();

            // File output
            $table->string('pdf_file')->nullable();
            $table->string('image_file')->nullable();

            // Pengiriman
            $table->boolean('email_sent')->default(false);
            $table->timestamp('email_sent_at')->nullable();
            $table->boolean('whatsapp_sent')->default(false);
            $table->timestamp('whatsapp_sent_at')->nullable();

            // Statistik
            $table->unsignedInteger('download_count')->default(0);
            $table->unsignedInteger('verification_count')->default(0);
            $table->timestamp('last_downloaded_at')->nullable();
            $table->timestamp('issued_at')->useCurrent();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['event_id', 'status']);
            $table->index('recipient_email');
            $table->index('certificate_number');
            $table->index('verification_uuid');
        });

        // =====================================================================
        // TABEL CERTIFICATE_SIGNATURES — pivot: sertifikat dan tanda tangan
        // =====================================================================
        Schema::create('certificate_signatures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('certificate_id')->constrained()->cascadeOnDelete();
            $table->foreignId('signature_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('order')->default(1);   // urutan tampil di sertifikat
            $table->json('position')->nullable();               // override posisi jika berbeda dari template
            $table->timestamps();
        });

        // =====================================================================
        // TABEL VERIFICATION_LOGS — rekam jejak setiap verifikasi
        // =====================================================================
        Schema::create('verification_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('certificate_id')->constrained()->cascadeOnDelete();
            $table->string('method', 20)->default('qr');        // qr, manual_input, api
            $table->string('ip_address', 45);
            $table->text('user_agent')->nullable();
            $table->string('device_type', 50)->nullable();      // mobile, desktop, tablet
            $table->string('country', 100)->nullable();
            $table->string('city', 100)->nullable();
            $table->string('result', 10);                       // valid, invalid, revoked
            $table->timestamp('verified_at')->useCurrent();

            $table->index(['certificate_id', 'verified_at']);
            $table->index('ip_address');
        });

        // =====================================================================
        // TABEL CERTIFICATE_LOGS — riwayat perubahan status sertifikat
        // =====================================================================
        Schema::create('certificate_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('certificate_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action', 50);                       // generated, sent_email, downloaded, revoked
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['certificate_id', 'created_at']);
        });

        // =====================================================================
        // TABEL SETTINGS — konfigurasi aplikasi
        // =====================================================================
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key', 100)->unique();
            $table->longText('value')->nullable();
            $table->string('type', 20)->default('string');      // string, integer, boolean, json, file
            $table->string('group', 50)->default('general');    // general, mail, security, certificate, api
            $table->string('label', 200)->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_public')->default(false);       // apakah bisa diakses via API publik
            $table->timestamps();

            $table->index('group');
        });

        // =====================================================================
        // TABEL API_TOKENS — JWT token management untuk REST API
        // =====================================================================
        Schema::create('api_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name', 100);                        // nama token / deskripsi penggunaan
            $table->string('token', 64)->unique();
            $table->json('abilities')->nullable();              // scope / abilities token
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'token']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('api_tokens');
        Schema::dropIfExists('settings');
        Schema::dropIfExists('certificate_logs');
        Schema::dropIfExists('verification_logs');
        Schema::dropIfExists('certificate_signatures');
        Schema::dropIfExists('certificates');
        Schema::dropIfExists('imports');
        Schema::dropIfExists('signatures');
        Schema::dropIfExists('certificate_templates');
        Schema::dropIfExists('events');
    }
};
