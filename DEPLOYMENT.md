# Deployment (Hosting)

## Minimal requirement
- PHP 7.4+ (disarankan 8.x)
- MySQL/MariaDB
- Apache + .htaccess (atau Nginx dengan rule rewrite setara)
- HTTPS aktif

## Checklist keamanan
- Ubah password default setelah install pertama.
- Pastikan `display_errors` = Off di production, dan error dicatat di log server.
- Pastikan folder berikut tidak bisa diakses langsung via browser:
  - `/config/`
  - `/vendor/`
  - `/database/`
  - `/backup/`
- Pastikan folder upload tidak bisa mengeksekusi file PHP:
  - `/assets/img/`
  - `/assets/img/users/`
- Batasi akses ke halaman admin (buat user admin/bendahara seperlunya).
- Gunakan HTTPS agar cookie session aman.

## Checklist operasional
- Set permission folder write:
  - `/backup/` (untuk backup database)
  - `/assets/img/` dan `/assets/img/users/` (untuk upload logo/foto)
- Pastikan `BASE_URL` sesuai domain saat diakses (aplikasi mengikuti host + base path secara otomatis).
- Pastikan rule rewrite aktif:
  - Apache: `AllowOverride All` untuk folder aplikasi, dan module rewrite aktif.
  - Nginx: buat rule agar `/pages/guru` mengarah ke `/pages/guru/index.php` dan URL tanpa `.php` diarahkan ke file `.php` jika ada.

## Catatan Nginx (jika tidak pakai Apache)
Konsep yang harus ditiru:
- URL tanpa `.php` harus mencoba file `.php` yang sesuai (mis. `/pages/dashboard` → `/pages/dashboard.php`).
- Akses langsung ke `/config/`, `/vendor/`, `/database/`, `/backup/` harus ditolak (403).
