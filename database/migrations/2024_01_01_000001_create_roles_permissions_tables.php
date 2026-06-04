<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Roles, Permissions, dan Activity Logs
 * Digital Certificate Management System (DCMS)
 * Urutan eksekusi: 1 — harus dijalankan sebelum users
 */
return new class extends Migration
{
    public function up(): void
    {
        // =====================================================================
        // TABEL ROLES — empat peran utama sistem
        // =====================================================================
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50)->unique();          // super_admin, admin, operator, user
            $table->string('display_name', 100);
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // =====================================================================
        // TABEL PERMISSIONS — granular permission per modul
        // =====================================================================
        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->unique();         // certificates.create, templates.edit, dll
            $table->string('module', 50);                 // certificates, templates, events, users
            $table->string('action', 50);                 // create, read, update, delete, export
            $table->string('display_name', 150);
            $table->timestamps();
        });

        // =====================================================================
        // PIVOT: role_permission — many-to-many
        // =====================================================================
        Schema::create('role_permission', function (Blueprint $table) {
            $table->foreignId('role_id')->constrained()->cascadeOnDelete();
            $table->foreignId('permission_id')->constrained()->cascadeOnDelete();
            $table->primary(['role_id', 'permission_id']);
        });

        // =====================================================================
        // TABEL USERS — pengguna sistem
        // =====================================================================
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->foreignId('role_id')->constrained()->restrictOnDelete();
            $table->string('name', 150);
            $table->string('email', 200)->unique();
            $table->string('phone', 20)->nullable();
            $table->string('institution', 200)->nullable();
            $table->string('avatar')->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->string('remember_token', 100)->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('two_factor_enabled')->default(false);
            $table->string('two_factor_secret')->nullable();
            $table->timestamp('last_login_at')->nullable();
            $table->string('last_login_ip', 45)->nullable();
            $table->string('password_reset_token')->nullable();
            $table->timestamp('password_reset_expires_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['email', 'is_active']);
            $table->index('role_id');
        });

        // =====================================================================
        // TABEL LOGIN HISTORY — rekam jejak login
        // =====================================================================
        Schema::create('login_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('ip_address', 45);
            $table->text('user_agent')->nullable();
            $table->string('status', 20);               // success, failed, blocked
            $table->string('failure_reason', 100)->nullable();
            $table->timestamp('logged_in_at');
            $table->timestamp('logged_out_at')->nullable();

            $table->index(['user_id', 'logged_in_at']);
        });

        // =====================================================================
        // TABEL ACTIVITY LOGS — audit trail seluruh aksi
        // =====================================================================
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action', 100);              // created, updated, deleted, generated, verified
            $table->string('module', 100);              // certificate, template, event, user
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->string('subject_type', 100)->nullable();
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->text('description')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['user_id', 'created_at']);
            $table->index(['module', 'action']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
        Schema::dropIfExists('login_histories');
        Schema::dropIfExists('users');
        Schema::dropIfExists('role_permission');
        Schema::dropIfExists('permissions');
        Schema::dropIfExists('roles');
    }
};
