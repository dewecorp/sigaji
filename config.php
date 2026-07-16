<?php
// Production settings - disable error display in production
error_reporting(E_ALL);
ini_set('display_errors', 0);  // Set to 0 in production
ini_set('log_errors', 1);

$cookieSecure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
if (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] !== '') {
    $forwarded = explode(',', $_SERVER['HTTP_X_FORWARDED_PROTO'])[0];
    $forwarded = strtolower(trim($forwarded));
    if ($forwarded === 'https') {
        $cookieSecure = true;
    } elseif ($forwarded === 'http') {
        $cookieSecure = false;
    }
}

if (PHP_SAPI !== 'cli' && PHP_SAPI !== 'phpdbg') {
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.cookie_httponly', '1');
    
    $cookieParams = session_get_cookie_params();
    $lifetime = isset($cookieParams['lifetime']) ? $cookieParams['lifetime'] : 0;
    $path = isset($cookieParams['path']) ? $cookieParams['path'] : '/';
    $domain = isset($cookieParams['domain']) ? $cookieParams['domain'] : '';
    
    // For PHP 7.3+, we can use samesite
    if (PHP_VERSION_ID >= 70300) {
        ini_set('session.cookie_samesite', 'Lax');
    }
    
    session_set_cookie_params(
        $lifetime,
        $path,
        $domain,
        $cookieSecure,
        true
    );
}

session_start();

// Application Configuration
define('APP_NAME', 'SIGaji');
$vRaw = @exec('git log -1 --format=%ct 2>nul', $vOut, $vCode);
if ($vCode === 0 && ($vT = trim($vOut[0] ?? '')) && $vT !== '') {
    define('APP_VERSION', date('ynjGis', (int)$vT));
} elseif (file_exists($vFile = __DIR__ . '/.version') && ($vC = trim(file_get_contents($vFile))) && $vC !== '') {
    define('APP_VERSION', date('ynjGis', (int)$vC));
} else {
    define('APP_VERSION', date('ynjGis', (int)@filemtime(__FILE__)));
}
$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
if (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] !== '') {
    $scheme = explode(',', $_SERVER['HTTP_X_FORWARDED_PROTO'])[0];
    $scheme = strtolower(trim($scheme));
    if ($scheme !== 'http' && $scheme !== 'https') {
        $scheme = 'http';
    }
}
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$scriptName = $_SERVER['SCRIPT_NAME'] ?? '/';
$scriptName = str_replace('\\', '/', $scriptName);
$posPages = strpos($scriptName, '/pages/');
if ($posPages !== false) {
    $basePath = substr($scriptName, 0, $posPages) . '/';
} else {
    $basePath = rtrim(str_replace('\\', '/', dirname($scriptName)), '/') . '/';
}
$basePath = preg_replace('#/+#', '/', $basePath);
if ($basePath === '//' || $basePath === '') {
    $basePath = '/';
}

define('BASE_URL', $scheme . '://' . $host . $basePath);

require_once __DIR__ . '/simad.php';

// Timezone
date_default_timezone_set('Asia/Jakarta');

// Include database
require_once __DIR__ . '/database.php';

// Helper Functions
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: ' . BASE_URL . 'login.php');
        exit();
    }
}

function logActivity($conn, $activity, $type = 'info') {
    $user_id = $_SESSION['user_id'] ?? null;
    $username = $_SESSION['username'] ?? 'System';
    $timestamp = date('Y-m-d H:i:s');
    
    $sql = "INSERT INTO activities (user_id, username, activity, type, created_at) 
            VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("issss", $user_id, $username, $activity, $type, $timestamp);
    $stmt->execute();
    
    // Delete activities older than 24 hours
    cleanupOldActivities($conn);
}

// Function to cleanup activities older than 24 hours
function cleanupOldActivities($conn) {
    $delete_sql = "DELETE FROM activities WHERE created_at < DATE_SUB(NOW(), INTERVAL 24 HOUR)";
    $conn->query($delete_sql);
}

function formatRupiah($angka) {
    // Ensure angka is numeric
    $angka = floatval($angka);
    // Use number_format with explicit dot separator
    $formatted = number_format($angka, 0, ',', '.');
    return 'Rp ' . $formatted;
}

function formatRupiahTanpaRp($angka) {
    return number_format($angka, 0, ',', '.');
}

// Function to get time ago (relative time)
function timeAgo($datetime) {
    $timestamp = strtotime($datetime);
    $current = time();
    $diff = $current - $timestamp;
    
    if ($diff < 60) {
        return 'Baru saja';
    } elseif ($diff < 3600) {
        $minutes = floor($diff / 60);
        return $minutes . ' menit yang lalu';
    } elseif ($diff < 86400) {
        $hours = floor($diff / 3600);
        return $hours . ' jam yang lalu';
    } elseif ($diff < 604800) {
        $days = floor($diff / 86400);
        return $days . ' hari yang lalu';
    } else {
        $date = new DateTime($datetime);
        return $date->format('d/m/Y H:i');
    }
}

function getCurrentPeriod() {
    return date('Y-m');
}

function getPeriodLabel($period) {
    $months = [
        '01' => 'Januari', '02' => 'Februari', '03' => 'Maret',
        '04' => 'April', '05' => 'Mei', '06' => 'Juni',
        '07' => 'Juli', '08' => 'Agustus', '09' => 'September',
        '10' => 'Oktober', '11' => 'November', '12' => 'Desember'
    ];
    $parts = explode('-', $period);
    return $months[$parts[1]] . ' ' . $parts[0];
}

function getPeriodRangeLabel($periode_mulai, $periode_akhir) {
    $months = [
        '01' => 'Januari', '02' => 'Februari', '03' => 'Maret',
        '04' => 'April', '05' => 'Mei', '06' => 'Juni',
        '07' => 'Juli', '08' => 'Agustus', '09' => 'September',
        '10' => 'Oktober', '11' => 'November', '12' => 'Desember'
    ];
    
    if (empty($periode_mulai) || empty($periode_akhir) || $periode_mulai == $periode_akhir) {
        return getPeriodLabel($periode_mulai ?: $periode_akhir);
    }
    
    $parts_mulai = explode('-', $periode_mulai);
    $parts_akhir = explode('-', $periode_akhir);
    
    return $months[$parts_mulai[1]] . ' ' . $parts_mulai[0] . ' - ' . $months[$parts_akhir[1]] . ' ' . $parts_akhir[0];
}

function formatTanggalIndonesia($date) {
    if (empty($date) || $date == '0000-00-00') {
        return '';
    }
    
    $hari = [
        'Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'
    ];
    
    $bulan = [
        'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
    ];
    
    $timestamp = strtotime($date);
    $hari_nama = $hari[date('w', $timestamp)];
    $tanggal = date('j', $timestamp);
    $bulan_nama = $bulan[date('n', $timestamp) - 1];
    $tahun = date('Y', $timestamp);
    
    return $hari_nama . ', ' . $tanggal . ' ' . $bulan_nama . ' ' . $tahun;
}

function formatTanggalTanpaHari($date) {
    if ($date === null || $date === '' || $date === '0000-00-00') {
        return '';
    }
    
    $s = trim((string)$date);
    if ($s === '') {
        return '';
    }
    if (!preg_match('/^(\d{4}-\d{2}-\d{2})(?:[\s T].*)?$/', $s, $m)) {
        return '';
    }
    
    $dt = DateTime::createFromFormat('Y-m-d', $m[1], new DateTimeZone('Asia/Jakarta'));
    if ($dt === false || $dt->format('Y-m-d') !== $m[1]) {
        return '';
    }

    $bulan = [
        'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
    ];
    
    return (string)(int)$dt->format('j') . ' ' . $bulan[(int)$dt->format('n') - 1] . ' ' . $dt->format('Y');
}
?>
