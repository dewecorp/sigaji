<?php
require_once __DIR__ . '/../../config/config.php';
requireLogin();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

$periode = $_POST['periode'] ?? date('Y-m');

try {
    // Get settings untuk jumlah_periode, periode_mulai, periode_akhir
    $sql = "SELECT jumlah_periode, periode_mulai, periode_akhir FROM settings LIMIT 1";
    $result_settings = $conn->query($sql);
    $settings = $result_settings ? $result_settings->fetch_assoc() : [];
    $jumlah_periode = isset($settings['jumlah_periode']) ? intval($settings['jumlah_periode']) : 1;
    $periode_mulai = isset($settings['periode_mulai']) ? $settings['periode_mulai'] : '';
    $periode_akhir = isset($settings['periode_akhir']) ? $settings['periode_akhir'] : '';
    
    // Get tunjangan: ambil yang aktif, PLUS yang pernah ada datanya di tunjangan_detail (dari periode manapun)
    // Ini memastikan tunjangan yang pernah punya data tetap di-generate meskipun tidak aktif atau tidak ada di periode aktif
    $sql = "SELECT DISTINCT t.* FROM tunjangan t 
            WHERE t.aktif = 1 
            OR EXISTS (
                SELECT 1 FROM tunjangan_detail td 
                WHERE td.tunjangan_id = t.id
            )
            ORDER BY t.nama_tunjangan";
    $result_tunjangan = $conn->query($sql);
    if (!$result_tunjangan) {
        throw new Exception('Gagal mengambil data tunjangan: ' . $conn->error);
    }
    $tunjangan = $result_tunjangan->fetch_all(MYSQLI_ASSOC);
    
    // Get potongan: ambil yang aktif, PLUS yang pernah ada datanya di potongan_detail (dari periode manapun)
    // Ini memastikan potongan yang pernah punya data tetap di-generate meskipun tidak aktif atau tidak ada di periode aktif
    $sql = "SELECT DISTINCT p.* FROM potongan p 
            WHERE p.aktif = 1 
            OR EXISTS (
                SELECT 1 FROM potongan_detail pd 
                WHERE pd.potongan_id = p.id
            )
            ORDER BY p.nama_potongan";
    $result_potongan = $conn->query($sql);
    if (!$result_potongan) {
        throw new Exception('Gagal mengambil data potongan: ' . $conn->error);
    }
    $potongan = $result_potongan->fetch_all(MYSQLI_ASSOC);
    
    // Get all teachers with masa_bakti
    $sql = "SELECT id, nama_lengkap, masa_bakti FROM guru ORDER BY nama_lengkap";
    $result_guru = $conn->query($sql);
    if (!$result_guru) {
        throw new Exception('Gagal mengambil data guru: ' . $conn->error);
    }
    $guru = $result_guru->fetch_all(MYSQLI_ASSOC);
    
    $total_guru = count($guru);
    $processed = 0;
    $errors = [];
    
    // Start transaction for each guru separately to avoid rollback on single error
    foreach ($guru as $g) {
        try {
            // Start transaction for this guru
            $conn->begin_transaction();
            // Get gaji pokok dari tabel gaji_pokok
            $sql = "SELECT jumlah FROM gaji_pokok WHERE guru_id = ? AND periode = ?";
            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                throw new Exception('Gagal prepare query gaji_pokok: ' . $conn->error);
            }
            $stmt->bind_param("is", $g['id'], $periode);
            $stmt->execute();
            $result = $stmt->get_result();
            $gaji_pokok_row = $result->fetch_assoc();
            $gaji_pokok_jumlah = isset($gaji_pokok_row['jumlah']) ? floatval($gaji_pokok_row['jumlah']) : 0;
            // Kalikan dengan jumlah_periode
            $gaji_pokok_jumlah = $gaji_pokok_jumlah * $jumlah_periode;
            $stmt->close();
            
            // Calculate total tunjangan dari tunjangan_detail
            // Khusus untuk tunjangan "Masa Bakti", hitung: masa_bakti × jumlah_tunjangan
            $total_tunjangan = 0;
            $tunjangan_details = [];
            
            // Get masa_bakti from guru table for Masa Bakti calculation
            $masa_bakti_guru = isset($g['masa_bakti']) ? intval($g['masa_bakti']) : 0;
            
            foreach ($tunjangan as $t) {
                $jumlah = 0;
                
                // Check if this is "Masa Bakti" tunjangan (case-insensitive)
                $nama_tunjangan_lower = strtolower(trim($t['nama_tunjangan']));
                $is_masa_bakti = (strpos($nama_tunjangan_lower, 'masa') !== false && strpos($nama_tunjangan_lower, 'bakti') !== false);
                
                // Debug: log tunjangan yang sedang diproses
                error_log("Processing tunjangan: {$t['nama_tunjangan']} (ID: {$t['id']}) for guru: {$g['nama_lengkap']}");
                
                if ($is_masa_bakti) {
                    // Calculate: masa_bakti × jumlah_tunjangan_per_tahun (per bulan)
                    $jumlah_tunjangan_per_tahun = isset($t['jumlah_tunjangan']) ? floatval($t['jumlah_tunjangan']) : 0;
                    $jumlah_per_bulan = $masa_bakti_guru * $jumlah_tunjangan_per_tahun;
                    
                    // Update or insert into tunjangan_detail (simpan per bulan)
                    $sql_check = "SELECT id FROM tunjangan_detail WHERE guru_id = ? AND tunjangan_id = ? AND periode = ?";
                    $stmt_check = $conn->prepare($sql_check);
                    if ($stmt_check) {
                        $stmt_check->bind_param("iis", $g['id'], $t['id'], $periode);
                        $stmt_check->execute();
                        $result_check = $stmt_check->get_result();
                        $existing = $result_check->fetch_assoc();
                        $stmt_check->close();
                        
                        if ($existing) {
                            // Update existing
                            $sql_update = "UPDATE tunjangan_detail SET jumlah = ? WHERE id = ?";
                            $stmt_update = $conn->prepare($sql_update);
                            if ($stmt_update) {
                                $stmt_update->bind_param("di", $jumlah_per_bulan, $existing['id']);
                                $stmt_update->execute();
                                $stmt_update->close();
                            }
                        } else {
                            // Insert new
                            $sql_insert = "INSERT INTO tunjangan_detail (guru_id, tunjangan_id, periode, jumlah) VALUES (?, ?, ?, ?)";
                            $stmt_insert = $conn->prepare($sql_insert);
                            if ($stmt_insert) {
                                $stmt_insert->bind_param("iisd", $g['id'], $t['id'], $periode, $jumlah_per_bulan);
                                $stmt_insert->execute();
                                $stmt_insert->close();
                            }
                        }
                    }
                    // Kalikan dengan jumlah_periode untuk legger
                    $jumlah = $jumlah_per_bulan * $jumlah_periode;
                } else {
                    // For other tunjangan, get from tunjangan_detail
                    // Hanya ambil data dari periode aktif, jangan copy dari periode sebelumnya
                    // Jika tidak ada data di periode aktif, berarti jumlah = 0 (guru tidak menerima tunjangan)
                    $sql = "SELECT jumlah FROM tunjangan_detail WHERE guru_id = ? AND tunjangan_id = ? AND periode = ?";
                    $stmt = $conn->prepare($sql);
                    if (!$stmt) {
                        continue; // Skip if prepare fails
                    }
                    $stmt->bind_param("iis", $g['id'], $t['id'], $periode);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $row = $result->fetch_assoc();
                    $jumlah = isset($row['jumlah']) ? floatval($row['jumlah']) : 0;
                    $stmt->close();
                    
                    // Kalikan dengan jumlah_periode
                    $jumlah = $jumlah * $jumlah_periode;
                }
                
                // Debug: log jumlah tunjangan yang ditemukan
                error_log("Tunjangan {$t['nama_tunjangan']} untuk {$g['nama_lengkap']}: {$jumlah}");
                
                $total_tunjangan += $jumlah;
                $tunjangan_details[$t['id']] = $jumlah;
            }
            
            // Debug: log total tunjangan
            error_log("Total tunjangan untuk {$g['nama_lengkap']}: {$total_tunjangan}");
            
            // Calculate total potongan dari potongan_detail
            $total_potongan = 0;
            $potongan_details = [];
            foreach ($potongan as $p) {
                // Debug: log potongan yang sedang diproses
                error_log("Processing potongan: {$p['nama_potongan']} (ID: {$p['id']}) for guru: {$g['nama_lengkap']}");
                // Hanya ambil data dari periode aktif, jangan copy dari periode sebelumnya
                // Jika tidak ada data di periode aktif, berarti jumlah = 0 (guru tidak menerima potongan)
                $sql = "SELECT jumlah FROM potongan_detail WHERE guru_id = ? AND potongan_id = ? AND periode = ?";
                $stmt = $conn->prepare($sql);
                if (!$stmt) {
                    continue; // Skip if prepare fails
                }
                $stmt->bind_param("iis", $g['id'], $p['id'], $periode);
                $stmt->execute();
                $result = $stmt->get_result();
                $row = $result->fetch_assoc();
                $jumlah = isset($row['jumlah']) ? floatval($row['jumlah']) : 0;
                $stmt->close();
                
                // Kalikan dengan jumlah_periode
                $jumlah = $jumlah * $jumlah_periode;
                
                // Debug: log jumlah potongan yang ditemukan
                error_log("Potongan {$p['nama_potongan']} untuk {$g['nama_lengkap']}: {$jumlah}");
                
                $total_potongan += $jumlah;
                $potongan_details[$p['id']] = $jumlah;
            }
            
            // Debug: log total potongan
            error_log("Total potongan untuk {$g['nama_lengkap']}: {$total_potongan}");
            
            // PERHITUNGAN GAJI BERSIH:
            // Formula: Gaji Bersih = Gaji Pokok + Total Tunjangan - Total Potongan
            // Langkah:
            // 1. Ambil Gaji Pokok dari tabel gaji_pokok
            // 2. Jumlahkan semua Tunjangan dari tunjangan_detail
            // 3. Jumlahkan semua Potongan dari potongan_detail
            // 4. Hitung: Gaji Pokok + Total Tunjangan - Total Potongan
            $gaji_bersih = $gaji_pokok_jumlah + $total_tunjangan - $total_potongan;
            
            // Log perhitungan untuk debugging (hanya jika ada nilai)
            if ($gaji_pokok_jumlah > 0 || $total_tunjangan > 0 || $total_potongan > 0) {
                error_log("Guru: {$g['nama_lengkap']} | Jumlah Periode: {$jumlah_periode} | Gaji Pokok: {$gaji_pokok_jumlah} | Tunjangan: {$total_tunjangan} | Potongan: {$total_potongan} | Gaji Bersih: {$gaji_bersih}");
            }
            
            // Insert or update legger
            $sql = "INSERT INTO legger_gaji (guru_id, periode, gaji_pokok, total_tunjangan, total_potongan, gaji_bersih) 
                    VALUES (?, ?, ?, ?, ?, ?)
                    ON DUPLICATE KEY UPDATE 
                    gaji_pokok=VALUES(gaji_pokok), 
                    total_tunjangan=VALUES(total_tunjangan), 
                    total_potongan=VALUES(total_potongan), 
                    gaji_bersih=VALUES(gaji_bersih)";
            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                throw new Exception('Gagal prepare query legger_gaji: ' . $conn->error);
            }
            $stmt->bind_param("isdddd", $g['id'], $periode, $gaji_pokok_jumlah, $total_tunjangan, $total_potongan, $gaji_bersih);
            
            if (!$stmt->execute()) {
                throw new Exception('Gagal insert/update legger_gaji untuk guru ' . $g['nama_lengkap'] . ': ' . $stmt->error);
            }
            
            // Get legger_id
            $legger_id = $conn->insert_id;
            if (!$legger_id) {
                $sql = "SELECT id FROM legger_gaji WHERE guru_id = ? AND periode = ?";
                $stmt = $conn->prepare($sql);
                if ($stmt) {
                    $stmt->bind_param("is", $g['id'], $periode);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    if ($row = $result->fetch_assoc()) {
                        $legger_id = $row['id'];
                    }
                    $stmt->close();
                }
            } else {
                $stmt->close();
            }
            
            // Delete old details
            if ($legger_id) {
                $sql = "DELETE FROM legger_detail WHERE legger_id = ?";
                $stmt = $conn->prepare($sql);
                if ($stmt) {
                    $stmt->bind_param("i", $legger_id);
                    $stmt->execute();
                    $stmt->close();
                }
                
                // Insert tunjangan details (jumlah sudah dikalikan dengan jumlah_periode)
                // Hanya insert tunjangan yang jumlahnya > 0 (guru benar-benar menerima tunjangan)
                foreach ($tunjangan as $t) {
                    $jumlah = isset($tunjangan_details[$t['id']]) ? $tunjangan_details[$t['id']] : 0;
                    // Hanya insert jika jumlah > 0
                    if ($jumlah > 0) {
                        $sql = "INSERT INTO legger_detail (legger_id, jenis, item_id, nama_item, jumlah) VALUES (?, 'tunjangan', ?, ?, ?)";
                        $stmt = $conn->prepare($sql);
                        if ($stmt) {
                            $stmt->bind_param("iisd", $legger_id, $t['id'], $t['nama_tunjangan'], $jumlah);
                            $stmt->execute();
                            $stmt->close();
                        }
                    }
                }
                
                // Insert potongan details (jumlah sudah dikalikan dengan jumlah_periode)
                // Hanya insert potongan yang jumlahnya > 0 (guru benar-benar menerima potongan)
                foreach ($potongan as $p) {
                    $jumlah = isset($potongan_details[$p['id']]) ? $potongan_details[$p['id']] : 0;
                    // Hanya insert jika jumlah > 0
                    if ($jumlah > 0) {
                        $sql = "INSERT INTO legger_detail (legger_id, jenis, item_id, nama_item, jumlah) VALUES (?, 'potongan', ?, ?, ?)";
                        $stmt = $conn->prepare($sql);
                        if ($stmt) {
                            $stmt->bind_param("iisd", $legger_id, $p['id'], $p['nama_potongan'], $jumlah);
                            $stmt->execute();
                            $stmt->close();
                        }
                    }
                }
            }
            
            // Commit transaction for this guru
            $conn->commit();
            $processed++;
            
        } catch (Exception $e) {
            // Rollback transaction for this guru only
            if ($conn->in_transaction) {
                $conn->rollback();
            }
            $errors[] = $g['nama_lengkap'] . ': ' . $e->getMessage();
            error_log('Error processing guru ' . $g['nama_lengkap'] . ': ' . $e->getMessage());
            // Continue processing other gurus
        }
    }
    
    // Log activity only if at least one guru was processed
    if ($processed > 0) {
        logActivity($conn, "Generate legger gaji periode {$periode} untuk {$processed} guru", 'success');
    }
    
    // Get period label with range if jumlah_periode > 1
    $period_label = $periode;
    if ($jumlah_periode > 1 && !empty($settings['periode_mulai']) && !empty($settings['periode_akhir'])) {
        if (function_exists('getPeriodRangeLabel')) {
            $period_label = getPeriodRangeLabel($settings['periode_mulai'], $settings['periode_akhir']);
        } else {
            $period_label = $settings['periode_mulai'] . ' - ' . $settings['periode_akhir'];
        }
    } else if (function_exists('getPeriodLabel')) {
        $period_label = getPeriodLabel($periode);
    }
    
    // Prepare response message
    $message = "Legger gaji berhasil digenerate untuk {$processed} guru pada periode {$period_label}";
    if (!empty($errors)) {
        $message .= ". Terjadi kesalahan pada " . count($errors) . " guru: " . implode(', ', array_slice($errors, 0, 3));
    }
    
    echo json_encode([
        'success' => $processed > 0,
        'message' => $message,
        'processed' => $processed,
        'total' => $total_guru,
        'errors' => $errors
    ]);
    
} catch (Exception $e) {
    if ($conn->in_transaction) {
        $conn->rollback();
    }
    error_log('Generate legger error: ' . $e->getMessage());
    error_log('Stack trace: ' . $e->getTraceAsString());
    
    // Return error with more details
    $error_message = 'Gagal generate legger: ' . $e->getMessage();
    if (isset($processed) && $processed > 0) {
        $error_message .= " (Berhasil memproses {$processed} dari {$total_guru} guru)";
    }
    
    echo json_encode([
        'success' => false,
        'message' => $error_message,
        'error' => $e->getMessage()
    ]);
}
?>


