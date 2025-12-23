<?php
require_once __DIR__ . '/../../config/config.php';
requireLogin();
header('Content-Type: application/json');

$tunjangan_id = $_GET['tunjangan_id'] ?? 0;

if (empty($tunjangan_id) || !is_numeric($tunjangan_id)) {
    echo json_encode(['guru_ids' => []]);
    exit();
}

// Get current period
$sql = "SELECT periode_aktif FROM settings LIMIT 1";
$result = $conn->query($sql);
$settings = $result->fetch_assoc();
$periode_aktif = $settings['periode_aktif'] ?? date('Y-m');

// Get guru IDs that have this tunjangan
$sql = "SELECT DISTINCT guru_id FROM tunjangan_detail WHERE tunjangan_id = ? AND periode = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("is", $tunjangan_id, $periode_aktif);
$stmt->execute();
$result = $stmt->get_result();

$guru_ids = [];
while ($row = $result->fetch_assoc()) {
    $guru_ids[] = intval($row['guru_id']);
}

$stmt->close();

echo json_encode(['guru_ids' => $guru_ids]);
?>



