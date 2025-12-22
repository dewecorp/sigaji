<?php
require_once __DIR__ . '/../../config/config.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = $_POST['id'] ?? null;
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $nama_lengkap = $_POST['nama_lengkap'] ?? '';
    $email = $_POST['email'] ?? '';
    $role = $_POST['role'] ?? 'bendahara';
    
    $foto = null;
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] == 0) {
        $upload_dir = __DIR__ . '/../../assets/img/users/';
        $file_ext = pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION);
        $foto = uniqid() . '.' . $file_ext;
        move_uploaded_file($_FILES['foto']['tmp_name'], $upload_dir . $foto);
    }
    
    if ($id) {
        // Update
        if ($password) {
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            if ($foto) {
                $sql = "UPDATE users SET username=?, password=?, nama_lengkap=?, email=?, role=?, foto=? WHERE id=?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ssssssi", $username, $password_hash, $nama_lengkap, $email, $role, $foto, $id);
            } else {
                $sql = "UPDATE users SET username=?, password=?, nama_lengkap=?, email=?, role=? WHERE id=?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("sssssi", $username, $password_hash, $nama_lengkap, $email, $role, $id);
            }
        } else {
            if ($foto) {
                $sql = "UPDATE users SET username=?, nama_lengkap=?, email=?, role=?, foto=? WHERE id=?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("sssssi", $username, $nama_lengkap, $email, $role, $foto, $id);
            } else {
                $sql = "UPDATE users SET username=?, nama_lengkap=?, email=?, role=? WHERE id=?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ssssi", $username, $nama_lengkap, $email, $role, $id);
            }
        }
        $action = 'mengubah';
    } else {
        // Insert
        if (empty($password)) {
            $_SESSION['error'] = "Password harus diisi";
            header('Location: ' . BASE_URL . 'pages/pengguna/index.php');
            exit();
        }
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        if (!$foto) $foto = 'default.jpg';
        $sql = "INSERT INTO users (username, password, nama_lengkap, email, role, foto) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssss", $username, $password_hash, $nama_lengkap, $email, $role, $foto);
        $action = 'menambah';
    }
    
    if ($stmt->execute()) {
        logActivity($conn, "{$action} pengguna: {$username}", 'success');
        
        // Update session foto if user is updating their own profile
        if ($id && $id == $_SESSION['user_id']) {
            // Get latest foto from database
            $foto_sql = "SELECT foto FROM users WHERE id = ?";
            $foto_stmt = $conn->prepare($foto_sql);
            $foto_stmt->bind_param("i", $id);
            $foto_stmt->execute();
            $foto_result = $foto_stmt->get_result();
            if ($foto_result->num_rows > 0) {
                $foto_data = $foto_result->fetch_assoc();
                $_SESSION['foto'] = $foto_data['foto'] ?? 'default.jpg';
            }
            $foto_stmt->close();
        }
        
        $_SESSION['success'] = "Data pengguna berhasil " . ($id ? 'diubah' : 'ditambahkan');
    } else {
        $_SESSION['error'] = "Gagal " . ($id ? 'mengubah' : 'menambah') . " data pengguna";
    }
}

header('Location: ' . BASE_URL . 'pages/pengguna/index.php');
exit();
?>



