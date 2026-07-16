<?php
/**
 * API endpoint untuk SIMAD menarik data gaji (gaji pokok, tunjangan, potongan).
 * Autentikasi: X-API-KEY header atau api_key query parameter.
 *
 * Response: JSON
 *   {"status":"success","period_info":{...},"data":{...}}
 *   {"status":"error","message":"..."}
 *
 * Query params opsional:
 *   periode   - filter by periode (YYYY-MM). Jika tidak dikirim, pakai periode_aktif dari settings.
 *   guru_id   - filter by local guru.id
 *   simad_id  - filter by simad_id_guru
 */

require_once __DIR__ . '/../../config/config.php';

header('Content-Type: application/json; charset=utf-8');

// --- Auth ---
$apiKey = $_SERVER['HTTP_X_API_KEY'] ?? $_GET['api_key'] ?? '';
$expectedKey = function_exists('simad_get_teachers_api_key') ? simad_get_teachers_api_key() : '';

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
$periods = [];

if ($requestedPeriode !== '') {
    $periods[] = $requestedPeriode;
} elseif ($jumlahPeriode > 1 && $periodeMulai !== '' && $periodeAkhir !== '') {
    $start = new DateTime($periodeMulai . '-01');
    $end   = new DateTime($periodeAkhir . '-01');
    $end->modify('+1 month');
    $interval = new DateInterval('P1M');
    while ($start < $end) {
        $periods[] = $start->format('Y-m');
        $start->modify('+1 month');
    }
} else {
    $periods[] = $periodeAktif;
}

// Build period list for SQL IN clause
$placeholders = implode(',', array_fill(0, count($periods), '?'));
$periodTypes = str_repeat('s', count($periods));

// --- Filters ---
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

// --- 1. Gaji Pokok ---
$gpParams = array_merge($periods, $guruParams);
$gpTypes = $periodTypes . $guruTypes;

$sqlGP = "SELECT gp.id, gp.guru_id, g.simad_id_guru, g.nama_lengkap, g.nip,
                 gp.jumlah, gp.periode
          FROM gaji_pokok gp
          JOIN guru g ON gp.guru_id = g.id
          WHERE gp.periode IN ($placeholders) $guruWhere
          ORDER BY g.nama_lengkap ASC, gp.periode ASC";
$stmtGP = $conn->prepare($sqlGP);
if (!empty($gpParams)) {
    $stmtGP->bind_param($gpTypes, ...$gpParams);
}
$stmtGP->execute();
$resultGP = $stmtGP->get_result();
$gajiPokokRaw = $resultGP ? $resultGP->fetch_all(MYSQLI_ASSOC) : [];
$stmtGP->close();

$gajiPokok = [];
foreach ($gajiPokokRaw as $row) {
    $gajiPokok[] = [
        'id'            => (int)$row['id'],
        'guru_id'       => (int)$row['guru_id'],
        'simad_id_guru' => $row['simad_id_guru'] ? (int)$row['simad_id_guru'] : null,
        'nama_lengkap'  => $row['nama_lengkap'],
        'nip'           => $row['nip'],
        'jumlah'        => (float)$row['jumlah'],
        'periode'       => $row['periode'],
    ];
}

// --- 2. Tunjangan + Detail ---
$tjParams = array_merge($periods, $guruParams);
$tjTypes = $periodTypes . $guruTypes;

$sqlTJ = "SELECT td.id, td.guru_id, g.simad_id_guru, g.nama_lengkap,
                 td.tunjangan_id, t.nama_tunjangan, t.jumlah_tunjangan, t.aktif,
                 td.jumlah, td.periode
          FROM tunjangan_detail td
          JOIN guru g ON td.guru_id = g.id
          JOIN tunjangan t ON td.tunjangan_id = t.id
          WHERE td.periode IN ($placeholders) $guruWhere
          ORDER BY t.nama_tunjangan ASC, g.nama_lengkap ASC, td.periode ASC";
$stmtTJ = $conn->prepare($sqlTJ);
if (!empty($tjParams)) {
    $stmtTJ->bind_param($tjTypes, ...$tjParams);
}
$stmtTJ->execute();
$resultTJ = $stmtTJ->get_result();
$tunjanganRaw = $resultTJ ? $resultTJ->fetch_all(MYSQLI_ASSOC) : [];
$stmtTJ->close();

$tunjangan = [];
foreach ($tunjanganRaw as $row) {
    $tid = $row['tunjangan_id'];
    if (!isset($tunjangan[$tid])) {
        $tunjangan[$tid] = [
            'id'               => (int)$tid,
            'nama_tunjangan'   => $row['nama_tunjangan'],
            'jumlah_tunjangan' => (float)$row['jumlah_tunjangan'],
            'aktif'            => (int)$row['aktif'],
            'detail'           => [],
        ];
    }
    $tunjangan[$tid]['detail'][] = [
        'id'             => (int)$row['id'],
        'guru_id'        => (int)$row['guru_id'],
        'simad_id_guru'  => $row['simad_id_guru'] ? (int)$row['simad_id_guru'] : null,
        'nama_lengkap'   => $row['nama_lengkap'],
        'jumlah'         => (float)$row['jumlah'],
        'periode'        => $row['periode'],
    ];
}
$tunjangan = array_values($tunjangan);

// --- 3. Potongan + Detail ---
$ptParams = array_merge($periods, $guruParams);
$ptTypes = $periodTypes . $guruTypes;

$sqlPT = "SELECT pd.id, pd.guru_id, g.simad_id_guru, g.nama_lengkap,
                 pd.potongan_id, p.nama_potongan, p.jumlah_potongan, p.aktif,
                 pd.jumlah, pd.periode
          FROM potongan_detail pd
          JOIN guru g ON pd.guru_id = g.id
          JOIN potongan p ON pd.potongan_id = p.id
          WHERE pd.periode IN ($placeholders) $guruWhere
          ORDER BY p.nama_potongan ASC, g.nama_lengkap ASC, pd.periode ASC";
$stmtPT = $conn->prepare($sqlPT);
if (!empty($ptParams)) {
    $stmtPT->bind_param($ptTypes, ...$ptParams);
}
$stmtPT->execute();
$resultPT = $stmtPT->get_result();
$potonganRaw = $resultPT ? $resultPT->fetch_all(MYSQLI_ASSOC) : [];
$stmtPT->close();

$potongan = [];
foreach ($potonganRaw as $row) {
    $pid = $row['potongan_id'];
    if (!isset($potongan[$pid])) {
        $potongan[$pid] = [
            'id'              => (int)$pid,
            'nama_potongan'   => $row['nama_potongan'],
            'jumlah_potongan' => (float)$row['jumlah_potongan'],
            'aktif'           => (int)$row['aktif'],
            'detail'          => [],
        ];
    }
    $potongan[$pid]['detail'][] = [
        'id'             => (int)$row['id'],
        'guru_id'        => (int)$row['guru_id'],
        'simad_id_guru'  => $row['simad_id_guru'] ? (int)$row['simad_id_guru'] : null,
        'nama_lengkap'   => $row['nama_lengkap'],
        'jumlah'         => (float)$row['jumlah'],
        'periode'        => $row['periode'],
    ];
}
$potongan = array_values($potongan);

// --- Response ---
echo json_encode([
    'status' => 'success',
    'period_info' => [
        'periode_aktif'  => $periodeAktif,
        'periode_mulai'  => $periodeMulai,
        'periode_akhir'  => $periodeAkhir,
        'jumlah_periode' => $jumlahPeriode,
        'tahun_ajaran'   => $tahunAjaran,
        'honor_per_jam'  => $honorPerJam,
        'periods_used'   => $periods,
    ],
    'data' => [
        'gaji_pokok' => $gajiPokok,
        'tunjangan'  => $tunjangan,
        'potongan'   => $potongan,
    ],
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
