<?php
// Endpoint AJAX untuk update sistem
require_once __DIR__ . '/../config/config.php';
requireLogin();

header('Content-Type: application/json');

function runGitPull() {
    $rootDir = __DIR__ . '/../';
    $output = [];
    $returnCode = 0;
    
    if (!is_dir($rootDir . '.git')) {
        return [
            'success' => false,
            'message' => 'Direktori ini bukan repository Git.'
        ];
    }
    
    chdir($rootDir);
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
