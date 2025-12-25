<?php
require_once __DIR__ . '/../../config/config.php';
requireLogin();

// Get settings first to get periode_aktif
$sql = "SELECT * FROM settings LIMIT 1";
$settings = $conn->query($sql)->fetch_assoc();
$bulan_aktif = date('Y-m'); // Bulan aktif (current month)
$periode = $_GET['periode'] ?? $bulan_aktif;
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

// Get legger_honor data
$sql = "SELECT lh.*, p.nama_pembina, e.jenis_ekstrakurikuler, h.jabatan, h.jumlah_honor as honor_per_pertemuan
        FROM legger_honor lh
        JOIN pembina p ON lh.pembina_id = p.id
        JOIN ekstrakurikuler e ON lh.ekstrakurikuler_id = e.id
        JOIN honor h ON lh.honor_id = h.id
        WHERE lh.periode = ?
        ORDER BY p.nama_pembina ASC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $periode);
$stmt->execute();
$legger = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Legger Honor - <?php echo getPeriodLabel($periode); ?></title>
    <style>
        @page {
            size: A4 landscape;
            margin: 10mm;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: Arial, sans-serif;
            font-size: 14px;
            padding: 10mm;
            margin: 0;
        }
        
        .header {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
            border-bottom: 2px solid #000;
            padding-bottom: 10px;
        }
        
        .header-logo {
            max-width: 60px;
            max-height: 60px;
            margin-right: 3px;
            object-fit: contain;
            flex-shrink: 0;
        }
        
        .header-content {
            flex: 1;
            text-align: center;
        }
        
        .header-content h2 {
            font-size: 20px;
            margin: 5px 0;
            font-weight: bold;
        }
        
        .header-content p {
            font-size: 16px;
            margin: 3px 0;
        }
        
        .period-info {
            text-align: center;
            margin-bottom: 15px;
            font-size: 15px;
            font-weight: bold;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 0;
            font-size: 16px;
        }
        
        table th,
        table td {
            border: 1px solid #000;
            padding: 12px 10px;
            text-align: left;
            vertical-align: middle;
            height: 50px;
            font-size: 16px;
        }
        
        table th {
            background-color: #f0f0f0;
            font-weight: bold;
            text-align: center;
            font-size: 16px;
        }
        
        /* Kolom No - width tetap kecil */
        table th:first-child,
        table td:first-child {
            width: 50px;
            text-align: center;
            padding: 12px 10px;
        }
        
        /* Kolom Nama Pembina - width lebih besar */
        table th:nth-child(2),
        table td:nth-child(2) {
            width: 220px;
            min-width: 220px;
            padding: 12px 10px;
        }
        
        /* Kolom Jabatan */
        table th:nth-child(3),
        table td:nth-child(3) {
            width: 180px;
            min-width: 180px;
            padding: 12px 10px;
        }
        
        /* Kolom dengan angka - align right */
        table td:nth-child(n+4):not(:last-child) {
            text-align: right;
            font-family: 'Courier New', monospace;
        }
        
        /* Kolom Honor per Pertemuan */
        table th:nth-child(4),
        table td:nth-child(4) {
            width: 150px;
            text-align: right;
            padding: 12px 10px;
        }
        
        /* Kolom Jumlah Pertemuan */
        table th:nth-child(5),
        table td:nth-child(5) {
            width: 100px;
            text-align: center;
            padding: 12px 10px;
        }
        
        /* Kolom Total Honor */
        table th:nth-child(6),
        table td:nth-child(6) {
            width: 160px;
            text-align: right;
            padding: 12px 10px;
        }
        
        /* Kolom Tanda Tangan - align center */
        table th:last-child,
        table td:last-child {
            text-align: center;
            font-family: Arial, sans-serif;
            min-width: 150px;
            width: 150px;
            padding: 12px 10px;
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
            margin-top: 30px;
            display: table;
            width: 100%;
            font-size: 12px;
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
                page-break-inside: avoid;
            }
            
            .header-logo {
                max-width: 60px;
                max-height: 60px;
            }
            
            .header-content h2 {
                font-size: 20px;
            }
            
            .header-content p {
                font-size: 16px;
            }
            
            .period-info {
                font-size: 15px;
            }
            
            table {
                font-size: 16px;
            }
            
            table th,
            table td {
                padding: 12px 10px;
                height: 50px;
                font-size: 16px;
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
    <div class="header">
        <?php if ($logo_exists): ?>
        <img src="<?php echo $logo_base64; ?>" alt="Logo Madrasah" class="header-logo" onerror="this.src='<?php echo $logo_path; ?>'">
        <?php endif; ?>
        <div class="header-content">
            <h2><?php echo strtoupper(htmlspecialchars($settings['nama_madrasah'] ?? 'MADRASAH IBTIDAIYAH')); ?></h2>
            <p>Legger Honor Ekstrakurikuler</p>
        </div>
    </div>
    
    <div class="period-info">
        Bulan Penerimaan: <?php echo getPeriodLabel($bulan_aktif); ?>
    </div>
    
    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Nama Pembina</th>
                <th>Jabatan</th>
                <th>Honor per Pertemuan</th>
                <th>Jumlah Pertemuan</th>
                <th>Total Honor</th>
                <th>Tanda Tangan</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $no = 1; 
            $total_honor = 0;
            foreach ($legger as $l): 
                $total_honor += $l['total_honor'];
            ?>
            <tr>
                <td><?php echo $no++; ?></td>
                <td><?php echo htmlspecialchars($l['nama_pembina']); ?></td>
                <td><?php echo htmlspecialchars($l['jabatan']); ?></td>
                <td><?php echo formatRupiahTanpaRp($l['jumlah_honor_per_pertemuan']); ?></td>
                <td><?php echo $l['jumlah_pertemuan']; ?></td>
                <td class="total"><?php echo formatRupiahTanpaRp($l['total_honor']); ?></td>
                <td></td>
            </tr>
            <?php endforeach; ?>
            
            <!-- Total row -->
            <tr style="background-color: #e0e0e0; font-weight: bold;">
                <td colspan="5" style="text-align: center; font-weight: bold;">TOTAL</td>
                <td><?php echo formatRupiahTanpaRp($total_honor); ?></td>
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
    
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <script>
        window.onload = function() {
            const element = document.body;
            const opt = {
                margin: [5, 5, 5, 5],
                filename: 'Legger_Honor_<?php echo $periode; ?>.pdf',
                image: { type: 'jpeg', quality: 0.98 },
                html2canvas: { scale: 2, useCORS: true, letterRendering: true },
                jsPDF: { unit: 'mm', format: 'a4', orientation: 'landscape' },
                pagebreak: { mode: ['avoid-all', 'css', 'legacy'] }
            };
            
            html2pdf().set(opt).from(element).save().then(function() {
                // Close window after download
                setTimeout(function() {
                    window.close();
                }, 1000);
            }).catch(function(error) {
                console.error('Error generating PDF:', error);
                alert('Terjadi kesalahan saat membuat PDF. Silakan coba lagi.');
            });
        };
    </script>
</body>
</html>

