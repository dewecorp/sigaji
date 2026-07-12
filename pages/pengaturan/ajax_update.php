<?php
require_once __DIR__ . '/../../config/config.php';

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

if (!function_exists('verifyCsrfToken')) {
    function verifyCsrfToken($token, $key = 'default') {
        return isset($_SESSION['csrf_tokens'][$key])
            && is_string($token)
            && hash_equals($_SESSION['csrf_tokens'][$key], $token);
    }
}

requireAdmin();

header('Content-Type: application/json');

define('SIGAJI_UPDATE_URL', 'https://github.com/dewecorp/sigaji/archive/refs/heads/master.zip');
define('SIGAJI_UPDATE_HOST', 'github.com');
define('SIGAJI_UPDATE_PATH', '/dewecorp/sigaji/archive/refs/heads/master.zip');

function updateSystemFromGitHub() {
    $rootDir = realpath(__DIR__ . '/../../');
    $repoUrl = SIGAJI_UPDATE_URL;
    $zipFile = $rootDir . '/latest_update.zip';
    $extractDir = $rootDir . '/update_temp';
    $lockFile = $rootDir . '/update.lock';
    
    if (!$rootDir) {
        return [
            'success' => false,
            'message' => 'Tidak dapat menentukan direktori root.'
        ];
    }

    if (!class_exists('ZipArchive')) {
        return [
            'success' => false,
            'message' => 'Ekstensi PHP ZipArchive belum aktif di hosting. Aktifkan extension zip/php-zip, lalu coba update lagi.'
        ];
    }

    if (!isAllowedUpdateUrl($repoUrl)) {
        return [
            'success' => false,
            'message' => 'Sumber update tidak valid. Update hanya diizinkan dari repository SIGAJI resmi.'
        ];
    }

    $lockHandle = fopen($lockFile, 'c');
    if (!$lockHandle || !flock($lockHandle, LOCK_EX | LOCK_NB)) {
        return [
            'success' => false,
            'message' => 'Proses update lain sedang berjalan. Tunggu beberapa saat lalu coba lagi.'
        ];
    }
    
    // Backup file yang wajib dipertahankan di hosting.
    $configBackup = [];
    foreach (['config/config.php', 'config/database.php', '.htaccess'] as $relativeFile) {
        $fullPath = $rootDir . '/' . $relativeFile;
        if (file_exists($fullPath)) {
            $configBackup[$relativeFile] = file_get_contents($fullPath);
        }
    }
    
    try {
        // Step 1: Download ZIP from GitHub
        $zipContent = downloadUpdateZip($repoUrl);
        if ($zipContent === false) {
            throw new Exception('Gagal mengunduh file update dari GitHub. Pastikan hosting dapat akses internet keluar ke github.com.');
        }
        
        if (file_put_contents($zipFile, $zipContent) === false) {
            throw new Exception('Gagal menyimpan file zip. Pastikan folder aplikasi bisa ditulis oleh PHP.');
        }
        
        // Step 2: Extract ZIP
        $zip = new ZipArchive();
        if ($zip->open($zipFile) !== TRUE) {
            throw new Exception('Gagal membuka file zip. File update mungkin tidak lengkap atau rusak.');
        }

        validateUpdateZip($zip);
        
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
        copyDirectory($sourceDir, $rootDir, $sourceDir, [
            'config/config.php',
            'config/database.php',
            '.htaccess',
            '.git',
            'latest_update.zip',
            'update_temp'
        ]);
        
        // Step 5: Restore config files
        foreach ($configBackup as $relativeFile => $content) {
            $targetFile = $rootDir . '/' . $relativeFile;
            $targetDir = dirname($targetFile);
            if (!is_dir($targetDir)) {
                mkdir($targetDir, 0755, true);
            }
            file_put_contents($targetFile, $content);
        }
        
        // Step 6: Cleanup temp files
        deleteFile($zipFile);
        deleteDirectory($extractDir);
        releaseUpdateLock($lockHandle, $lockFile);
        
        return [
            'success' => true,
            'message' => 'Sistem berhasil diperbarui ke versi terbaru!'
        ];
        
    } catch (Exception $e) {
        // Cleanup on error
        if (file_exists($zipFile)) deleteFile($zipFile);
        if (is_dir($extractDir)) deleteDirectory($extractDir);
        
        foreach ($configBackup as $relativeFile => $content) {
            $fullPath = $rootDir . '/' . $relativeFile;
            $targetDir = dirname($fullPath);
            if (!is_dir($targetDir)) {
                mkdir($targetDir, 0755, true);
            }
            file_put_contents($fullPath, $content);
        }
        releaseUpdateLock($lockHandle, $lockFile);
        
        return [
            'success' => false,
            'message' => 'Error: ' . $e->getMessage()
        ];
    }
}

function releaseUpdateLock($lockHandle, $lockFile) {
    if (is_resource($lockHandle)) {
        flock($lockHandle, LOCK_UN);
        fclose($lockHandle);
    }
    if (file_exists($lockFile)) {
        @unlink($lockFile);
    }
}

function isAllowedUpdateUrl($url) {
    $parts = parse_url($url);
    return isset($parts['scheme'], $parts['host'], $parts['path'])
        && $parts['scheme'] === 'https'
        && strtolower($parts['host']) === SIGAJI_UPDATE_HOST
        && $parts['path'] === SIGAJI_UPDATE_PATH;
}

function validateUpdateZip($zip) {
    $allowedTopFolders = [];

    for ($i = 0; $i < $zip->numFiles; $i++) {
        $name = $zip->getNameIndex($i);
        if ($name === false) {
            throw new Exception('Gagal membaca daftar isi ZIP update.');
        }

        $name = str_replace('\\', '/', $name);
        if ($name === '' || $name[0] === '/' || preg_match('#(^|/)\.\.(?:/|$)#', $name)) {
            throw new Exception('ZIP update mengandung path tidak aman: ' . $name);
        }

        $parts = explode('/', $name);
        if (!isset($parts[0]) || !preg_match('/^sigaji-[A-Za-z0-9._-]+$/', $parts[0])) {
            throw new Exception('Struktur ZIP update tidak sesuai repository SIGAJI.');
        }
        $allowedTopFolders[$parts[0]] = true;

        if (count($allowedTopFolders) > 1) {
            throw new Exception('ZIP update berisi lebih dari satu folder root.');
        }

        if (substr($name, -1) === '/') {
            continue;
        }

        $relativeName = implode('/', array_slice($parts, 1));
        validateUpdateFilePath($relativeName);
    }
}

function validateUpdateFilePath($relativeName) {
    if ($relativeName === '') {
        return;
    }

    $basename = basename($relativeName);
    $lowerName = strtolower($relativeName);
    $lowerBase = strtolower($basename);

    $blockedExact = [
        '.env',
        '.user.ini',
        'php.ini',
        'web.config',
        'config/config.php',
        'config/database.php',
    ];

    if (in_array($lowerName, $blockedExact, true) || strpos($lowerName, '.git/') === 0) {
        throw new Exception('ZIP update mengandung file yang tidak boleh diubah: ' . $relativeName);
    }

    $blockedExtensions = ['phar', 'phtml', 'shtml', 'cgi', 'pl', 'py', 'sh', 'bat', 'cmd', 'exe', 'dll', 'so'];
    $extension = strtolower(pathinfo($lowerBase, PATHINFO_EXTENSION));
    if (in_array($extension, $blockedExtensions, true)) {
        throw new Exception('ZIP update mengandung tipe file berbahaya: ' . $relativeName);
    }
}

function downloadUpdateZip($url) {
    $context = stream_context_create([
        'http' => [
            'timeout' => 60,
            'header' => "User-Agent: SIGAJI-Updater\r\n"
        ],
        'ssl' => [
            'verify_peer' => true,
            'verify_peer_name' => true
        ]
    ]);

    $content = @file_get_contents($url, false, $context);
    if ($content !== false) {
        return $content;
    }

    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 60,
            CURLOPT_USERAGENT => 'SIGAJI-Updater',
        ]);
        $content = curl_exec($ch);
        $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($content !== false && $statusCode >= 200 && $statusCode < 300) {
            return $content;
        }
    }

    return false;
}

function copyDirectory($src, $dst, $baseSrc, $exclude = []) {
    $dir = opendir($src);
    if ($dir === false) {
        throw new Exception('Tidak dapat membuka folder update: ' . $src);
    }

    if (!is_dir($dst) && !mkdir($dst, 0755, true)) {
        throw new Exception('Tidak dapat membuat folder tujuan: ' . $dst);
    }
    
    while (false !== ($file = readdir($dir))) {
        if ($file != '.' && $file != '..') {
            $srcPath = $src . '/' . $file;
            $dstPath = $dst . '/' . $file;
            $relativePath = normalizePath(ltrim(str_replace($baseSrc, '', $srcPath), '/\\'));
            
            if (isExcludedPath($relativePath, $exclude)) {
                continue;
            }
            
            if (is_dir($srcPath)) {
                copyDirectory($srcPath, $dstPath, $baseSrc, $exclude);
            } else {
                if (!copy($srcPath, $dstPath)) {
                    throw new Exception('Gagal menyalin file: ' . $relativePath);
                }
            }
        }
    }
    closedir($dir);
}

function normalizePath($path) {
    return str_replace('\\', '/', $path);
}

function isExcludedPath($relativePath, $exclude) {
    foreach ($exclude as $excludedPath) {
        $excludedPath = normalizePath($excludedPath);
        if ($relativePath === $excludedPath || strpos($relativePath, rtrim($excludedPath, '/') . '/') === 0) {
            return true;
        }
    }

    return false;
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
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '', 'system_update')) {
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'message' => 'Token keamanan tidak valid. Muat ulang halaman lalu coba lagi.'
        ]);
        exit;
    }

    $result = updateSystemFromGitHub();
    echo json_encode($result);
    exit;
}
