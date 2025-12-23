<?php
require_once __DIR__ . '/../../config/config.php';
requireLogin();

$id = $_GET['id'] ?? 0;

if ($id == $_SESSION['user_id']) {
    $_SESSION['error'] = "Tidak dapat menghapus akun sendiri";
    header('Location: ' . BASE_URL . 'pages/pengguna/index.php');
    exit();
}

$sql = "SELECT username, role FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if ($user) {
    // Cek apakah user adalah administrator
    if ($user['role'] == 'admin') {
        $_SESSION['error'] = "Akun administrator tidak dapat dihapus";
        header('Location: ' . BASE_URL . 'pages/pengguna/index.php');
        exit();
    }
    
    $sql = "DELETE FROM users WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        logActivity($conn, "Menghapus pengguna: {$user['username']}", 'danger');
        $_SESSION['success'] = "Data pengguna berhasil dihapus";
    } else {
        $_SESSION['error'] = "Gagal menghapus data pengguna";
    }
} else {
    $_SESSION['error'] = "Data tidak ditemukan";
}

header('Location: ' . BASE_URL . 'pages/pengguna/index.php');
exit();
?>




