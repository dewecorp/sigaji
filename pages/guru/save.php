<?php
require_once __DIR__ . '/../../config/config.php';
requireLogin();

// Function to check and add missing columns
function ensureGuruColumns($conn) {
    $db_name = DB_NAME;
    
    // Check and add tmt
    $check_tmt = $conn->query("SELECT COUNT(*) as count 
        FROM INFORMATION_SCHEMA.COLUMNS 
        WHERE TABLE_SCHEMA = '$db_name' 
        AND TABLE_NAME = 'guru' 
        AND COLUMN_NAME = 'tmt'");
    if ($check_tmt && $check_tmt->fetch_assoc()['count'] == 0) {
        $conn->query("ALTER TABLE guru ADD COLUMN tmt INT AFTER nama_lengkap");
    }
    
    // Check and add masa_bakti
    $check_masa_bakti = $conn->query("SELECT COUNT(*) as count 
        FROM INFORMATION_SCHEMA.COLUMNS 
        WHERE TABLE_SCHEMA = '$db_name' 
        AND TABLE_NAME = 'guru' 
        AND COLUMN_NAME = 'masa_bakti'");
    if ($check_masa_bakti && $check_masa_bakti->fetch_assoc()['count'] == 0) {
        // Check if tmt exists to use AFTER clause
        $check_tmt_again = $conn->query("SELECT COUNT(*) as count 
            FROM INFORMATION_SCHEMA.COLUMNS 
            WHERE TABLE_SCHEMA = '$db_name' 
            AND TABLE_NAME = 'guru' 
            AND COLUMN_NAME = 'tmt'");
        if ($check_tmt_again && $check_tmt_again->fetch_assoc()['count'] > 0) {
            $conn->query("ALTER TABLE guru ADD COLUMN masa_bakti INT AFTER tmt");
        } else {
            $conn->query("ALTER TABLE guru ADD COLUMN masa_bakti INT");
        }
    }
    
    // Check and add jumlah_jam_mengajar
    $check_jam = $conn->query("SELECT COUNT(*) as count 
        FROM INFORMATION_SCHEMA.COLUMNS 
        WHERE TABLE_SCHEMA = '$db_name' 
        AND TABLE_NAME = 'guru' 
        AND COLUMN_NAME = 'jumlah_jam_mengajar'");
    if ($check_jam && $check_jam->fetch_assoc()['count'] == 0) {
        // Check if masa_bakti exists to use AFTER clause
        $check_masa_again = $conn->query("SELECT COUNT(*) as count 
            FROM INFORMATION_SCHEMA.COLUMNS 
            WHERE TABLE_SCHEMA = '$db_name' 
            AND TABLE_NAME = 'guru' 
            AND COLUMN_NAME = 'masa_bakti'");
        if ($check_masa_again && $check_masa_again->fetch_assoc()['count'] > 0) {
            $conn->query("ALTER TABLE guru ADD COLUMN jumlah_jam_mengajar INT DEFAULT 0 AFTER masa_bakti");
        } else {
            $conn->query("ALTER TABLE guru ADD COLUMN jumlah_jam_mengajar INT DEFAULT 0");
        }
    }
}

// Ensure columns exist before processing
ensureGuruColumns($conn);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = $_POST['id'] ?? null;
    $nama_lengkap = $_POST['nama_lengkap'] ?? '';
    $tmt = $_POST['tmt'] ?? null;
    $jumlah_jam_mengajar = $_POST['jumlah_jam_mengajar'] ?? 0;
    
    // Handle multiple jabatan
    $jabatan_array = $_POST['jabatan'] ?? [];
    if (!is_array($jabatan_array)) {
        $jabatan_array = [];
    }
    // Filter empty values and trim
    $jabatan_array = array_filter(array_map('trim', $jabatan_array), function($j) {
        return !empty($j);
    });
    // Save as JSON array
    $jabatan = !empty($jabatan_array) ? json_encode(array_values($jabatan_array)) : '';
    
    $status_pegawai = $_POST['status_pegawai'] ?? 'Honor';
    
    // Calculate masa bakti (tahun sekarang - tahun TMT)
    $masa_bakti = null;
    if ($tmt) {
        $tahun_sekarang = (int)date('Y');
        $tahun_tmt = (int)$tmt;
        $masa_bakti = $tahun_sekarang - $tahun_tmt;
    }
    
    // Convert empty strings to null for optional fields
    $tmt = ($tmt === '' || $tmt === null) ? null : (int)$tmt;
    $masa_bakti = ($masa_bakti === null) ? null : (int)$masa_bakti;
    $jumlah_jam_mengajar = (int)$jumlah_jam_mengajar;
    
    if ($id) {
        // Update
        $sql = "UPDATE guru SET nama_lengkap=?, tmt=?, masa_bakti=?, jumlah_jam_mengajar=?, jabatan=?, status_pegawai=? WHERE id=?";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            $_SESSION['error'] = "Error preparing query: " . $conn->error;
            header('Location: ' . BASE_URL . 'pages/guru/index.php');
            exit();
        }
        $stmt->bind_param("siiissi", $nama_lengkap, $tmt, $masa_bakti, $jumlah_jam_mengajar, $jabatan, $status_pegawai, $id);
        $action = 'mengubah';
    } else {
        // Insert
        $sql = "INSERT INTO guru (nama_lengkap, tmt, masa_bakti, jumlah_jam_mengajar, jabatan, status_pegawai) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            $_SESSION['error'] = "Error preparing query: " . $conn->error;
            header('Location: ' . BASE_URL . 'pages/guru/index.php');
            exit();
        }
        $stmt->bind_param("siiiss", $nama_lengkap, $tmt, $masa_bakti, $jumlah_jam_mengajar, $jabatan, $status_pegawai);
        $action = 'menambah';
    }
    
    if ($stmt->execute()) {
        logActivity($conn, "{$action} data guru: {$nama_lengkap}", 'success');
        $_SESSION['success'] = "Data guru berhasil " . ($id ? 'diubah' : 'ditambahkan');
    } else {
        $_SESSION['error'] = "Gagal " . ($id ? 'mengubah' : 'menambah') . " data guru: " . $conn->error;
    }
}

header('Location: ' . BASE_URL . 'pages/guru/index.php');
exit();
?>


