<?php
require_once __DIR__ . '/../../config/config.php';
requireLogin();

$periode = $_GET['periode'] ?? date('Y-m');

// Get settings
$sql = "SELECT * FROM settings LIMIT 1";
$settings = $conn->query($sql)->fetch_assoc();
$jumlah_periode = isset($settings['jumlah_periode']) ? intval($settings['jumlah_periode']) : 1;
$periode_mulai = $settings['periode_mulai'] ?? '';
$periode_akhir = $settings['periode_akhir'] ?? '';

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

// Get active tunjangan and potongan
$sql = "SELECT * FROM tunjangan WHERE aktif = 1 ORDER BY nama_tunjangan";
$tunjangan = $conn->query($sql)->fetch_all(MYSQLI_ASSOC);

$sql = "SELECT * FROM potongan WHERE aktif = 1 ORDER BY nama_potongan";
$potongan = $conn->query($sql)->fetch_all(MYSQLI_ASSOC);

// Get legger data
$sql = "SELECT lg.*, g.nama_lengkap 
        FROM legger_gaji lg 
        JOIN guru g ON lg.guru_id = g.id 
        WHERE lg.periode = ? 
        ORDER BY g.nama_lengkap";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $periode);
$stmt->execute();
$legger = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Legger Gaji - <?php echo getPeriodLabel($periode); ?></title>
    <style>
        @page {
            size: 330mm 210mm;
            margin: 10mm 5mm;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: Arial, sans-serif;
            font-size: 13px;
            padding: 0;
            margin: 0;
            background: white;
        }
        
        .content-wrapper {
            width: 310mm;
            max-width: 310mm;
            padding: 10mm;
            box-sizing: border-box;
            page-break-inside: avoid !important;
            break-inside: avoid !important;
            background: white;
        }
        
        .header {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
            border-bottom: 2px solid #000;
            padding-bottom: 10px;
            padding-left: 5px;
            page-break-inside: avoid !important;
            break-inside: avoid !important;
        }
        
        .period-info {
            page-break-inside: avoid !important;
            break-inside: avoid !important;
        }
        
        .header-logo {
            max-width: 60px;
            max-height: 60px;
            margin-right: 0px !important;
            object-fit: contain;
            flex-shrink: 0;
        }
        
        .header-content {
            flex: 1;
            text-align: center;
        }
        
        .header-content h2 {
            font-size: 18px;
            margin: 5px 0;
            font-weight: bold;
        }
        
        .header-content p {
            font-size: 14px;
            margin: 3px 0;
        }
        
        .period-info {
            text-align: center;
            margin-bottom: 10px;
            font-size: 13px;
            font-weight: bold;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 0;
            font-size: 12px;
            table-layout: fixed;
        }
        
        table th,
        table td {
            border: 1px solid #000;
            padding: 10px 7px;
            text-align: left;
            vertical-align: middle;
        }
        
        table td {
            height: 45px;
        }
        
        table th {
            height: auto;
            min-height: 45px;
        }
        
        table th {
            background-color: #f0f0f0;
            font-weight: bold;
            text-align: center;
            font-size: 12px;
            white-space: normal !important;
            word-wrap: break-word !important;
            overflow-wrap: break-word !important;
            line-height: 1.3 !important;
            overflow: visible !important;
            text-overflow: clip !important;
        }
        
        table th[colspan] {
            background-color: #e0e0e0;
            font-weight: bold;
            white-space: normal !important;
            word-wrap: break-word !important;
            overflow-wrap: break-word !important;
            overflow: visible !important;
            text-overflow: clip !important;
        }
        
        /* Kolom No - width tetap kecil */
        table th:first-child {
            width: 35px !important;
            min-width: 35px !important;
            max-width: 35px !important;
            text-align: center !important;
            padding: 10px 5px !important;
            white-space: normal !important;
            word-wrap: break-word !important;
            overflow-wrap: break-word !important;
            overflow: visible !important;
            text-overflow: clip !important;
            box-sizing: border-box !important;
        }
        table td:first-child {
            width: 35px !important;
            min-width: 35px !important;
            max-width: 35px !important;
            text-align: center !important;
            padding: 10px 5px !important;
            box-sizing: border-box !important;
        }
        
        /* Kolom Nama - width lebih besar */
        table th:nth-child(2) {
            width: 140px !important;
            min-width: 140px !important;
            max-width: 140px !important;
            padding: 10px 7px !important;
            white-space: normal !important;
            word-wrap: break-word !important;
            overflow-wrap: break-word !important;
            overflow: visible !important;
            text-overflow: clip !important;
            box-sizing: border-box !important;
        }
        table td:nth-child(2) {
            width: 140px !important;
            min-width: 140px !important;
            max-width: 140px !important;
            padding: 10px 7px !important;
            box-sizing: border-box !important;
        }
        
        /* Kolom dengan angka - align right */
        table td:nth-child(n+3):not(:last-child) {
            text-align: right;
            font-family: 'Courier New', monospace;
            font-size: 10px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        /* Kolom Gaji Pokok */
        table th:nth-child(3),
        table thead th:nth-child(3) {
            width: 70px !important;
            min-width: 70px !important;
            max-width: 70px !important;
            text-align: center !important;
            font-size: 11px !important;
            padding: 8px 4px !important;
            white-space: normal !important;
            word-wrap: break-word !important;
            overflow-wrap: break-word !important;
            overflow: visible !important;
            text-overflow: clip !important;
            line-height: 1.3 !important;
            box-sizing: border-box !important;
        }
        table td:nth-child(3),
        table tbody td:nth-child(3) {
            width: 70px !important;
            min-width: 70px !important;
            max-width: 70px !important;
            text-align: right !important;
            font-size: 10px !important;
            padding: 8px 4px !important;
            white-space: nowrap !important;
            overflow: hidden !important;
            text-overflow: ellipsis !important;
            box-sizing: border-box !important;
        }
        
        /* Kolom Tunjangan individual - ditambah lebar */
        table thead tr:last-child th:not([rowspan]):not([colspan]) {
            width: 105px !important;
            min-width: 105px !important;
            max-width: 105px !important;
            font-size: 11px !important;
            padding: 8px 4px !important;
            white-space: normal !important;
            word-wrap: break-word !important;
            overflow-wrap: break-word !important;
            line-height: 1.3 !important;
            overflow: visible !important;
            text-overflow: clip !important;
            box-sizing: border-box !important;
        }
        
        /* Kolom Tunjangan dan Potongan individual di tbody - ditambah lebar */
        table tbody tr td:not(:first-child):not(:nth-child(2)):not(:nth-child(3)):not([colspan]):not(:last-child):not(:nth-last-child(4)):not(:nth-last-child(3)):not(:nth-last-child(2)) {
            width: 105px !important;
            min-width: 105px !important;
            max-width: 105px !important;
            font-size: 10px !important;
            padding: 8px 4px !important;
            white-space: nowrap !important;
            overflow: hidden !important;
            text-overflow: ellipsis !important;
            box-sizing: border-box !important;
        }
        
        /* Kolom Total Tunjangan, Total Potongan, Gaji Bersih - sama dengan Gaji Pokok */
        table th[rowspan="2"]:nth-child(n+4):not(:last-child) {
            width: 70px !important;
            min-width: 70px !important;
            max-width: 70px !important;
            text-align: center !important;
            font-weight: bold !important;
            padding: 8px 4px !important;
            white-space: normal !important;
            word-wrap: break-word !important;
            overflow-wrap: break-word !important;
            overflow: visible !important;
            text-overflow: clip !important;
            line-height: 1.3 !important;
            box-sizing: border-box !important;
        }
        
        /* Body kolom Total Tunjangan, Total Potongan, Gaji Bersih */
        table td:nth-last-child(4),
        table td:nth-last-child(3),
        table td:nth-last-child(2) {
            width: 70px !important;
            min-width: 70px !important;
            max-width: 70px !important;
            text-align: right !important;
            font-weight: bold !important;
            font-size: 10px !important;
            padding: 8px 4px !important;
            white-space: nowrap !important;
            overflow: hidden !important;
            text-overflow: ellipsis !important;
            box-sizing: border-box !important;
        }
        
        /* Kolom Tanda Tangan - sama dengan Gaji Pokok */
        table th:last-child {
            text-align: center !important;
            font-family: Arial, sans-serif !important;
            min-width: 70px !important;
            width: 70px !important;
            max-width: 70px !important;
            white-space: normal !important;
            word-wrap: break-word !important;
            overflow-wrap: break-word !important;
            overflow: visible !important;
            text-overflow: clip !important;
            line-height: 1.3 !important;
            box-sizing: border-box !important;
        }
        table td:last-child {
            text-align: center !important;
            font-family: Arial, sans-serif !important;
            min-width: 70px !important;
            width: 70px !important;
            max-width: 70px !important;
            box-sizing: border-box !important;
        }
        
        /* Total columns - bold */
        .total {
            font-weight: bold;
            background-color: #f9f9f9;
        }
        
        /* Zebra striping */
        tbody tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        
        .footer {
            margin-top: 20px;
            display: table;
            width: 100%;
            font-size: 9px;
            page-break-inside: avoid !important;
            break-inside: avoid !important;
        }
        
        .footer-left,
        .footer-right {
            display: table-cell;
            width: 50%;
            text-align: center;
            vertical-align: top;
            padding: 0 20px;
        }
        
        .signature-line {
            width: 80%;
            margin: 40px auto 5px auto;
            min-height: 40px;
        }
        
        @media print {
            body {
                padding: 5mm;
                margin: 0;
            }
            
            .header {
                page-break-inside: avoid !important;
                break-inside: avoid !important;
                padding-left: 5px;
            }
            
            .period-info {
                page-break-inside: avoid !important;
                break-inside: avoid !important;
            }
            
            .footer {
                page-break-inside: avoid !important;
                break-inside: avoid !important;
            }
            
            .header-logo {
                max-width: 60px;
                max-height: 60px;
                margin-right: 0px !important;
            }
            
            .header-content h2 {
                font-size: 18px;
            }
            
            .header-content p {
                font-size: 14px;
            }
            
            .period-info {
                font-size: 13px;
            }
            
            table {
                font-size: 12px;
            }
            
            table th,
            table td {
                padding: 10px 7px;
                height: 45px;
            }
            
            /* Ensure table fits on page */
            table {
                page-break-inside: auto;
            }
            
            tr {
                page-break-inside: avoid;
                page-break-after: auto;
            }
            
            thead {
                display: table-header-group;
            }
            
            tfoot {
                display: table-footer-group;
            }
        }
    </style>
</head>
<body>
    <div class="content-wrapper">
    <div class="header">
        <?php if ($logo_exists): ?>
        <img src="<?php echo $logo_base64; ?>" alt="Logo Madrasah" class="header-logo" onerror="this.src='<?php echo $logo_path; ?>'">
        <?php endif; ?>
        <div class="header-content">
            <h2><?php echo strtoupper(htmlspecialchars($settings['nama_madrasah'] ?? 'MADRASAH IBTIDAIYAH')); ?></h2>
            <p>LEGGER GAJI</p>
        </div>
    </div>
    
    <div class="period-info">
        Periode: <?php 
        if ($jumlah_periode > 1 && !empty($periode_mulai) && !empty($periode_akhir)) {
            echo getPeriodRangeLabel($periode_mulai, $periode_akhir);
        } else {
            echo getPeriodLabel($periode);
        }
        ?>
    </div>
    
    <table>
        <thead>
            <tr>
                <th rowspan="2">No</th>
                <th rowspan="2">Nama</th>
                <th rowspan="2">Gaji Pokok</th>
                <?php if (count($tunjangan) > 0): ?>
                    <th colspan="<?php echo count($tunjangan); ?>" class="text-center">Tunjangan</th>
                <?php endif; ?>
                <th rowspan="2">Total Tunjangan</th>
                <?php if (count($potongan) > 0): ?>
                    <th colspan="<?php echo count($potongan); ?>" class="text-center">Potongan</th>
                <?php endif; ?>
                <th rowspan="2">Total Potongan</th>
                <th rowspan="2">Gaji Bersih</th>
                <th rowspan="2">Tanda Tangan</th>
            </tr>
            <tr>
                <?php foreach ($tunjangan as $t): ?>
                    <th><?php echo htmlspecialchars($t['nama_tunjangan']); ?></th>
                <?php endforeach; ?>
                <?php foreach ($potongan as $p): ?>
                    <th><?php echo htmlspecialchars($p['nama_potongan']); ?></th>
                <?php endforeach; ?>
            </tr>
        </thead>
        <tbody>
            <?php 
            $no = 1; 
            $total_gaji_pokok = 0;
            $total_tunjangan_all = 0;
            $total_potongan_all = 0;
            $total_gaji_bersih = 0;
            foreach ($legger as $l): 
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
                
                $total_gaji_pokok += $l['gaji_pokok'];
                $total_tunjangan_all += $l['total_tunjangan'];
                $total_potongan_all += $l['total_potongan'];
                $total_gaji_bersih += $l['gaji_bersih'];
            ?>
            <tr>
                <td><?php echo $no++; ?></td>
                <td><?php echo htmlspecialchars($l['nama_lengkap']); ?></td>
                <td><?php echo formatRupiahTanpaRp($l['gaji_pokok']); ?></td>
                <?php foreach ($tunjangan as $t): ?>
                    <td><?php echo formatRupiahTanpaRp($tunjangan_data[$t['id']] ?? 0); ?></td>
                <?php endforeach; ?>
                <td class="total"><?php echo formatRupiahTanpaRp($l['total_tunjangan']); ?></td>
                <?php foreach ($potongan as $p): ?>
                    <td><?php echo formatRupiahTanpaRp($potongan_data[$p['id']] ?? 0); ?></td>
                <?php endforeach; ?>
                <td class="total"><?php echo formatRupiahTanpaRp($l['total_potongan']); ?></td>
                <td class="total"><?php echo formatRupiahTanpaRp($l['gaji_bersih']); ?></td>
                <td></td>
            </tr>
            <?php endforeach; ?>
            
            <!-- Total row -->
            <tr style="background-color: #e0e0e0; font-weight: bold;">
                <td colspan="2" style="text-align: center; font-weight: bold;">TOTAL</td>
                <td><?php echo formatRupiahTanpaRp($total_gaji_pokok); ?></td>
                <?php foreach ($tunjangan as $t): ?>
                    <td>-</td>
                <?php endforeach; ?>
                <td><?php echo formatRupiahTanpaRp($total_tunjangan_all); ?></td>
                <?php foreach ($potongan as $p): ?>
                    <td>-</td>
                <?php endforeach; ?>
                <td><?php echo formatRupiahTanpaRp($total_potongan_all); ?></td>
                <td><?php echo formatRupiahTanpaRp($total_gaji_bersih); ?></td>
                <td>-</td>
            </tr>
        </tbody>
    </table>
    
    <div class="footer">
        <div class="footer-left">
            <p><strong>Mengetahui,</strong></p>
            <p><strong>Kepala Madrasah</strong></p>
            <div class="signature-line"></div>
            <p><?php echo htmlspecialchars($settings['nama_kepala'] ?? ''); ?></p>
        </div>
        <div class="footer-right">
            <p><strong>Bendahara</strong></p>
            <div class="signature-line"></div>
            <p><?php echo htmlspecialchars($settings['nama_bendahara'] ?? ''); ?></p>
        </div>
    </div>
    </div>
    
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <script>
        window.onload = function() {
            // Use content-wrapper instead of body for better control
            const element = document.querySelector('.content-wrapper') || document.body;
            const opt = {
                margin: [5, 5, 5, 5],
                filename: 'Legger_Gaji_<?php echo $periode; ?>.pdf',
                image: { type: 'jpeg', quality: 0.98 },
                html2canvas: { 
                    scale: 1, 
                    useCORS: true, 
                    letterRendering: true,
                    logging: false,
                    windowWidth: 310,
                    windowHeight: window.innerHeight,
                    allowTaint: true
                },
                jsPDF: { unit: 'mm', format: [330, 210], orientation: 'landscape' },
                pagebreak: { 
                    mode: ['avoid-all', 'css', 'legacy'],
                    avoid: ['.header', '.period-info', '.footer', 'table thead', '.content-wrapper']
                }
            };
            
            // Wait a bit for images to load
            setTimeout(function() {
                html2pdf().set(opt).from(element).save().then(function() {
                    // Close window after download
                    setTimeout(function() {
                        window.close();
                    }, 1500);
                }).catch(function(error) {
                    console.error('Error generating PDF:', error);
                    alert('Terjadi kesalahan saat membuat PDF. Silakan coba lagi.');
                });
            }, 500);
        };
    </script>
</body>
</html>

