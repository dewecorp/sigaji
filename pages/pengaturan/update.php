<?php
$page_title = 'Update Sistem';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';

// Fungsi untuk menjalankan git pull
function runGitPull() {
    // Pastikan direktori kerja adalah root project
    $rootDir = __DIR__ . '/../../';
    $output = [];
    $returnCode = 0;
    
    // Cek apakah git tersedia dan direktori ini adalah repo git
    if (!is_dir($rootDir . '.git')) {
        return [
            'success' => false,
            'message' => 'Direktori ini bukan repository Git.'
        ];
    }
    
    // Menjalankan git pull
    chdir($rootDir);
    exec('git pull 2>&1', $output, $returnCode);
    
    return [
        'success' => $returnCode === 0,
        'message' => implode("\n", $output)
    ];
}

// Cek apakah ada request POST untuk update
$updateResult = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_system'])) {
    $updateResult = runGitPull();
}
?>

<div class="main-content">
    <section class="section">
        <div class="section-header">
            <h1>Update Sistem</h1>
        </div>

        <div class="section-body">
            <div class="card">
                <div class="card-header">
                    <h4>Update Sistem dari GitHub</h4>
                </div>
                <div class="card-body">
                    <p>Anda dapat memperbarui sistem dari repository GitHub dengan mengklik tombol di bawah ini.</p>
                    
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle mr-2"></i> Pastikan server Anda memiliki akses ke repository GitHub dan git terinstal.
                    </div>
                    
                    <?php if ($updateResult): ?>
                        <div class="alert alert-<?php echo $updateResult['success'] ? 'success' : 'danger'; ?>">
                            <h5><?php echo $updateResult['success'] ? 'Update Berhasil!' : 'Update Gagal!'; ?></h5>
                            <pre><?php echo htmlspecialchars($updateResult['message']); ?></pre>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" action="">
                        <button type="submit" name="update_system" class="btn btn-primary btn-lg">
                            <i class="fas fa-sync-alt mr-2"></i> Update Sistem Sekarang
                        </button>
                    </form>
                </div>
            </div>
            
            <div class="card mt-4">
                <div class="card-header">
                    <h4>Repository GitHub</h4>
                </div>
                <div class="card-body">
                    <p><strong>URL:</strong> <a href="https://github.com/dewecorp/sigaji" target="_blank">https://github.com/dewecorp/sigaji</a></p>
                </div>
            </div>
        </div>
    </section>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
