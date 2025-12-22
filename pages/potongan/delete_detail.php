<?php
require_once __DIR__ . '/../../config/config.php';
requireLogin();
$id = $_GET['id'] ?? 0;
$potongan_id = $_GET['potongan_id'] ?? 0;

$sql = "DELETE FROM potongan_detail WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
if ($stmt->execute()) {
    logActivity($conn, "Menghapus detail potongan", 'danger');
    $_SESSION['success'] = "Data berhasil dihapus";
} else {
    $_SESSION['error'] = "Gagal menghapus data";
}

if ($potongan_id) {
    header('Location: ' . BASE_URL . 'pages/potongan/detail.php?potongan_id=' . $potongan_id);
} else {
    header('Location: ' . BASE_URL . 'pages/potongan/index.php');
}
exit();
?>



