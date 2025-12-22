<?php
/**
 * Script to add new fields to settings table
 * Jumlah Insentif Masa Bakti dan Jumlah Honor Per Jam
 */

require_once __DIR__ . '/../config/database.php';

try {
    // Check if columns exist
    $check_insentif = $conn->query("SHOW COLUMNS FROM settings LIKE 'insentif_masa_bakti'");
    if ($check_insentif->num_rows == 0) {
        $conn->query("ALTER TABLE settings ADD COLUMN insentif_masa_bakti DECIMAL(15,2) DEFAULT 0 AFTER jumlah_periode");
        echo "✓ Kolom 'insentif_masa_bakti' berhasil ditambahkan\n";
    } else {
        echo "✓ Kolom 'insentif_masa_bakti' sudah ada\n";
    }
    
    $check_honor = $conn->query("SHOW COLUMNS FROM settings LIKE 'honor_per_jam'");
    if ($check_honor->num_rows == 0) {
        $conn->query("ALTER TABLE settings ADD COLUMN honor_per_jam DECIMAL(15,2) DEFAULT 0 AFTER insentif_masa_bakti");
        echo "✓ Kolom 'honor_per_jam' berhasil ditambahkan\n";
    } else {
        echo "✓ Kolom 'honor_per_jam' sudah ada\n";
    }
    
    echo "\n✓ Migration selesai!\n";
    
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
}
?>


