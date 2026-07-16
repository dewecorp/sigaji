<?php
/**
 * API endpoint untuk SIMAD menarik data gaji per guru (rincian gaji pokok, tunjangan, potongan).
 *
 * Autentikasi: X-API-KEY header atau api_key query parameter.
 *
 * Query params opsional:
 *   periode   - YYYY-MM. Jika tidak dikirim, pakai periode_aktif dari settings.
 *   guru_id   - filter by local guru.id
 *   simad_id  - filter by simad_id_guru
 */

require_once __DIR__ . '/../../config/config.php';

header('Content-Type: application/json; charset=utf-8');

// --- Auth ---
$apiKey = $_SERVER['HTTP_X_API_KEY'] ?? $_GET['api_key'] ?? '';
$expectedKey = simad_get_teachers_api_key();

if ($apiKey === '' || !hash_equals($expectedKey, $apiKey)) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit();
}

// --- Read settings ---
$sqlSet = "SELECT periode_aktif, periode_mulai, periode_akhir, jumlah_periode, tahun_ajaran, honor_per_jam FROM settings LIMIT 1";
$resSet = $conn->query($sqlSet);
$settings = $resSet ? $resSet->fetch_assoc() : [];

$periodeAktif  = $settings['periode_aktif'] ?? date('Y-m');
$periodeMulai  = $settings['periode_mulai'] ?? '';
$periodeAkhir  = $settings['periode_akhir'] ?? '';
$jumlahPeriode = isset($settings['jumlah_periode']) ? (int)$settings['jumlah_periode'] : 1;
$tahunAjaran   = $settings['tahun_ajaran'] ?? '';
$honorPerJam   = isset($settings['honor_per_jam']) ? (float)$settings['honor_per_jam'] : 0;

// --- Determine period(s) ---
$requestedPeriode = isset($_GET['periode']) ? trim($_GET['periode']) : '';
if ($requestedPeriode !== '') {
    $periods = [$requestedPeriode];
} elseif ($jumlahPeriode > 1 && $periodeMulai !== '' && $periodeAkhir !== '') {
    $periods = [];
    $start = new DateTime($periodeMulai . '-01');
    $end   = new DateTime($periodeAkhir . '-01');
    $end->modify('+1 month');
    while ($start < $end) {
        $periods[] = $start->format('Y-m');
        $start->modify('+1 month');
    }
} else {
    $periods = [$periodeAktif];
}

$placeholders = implode(',', array_fill(0, count($periods), '?'));
$periodTypes = str_repeat('s', count($periods));

// --- Guru filter ---
$guruId  = isset($_GET['guru_id'])  ? (int)$_GET['guru_id']  : 0;
$simadId = isset($_GET['simad_id']) ? (int)$_GET['simad_id'] : 0;

$guruWhere = '';
$guruParams = [];
$guruTypes = '';
if ($guruId > 0) {
    $guruWhere = 'AND g.id = ?';
    $guruParams[] = $guruId;
    $guruTypes .= 'i';
}
if ($simadId > 0) {
    $guruWhere = 'AND g.simad_id_guru = ?';
    $guruParams[] = $simadId;
    $guruTypes .= 'i';
}

// --- All teachers ---
$sqlGuru = "SELECT g.id, g.simad_id_guru, g.nama_lengkap, g.nip
            FROM guru g WHERE 1=1 $guruWhere ORDER BY g.nama_lengkap ASC";
$stmtGuru = $conn->prepare($sqlGuru);
if (!empty($guruParams)) {
    $stmtGuru->bind_param($guruTypes, ...$guruParams);
}
$stmtGuru->execute();
$resultGuru = $stmtGuru->get_result();
$guruAll = $resultGuru ? $resultGuru->fetch_all(MYSQLI_ASSOC) : [];
$stmtGuru->close();

if (empty($guruAll)) {
    echo json_encode(['status' => 'success', 'period_info' => [...], 'guru' => []], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit();
}

// Collect guru IDs
$guruIds = array_column($guruAll, 'id');
$guruIdPlaceholders = implode(',', array_fill(0, count($guruIds), '?'));
$guruIdTypes = str_repeat('i', count($guruIds));

// --- Gaji Pokok (per guru, period-independent) ---
$sqlGP = "SELECT guru_id, jumlah FROM gaji_pokok WHERE guru_id IN ($guruIdPlaceholders)";
$stmtGP = $conn->prepare($sqlGP);
$stmtGP->bind_param($guruIdTypes, ...$guruIds);
$stmtGP->execute();
$resultGP = $stmtGP->get_result();
$gajiPokokMap = [];
while ($row = $resultGP->fetch_assoc()) {
    $gajiPokokMap[(int)$row['guru_id']] = (float)$row['jumlah'];
}
$stmtGP->close();

// --- Tunjangan per guru (grouped by period) ---
$sqlTJ = "SELECT td.guru_id, td.tunjangan_id, t.nama_tunjangan, td.jumlah, td.periode
          FROM tunjangan_detail td
          JOIN tunjangan t ON td.tunjangan_id = t.id
          WHERE td.guru_id IN ($guruIdPlaceholders) AND td.periode IN ($placeholders)
          ORDER BY td.guru_id ASC, t.nama_tunjangan ASC";
$tjAllParams = array_merge($guruIds, $periods);
$tjAllTypes = $guruIdTypes . $periodTypes;
$stmtTJ = $conn->prepare($sqlTJ);
$stmtTJ->bind_param($tjAllTypes, ...$tjAllParams);
$stmtTJ->execute();
$resultTJ = $stmtTJ->get_result();
$tunjanganMap = [];
while ($row = $resultTJ->fetch_assoc()) {
    $gid = (int)$row['guru_id'];
    $tunjanganMap[$gid][] = [
        'id'             => (int)$row['tunjangan_id'],
        'nama_tunjangan' => $row['nama_tunjangan'],
        'jumlah_bulanan' => (float)$row['jumlah'],
        'jumlah_total'   => (float)$row['jumlah'] * $jumlahPeriode,
        'periode'        => $row['periode'],
    ];
}
$stmtTJ->close();

// --- Potongan per guru ---
$sqlPT = "SELECT pd.guru_id, pd.potongan_id, p.nama_potongan, pd.jumlah, pd.periode
          FROM potongan_detail pd
          JOIN potongan p ON pd.potongan_id = p.id
          WHERE pd.guru_id IN ($guruIdPlaceholders) AND pd.periode IN ($placeholders)
          ORDER BY pd.guru_id ASC, p.nama_potongan ASC";
$ptAllParams = array_merge($guruIds, $periods);
$ptAllTypes = $guruIdTypes . $periodTypes;
$stmtPT = $conn->prepare($sqlPT);
$stmtPT->bind_param($ptAllTypes, ...$ptAllParams);
$stmtPT->execute();
$resultPT = $stmtPT->get_result();
$potonganMap = [];
while ($row = $resultPT->fetch_assoc()) {
    $gid = (int)$row['guru_id'];
    $potonganMap[$gid][] = [
        'id'            => (int)$row['potongan_id'],
        'nama_potongan' => $row['nama_potongan'],
        'jumlah_bulanan' => (float)$row['jumlah'],
        'jumlah_total'  => (float)$row['jumlah'] * $jumlahPeriode,
        'periode'       => $row['periode'],
    ];
}
$stmtPT->close();

// --- Build per-teacher response ---
$guruList = [];
foreach ($guruAll as $g) {
    $gid = (int)$g['id'];
    $gajiPokokBulanan = $gajiPokokMap[$gid] ?? 0;
    $gajiPokokTotal   = $gajiPokokBulanan * $jumlahPeriode;

    $tunjanganGuru = $tunjanganMap[$gid] ?? [];
    $potonganGuru  = $potonganMap[$gid] ?? [];

    $totalTunjangan = array_sum(array_column($tunjanganGuru, 'jumlah_total'));
    $totalPotongan  = array_sum(array_column($potonganGuru, 'jumlah_total'));
    $gajiBersih     = $gajiPokokTotal + $totalTunjangan - $totalPotongan;

    $guruList[] = [
        'guru_id'       => $gid,
        'simad_id_guru' => $g['simad_id_guru'] ? (int)$g['simad_id_guru'] : null,
        'nama_lengkap'  => $g['nama_lengkap'],
        'nip'           => $g['nip'],
        'gaji_pokok' => [
            'jumlah_bulanan' => $gajiPokokBulanan,
            'jumlah_total'   => $gajiPokokTotal,
        ],
        'tunjangan'  => $tunjanganGuru,
        'potongan'   => $potonganGuru,
        'total_gaji_pokok'  => $gajiPokokTotal,
        'total_tunjangan'   => $totalTunjangan,
        'total_potongan'    => $totalPotongan,
        'gaji_bersih'       => $gajiBersih,
    ];
}

echo json_encode([
    'status' => 'success',
    'period_info' => [
        'periode_aktif'   => $periodeAktif,
        'periode_mulai'   => $periodeMulai,
        'periode_akhir'   => $periodeAkhir,
        'jumlah_periode'  => $jumlahPeriode,
        'jumlah_bulan'    => $jumlahPeriode,
        'tahun_ajaran'    => $tahunAjaran,
        'honor_per_jam'   => $honorPerJam,
        'periods_used'    => $periods,
        'penjelasan'      => "Periode mencakup {$jumlahPeriode} bulan. "
                           . "jumlah_bulanan = nilai per bulan, jumlah_total = jumlah_bulanan × {$jumlahPeriode}. "
                           . "gaji_bersih = total_gaji_pokok + total_tunjangan − total_potongan.",
    ],
    'guru' => $guruList,
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
