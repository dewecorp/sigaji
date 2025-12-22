<?php
require_once __DIR__ . '/../../config/config.php';
requireLogin();

// Set JSON header
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $ids = $_POST['ids'] ?? '';
    
    if (empty($ids)) {
        echo json_encode([
            'success' => false,
            'message' => 'Tidak ada data yang dipilih'
        ]);
        exit();
    }
    
    $id_array = explode(',', $ids);
    $id_array = array_map('intval', $id_array);
    $id_array = array_filter($id_array);
    
    if (empty($id_array)) {
        echo json_encode([
            'success' => false,
            'message' => 'ID tidak valid'
        ]);
        exit();
    }
    
    // Get data per row (array format)
    $nama_lengkap = $_POST['nama_lengkap'] ?? [];
    $tmt = $_POST['tmt'] ?? [];
    $jumlah_jam_mengajar = $_POST['jumlah_jam_mengajar'] ?? [];
    $jabatan = $_POST['jabatan'] ?? [];
    $status_pegawai = $_POST['status_pegawai'] ?? [];
    
    $tahun_sekarang = (int)date('Y');
    $updated_count = 0;
    $errors = [];
    
    // Update each record individually
    foreach ($id_array as $id) {
        $update_fields = [];
        $params = [];
        $types = '';
        
        // Nama Lengkap
        if (isset($nama_lengkap[$id]) && trim($nama_lengkap[$id]) !== '') {
            $update_fields[] = "nama_lengkap = ?";
            $params[] = trim($nama_lengkap[$id]);
            $types .= 's';
        }
        
        // TMT
        $masa_bakti = null;
        if (isset($tmt[$id]) && trim($tmt[$id]) !== '') {
            $tmt_value = (int)$tmt[$id];
            if ($tmt_value >= 1950 && $tmt_value <= $tahun_sekarang) {
                $update_fields[] = "tmt = ?";
                $params[] = $tmt_value;
                $types .= 'i';
                
                // Calculate masa bakti
                $masa_bakti = $tahun_sekarang - $tmt_value;
                if ($masa_bakti >= 0) {
                    $update_fields[] = "masa_bakti = ?";
                    $params[] = $masa_bakti;
                    $types .= 'i';
                }
            }
        }
        
        // Jumlah Jam Mengajar
        if (isset($jumlah_jam_mengajar[$id]) && trim($jumlah_jam_mengajar[$id]) !== '') {
            $update_fields[] = "jumlah_jam_mengajar = ?";
            $params[] = (int)$jumlah_jam_mengajar[$id];
            $types .= 'i';
        }
        
        // Jabatan - handle multiple jabatan (comma-separated or JSON)
        if (isset($jabatan[$id]) && trim($jabatan[$id]) !== '') {
            $jabatan_value = trim($jabatan[$id]);
            // If comma-separated, convert to JSON array
            if (strpos($jabatan_value, ',') !== false) {
                $jabatan_array = array_map('trim', explode(',', $jabatan_value));
                $jabatan_array = array_filter($jabatan_array, function($j) {
                    return !empty($j);
                });
                $jabatan_value = !empty($jabatan_array) ? json_encode(array_values($jabatan_array)) : '';
            }
            // If not empty, save it
            if (!empty($jabatan_value)) {
                $update_fields[] = "jabatan = ?";
                $params[] = $jabatan_value;
                $types .= 's';
            }
        }
        
        // Status Pegawai
        if (isset($status_pegawai[$id]) && trim($status_pegawai[$id]) !== '') {
            $update_fields[] = "status_pegawai = ?";
            $params[] = trim($status_pegawai[$id]);
            $types .= 's';
        }
        
        // Only update if there are changes
        if (!empty($update_fields)) {
            $params[] = $id;
            $types .= 'i';
            
            $sql = "UPDATE guru SET " . implode(', ', $update_fields) . " WHERE id = ?";
            $stmt = $conn->prepare($sql);
            
            if ($stmt) {
                $stmt->bind_param($types, ...$params);
                if ($stmt->execute()) {
                    $updated_count += $stmt->affected_rows;
                } else {
                    $errors[] = "Gagal update ID $id: " . $conn->error;
                }
            } else {
                $errors[] = "Error preparing query for ID $id: " . $conn->error;
            }
        }
    }
    
    if ($updated_count > 0) {
        logActivity($conn, "Mengubah $updated_count data guru secara massal", 'success');
        $_SESSION['success'] = "Berhasil mengubah $updated_count data guru";
        
        echo json_encode([
            'success' => true,
            'message' => "Berhasil mengubah $updated_count data guru" . (!empty($errors) ? '. Beberapa error: ' . implode(', ', $errors) : '')
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Tidak ada data yang diubah' . (!empty($errors) ? '. Errors: ' . implode(', ', $errors) : '')
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request'
    ]);
}
?>

