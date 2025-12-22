<?php
require_once __DIR__ . '/../../config/config.php';
requireLogin();

// Load PhpSpreadsheet
$vendorPath = __DIR__ . '/../../vendor/autoload.php';
if (!file_exists($vendorPath)) {
    $_SESSION['error'] = 'PhpSpreadsheet tidak ditemukan. Silakan install dengan: composer require phpoffice/phpspreadsheet';
    header('Location: ' . BASE_URL . 'pages/guru/index.php');
    exit();
}

require_once $vendorPath;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

try {
    // Create new Spreadsheet object
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    
    // Set sheet title
    $sheet->setTitle('Template Import Guru');
    
    // Set header row with styling
    $headers = ['Nama Lengkap', 'TMT', 'Jumlah Jam Mengajar', 'Jabatan', 'Status Pegawai'];
    $col = 'A';
    foreach ($headers as $header) {
        $sheet->setCellValue($col . '1', $header);
        $sheet->getStyle($col . '1')->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '667eea'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                ],
            ],
        ]);
        $sheet->getColumnDimension($col)->setAutoSize(true);
        $col++;
    }
    
    // Add example data row
    $exampleRow = [
        'Contoh Guru',
        2020,
        24,
        'Guru Mata Pelajaran',
        'Honor'
    ];
    $col = 'A';
    $row = 2;
    foreach ($exampleRow as $value) {
        $sheet->setCellValue($col . $row, $value);
        $sheet->getStyle($col . $row)->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                ],
            ],
        ]);
        $col++;
    }
    
    // Add note row
    $sheet->setCellValue('A4', 'Catatan:');
    $sheet->getStyle('A4')->getFont()->setBold(true);
    $sheet->setCellValue('A5', '1. Nama Lengkap wajib diisi');
    $sheet->setCellValue('A6', '2. TMT (Tahun Mulai Tugas) format: tahun (contoh: 2020)');
    $sheet->setCellValue('A7', '3. Masa Bakti akan dihitung otomatis dari TMT');
    $sheet->setCellValue('A8', '4. Status Pegawai: Honor, PNS, atau Kontrak');
    $sheet->setCellValue('A9', '5. Hapus baris contoh sebelum import');
    
    // Set filename
    $filename = 'Template_Import_Guru_' . date('Y-m-d') . '.xlsx';
    
    // Set headers for download
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="' . $filename . '"');
    header('Cache-Control: max-age=0');
    
    // Write file to output
    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
    exit();
    
} catch (Exception $e) {
    // Fallback: redirect with error
    $_SESSION['error'] = 'Gagal membuat template: ' . $e->getMessage();
    header('Location: ' . BASE_URL . 'pages/guru/index.php');
    exit();
}
?>

