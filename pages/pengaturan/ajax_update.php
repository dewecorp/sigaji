<?php
require_once __DIR__ . '/../../config/config.php';
requireLogin();

header('Content-Type: application/json');

function updateSystemFromGitHub() {
    $rootDir = realpath(__DIR__ . '/../../');
    $repoUrl = 'https://github.com/dewecorp/sigaji/archive/refs/heads/master.zip';
    $zipFile = $rootDir . '/latest_update.zip';
    $extractDir = $rootDir . '/update_temp';
    $configFile = $rootDir . '/config/config.php';
    $databaseFile = $rootDir . '/config/database.php';
    
    if (!$rootDir) {
        return [
            'success' => false,
            'message' => 'Tidak dapat menentukan direktori root.'
        ];
    }
    
    // Backup config files to preserve user's settings
    $configBackup = [];
    if (file_exists($configFile)) {
        $configBackup['config.php'] = file_get_contents($configFile);
    }
    if (file_exists($databaseFile)) {
        $configBackup['database.php'] = file_get_contents($databaseFile);
    }
    
    try {
        // Step 1: Download ZIP from GitHub
        $zipContent = file_get_contents($repoUrl);
        if ($zipContent === false) {
            return [
                'success' => false,
                'message' => 'Gagal mengunduh file dari GitHub.'
            ];
        }
        
        if (file_put_contents($zipFile, $zipContent) === false) {
            return [
                'success' => false,
                'message' => 'Gagal menyimpan file zip.'
            ];
        }
        
        // Step 2: Extract ZIP
        $zip = new ZipArchive();
        if ($zip->open($zipFile) !== TRUE) {
            return [
                'success' => false,
                'message' => 'Gagal membuka file zip.'
            ];
        }
        
        if (!is_dir($extractDir)) {
            mkdir($extractDir, 0755, true);
        }
        
        $zip->extractTo($extractDir);
        $zip->close();
        
        // Step 3: Find extracted folder (GitHub adds -master suffix)
        $extractedFolders = glob($extractDir . '/*', GLOB_ONLYDIR);
        if (empty($extractedFolders)) {
            throw new Exception('Tidak dapat menemukan folder hasil ekstrak.');
        }
        $sourceDir = $extractedFolders[0];
        
        // Step 4: Copy files from extracted folder to root, preserving config files
        copyDirectory($sourceDir, $rootDir, [
            'config/config.php',
            'config/database.php',
            '.htaccess' // Optional, preserve user's .htaccess
        ]);
        
        // Step 5: Restore config files
        foreach ($configBackup as $filename => $content) {
            file_put_contents($rootDir . '/' . $filename, $content);
        }
        
        // Step 6: Cleanup temp files
        deleteFile($zipFile);
        deleteDirectory($extractDir);
        
        return [
            'success' => true,
            'message' => 'Sistem berhasil diperbarui ke versi terbaru!'
        ];
        
    } catch (Exception $e) {
        // Cleanup on error
        if (file_exists($zipFile)) deleteFile($zipFile);
        if (is_dir($extractDir)) deleteDirectory($extractDir);
        
        // Restore config files if they were deleted
        foreach ($configBackup as $filename => $content) {
            $fullPath = $rootDir . '/' . $filename;
            if (!file_exists($fullPath)) {
                file_put_contents($fullPath, $content);
            }
        }
        
        return [
            'success' => false,
            'message' => 'Error: ' . $e->getMessage()
        ];
    }
}

function copyDirectory($src, $dst, $exclude = []) {
    $dir = opendir($src);
    if (!is_dir($dst)) mkdir($dst, 0755, true);
    
    while (false !== ($file = readdir($dir))) {
        if ($file != '.' && $file != '..') {
            $srcPath = $src . '/' . $file;
            $dstPath = $dst . '/' . $file;
            $relativePath = ltrim(str_replace(realpath(__DIR__ . '/../../'), '', $srcPath), '/\\');
            
            // Skip excluded files
            if (in_array($relativePath, $exclude)) {
                continue;
            }
            
            if (is_dir($srcPath)) {
                copyDirectory($srcPath, $dstPath, $exclude);
            } else {
                copy($srcPath, $dstPath);
            }
        }
    }
    closedir($dir);
}

function deleteDirectory($dir) {
    if (!is_dir($dir)) return;
    $files = array_diff(scandir($dir), array('.', '..'));
    foreach ($files as $file) {
        $path = $dir . '/' . $file;
        is_dir($path) ? deleteDirectory($path) : unlink($path);
    }
    rmdir($dir);
}

function deleteFile($file) {
    if (file_exists($file)) {
        unlink($file);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $result = updateSystemFromGitHub();
    echo json_encode($result);
    exit;
}
