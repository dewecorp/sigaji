<?php
require_once __DIR__ . '/../../config/config.php';
requireLogin();

$id = $_GET['id'] ?? 0;

try {
    if (empty($id) || !is_numeric($id)) {
        throw new Exception("ID backup tidak valid");
    }
    
    $sql = "SELECT * FROM backups WHERE id = ?";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception("Gagal mempersiapkan query: " . $conn->error);
    }
    
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $stmt->close();

    if ($result->num_rows > 0) {
        $backup = $result->fetch_assoc();
        $filepath = $backup['filepath'];
        $filename = $backup['filename'];
        
        // Delete file
        if (file_exists($filepath)) {
            if (!unlink($filepath)) {
                throw new Exception("Gagal menghapus file backup");
            }
        }
        
        // Delete from database
        $delete_sql = "DELETE FROM backups WHERE id = ?";
        $delete_stmt = $conn->prepare($delete_sql);
        if (!$delete_stmt) {
            throw new Exception("Gagal mempersiapkan query delete: " . $conn->error);
        }
        
        $delete_stmt->bind_param("i", $id);
        if (!$delete_stmt->execute()) {
            throw new Exception("Gagal menghapus data backup: " . $delete_stmt->error);
        }
        $delete_stmt->close();
        
        logActivity($conn, "Menghapus backup: $filename", 'warning');
        $_SESSION['success'] = "Backup berhasil dihapus: " . $filename;
    } else {
        throw new Exception("Backup tidak ditemukan");
    }
} catch (Exception $e) {
    logActivity($conn, "Gagal menghapus backup: " . $e->getMessage(), 'danger');
    $_SESSION['backup_error'] = "Gagal menghapus backup: " . $e->getMessage();
}

header('Location: ' . BASE_URL . 'pages/backup/index.php');
exit();
?>

