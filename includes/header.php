<?php
require_once __DIR__ . '/../config/config.php';
requireLogin();

$user_id = $_SESSION['user_id'] ?? null;
$username = $_SESSION['username'] ?? '';
$nama_lengkap = $_SESSION['nama_lengkap'] ?? '';

// Function to generate avatar with initials
function generateAvatar($name, $size = 100) {
    $name = trim($name);
    if (empty($name)) {
        $initials = 'U';
    } else {
        $words = explode(' ', $name);
        if (count($words) >= 2) {
            $initials = strtoupper(substr($words[0], 0, 1) . substr($words[count($words) - 1], 0, 1));
        } else {
            $initials = strtoupper(substr($name, 0, 2));
        }
    }
    
    // Generate color based on name (consistent color for same name)
    $colors = [
        ['#667eea', '#764ba2'], // Purple gradient
        ['#f093fb', '#f5576c'], // Pink gradient
        ['#4facfe', '#00f2fe'], // Blue gradient
        ['#43e97b', '#38f9d7'], // Green gradient
        ['#fa709a', '#fee140'], // Orange gradient
        ['#30cfd0', '#330867'], // Cyan gradient
        ['#a8edea', '#fed6e3'], // Light gradient
        ['#ff9a9e', '#fecfef'], // Rose gradient
    ];
    
    $colorIndex = abs(crc32($name)) % count($colors);
    $color = $colors[$colorIndex];
    
    $svg = '<svg width="' . $size . '" height="' . $size . '" xmlns="http://www.w3.org/2000/svg">
        <defs>
            <linearGradient id="grad' . $colorIndex . '" x1="0%" y1="0%" x2="100%" y2="100%">
                <stop offset="0%" style="stop-color:' . $color[0] . ';stop-opacity:1" />
                <stop offset="100%" style="stop-color:' . $color[1] . ';stop-opacity:1" />
            </linearGradient>
        </defs>
        <circle cx="' . ($size/2) . '" cy="' . ($size/2) . '" r="' . ($size/2) . '" fill="url(#grad' . $colorIndex . ')"/>
        <text x="50%" y="50%" font-family="Arial, sans-serif" font-size="' . ($size * 0.4) . '" font-weight="bold" fill="white" text-anchor="middle" dominant-baseline="central">' . htmlspecialchars($initials) . '</text>
    </svg>';
    
    return 'data:image/svg+xml;base64,' . base64_encode($svg);
}

// Get all user data including foto from database to ensure it's always correct
// Never use session foto to avoid showing wrong user's foto
$foto = '';
$has_foto = false;
if ($user_id) {
    // Get complete user data to ensure we have the right user
    $user_sql = "SELECT id, username, nama_lengkap, foto FROM users WHERE id = ?";
    $user_stmt = $conn->prepare($user_sql);
    if ($user_stmt) {
        $user_stmt->bind_param("i", $user_id);
        $user_stmt->execute();
        $user_result = $user_stmt->get_result();
        if ($user_result && $user_result->num_rows > 0) {
            $user_data = $user_result->fetch_assoc();
            // Verify this is the correct user by checking username matches session
            if ($user_data['username'] === $username) {
                // Check if user has foto
                $foto_file = !empty($user_data['foto']) ? $user_data['foto'] : '';
                $foto_path = __DIR__ . '/../assets/img/users/' . $foto_file;
                
                if (!empty($foto_file) && file_exists($foto_path)) {
                    $foto = $foto_file;
                    $has_foto = true;
                } else {
                    $foto = '';
                    $has_foto = false;
                }
                // Update session with latest data
                $_SESSION['foto'] = $foto;
                $_SESSION['nama_lengkap'] = $user_data['nama_lengkap'] ?? $nama_lengkap;
            } else {
                // Username mismatch - use avatar
                $foto = '';
                $has_foto = false;
                $_SESSION['foto'] = '';
            }
        }
        $user_stmt->close();
    }
}

// Generate avatar if no foto
$avatar_url = $has_foto ? '' : generateAvatar($nama_lengkap, 100);

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
    
    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 640 512'%3E%3Cpath fill='%23677eea' d='M0 80C0 53.5 21.5 32 48 32h544c26.5 0 48 21.5 48 48v352c0 26.5-21.5 48-48 48H48c-26.5 0-48-21.5-48-48V80zm64 96c0-17.7 14.3-32 32-32h448c17.7 0 32 14.3 32 32v64c0 17.7-14.3 32-32 32H96c-17.7 0-32-14.3-32-32v-64zm416 32c17.7 0 32-14.3 32-32s-14.3-32-32-32-32 14.3-32 32 14.3 32 32 32zM64 352c0-17.7 14.3-32 32-32h448c17.7 0 32 14.3 32 32v32c0 17.7-14.3 32-32 32H96c-17.7 0-32-14.3-32-32v-32zm416 32c17.7 0 32-14.3 32-32s-14.3-32-32-32-32 14.3-32 32 14.3 32 32 32z'/%3E%3C/svg%3E">
    <link rel="apple-touch-icon" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 640 512'%3E%3Cpath fill='%23677eea' d='M0 80C0 53.5 21.5 32 48 32h544c26.5 0 48 21.5 48 48v352c0 26.5-21.5 48-48 48H48c-26.5 0-48-21.5-48-48V80zm64 96c0-17.7 14.3-32 32-32h448c17.7 0 32 14.3 32 32v64c0 17.7-14.3 32-32 32H96c-17.7 0-32-14.3-32-32v-64zm416 32c17.7 0 32-14.3 32-32s-14.3-32-32-32-32 14.3-32 32 14.3 32 32 32zM64 352c0-17.7 14.3-32 32-32h448c17.7 0 32 14.3 32 32v32c0 17.7-14.3 32-32 32H96c-17.7 0-32-14.3-32-32v-32zm416 32c17.7 0 32-14.3 32-32s-14.3-32-32-32-32 14.3-32 32 14.3 32 32 32z'/%3E%3C/svg%3E">
    
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
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/responsive-fix.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/responsive-all.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/sidebar-expand.css">
    
    <!-- Sidebar Active Menu Styles -->
    <style>
        /* Pastikan icon hamburger menu selalu terlihat, bahkan ketika sidebar tertutup */
        /* Icon harus selalu terlihat di navbar, tidak peduli status sidebar */
        body .main-navbar [data-toggle="sidebar"],
        body .main-navbar a[data-toggle="sidebar"],
        body .navbar-nav [data-toggle="sidebar"],
        body .navbar-nav a[data-toggle="sidebar"],
        body .navbar-nav li [data-toggle="sidebar"],
        body .navbar-nav li a[data-toggle="sidebar"] {
            display: inline-block !important;
            visibility: visible !important;
            opacity: 1 !important;
            color: #6777ef !important;
            z-index: 999 !important;
            pointer-events: auto !important;
        }
        
        body .main-navbar [data-toggle="sidebar"] i,
        body .main-navbar a[data-toggle="sidebar"] i,
        body .navbar-nav [data-toggle="sidebar"] i,
        body .navbar-nav a[data-toggle="sidebar"] i {
            display: inline-block !important;
            visibility: visible !important;
            opacity: 1 !important;
            color: #6777ef !important;
        }
        .main-sidebar,
        .main-sidebar.sidebar-style-2 {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
        }
        
        .main-sidebar .sidebar-brand a,
        .main-sidebar .sidebar-brand {
            color: #ffffff !important;
        }
        
        .main-sidebar .sidebar-menu li a {
            color: rgba(255, 255, 255, 0.9) !important;
            font-size: 14px !important;
            font-weight: 500 !important;
        }
        
        .main-sidebar .sidebar-menu li a i {
            color: rgba(255, 255, 255, 0.9) !important;
            font-size: 15px !important;
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
                margin-right: 10px;
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
            
            /* Responsive navbar user dropdown */
            .navbar-nav.navbar-right {
                gap: 10px;
            }
            
            .nav-link-user {
                padding: 0.5rem 0.75rem;
            }
            
            .nav-link-user .d-sm-none.d-lg-inline-block {
                display: none !important;
            }
            
            .user-img {
                width: 32px;
                height: 32px;
            }
        }
        
        @media (max-width: 480px) {
            .datetime-display {
                padding: 4px 8px;
                gap: 8px;
                margin-right: 5px;
            }
            
            .datetime-display .date-part {
                font-size: 11px;
            }
            
            .datetime-display .time-part {
                font-size: 14px;
            }
            
            .main-navbar {
                padding: 0.5rem 0.75rem;
            }
            
            .main-navbar [data-toggle="sidebar"],
            .main-navbar a[data-toggle="sidebar"] {
                display: inline-block !important;
                visibility: visible !important;
                opacity: 1 !important;
                font-size: 1.125rem !important;
                padding: 0.375rem !important;
                color: #6777ef !important;
                z-index: 999 !important;
            }
            
            .main-navbar [data-toggle="sidebar"] i,
            .main-navbar a[data-toggle="sidebar"] i {
                display: inline-block !important;
                visibility: visible !important;
                opacity: 1 !important;
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
                            Tahun Ajaran <?php echo htmlspecialchars($tahun_ajaran ?? ''); ?>
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
                            if ($has_foto && !empty($foto)) {
                                $foto_path = __DIR__ . '/../assets/img/users/' . $foto;
                                $foto_url = BASE_URL . 'assets/img/users/' . $foto;
                                $cache_buster = '?v=' . $user_id . '&t=' . time();
                                $foto_url .= $cache_buster;
                                $img_src = $foto_url;
                            } else {
                                // Use avatar with initials
                                $img_src = $avatar_url;
                            }
                            ?>
                            <img alt="image" src="<?php echo $img_src; ?>" class="user-img mr-2" style="width: 40px; height: 40px; object-fit: cover; border-radius: 50%; border: 2px solid #667eea; box-shadow: 0 2px 4px rgba(0,0,0,0.1); display: inline-block !important;">
                            <div class="d-sm-none d-lg-inline-block" style="font-size: 14px; font-weight: 500; vertical-align: middle;"><?php echo htmlspecialchars($nama_lengkap ?? ''); ?></div>
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


