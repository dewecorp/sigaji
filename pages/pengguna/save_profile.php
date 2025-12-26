<?php
require_once __DIR__ . '/../../config/config.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = $_POST['id'] ?? null;
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';
    $nama_lengkap = $_POST['nama_lengkap'] ?? '';
    $email = $_POST['email'] ?? '';
    
    // Validate: user can only edit their own profile
    if ($id != $_SESSION['user_id']) {
        $_SESSION['error'] = "Anda tidak memiliki izin untuk mengubah profil ini";
        header('Location: ' . BASE_URL . 'pages/pengguna/profile.php');
        exit();
    }
    
    // Validate input
    $username = trim($username);
    $nama_lengkap = trim($nama_lengkap);
    $email = trim($email);
    
    if (empty($username)) {
        $_SESSION['error'] = "Username tidak boleh kosong";
        header('Location: ' . BASE_URL . 'pages/pengguna/profile.php');
        exit();
    }
    
    if (empty($nama_lengkap)) {
        $_SESSION['error'] = "Nama lengkap tidak boleh kosong";
        header('Location: ' . BASE_URL . 'pages/pengguna/profile.php');
        exit();
    }
    
    // Validate email format if provided
    if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['error'] = "Format email tidak valid";
        header('Location: ' . BASE_URL . 'pages/pengguna/profile.php');
        exit();
    }
    
    // Check if username already exists (except for current user)
    $check_username_sql = "SELECT id FROM users WHERE username = ? AND id != ?";
    $check_username_stmt = $conn->prepare($check_username_sql);
    $check_username_stmt->bind_param("si", $username, $id);
    $check_username_stmt->execute();
    $check_username_result = $check_username_stmt->get_result();
    
    if ($check_username_result->num_rows > 0) {
        $check_username_stmt->close();
        $_SESSION['error'] = "Username sudah digunakan oleh pengguna lain";
        header('Location: ' . BASE_URL . 'pages/pengguna/profile.php');
        exit();
    }
    $check_username_stmt->close();
    
    // Validate password if provided
    if ($password) {
        if (strlen($password) < 6) {
            $_SESSION['error'] = "Password minimal 6 karakter";
            header('Location: ' . BASE_URL . 'pages/pengguna/profile.php');
            exit();
        }
        
        if ($password !== $password_confirm) {
            $_SESSION['error'] = "Password dan konfirmasi password tidak cocok";
            header('Location: ' . BASE_URL . 'pages/pengguna/profile.php');
            exit();
        }
    }
    
    // Handle foto upload
    $foto = null;
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] == 0) {
        $upload_dir = __DIR__ . '/../../assets/img/users/';
        
        // Create directory if it doesn't exist
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        // Validate file type
        $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
        $file_ext = strtolower(pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION));
        
        if (in_array($file_ext, $allowed_extensions)) {
            // Validate file size (2MB max)
            if ($_FILES['foto']['size'] <= 2 * 1024 * 1024) {
                // Get current foto to delete old one if exists
                $current_foto_sql = "SELECT foto FROM users WHERE id = ?";
                $current_foto_stmt = $conn->prepare($current_foto_sql);
                $current_foto_stmt->bind_param("i", $id);
                $current_foto_stmt->execute();
                $current_foto_result = $current_foto_stmt->get_result();
                $current_foto = $current_foto_result->fetch_assoc()['foto'] ?? null;
                $current_foto_stmt->close();
                
                // Generate unique filename
                $foto = uniqid() . '.' . $file_ext;
                $upload_path = $upload_dir . $foto;
                
                // Delete old foto if exists and not default
                if ($current_foto && $current_foto != 'default.jpg' && file_exists($upload_dir . $current_foto)) {
                    @unlink($upload_dir . $current_foto);
                }
                
                // Move uploaded file
                if (move_uploaded_file($_FILES['foto']['tmp_name'], $upload_path)) {
                    // File uploaded successfully
                } else {
                    $_SESSION['error'] = "Gagal mengupload foto";
                    header('Location: ' . BASE_URL . 'pages/pengguna/profile.php');
                    exit();
                }
            } else {
                $_SESSION['error'] = "Ukuran file terlalu besar. Maksimal 2MB";
                header('Location: ' . BASE_URL . 'pages/pengguna/profile.php');
                exit();
            }
        } else {
            $_SESSION['error'] = "Format file tidak didukung. Gunakan JPG, PNG, atau GIF";
            header('Location: ' . BASE_URL . 'pages/pengguna/profile.php');
            exit();
        }
    }
    
    // Update user data
    if ($password) {
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        if ($foto) {
            $sql = "UPDATE users SET username=?, password=?, nama_lengkap=?, email=?, foto=? WHERE id=?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sssssi", $username, $password_hash, $nama_lengkap, $email, $foto, $id);
        } else {
            $sql = "UPDATE users SET username=?, password=?, nama_lengkap=?, email=? WHERE id=?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssssi", $username, $password_hash, $nama_lengkap, $email, $id);
        }
    } else {
        if ($foto) {
            $sql = "UPDATE users SET username=?, nama_lengkap=?, email=?, foto=? WHERE id=?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssssi", $username, $nama_lengkap, $email, $foto, $id);
        } else {
            $sql = "UPDATE users SET username=?, nama_lengkap=?, email=? WHERE id=?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sssi", $username, $nama_lengkap, $email, $id);
        }
    }
    
    if ($stmt->execute()) {
        // Update session data
        $_SESSION['username'] = $username;
        $_SESSION['nama_lengkap'] = $nama_lengkap;
        
        // Update session foto if foto was changed
        if ($foto) {
            $_SESSION['foto'] = $foto;
        }
        
        logActivity($conn, "Mengubah profile sendiri", 'success');
        $_SESSION['success'] = "Profile berhasil diubah";
    } else {
        // Better error handling
        $error_msg = $stmt->error;
        if (strpos($error_msg, 'Duplicate entry') !== false && strpos($error_msg, 'username') !== false) {
            $_SESSION['error'] = "Username sudah digunakan oleh pengguna lain";
        } else {
            $_SESSION['error'] = "Gagal mengubah profile: " . $error_msg;
        }
    }
    
    $stmt->close();
}

header('Location: ' . BASE_URL . 'pages/pengguna/profile.php');
exit();
?>



