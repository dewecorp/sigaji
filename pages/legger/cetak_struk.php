<?php
require_once __DIR__ . '/../../config/config.php';
requireLogin();

$id = $_GET['id'] ?? 0;
$sql = "SELECT lg.*, g.* FROM legger_gaji lg JOIN guru g ON lg.guru_id = g.id WHERE lg.id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$legger = $stmt->get_result()->fetch_assoc();

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

$sql = "SELECT * FROM legger_detail WHERE legger_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$details = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Slip Gaji - <?php echo htmlspecialchars($legger['nama_lengkap']); ?></title>
    <style>
        @page {
            size: F4;
            margin: 8mm;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: Arial, sans-serif;
            font-size: 10px;
            padding: 0;
            margin: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }
        
        .slip {
            width: calc((210mm - 8mm * 2 - 4mm * 2 - 3mm) / 2);
            height: calc((330mm - 8mm * 2 - 4mm * 2 - 3mm) / 2);
            border: 1px solid #000;
            padding: 4mm;
            display: flex;
            flex-direction: column;
            page-break-inside: avoid;
            overflow: hidden;
            box-sizing: border-box;
        }
        
        .header {
            display: flex;
            align-items: center;
            margin-bottom: 3mm;
            border-bottom: 1px solid #000;
            padding-bottom: 2mm;
            flex-shrink: 0;
        }
        
        .header-logo {
            max-width: 40px;
            max-height: 40px;
            margin-right: 2mm;
            object-fit: contain;
            flex-shrink: 0;
        }
        
        .header-content {
            flex: 1;
            text-align: center;
        }
        
        .header-content h2 {
            font-size: 11px;
            margin: 0 0 1mm 0;
            font-weight: bold;
            line-height: 1.2;
        }
        
        .header-content p {
            font-size: 9px;
            margin: 0;
            line-height: 1.2;
        }
        
        .info {
            margin: 2mm 0;
            font-size: 9px;
            flex-shrink: 0;
        }
        
        .info strong {
            font-weight: bold;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 2mm 0;
            font-size: 7px;
            flex: 1;
            min-height: 0;
            display: table;
        }
        
        th, td {
            border: 1px solid #000;
            padding: 0.8mm;
            text-align: left;
            line-height: 1.1;
            word-wrap: break-word;
        }
        
        th {
            background-color: #f0f0f0;
            font-weight: bold;
            font-size: 7px;
        }
        
        .text-right {
            text-align: right;
        }
        
        .total {
            font-weight: bold;
        }
        
        .signature-row {
            margin-top: auto;
            padding-top: 2mm;
            border-top: 1px solid #000;
            font-size: 7px;
            flex-shrink: 0;
            display: table;
            width: 100%;
        }
        
        .signature-col {
            display: table-cell;
            width: 50%;
            text-align: center;
            vertical-align: top;
            padding: 0 2mm;
        }
        
        .signature-col p {
            margin: 0.5mm 0;
            line-height: 1.2;
        }
        
        .signature-line {
            width: 70%;
            margin: 15px auto 1mm auto;
            min-height: 20px;
        }
        
        .tempat-tanggal {
            margin-top: 1mm;
            text-align: right;
            font-size: 6px;
            padding-right: 2mm;
            flex-shrink: 0;
        }
        
        @media print {
            body {
                margin: 0;
                padding: 0;
                display: flex;
                justify-content: center;
                align-items: center;
            }
            
            .slip {
                width: calc((210mm - 8mm * 2 - 4mm * 2 - 3mm) / 2);
                height: calc((330mm - 8mm * 2 - 4mm * 2 - 3mm) / 2);
                page-break-inside: avoid;
                break-inside: avoid;
            }
            
            .header {
                page-break-inside: avoid;
            }
        }
    </style>
</head>
<body>
    <div class="slip">
        <div class="header">
            <?php if ($logo_exists): ?>
            <img src="<?php echo $logo_base64; ?>" alt="Logo Madrasah" class="header-logo" onerror="this.src='<?php echo $logo_path; ?>'">
            <?php endif; ?>
            <div class="header-content">
                <h2><?php echo strtoupper(htmlspecialchars($settings['nama_madrasah'])); ?></h2>
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
    <script>window.print();</script>
</body>
</html>


