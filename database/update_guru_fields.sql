-- Script untuk update tabel guru
-- Hapus kolom lama dan tambah kolom baru
-- Jalankan script ini di phpMyAdmin atau MySQL client

USE sistem_gaji;

-- Hapus kolom lama
ALTER TABLE guru DROP COLUMN IF EXISTS tempat_lahir;
ALTER TABLE guru DROP COLUMN IF EXISTS tanggal_lahir;
ALTER TABLE guru DROP COLUMN IF EXISTS alamat;

-- Tambah kolom baru
ALTER TABLE guru ADD COLUMN IF NOT EXISTS tmt INT AFTER jenis_kelamin;
ALTER TABLE guru ADD COLUMN IF NOT EXISTS masa_bakti INT AFTER tmt;
ALTER TABLE guru ADD COLUMN IF NOT EXISTS jumlah_jam_mengajar INT DEFAULT 0 AFTER masa_bakti;



