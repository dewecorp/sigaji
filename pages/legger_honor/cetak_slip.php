<?php
require_once __DIR__ . '/../../config/config.php';
requireLogin();

$id = $_GET['id'] ?? 0;
$sql = "SELECT lh.*, p.nama_pembina, e.jenis_ekstrakurikuler, h.jabatan, h.jumlah_honor as honor_per_pertemuan
        FROM legger_honor lh
        JOIN pembina p ON lh.pembina_id = p.id
        JOIN ekstrakurikuler e ON lh.ekstrakurikuler_id = e.id
        JOIN honor h ON lh.honor_id = h.id
        WHERE lh.id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$legger = $stmt->get_result()->fetch_assoc();

if (!$legger) {
    die('Data tidak ditemukan');
}

$sql = "SELECT * FROM settings LIMIT 1";
$settings = $conn->query($sql)->fetch_assoc();
$bulan_aktif = date('Y-m'); // Bulan aktif (current month)

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
?>
<!DOCTYPE html>
<html>
<head>
    <title>Slip Honor - <?php echo htmlspecialchars($legger['nama_pembina']); ?></title>
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
            font-size: 12px;
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
            min-height: 0;
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
            font-size: 16px;
            margin: 0 0 1mm 0;
            font-weight: bold;
            line-height: 1.2;
        }
        
        .header-content p {
            font-size: 12px;
            margin: 0;
            line-height: 1.2;
        }
        
        .info-table {
            margin: 1mm 0 0 0;
            border: none;
            width: 100%;
            table-layout: fixed;
            font-size: 12px;
            flex-shrink: 0;
            border-collapse: collapse;
            border-spacing: 0;
        }
        .info-table tr {
            border: none;
            margin: 0;
            padding: 0;
            line-height: 1;
        }
        .info-table td {
            border: none;
            padding: 0.1mm 0;
            vertical-align: top;
            font-size: 12px;
            line-height: 0.8;
        }
        .info-table td:first-child {
            width: 120px;
            white-space: nowrap;
            padding-right: 2mm;
        }
        .info-table td:nth-child(2) {
            width: 5px;
            text-align: left;
            padding-right: 1mm;
        }
        .info-table td:last-child {
            width: auto;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 2mm 0;
            font-size: 12px;
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
            font-size: 12px;
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
            border-top: none;
        }
        
        .tempat-tanggal {
            margin-top: 1mm;
            text-align: right;
            font-size: 12px;
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
            
            .header-logo {
                max-width: 40px;
                max-height: 40px;
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
                <p>Slip Honor Ekstrakurikuler</p>
            </div>
        </div>
        <table class="info-table">
            <tr>
                <td><strong>Bulan Penerimaan</strong></td>
                <td><strong>:</strong></td>
                <td><?php echo getPeriodLabel($bulan_aktif); ?></td>
            </tr>
            <tr>
                <td><strong>Nama</strong></td>
                <td><strong>:</strong></td>
                <td><?php echo htmlspecialchars($legger['nama_pembina']); ?></td>
            </tr>
            <tr>
                <td><strong>Jabatan</strong></td>
                <td><strong>:</strong></td>
                <td><?php echo htmlspecialchars($legger['jabatan']); ?></td>
            </tr>
        </table>
        <table>
            <tr>
                <th>Keterangan</th>
                <th class="text-right">Jumlah</th>
            </tr>
            <tr>
                <td>Honor per Pertemuan</td>
                <td class="text-right"><?php echo formatRupiah($legger['jumlah_honor_per_pertemuan']); ?></td>
            </tr>
            <tr>
                <td>Jumlah Pertemuan</td>
                <td class="text-right"><?php echo $legger['jumlah_pertemuan']; ?> x</td>
            </tr>
            <tr>
                <td class="total">Total Honor</td>
                <td class="text-right total"><?php echo formatRupiah($legger['total_honor']); ?></td>
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
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <script>
        window.onload = function() {
            const element = document.body;
            const opt = {
                margin: [2, 2, 2, 2],
                filename: 'Slip_Honor_<?php echo htmlspecialchars($legger['nama_pembina'], ENT_QUOTES); ?>_<?php echo $legger['periode']; ?>.pdf',
                image: { type: 'jpeg', quality: 0.98 },
                html2canvas: { scale: 2, useCORS: true, letterRendering: true },
                jsPDF: { unit: 'mm', format: [210, 330], orientation: 'portrait' },
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

