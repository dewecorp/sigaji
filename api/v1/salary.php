<?php
/**
 * API endpoint untuk SIMAD menarik data gaji per guru.
 * Tunjangan & potongan menggunakan LATEST record (ORDER BY periode DESC),
 * mengikuti logika generate_ajax.php — jadi data tetap muncul walau periode
 * di settings sudah berubah tapi data detail belum diinput ulang.
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

// --- Determine target period ---
$requestedPeriode = isset($_GET['periode']) ? trim($_GET['periode']) : '';
if ($requestedPeriode !== '') {
    $targetPeriode = $requestedPeriode;
} elseif ($jumlahPeriode > 1 && $periodeMulai !== '' && $periodeAkhir !== '') {
    $targetPeriode = $periodeMulai;
} else {
    $targetPeriode = $periodeAktif;
}

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

// --- All teachers (with masa_bakti) ---
$sqlGuru = "SELECT g.id, g.simad_id_guru, g.nama_lengkap, g.nip, g.masa_bakti
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
    echo json_encode(['status' => 'success', 'period_info' => [], 'guru' => []], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit();
}

$guruIds = array_column($guruAll, 'id');
$guruIdPlaceholders = implode(',', array_fill(0, count($guruIds), '?'));
$guruIdTypes = str_repeat('i', count($guruIds));

// --- All active tunjangan & potongan master ---
$tunjanganMaster = $conn->query("SELECT * FROM tunjangan WHERE aktif=1")->fetch_all(MYSQLI_ASSOC) ?: [];
$potonganMaster  = $conn->query("SELECT * FROM potongan WHERE aktif=1")->fetch_all(MYSQLI_ASSOC) ?: [];

// --- Gaji Pokok: any record per guru (gaji pokok tidak tergantung periode) ---
$sqlGP = "SELECT guru_id, jumlah FROM gaji_pokok WHERE guru_id IN ($guruIdPlaceholders)";
$stmtGP = $conn->prepare($sqlGP);
$stmtGP->bind_param($guruIdTypes, ...$guruIds);
$stmtGP->execute();
$resultGP = $stmtGP->get_result();
$gajiPokokMap = [];
while ($row = $resultGP->fetch_assoc()) {
    $gajiPokokMap[(int)$row['guru_id']] = (float)$row['jumlah'];
}
$resultGP->free();
$stmtGP->close();

// --- Tunjangan: latest record per guru+tunjangan_id (spt generate_ajax.php) ---
$sqlTJ = "SELECT td.guru_id, td.tunjangan_id, td.jumlah, td.periode
          FROM tunjangan_detail td
          INNER JOIN (
              SELECT guru_id, tunjangan_id, MAX(periode) AS max_periode
              FROM tunjangan_detail
              WHERE guru_id IN ($guruIdPlaceholders)
              GROUP BY guru_id, tunjangan_id
          ) latest ON td.guru_id = latest.guru_id AND td.tunjangan_id = latest.tunjangan_id AND td.periode = latest.max_periode";
$stmtTJ = $conn->prepare($sqlTJ);
$stmtTJ->bind_param($guruIdTypes, ...$guruIds);
$stmtTJ->execute();
$resultTJ = $stmtTJ->get_result();
$tunjanganLatest = [];
while ($row = $resultTJ->fetch_assoc()) {
    $gid = (int)$row['guru_id'];
    $tid = (int)$row['tunjangan_id'];
    $tunjanganLatest["{$gid}_{$tid}"] = $row;
}
$resultTJ->free();
$stmtTJ->close();

// --- Potongan: latest record per guru+potongan_id ---
$sqlPT = "SELECT pd.guru_id, pd.potongan_id, pd.jumlah, pd.periode
          FROM potongan_detail pd
          INNER JOIN (
              SELECT guru_id, potongan_id, MAX(periode) AS max_periode
              FROM potongan_detail
              WHERE guru_id IN ($guruIdPlaceholders)
              GROUP BY guru_id, potongan_id
          ) latest ON pd.guru_id = latest.guru_id AND pd.potongan_id = latest.potongan_id AND pd.periode = latest.max_periode";
$stmtPT = $conn->prepare($sqlPT);
$stmtPT->bind_param($guruIdTypes, ...$guruIds);
$stmtPT->execute();
$resultPT = $stmtPT->get_result();
$potonganLatest = [];
while ($row = $resultPT->fetch_assoc()) {
    $gid = (int)$row['guru_id'];
    $pid = (int)$row['potongan_id'];
    $potonganLatest["{$gid}_{$pid}"] = $row;
}
$resultPT->free();
$stmtPT->close();

// --- Build per-teacher response ---
$guruList = [];
foreach ($guruAll as $g) {
    $gid = (int)$g['id'];
    $gajiPokokBulanan = $gajiPokokMap[$gid] ?? 0;
    $gajiPokokTotal   = $gajiPokokBulanan * $jumlahPeriode;

    // Tunjangan per guru
    $tunjanganGuru = [];
    foreach ($tunjanganMaster as $t) {
        $tid = (int)$t['id'];
        $key = "{$gid}_{$tid}";
        $nama_lower = strtolower(trim($t['nama_tunjangan']));
        $is_masa_bakti = (strpos($nama_lower, 'masa') !== false && strpos($nama_lower, 'bakti') !== false);

        if ($is_masa_bakti) {
            $masa_bakti_guru = isset($g['masa_bakti']) ? (int)$g['masa_bakti'] : 0;
            $jumlah_tunj_per_tahun = isset($t['jumlah_tunjangan']) ? (float)$t['jumlah_tunjangan'] : 0;
            $jumlah_bulanan = $masa_bakti_guru * $jumlah_tunj_per_tahun;
        } elseif (isset($tunjanganLatest[$key])) {
            $jumlah_bulanan = (float)$tunjanganLatest[$key]['jumlah'];
        } else {
            continue;
        }

        $tunjanganGuru[] = [
            'id'             => $tid,
            'nama_tunjangan' => $t['nama_tunjangan'],
            'jumlah_bulanan' => $jumlah_bulanan,
            'jumlah_total'   => $jumlah_bulanan * $jumlahPeriode,
            'periode'        => $tunjanganLatest[$key]['periode'] ?? ($is_masa_bakti ? $targetPeriode : ''),
        ];
    }

    // Potongan per guru
    $potonganGuru = [];
    foreach ($potonganMaster as $p) {
        $pid = (int)$p['id'];
        $key = "{$gid}_{$pid}";
        if (!isset($potonganLatest[$key])) continue;
        $jumlah_bulanan = (float)$potonganLatest[$key]['jumlah'];
        $potonganGuru[] = [
            'id'            => $pid,
            'nama_potongan' => $p['nama_potongan'],
            'jumlah_bulanan' => $jumlah_bulanan,
            'jumlah_total'  => $jumlah_bulanan * $jumlahPeriode,
            'periode'       => $potonganLatest[$key]['periode'],
        ];
    }

    $totalTunjangan = array_sum(array_column($tunjanganGuru, 'jumlah_total'));
    $totalPotongan  = array_sum(array_column($potonganGuru, 'jumlah_total'));

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
        'gaji_bersih'       => $gajiPokokTotal + $totalTunjangan - $totalPotongan,
    ];
}

echo json_encode([
    'status' => 'success',
    'period_info' => [
        'periode_aktif'  => $periodeAktif,
        'periode_mulai'  => $periodeMulai,
        'periode_akhir'  => $periodeAkhir,
        'jumlah_periode' => $jumlahPeriode,
        'jumlah_bulan'   => $jumlahPeriode,
        'tahun_ajaran'   => $tahunAjaran,
        'honor_per_jam'  => $honorPerJam,
        'target_periode' => $targetPeriode,
        'penjelasan'     => "Periode mencakup {$jumlahPeriode} bulan. "
                          . "Tunjangan/potongan = record terbaru (ORDER BY periode DESC). "
                          . "jumlah_bulanan * {$jumlahPeriode} = jumlah_total. "
                          . "gaji_bersih = total_gaji_pokok + total_tunjangan − total_potongan.",
    ],
    'guru' => $guruList,
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
