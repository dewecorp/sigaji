<?php
/**
 * Script untuk memperbaiki password di database
 * Jalankan script ini sekali untuk memperbaiki hash password
 * Hapus file ini setelah digunakan untuk keamanan
 */
require_once 'config/database.php';

// Password yang ingin di-set
$username = 'admin';
$password = 'admin123';

// Generate password hash yang benar
$password_hash = password_hash($password, PASSWORD_DEFAULT);

echo "Mengupdate password untuk user: $username<br>";
echo "Password baru: $password<br>";
echo "Hash: $password_hash<br><br>";

// Update password
$sql = "UPDATE users SET password = ? WHERE username = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ss", $password_hash, $username);

if ($stmt->execute()) {
    echo "<strong style='color: green;'>✓ Password berhasil diupdate!</strong><br><br>";
    echo "Silakan coba login dengan:<br>";
    echo "Username: <strong>admin</strong><br>";
    echo "Password: <strong>admin123</strong><br><br>";
    echo "<a href='login.php' style='padding: 10px 20px; background: #6777ef; color: white; text-decoration: none; border-radius: 5px;'>Klik di sini untuk login</a>";
} else {
    echo "<strong style='color: red;'>✗ Error:</strong> " . $conn->error . "<br>";
    
    // Try to insert if user doesn't exist
    $sql = "INSERT INTO users (username, password, nama_lengkap, email, role) VALUES (?, ?, 'Administrator', 'admin@sigaji.com', 'admin')";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $username, $password_hash);
    
    if ($stmt->execute()) {
        echo "<strong style='color: green;'>✓ User berhasil dibuat!</strong><br>";
        echo "Silakan coba login dengan:<br>";
        echo "Username: <strong>admin</strong><br>";
        echo "Password: <strong>admin123</strong><br>";
    } else {
        echo "<strong style='color: red;'>✗ Error saat membuat user:</strong> " . $conn->error;
    }
}

// Verify the password
echo "<br><br><hr>";
echo "<strong>Verifikasi:</strong><br>";
$sql = "SELECT password FROM users WHERE username = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();
if ($row = $result->fetch_assoc()) {
    $stored_hash = $row['password'];
    if (password_verify($password, $stored_hash)) {
        echo "<span style='color: green;'>✓ Password hash valid dan bisa digunakan untuk login</span>";
    } else {
        echo "<span style='color: red;'>✗ Password hash tidak valid</span>";
    }
}

$conn->close();
?>




