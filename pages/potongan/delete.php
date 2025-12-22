<?php
require_once __DIR__ . '/../../config/config.php';
requireLogin();
$id = $_GET['id'] ?? 0;
$sql = "SELECT nama_potongan FROM potongan WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$potongan = $result->fetch_assoc();

if ($potongan) {
    $sql = "DELETE FROM potongan WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        logActivity($conn, "Menghapus potongan: {$potongan['nama_potongan']}", 'danger');
        $_SESSION['success'] = "Data potongan berhasil dihapus";
    } else {
        $_SESSION['error'] = "Gagal menghapus data potongan";
    }
} else {
    $_SESSION['error'] = "Data tidak ditemukan";
}
header('Location: ' . BASE_URL . 'pages/potongan/index.php');
exit();
?>



