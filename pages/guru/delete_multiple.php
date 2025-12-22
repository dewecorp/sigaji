<?php
require_once __DIR__ . '/../../config/config.php';
requireLogin();

$ids = $_GET['ids'] ?? '';

if (empty($ids)) {
    $_SESSION['error'] = 'Tidak ada data yang dipilih';
    header('Location: ' . BASE_URL . 'pages/guru/index.php');
    exit();
}

$id_array = explode(',', $ids);
$id_array = array_map('intval', $id_array);
$id_array = array_filter($id_array);

if (empty($id_array)) {
    $_SESSION['error'] = 'ID tidak valid';
    header('Location: ' . BASE_URL . 'pages/guru/index.php');
    exit();
}

// Get names for logging
$placeholders = str_repeat('?,', count($id_array) - 1) . '?';
$sql = "SELECT nama_lengkap FROM guru WHERE id IN ($placeholders)";
$stmt = $conn->prepare($sql);
$stmt->bind_param(str_repeat('i', count($id_array)), ...$id_array);
$stmt->execute();
$result = $stmt->get_result();
$names = [];
while ($row = $result->fetch_assoc()) {
    $names[] = $row['nama_lengkap'];
}

// Delete records
$sql = "DELETE FROM guru WHERE id IN ($placeholders)";
$stmt = $conn->prepare($sql);
$stmt->bind_param(str_repeat('i', count($id_array)), ...$id_array);

if ($stmt->execute()) {
    $deleted_count = $stmt->affected_rows;
    logActivity($conn, "Menghapus $deleted_count data guru: " . implode(', ', $names), 'warning');
    $_SESSION['success'] = "Berhasil menghapus $deleted_count data guru";
} else {
    $_SESSION['error'] = "Gagal menghapus data: " . $conn->error;
}

header('Location: ' . BASE_URL . 'pages/guru/index.php');
exit();
?>


