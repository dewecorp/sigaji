<?php
// Prevent any output before headers
ob_start();

require_once __DIR__ . '/../../config/config.php';
requireLogin();

// Get periode from GET or use periode_aktif from settings
$periode = $_GET['periode'] ?? null;
if (empty($periode)) {
    $sql_settings = "SELECT periode_aktif FROM settings LIMIT 1";
    $result_settings = $conn->query($sql_settings);
    if ($result_settings && $row_settings = $result_settings->fetch_assoc()) {
        $periode = $row_settings['periode_aktif'] ?? date('Y-m');
    } else {
        $periode = date('Y-m');
    }
}

// Load PhpSpreadsheet
$vendorPath = __DIR__ . '/../../vendor/autoload.php';
if (!file_exists($vendorPath)) {
    $_SESSION['error'] = 'PhpSpreadsheet tidak ditemukan. Silakan install dengan: composer require phpoffice/phpspreadsheet';
    header('Location: ' . BASE_URL . 'pages/legger/index.php');
    exit();
}

require_once $vendorPath;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

// Function to get list of months
function getMonthList($periode_mulai, $periode_akhir, $jumlah_periode) {
    $months = [];
    if (!empty($periode_mulai) && !empty($periode_akhir) && $jumlah_periode > 1) {
        $start = new DateTime($periode_mulai . '-01');
        $end = new DateTime($periode_akhir . '-01');
        $current = clone $start;
        
        while ($current <= $end) {
            $months[] = $current->format('Y-m');
            $current->modify('+1 month');
        }
    } else {
        $months[] = $periode_mulai ?: date('Y-m');
    }
    return $months;
}

// Function to get month name for Excel tab (short format)
function getMonthNameForTab($periode) {
    $month_names = [
        1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
        5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
        9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
    ];
    
    $date = DateTime::createFromFormat('Y-m', $periode);
    if ($date) {
        $month = (int)$date->format('m');
        $year = $date->format('Y');
        return $month_names[$month] . ' ' . $year;
    }
    return $periode;
}

// Function to increment column letter (A -> B -> C -> ... -> Z -> AA -> AB -> ...)
function incrementColumn($col) {
    $col_index = Coordinate::columnIndexFromString($col);
    return Coordinate::stringFromColumnIndex($col_index + 1);
}

try {
    // Get settings
    $sql_settings = "SELECT * FROM settings LIMIT 1";
    $settings = $conn->query($sql_settings)->fetch_assoc();
    $jumlah_periode = isset($settings['jumlah_periode']) ? intval($settings['jumlah_periode']) : 1;
    $periode_mulai = $settings['periode_mulai'] ?? '';
    $periode_akhir = $settings['periode_akhir'] ?? '';
    
    // Get list of months
    $month_list = getMonthList($periode_mulai, $periode_akhir, $jumlah_periode);
    
    // Get base legger data (from periode_aktif, which contains multiplied data)
    $sql = "SELECT lg.*, g.nama_lengkap 
            FROM legger_gaji lg 
            JOIN guru g ON lg.guru_id = g.id 
            WHERE lg.periode = ? 
            ORDER BY g.nama_lengkap";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $periode);
    $stmt->execute();
    $legger_base = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    
    // Get tunjangan and potongan
    $sql = "SELECT DISTINCT t.* FROM tunjangan t 
            WHERE t.aktif = 1 
            OR EXISTS (
                SELECT 1 FROM legger_detail ld 
                INNER JOIN legger_gaji lg ON ld.legger_id = lg.id 
                WHERE lg.periode = ? 
                AND ld.jenis = 'tunjangan' 
                AND ld.item_id = t.id
            )
            ORDER BY t.nama_tunjangan";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $periode);
    $stmt->execute();
    $tunjangan = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    
    $sql = "SELECT DISTINCT p.* FROM potongan p 
            WHERE p.aktif = 1 
            OR EXISTS (
                SELECT 1 FROM legger_detail ld 
                INNER JOIN legger_gaji lg ON ld.legger_id = lg.id 
                WHERE lg.periode = ? 
                AND ld.jenis = 'potongan' 
                AND ld.item_id = p.id
            )
            OR EXISTS (
                SELECT 1 FROM potongan_detail pd 
                WHERE pd.potongan_id = p.id 
                AND pd.periode = ?
            )
            ORDER BY p.nama_potongan";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $periode, $periode);
    $stmt->execute();
    $potongan = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    
    // Validate month list
    if (empty($month_list)) {
        throw new Exception('Tidak ada bulan yang ditemukan untuk periode yang dipilih');
    }
    
    // Create new Spreadsheet object
    $spreadsheet = new Spreadsheet();
    
    // Remove default sheet
    $spreadsheet->removeSheetByIndex(0);
    
    // Create sheet for each month
    $sheet_created = false;
    foreach ($month_list as $month_index => $current_month) {
        $sheet = $spreadsheet->createSheet();
        // Set tab name to month name (e.g., "Januari 2024", "Februari 2024")
        $tab_name = getMonthNameForTab($current_month);
        // Excel sheet name max 31 characters, truncate if needed
        if (strlen($tab_name) > 31) {
            $tab_name = substr($tab_name, 0, 31);
        }
        $sheet->setTitle($tab_name);
        
        $row = 1;
        
        // Header row 1 (Title)
        $sheet->setCellValue('A' . $row, strtoupper($settings['nama_madrasah'] ?? 'MADRASAH IBTIDAIYAH'));
        $col_count = 3 + count($tunjangan) + 1 + count($potongan) + 3; // No, Nama, Gaji Pokok, Tunjangan cols, Total Tunjangan, Potongan cols, Total Potongan, Gaji Bersih, Tanda Tangan
        $sheet->mergeCells('A' . $row . ':' . Coordinate::stringFromColumnIndex($col_count) . $row);
        $sheet->getStyle('A' . $row)->applyFromArray([
            'font' => ['bold' => true, 'size' => 16],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $row++;
        
        // Header row 2 (Subtitle)
        $sheet->setCellValue('A' . $row, 'LEGGER GAJI');
        $sheet->mergeCells('A' . $row . ':' . Coordinate::stringFromColumnIndex($col_count) . $row);
        $sheet->getStyle('A' . $row)->applyFromArray([
            'font' => ['bold' => true, 'size' => 14],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $row++;
        
        // Header row 3 (Period)
        $sheet->setCellValue('A' . $row, 'Periode: ' . getPeriodLabel($current_month));
        $sheet->mergeCells('A' . $row . ':' . Coordinate::stringFromColumnIndex($col_count) . $row);
        $sheet->getStyle('A' . $row)->applyFromArray([
            'font' => ['size' => 12],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $row++;
        $row++; // Empty row
        
        // Table header row 1
        $col_letter = 'A';
        $sheet->setCellValue($col_letter . $row, 'No');
        $col_letter = incrementColumn($col_letter);
        $sheet->setCellValue($col_letter . $row, 'Nama');
        $col_letter = incrementColumn($col_letter);
        $sheet->setCellValue($col_letter . $row, 'Gaji Pokok');
        $col_letter = incrementColumn($col_letter);
        
        // Tunjangan header
        if (count($tunjangan) > 0) {
            foreach ($tunjangan as $t) {
                $sheet->setCellValue($col_letter . $row, $t['nama_tunjangan']);
                $col_letter = incrementColumn($col_letter);
            }
        }
        
        $sheet->setCellValue($col_letter . $row, 'Total Tunjangan');
        $col_letter = incrementColumn($col_letter);
        
        // Potongan header
        if (count($potongan) > 0) {
            foreach ($potongan as $p) {
                $sheet->setCellValue($col_letter . $row, $p['nama_potongan']);
                $col_letter = incrementColumn($col_letter);
            }
        }
        
        $sheet->setCellValue($col_letter . $row, 'Total Potongan');
        $col_letter = incrementColumn($col_letter);
        $sheet->setCellValue($col_letter . $row, 'Gaji Bersih');
        $col_letter = incrementColumn($col_letter);
        $sheet->setCellValue($col_letter . $row, 'Tanda Tangan');
        $last_header_col = $col_letter; // Save last column
        
        // Style header row
        $header_range = 'A' . $row . ':' . $last_header_col . $row;
        $sheet->getStyle($header_range)->applyFromArray([
            'font' => ['bold' => true],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E7E6E6']],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $row++;
        
        // Data rows
        $no = 1;
        $total_gaji_pokok = 0;
        $total_tunjangan_all = 0;
        $total_potongan_all = 0;
        $total_gaji_bersih = 0;
        
        foreach ($legger_base as $l) {
            // Get legger details
            $sql = "SELECT * FROM legger_detail WHERE legger_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $l['id']);
            $stmt->execute();
            $details = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            
            $tunjangan_data = [];
            $potongan_data = [];
            foreach ($details as $d) {
                if ($d['jenis'] == 'tunjangan') {
                    $tunjangan_data[$d['item_id']] = $d['jumlah'];
                } else {
                    $potongan_data[$d['item_id']] = $d['jumlah'];
                }
            }
            
            // Calculate per month values
            $gaji_pokok_per_bulan = $l['gaji_pokok'] / $jumlah_periode;
            $total_tunjangan_per_bulan = $l['total_tunjangan'] / $jumlah_periode;
            $total_potongan_per_bulan = $l['total_potongan'] / $jumlah_periode;
            $gaji_bersih_per_bulan = $l['gaji_bersih'] / $jumlah_periode;
            
            $total_gaji_pokok += $gaji_pokok_per_bulan;
            $total_tunjangan_all += $total_tunjangan_per_bulan;
            $total_potongan_all += $total_potongan_per_bulan;
            $total_gaji_bersih += $gaji_bersih_per_bulan;
            
            $col_letter = 'A';
            $first_col = $col_letter;
            $sheet->setCellValue($col_letter . $row, $no++);
            $col_letter = incrementColumn($col_letter);
            $sheet->setCellValue($col_letter . $row, $l['nama_lengkap']);
            $col_letter = incrementColumn($col_letter);
            $gaji_pokok_col = $col_letter;
            $sheet->setCellValue($col_letter . $row, $gaji_pokok_per_bulan);
            $sheet->getStyle($gaji_pokok_col . $row)->getNumberFormat()->setFormatCode('#,##0');
            $col_letter = incrementColumn($col_letter);
            
            // Tunjangan data
            foreach ($tunjangan as $t) {
                $jumlah = ($tunjangan_data[$t['id']] ?? 0) / $jumlah_periode;
                $tunjangan_col = $col_letter;
                $sheet->setCellValue($col_letter . $row, $jumlah);
                $sheet->getStyle($tunjangan_col . $row)->getNumberFormat()->setFormatCode('#,##0');
                $col_letter = incrementColumn($col_letter);
            }
            
            $total_tunjangan_col = $col_letter;
            $sheet->setCellValue($col_letter . $row, $total_tunjangan_per_bulan);
            $sheet->getStyle($total_tunjangan_col . $row)->getNumberFormat()->setFormatCode('#,##0');
            $col_letter = incrementColumn($col_letter);
            
            // Potongan data
            foreach ($potongan as $p) {
                $jumlah = ($potongan_data[$p['id']] ?? 0) / $jumlah_periode;
                $potongan_col = $col_letter;
                $sheet->setCellValue($col_letter . $row, $jumlah);
                $sheet->getStyle($potongan_col . $row)->getNumberFormat()->setFormatCode('#,##0');
                $col_letter = incrementColumn($col_letter);
            }
            
            $total_potongan_col = $col_letter;
            $sheet->setCellValue($col_letter . $row, $total_potongan_per_bulan);
            $sheet->getStyle($total_potongan_col . $row)->getNumberFormat()->setFormatCode('#,##0');
            $col_letter = incrementColumn($col_letter);
            
            $gaji_bersih_col = $col_letter;
            $sheet->setCellValue($col_letter . $row, $gaji_bersih_per_bulan);
            $sheet->getStyle($gaji_bersih_col . $row)->getNumberFormat()->setFormatCode('#,##0');
            $col_letter = incrementColumn($col_letter);
            
            $sheet->setCellValue($col_letter . $row, '');
            $last_col = $col_letter; // Last column
            
            // Style data row
            $data_range = $first_col . $row . ':' . $last_col . $row;
            $sheet->getStyle($data_range)->applyFromArray([
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
            ]);
            $row++;
        }
        
        // Total row
        $col_letter = 'A';
        $first_col_total = $col_letter;
        $sheet->setCellValue($col_letter . $row, 'TOTAL');
        $col_letter = incrementColumn($col_letter);
        $sheet->setCellValue($col_letter . $row, '');
        $col_letter = incrementColumn($col_letter);
        $total_gaji_pokok_col = $col_letter;
        $sheet->setCellValue($col_letter . $row, $total_gaji_pokok);
        $sheet->getStyle($total_gaji_pokok_col . $row)->getNumberFormat()->setFormatCode('#,##0');
        $col_letter = incrementColumn($col_letter);
        
        // Skip tunjangan columns
        foreach ($tunjangan as $t) {
            $col_letter = incrementColumn($col_letter);
        }
        
        $total_tunjangan_col = $col_letter;
        $sheet->setCellValue($col_letter . $row, $total_tunjangan_all);
        $sheet->getStyle($total_tunjangan_col . $row)->getNumberFormat()->setFormatCode('#,##0');
        $col_letter = incrementColumn($col_letter);
        
        // Skip potongan columns
        foreach ($potongan as $p) {
            $col_letter = incrementColumn($col_letter);
        }
        
        $total_potongan_col = $col_letter;
        $sheet->setCellValue($col_letter . $row, $total_potongan_all);
        $sheet->getStyle($total_potongan_col . $row)->getNumberFormat()->setFormatCode('#,##0');
        $col_letter = incrementColumn($col_letter);
        
        $total_gaji_bersih_col = $col_letter;
        $sheet->setCellValue($col_letter . $row, $total_gaji_bersih);
        $sheet->getStyle($total_gaji_bersih_col . $row)->getNumberFormat()->setFormatCode('#,##0');
        $col_letter = incrementColumn($col_letter);
        
        $sheet->setCellValue($col_letter . $row, '');
        $last_col_total = $col_letter; // Last column
        
        // Style total row
        $total_range = $first_col_total . $row . ':' . $last_col_total . $row;
        $sheet->getStyle($total_range)->applyFromArray([
            'font' => ['bold' => true],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E7E6E6']],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
        ]);
        $sheet->getStyle('A' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet_created = true;
    }
    
    // Validate that at least one sheet was created
    if (!$sheet_created) {
        throw new Exception('Tidak ada data legger untuk periode yang dipilih');
    }
    
    // Set active sheet to first
    $spreadsheet->setActiveSheetIndex(0);
    
    // Set filename
    $filename = 'Legger_Gaji_' . str_replace('-', '_', $periode) . '_' . date('Ymd') . '.xlsx';
    
    // Clear any output buffer
    if (ob_get_level()) {
        ob_end_clean();
    }
    
    // Set headers for download - must be before any output
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="' . $filename . '"');
    header('Cache-Control: max-age=0');
    header('Pragma: public');
    
    // Write file to output
    try {
        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
    } catch (Exception $write_error) {
        throw $write_error;
    }
    exit();
    
} catch (Exception $e) {
    // Clear output buffer
    ob_end_clean();
    
    // Fallback: redirect with error
    $_SESSION['error'] = 'Gagal export Excel: ' . $e->getMessage();
    header('Location: ' . BASE_URL . 'pages/legger/index.php');
    exit();
}
?>

