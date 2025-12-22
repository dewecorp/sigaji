<?php
require_once __DIR__ . '/../../config/config.php';
requireLogin();
$id = $_GET['id'] ?? 0;
$tunjangan_id = $_GET['tunjangan_id'] ?? 0;

$sql = "DELETE FROM tunjangan_detail WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
if ($stmt->execute()) {
    logActivity($conn, "Menghapus detail tunjangan", 'danger');
    $_SESSION['success'] = "Data berhasil dihapus";
} else {
    $_SESSION['error'] = "Gagal menghapus data";
}

if ($tunjangan_id) {
    header('Location: ' . BASE_URL . 'pages/tunjangan/detail.php?tunjangan_id=' . $tunjangan_id);
} else {
    header('Location: ' . BASE_URL . 'pages/tunjangan/index.php');
}
exit();
?>



