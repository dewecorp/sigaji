<?php
$page_title = 'Backup & Restore';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';

// Store backup messages for JavaScript handling
$backup_success = null;
$backup_error = null;

// Flag to prevent footer from showing alerts
$backup_page_handled = false;

if (isset($_SESSION['success'])) {
    $backup_success = $_SESSION['success'];
    unset($_SESSION['success']);
    $backup_page_handled = true;
}

if (isset($_SESSION['backup_error'])) {
    $backup_error = $_SESSION['backup_error'];
    unset($_SESSION['backup_error']);
    $backup_page_handled = true;
} elseif (isset($_SESSION['error'])) {
    $backup_error = $_SESSION['error'];
    unset($_SESSION['error']);
    $backup_page_handled = true;
}

// Set flag in session to prevent footer alerts
if ($backup_page_handled) {
    $_SESSION['backup_page_handled'] = true;
}

// Create backups table if not exists
$create_table_sql = "CREATE TABLE IF NOT EXISTS backups (
    id INT AUTO_INCREMENT PRIMARY KEY,
    filename VARCHAR(255) NOT NULL,
    filepath VARCHAR(500) NOT NULL,
    file_size BIGINT NOT NULL,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
$conn->query($create_table_sql);

// Add foreign key if not exists (safer approach)
$check_fk_sql = "SELECT COUNT(*) as count 
                 FROM information_schema.TABLE_CONSTRAINTS 
                 WHERE CONSTRAINT_SCHEMA = DATABASE() 
                 AND TABLE_NAME = 'backups' 
                 AND CONSTRAINT_TYPE = 'FOREIGN KEY' 
                 AND CONSTRAINT_NAME = 'backups_ibfk_1'";
$result = $conn->query($check_fk_sql);
$row = $result->fetch_assoc();
if ($row['count'] == 0) {
    // Check if users table exists
    $check_users = $conn->query("SHOW TABLES LIKE 'users'");
    if ($check_users->num_rows > 0) {
        $conn->query("ALTER TABLE backups ADD CONSTRAINT backups_ibfk_1 FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL");
    }
}

// Get all backups
$sql = "SELECT b.*, u.nama_lengkap 
        FROM backups b 
        LEFT JOIN users u ON b.created_by = u.id 
        ORDER BY b.created_at DESC";
$backups = $conn->query($sql)->fetch_all(MYSQLI_ASSOC);

// Helper function to format file size
function formatFileSize($bytes) {
    if ($bytes >= 1073741824) {
        return number_format($bytes / 1073741824, 2) . ' GB';
    } elseif ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 2) . ' MB';
    } elseif ($bytes >= 1024) {
        return number_format($bytes / 1024, 2) . ' KB';
    } else {
        return $bytes . ' bytes';
    }
}
?>

            <div class="main-content">
                <section class="section">
                    <div class="section-header">
                        <h1>Backup & Restore</h1>
                        <div class="section-header-breadcrumb">
                            <div class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>pages/dashboard.php">Dashboard</a></div>
                            <div class="breadcrumb-item active">Backup & Restore</div>
                        </div>
                    </div>

                    <div class="section-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-header">
                                        <h4>Backup Database</h4>
                                    </div>
                                    <div class="card-body">
                                        <p>Lakukan backup database untuk keamanan data.</p>
                                        <form method="POST" action="backup.php" id="formBackup">
                                            <button type="submit" class="btn btn-primary" id="btnBackup">
                                                <i class="fas fa-download"></i> Backup Database
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-header">
                                        <h4>Restore Database</h4>
                                    </div>
                                    <div class="card-body">
                                        <p>Restore database dari file backup.</p>
                                        <form method="POST" action="restore.php" enctype="multipart/form-data" id="formRestore">
                                            <div class="form-group">
                                                <label>Pilih File Backup (.sql)</label>
                                                <input type="file" class="form-control" name="backup_file" accept=".sql" required id="backupFileInput">
                                            </div>
                                            <button type="submit" class="btn btn-warning" id="btnRestore">
                                                <i class="fas fa-upload"></i> Restore Database
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row mt-4">
                            <div class="col-12">
                                <div class="card">
                                    <div class="card-header">
                                        <h4>Daftar Backup</h4>
                                    </div>
                                    <div class="card-body">
                                        <div class="table-responsive">
                                            <table class="table table-striped" id="tableBackups">
                                                <thead>
                                                    <tr>
                                                        <th>No</th>
                                                        <th>Nama File</th>
                                                        <th>Ukuran</th>
                                                        <th>Dibuat Oleh</th>
                                                        <th>Tanggal Backup</th>
                                                        <th>Aksi</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php if (count($backups) > 0): ?>
                                                        <?php $no = 1; foreach ($backups as $backup): ?>
                                                            <tr>
                                                                <td><?php echo $no++; ?></td>
                                                                <td><?php echo htmlspecialchars($backup['filename']); ?></td>
                                                                <td><?php echo formatFileSize($backup['file_size']); ?></td>
                                                                <td><?php echo htmlspecialchars($backup['nama_lengkap'] ?? '-'); ?></td>
                                                                <td><?php echo date('d/m/Y H:i:s', strtotime($backup['created_at'])); ?></td>
                                                                <td>
                                                                    <a href="download.php?id=<?php echo $backup['id']; ?>" class="btn btn-sm btn-primary download-backup" 
                                                                       data-filename="<?php echo htmlspecialchars($backup['filename']); ?>" 
                                                                       title="Unduh">
                                                                        <i class="fas fa-download"></i> Unduh
                                                                    </a>
                                                                    <button type="button" class="btn btn-sm btn-danger delete-backup" 
                                                                            data-id="<?php echo $backup['id']; ?>"
                                                                            data-filename="<?php echo htmlspecialchars($backup['filename']); ?>" 
                                                                            title="Hapus">
                                                                        <i class="fas fa-trash"></i> Hapus
                                                                    </button>
                                                                </td>
                                                            </tr>
                                                        <?php endforeach; ?>
                                                    <?php else: ?>
                                                        <tr>
                                                            <td colspan="6" class="text-center">Belum ada backup</td>
                                                        </tr>
                                                    <?php endif; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>
            </div>

<script>
(function() {
    // Store messages
    <?php if ($backup_success): ?>
    var backupSuccessMsg = <?php echo json_encode($backup_success); ?>;
    <?php else: ?>
    var backupSuccessMsg = null;
    <?php endif; ?>
    
    <?php if ($backup_error): ?>
    var backupErrorMsg = <?php echo json_encode($backup_error); ?>;
    <?php else: ?>
    var backupErrorMsg = null;
    <?php endif; ?>
    
    function initBackupPage() {
        if (typeof jQuery === 'undefined' || typeof $ === 'undefined' || typeof Swal === 'undefined') {
            setTimeout(initBackupPage, 50);
            return;
        }
        
        $(document).ready(function() {
            // Show alerts
            if (backupSuccessMsg && backupSuccessMsg !== null && backupSuccessMsg !== 'null') {
                var msg = backupSuccessMsg;
                var filename = '';
                if (typeof msg === 'string' && msg.indexOf('Backup database berhasil dibuat') !== -1) {
                    filename = msg.split(': ')[1] || '';
                    Swal.fire({
                        title: 'Backup Berhasil Dibuat!',
                        html: '<div style="text-align: center;"><i class="fas fa-check-circle" style="font-size: 60px; color: #28a745; margin-bottom: 20px;"></i><p style="font-size: 16px; margin-bottom: 10px;">Backup database berhasil dibuat dan disimpan.</p><p style="font-weight: bold; color: #3085d6; font-size: 14px; background: #e7f3ff; padding: 10px; border-radius: 5px; margin: 10px 0;">' + filename + '</p><p style="font-size: 13px; color: #666; margin-top: 10px;">File backup telah tersimpan di tabel. Anda dapat mengunduhnya kapan saja.</p></div>',
                        icon: 'success',
                        confirmButtonColor: '#3085d6',
                        confirmButtonText: '<i class="fas fa-check"></i> OK',
                        timer: 5000,
                        timerProgressBar: true
                    });
                    if (typeof toastr !== 'undefined') toastr.success(msg);
                } else if (typeof msg === 'string' && msg.indexOf('Backup berhasil dihapus') !== -1) {
                    Swal.fire({
                        title: 'Berhasil!',
                        html: '<div style="text-align: center;"><i class="fas fa-check-circle" style="font-size: 60px; color: #28a745; margin-bottom: 20px;"></i><p style="font-size: 16px;">' + msg + '</p></div>',
                        icon: 'success',
                        confirmButtonColor: '#3085d6',
                        confirmButtonText: '<i class="fas fa-check"></i> OK',
                        timer: 3000,
                        timerProgressBar: true
                    });
                    if (typeof toastr !== 'undefined') toastr.success(msg);
                } else if (typeof msg === 'string') {
                    Swal.fire({
                        title: 'Berhasil!',
                        text: msg,
                        icon: 'success',
                        confirmButtonColor: '#3085d6',
                        confirmButtonText: 'OK'
                    });
                    if (typeof toastr !== 'undefined') toastr.success(msg);
                }
            }
            
            if (backupErrorMsg && backupErrorMsg !== null && backupErrorMsg !== 'null' && typeof backupErrorMsg === 'string') {
                Swal.fire({
                    title: 'Error!',
                    html: '<div style="text-align: center;"><i class="fas fa-exclamation-circle" style="font-size: 60px; color: #dc3545; margin-bottom: 20px;"></i><p style="font-size: 16px; color: #dc3545;">' + backupErrorMsg + '</p></div>',
                    icon: 'error',
                    confirmButtonColor: '#dc3545',
                    confirmButtonText: '<i class="fas fa-times"></i> OK',
                    allowOutsideClick: false
                });
                if (typeof toastr !== 'undefined') toastr.error(backupErrorMsg);
            }
            
            // Initialize DataTable
            $('#tableBackups').DataTable({
                language: { url: 'https://cdn.datatables.net/plug-ins/1.11.5/i18n/id.json' },
                order: [[4, 'desc']],
                drawCallback: function() {
                    $('[data-toggle="tooltip"]').tooltip();
                }
            });
            
            // Download handler
            $(document).on('click', '.download-backup', function(e) {
                e.preventDefault();
                var filename = $(this).data('filename');
                var url = $(this).attr('href');
                Swal.fire({
                    title: 'Mengunduh Backup',
                    html: '<div style="text-align: center;"><i class="fas fa-download" style="font-size: 50px; color: #3085d6; margin-bottom: 20px;"></i><p style="font-size: 16px; margin-bottom: 10px;">Sedang mempersiapkan file...</p><p style="font-weight: bold; color: #3085d6; font-size: 14px;">' + filename + '</p></div>',
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    showConfirmButton: false,
                    didOpen: function() { Swal.showLoading(); }
                });
                setTimeout(function() {
                    window.location.href = url;
                    setTimeout(function() {
                        Swal.fire({
                            icon: 'success',
                            title: 'Download Dimulai',
                            html: '<div style="text-align: center;"><i class="fas fa-check-circle" style="font-size: 50px; color: #28a745; margin-bottom: 15px;"></i><p style="font-size: 15px;">File backup sedang diunduh.</p></div>',
                            timer: 3000,
                            showConfirmButton: true,
                            confirmButtonText: 'OK',
                            confirmButtonColor: '#3085d6'
                        });
                    }, 500);
                }, 500);
            });
            
            // Delete handler
            $(document).on('click', '.delete-backup', function(e) {
                e.preventDefault();
                e.stopPropagation();
                var id = $(this).attr('data-id') || $(this).data('id');
                var filename = $(this).attr('data-filename') || $(this).data('filename');
                if (!id || !filename) {
                    Swal.fire({ icon: 'error', title: 'Error', text: 'Data tidak valid' });
                    return false;
                }
                Swal.fire({
                    title: 'Konfirmasi Hapus Backup',
                    html: '<div style="text-align: center;"><i class="fas fa-exclamation-triangle" style="font-size: 60px; color: #f8bb86; margin-bottom: 20px;"></i><p style="font-size: 16px; margin-bottom: 10px;">Apakah Anda yakin ingin menghapus backup ini?</p><p style="font-weight: bold; color: #d33; font-size: 14px; background: #ffe6e6; padding: 10px; border-radius: 5px; margin: 10px 0;">' + filename + '</p><p style="font-size: 13px; color: #666; margin-top: 10px;">Tindakan ini tidak dapat dibatalkan!</p></div>',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: '<i class="fas fa-trash"></i> Ya, Hapus!',
                    cancelButtonText: '<i class="fas fa-times"></i> Batal',
                    reverseButtons: true,
                    focusCancel: true,
                    customClass: { popup: 'animated fadeIn', confirmButton: 'btn btn-danger', cancelButton: 'btn btn-secondary' },
                    buttonsStyling: false
                }).then(function(result) {
                    if (result.isConfirmed) {
                        Swal.fire({
                            title: 'Menghapus...',
                            html: 'Sedang menghapus backup, harap tunggu...',
                            allowOutsideClick: false,
                            allowEscapeKey: false,
                            showConfirmButton: false,
                            didOpen: function() { Swal.showLoading(); }
                        });
                        window.location.href = '<?php echo BASE_URL; ?>pages/backup/delete.php?id=' + id;
                    }
                });
                return false;
            });
            
            // Restore handler
            $('#formRestore').on('submit', function(e) {
                e.preventDefault();
                var form = this;
                var fileInput = $('#backupFileInput')[0];
                if (!fileInput.files || !fileInput.files[0]) {
                    Swal.fire({ icon: 'error', title: 'File Belum Dipilih', text: 'Silakan pilih file backup terlebih dahulu!', confirmButtonColor: '#3085d6', confirmButtonText: 'OK' });
                    return false;
                }
                var fileName = fileInput.files[0].name;
                var fileSize = (fileInput.files[0].size / 1024 / 1024).toFixed(2) + ' MB';
                Swal.fire({
                    title: 'Konfirmasi Restore Database',
                    html: '<div style="text-align: center;"><i class="fas fa-exclamation-triangle" style="font-size: 60px; color: #ffc107; margin-bottom: 20px;"></i><p style="font-size: 16px; margin-bottom: 15px; font-weight: bold; color: #d33;">PERINGATAN!</p><p style="font-size: 15px; margin-bottom: 10px;">Apakah Anda yakin ingin restore database?</p><div style="background: #fff3cd; padding: 15px; border-radius: 5px; margin: 15px 0; border-left: 4px solid #ffc107;"><p style="font-weight: bold; margin-bottom: 5px;">File: ' + fileName + '</p><p style="font-size: 13px; margin: 0;">Ukuran: ' + fileSize + '</p></div><p style="font-size: 14px; color: #d33; font-weight: bold;">Data yang ada akan diganti dengan data dari backup!</p><p style="font-size: 13px; color: #666; margin-top: 10px;">Pastikan Anda sudah melakukan backup terlebih dahulu.</p></div>',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#ffc107',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: '<i class="fas fa-upload"></i> Ya, Restore!',
                    cancelButtonText: '<i class="fas fa-times"></i> Batal',
                    reverseButtons: true,
                    focusCancel: true,
                    customClass: { popup: 'animated fadeIn', confirmButton: 'btn btn-warning', cancelButton: 'btn btn-secondary' },
                    buttonsStyling: false
                }).then(function(result) {
                    if (result.isConfirmed) {
                        Swal.fire({
                            title: 'Memproses Restore...',
                            html: 'Sedang memproses restore database, harap tunggu...<br><small>Proses ini mungkin memakan waktu beberapa saat</small>',
                            allowOutsideClick: false,
                            allowEscapeKey: false,
                            didOpen: function() { Swal.showLoading(); }
                        });
                        form.submit();
                    }
                });
                return false;
            });
            
            // Backup handler
            $('#formBackup').on('submit', function(e) {
                e.preventDefault();
                Swal.fire({
                    title: 'Membuat Backup...',
                    html: '<div style="text-align: center;"><i class="fas fa-database" style="font-size: 50px; color: #3085d6; margin-bottom: 20px;"></i><p style="font-size: 16px; margin-bottom: 10px;">Sedang membuat backup database...</p><p style="font-size: 13px; color: #666; margin-top: 10px;">Harap tunggu, proses ini mungkin memakan waktu beberapa saat.</p></div>',
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    showConfirmButton: false,
                    didOpen: function() { Swal.showLoading(); }
                });
                this.submit();
            });
        });
    }
    
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() { setTimeout(initBackupPage, 100); });
    } else {
        setTimeout(initBackupPage, 100);
    }
})();
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>



