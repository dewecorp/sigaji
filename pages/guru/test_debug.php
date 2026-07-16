<?php
require_once __DIR__ . '/../../config/config.php';
requireLogin();

echo "1. SESSION OK<br>";
echo "2. CSRF: " . csrfToken() . "<br>";
echo "3. CSRF FIELD: " . csrfField() . "<br>";

$sql = "SELECT * FROM guru ORDER BY LOWER(TRIM(nama_lengkap)) ASC, nama_lengkap ASC";
$result = $conn->query($sql);
$guru = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
echo "4. Total guru: " . count($guru) . "<br>";

if (count($guru) > 0) {
    echo "5. Guru pertama: " . htmlspecialchars($guru[0]['nama_lengkap']) . "<br>";
    echo "6. TMT: " . ($guru[0]['tmt'] ?? '-') . "<br>";
    echo "7. Status: " . htmlspecialchars($guru[0]['status_pegawai'] ?? '-') . "<br>";
} else {
    echo "5. Tidak ada data guru<br>";
}

echo "8. SELESAI<br>";
echo "9. Error: " . error_get_last()['message'] ?? 'none';
