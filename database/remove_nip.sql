-- Script untuk menghapus kolom NIP dari database
-- Jalankan script ini di phpMyAdmin atau MySQL client

USE sistem_gaji;

-- Hapus UNIQUE constraint pada NIP (jika ada)
ALTER TABLE guru DROP INDEX nip;

-- Hapus kolom NIP dari tabel guru
ALTER TABLE guru DROP COLUMN nip;



