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
?>
<!DOCTYPE html>
<html>
<head>
    <title>Cetak Slip Gaji Semua - <?php echo getPeriodLabel($periode); ?></title>
    <style>
        @page {
            size: F4;
            margin: 0.5mm 5mm 0 5mm;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            padding: 0;
            margin: 0;
        }
        
        .page {
            width: 210mm;
            height: 330mm;
            page-break-after: always;
            padding: 0.5mm 0.5mm 0 0.5mm;
            margin: 0 auto;
            display: grid;
            grid-template-columns: 1fr 1fr;
            grid-template-rows: 1fr 1fr;
            gap: 5mm;
            box-sizing: border-box;
        }
        
        .page:last-child {
            page-break-after: auto;
        }
        
        .slip {
            border: 1px solid #000;
            padding: 2mm 0.8mm 0.3mm 0.3mm;
            display: flex;
            flex-direction: column;
            width: 100%;
            height: 100%;
            page-break-inside: avoid;
            break-inside: avoid;
            overflow: hidden;
            box-sizing: border-box;
        }
        
        .header {
            display: flex;
            align-items: center;
            margin-bottom: 3.5mm;
            border-bottom: 1px solid #000;
            padding-bottom: 0.5mm;
            flex-shrink: 0;
        }
        
        .header-logo {
            max-width: 20px;
            max-height: 20px;
            margin-right: 0.3mm;
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
            line-height: 1.1;
        }
        
        .header-content p {
            font-size: 12px;
            margin: 0;
            line-height: 1.1;
        }
        
        .info {
            margin: 1mm 0 0 0;
            font-size: 12px;
            flex-shrink: 0;
        }
        
        .info strong {
            font-weight: bold;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 2mm 0 0 0;
            font-size: 12px;
            flex-shrink: 0;
        }
        
        table th,
        table td {
            border: 1px solid #000;
            padding: 1mm 0.5mm;
            text-align: left;
            line-height: 1.3;
            word-wrap: break-word;
        }
        
        table th {
            background-color: #f0f0f0;
            font-weight: bold;
            font-size: 12px;
        }
        
        .text-right {
            text-align: right;
        }
        
        .total {
            font-weight: bold;
        }
        
        .signature-row {
            margin-top: 15mm;
            padding-top: 0.5mm;
            border-top: 1px solid #000;
            font-size: 12px;
            flex-shrink: 0;
            display: table;
            width: 100%;
        }
        
        .signature-col {
            display: table-cell;
            width: 50%;
            text-align: center;
            vertical-align: top;
            padding: 0 0.1mm;
        }
        
        .signature-col p {
            margin: 0;
            line-height: 1.1;
        }
        
        .signature-col p:last-child {
            margin-top: 15mm;
        }
        
        .signature-line {
            width: 55%;
            margin: 3px auto 0 auto;
            min-height: 5px;
        }
        
        .tempat-tanggal {
            margin-top: 4mm;
            margin-bottom: 6mm;
            text-align: right;
            font-size: 12px;
            padding-right: 0.1mm;
            flex-shrink: 0;
        }
        
        .info {
            flex-shrink: 0;
        }
        
        .header {
            flex-shrink: 0;
        }
        
        @media print {
            body {
                margin: 0;
                padding: 0;
            }
            
            .page {
                margin: 0;
                padding: 0.5mm 0.5mm 0 0.5mm !important;
                width: 210mm;
                height: 330mm;
                display: grid !important;
                grid-template-columns: 1fr 1fr !important;
                grid-template-rows: 1fr 1fr !important;
                gap: 5mm !important;
            }
            
            .slip {
                width: 100%;
                height: 100%;
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
        
        // Start new page every 4 slips (2x2 grid)
        if ($count % 4 == 0):
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
            <table>
                <tr>
                    <th>Keterangan</th>
                    <th class="text-right">Jumlah</th>
                </tr>
                <?php if ($legger['gaji_pokok'] > 0): ?>
                <tr>
                    <td>Gaji Pokok</td>
                    <td class="text-right"><?php echo formatRupiah($legger['gaji_pokok']); ?></td>
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
                    <tr>
                        <td>Tunjangan <?php echo htmlspecialchars($d['nama_item']); ?></td>
                        <td class="text-right"><?php echo formatRupiah($d['jumlah']); ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if ($legger['total_tunjangan'] > 0): ?>
                <tr>
                    <td class="total">Total Tunjangan</td>
                    <td class="text-right total"><?php echo formatRupiah($legger['total_tunjangan']); ?></td>
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
                    <tr>
                        <td>Potongan <?php echo htmlspecialchars($d['nama_item']); ?></td>
                        <td class="text-right"><?php echo formatRupiah($d['jumlah']); ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if ($legger['total_potongan'] > 0): ?>
                <tr>
                    <td class="total">Total Potongan</td>
                    <td class="text-right total"><?php echo formatRupiah($legger['total_potongan']); ?></td>
                </tr>
                <?php endif; ?>
                <tr>
                    <td class="total">Gaji Bersih</td>
                    <td class="text-right total"><?php echo formatRupiah($legger['gaji_bersih']); ?></td>
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
        
        // Close page after 4 slips or at the end
        if ($count % 4 == 0 || $count == $total):
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

