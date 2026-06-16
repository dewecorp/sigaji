<?php
require_once __DIR__ . '/../../config/config.php';
requireLogin();

header('Content-Type: application/json');

function runGitPull() {
    $rootDir = __DIR__ . '/../../';
    $output = [];
    $returnCode = 0;
    
    // Check if directory is git repo
    if (!is_dir($rootDir . '.git')) {
        return [
            'success' => false,
            'message' => 'Direktori ' . $rootDir . ' bukan repository Git. Pastikan .git directory ada.'
        ];
    }
    
    // Check if git is available
    $gitPath = exec('which git 2>/dev/null || where git 2>NUL', $gitOutput, $gitCode);
    if ($gitCode !== 0) {
        return [
            'success' => false,
            'message' => 'Git tidak terinstal atau tidak ditemukan di PATH server.'
        ];
    }
    
    // Check if exec function is available
    if (!function_exists('exec')) {
        return [
            'success' => false,
            'message' => 'Fungsi exec() dinonaktifkan di server. Tidak bisa menjalankan git pull.'
        ];
    }
    
    // Change to root dir
    if (!chdir($rootDir)) {
        return [
            'success' => false,
            'message' => 'Tidak bisa pindah ke direktori root: ' . $rootDir
        ];
    }
    
    // Run git pull
    exec('git pull 2>&1', $output, $returnCode);
    
    return [
        'success' => $returnCode === 0,
        'message' => implode("\n", $output)
    ];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $result = runGitPull();
    echo json_encode($result);
    exit;
}
