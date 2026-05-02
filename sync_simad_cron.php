<?php
/**
 * Sinkron nama guru SIMAD dari cron / Task Scheduler (tanpa membuka browser).
 *
 * Sesuaikan host SIGaji Anda seperti saat akses dari browser Laragon:
 *   php sync_simad_cron.php
 *   php sync_simad_cron.php sistem_gaji.test
 *
 * Tanpa argumen pakai localhost + subdirectory /sistem_gaji/ (samakan struktur Anda).
 */

if (php_sapi_name() !== 'cli') {
    exit('Gunakan CLI: php sync_simad_cron.php');
}

$hostArg = isset($argv[1]) ? trim((string)$argv[1]) : '';

if ($hostArg !== '') {
    $_SERVER['HTTP_HOST'] = $hostArg;
    $_SERVER['SCRIPT_NAME'] = '/index.php';
} else {
    $_SERVER['HTTP_HOST'] = $_ENV['SIGAJI_HTTP_HOST'] ?? 'localhost';
    $_SERVER['SCRIPT_NAME'] = $_ENV['SIGAJI_SCRIPT_NAME'] ?? '/sistem_gaji/index.php';
}

$_SERVER['HTTPS'] ??= $_ENV['SIGAJI_HTTPS'] ?? 'off';
$_SERVER['SERVER_PORT'] ??= $_ENV['SIGAJI_PORT'] ?? '80';

require __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/sync_simad_gurus.php';

sync_simad_gurus_ensure_columns($conn);
$r = sync_guru_nama_dari_simad($conn);

echo ($r['success'] ? '[OK] ' : '[GAGAL] ') . $r['message'] . PHP_EOL;
exit($r['success'] ? 0 : 1);
