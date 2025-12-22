<?php
require_once __DIR__ . '/../../config/config.php';
requireLogin();

$id = $_GET['id'] ?? 0;

$sql = "SELECT * FROM backups WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

try {
    if ($result->num_rows > 0) {
        $backup = $result->fetch_assoc();
        $filepath = $backup['filepath'];
        
        if (file_exists($filepath)) {
            logActivity($conn, "Mengunduh backup: " . $backup['filename'], 'info');
            
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . $backup['filename'] . '"');
            header('Content-Length: ' . filesize($filepath));
            readfile($filepath);
            exit();
        } else {
            throw new Exception("File backup tidak ditemukan di server");
        }
    } else {
        throw new Exception("Backup tidak ditemukan");
    }
} catch (Exception $e) {
    logActivity($conn, "Gagal mengunduh backup: " . $e->getMessage(), 'danger');
    $_SESSION['error'] = "Gagal mengunduh backup: " . $e->getMessage();
}

header('Location: ' . BASE_URL . 'pages/backup/index.php');
exit();
?>

