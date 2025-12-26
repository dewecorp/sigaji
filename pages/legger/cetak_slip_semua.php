<?php
require_once __DIR__ . '/../../config/config.php';
requireLogin();

$periode = $_GET['periode'] ?? date('Y-m');

// Get settings
$sql = "SELECT * FROM settings LIMIT 1";
$settings = $conn->query($sql)->fetch_assoc();

// Get logo path - use base64 for better print compatibility
$logo_file = __DIR__ . '/../../assets/img/' . ($settings['logo'] ?? '');
$logo_exists = !empty($settings['logo']) && file_exists($logo_file);
$logo_base64 = '';
$logo_path = '';
if ($logo_exists) {
    $logo_path = BASE_URL . 'assets/img/' . $settings['logo'];
    // Convert to base64 for better print compatibility
    $image_data = file_get_contents($logo_file);
    $logo_base64 = 'data:' . mime_content_type($logo_file) . ';base64,' . base64_encode($image_data);
}

// Get all legger data for the period
$sql = "SELECT lg.*, g.nama_lengkap 
        FROM legger_gaji lg 
        JOIN guru g ON lg.guru_id = g.id 
        WHERE lg.periode = ? 
        ORDER BY g.nama_lengkap";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $periode);
$stmt->execute();
$legger_list = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get all legger details
$all_details = [];
foreach ($legger_list as $l) {
    $sql = "SELECT * FROM legger_detail WHERE legger_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $l['id']);
    $stmt->execute();
    $details = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $all_details[$l['id']] = $details;
}

// Helper function for table row style
function getTableRowStyle() {
    return 'style="height: 4mm !important; min-height: 4mm !important; max-height: 4mm !important;"';
}

// Helper function for table cell style
function getTableCellStyle() {
    return 'style="height: 4mm !important; line-height: 3.5mm !important; font-size: 8px !important; padding: 0 2mm !important; overflow: hidden !important;"';
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Cetak Slip Gaji Semua - <?php echo getPeriodLabel($periode); ?></title>
    <style>
        @page {
            size: F4;
            margin: 10mm 5mm 5mm 5mm; /* top: 1cm, right: 5mm, bottom: 5mm, left: 5mm */
        }
        
        /* Force table row height - highest priority */
        html body table tr,
        html body .slip table tr {
            height: 4mm !important;
            min-height: 4mm !important;
            max-height: 4mm !important;
        }
        
        html body table td,
        html body table th,
        html body .slip table td,
        html body .slip table th {
            height: 4mm !important;
            min-height: 4mm !important;
            max-height: 4mm !important;
            line-height: 3.5mm !important;
            font-size: 8px !important;
            padding: 0 !important;
            padding-left: 2mm !important;
            padding-right: 2mm !important;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        table, table *, table th, table td {
            box-sizing: border-box;
        }
        
        table tbody tr td,
        table thead tr th {
            box-sizing: border-box !important;
        }
        
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            padding: 0;
            margin: 0;
        }
        
        .page {
            width: 200mm; /* 210mm - 5mm left - 5mm right */
            height: 315mm; /* 330mm - 10mm top - 5mm bottom */
            page-break-after: always;
            display: flex;
            flex-direction: row;
            gap: 3mm;
            margin: 0 auto;
            align-items: flex-start;
        }
        
        .page:last-child {
            page-break-after: auto;
        }
        
        .slip {
            width: calc((200mm - 3mm) / 2); /* Setengah lebar halaman dikurangi gap */
            height: 170mm; /* Setengah tinggi kertas F4 dengan sedikit tambahan untuk memastikan cukup tinggi */
            min-height: 170mm;
            max-height: 170mm;
            page-break-inside: avoid;
            break-inside: avoid;
            border: 1px solid #000;
            padding: 5mm;
            display: flex;
            flex-direction: column;
            box-sizing: border-box;
            overflow: hidden;
        }
        
        .header {
            display: flex;
            align-items: center;
            margin-bottom: 1mm;
            border-bottom: 1px solid #000;
            padding-bottom: 3mm;
            flex-shrink: 0;
        }
        
        .header-logo {
            max-width: 35px;
            max-height: 35px;
            margin-right: 5mm;
            object-fit: contain;
            flex-shrink: 0;
        }
        
        .header-content {
            flex: 1;
            text-align: center;
        }
        
        .header-content h3 {
            font-size: 16px;
            margin: 0;
            font-weight: bold;
            line-height: 1.3;
            letter-spacing: 0.5px;
        }
        
        .header-content p {
            font-size: 12px;
            margin: 3px 0 0 0;
            line-height: 1.3;
        }
        
        .info {
            margin: 2mm 0;
            font-size: 12px;
            flex-shrink: 0;
        }
        
        .info strong {
            font-weight: bold;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            border-spacing: 0;
            margin: 2mm 0 2mm 0;
            font-size: 11px;
            flex: 1;
            min-height: 0;
            table-layout: fixed;
        }
        
        table tr {
            height: 4mm !important;
            min-height: 4mm !important;
            max-height: 4mm !important;
            display: table-row;
        }
        
        table th,
        table td {
            border: 1px solid #000;
            padding: 0 !important;
            padding-left: 2mm !important;
            padding-right: 2mm !important;
            text-align: left;
            line-height: 3.5mm !important;
            vertical-align: middle !important;
            overflow: hidden !important;
            font-size: 8px !important;
            height: 4mm !important;
            min-height: 4mm !important;
            max-height: 4mm !important;
            white-space: nowrap;
            text-overflow: ellipsis;
            box-sizing: border-box !important;
            display: table-cell !important;
            margin: 0 !important;
        }
        
        table th {
            background-color: #f0f0f0;
            font-weight: bold;
            font-size: 8px !important;
            text-align: center;
            text-transform: uppercase;
            padding: 0 !important;
            padding-left: 2mm !important;
            padding-right: 2mm !important;
            height: 4mm !important;
            min-height: 4mm !important;
            max-height: 4mm !important;
            line-height: 3.5mm !important;
            box-sizing: border-box !important;
            display: table-cell !important;
            margin: 0 !important;
        }
        
        table td {
            line-height: 3.5mm !important;
        }
        
        .text-right {
            text-align: right;
        }
        
        .text-center {
            text-align: center;
        }
        
        .total {
            font-weight: bold;
        }
        
        .signature-row {
            margin-top: 3mm;
            padding-top: 2mm;
            border-top: 1px solid #000;
            font-size: 11px;
            flex-shrink: 0;
            display: table;
            width: 100%;
        }
        
        .signature-col {
            display: table-cell;
            width: 50%;
            text-align: center;
            vertical-align: top;
            padding: 0 5mm;
        }
        
        .signature-col p {
            margin: 0;
            line-height: 1.4;
        }
        
        .signature-col p:last-child {
            margin-top: 15mm;
        }
        
        .signature-line {
            width: 55%;
            margin: 3px auto 0 auto;
            min-height: 5px;
            border-top: none;
        }
        
        .tempat-tanggal {
            margin-top: 0;
            margin-bottom: 5mm;
            text-align: right;
            font-size: 11px;
            padding-right: 2mm;
            flex-shrink: 0;
        }
        
        @media print {
            body {
                margin: 0;
                padding: 0;
            }
            
            .page {
                width: 200mm !important;
                height: 315mm !important;
                display: flex !important;
                flex-direction: row !important;
                gap: 3mm !important;
                align-items: flex-start !important;
            }
            
            .slip {
                width: calc((200mm - 3mm) / 2) !important;
                height: 170mm !important;
                min-height: 170mm !important;
                max-height: 170mm !important;
                page-break-inside: avoid !important;
                break-inside: avoid !important;
                overflow: hidden !important;
            }
            
            .header {
                page-break-inside: avoid;
                break-inside: avoid;
            }
            
            table {
                page-break-inside: avoid;
                break-inside: avoid;
            }
            
            table tr {
                height: 4mm !important;
                min-height: 4mm !important;
                max-height: 4mm !important;
                display: table-row !important;
            }
            
            table th,
            table td {
                padding: 0 !important;
                padding-left: 2mm !important;
                padding-right: 2mm !important;
            line-height: 3.5mm !important;
            overflow: hidden !important;
            font-size: 8px !important;
                height: 4mm !important;
                min-height: 4mm !important;
                max-height: 4mm !important;
                white-space: nowrap !important;
                text-overflow: ellipsis !important;
                display: table-cell !important;
                vertical-align: middle !important;
            }
            
            table th {
                font-size: 8px !important;
                height: 4mm !important;
                min-height: 4mm !important;
                max-height: 4mm !important;
                line-height: 3.5mm !important;
                display: table-cell !important;
            }
            
            .signature-row {
                page-break-inside: avoid;
                break-inside: avoid;
            }
        }
    </style>
</head>
<body>
    <?php
    $count = 0;
    $total = count($legger_list);
    
    foreach ($legger_list as $index => $legger):
        $details = $all_details[$legger['id']];
        
        // Start new page every 2 slips
        if ($count % 2 == 0):
            if ($count > 0):
                echo '</div>'; // Close previous page
            endif;
            echo '<div class="page">';
        endif;
    ?>
        <div class="slip">
            <div class="header">
                <?php if ($logo_exists): ?>
                <img src="<?php echo $logo_base64; ?>" alt="Logo Madrasah" class="header-logo" onerror="this.src='<?php echo $logo_path; ?>'">
                <?php endif; ?>
                <div class="header-content">
                    <h3><?php echo strtoupper(htmlspecialchars($settings['nama_madrasah'])); ?></h3>
                    <p>Slip Gaji 
                        <?php 
                        $jumlah_periode = $settings['jumlah_periode'] ?? 1;
                        $periode_mulai = $settings['periode_mulai'] ?? '';
                        $periode_akhir = $settings['periode_akhir'] ?? '';
                        
                        if ($jumlah_periode > 1 && !empty($periode_mulai) && !empty($periode_akhir)) {
                            echo 'Bulan ' . getPeriodRangeLabel($periode_mulai, $periode_akhir);
                        } else {
                            echo 'Bulan ' . getPeriodLabel($legger['periode']);
                        }
                        ?>
                    </p>
                </div>
            </div>
            <div class="info">
                <p><strong>Nama:</strong> <?php echo htmlspecialchars($legger['nama_lengkap']); ?></p>
            </div>
            <table cellpadding="0" cellspacing="0" style="border-collapse: collapse;">
                <tr <?php echo getTableRowStyle(); ?>>
                    <th <?php echo getTableCellStyle(); ?>>Keterangan</th>
                    <th class="text-center" <?php echo getTableCellStyle(); ?>>Jumlah</th>
                </tr>
                <?php if ($legger['gaji_pokok'] > 0): ?>
                <tr <?php echo getTableRowStyle(); ?>>
                    <td <?php echo getTableCellStyle(); ?>>Gaji Pokok</td>
                    <td class="text-center" <?php echo getTableCellStyle(); ?>><?php echo formatRupiah($legger['gaji_pokok']); ?></td>
                </tr>
                <?php endif; ?>
                <?php 
                $tunjangan_items = [];
                foreach ($details as $d): 
                    if ($d['jenis'] == 'tunjangan' && $d['jumlah'] > 0):
                        $tunjangan_items[] = $d;
                    endif;
                endforeach;
                
                foreach ($tunjangan_items as $d):
                ?>
                    <tr <?php echo getTableRowStyle(); ?>>
                        <td <?php echo getTableCellStyle(); ?>>Tunjangan <?php echo htmlspecialchars($d['nama_item']); ?></td>
                        <td class="text-center" <?php echo getTableCellStyle(); ?>><?php echo formatRupiah($d['jumlah']); ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if ($legger['total_tunjangan'] > 0): ?>
                <tr <?php echo getTableRowStyle(); ?>>
                    <td class="total" <?php echo getTableCellStyle(); ?>>Total Tunjangan</td>
                    <td class="text-center total" <?php echo getTableCellStyle(); ?>><?php echo formatRupiah($legger['total_tunjangan']); ?></td>
                </tr>
                <?php endif; ?>
                <?php 
                $potongan_items = [];
                foreach ($details as $d): 
                    if ($d['jenis'] == 'potongan' && $d['jumlah'] > 0):
                        $potongan_items[] = $d;
                    endif;
                endforeach;
                
                foreach ($potongan_items as $d):
                ?>
                    <tr <?php echo getTableRowStyle(); ?>>
                        <td <?php echo getTableCellStyle(); ?>>Potongan <?php echo htmlspecialchars($d['nama_item']); ?></td>
                        <td class="text-center" <?php echo getTableCellStyle(); ?>><?php echo formatRupiah($d['jumlah']); ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if ($legger['total_potongan'] > 0): ?>
                <tr <?php echo getTableRowStyle(); ?>>
                    <td class="total" <?php echo getTableCellStyle(); ?>>Total Potongan</td>
                    <td class="text-center total" <?php echo getTableCellStyle(); ?>><?php echo formatRupiah($legger['total_potongan']); ?></td>
                </tr>
                <?php endif; ?>
                <tr <?php echo getTableRowStyle(); ?>>
                    <td class="total" <?php echo getTableCellStyle(); ?>>Gaji Bersih</td>
                    <td class="text-center total" <?php echo getTableCellStyle(); ?>><?php echo formatRupiah($legger['gaji_bersih']); ?></td>
                </tr>
            </table>
            <?php if (!empty($settings['tempat']) || !empty($settings['hari_tanggal'])): ?>
            <div class="tempat-tanggal">
                <?php if (!empty($settings['tempat'])): ?>
                    <?php echo htmlspecialchars($settings['tempat']); ?><?php if (!empty($settings['hari_tanggal'])): ?>,<?php endif; ?>
                <?php endif; ?>
                <?php if (!empty($settings['hari_tanggal'])): ?>
                    <?php echo formatTanggalTanpaHari($settings['hari_tanggal']); ?>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            <div class="signature-row">
                <div class="signature-col">
                    <p><strong>Kepala Madrasah</strong></p>
                    <div class="signature-line"></div>
                    <p><?php echo htmlspecialchars($settings['nama_kepala'] ?? ''); ?></p>
                </div>
                <div class="signature-col">
                    <p><strong>Bendahara</strong></p>
                    <div class="signature-line"></div>
                    <p><?php echo htmlspecialchars($settings['nama_bendahara'] ?? ''); ?></p>
                </div>
            </div>
        </div>
    <?php
        $count++;
        
        // Close page after 2 slips or at the end
        if ($count % 2 == 0 || $count == $total):
            echo '</div>'; // Close page
        endif;
    endforeach;
    ?>
    <script>
        window.onload = function() {
            window.print();
        };
    </script>
</body>
</html>

