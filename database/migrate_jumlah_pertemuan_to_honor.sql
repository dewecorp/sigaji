-- Migration script: Move jumlah_pertemuan from ekstrakurikuler to honor
-- Run this script in phpMyAdmin or MySQL client

USE sistem_gaji;

-- Step 1: Add jumlah_pertemuan column to honor table if not exists
ALTER TABLE honor ADD COLUMN IF NOT EXISTS jumlah_pertemuan INT NOT NULL DEFAULT 0 AFTER jumlah_honor;

-- Step 2: Migrate data from ekstrakurikuler to honor (if needed)
-- This will set jumlah_pertemuan in honor based on the ekstrakurikuler associated with each pembina
-- Note: This migration assumes that honor records are linked to pembina, and pembina are linked to ekstrakurikuler
UPDATE honor h
INNER JOIN pembina p ON h.pembina_id = p.id
INNER JOIN ekstrakurikuler e ON p.ekstrakurikuler_id = e.id
SET h.jumlah_pertemuan = e.jumlah_pertemuan
WHERE h.jumlah_pertemuan = 0;

-- Step 3: Remove jumlah_pertemuan column from ekstrakurikuler table
-- Note: MySQL doesn't support IF EXISTS for DROP COLUMN, so use this carefully
-- Check if column exists first, then drop it
-- You may need to run this separately after verifying the migration worked
-- ALTER TABLE ekstrakurikuler DROP COLUMN jumlah_pertemuan;

