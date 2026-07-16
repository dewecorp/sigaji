<?php
/**
 * API endpoint untuk SIMAD menarik data gaji (gaji pokok, tunjangan, potongan).
 * Autentikasi: X-API-KEY header atau api_key query parameter.
 *
 * Response: JSON
 *   {"status":"success","data":{...}}
 *   {"status":"error","message":"..."}
 *
 * Query params opsional:
 *   periode   - filter by periode (YYYY-MM)
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

// --- Filters ---
$periode  = isset($_GET['periode'])  ? trim($_GET['periode'])  : '';
$guruId   = isset($_GET['guru_id'])  ? (int)$_GET['guru_id']   : 0;
$simadId  = isset($_GET['simad_id']) ? (int)$_GET['simad_id']  : 0;

// Build WHERE clause for guru filtering
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
$gpWhere = $guruWhere;
$gpParams = $guruParams;
$gpTypes = $guruTypes;
if ($periode !== '') {
    $gpWhere .= ' AND gp.periode = ?';
    $gpParams[] = $periode;
    $gpTypes .= 's';
}

$sqlGP = "SELECT gp.id, gp.guru_id, g.simad_id_guru, g.nama_lengkap, g.nip,
                 gp.jumlah, gp.periode
          FROM gaji_pokok gp
          JOIN guru g ON gp.guru_id = g.id
          WHERE 1=1 $gpWhere
          ORDER BY g.nama_lengkap ASC";
$stmtGP = $conn->prepare($sqlGP);
if (!empty($gpParams)) {
    $stmtGP->bind_param($gpTypes, ...$gpParams);
}
$stmtGP->execute();
$resultGP = $stmtGP->get_result();
$gajiPokok = $resultGP ? $resultGP->fetch_all(MYSQLI_ASSOC) : [];
$stmtGP->close();

// --- 2. Tunjangan + Detail ---
$tjWhere = $guruWhere;
$tjParams = $guruParams;
$tjTypes = $guruTypes;
if ($periode !== '') {
    $tjWhere .= ' AND td.periode = ?';
    $tjParams[] = $periode;
    $tjTypes .= 's';
}

$sqlTJ = "SELECT td.id, td.guru_id, g.simad_id_guru, g.nama_lengkap,
                 td.tunjangan_id, t.nama_tunjangan, t.jumlah_tunjangan, t.aktif,
                 td.jumlah, td.periode
          FROM tunjangan_detail td
          JOIN guru g ON td.guru_id = g.id
          JOIN tunjangan t ON td.tunjangan_id = t.id
          WHERE 1=1 $tjWhere
          ORDER BY t.nama_tunjangan ASC, g.nama_lengkap ASC";
$stmtTJ = $conn->prepare($sqlTJ);
if (!empty($tjParams)) {
    $stmtTJ->bind_param($tjTypes, ...$tjParams);
}
$stmtTJ->execute();
$resultTJ = $stmtTJ->get_result();
$tunjanganRaw = $resultTJ ? $resultTJ->fetch_all(MYSQLI_ASSOC) : [];
$stmtTJ->close();

// Group tunjangan by parent
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
$ptWhere = $guruWhere;
$ptParams = $guruParams;
$ptTypes = $guruTypes;
if ($periode !== '') {
    $ptWhere .= ' AND pd.periode = ?';
    $ptParams[] = $periode;
    $ptTypes .= 's';
}

$sqlPT = "SELECT pd.id, pd.guru_id, g.simad_id_guru, g.nama_lengkap,
                 pd.potongan_id, p.nama_potongan, p.jumlah_potongan, p.aktif,
                 pd.jumlah, pd.periode
          FROM potongan_detail pd
          JOIN guru g ON pd.guru_id = g.id
          JOIN potongan p ON pd.potongan_id = p.id
          WHERE 1=1 $ptWhere
          ORDER BY p.nama_potongan ASC, g.nama_lengkap ASC";
$stmtPT = $conn->prepare($sqlPT);
if (!empty($ptParams)) {
    $stmtPT->bind_param($ptTypes, ...$ptParams);
}
$stmtPT->execute();
$resultPT = $stmtPT->get_result();
$potonganRaw = $resultPT ? $resultPT->fetch_all(MYSQLI_ASSOC) : [];
$stmtPT->close();

// Group potongan by parent
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
    'data' => [
        'gaji_pokok' => $gajiPokok,
        'tunjangan'  => $tunjangan,
        'potongan'   => $potongan,
    ],
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
