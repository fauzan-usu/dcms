<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\{DB, Hash};
use Illuminate\Support\Str;
use Carbon\Carbon;

/**
 * DatabaseSeeder — DCMS (Digital Certificate Management System)
 *
 * Menjalankan seluruh seeder dalam urutan yang benar agar relasi
 * antar-tabel tidak mengalami foreign key constraint violation.
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Nonaktifkan foreign key sementara agar truncate aman saat re-seed
        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        $this->call([
            RoleSeeder::class,
            PermissionSeeder::class,
            UserSeeder::class,
            SettingSeeder::class,
            EventSeeder::class,
            TemplateSeeder::class,
            CertificateSeeder::class,
        ]);

        DB::statement('SET FOREIGN_KEY_CHECKS=1');
    }
}

// =====================================================================
// ROLE SEEDER
// =====================================================================
class RoleSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('roles')->truncate();

        $roles = [
            [
                'id'           => 1,
                'name'         => 'super_admin',
                'display_name' => 'Super Administrator',
                'description'  => 'Akses penuh ke seluruh sistem, tidak dibatasi.',
                'is_active'    => true,
                'created_at'   => now(),
                'updated_at'   => now(),
            ],
            [
                'id'           => 2,
                'name'         => 'admin',
                'display_name' => 'Administrator',
                'description'  => 'Mengelola sertifikat, template, event, dan pengguna.',
                'is_active'    => true,
                'created_at'   => now(),
                'updated_at'   => now(),
            ],
            [
                'id'           => 3,
                'name'         => 'operator',
                'display_name' => 'Operator',
                'description'  => 'Input data peserta dan generate sertifikat.',
                'is_active'    => true,
                'created_at'   => now(),
                'updated_at'   => now(),
            ],
            [
                'id'           => 4,
                'name'         => 'user',
                'display_name' => 'User / Peserta',
                'description'  => 'Melihat dan mengunduh sertifikat milik sendiri.',
                'is_active'    => true,
                'created_at'   => now(),
                'updated_at'   => now(),
            ],
        ];

        DB::table('roles')->insert($roles);
    }
}

// =====================================================================
// PERMISSION SEEDER
// =====================================================================
class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('role_permission')->truncate();
        DB::table('permissions')->truncate();

        // Mendefinisikan seluruh permission yang ada di sistem
        $modules = [
            'certificates' => ['create', 'read', 'update', 'delete', 'generate', 'revoke', 'export', 'send'],
            'templates'    => ['create', 'read', 'update', 'delete'],
            'events'       => ['create', 'read', 'update', 'delete'],
            'users'        => ['create', 'read', 'update', 'delete', 'manage_roles'],
            'reports'      => ['read', 'export'],
            'settings'     => ['read', 'update'],
            'api'          => ['access'],
        ];

        $permissions = [];
        $id = 1;
        foreach ($modules as $module => $actions) {
            foreach ($actions as $action) {
                $permissions[] = [
                    'id'           => $id++,
                    'name'         => "{$module}.{$action}",
                    'module'       => $module,
                    'action'       => $action,
                    'display_name' => ucfirst($action) . ' ' . ucfirst($module),
                    'created_at'   => now(),
                    'updated_at'   => now(),
                ];
            }
        }

        DB::table('permissions')->insert($permissions);

        // Penetapan permission per role
        // Super Admin mendapat semua permission
        $allIds = range(1, count($permissions));
        foreach ($allIds as $permId) {
            DB::table('role_permission')->insert(['role_id' => 1, 'permission_id' => $permId]);
        }

        // Admin: semua kecuali users.manage_roles dan settings
        $adminExclude = ['users.manage_roles', 'settings.update'];
        $adminPerms = collect($permissions)
            ->whereNotIn('name', $adminExclude)
            ->pluck('id');
        foreach ($adminPerms as $permId) {
            DB::table('role_permission')->insert(['role_id' => 2, 'permission_id' => $permId]);
        }

        // Operator: operasional sertifikat dan event (read only untuk template)
        $operatorAllow = [
            'certificates.create', 'certificates.read', 'certificates.generate',
            'certificates.export', 'certificates.send',
            'templates.read',
            'events.read',
            'reports.read',
        ];
        $operatorPerms = collect($permissions)->whereIn('name', $operatorAllow)->pluck('id');
        foreach ($operatorPerms as $permId) {
            DB::table('role_permission')->insert(['role_id' => 3, 'permission_id' => $permId]);
        }

        // User: hanya baca sertifikat sendiri
        $userPerms = collect($permissions)->where('name', 'certificates.read')->pluck('id');
        foreach ($userPerms as $permId) {
            DB::table('role_permission')->insert(['role_id' => 4, 'permission_id' => $permId]);
        }
    }
}

// =====================================================================
// USER SEEDER
// =====================================================================
class UserSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('users')->truncate();

        DB::table('users')->insert([
            [
                'id'          => 1,
                'role_id'     => 1,
                'name'        => 'Super Administrator',
                'email'       => 'superadmin@dcms.test',
                'password'    => Hash::make('SuperAdmin@2024!'),
                'is_active'   => true,
                'created_at'  => now(),
                'updated_at'  => now(),
            ],
            [
                'id'          => 2,
                'role_id'     => 2,
                'name'        => 'Administrator DCMS',
                'email'       => 'admin@dcms.test',
                'institution' => 'Universitas Sumatera Utara',
                'password'    => Hash::make('Admin@2024!'),
                'is_active'   => true,
                'created_at'  => now(),
                'updated_at'  => now(),
            ],
            [
                'id'          => 3,
                'role_id'     => 3,
                'name'        => 'Operator Sertifikat',
                'email'       => 'operator@dcms.test',
                'institution' => 'Universitas Sumatera Utara',
                'password'    => Hash::make('Operator@2024!'),
                'is_active'   => true,
                'created_at'  => now(),
                'updated_at'  => now(),
            ],
            [
                'id'          => 4,
                'role_id'     => 4,
                'name'        => 'Demo Peserta',
                'email'       => 'peserta@dcms.test',
                'password'    => Hash::make('Peserta@2024!'),
                'is_active'   => true,
                'created_at'  => now(),
                'updated_at'  => now(),
            ],
        ]);
    }
}

// =====================================================================
// SETTING SEEDER
// =====================================================================
class SettingSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('settings')->truncate();

        $settings = [
            // Grup: general
            ['key' => 'app_name',               'value' => 'Digital Certificate Management System', 'type' => 'string',  'group' => 'general', 'label' => 'Nama Aplikasi',                  'is_public' => true],
            ['key' => 'app_logo',               'value' => null,                                     'type' => 'file',    'group' => 'general', 'label' => 'Logo Aplikasi',                  'is_public' => true],
            ['key' => 'app_url',                'value' => 'http://localhost/dcms',                  'type' => 'string',  'group' => 'general', 'label' => 'URL Aplikasi',                   'is_public' => true],
            ['key' => 'app_timezone',           'value' => 'Asia/Jakarta',                           'type' => 'string',  'group' => 'general', 'label' => 'Zona Waktu'],
            ['key' => 'app_locale',             'value' => 'id',                                     'type' => 'string',  'group' => 'general', 'label' => 'Bahasa'],
            ['key' => 'institution_name',       'value' => 'Universitas Sumatera Utara',             'type' => 'string',  'group' => 'general', 'label' => 'Nama Institusi',                 'is_public' => true],
            ['key' => 'institution_address',    'value' => 'Jl. Dr. T. Mansur No.9, Padang Bulan, Medan Baru', 'type' => 'string', 'group' => 'general', 'label' => 'Alamat Institusi'],

            // Grup: certificate
            ['key' => 'cert_number_prefix',     'value' => 'CERT',                                  'type' => 'string',  'group' => 'certificate', 'label' => 'Prefix Nomor Sertifikat'],
            ['key' => 'cert_number_format',     'value' => '{PREFIX}/{YEAR}/{MONTH}/{SEQ}',          'type' => 'string',  'group' => 'certificate', 'label' => 'Format Nomor Sertifikat'],
            ['key' => 'cert_sequence_length',   'value' => '5',                                     'type' => 'integer', 'group' => 'certificate', 'label' => 'Panjang Nomor Urut'],
            ['key' => 'cert_default_template',  'value' => '1',                                     'type' => 'integer', 'group' => 'certificate', 'label' => 'ID Template Default'],
            ['key' => 'cert_verify_base_url',   'value' => 'http://localhost/dcms/verify',          'type' => 'string',  'group' => 'certificate', 'label' => 'URL Verifikasi Sertifikat', 'is_public' => true],
            ['key' => 'cert_qr_size',           'value' => '200',                                   'type' => 'integer', 'group' => 'certificate', 'label' => 'Ukuran QR Code (px)'],
            ['key' => 'cert_auto_send_email',   'value' => '0',                                     'type' => 'boolean', 'group' => 'certificate', 'label' => 'Otomatis Kirim Email'],

            // Grup: mail
            ['key' => 'mail_driver',            'value' => 'smtp',                                  'type' => 'string',  'group' => 'mail', 'label' => 'Mail Driver'],
            ['key' => 'mail_host',              'value' => 'smtp.gmail.com',                        'type' => 'string',  'group' => 'mail', 'label' => 'SMTP Host'],
            ['key' => 'mail_port',              'value' => '587',                                   'type' => 'integer', 'group' => 'mail', 'label' => 'SMTP Port'],
            ['key' => 'mail_username',          'value' => '',                                      'type' => 'string',  'group' => 'mail', 'label' => 'SMTP Username'],
            ['key' => 'mail_password',          'value' => '',                                      'type' => 'string',  'group' => 'mail', 'label' => 'SMTP Password'],
            ['key' => 'mail_from_address',      'value' => 'noreply@dcms.test',                     'type' => 'string',  'group' => 'mail', 'label' => 'From Address'],
            ['key' => 'mail_from_name',         'value' => 'DCMS - Digital Certificate',            'type' => 'string',  'group' => 'mail', 'label' => 'From Name'],

            // Grup: security
            ['key' => 'login_max_attempts',     'value' => '5',                                     'type' => 'integer', 'group' => 'security', 'label' => 'Maks. Percobaan Login'],
            ['key' => 'login_lockout_minutes',  'value' => '15',                                    'type' => 'integer', 'group' => 'security', 'label' => 'Durasi Lockout (menit)'],
            ['key' => 'session_timeout_minutes','value' => '120',                                   'type' => 'integer', 'group' => 'security', 'label' => 'Session Timeout (menit)'],
            ['key' => 'recaptcha_enabled',      'value' => '0',                                     'type' => 'boolean', 'group' => 'security', 'label' => 'Aktifkan reCAPTCHA'],
            ['key' => 'recaptcha_site_key',     'value' => '',                                      'type' => 'string',  'group' => 'security', 'label' => 'reCAPTCHA Site Key'],
            ['key' => 'recaptcha_secret_key',   'value' => '',                                      'type' => 'string',  'group' => 'security', 'label' => 'reCAPTCHA Secret Key'],
            ['key' => '2fa_enabled',            'value' => '0',                                     'type' => 'boolean', 'group' => 'security', 'label' => 'Aktifkan 2FA'],
        ];

        foreach ($settings as &$s) {
            $s['is_public'] = $s['is_public'] ?? false;
            $s['created_at'] = now();
            $s['updated_at'] = now();
        }

        DB::table('settings')->insert($settings);
    }
}

// =====================================================================
// EVENT SEEDER
// =====================================================================
class EventSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('events')->truncate();

        DB::table('events')->insert([
            [
                'created_by'   => 2,
                'name'         => 'Seminar Nasional Kecerdasan Buatan 2024',
                'slug'         => 'semnas-ai-2024',
                'description'  => 'Seminar nasional yang membahas perkembangan terkini AI di Indonesia.',
                'organizer'    => 'Universitas Sumatera Utara',
                'location'     => 'Aula Utama USU, Medan',
                'event_date'   => '2024-08-15',
                'category'     => 'seminar',
                'is_active'    => true,
                'is_public'    => true,
                'created_at'   => now(),
                'updated_at'   => now(),
            ],
            [
                'created_by'   => 2,
                'name'         => 'Workshop Laravel untuk Pemula',
                'slug'         => 'workshop-laravel-2024',
                'description'  => 'Workshop intensif pengembangan web menggunakan Laravel framework.',
                'organizer'    => 'FASILKOMTI USU',
                'location'     => 'Lab Komputer FASILKOMTI',
                'event_date'   => '2024-09-10',
                'event_end_date' => '2024-09-12',
                'category'     => 'workshop',
                'is_active'    => true,
                'is_public'    => true,
                'created_at'   => now(),
                'updated_at'   => now(),
            ],
        ]);
    }
}

// =====================================================================
// TEMPLATE SEEDER
// =====================================================================
class TemplateSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('certificate_templates')->truncate();

        $defaultFieldsLandscape = [
            'recipient_name'      => ['x' => 1240, 'y' => 580, 'font' => 'Playfair Display', 'size' => 58, 'color' => '#1a237e', 'align' => 'center', 'bold' => true],
            'certificate_number'  => ['x' => 1240, 'y' => 680, 'font' => 'Montserrat', 'size' => 16, 'color' => '#757575', 'align' => 'center', 'bold' => false],
            'event_name'          => ['x' => 1240, 'y' => 450, 'font' => 'Montserrat', 'size' => 24, 'color' => '#37474f', 'align' => 'center', 'bold' => true],
            'event_date'          => ['x' => 1240, 'y' => 740, 'font' => 'Montserrat', 'size' => 18, 'color' => '#546e7a', 'align' => 'center', 'bold' => false],
            'institution'         => ['x' => 1240, 'y' => 390, 'font' => 'Montserrat', 'size' => 20, 'color' => '#455a64', 'align' => 'center', 'bold' => false],
            'qr_code'             => ['x' => 2200, 'y' => 1400, 'size' => 220],
        ];

        DB::table('certificate_templates')->insert([
            [
                'created_by'       => 1,
                'name'             => 'Classic Blue Landscape',
                'description'      => 'Template profesional biru klasik untuk seminar dan konferensi.',
                'category'         => 'seminar',
                'orientation'      => 'landscape',
                'width_px'         => 2480,
                'height_px'        => 1748,
                'background_file'  => 'system/templates/classic_blue_landscape.png',
                'background_type'  => 'image',
                'fields_config'    => json_encode($defaultFieldsLandscape),
                'is_active'        => true,
                'is_system'        => true,
                'created_at'       => now(),
                'updated_at'       => now(),
            ],
            [
                'created_by'       => 1,
                'name'             => 'Gold Prestige Landscape',
                'description'      => 'Template mewah dengan aksen emas untuk penghargaan bergengsi.',
                'category'         => 'award',
                'orientation'      => 'landscape',
                'width_px'         => 2480,
                'height_px'        => 1748,
                'background_file'  => 'system/templates/gold_prestige_landscape.png',
                'background_type'  => 'image',
                'fields_config'    => json_encode(array_merge($defaultFieldsLandscape, [
                    'recipient_name' => ['x' => 1240, 'y' => 580, 'font' => 'Cormorant Garamond', 'size' => 64, 'color' => '#b8860b', 'align' => 'center', 'bold' => true],
                ])),
                'is_active'        => true,
                'is_system'        => true,
                'created_at'       => now(),
                'updated_at'       => now(),
            ],
            [
                'created_by'       => 1,
                'name'             => 'Modern Minimal Portrait',
                'description'      => 'Template minimalis modern cocok untuk pelatihan dan workshop.',
                'category'         => 'training',
                'orientation'      => 'portrait',
                'width_px'         => 1748,
                'height_px'        => 2480,
                'background_file'  => 'system/templates/modern_minimal_portrait.png',
                'background_type'  => 'image',
                'fields_config'    => json_encode([
                    'recipient_name'     => ['x' => 874, 'y' => 1100, 'font' => 'DM Serif Display', 'size' => 54, 'color' => '#212121', 'align' => 'center', 'bold' => true],
                    'certificate_number' => ['x' => 874, 'y' => 1200, 'font' => 'DM Sans', 'size' => 16, 'color' => '#9e9e9e', 'align' => 'center', 'bold' => false],
                    'event_name'         => ['x' => 874, 'y' => 980,  'font' => 'DM Sans', 'size' => 22, 'color' => '#424242', 'align' => 'center', 'bold' => true],
                    'event_date'         => ['x' => 874, 'y' => 1270, 'font' => 'DM Sans', 'size' => 17, 'color' => '#757575', 'align' => 'center', 'bold' => false],
                    'qr_code'            => ['x' => 1450, 'y' => 2150, 'size' => 200],
                ]),
                'is_active'        => true,
                'is_system'        => true,
                'created_at'       => now(),
                'updated_at'       => now(),
            ],
        ]);
    }
}

// =====================================================================
// CERTIFICATE SEEDER — demo data sertifikat
// =====================================================================
class CertificateSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('certificates')->truncate();

        $demoParticipants = [
            ['Dr. Ahmad Fauzi, M.Kom.',    'ahmad.fauzi@usu.ac.id',        'Universitas Sumatera Utara'],
            ['Siti Rahma Dewi, S.Pd.',     'siti.rahma@example.com',       'SMA Negeri 1 Medan'],
            ['Budi Santoso, S.T.',         'budi.santoso@industry.co.id',  'PT. Maju Bersama Indonesia'],
            ['Dr. Lestari Wulandari',      'lestari.w@usu.ac.id',          'Universitas Sumatera Utara'],
            ['Muhammad Rizki, S.Kom.',     'mrizki@email.com',             'Politeknik Negeri Medan'],
        ];

        $baseDate = Carbon::now()->subDays(30);

        foreach ($demoParticipants as $i => [$name, $email, $inst]) {
            $uuid  = Str::uuid()->toString();
            $hash  = hash('sha256', $uuid . $name . $email);
            $token = Str::random(64);
            $seq   = str_pad($i + 1, 5, '0', STR_PAD_LEFT);
            $certNo = "CERT/2024/08/{$seq}";

            DB::table('certificates')->insert([
                'event_id'                  => 1,
                'template_id'               => 1,
                'issued_by'                 => 2,
                'recipient_name'            => $name,
                'recipient_email'           => $email,
                'recipient_institution'     => $inst,
                'certificate_number'        => $certNo,
                'verification_uuid'         => $uuid,
                'verification_hash'         => $hash,
                'verification_token'        => $token,
                'status'                    => 'active',
                'issued_at'                 => $baseDate->addDays($i)->toDateTimeString(),
                'created_at'                => now(),
                'updated_at'                => now(),
            ]);
        }
    }
}
