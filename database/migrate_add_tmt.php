<?php
/**
 * Migration script to add tmt and masa_bakti columns to guru table
 * Run this file once: http://localhost/sistem_gaji/database/migrate_add_tmt.php
 */

require_once __DIR__ . '/../config/database.php';

echo "<h2>Migration: Adding tmt and masa_bakti columns to guru table</h2>";
echo "<pre>";

$db_name = DB_NAME;

// Check if tmt column exists
$check_tmt = $conn->query("SELECT COUNT(*) as count 
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = '$db_name' 
    AND TABLE_NAME = 'guru' 
    AND COLUMN_NAME = 'tmt'");
$tmt_exists = $check_tmt->fetch_assoc()['count'] > 0;

// Check if masa_bakti column exists
$check_masa_bakti = $conn->query("SELECT COUNT(*) as count 
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = '$db_name' 
    AND TABLE_NAME = 'guru' 
    AND COLUMN_NAME = 'masa_bakti'");
$masa_bakti_exists = $check_masa_bakti->fetch_assoc()['count'] > 0;

// Add tmt column if it doesn't exist
if (!$tmt_exists) {
    echo "Adding tmt column...\n";
    $sql = "ALTER TABLE guru ADD COLUMN tmt INT AFTER jenis_kelamin";
    if ($conn->query($sql)) {
        echo "✓ tmt column added successfully\n";
    } else {
        echo "✗ Error adding tmt column: " . $conn->error . "\n";
    }
} else {
    echo "✓ tmt column already exists\n";
}

// Add masa_bakti column if it doesn't exist
if (!$masa_bakti_exists) {
    echo "Adding masa_bakti column...\n";
    $sql = "ALTER TABLE guru ADD COLUMN masa_bakti INT AFTER tmt";
    if ($conn->query($sql)) {
        echo "✓ masa_bakti column added successfully\n";
    } else {
        echo "✗ Error adding masa_bakti column: " . $conn->error . "\n";
    }
} else {
    echo "✓ masa_bakti column already exists\n";
}

echo "\nMigration completed!\n";
echo "</pre>";
echo "<p><a href='" . (isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '../pages/guru/index.php') . "'>Go back</a></p>";
?>

