<?php
require_once __DIR__ . '/../../config/config.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['excel_file'])) {
    $file = $_FILES['excel_file'];
    
    if ($file['error'] == 0) {
        $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        if (in_array($file_ext, ['xlsx', 'xls', 'csv'])) {
            // Read Excel file using SimpleXLSX or PhpSpreadsheet
            // For simplicity, we'll use a basic approach with SimpleXLSX
            // You may need to install: composer require shuchkin/simplexlsx
            
            // Try to use PhpSpreadsheet if available
            $rows = [];
            
            try {
                // Load Composer autoloader
                $autoload_path = __DIR__ . '/../../vendor/autoload.php';
                if (file_exists($autoload_path)) {
                    require_once $autoload_path;
                }
                
                // Use PhpSpreadsheet to read Excel files
                if (class_exists('PhpOffice\PhpSpreadsheet\IOFactory')) {
                    $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file['tmp_name']);
                    $worksheet = $spreadsheet->getActiveSheet();
                    $rows = $worksheet->toArray(null, true, true, true); // Get all rows with keys
                    
                    // Convert associative array to indexed array
                    $rows = array_values($rows);
                } elseif ($file_ext == 'csv' || $file_ext == 'txt') {
                    // Fallback: Read CSV file
                    $handle = fopen($file['tmp_name'], 'r');
                    if ($handle !== false) {
                        while (($data = fgetcsv($handle, 1000, ',')) !== FALSE) {
                            $rows[] = $data;
                        }
                        fclose($handle);
                    } else {
                        throw new Exception('Gagal membaca file CSV');
                    }
                } else {
                    throw new Exception('PhpSpreadsheet tidak ditemukan. Pastikan sudah diinstall dengan: composer require phpoffice/phpspreadsheet');
                }
                
                // Skip header row (first row)
                array_shift($rows);
                
                $success_count = 0;
                $error_count = 0;
                $errors = [];
                
                foreach ($rows as $index => $row) {
                    $row_num = $index + 2; // +2 because we skipped header and array is 0-indexed
                    
                    // Skip empty rows
                    if (empty($row[0])) {
                        continue;
                    }
                    
                    $nama_lengkap = trim($row[0] ?? '');
                    $tmt = !empty($row[1]) ? (int)trim($row[1]) : null;
                    $jumlah_jam_mengajar = !empty($row[2]) ? (int)trim($row[2]) : 0;
                    $jabatan = trim($row[3] ?? '');
                    $status_pegawai = trim($row[4] ?? 'Honor');
                    
                    // Validate required fields
                    if (empty($nama_lengkap)) {
                        $errors[] = "Baris $row_num: Nama Lengkap tidak boleh kosong";
                        $error_count++;
                        continue;
                    }
                    
                    // Validate status_pegawai
                    if (!in_array($status_pegawai, ['PNS', 'Honor', 'Kontrak'])) {
                        $status_pegawai = 'Honor';
                    }
                    
                    // Insert data
                    $sql = "INSERT INTO guru (nama_lengkap, tmt, jumlah_jam_mengajar, jabatan, status_pegawai) 
                            VALUES (?, ?, ?, ?, ?)";
                    $stmt = $conn->prepare($sql);
                    
                    if ($stmt) {
                        $stmt->bind_param("siiss", $nama_lengkap, $tmt, $jumlah_jam_mengajar, $jabatan, $status_pegawai);
                        
                        if ($stmt->execute()) {
                            $success_count++;
                        } else {
                            $errors[] = "Baris $row_num: " . $conn->error;
                            $error_count++;
                        }
                    } else {
                        $errors[] = "Baris $row_num: Error preparing query";
                        $error_count++;
                    }
                }
                
                if ($success_count > 0) {
                    logActivity($conn, "Import data guru: $success_count data berhasil diimport", 'success');
                    $_SESSION['success'] = "Berhasil mengimport $success_count data guru";
                    if ($error_count > 0) {
                        $_SESSION['warning'] = "$error_count data gagal diimport. " . implode('; ', array_slice($errors, 0, 5));
                    }
                } else {
                    $_SESSION['error'] = "Tidak ada data yang berhasil diimport. " . implode('; ', array_slice($errors, 0, 5));
                }
                
            } catch (Exception $e) {
                $_SESSION['error'] = "Error: " . $e->getMessage();
            }
        } else {
            $_SESSION['error'] = "File harus berformat Excel (.xlsx atau .xls)";
        }
    } else {
        $_SESSION['error'] = "Error upload file";
    }
} else {
    $_SESSION['error'] = "File tidak ditemukan";
}

header('Location: ' . BASE_URL . 'pages/guru/index.php');
exit();
?>

