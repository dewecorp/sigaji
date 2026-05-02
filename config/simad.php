<?php
/**
 * Endpoint SIMAD Anda: .../api/v1/teachers.php (sesuai skrip yang diberikan).
 * Folder aplikasi SIMAD dicari OTOMATIS di laragon/www (sekatan sistem_gaji) — tidak ada input URL dari layar aplikasi.
 * Hanya isi SIMAD_TEACHERS_API_KEY jika di server SIMAD bukan nilai default.
 */

if (!defined('SIMAD_TEACHERS_API_PATH')) {
    define('SIMAD_TEACHERS_API_PATH', 'api/v1/teachers.php');
}
if (!defined('SIMAD_TEACHERS_API_KEY')) {
    define('SIMAD_TEACHERS_API_KEY', 'SIS_CENTRAL_HUB_SECRET_2026');
}

/** Fallback bila penyisiran disk tidak menemukan berkas teachers.php */
if (!defined('SIMAD_PUBLIC_FOLDER_FALLBACK')) {
    define('SIMAD_PUBLIC_FOLDER_FALLBACK', 'simad');
}

/** Hanya bila SIMAD benar‑benar di luar mesin/host ini */
if (!defined('SIMAD_TEACHERS_URL_MANUAL')) {
    define('SIMAD_TEACHERS_URL_MANUAL', '');
}

/** Path marker relatif dari root web SIMAD (normalize FS) */
function simad_marker_path_fs(): string
{
    return str_replace('/', DIRECTORY_SEPARATOR, trim(SIMAD_TEACHERS_API_PATH, '/'));
}

/**
 * Cari direktori aplikasi SIMAD: berisi berkas SIMAD_TEACHERS_API_PATH.
 * Mengembalikan path relatif dari folder www (gunakan slash), tanpa slash di tepi — kosong jika gagal.
 */
function simad_discover_relative_path_from_www(): string
{
    static $cache;
    if ($cache !== null) {
        return $cache;
    }

    $cfgDir = realpath(__DIR__);
    $projectSigaji = $cfgDir ? realpath(dirname($cfgDir)) : false;
    if (!$projectSigaji) {
        $cache = '';
        return '';
    }

    $www = dirname($projectSigaji);
    $wwwReal = realpath($www);
    if (!$wwwReal || !is_dir($wwwReal)) {
        $cache = '';
        return '';
    }

    $marker = simad_marker_path_fs();

    /** @var string[] */
    $allRelRoots = [];

    foreach (scandir($wwwReal) as $entry) {
        if ($entry === '.' || $entry === '..') {
            continue;
        }
        $candidate = $wwwReal . DIRECTORY_SEPARATOR . $entry;
        if (!is_dir($candidate)) {
            continue;
        }
        if (strcasecmp(realpath($candidate) ?: '', $projectSigaji) === 0) {
            continue;
        }
        if (!is_file($candidate . DIRECTORY_SEPARATOR . $marker)) {
            continue;
        }
        $allRelRoots[] = $entry;
    }

    /**
     * Satu tingkat lagi: www/induk/SIMAD/... (folder SIMAD tidak persis sejajar sistem_gaji).
     */
    if (empty($allRelRoots)) {
        foreach (scandir($wwwReal) as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }
            $lvl1 = $wwwReal . DIRECTORY_SEPARATOR . $entry;
            if (!is_dir($lvl1)) {
                continue;
            }
            if (strcasecmp(realpath($lvl1) ?: '', $projectSigaji) === 0) {
                continue;
            }
            foreach (scandir($lvl1) as $sub) {
                if ($sub === '.' || $sub === '..') {
                    continue;
                }
                $lvl2 = $lvl1 . DIRECTORY_SEPARATOR . $sub;
                if (!is_dir($lvl2)) {
                    continue;
                }
                if (strpos(($lvl2 . DIRECTORY_SEPARATOR), $projectSigaji . DIRECTORY_SEPARATOR) === 0) {
                    continue;
                }
                if (!is_file($lvl2 . DIRECTORY_SEPARATOR . $marker)) {
                    continue;
                }
                $rel = substr($lvl2, strlen($wwwReal));
                $rel = trim(str_replace('\\', '/', $rel), '/');
                if ($rel !== '') {
                    $allRelRoots[] = $rel;
                }
            }
        }
        $allRelRoots = array_values(array_unique($allRelRoots));
    }

    if (empty($allRelRoots)) {
        $cache = '';
        return '';
    }

    $pick = '';
    foreach ($allRelRoots as $rel) {
        $top = strtolower(explode('/', $rel)[0]);
        if ($top === 'simad') {
            $pick = $rel;
            break;
        }
    }
    if ($pick === '') {
        sort($allRelRoots, SORT_NATURAL | SORT_FLAG_CASE);
        $pick = $allRelRoots[0];
    }

    $cache = $pick;

    return $pick;
}

/**
 * Bentuk URL penuju teachers.php: disk dulu → host sama dengan SIGaji atau subdomain mirror.
 */
function simad_get_teachers_api_url(): string
{
    $manual = trim((string)SIMAD_TEACHERS_URL_MANUAL);
    if ($manual !== '') {
        return $manual;
    }

    if (!defined('BASE_URL')) {
        return '';
    }

    $p = parse_url(BASE_URL);
    if (!$p || empty($p['host'])) {
        return '';
    }

    $scheme = isset($p['scheme']) ? $p['scheme'] : 'http';
    $host = $p['host'];
    $port = isset($p['port']) ? ':' . (int)$p['port'] : '';
    $rawPath = $p['path'] ?? '';
    $pathNorm = '/' . trim($rawPath, '/');

    $apiPathJs = trim(SIMAD_TEACHERS_API_PATH, '/');

    $relFromWww = simad_discover_relative_path_from_www();
    if ($relFromWww === '') {
        $relFromWww = trim(str_replace('\\', '/', SIMAD_PUBLIC_FOLDER_FALLBACK), '/');
    }

    $segments = explode('/', $relFromWww);
    $topFolder = $segments[0] ?? $relFromWww;
    $singleSegment = count($segments) === 1;

    $suffixInDomain = strstr($host, '.');

    $isRootVirtualHostPath = ($pathNorm === '/' || $pathNorm === '');
    /**
     * Vhost satu label (mis. sistem_gaji.test) → pakai subdomain folder teratas SIMAD mirror (simad.test)
     */
    if ($singleSegment && $isRootVirtualHostPath && $suffixInDomain !== false && strlen($suffixInDomain) >= 2) {
        $simadHost = $topFolder . $suffixInDomain;

        return $scheme . '://' . $simadHost . $port . '/' . $apiPathJs;
    }

    /**
     * localhost/…/ subdirectory atau path dalam satu host.
     */
    $prefix = '/' . trim(str_replace('\\', '/', $relFromWww), '/');

    return $scheme . '://' . $host . $port . $prefix . '/' . $apiPathJs;
}

function simad_get_teachers_api_key(): string
{
    return (string)SIMAD_TEACHERS_API_KEY;
}
