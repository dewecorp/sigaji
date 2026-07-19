<?php
// Security & Helper Functions
// Included from config/config.php
// All functions guarded with !function_exists() for compatibility across environments

if (!function_exists('isLoggedIn')) {
    function isLoggedIn() {
        return isset($_SESSION['user_id']);
    }
}

if (!function_exists('requireLogin')) {
    function requireLogin() {
        if (!isLoggedIn()) {
            header('Location: ' . BASE_URL . 'login');
            exit();
        }
    }
}

if (!function_exists('isAdmin')) {
    function isAdmin() {
        return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
    }
}

if (!function_exists('requireAdmin')) {
    function requireAdmin() {
        requireLogin();
        if (!isAdmin()) {
            http_response_code(403);
            exit('Akses ditolak. Fitur ini hanya untuk administrator.');
        }
    }
}

if (!function_exists('csrfToken')) {
    function csrfToken($key = 'default') {
        if (empty($_SESSION['csrf_tokens']) || !is_array($_SESSION['csrf_tokens'])) {
            $_SESSION['csrf_tokens'] = [];
        }
        if (empty($_SESSION['csrf_tokens'][$key])) {
            if (function_exists('random_bytes')) {
                $_SESSION['csrf_tokens'][$key] = bin2hex(random_bytes(32));
            } elseif (function_exists('openssl_random_pseudo_bytes')) {
                $_SESSION['csrf_tokens'][$key] = bin2hex(openssl_random_pseudo_bytes(32));
            } else {
                $_SESSION['csrf_tokens'][$key] = md5(uniqid(mt_rand(), true)) . md5(uniqid(mt_rand(), true));
            }
        }
        return $_SESSION['csrf_tokens'][$key];
    }
}

if (!function_exists('verifyCsrfToken')) {
    function verifyCsrfToken($token, $key = 'default') {
        return isset($_SESSION['csrf_tokens'][$key])
            && is_string($token)
            && hash_equals($_SESSION['csrf_tokens'][$key], $token);
    }
}

if (!function_exists('csrfField')) {
    function csrfField($key = 'default') {
        return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrfToken($key), ENT_QUOTES, 'UTF-8') . '">';
    }
}

if (!function_exists('logActivity')) {
    function logActivity($conn, $activity, $type = 'info') {
        $user_id = $_SESSION['user_id'] ?? null;
        $username = $_SESSION['username'] ?? 'System';
        $timestamp = date('Y-m-d H:i:s');
        $sql = "INSERT INTO activities (user_id, username, activity, type, created_at) VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("issss", $user_id, $username, $activity, $type, $timestamp);
        $stmt->execute();
        cleanupOldActivities($conn);
    }
}

if (!function_exists('cleanupOldActivities')) {
    function cleanupOldActivities($conn) {
        $delete_sql = "DELETE FROM activities WHERE created_at < DATE_SUB(NOW(), INTERVAL 24 HOUR)";
        $conn->query($delete_sql);
    }
}

if (!function_exists('formatRupiah')) {
    function formatRupiah($angka) {
        $angka = floatval($angka);
        $formatted = number_format($angka, 0, ',', '.');
        return 'Rp ' . $formatted;
    }
}

if (!function_exists('formatRupiahTanpaRp')) {
    function formatRupiahTanpaRp($angka) {
        return number_format($angka, 0, ',', '.');
    }
}

if (!function_exists('timeAgo')) {
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
            $dt = new DateTime($datetime);
            return $dt->format('d/m/Y H:i');
        }
    }
}

if (!function_exists('getCurrentPeriod')) {
    function getCurrentPeriod() {
        return date('Y-m');
    }
}

if (!function_exists('getPeriodLabel')) {
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
}

if (!function_exists('getPeriodRangeLabel')) {
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
}

if (!function_exists('formatTanggalIndonesia')) {
    function formatTanggalIndonesia($date) {
        if (empty($date) || $date == '0000-00-00') {
            return '';
        }
        $hari = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
        $bulan = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
                  'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
        $timestamp = strtotime($date);
        $hari_nama = $hari[date('w', $timestamp)];
        $tanggal = date('j', $timestamp);
        $bulan_nama = $bulan[date('n', $timestamp) - 1];
        $tahun = date('Y', $timestamp);
        return $hari_nama . ', ' . $tanggal . ' ' . $bulan_nama . ' ' . $tahun;
    }
}

if (!function_exists('formatTanggalTanpaHari')) {
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
        $bulan = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
                  'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
        return (string)(int)$dt->format('j') . ' ' . $bulan[(int)$dt->format('n') - 1] . ' ' . $dt->format('Y');
    }
}

// Session timeout constant & idle check
if (!defined('SESSION_TIMEOUT')) {
    define('SESSION_TIMEOUT', 7200);
}
if (function_exists('isLoggedIn') && isLoggedIn()) {
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > SESSION_TIMEOUT)) {
        session_unset();
        session_destroy();
        if (defined('BASE_URL')) {
            header('Location: ' . BASE_URL . 'login?expired=1');
        }
        exit();
    }
    $_SESSION['last_activity'] = time();
}
