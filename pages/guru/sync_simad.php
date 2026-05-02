<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/sync_simad_gurus.php';

requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . 'pages/guru');
    exit();
}

sync_simad_gurus_ensure_columns($conn);

$result = sync_guru_nama_dari_simad($conn);

if ($result['success']) {
    $_SESSION['success'] = $result['message'];
    logActivity(
        $conn,
        'Sinkron guru SIMAD: ' . $result['message'] . ' (ambil API: ' . (int)$result['fetched'] . ')',
        'success'
    );
} else {
    $_SESSION['error'] = $result['message'];
    logActivity($conn, 'Sinkron guru SIMAD gagal: ' . $result['message'], 'danger');
}

header('Location: ' . BASE_URL . 'pages/guru');
exit();
