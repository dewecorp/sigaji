<?php
require_once __DIR__ . '/../../config/config.php';
requireLogin();

$backup_dir = __DIR__ . '/../../backup/';
if (!is_dir($backup_dir)) {
    mkdir($backup_dir, 0777, true);
}

$filename = 'backup_' . date('Y-m-d_His') . '.sql';
$filepath = $backup_dir . $filename;

$tables = ['users', 'settings', 'guru', 'gaji_pokok', 'tunjangan', 'tunjangan_detail', 'potongan', 'potongan_detail', 'legger_gaji', 'legger_detail', 'activities'];

$output = "-- Backup Database SIGaji\n";
$output .= "-- Generated: " . date('Y-m-d H:i:s') . "\n\n";
$output .= "SET SQL_MODE = \"NO_AUTO_VALUE_ON_ZERO\";\n";
$output .= "SET AUTOCOMMIT = 0;\n";
$output .= "START TRANSACTION;\n";
$output .= "SET time_zone = \"+00:00\";\n\n";

foreach ($tables as $table) {
    // Check if table exists
    $table_check = $conn->query("SHOW TABLES LIKE '$table'");
    if ($table_check && $table_check->num_rows > 0) {
        $result = $conn->query("SHOW CREATE TABLE `$table`");
        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_row();
            $output .= "\n-- Table structure for table `$table`\n";
            $output .= "DROP TABLE IF EXISTS `$table`;\n";
            $output .= $row[1] . ";\n\n";
            
            $result = $conn->query("SELECT * FROM `$table`");
            if ($result && $result->num_rows > 0) {
                $output .= "-- Dumping data for table `$table`\n";
                while ($row = $result->fetch_assoc()) {
                    $output .= "INSERT INTO `$table` VALUES (";
                    $values = [];
                    foreach ($row as $value) {
                        // Handle null values
                        if ($value === null) {
                            $values[] = "NULL";
                        } else {
                            $values[] = "'" . $conn->real_escape_string($value) . "'";
                        }
                    }
                    $output .= implode(',', $values) . ");\n";
                }
                $output .= "\n";
            }
        }
    }
}

$output .= "COMMIT;\n";

try {
    // Write backup file
    if (file_put_contents($filepath, $output) === false) {
        throw new Exception("Gagal menulis file backup");
    }
    
    // Create backups table if not exists
    $create_table_sql = "CREATE TABLE IF NOT EXISTS backups (
        id INT AUTO_INCREMENT PRIMARY KEY,
        filename VARCHAR(255) NOT NULL,
        filepath VARCHAR(500) NOT NULL,
        file_size BIGINT NOT NULL,
        created_by INT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    if (!$conn->query($create_table_sql)) {
        throw new Exception("Gagal membuat tabel backups: " . $conn->error);
    }
    
    // Add foreign key if not exists (safer approach)
    $check_fk_sql = "SELECT COUNT(*) as count 
                     FROM information_schema.TABLE_CONSTRAINTS 
                     WHERE CONSTRAINT_SCHEMA = DATABASE() 
                     AND TABLE_NAME = 'backups' 
                     AND CONSTRAINT_TYPE = 'FOREIGN KEY' 
                     AND CONSTRAINT_NAME = 'backups_ibfk_1'";
    $result = $conn->query($check_fk_sql);
    if ($result) {
        $row = $result->fetch_assoc();
        if ($row['count'] == 0) {
            // Check if users table exists
            $check_users = $conn->query("SHOW TABLES LIKE 'users'");
            if ($check_users && $check_users->num_rows > 0) {
                $conn->query("ALTER TABLE backups ADD CONSTRAINT backups_ibfk_1 FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL");
            }
        }
    }
    
    // Save backup info to database
    $file_size = filesize($filepath);
    $user_id = $_SESSION['user_id'] ?? null;
    $stmt = $conn->prepare("INSERT INTO backups (filename, filepath, file_size, created_by) VALUES (?, ?, ?, ?)");
    if (!$stmt) {
        throw new Exception("Gagal mempersiapkan query: " . $conn->error);
    }
    $stmt->bind_param("ssii", $filename, $filepath, $file_size, $user_id);
    if (!$stmt->execute()) {
        throw new Exception("Gagal menyimpan informasi backup: " . $stmt->error);
    }
    $stmt->close();

    logActivity($conn, "Membuat backup database: $filename", 'success');
    
    // Set success message and redirect to index page
    $_SESSION['success'] = "Backup database berhasil dibuat: " . $filename;
    header('Location: ' . BASE_URL . 'pages/backup/index.php');
    exit();
    
} catch (Exception $e) {
    // Delete file if exists
    if (file_exists($filepath)) {
        unlink($filepath);
    }
    
    logActivity($conn, "Gagal membuat backup: " . $e->getMessage(), 'danger');
    // Use specific session key for backup errors
    $_SESSION['backup_error'] = "Gagal membuat backup: " . $e->getMessage();
    // Clear any success messages that might be lingering
    unset($_SESSION['success']);
    header('Location: ' . BASE_URL . 'pages/backup/index.php');
    exit();
}
?>



