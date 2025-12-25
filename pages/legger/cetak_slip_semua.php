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
            margin: 10mm 5mm 5mm 5mm; /* top: 1cm, right: 5mm, bottom: 5mm, left: 5mm */
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        table, table *, table th, table td {
            box-sizing: border-box;
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
            font-size: 12px;
            flex: 1;
            min-height: 0;
            table-layout: fixed;
        }
        
        table tbody tr,
        table thead tr {
            height: 4mm;
            display: table-row;
        }
        
        table th,
        table td {
            border: 1px solid #000;
            padding: 0;
            text-align: left;
            line-height: 1;
            vertical-align: middle;
            height: 4mm;
            overflow: hidden;
        }
        
        table th {
            background-color: #f0f0f0;
            font-weight: bold;
            font-size: 12px;
            text-align: center;
            text-transform: uppercase;
            padding: 0;
            height: 4mm;
        }
        
        table th,
        table td {
            padding-left: 2mm;
            padding-right: 2mm;
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
            
            table tbody tr,
            table thead tr {
                height: 4mm !important;
                display: table-row !important;
            }
            
            table th,
            table td {
                padding: 0 !important;
                padding-left: 2mm !important;
                padding-right: 2mm !important;
                line-height: 1 !important;
                height: 4mm !important;
                overflow: hidden !important;
            }
            
            table th {
                height: 4mm !important;
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
            <table>
                <tr>
                    <th>Keterangan</th>
                    <th class="text-center">Jumlah</th>
                </tr>
                <?php if ($legger['gaji_pokok'] > 0): ?>
                <tr>
                    <td>Gaji Pokok</td>
                    <td class="text-center"><?php echo formatRupiah($legger['gaji_pokok']); ?></td>
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
                        <td class="text-center"><?php echo formatRupiah($d['jumlah']); ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if ($legger['total_tunjangan'] > 0): ?>
                <tr>
                    <td class="total">Total Tunjangan</td>
                    <td class="text-center total"><?php echo formatRupiah($legger['total_tunjangan']); ?></td>
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
                        <td class="text-center"><?php echo formatRupiah($d['jumlah']); ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if ($legger['total_potongan'] > 0): ?>
                <tr>
                    <td class="total">Total Potongan</td>
                    <td class="text-center total"><?php echo formatRupiah($legger['total_potongan']); ?></td>
                </tr>
                <?php endif; ?>
                <tr>
                    <td class="total">Gaji Bersih</td>
                    <td class="text-center total"><?php echo formatRupiah($legger['gaji_bersih']); ?></td>
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

