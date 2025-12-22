<?php
require_once __DIR__ . '/../../config/config.php';
requireLogin();

$id = $_GET['id'] ?? 0;

$sql = "SELECT nama_lengkap FROM guru WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$guru = $result->fetch_assoc();

if ($guru) {
    $sql = "DELETE FROM guru WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        logActivity($conn, "Menghapus data guru: {$guru['nama_lengkap']}", 'danger');
        $_SESSION['success'] = "Data guru berhasil dihapus";
    } else {
        $_SESSION['error'] = "Gagal menghapus data guru";
    }
} else {
    $_SESSION['error'] = "Data tidak ditemukan";
}

header('Location: ' . BASE_URL . 'pages/guru/index.php');
exit();
?>



