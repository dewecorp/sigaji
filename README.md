# SIGaji - Sistem Informasi Gaji
## MI Sultan Fattah Sukosono

Aplikasi pembayaran gaji guru dan karyawan berbasis PHP dan MySQLi dengan template Bootstrap dan dashboard Stisla yang modern.

### Fitur Utama:
- Dashboard dengan widget statistik
- Manajemen Data Guru
- Manajemen Gaji Pokok
- Manajemen Tunjangan (dengan detail per guru)
- Manajemen Potongan (dengan detail per guru)
- Legger Gaji dengan kolom dinamis
- Riwayat Aktivitas dengan timeline
- Pengaturan Sistem
- Backup & Restore Database
- Manajemen Pengguna
- Cetak Legger dan Struk Gaji
- Export Excel dan PDF

### Teknologi:
- PHP 7.4+
- MySQLi
- Bootstrap 4
- DataTables
- SweetAlert2
- Toastr
- Font Awesome

### Instalasi:

1. Import database dari file `database/schema.sql`
2. Konfigurasi database di `config/database.php`
3. Buat folder berikut jika belum ada:
   - `assets/img/users/`
   - `assets/img/`
   - `backup/`
4. Set default password untuk admin: `admin123`
5. Akses aplikasi melalui browser

### Default Login:
- Username: `admin`
- Password: `admin123`

### Catatan:
- Semua aktivitas pengguna tercatat dan otomatis terhapus setelah 24 jam
- Kolom legger otomatis menyesuaikan dengan tunjangan dan potongan yang aktif
- 1 periode = 1 bulan = 1 legger gaji



