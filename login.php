<?php
require_once 'config/config.php';

// If already logged in, redirect to dashboard
if (isLoggedIn()) {
    header('Location: ' . BASE_URL . 'pages/dashboard');
    exit();
}

$settings = [];
$nama_madrasah = 'Madrasah Ibtidaiyah';
$logo_url = '';
$logo_exists = false;
try {
    $settings_result = $conn->query("SELECT nama_madrasah, logo FROM settings LIMIT 1");
    if ($settings_result) {
        $settings = $settings_result->fetch_assoc() ?: [];
        $nama_madrasah = $settings['nama_madrasah'] ?? $nama_madrasah;
        $logo_filename = !empty($settings['logo']) ? basename($settings['logo']) : '';
        if ($logo_filename !== '') {
            $logo_path = __DIR__ . '/assets/img/' . $logo_filename;
            if (file_exists($logo_path)) {
                $logo_exists = true;
                $logo_url = BASE_URL . 'assets/img/' . rawurlencode($logo_filename);
            }
        }
    }
} catch (Throwable $e) {
}

$error = '';
$login_success = false;
$login_name = '';
$expired = isset($_GET['expired']) ? true : false;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if (!empty($username) && !empty($password)) {
        $sql = "SELECT * FROM users WHERE username = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            
            // Debug: Check if password hash is valid
            $password_hash = $user['password'];
            $is_valid = password_verify($password, $password_hash);
            
            if ($is_valid) {
                session_regenerate_id(true);
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['nama_lengkap'] = $user['nama_lengkap'];
                $_SESSION['foto'] = $user['foto'] ?? 'default.jpg';
                $_SESSION['role'] = $user['role'];
                
                logActivity($conn, "User {$user['username']} berhasil login", 'success');
                
                $login_success = true;
                $login_name = $user['nama_lengkap'];
            } else {
                $error = 'Username atau password salah!';
            }
        } else {
            $error = 'Username atau password salah!';
        }
    } else {
        $error = 'Username dan password harus diisi!';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta content="width=device-width, initial-scale=1, maximum-scale=1, shrink-to-fit=no" name="viewport">
    <title>Login - <?php echo APP_NAME; ?></title>
    <?php
    $favicon_url = $logo_exists && $logo_url ? $logo_url : (BASE_URL . 'assets/img/logo.png');
    ?>
    <link rel="icon" href="<?php echo $favicon_url; ?>?v=<?php echo time(); ?>" type="image/png">
    
    <!-- General CSS Files -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.7.2/css/all.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@300;400;600;700&display=swap" rel="stylesheet">
    
    <style>
        body {
            font-family: 'Nunito', sans-serif;
        }
        .login-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #0ea5e9 100%);
            padding: 20px;
        }
        .login-card {
            background: #fff;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,.3);
            overflow: hidden;
            max-width: 1000px;
            width: 100%;
        }
        .login-image {
            background: linear-gradient(135deg, #0ea5e9 0%, #10b981 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px;
            min-height: 500px;
            position: relative;
            overflow: hidden;
        }
        .login-image::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.06) 0%, transparent 60%);
            animation: pulse 8s ease-in-out infinite;
        }
        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.1); }
        }
        .login-image img {
            max-width: 100%;
            height: auto;
            filter: drop-shadow(0 10px 20px rgba(0,0,0,.3));
            position: relative;
            z-index: 1;
        }
        .login-brand {
            text-align: center;
            color: #fff;
            position: relative;
            z-index: 1;
        }
        .login-logo {
            width: 180px;
            height: 180px;
            object-fit: contain;
            margin-bottom: 18px;
            filter: drop-shadow(0 8px 24px rgba(0,0,0,0.2));
        }
        .login-brand-title {
            font-size: 32px;
            font-weight: 800;
            line-height: 1.2;
            letter-spacing: 1px;
            text-shadow: 0 2px 8px rgba(0,0,0,0.15);
        }
        .login-brand-subtitle {
            margin-top: 8px;
            font-size: 15px;
            opacity: 0.9;
            text-shadow: 0 1px 4px rgba(0,0,0,0.1);
        }
        .login-form {
            padding: 48px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        .login-heading {
            text-align: center;
            margin-bottom: 8px;
        }
        .login-title {
            font-size: 1.75rem;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 0.25rem;
        }
        .login-subtitle {
            color: #64748b;
            margin-bottom: 2rem;
            font-size: 0.95rem;
        }
        .form-control {
            border-radius: 10px;
            padding: 12px 16px;
            border: 1.5px solid #e2e8f0;
            font-size: 0.95rem;
            transition: all 0.2s;
        }
        .form-control:focus {
            border-color: #0ea5e9;
            box-shadow: 0 0 0 3px rgba(14, 165, 233, 0.15);
        }
        .btn-login {
            background: linear-gradient(135deg, #0ea5e9 0%, #10b981 100%);
            border: none;
            border-radius: 10px;
            padding: 14px;
            font-weight: 700;
            color: #fff;
            width: 100%;
            font-size: 1rem;
            letter-spacing: 0.5px;
            transition: all 0.3s;
            cursor: pointer;
        }
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(14, 165, 233, 0.35);
        }
        .btn-login:active {
            transform: translateY(0);
        }
        .form-group label {
            font-weight: 600;
            color: #334155;
            font-size: 0.9rem;
            margin-bottom: 6px;
        }
        @media (max-width: 768px) {
            .login-image {
                min-height: 300px;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="row no-gutters">
                <div class="col-md-6 d-none d-md-block">
                    <div class="login-image">
                        <?php if ($logo_exists && $logo_url): ?>
                            <div class="login-brand">
                                <img src="<?php echo $logo_url; ?>?v=<?php echo time(); ?>" alt="Logo <?php echo htmlspecialchars($nama_madrasah, ENT_QUOTES); ?>" class="login-logo">
                                <div class="login-brand-title">SIGaji</div>
                                <div class="login-brand-subtitle"><?php echo htmlspecialchars($nama_madrasah); ?></div>
                            </div>
                        <?php else: ?>
                        <svg width="400" height="400" viewBox="0 0 400 400" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <circle cx="200" cy="200" r="180" fill="rgba(255,255,255,0.1)"/>
                            <path d="M150 150 L250 150 L250 200 L200 250 L150 200 Z" fill="rgba(255,255,255,0.3)" stroke="white" stroke-width="3"/>
                            <circle cx="200" cy="180" r="30" fill="white"/>
                            <path d="M170 220 Q200 240 230 220" stroke="white" stroke-width="3" fill="none"/>
                            <text x="200" y="320" text-anchor="middle" fill="white" font-size="24" font-weight="bold">SIGaji</text>
                            <text x="200" y="350" text-anchor="middle" fill="rgba(255,255,255,0.8)" font-size="16">Sistem Informasi Gaji</text>
                        </svg>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="login-form">
                        <div class="login-heading">
                            <h1 class="login-title">Selamat Datang</h1>
                            <p class="login-subtitle">Silakan login untuk mengakses sistem</p>
                        </div>
                        
                        <?php if ($expired): ?>
                            <div class="alert alert-warning" role="alert">
                                <i class="fas fa-clock"></i> Sesi Anda telah berakhir. Silakan login kembali.
                            </div>
                        <?php endif; ?>
                        
                        <form method="POST" action="">
                            <div class="form-group">
                                <label for="username"><i class="fas fa-user"></i> Username</label>
                                <input type="text" class="form-control" id="username" name="username" required autofocus>
                            </div>
                            
                            <div class="form-group">
                                <label for="password"><i class="fas fa-lock"></i> Password</label>
                                <input type="password" class="form-control" id="password" name="password" required>
                            </div>
                            
                            <button type="submit" class="btn btn-login">
                                <i class="fas fa-sign-in-alt"></i> Masuk
                            </button>
                        </form>
                        
                        <div class="text-center mt-4">
                            <small class="text-muted">
                                <i class="fas fa-shield-alt"></i> Sistem Informasi Gaji - MI Sultan Fattah Sukosono
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.7/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <?php if ($error): ?>
    <script>
        Swal.fire({
            icon: 'error',
            title: 'Login Gagal',
            text: '<?php echo addslashes($error); ?>',
            confirmButtonColor: '#6777ef',
            confirmButtonText: 'Coba Lagi'
        });
    </script>
    <?php endif; ?>
    
    <?php if ($login_success): ?>
    <script>
        Swal.fire({
            icon: 'success',
            title: 'Berhasil',
            text: 'Selamat datang, <?php echo addslashes($login_name); ?>!',
            timer: 2000,
            showConfirmButton: false
        }).then(() => {
            window.location.href = '<?php echo BASE_URL; ?>pages/dashboard';
        });
        // Fallback: redirect after timer if SweetAlert fails to load
        setTimeout(function() {
            window.location.href = '<?php echo BASE_URL; ?>pages/dashboard';
        }, 2500);
    </script>
    <?php endif; ?>
</body>
</html>
