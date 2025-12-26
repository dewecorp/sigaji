<?php
$page_title = 'Profile';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';

// Get current user data
$user_id = $_SESSION['user_id'];
$sql = "SELECT * FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if (!$user) {
    $_SESSION['error'] = "Data pengguna tidak ditemukan";
    header('Location: ' . BASE_URL . 'pages/dashboard.php');
    exit();
}

// Get foto
$foto_user = $user['foto'] ?? 'default.jpg';
$foto_path = __DIR__ . '/../../assets/img/users/' . $foto_user;
$foto_url = BASE_URL . 'assets/img/users/' . $foto_user;
$default_foto_url = BASE_URL . 'assets/img/users/default.jpg';

if (!file_exists($foto_path) || empty($user['foto'])) {
    $foto_url = $default_foto_url;
}
?>

            <div class="main-content">
                <section class="section">
                    <div class="section-header">
                        <h1>Profile Saya</h1>
                        <div class="section-header-breadcrumb">
                            <div class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>pages/dashboard.php">Dashboard</a></div>
                            <div class="breadcrumb-item active">Profile</div>
                        </div>
                    </div>

                    <div class="section-body">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="card">
                                    <div class="card-body text-center">
                                        <img src="<?php echo $foto_url; ?>" alt="Foto Profile" 
                                             class="img-thumbnail rounded-circle mb-3" 
                                             style="width: 150px; height: 150px; object-fit: cover; border: 3px solid #667eea;"
                                             onerror="this.src='<?php echo $default_foto_url; ?>'">
                                        <h4 class="mb-1"><?php echo htmlspecialchars($user['nama_lengkap']); ?></h4>
                                        <p class="text-muted mb-3">@<?php echo htmlspecialchars($user['username']); ?></p>
                                        <span class="badge badge-<?php echo $user['role'] == 'admin' ? 'danger' : 'info'; ?> badge-lg">
                                            <?php echo ucfirst($user['role']); ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-8">
                                <div class="card">
                                    <div class="card-header">
                                        <h4>Edit Profile</h4>
                                    </div>
                                    <div class="card-body">
                                        <form method="POST" action="save_profile.php" enctype="multipart/form-data">
                                            <input type="hidden" name="id" value="<?php echo $user['id']; ?>">
                                            
                                            <div class="form-group">
                                                <label><strong>Foto Profile</strong></label>
                                                <div class="row">
                                                    <div class="col-md-6">
                                                        <input type="file" class="form-control" id="fotoInput" name="foto" accept="image/jpeg,image/jpg,image/png,image/gif">
                                                        <small class="text-muted">Format: JPG, PNG, GIF. Max: 2MB</small>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div id="fotoPreview" style="display: none;">
                                                            <p class="mb-2"><strong>Preview:</strong></p>
                                                            <img id="previewImage" src="" alt="Preview" class="img-thumbnail" style="max-width: 150px; max-height: 150px; object-fit: cover;">
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <div class="form-group">
                                                <label><strong>Username</strong></label>
                                                <input type="text" class="form-control" name="username" value="<?php echo htmlspecialchars($user['username']); ?>" required>
                                            </div>
                                            
                                            <div class="form-group">
                                                <label><strong>Nama Lengkap</strong></label>
                                                <input type="text" class="form-control" name="nama_lengkap" value="<?php echo htmlspecialchars($user['nama_lengkap']); ?>" required>
                                            </div>
                                            
                                            <div class="form-group">
                                                <label><strong>Email</strong></label>
                                                <input type="email" class="form-control" name="email" value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>">
                                            </div>
                                            
                                            <div class="form-group">
                                                <label><strong>Password Baru</strong> <small class="text-muted">(Kosongkan jika tidak ingin mengubah)</small></label>
                                                <input type="password" class="form-control" name="password" placeholder="Masukkan password baru">
                                                <small class="text-muted">Minimal 6 karakter</small>
                                            </div>
                                            
                                            <div class="form-group">
                                                <label><strong>Konfirmasi Password Baru</strong></label>
                                                <input type="password" class="form-control" name="password_confirm" placeholder="Konfirmasi password baru">
                                            </div>
                                            
                                            <div class="form-group">
                                                <button type="submit" class="btn btn-primary">
                                                    <i class="fas fa-save"></i> Simpan Perubahan
                                                </button>
                                                <a href="<?php echo BASE_URL; ?>pages/dashboard.php" class="btn btn-secondary">
                                                    <i class="fas fa-times"></i> Batal
                                                </a>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>
            </div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>

<script>
// Wait for jQuery to be loaded
(function() {
    function initProfileScripts() {
        if (typeof jQuery === 'undefined') {
            setTimeout(initProfileScripts, 50);
            return;
        }
        
        var $ = jQuery;
        
        $(document).ready(function() {
            // Preview foto when file is selected
            $('#fotoInput').on('change', function(e) {
                var file = e.target.files[0];
                if (file) {
                    // Validate file size (2MB)
                    if (file.size > 2 * 1024 * 1024) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'Ukuran file terlalu besar. Maksimal 2MB'
                        });
                        $(this).val('');
                        return;
                    }
                    
                    // Validate file type
                    var allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
                    if (!allowedTypes.includes(file.type)) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'Format file tidak didukung. Gunakan JPG, PNG, atau GIF'
                        });
                        $(this).val('');
                        return;
                    }
                    
                    // Show preview
                    var reader = new FileReader();
                    reader.onload = function(e) {
                        $('#previewImage').attr('src', e.target.result);
                        $('#fotoPreview').show();
                    };
                    reader.readAsDataURL(file);
                } else {
                    $('#fotoPreview').hide();
                }
            });
            
            // Validate password confirmation
            $('form').on('submit', function(e) {
                var password = $('input[name="password"]').val();
                var passwordConfirm = $('input[name="password_confirm"]').val();
                
                if (password && password.length < 6) {
                    e.preventDefault();
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Password minimal 6 karakter'
                    });
                    return false;
                }
                
                if (password && password !== passwordConfirm) {
                    e.preventDefault();
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Password dan konfirmasi password tidak cocok'
                    });
                    return false;
                }
            });
        });
    }
    
    // Start initialization
    initProfileScripts();
})();
</script>



