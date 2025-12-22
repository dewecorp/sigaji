<?php
require_once __DIR__ . '/../../config/config.php';
requireLogin();

// Set JSON header for AJAX response
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Debug: log all POST data
    error_log("=== TUNJANGAN SAVE DEBUG ===");
    error_log("POST data: " . json_encode($_POST));
    error_log("POST guru_ids: " . (isset($_POST['guru_ids']) ? json_encode($_POST['guru_ids']) : 'NOT SET'));
    
    // Parse ID - handle both string and integer
    $id_raw = $_POST['id'] ?? null;
    $id = null;
    if ($id_raw !== null && $id_raw !== '' && $id_raw !== '0') {
        $id = intval($id_raw);
        if ($id <= 0) {
            $id = null;
        }
    }
    
    $nama_tunjangan = trim($_POST['nama_tunjangan'] ?? '');
    
    error_log("=== ID PARSING ===");
    error_log("ID Raw: " . var_export($id_raw, true));
    error_log("ID Parsed: " . var_export($id, true));
    error_log("Is Edit Mode: " . ($id !== null ? 'YES' : 'NO'));
    
    // Get jumlah_tunjangan from hidden input first, fallback to regular input
    $jumlah_tunjangan_raw = $_POST['jumlah_tunjangan_hidden'] ?? $_POST['jumlah_tunjangan'] ?? '0';
    
    // Clean and convert to float
    // Remove all non-numeric characters except dot
    $jumlah_tunjangan_cleaned = preg_replace('/[^0-9.]/', '', $jumlah_tunjangan_raw);
    // Replace comma with dot if any
    $jumlah_tunjangan_cleaned = str_replace(',', '.', $jumlah_tunjangan_cleaned);
    // Convert to float
    $jumlah_tunjangan = floatval($jumlah_tunjangan_cleaned);
    
    // Debug log
    error_log("Tunjangan save - ID: " . ($id ?? 'null') . ", Raw: " . $jumlah_tunjangan_raw . ", Cleaned: " . $jumlah_tunjangan_cleaned . ", Final: " . $jumlah_tunjangan);
    $aktif = isset($_POST['aktif']) ? 1 : 0;
    
    // Validation
    if (empty($nama_tunjangan)) {
        echo json_encode([
            'success' => false,
            'message' => 'Nama tunjangan tidak boleh kosong'
        ]);
        exit();
    }
    
    // Validate jumlah_tunjangan must be greater than 0
    if ($jumlah_tunjangan <= 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Jumlah tunjangan harus lebih besar dari 0'
        ]);
        exit();
    }
    
    if ($id && $id > 0) {
        // Update existing record
        error_log("=== UPDATE MODE ===");
        error_log("Updating tunjangan ID: " . $id);
        error_log("Data - Nama: " . $nama_tunjangan . ", Jumlah: " . $jumlah_tunjangan . ", Aktif: " . $aktif);
        
        $sql = "UPDATE tunjangan SET nama_tunjangan=?, jumlah_tunjangan=?, aktif=? WHERE id=?";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            error_log("Prepare failed: " . $conn->error);
            echo json_encode([
                'success' => false,
                'message' => 'Gagal menyiapkan query: ' . $conn->error
            ]);
            exit();
        }
        $stmt->bind_param("sdii", $nama_tunjangan, $jumlah_tunjangan, $aktif, $id);
        $action = 'mengubah';
    } else {
        // Insert new record
        $sql = "INSERT INTO tunjangan (nama_tunjangan, jumlah_tunjangan, aktif) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            error_log("Prepare failed: " . $conn->error);
            echo json_encode([
                'success' => false,
                'message' => 'Gagal menyiapkan query: ' . $conn->error
            ]);
            exit();
        }
        $stmt->bind_param("sdi", $nama_tunjangan, $jumlah_tunjangan, $aktif);
        $action = 'menambah';
    }
    
    // Log values for debugging
    error_log("Tunjangan save - ID: " . ($id ?? 'null') . ", Nama: " . $nama_tunjangan . ", Jumlah: " . $jumlah_tunjangan . ", Aktif: " . $aktif);
    error_log("Action: " . ($action ?? 'unknown'));
    
    $execute_result = $stmt->execute();
    error_log("Execute result: " . ($execute_result ? 'SUCCESS' : 'FAILED'));
    
    if (!$execute_result) {
        $error_msg = $stmt->error ? $stmt->error : "Database error";
        error_log("Tunjangan save execute error: " . $error_msg);
        echo json_encode([
            'success' => false,
            'message' => 'Gagal ' . ($id ? 'mengubah' : 'menambah') . ' data tunjangan: ' . $error_msg
        ]);
        $stmt->close();
        exit();
    }
    
    if ($execute_result) {
        $tunjangan_id = $id ? $id : $conn->insert_id;
        error_log("Tunjangan ID for detail: " . $tunjangan_id);
        
        // Get periode_aktif from settings
        $sql = "SELECT periode_aktif FROM settings LIMIT 1";
        $result = $conn->query($sql);
        $settings = $result->fetch_assoc();
        $periode_aktif = $settings['periode_aktif'] ?? date('Y-m');
        
        // Get selected guru IDs - handle both array and indexed array formats
        $guru_ids = [];
        
        // Check for guru_ids[] array format (standard PHP array)
        if (isset($_POST['guru_ids']) && is_array($_POST['guru_ids'])) {
            $guru_ids = $_POST['guru_ids'];
        }
        
        error_log("Raw POST guru_ids: " . var_export($_POST['guru_ids'] ?? 'NOT SET', true));
        error_log("All POST keys: " . implode(', ', array_keys($_POST)));
        
        // If still empty, try to find any guru_ids keys
        if (empty($guru_ids)) {
            foreach ($_POST as $key => $value) {
                if (strpos($key, 'guru_ids') !== false) {
                    if (is_array($value)) {
                        $guru_ids = array_merge($guru_ids, $value);
                    } else {
                        $guru_ids[] = $value;
                    }
                }
            }
        }
        
        // Clean and validate guru IDs
        $guru_ids = array_filter(array_map('intval', $guru_ids), function($id) {
            return $id > 0;
        });
        $guru_ids = array_values($guru_ids); // Re-index array
        
        // Debug log
        error_log("Tunjangan save - Received guru_ids: " . json_encode($guru_ids));
        error_log("Tunjangan save - guru_ids count: " . count($guru_ids));
        
        // Validate: at least one guru must be selected
        if (empty($guru_ids)) {
            error_log("ERROR: No guru_ids provided - cannot save tunjangan_detail");
            echo json_encode([
                'success' => false,
                'message' => 'Pilih minimal satu guru untuk tunjangan ini'
            ]);
            $stmt->close();
            exit();
        }
        
        // Delete existing tunjangan_detail for this tunjangan and period
        $sql = "DELETE FROM tunjangan_detail WHERE tunjangan_id = ? AND periode = ?";
        $delete_stmt = $conn->prepare($sql);
        $delete_stmt->bind_param("is", $tunjangan_id, $periode_aktif);
        $delete_stmt->execute();
        $delete_stmt->close();
        
        // Insert tunjangan_detail for each selected guru
        if (!empty($guru_ids)) {
            $sql = "INSERT INTO tunjangan_detail (guru_id, tunjangan_id, jumlah, periode) VALUES (?, ?, ?, ?)";
            $detail_stmt = $conn->prepare($sql);
            
            $inserted_count = 0;
            foreach ($guru_ids as $guru_id) {
                $guru_id = intval($guru_id);
                if ($guru_id > 0) {
                    $detail_stmt->bind_param("iids", $guru_id, $tunjangan_id, $jumlah_tunjangan, $periode_aktif);
                    if ($detail_stmt->execute()) {
                        $inserted_count++;
                    } else {
                        error_log("Failed to insert tunjangan_detail for guru_id: " . $guru_id . ", Error: " . $detail_stmt->error);
                    }
                }
            }
            $detail_stmt->close();
            error_log("Inserted " . $inserted_count . " tunjangan_detail records for tunjangan_id: " . $tunjangan_id . ", periode: " . $periode_aktif);
        } else {
            error_log("No guru_ids provided for tunjangan_detail insertion");
        }
        
        logActivity($conn, "{$action} tunjangan: {$nama_tunjangan}", 'success');
        echo json_encode([
            'success' => true,
            'message' => 'Data tunjangan berhasil ' . ($id ? 'diubah' : 'ditambahkan')
        ]);
    } else {
        $error_msg = $stmt->error ? $stmt->error : "Database error";
        error_log("Tunjangan save error: " . $error_msg);
        echo json_encode([
            'success' => false,
            'message' => 'Gagal ' . ($id ? 'mengubah' : 'menambah') . ' data tunjangan: ' . $error_msg
        ]);
    }
    $stmt->close();
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method'
    ]);
}
exit();
?>


