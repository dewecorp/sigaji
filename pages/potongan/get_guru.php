<?php
require_once __DIR__ . '/../../config/config.php';
requireLogin();
header('Content-Type: application/json');

$potongan_id = $_GET['potongan_id'] ?? 0;

if (empty($potongan_id) || !is_numeric($potongan_id)) {
    echo json_encode(['guru_ids' => []]);
    exit();
}

// Get current period
$sql = "SELECT periode_aktif FROM settings LIMIT 1";
$result = $conn->query($sql);
$settings = $result->fetch_assoc();
$periode_aktif = $settings['periode_aktif'] ?? date('Y-m');

// Get guru IDs that have this potongan
$sql = "SELECT DISTINCT guru_id FROM potongan_detail WHERE potongan_id = ? AND periode = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("is", $potongan_id, $periode_aktif);
$stmt->execute();
$result = $stmt->get_result();

$guru_ids = [];
while ($row = $result->fetch_assoc()) {
    $guru_ids[] = intval($row['guru_id']);
}

$stmt->close();

echo json_encode(['guru_ids' => $guru_ids]);
?>

