<?php
require_once __DIR__ . '/../../config/config.php';
requireLogin();

$sql = "SELECT * FROM settings LIMIT 1";
$settings = $conn->query($sql)->fetch_assoc();
$bulan_aktif = date('Y-m');

$logo_file = __DIR__ . '/../../assets/img/' . ($settings['logo'] ?? '');
$logo_exists = !empty($settings['logo']) && file_exists($logo_file);
$logo_base64 = '';
$logo_path = '';
if ($logo_exists) {
    $logo_path = BASE_URL . 'assets/img/' . $settings['logo'];
    $image_data = file_get_contents($logo_file);
    $logo_base64 = 'data:' . mime_content_type($logo_file) . ';base64,' . base64_encode($image_data);
}

$conn->query("CREATE TABLE IF NOT EXISTS insentif (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama_insentif VARCHAR(100) NOT NULL,
    jumlah_insentif DECIMAL(15,2) NOT NULL DEFAULT 0,
    aktif TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$conn->query("CREATE TABLE IF NOT EXISTS insentif_detail (
    id INT AUTO_INCREMENT PRIMARY KEY,
    guru_id INT NOT NULL,
    insentif_id INT NOT NULL,
    jumlah DECIMAL(15,2) NOT NULL DEFAULT 0,
    periode VARCHAR(7) NOT NULL DEFAULT '',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_guru_id (guru_id),
    INDEX idx_insentif_id (insentif_id),
    CONSTRAINT fk_insentif_detail_guru FOREIGN KEY (guru_id) REFERENCES guru(id) ON DELETE CASCADE,
    CONSTRAINT fk_insentif_detail_insentif FOREIGN KEY (insentif_id) REFERENCES insentif(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$sql = "SELECT i.*
        FROM insentif i
        WHERE i.aktif = 1
        ORDER BY i.nama_insentif ASC";
$insentif_list = $conn->query($sql)->fetch_all(MYSQLI_ASSOC);

$sql = "SELECT DISTINCT g.id AS guru_id, g.nama_lengkap
        FROM insentif_detail idt
        JOIN guru g ON idt.guru_id = g.id
        JOIN insentif i ON idt.insentif_id = i.id
        WHERE i.aktif = 1
        ORDER BY LOWER(TRIM(g.nama_lengkap)) ASC, g.nama_lengkap ASC";
$guru_list = $conn->query($sql)->fetch_all(MYSQLI_ASSOC);

$sql = "SELECT guru_id, insentif_id, SUM(jumlah) AS jumlah
        FROM insentif_detail idt
        JOIN insentif i ON idt.insentif_id = i.id
        WHERE i.aktif = 1
        GROUP BY guru_id, insentif_id";
$detail_rows = $conn->query($sql)->fetch_all(MYSQLI_ASSOC);

$detail_map = [];
foreach ($detail_rows as $row) {
    $gid = intval($row['guru_id']);
    $iid = intval($row['insentif_id']);
    if (!isset($detail_map[$gid])) {
        $detail_map[$gid] = [];
    }
    $detail_map[$gid][$iid] = floatval($row['jumlah'] ?? 0);
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Legger Insentif</title>
    <style>
        @page {
            size: 330mm 210mm;
            margin: 0mm 10mm 10mm 5mm;
        }

        * {
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            font-size: 11px;
            margin: 0;
            padding: 0;
        }

        .content-wrapper {
            width: 100%;
            max-width: 100%;
            padding: 0mm 5mm 5mm 3mm;
            background: white;
            display: flex;
            flex-direction: column;
        }

        .header {
            text-align: center;
            margin-bottom: 10px;
            padding-bottom: 10px;
            border-bottom: 2px solid #000;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .header-logo {
            max-width: 60px;
            max-height: 60px;
            object-fit: contain;
        }

        .header-content h2 {
            margin: 0;
            font-size: 18px;
        }

        .header-content p {
            margin: 2px 0;
            font-size: 12px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            table-layout: fixed;
        }

        table, th, td {
            border: 1px solid #000;
        }

        th, td {
            padding: 3px;
        }

        th {
            background: #f0f0f0;
            text-align: center;
            font-size: 11pt;
            white-space: normal;
            word-break: break-word;
        }

        td {
            font-size: 11pt;
        }

        td.num {
            text-align: right;
            white-space: nowrap;
        }

        td.name {
            white-space: normal;
            word-break: break-word;
        }

        td.signature {
            min-width: 80px;
        }

        .total {
            font-weight: bold;
            background: #fafafa;
        }

        .tempat-tanggal {
            text-align: right;
            margin-top: 15px;
            margin-bottom: 5px;
            font-size: 12pt;
        }

        .footer {
            margin-top: 20px;
            display: table;
            width: 100%;
            font-size: 12pt;
        }

        .footer-left,
        .footer-right {
            display: table-cell;
            width: 50%;
            text-align: center;
            vertical-align: top;
            padding: 0 20px;
        }

        .footer-left p,
        .footer-right p {
            margin: 2px 0;
            line-height: 1.2;
            font-size: 12pt;
        }

        .signature-line {
            width: 80%;
            margin: 40px auto 5px auto;
            min-height: 40px;
            text-align: center;
        }

        .qr-signature {
            width: 18mm;
            height: 18mm;
        }

        @media print {
            @page {
                size: 330mm 210mm;
                margin: 0mm 10mm 10mm 5mm;
            }

            body {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            .content-wrapper {
                padding: 2mm 0mm;
                width: 100%;
                max-width: 100%;
            }

            thead { display: table-header-group; }
            tfoot { display: table-footer-group; }
            tr { page-break-inside: avoid; }
        }
    </style>
</head>
<body>
    <div class="content-wrapper">
    <div class="header">
        <?php if ($logo_exists): ?>
            <img src="<?php echo $logo_base64; ?>" alt="Logo" class="header-logo" onerror="this.src='<?php echo $logo_path; ?>'">
        <?php endif; ?>
        <div class="header-content">
            <h2><?php echo strtoupper(htmlspecialchars($settings['nama_madrasah'] ?? 'MADRASAH')); ?></h2>
            <p>LEGGER INSENTIF</p>
            <p>Bulan <?php echo htmlspecialchars(getPeriodLabel($bulan_aktif)); ?></p>
            <?php if (!empty($settings['tahun_ajaran'])): ?>
                <p>Tahun Ajaran <?php echo htmlspecialchars($settings['tahun_ajaran']); ?></p>
            <?php endif; ?>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 30px;">No</th>
                <th style="width: 220px;">Nama Guru</th>
                <?php foreach ($insentif_list as $i): ?>
                    <th style="width: 95px;"><?php echo htmlspecialchars($i['nama_insentif']); ?></th>
                <?php endforeach; ?>
                <th style="width: 120px;">Total</th>
                <th style="width: 100px;">Tanda Tangan</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $no = 1;
            $grand_total = 0;
            foreach ($guru_list as $g):
                $gid = intval($g['guru_id']);
                $row_total = 0;
            ?>
            <tr>
                <td style="text-align: center;"><?php echo $no++; ?></td>
                <td class="name"><?php echo htmlspecialchars($g['nama_lengkap']); ?></td>
                <?php foreach ($insentif_list as $i): ?>
                    <?php
                    $iid = intval($i['id']);
                    $val = floatval($detail_map[$gid][$iid] ?? 0);
                    $row_total += $val;
                    ?>
                    <td class="num"><?php echo $val > 0 ? formatRupiahTanpaRp($val) : '-'; ?></td>
                <?php endforeach; ?>
                <td class="num total"><?php echo formatRupiahTanpaRp($row_total); ?></td>
                <td class="signature"></td>
            </tr>
            <?php
                $grand_total += $row_total;
            endforeach;
            ?>
            <tr class="total">
                <td colspan="<?php echo 2 + count($insentif_list); ?>" style="text-align:center;">TOTAL</td>
                <td class="num"><?php echo formatRupiahTanpaRp($grand_total); ?></td>
                <td></td>
            </tr>
        </tbody>
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

    <?php
    $qr_kepala_data = 'Kepala Madrasah|' . ($settings['nama_kepala'] ?? '') . '|Legger Insentif';
    $qr_bendahara_data = 'Bendahara|' . ($settings['nama_bendahara'] ?? '') . '|Legger Insentif';
    ?>
    <div class="footer">
        <div class="footer-left">
            <p>Mengetahui,</p>
            <p>Kepala Madrasah</p>
            <div class="signature-line">
                <img
                    class="qr-signature"
                    src="<?php echo BASE_URL; ?>qrcode.php?data=<?php echo rawurlencode($qr_kepala_data); ?>"
                    alt="QR Kepala Madrasah"
                    onerror="if(!this.dataset.fallback){this.dataset.fallback='1';this.src='https://api.qrserver.com/v1/create-qr-code/?size=180x180&data='+encodeURIComponent('<?php echo htmlspecialchars($qr_kepala_data, ENT_QUOTES); ?>');}"
                >
            </div>
            <p><?php echo htmlspecialchars($settings['nama_kepala'] ?? ''); ?></p>
        </div>
        <div class="footer-right">
            <p>&nbsp;</p>
            <p>Bendahara</p>
            <div class="signature-line">
                <img
                    class="qr-signature"
                    src="<?php echo BASE_URL; ?>qrcode.php?data=<?php echo rawurlencode($qr_bendahara_data); ?>"
                    alt="QR Bendahara"
                    onerror="if(!this.dataset.fallback){this.dataset.fallback='1';this.src='https://api.qrserver.com/v1/create-qr-code/?size=180x180&data='+encodeURIComponent('<?php echo htmlspecialchars($qr_bendahara_data, ENT_QUOTES); ?>');}"
                >
            </div>
            <p><?php echo htmlspecialchars($settings['nama_bendahara'] ?? ''); ?></p>
        </div>
    </div>

    <script>
        window.onload = function() {
            setTimeout(function() {
                window.print();
            }, 200);
        };
    </script>
    </div>
</body>
</html>
