<?php
/**
 * API endpoint untuk SIMAD menarik data gaji.
 * Mengembalikan data dari legger_gaji (hasil perhitungan final) + komponen gaji.
 *
 * Autentikasi: X-API-KEY header atau api_key query parameter.
 *
 * Response: JSON
 *   {"status":"success","period_info":{...},"data":{...,"legger":[...],"komponen":{...}}}
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

// --- Determine period(s) to query ---
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

// --- 1. Legger Gaji (calculated salary - final data matching SIGaji display) ---
$lgParams = array_merge($periods, $guruParams);
$lgTypes = $periodTypes . $guruTypes;

$sqlLG = "SELECT lg.id, lg.guru_id, g.simad_id_guru, g.nama_lengkap, g.nip,
                 lg.periode,
                 lg.gaji_pokok, lg.total_tunjangan, lg.total_potongan, lg.gaji_bersih,
                 lg.tanda_tangan
          FROM legger_gaji lg
          JOIN guru g ON lg.guru_id = g.id
          WHERE lg.periode IN ($placeholders) $guruWhere
          ORDER BY g.nama_lengkap ASC, lg.periode ASC";
$stmtLG = $conn->prepare($sqlLG);
$stmtLG->bind_param($lgTypes, ...$lgParams);
$stmtLG->execute();
$resultLG = $stmtLG->get_result();
$leggerRaw = $resultLG ? $resultLG->fetch_all(MYSQLI_ASSOC) : [];
$stmtLG->close();

$legger = [];
foreach ($leggerRaw as $row) {
    $legger[] = [
        'id'               => (int)$row['id'],
        'guru_id'          => (int)$row['guru_id'],
        'simad_id_guru'    => $row['simad_id_guru'] ? (int)$row['simad_id_guru'] : null,
        'nama_lengkap'     => $row['nama_lengkap'],
        'nip'              => $row['nip'],
        'periode'          => $row['periode'],
        'gaji_pokok'       => (float)$row['gaji_pokok'],
        'total_tunjangan'  => (float)$row['total_tunjangan'],
        'total_potongan'   => (float)$row['total_potongan'],
        'gaji_bersih'      => (float)$row['gaji_bersih'],
        'tanda_tangan'     => (int)$row['tanda_tangan'],
    ];
}

// --- 2. Komponen Gaji (raw data) ---
// Gaji Pokok (period-independent, monthly base)
$sqlGP = "SELECT gp.id, gp.guru_id, g.simad_id_guru, g.nama_lengkap, gp.jumlah AS jumlah_bulanan
          FROM gaji_pokok gp
          JOIN guru g ON gp.guru_id = g.id
          WHERE 1=1 $guruWhere
          ORDER BY g.nama_lengkap ASC";
$stmtGP = $conn->prepare($sqlGP);
if (!empty($guruParams)) {
    $stmtGP->bind_param($guruTypes, ...$guruParams);
}
$stmtGP->execute();
$resultGP = $stmtGP->get_result();
$gajiPokokRaw = $resultGP ? $resultGP->fetch_all(MYSQLI_ASSOC) : [];
$stmtGP->close();

$gajiPokok = [];
foreach ($gajiPokokRaw as $row) {
    $gajiPokok[] = [
        'guru_id'       => (int)$row['guru_id'],
        'simad_id_guru' => $row['simad_id_guru'] ? (int)$row['simad_id_guru'] : null,
        'nama_lengkap'  => $row['nama_lengkap'],
        'jumlah_bulanan' => (float)$row['jumlah_bulanan'],
    ];
}

// Tunjangan per guru per periode (from detail)
$tjParams = array_merge($periods, $guruParams);
$tjTypes = $periodTypes . $guruTypes;

$sqlTJ = "SELECT td.id, td.guru_id, g.simad_id_guru, td.tunjangan_id,
                 t.nama_tunjangan, td.jumlah, td.periode
          FROM tunjangan_detail td
          JOIN guru g ON td.guru_id = g.id
          JOIN tunjangan t ON td.tunjangan_id = t.id
          WHERE td.periode IN ($placeholders) $guruWhere
          ORDER BY g.nama_lengkap ASC, t.nama_tunjangan ASC";
$stmtTJ = $conn->prepare($sqlTJ);
$stmtTJ->bind_param($tjTypes, ...$tjParams);
$stmtTJ->execute();
$resultTJ = $stmtTJ->get_result();
$tunjanganRaw = $resultTJ ? $resultTJ->fetch_all(MYSQLI_ASSOC) : [];
$stmtTJ->close();

$tunjangan = [];
foreach ($tunjanganRaw as $row) {
    $tunjangan[] = [
        'guru_id'       => (int)$row['guru_id'],
        'simad_id_guru' => $row['simad_id_guru'] ? (int)$row['simad_id_guru'] : null,
        'nama_tunjangan' => $row['nama_tunjangan'],
        'jumlah'        => (float)$row['jumlah'],
        'periode'       => $row['periode'],
    ];
}

// Potongan per guru per periode
$ptParams = array_merge($periods, $guruParams);
$ptTypes = $periodTypes . $guruTypes;

$sqlPT = "SELECT pd.id, pd.guru_id, g.simad_id_guru, pd.potongan_id,
                 p.nama_potongan, pd.jumlah, pd.periode
          FROM potongan_detail pd
          JOIN guru g ON pd.guru_id = g.id
          JOIN potongan p ON pd.potongan_id = p.id
          WHERE pd.periode IN ($placeholders) $guruWhere
          ORDER BY g.nama_lengkap ASC, p.nama_potongan ASC";
$stmtPT = $conn->prepare($sqlPT);
$stmtPT->bind_param($ptTypes, ...$ptParams);
$stmtPT->execute();
$resultPT = $stmtPT->get_result();
$potonganRaw = $resultPT ? $resultPT->fetch_all(MYSQLI_ASSOC) : [];
$stmtPT->close();

$potongan = [];
foreach ($potonganRaw as $row) {
    $potongan[] = [
        'guru_id'       => (int)$row['guru_id'],
        'simad_id_guru' => $row['simad_id_guru'] ? (int)$row['simad_id_guru'] : null,
        'nama_potongan' => $row['nama_potongan'],
        'jumlah'        => (float)$row['jumlah'],
        'periode'       => $row['periode'],
    ];
}

// --- Response ---
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
        'penjelasan'      => "Periode mencakup {$jumlahPeriode} bulan (dari {$periodeMulai} sampai {$periodeAkhir}). "
                           . "Gaji bulanan dikalikan {$jumlahPeriode} untuk mendapatkan total periode. "
                           . "Data 'legger' sudah dalam nilai total periode (sesuai tampilan SIGaji). "
                           . "Data 'komponen.gaji_pokok.jumlah_bulanan' adalah nilai per bulan — kalikan dengan {$jumlahPeriode} untuk total.",
    ],
    'data' => [
        'legger'   => $legger,
        'komponen' => [
            'gaji_pokok' => $gajiPokok,
            'tunjangan'  => $tunjangan,
            'potongan'   => $potongan,
        ],
    ],
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
