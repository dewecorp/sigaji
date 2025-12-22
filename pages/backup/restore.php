<?php
require_once __DIR__ . '/../../config/config.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['backup_file'])) {
    $file = $_FILES['backup_file'];
    
    try {
        // Check file upload error
        if ($file['error'] != 0) {
            throw new Exception("Error upload file. Error code: " . $file['error']);
        }
        
        // Check file extension
        if (pathinfo($file['name'], PATHINFO_EXTENSION) != 'sql') {
            throw new Exception("File backup harus berformat .sql");
        }
        
        // Check file size (max 50MB)
        if ($file['size'] > 50 * 1024 * 1024) {
            throw new Exception("Ukuran file terlalu besar. Maksimal 50MB");
        }
        
        // Read SQL file
        $sql = file_get_contents($file['tmp_name']);
        if ($sql === false) {
            throw new Exception("Gagal membaca file backup");
        }
        
        if (empty(trim($sql))) {
            throw new Exception("File backup kosong");
        }
        
        // Disable foreign key checks
        $conn->query("SET FOREIGN_KEY_CHECKS = 0");
        $conn->query("SET AUTOCOMMIT = 0");
        $conn->begin_transaction();
        
        try {
            // Execute SQL queries
            $queries = explode(';', $sql);
            $executed = 0;
            foreach ($queries as $query) {
                $query = trim($query);
                // Skip empty queries and comments
                if (!empty($query) && !preg_match('/^--/', $query) && !preg_match('/^\/\*/', $query)) {
                    if (!$conn->query($query)) {
                        throw new Exception("Error executing query: " . $conn->error . " - Query: " . substr($query, 0, 100));
                    }
                    $executed++;
                }
            }
            
            // Commit transaction
            $conn->commit();
            
            // Enable foreign key checks
            $conn->query("SET FOREIGN_KEY_CHECKS = 1");
            $conn->query("SET AUTOCOMMIT = 1");
            
            logActivity($conn, "Restore database dari backup: " . $file['name'], 'warning');
            $_SESSION['success'] = "Database berhasil di-restore dari file: " . htmlspecialchars($file['name']);
            
        } catch (Exception $e) {
            // Rollback transaction on error
            $conn->rollback();
            $conn->query("SET FOREIGN_KEY_CHECKS = 1");
            $conn->query("SET AUTOCOMMIT = 1");
            throw $e;
        }
        
    } catch (Exception $e) {
        logActivity($conn, "Gagal restore database: " . $e->getMessage(), 'danger');
        $_SESSION['error'] = "Gagal restore database: " . $e->getMessage();
    }
} else {
    $_SESSION['error'] = "File backup tidak ditemukan";
}

header('Location: ' . BASE_URL . 'pages/backup/index.php');
exit();
?>



