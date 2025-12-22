<?php
require_once __DIR__ . '/../../config/config.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = $_POST['id'] ?? null;
    $guru_id = $_POST['guru_id'] ?? 0;
    $jumlah = str_replace(['.', ','], '', $_POST['jumlah'] ?? 0);
    $periode = $_POST['periode'] ?? '';
    
    if ($id) {
        $sql = "UPDATE gaji_pokok SET guru_id=?, jumlah=?, periode=? WHERE id=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("idsi", $guru_id, $jumlah, $periode, $id);
        $action = 'mengubah';
    } else {
        $sql = "INSERT INTO gaji_pokok (guru_id, jumlah, periode) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ids", $guru_id, $jumlah, $periode);
        $action = 'menambah';
    }
    
    if ($stmt->execute()) {
        $guru_name = $conn->query("SELECT nama_lengkap FROM guru WHERE id = $guru_id")->fetch_assoc()['nama_lengkap'];
        logActivity($conn, "{$action} gaji pokok untuk {$guru_name}", 'success');
        $_SESSION['success'] = "Data gaji pokok berhasil " . ($id ? 'diubah' : 'ditambahkan');
    } else {
        $_SESSION['error'] = "Gagal " . ($id ? 'mengubah' : 'menambah') . " data gaji pokok";
    }
}

header('Location: ' . BASE_URL . 'pages/gaji_pokok/index.php');
exit();
?>



