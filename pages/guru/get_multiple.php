<?php
require_once __DIR__ . '/../../config/config.php';
requireLogin();

header('Content-Type: application/json');

$ids = $_GET['ids'] ?? '';

if (empty($ids)) {
    echo json_encode([
        'success' => false,
        'error' => 'Tidak ada data yang dipilih'
    ]);
    exit();
}

$id_array = explode(',', $ids);
$id_array = array_map('intval', $id_array);
$id_array = array_filter($id_array);

if (empty($id_array)) {
    echo json_encode([
        'success' => false,
        'error' => 'ID tidak valid'
    ]);
    exit();
}

// Get selected records
$placeholders = str_repeat('?,', count($id_array) - 1) . '?';
$sql = "SELECT * FROM guru WHERE id IN ($placeholders) ORDER BY nama_lengkap ASC";
$stmt = $conn->prepare($sql);
$stmt->bind_param(str_repeat('i', count($id_array)), ...$id_array);
$stmt->execute();
$result = $stmt->get_result();
$guru_list = $result->fetch_all(MYSQLI_ASSOC);

echo json_encode([
    'success' => true,
    'data' => $guru_list
]);
?>



