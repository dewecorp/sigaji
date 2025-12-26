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
                                        <h4>Informasi Profile</h4>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-12">
                                                <div class="form-group row">
                                                    <label class="col-sm-4 col-form-label"><strong>Username</strong></label>
                                                    <div class="col-sm-8">
                                                        <p class="form-control-plaintext"><?php echo htmlspecialchars($user['username']); ?></p>
                                                    </div>
                                                </div>
                                                
                                                <div class="form-group row">
                                                    <label class="col-sm-4 col-form-label"><strong>Nama Lengkap</strong></label>
                                                    <div class="col-sm-8">
                                                        <p class="form-control-plaintext"><?php echo htmlspecialchars($user['nama_lengkap']); ?></p>
                                                    </div>
                                                </div>
                                                
                                                <?php if (!empty($user['email'])): ?>
                                                <div class="form-group row">
                                                    <label class="col-sm-4 col-form-label"><strong>Email</strong></label>
                                                    <div class="col-sm-8">
                                                        <p class="form-control-plaintext"><?php echo htmlspecialchars($user['email']); ?></p>
                                                    </div>
                                                </div>
                                                <?php endif; ?>
                                                
                                                <div class="form-group row">
                                                    <label class="col-sm-4 col-form-label"><strong>Role</strong></label>
                                                    <div class="col-sm-8">
                                                        <span class="badge badge-<?php echo $user['role'] == 'admin' ? 'danger' : 'info'; ?> badge-lg">
                                                            <?php echo ucfirst($user['role']); ?>
                                                        </span>
                                                    </div>
                                                </div>
                                                
                                                <?php if (isset($user['created_at'])): ?>
                                                <div class="form-group row">
                                                    <label class="col-sm-4 col-form-label"><strong>Tanggal Dibuat</strong></label>
                                                    <div class="col-sm-8">
                                                        <p class="form-control-plaintext"><?php echo date('d F Y H:i', strtotime($user['created_at'])); ?></p>
                                                    </div>
                                                </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>
            </div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>



