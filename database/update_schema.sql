-- Update Schema untuk SIGaji
-- Jalankan script ini jika database sudah ada

USE sistem_gaji;

-- Pastikan role hanya admin dan bendahara
ALTER TABLE users MODIFY COLUMN role ENUM('admin', 'bendahara') DEFAULT 'bendahara';

-- Kolom keterangan tetap ada di tabel potongan dan tunjangan untuk catatan
-- Tapi di tampilan, kolom keterangan diganti dengan menampilkan jumlah total
-- (Tidak perlu perubahan struktur tabel, hanya perubahan query di PHP)

-- Jika perlu menghapus kolom keterangan (opsional, tidak disarankan karena masih berguna untuk catatan):
-- ALTER TABLE potongan DROP COLUMN keterangan;
-- ALTER TABLE tunjangan DROP COLUMN keterangan;

-- Update password admin jika perlu
UPDATE users 
SET password = '$2y$10$N9qo8uLOickgx2ZMRZoMyeIjZAgcfl7p92ldGxad68LJZdL17lhWy' 
WHERE username = 'admin';



