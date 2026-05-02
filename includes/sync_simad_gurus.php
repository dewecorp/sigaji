<?php

/**
 * Sinkron nama guru dari SIMAD — nama SIMAD selalu menjadi sumber utama (ditimpa ke baris utama).
 *
 * Untuk satu id_guru SIMAD: gabungkan baris yang bertaut sid + guru belum bertaut tetapi namanya mirip nama SIMAD,
 * ambil BARIS TERLENGKAP sebagai pemenang, pasang nama + sid ke situ. Baris stup yang salah pegang sid dilepas/dihapus.
 * Sisanya sangat mirip nama SIMAD tanpa pegang sid lain → dihapus sebagai duplikat. Baru sekali‑kali INSERT.
 */

/** Minimal kemiripan untuk guru yang belum punya simad_id (0–100) */
const SYNC_SIMAD_GURU_MATCH_MIN_PCT = 76.0;

function sync_simad_gurus_ensure_columns(mysqli $conn) {
    $db = mysqli_real_escape_string($conn, DB_NAME);

    $qCol = "SELECT COUNT(*) AS c FROM INFORMATION_SCHEMA.COLUMNS
             WHERE TABLE_SCHEMA = '$db' AND TABLE_NAME = 'guru' AND COLUMN_NAME = 'simad_id_guru'";
    $rCol = $conn->query($qCol);
    $rowCol = $rCol ? $rCol->fetch_assoc() : null;
    if (!$rowCol || (int)$rowCol['c'] === 0) {
        $conn->query("ALTER TABLE guru ADD COLUMN simad_id_guru INT NULL DEFAULT NULL AFTER id");
    }

    $qIdx = "SELECT COUNT(*) AS c FROM INFORMATION_SCHEMA.STATISTICS
             WHERE TABLE_SCHEMA = '$db' AND TABLE_NAME = 'guru' AND INDEX_NAME = 'uk_guru_simad_id'";
    $rIdx = $conn->query($qIdx);
    $rowIdx = $rIdx ? $rIdx->fetch_assoc() : null;
    if (!$rowIdx || (int)$rowIdx['c'] === 0) {
        $conn->query("ALTER TABLE guru ADD UNIQUE KEY uk_guru_simad_id (simad_id_guru)");
    }
}

function sync_simad_gurus_normalize_name($nama) {
    return trim((string)$nama);
}

/**
 * String untuk dicocokkan (bukan nama tampilan): huruf kecil, buang banyak variasi gelar/singkatan.
 */
function sync_simad_gurus_normalize_for_match($nama) {
    $enc = 'UTF-8';
    $s = mb_strtolower(trim((string)$nama), $enc);

    $s = preg_replace('/^dr\.?\s+/iu', 'dr ', $s);
    $s = preg_replace('/^ah\.?\s+/iu', 'ahmad ', $s);
    $s = preg_replace('/^abd\.?\s+/iu', 'abdul ', $s);
    $s = preg_replace('/^mrs\.?\s+/iu', ' ', $s);
    $s = preg_replace('/^mr\.?\s+/iu', ' ', $s);

    for ($g = 0; $g < 6; $g++) {
        $before = $s;
        $s = preg_replace('/\s*,\s*s\s*\.\s*p\s*\.\s*d\s*\.\s*i\s*\.\s*/iu', '', $s);
        $s = preg_replace('/\s*,\s*s\s*\.\s*p\s*\.\s*d\s*\./iu', '', $s);
        $s = preg_replace('/\s*,\s*a\s*\.\s*m\s*a\s*\./iu', '', $s);
        $s = preg_replace('/\s*,\s*([a-z]\s*\.)+\s*[a-z]*/iu', '', $s);
        if ($s === $before) {
            break;
        }
    }

    $s = preg_replace('/[.,\'`«»]/u', ' ', $s);
    $s = preg_replace('/\s+/u', ' ', $s);

    return trim($s);
}

function sync_simad_gurus_similarity_pct($namaA, $namaB) {
    $ka = sync_simad_gurus_normalize_for_match($namaA);
    $kb = sync_simad_gurus_normalize_for_match($namaB);
    if ($ka === '' || $kb === '') {
        return 0.0;
    }
    if ($ka === $kb) {
        return 100.0;
    }

    similar_text($ka, $kb, $pct);

    $lenA = mb_strlen($ka, 'UTF-8');
    $lenB = mb_strlen($kb, 'UTF-8');
    $minLen = max(8, min($lenA, $lenB));

    /** Nama panjang salah satu merupakan substring inti nama lain → naikkan skor sedikit */
    if ($lenA >= $minLen && $lenB >= $minLen) {
        if (mb_strpos($ka, $kb, 0, 'UTF-8') !== false || mb_strpos($kb, $ka, 0, 'UTF-8') !== false) {
            $pct = max($pct, SYNC_SIMAD_GURU_MATCH_MIN_PCT + 1.0);
        }
        $tokensA = array_filter(explode(' ', $ka));
        $tokensB = array_filter(explode(' ', $kb));
        if (count($tokensA) >= 2 && count($tokensB) >= 2) {
            $lastA = array_slice($tokensA, -2);
            $lastB = array_slice($tokensB, -2);
            if ($lastA === $lastB) {
                $pct = max($pct, SYNC_SIMAD_GURU_MATCH_MIN_PCT + 2.0);
            }
        }
    }

    return (float)$pct;
}

function sync_simad_gurus_row_completeness(array $row) {
    $s = 0;
    $tmt = $row['tmt'] ?? null;
    if ($tmt !== null && $tmt !== '') {
        $s += 500;
    }

    $s += max(0, min(200, (int)($row['jumlah_jam_mengajar'] ?? 0)));
    $jab = trim((string)($row['jabatan'] ?? ''));
    if ($jab !== '' && strtolower($jab) !== 'null') {
        $s += min(mb_strlen($jab, 'UTF-8'), 300);
        $s += 40;
    }

    $namaLen = mb_strlen(trim((string)($row['nama_lengkap'] ?? '')), 'UTF-8');
    $s += min($namaLen, 80);

    return $s;
}

function sync_simad_gurus_fetch_all_local(mysqli $conn) {
    $out = [];
    $q = $conn->query('SELECT id, nama_lengkap, simad_id_guru, tmt, jumlah_jam_mengajar, jabatan FROM guru');
    if (!$q) {
        return $out;
    }
    while ($r = $q->fetch_assoc()) {
        $out[] = $r;
    }
    return $out;
}

function sync_simad_gurus_delete_local_by_id(mysqli $conn, $id) {
    $id = (int)$id;
    if ($id <= 0) {
        return;
    }
    $st = $conn->prepare('DELETE FROM guru WHERE id = ? LIMIT 1');
    if (!$st) {
        return;
    }
    $st->bind_param('i', $id);
    $st->execute();
}

/**
 * Tentukan satu baris pemenang untuk id_guru SIMAD ini:
 * gabungkan baris yang sudah bertaut sid + guru belum bertaut tetapi namanya sangat mirip nama SIMAD.
 * Pemenang = data paling lengkap; sid dipindah dari “stup” kosong kalau perlu → baris utama diperbaiki namanya dari SIMAD.
 */
function sync_simad_gurus_sync_one(
    mysqli $conn,
    mysqli_stmt $stmtBind,
    mysqli_stmt $stmtClearSid,
    mysqli_stmt $stmtInsert,
    array &$locals,
    int $sid,
    string $namaApi,
    &$inserted,
    &$updated
) {
    $official = null;
    foreach ($locals as $r) {
        if ((int)($r['simad_id_guru'] ?? 0) === $sid) {
            $official = $r;
            break;
        }
    }

    /** @var array<int,array{row:array,pct:float}> */
    $pool = [];

    foreach ($locals as $r) {
        $rSid = (int)($r['simad_id_guru'] ?? 0);
        $pct = sync_simad_gurus_similarity_pct($namaApi, $r['nama_lengkap'] ?? '');

        if ($rSid === $sid) {
            $pool[] = ['row' => $r, 'pct' => $pct];
            continue;
        }
        if ($rSid !== 0) {
            continue;
        }
        if ($pct >= SYNC_SIMAD_GURU_MATCH_MIN_PCT) {
            $pool[] = ['row' => $r, 'pct' => $pct];
        }
    }

    if (empty($pool)) {
        $stmtInsert->bind_param('si', $namaApi, $sid);
        if ($stmtInsert->execute()) {
            $inserted++;
            $locals[] = [
                'id' => (int)$conn->insert_id,
                'nama_lengkap' => $namaApi,
                'simad_id_guru' => $sid,
                'tmt' => null,
                'jumlah_jam_mengajar' => 0,
                'jabatan' => '',
            ];
        }
        return;
    }

    usort($pool, static function ($a, $b) {
        $scoreA = sync_simad_gurus_row_completeness($a['row']);
        $scoreB = sync_simad_gurus_row_completeness($b['row']);
        $d = $scoreB <=> $scoreA;
        if ($d !== 0) {
            return $d;
        }
        $d2 = ($b['pct'] ?? 0) <=> ($a['pct'] ?? 0);
        if ($d2 !== 0) {
            return $d2;
        }
        return ((int)$a['row']['id']) <=> ((int)$b['row']['id']);
    });

    $keeper = $pool[0]['row'];
    $keeperId = (int)$keeper['id'];

    /** id baris lamanya pegang SID; jangan hapus kalau dipindah SID saja dan baris lain tetap disimpan */
    $protectedDuplicateIds = [];

    if ($official !== null && (int)$official['id'] !== $keeperId) {
        $oldId = (int)$official['id'];
        $stmtClearSid->bind_param('i', $oldId);
        $stmtClearSid->execute();

        $oldScore = sync_simad_gurus_row_completeness($official);
        $keepScore = sync_simad_gurus_row_completeness($keeper);
        $discardStub = ($oldScore + 240 < $keepScore) || ($oldScore < 200 && $keepScore >= 320);
        if ($discardStub) {
            sync_simad_gurus_delete_local_by_id($conn, $oldId);
            $locals = array_values(array_filter($locals, static function ($x) use ($oldId) {
                return (int)$x['id'] !== $oldId;
            }));
        } else {
            $protectedDuplicateIds[$oldId] = true;
            foreach ($locals as &$lr) {
                if ((int)$lr['id'] === $oldId) {
                    $lr['simad_id_guru'] = null;
                    break;
                }
            }
            unset($lr);
        }
    }

    $stmtBind->bind_param('sii', $namaApi, $sid, $keeperId);
    if ($stmtBind->execute()) {
        $updated++;
        foreach ($locals as &$lr) {
            if ((int)$lr['id'] === $keeperId) {
                $lr['nama_lengkap'] = $namaApi;
                $lr['simad_id_guru'] = $sid;
                break;
            }
        }
        unset($lr);
    }

    /*
     * Sisanya dalam pool adalah duplikat orang yang sama → hapus (data terbaik ada di keeper).
     * Tidak memeriksa simad_id di snapshot pool (bisa sudah dibersihkan di DB untuk baris stup).
     */
    foreach (array_slice($pool, 1) as $p) {
        $did = (int)$p['row']['id'];
        if ($did === $keeperId) {
            continue;
        }
        if (isset($protectedDuplicateIds[$did])) {
            continue;
        }
        if (($p['pct'] ?? 0) + 0.01 < SYNC_SIMAD_GURU_MATCH_MIN_PCT && (int)($p['row']['simad_id_guru'] ?? 0) !== $sid) {
            continue;
        }

        sync_simad_gurus_delete_local_by_id($conn, $did);
        $locals = array_values(array_filter($locals, static function ($x) use ($did) {
            return (int)$x['id'] !== $did;
        }));
    }
}

/**
 * @return array{success:bool,message:string,inserted:int,updated:int,fetched:int}
 */
function sync_guru_nama_dari_simad(mysqli $conn) {
    $urlBase = function_exists('simad_get_teachers_api_url') ? trim(simad_get_teachers_api_url()) : '';
    $apiKey = function_exists('simad_get_teachers_api_key') ? simad_get_teachers_api_key() : '';

    if ($urlBase === '') {
        return [
            'success' => false,
            'message' => 'Tidak dapat membentuk URL API SIMAD. Pastikan folder SIMAD ada di Laragon www dan berisi ' . (defined('SIMAD_TEACHERS_API_PATH') ? SIMAD_TEACHERS_API_PATH : 'api/v1/teachers.php') . ', atau isi SIMAD_TEACHERS_URL_MANUAL di config/simad.php.',
            'inserted' => 0,
            'updated' => 0,
            'fetched' => 0,
        ];
    }

    $urlBase = trim($urlBase);
    $sep = (strpos($urlBase, '?') !== false) ? '&' : '?';
    $url = $urlBase . $sep . 'api_key=' . rawurlencode($apiKey);

    $opts = [
        'http' => [
            'method' => 'GET',
            'header' => "X-API-KEY: " . $apiKey . "\r\nAccept: application/json\r\n",
            'timeout' => 120,
        ],
        'ssl' => ['verify_peer' => true, 'verify_peer_name' => true],
    ];
    $ctx = stream_context_create($opts);
    $raw = @file_get_contents($url, false, $ctx);

    if ($raw === false) {
        return [
            'success' => false,
            'message' => 'Gagal menghubungi API SIMAD (cek URL atau jaringan).',
            'inserted' => 0,
            'updated' => 0,
            'fetched' => 0,
        ];
    }

    $decoded = json_decode($raw, true);
    if (!is_array($decoded)) {
        return [
            'success' => false,
            'message' => 'Jawaban API SIMAD bukan JSON valid.',
            'inserted' => 0,
            'updated' => 0,
            'fetched' => 0,
        ];
    }

    if (($decoded['status'] ?? '') !== 'success') {
        $msg = (string)($decoded['message'] ?? 'API SIMAD mengembalikan error.');
        return [
            'success' => false,
            'message' => $msg,
            'inserted' => 0,
            'updated' => 0,
            'fetched' => 0,
        ];
    }

    $list = $decoded['data'] ?? [];
    if (!is_array($list)) {
        return [
            'success' => false,
            'message' => 'Format data API tidak dikenali.',
            'inserted' => 0,
            'updated' => 0,
            'fetched' => 0,
        ];
    }

    $conn->begin_transaction();

    try {
        $locals = sync_simad_gurus_fetch_all_local($conn);

        $stmtBind = $conn->prepare('UPDATE guru SET nama_lengkap = ?, simad_id_guru = ? WHERE id = ?');
        $stmtClearSid = $conn->prepare('UPDATE guru SET simad_id_guru = NULL WHERE id = ?');
        $stmtInsert = $conn->prepare(
            'INSERT INTO guru (nama_lengkap, jenis_kelamin, status_pegawai, simad_id_guru, jumlah_jam_mengajar, jabatan)
             VALUES (?, \'L\', \'Honor\', ?, 0, \'\')'
        );

        if (!$stmtBind || !$stmtClearSid || !$stmtInsert) {
            $conn->rollback();
            return [
                'success' => false,
                'message' => 'Gagal menyiapkan query database.',
                'inserted' => 0,
                'updated' => 0,
                'fetched' => count($list),
            ];
        }

        $inserted = 0;
        $updated = 0;

        foreach ($list as $row) {
            if (!is_array($row)) {
                continue;
            }
            $sid = isset($row['id_guru']) ? (int)$row['id_guru'] : 0;
            $nama = sync_simad_gurus_normalize_name($row['nama_guru'] ?? '');
            if ($sid <= 0 || $nama === '') {
                continue;
            }

            sync_simad_gurus_sync_one($conn, $stmtBind, $stmtClearSid, $stmtInsert, $locals, $sid, $nama, $inserted, $updated);
        }

        $conn->commit();

        return [
            'success' => true,
            'message' => sprintf('Sinkron SIMAD selesai: %d diperbarui, %d ditambahkan.', $updated, $inserted),
            'inserted' => $inserted,
            'updated' => $updated,
            'fetched' => count($list),
        ];
    } catch (Throwable $e) {
        $conn->rollback();
        return [
            'success' => false,
            'message' => 'Sinkron gagal: ' . $e->getMessage(),
            'inserted' => 0,
            'updated' => 0,
            'fetched' => count($list),
        ];
    }
}
