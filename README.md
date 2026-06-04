# Panduan Instalasi & Deployment — DCMS
## Digital Certificate Management System
**Laravel 11 · Bootstrap 5 · PHP 8.2+ · MySQL/MariaDB**

---

## PRASYARAT SISTEM

Sebelum memulai instalasi, pastikan lingkungan pengembangan Anda telah memenuhi spesifikasi berikut.

**XAMPP (versi terbaru):**
- Apache 2.4+
- PHP 8.2 atau lebih tinggi (cek dengan `php -v`)
- MySQL 8.0 / MariaDB 10.6+

**Tools tambahan:**
- Composer 2.x (`composer --version`)
- Node.js 18+ dan NPM (opsional, untuk asset build)
- Git (opsional)

**Ekstensi PHP wajib aktif** (cek di `php.ini`):
```
extension=pdo_mysql
extension=mbstring
extension=openssl
extension=fileinfo
extension=gd
extension=zip
extension=intl
extension=bcmath
extension=exif
```

---

## LANGKAH 1: PERSIAPAN PROJECT

### 1.1 Ekstrak / Clone Project

```bash
# Jika menggunakan Git
git clone https://github.com/fauzan-usu/dcms.git C:/xampp/htdocs/dcms

# Atau ekstrak arsip ZIP ke:
# C:/xampp/htdocs/dcms
```

### 1.2 Masuk ke Direktori Project

```bash
cd C:/xampp/htdocs/dcms
```

### 1.3 Install Dependensi PHP via Composer

```bash
composer install --optimize-autoloader
```

Proses ini akan mengunduh semua paket yang tercantum di `composer.json`, termasuk:
- Laravel 11 (framework utama)
- endroid/qr-code (generate QR Code)
- dompdf/dompdf (render PDF sertifikat)
- phpoffice/phpspreadsheet (baca/tulis Excel)

Estimasi waktu: 2–5 menit tergantung koneksi internet.

---

## LANGKAH 2: KONFIGURASI ENVIRONMENT

### 2.1 Buat File .env

```bash
cp .env.example .env
```

### 2.2 Generate Application Key

```bash
php artisan key:generate
```

### 2.3 Edit File .env

Buka file `.env` dengan teks editor dan sesuaikan konfigurasi berikut:

```env
APP_NAME="Digital Certificate Management System"
APP_URL=http://localhost/dcms

DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=dcms
DB_USERNAME=root
DB_PASSWORD=           # kosong jika XAMPP default tanpa password

MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=email_anda@gmail.com
MAIL_PASSWORD=app_password_gmail
MAIL_FROM_ADDRESS=noreply@dcms.test

QUEUE_CONNECTION=database   # ganti sync → database untuk batch import
```

**Catatan penting untuk Gmail:** Gunakan "App Password" (bukan password biasa). Buat di: Google Account → Security → 2-Step Verification → App passwords.

---

## LANGKAH 3: SETUP DATABASE

### 3.1 Buat Database

Buka **phpMyAdmin** (`http://localhost/phpmyadmin`) lalu jalankan:

```sql
CREATE DATABASE dcms
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;
```

Atau via command line:

```bash
mysql -u root -e "CREATE DATABASE dcms CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

### 3.2 Jalankan Migration

```bash
php artisan migrate
```

Migration akan membuat semua tabel: `roles`, `permissions`, `users`, `events`, `certificate_templates`, `certificates`, `verification_logs`, `settings`, dan tabel pendukung lainnya.

### 3.3 Jalankan Seeder (Data Awal)

```bash
php artisan db:seed
```

Seeder akan membuat:
- 4 role (super_admin, admin, operator, user)
- Seluruh permission granular
- 4 user demo (lihat Tabel Akun Demo di bawah)
- 3 template sertifikat sistem
- 2 event contoh
- 5 sertifikat demo

### Tabel Akun Demo

| Role        | Email                      | Password        |
|-------------|----------------------------|-----------------|
| Super Admin | superadmin@dcms.test       | SuperAdmin@2024!|
| Admin       | admin@dcms.test            | Admin@2024!     |
| Operator    | operator@dcms.test         | Operator@2024!  |
| User        | peserta@dcms.test          | Peserta@2024!   |

> **Penting:** Segera ganti password akun Super Admin setelah instalasi pertama.

---

## LANGKAH 4: KONFIGURASI STORAGE

### 4.1 Buat Symbolic Link Storage

```bash
php artisan storage:link
```

Perintah ini membuat link dari `public/storage` ke `storage/app/public` agar file yang diupload (template, QR Code, PDF sertifikat) bisa diakses via URL.

### 4.2 Pastikan Permission Folder (Linux/Mac)

```bash
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```

Di Windows dengan XAMPP, hal ini biasanya tidak diperlukan.

---

## LANGKAH 5: KONFIGURASI APACHE (XAMPP)

### 5.1 Aktifkan mod_rewrite

Buka `C:\xampp\apache\conf\httpd.conf`, cari dan aktifkan:

```apache
LoadModule rewrite_module modules/mod_rewrite.so
```

### 5.2 Konfigurasi Virtual Host (Opsional tapi Direkomendasikan)

Tambahkan ke `C:\xampp\apache\conf\extra\httpd-vhosts.conf`:

```apache
<VirtualHost *:80>
    DocumentRoot "C:/xampp/htdocs/dcms/public"
    ServerName dcms.local
    <Directory "C:/xampp/htdocs/dcms/public">
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

Tambahkan ke `C:\Windows\System32\drivers\etc\hosts`:
```
127.0.0.1    dcms.local
```

Restart Apache, lalu akses via `http://dcms.local`.

### 5.3 Tanpa Virtual Host

Akses langsung via `http://localhost/dcms/public`. Pastikan file `.htaccess` di folder `public` sudah ada.

---

## LANGKAH 6: SETUP QUEUE (Untuk Batch Import)

Queue diperlukan agar proses generate sertifikat massal berjalan di background.

### 6.1 Buat Tabel Queue

```bash
php artisan queue:table
php artisan migrate
```

### 6.2 Jalankan Queue Worker

```bash
# Untuk pengembangan
php artisan queue:work --tries=3 --timeout=60

# Untuk production (gunakan Supervisor)
php artisan queue:work --daemon --tries=3 --timeout=60
```

**Di Windows (XAMPP):** Buka CMD baru, masuk ke direktori project, lalu jalankan perintah di atas. Biarkan window CMD tetap berjalan selama aplikasi digunakan.

---

## LANGKAH 7: VERIFIKASI INSTALASI

Buka browser dan akses:

1. **Halaman Login:** `http://localhost/dcms/public` → harus menampilkan halaman login
2. **Dashboard Admin:** Login dengan akun Super Admin → seharusnya tampil dashboard
3. **Halaman Verifikasi Publik:** `http://localhost/dcms/public/verify/check` → tanpa login

---

## PANDUAN DEPLOYMENT KE HOSTING/VPS

### Langkah Deployment di cPanel/Shared Hosting

```bash
# 1. Upload semua file ke folder project (misal: /public_html/dcms)
# 2. Isi konten folder 'public' ke root domain/subdomain

# 3. Edit index.php di root domain:
# Ubah path ke: require __DIR__.'/../dcms/public/index.php';

# 4. Atau gunakan .htaccess di root:
```

```apache
RewriteEngine On
RewriteRule ^(.*)$ /dcms/public/$1 [L]
```

```bash
# 5. Set environment variables di .env (mode production):
APP_ENV=production
APP_DEBUG=false
APP_URL=https://domain-anda.com

# 6. Jalankan optimisasi:
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize
```

### Langkah Deployment di VPS (Ubuntu/Nginx)

```bash
# Install Nginx, PHP 8.2, MySQL
sudo apt update
sudo apt install nginx php8.2-fpm php8.2-mysql php8.2-mbstring php8.2-xml \
    php8.2-curl php8.2-gd php8.2-zip php8.2-bcmath php8.2-intl mysql-server

# Clone project
cd /var/www
git clone https://github.com/yourrepo/dcms.git
cd dcms

# Install dependencies
composer install --no-dev --optimize-autoloader

# Setup
cp .env.example .env
php artisan key:generate
php artisan migrate --force
php artisan db:seed --force
php artisan storage:link
php artisan optimize

# Nginx config
sudo nano /etc/nginx/sites-available/dcms
```

Konfigurasi Nginx:

```nginx
server {
    listen 80;
    server_name domain-anda.com www.domain-anda.com;
    root /var/www/dcms/public;
    index index.php;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

```bash
# Aktifkan site
sudo ln -s /etc/nginx/sites-available/dcms /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx

# Setup Supervisor untuk queue worker
sudo apt install supervisor
sudo nano /etc/supervisor/conf.d/dcms-worker.conf
```

Konfigurasi Supervisor:

```ini
[program:dcms-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/dcms/artisan queue:work --tries=3 --timeout=60
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/www/dcms/storage/logs/worker.log
```

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start dcms-worker:*
```

---

## BACKUP DATABASE OTOMATIS

Tambahkan di `app/Console/Kernel.php`:

```php
$schedule->command('backup:run')->dailyAt('02:00');
```

Atau gunakan cron job manual:

```bash
# Jalankan: crontab -e
# Tambahkan:
0 2 * * * mysqldump -u root dcms | gzip > /backup/dcms_$(date +\%Y\%m\%d).sql.gz
```

---

## TROUBLESHOOTING

**Error: "No application encryption key has been specified"**
```bash
php artisan key:generate
```

**Error: "Class ... not found" setelah menambah file baru**
```bash
composer dump-autoload
```

**Error 500 setelah deploy**
```bash
php artisan config:clear
php artisan cache:clear
php artisan view:clear
tail -f storage/logs/laravel.log
```

**File upload gagal / storage tidak dapat diakses**
```bash
php artisan storage:link
chmod -R 775 storage bootstrap/cache
```

**QR Code tidak muncul di PDF**
- Pastikan ekstensi `gd` aktif di `php.ini`
- Pastikan `storage:link` sudah dijalankan

**Batch import lambat / timeout**
- Pastikan `QUEUE_CONNECTION=database` di `.env`
- Jalankan `php artisan queue:work` di terminal terpisah

---

## INFORMASI KONTAK & DUKUNGAN

Untuk pertanyaan teknis dan dukungan instalasi, silakan merujuk ke dokumentasi resmi:
- Laravel: https://laravel.com/docs
- endroid/qr-code: https://github.com/endroid/qr-code
- DomPDF: https://github.com/dompdf/dompdf
- PhpSpreadsheet: https://phpspreadsheet.readthedocs.io

---

*Dokumen ini dibuat untuk DCMS v1.0.0 | Laravel 11 | PHP 8.2+*
