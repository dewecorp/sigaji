<?php
require_once __DIR__ . '/../../config/config.php';
requireLogin();

if (!verifyCsrfToken($_REQUEST['csrf_token'] ?? '')) { $_SESSION['error'] = 'Token tidak valid. Silakan coba lagi.'; header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? BASE_URL . 'pages/dashboard')); exit(); }

$id = $_REQUEST['id'] ?? 0;
$sql = "DELETE FROM gaji_pokok WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
if ($stmt->execute()) {
    logActivity($conn, "Menghapus data gaji pokok", 'danger');
    $_SESSION['success'] = "Data gaji pokok berhasil dihapus";
} else {
    $_SESSION['error'] = "Gagal menghapus data gaji pokok";
}
header('Location: ' . BASE_URL . 'pages/gaji_pokok');
exit();
?>




