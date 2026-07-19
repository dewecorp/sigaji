-- Optimasi database SIGaji: tambah composite indexes untuk query lebih cepat

-- Index untuk ORDER BY nama_lengkap di tabel guru
CREATE INDEX idx_guru_nama ON guru (nama_lengkap(50));

-- Composite index untuk gaji_pokok (guru_id, periode)
CREATE INDEX idx_gaji_pokok_guru_periode ON gaji_pokok (guru_id, periode);

-- Composite index untuk tunjangan_detail (guru_id, tunjangan_id, periode)
CREATE INDEX idx_tunjangan_detail_guru_tunjangan_periode ON tunjangan_detail (guru_id, tunjangan_id, periode);

-- Composite index untuk potongan_detail (guru_id, potongan_id, periode)
CREATE INDEX idx_potongan_detail_guru_potongan_periode ON potongan_detail (guru_id, potongan_id, periode);

-- Index untuk kolom aktif di tunjangan dan potongan
CREATE INDEX idx_tunjangan_aktif ON tunjangan (aktif);
CREATE INDEX idx_potongan_aktif ON potongan (aktif);
