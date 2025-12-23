-- Add new fields to settings table
-- Jumlah Insentif Masa Bakti dan Jumlah Honor Per Jam

ALTER TABLE settings 
ADD COLUMN IF NOT EXISTS insentif_masa_bakti DECIMAL(15,2) DEFAULT 0 AFTER jumlah_periode,
ADD COLUMN IF NOT EXISTS honor_per_jam DECIMAL(15,2) DEFAULT 0 AFTER insentif_masa_bakti;



