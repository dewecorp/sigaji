# Panduan Instalasi SIGaji

## Persyaratan Sistem
- PHP 7.4 atau lebih tinggi
- MySQL 5.7 atau lebih tinggi / MariaDB 10.2 atau lebih tinggi
- Web Server (Apache/Nginx)
- Extension PHP: mysqli, mbstring, gd

## Langkah Instalasi

### 1. Upload File
Upload semua file aplikasi ke direktori web server Anda (contoh: `htdocs` atau `www`)

### 2. Buat Database
Buat database baru dengan nama `sistem_gaji` atau sesuaikan dengan kebutuhan Anda.

### 3. Import Database
- Buka phpMyAdmin atau tool database management lainnya
- Pilih database yang telah dibuat
- Import file `database/schema.sql`

### 4. Konfigurasi Database
Edit file `config/database.php` dan sesuaikan dengan konfigurasi database Anda:
```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'sistem_gaji');
```

### 5. Konfigurasi Base URL
Edit file `config/config.php` dan sesuaikan BASE_URL:
```php
define('BASE_URL', 'http://localhost/sistem_gaji/');
```

### 6. Buat Direktori
Pastikan direktori berikut ada dan memiliki permission write:
- `assets/img/users/` - untuk foto pengguna
- `assets/img/` - untuk logo madrasah
- `backup/` - untuk file backup database

### 7. Set Permission (Linux/Mac)
```bash
chmod -R 755 assets/img/users
chmod -R 755 assets/img
chmod -R 755 backup
```

### 8. Akses Aplikasi
Buka browser dan akses:
```
http://localhost/sistem_gaji
```

### 9. Login Default
- **Username:** admin
- **Password:** admin123

**PENTING:** Segera ubah password default setelah login pertama kali!

## Troubleshooting

### Error: "Connection failed"
- Pastikan konfigurasi database di `config/database.php` sudah benar
- Pastikan MySQL service berjalan
- Pastikan database sudah dibuat

### Error: "Permission denied"
- Pastikan direktori `assets/img/users`, `assets/img`, dan `backup` memiliki permission write
- Di Linux/Mac, gunakan `chmod -R 755` pada direktori tersebut

### Error: "Table doesn't exist"
- Pastikan database sudah di-import dengan benar
- Cek apakah semua tabel sudah ada di database

### Halaman Blank
- Aktifkan error reporting di PHP untuk melihat error detail
- Cek file log error PHP
- Pastikan semua file sudah ter-upload dengan lengkap

## Support
Untuk bantuan lebih lanjut, silakan hubungi developer atau buat issue di repository.



