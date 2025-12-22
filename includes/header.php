<?php
require_once __DIR__ . '/../config/config.php';
requireLogin();

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];
$nama_lengkap = $_SESSION['nama_lengkap'];

// Get foto from database to ensure it's always up to date
$foto_sql = "SELECT foto FROM users WHERE id = ?";
$foto_stmt = $conn->prepare($foto_sql);
$foto_stmt->bind_param("i", $user_id);
$foto_stmt->execute();
$foto_result = $foto_stmt->get_result();
if ($foto_result->num_rows > 0) {
    $foto_data = $foto_result->fetch_assoc();
    $foto = $foto_data['foto'] ?? 'default.jpg';
    // Update session with latest foto
    $_SESSION['foto'] = $foto;
} else {
    $foto = $_SESSION['foto'] ?? 'default.jpg';
}
$foto_stmt->close();

// Get tahun ajaran from settings
$sql = "SELECT tahun_ajaran FROM settings LIMIT 1";
$result = $conn->query($sql);
$settings = $result->fetch_assoc();
$tahun_ajaran = $settings['tahun_ajaran'] ?? date('Y') . '/' . (date('Y') + 1);

// Cleanup old activities (older than 24 hours) on every page load
cleanupOldActivities($conn);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta content="width=device-width, initial-scale=1, maximum-scale=1, shrink-to-fit=no" name="viewport">
    <title><?php echo isset($page_title) ? $page_title . ' - ' : ''; ?><?php echo APP_NAME; ?></title>
    
    <!-- General CSS Files -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.7.2/css/all.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- CSS Libraries -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap4.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.2.2/css/buttons.bootstrap4.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
    
    <!-- Template CSS -->
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/style.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/components.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/custom.css">
    
    <!-- Sidebar Active Menu Styles -->
    <style>
        .main-sidebar,
        .main-sidebar.sidebar-style-2 {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
        }
        
        .main-sidebar .sidebar-brand a,
        .main-sidebar .sidebar-brand {
            color: #ffffff !important;
        }
        
        .main-sidebar .sidebar-menu li a,
        .main-sidebar .sidebar-menu li a i {
            color: rgba(255, 255, 255, 0.9) !important;
        }
        
        .main-sidebar .sidebar-menu li.active > a,
        .main-sidebar .sidebar-menu li.active a.nav-link,
        .main-sidebar .sidebar-menu a.nav-link.active {
            background-color: rgba(255, 255, 255, 0.25) !important;
            color: #ffffff !important;
            font-weight: 600 !important;
            border-left: 4px solid #ffffff !important;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2) !important;
            transform: translateX(5px);
        }
        
        .main-sidebar .sidebar-menu li.active > a i,
        .main-sidebar .sidebar-menu li.active a.nav-link i,
        .main-sidebar .sidebar-menu a.nav-link.active i {
            color: #ffffff !important;
            transform: scale(1.1);
        }
        
        .main-sidebar .sidebar-menu li a:hover {
            background-color: rgba(255, 255, 255, 0.15) !important;
            color: #ffffff !important;
        }
        
        .main-sidebar .sidebar-menu li.active > a:hover {
            background-color: rgba(255, 255, 255, 0.3) !important;
        }
        
        /* Header Info Styles */
        .navbar-info {
            flex: 1;
            justify-content: flex-start;
            display: flex;
            align-items: center;
            text-align: left;
            padding-left: 20px;
        }
        
        .navbar-info .madrasah-name {
            font-weight: 600;
            font-size: 20px;
            color: #2d3748;
            line-height: 1.3;
        }
        
        .navbar-info .tahun-ajaran {
            font-size: 16px;
            color: #667eea;
            font-weight: 600;
            margin-top: 3px;
        }
        
        @media (max-width: 991px) {
            .navbar-info {
                display: none !important;
            }
        }
        
        @media (max-width: 768px) {
            .navbar-info {
                display: none !important;
            }
        }
        
        /* Modern Clock Style */
        .datetime-display {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            white-space: nowrap;
            display: flex;
            flex-direction: row;
            align-items: center;
            justify-content: center;
            gap: 15px;
            padding: 0;
            background: none !important;
            border: none !important;
            border-radius: 0;
            min-width: auto;
            box-shadow: none !important;
            text-shadow: none !important;
            margin-right: 40px;
        }
        
        .navbar-right {
            display: flex !important;
            align-items: center !important;
            justify-content: flex-end !important;
        }
        
        .datetime-display * {
            text-shadow: none !important;
        }
        
        .datetime-display .date-part {
            color: #495057;
            font-size: 15px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
            margin: 0;
        }
        
        .datetime-display .date-part::before {
            content: '\f073';
            font-family: 'Font Awesome 5 Free';
            font-weight: 900;
            color: #667eea;
            font-size: 14px;
        }
        
        .datetime-display .time-part {
            color: #667eea;
            font-weight: 700;
            font-size: 20px;
            letter-spacing: 1.5px;
            display: flex;
            align-items: center;
            gap: 4px;
            font-variant-numeric: tabular-nums;
            margin: 0;
        }
        
        .datetime-display .time-part::before {
            content: '\f017';
            font-family: 'Font Awesome 5 Free';
            font-weight: 900;
            color: #667eea;
            font-size: 16px;
            margin-right: 6px;
        }
        
        .datetime-display .separator {
            color: #667eea;
            font-weight: 700;
            margin: 0 2px;
            animation: blink 1s infinite;
        }
        
        @keyframes blink {
            0%, 50% { opacity: 1; }
            51%, 100% { opacity: 0.3; }
        }
        
        .datetime-display .day-name {
            color: #667eea;
            font-weight: 600;
        }
        
        @media (max-width: 991px) {
            .datetime-display {
                padding: 8px 15px;
                gap: 12px;
            }
            
            .datetime-display .date-part {
                font-size: 14px;
            }
            
            .datetime-display .time-part {
                font-size: 18px;
            }
        }
        
        @media (max-width: 768px) {
            .datetime-display {
                padding: 6px 12px;
                gap: 10px;
            }
            
            .datetime-display .date-part {
                font-size: 13px;
            }
            
            .datetime-display .time-part {
                font-size: 16px;
            }
            
            .datetime-display .date-part::before,
            .datetime-display .time-part::before {
                display: none;
            }
        }
    </style>
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@300;400;600;700&display=swap" rel="stylesheet">
    
    <!-- Initialize Clock Immediately -->
    <script>
        // Initialize clock immediately and update every second
        (function() {
            var clockInterval = null;
            
            function updateClock() {
                var clockElement = document.getElementById('current-datetime');
                if (clockElement) {
                    var now = new Date();
                    var days = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
                    var months = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 
                                 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
                    
                    var dayName = days[now.getDay()];
                    var day = now.getDate();
                    var month = months[now.getMonth()];
                    var year = now.getFullYear();
                    
                    var hours = String(now.getHours()).padStart(2, '0');
                    var minutes = String(now.getMinutes()).padStart(2, '0');
                    var seconds = String(now.getSeconds()).padStart(2, '0');
                    
                    var datePart = '<span class="date-part"><span class="day-name">' + dayName + '</span>, ' + day + ' ' + month + ' ' + year + '</span>';
                    var timePart = '<span class="time-part">' + hours + '<span class="separator">:</span>' + minutes + '<span class="separator">:</span>' + seconds + '</span>';
                    
                    clockElement.innerHTML = datePart + timePart;
                }
            }
            
            function initClock() {
                // Update immediately
                updateClock();
                
                // Clear any existing interval
                if (clockInterval) {
                    clearInterval(clockInterval);
                }
                
                // Update every second (1000ms)
                clockInterval = setInterval(updateClock, 1000);
            }
            
            // Try to initialize immediately
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', initClock);
            } else {
                initClock();
            }
        })();
    </script>
</head>
<body>
    <div id="app">
        <div class="main-wrapper main-wrapper-1">
            <div class="navbar-bg"></div>
            <nav class="navbar navbar-expand-lg main-navbar">
                <form class="form-inline mr-auto">
                    <ul class="navbar-nav mr-3">
                        <li><a href="#" data-toggle="sidebar" class="nav-link nav-link-lg"><i class="fas fa-bars"></i></a></li>
                    </ul>
                </form>
                <div class="navbar-nav mr-auto navbar-info">
                    <div>
                        <div class="madrasah-name">
                            Madrasah Ibtidaiyah Sultan Fattah Sukosono
                        </div>
                        <div class="tahun-ajaran">
                            Tahun Ajaran <?php echo htmlspecialchars($tahun_ajaran); ?>
                        </div>
                    </div>
                </div>
                <ul class="navbar-nav navbar-right" style="display: flex; align-items: center; gap: 20px;">
                    <li class="dropdown" style="margin-right: 0;">
                        <div id="current-datetime" class="datetime-display"></div>
                    </li>
                    <li class="dropdown">
                        <a href="#" data-toggle="dropdown" class="nav-link dropdown-toggle nav-link-lg nav-link-user">
                            <?php 
                            $foto_path = __DIR__ . '/../assets/img/users/' . $foto;
                            $foto_url = BASE_URL . 'assets/img/users/' . $foto;
                            $default_foto_url = BASE_URL . 'assets/img/users/default.jpg';
                            
                            // Use default if user foto doesn't exist
                            if (!file_exists($foto_path) || empty($foto)) {
                                $foto_url = $default_foto_url;
                            }
                            
                            // Data URI fallback for SVG placeholder (prevents infinite loop)
                            $fallback_data_uri = 'data:image/svg+xml;base64,' . base64_encode('<svg width="100" height="100" xmlns="http://www.w3.org/2000/svg"><rect width="100" height="100" fill="#e0e0e0"/><circle cx="50" cy="35" r="15" fill="#999"/><path d="M 30 70 Q 30 55 50 55 Q 70 55 70 70 L 70 85 L 30 85 Z" fill="#999"/></svg>');
                            ?>
                            <img alt="image" src="<?php echo $foto_url; ?>" class="user-img mr-2" style="width: 40px; height: 40px; object-fit: cover; border-radius: 50%; border: 2px solid #667eea; box-shadow: 0 2px 4px rgba(0,0,0,0.1); display: inline-block !important;" onerror="if(this.src!=='<?php echo $fallback_data_uri; ?>' && this.src!=='<?php echo $default_foto_url; ?>'){this.src='<?php echo $default_foto_url; ?>';}else if(this.src!=='<?php echo $fallback_data_uri; ?>'){this.src='<?php echo $fallback_data_uri; ?>';}this.onerror=null;">
                            <div class="d-sm-none d-lg-inline-block" style="font-size: 14px; font-weight: 500; vertical-align: middle;"><?php echo htmlspecialchars($nama_lengkap); ?></div>
                        </a>
                        <div class="dropdown-menu dropdown-menu-right">
                            <a href="<?php echo BASE_URL; ?>pages/pengguna/profile.php" class="dropdown-item has-icon">
                                <i class="far fa-user"></i> Profile
                            </a>
                            <div class="dropdown-divider"></div>
                            <a href="#" onclick="confirmLogout(event)" class="dropdown-item has-icon text-danger">
                                <i class="fas fa-sign-out-alt"></i> Logout
                            </a>
                        </div>
                    </li>
                </ul>
            </nav>


