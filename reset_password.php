<?php
/**
 * Script untuk reset password admin
 * Hapus file ini setelah digunakan untuk keamanan
 */
require_once 'config/database.php';

$username = 'admin';
$new_password = 'admin123';

// Generate new password hash
$password_hash = password_hash($new_password, PASSWORD_DEFAULT);

// Update password
$sql = "UPDATE users SET password = ? WHERE username = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ss", $password_hash, $username);

if ($stmt->execute()) {
    echo "Password berhasil direset!<br>";
    echo "Username: admin<br>";
    echo "Password: admin123<br>";
    echo "<br><a href='login.php'>Klik di sini untuk login</a>";
} else {
    echo "Error: " . $conn->error;
}

$conn->close();
?>



