<?php
$current_page = basename($_SERVER['PHP_SELF']);
$menu_items = [
    'dashboard' => ['icon' => 'fas fa-home', 'url' => 'dashboard.php', 'label' => 'Dashboard', 'parent' => null],
    'gaji_guru' => [
        'icon' => 'fas fa-chalkboard-teacher', 
        'url' => '#', 
        'label' => 'Gaji Guru', 
        'parent' => null,
        'children' => [
            'guru' => ['icon' => 'fas fa-users', 'url' => 'guru/index.php', 'label' => 'Data Guru'],
            'gaji_pokok' => ['icon' => 'fas fa-money-bill-wave', 'url' => 'gaji_pokok/index.php', 'label' => 'Data Gaji Pokok'],
            'tunjangan' => ['icon' => 'fas fa-hand-holding-usd', 'url' => 'tunjangan/index.php', 'label' => 'Data Tunjangan'],
            'potongan' => ['icon' => 'fas fa-minus-circle', 'url' => 'potongan/index.php', 'label' => 'Data Potongan'],
            'legger' => ['icon' => 'fas fa-file-invoice-dollar', 'url' => 'legger/index.php', 'label' => 'Legger Gaji'],
        ]
    ],
    'honor_ekstrakurikuler' => [
        'icon' => 'fas fa-football-ball', 
        'url' => '#', 
        'label' => 'Honor Ekstrakurikuler', 
        'parent' => null,
        'children' => [
            'ekstrakurikuler' => ['icon' => 'fas fa-list', 'url' => 'ekstrakurikuler/index.php', 'label' => 'Data Ekstrakurikuler'],
            'pembina' => ['icon' => 'fas fa-user-tie', 'url' => 'pembina/index.php', 'label' => 'Data Pembina'],
            'honor' => ['icon' => 'fas fa-money-check-alt', 'url' => 'honor/index.php', 'label' => 'Data Honor'],
            'legger_honor' => ['icon' => 'fas fa-file-alt', 'url' => 'legger_honor/index.php', 'label' => 'Legger Honor'],
        ]
    ],
    'pengaturan' => ['icon' => 'fas fa-cog', 'url' => 'pengaturan/index.php', 'label' => 'Pengaturan', 'parent' => null],
    'backup' => ['icon' => 'fas fa-database', 'url' => 'backup/index.php', 'label' => 'Backup & Restore', 'parent' => null],
    'pengguna' => ['icon' => 'fas fa-user-shield', 'url' => 'pengguna/index.php', 'label' => 'Pengguna', 'parent' => null]
];

function isActive($url, $current_page) {
    // Skip if URL is '#' (parent menu)
    if ($url === '#') {
        return false;
    }
    
    // Get current script path
    $current_script = $_SERVER['PHP_SELF'];
    $script_path = str_replace('\\', '/', $current_script);
    
    // Parse menu URL
    $url_parts = explode('/', $url);
    $url_file = end($url_parts);
    $url_dir = count($url_parts) > 1 ? $url_parts[0] : '';
    
    // Special handling for dashboard
    if ($url === 'dashboard.php') {
        if ($current_page === 'dashboard.php') {
            if (preg_match('#/pages/dashboard\.php(\?|$)#', $script_path) && 
                !preg_match('#/pages/[^/]+/dashboard\.php#', $script_path)) {
                return true;
            }
        }
        return false;
    }
    
    // For menu items with subdirectories
    if ($url_dir) {
        $pattern = '#/pages/' . preg_quote($url_dir, '#') . '(/[^/]*\.php|/|\?|$)#';
        if (preg_match($pattern, $script_path)) {
            return true;
        }
    }
    
    return false;
}
?>
            <div class="main-sidebar sidebar-style-2">
                <aside id="sidebar-wrapper">
                    <div class="sidebar-brand">
                        <a href="<?php echo BASE_URL; ?>pages/dashboard.php">
                            <i class="fas fa-money-bill-wave mr-2"></i><?php echo APP_NAME; ?>
                        </a>
                    </div>
                    <div class="sidebar-brand sidebar-brand-sm">
                        <a href="<?php echo BASE_URL; ?>pages/dashboard.php">
                            <i class="fas fa-money-bill-wave mr-1"></i>SG
                        </a>
                    </div>
                    <ul class="sidebar-menu">
                        <?php foreach ($menu_items as $key => $item): 
                            if (isset($item['children']) && is_array($item['children']) && count($item['children']) > 0) {
                                // Parent menu with children - display as bold header
                                ?>
                                <li class="menu-parent">
                                    <a class="nav-link menu-parent-link" href="#" onclick="return false;" style="cursor: default;">
                                        <i class="<?php echo $item['icon']; ?>"></i>
                                        <span><?php echo $item['label']; ?></span>
                                    </a>
                                    <ul class="submenu">
                                        <?php foreach ($item['children'] as $child_key => $child): 
                                            $child_is_active = isActive($child['url'], $current_page);
                                            $child_active_class = $child_is_active ? 'active' : '';
                                        ?>
                                            <li class="submenu-item <?php echo $child_active_class; ?>">
                                                <a class="nav-link <?php echo $child_active_class; ?>" href="<?php echo BASE_URL; ?>pages/<?php echo $child['url']; ?>">
                                                    <i class="<?php echo $child['icon']; ?>"></i>
                                                    <span><?php echo $child['label']; ?></span>
                                                </a>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                </li>
                            <?php } else {
                                // Regular menu item
                                $is_active = isActive($item['url'], $current_page);
                                $active_class = $is_active ? 'active' : '';
                                ?>
                                <li class="<?php echo $active_class; ?>">
                                    <a class="nav-link <?php echo $active_class; ?>" href="<?php echo BASE_URL; ?>pages/<?php echo $item['url']; ?>">
                                        <i class="<?php echo $item['icon']; ?>"></i>
                                        <span><?php echo $item['label']; ?></span>
                                    </a>
                                </li>
                            <?php } ?>
                        <?php endforeach; ?>
                        <li>
                            <a class="nav-link" href="#" onclick="confirmLogout(event)">
                                <i class="fas fa-sign-out-alt"></i>
                                <span>Log Out</span>
                            </a>
                        </li>
                    </ul>
                </aside>
            </div>

<style>
/* Sidebar Menu Styles */
.sidebar-menu {
    list-style: none;
    margin: 0;
    padding: 0;
}

.sidebar-menu > li {
    list-style: none;
    margin: 0;
    padding: 0;
}

.sidebar-menu > li > a {
    display: flex;
    align-items: center;
    padding: 0.75rem 1.5rem;
    color: rgba(255, 255, 255, 0.9);
    text-decoration: none;
    transition: all 0.3s ease;
    font-size: 14px !important;
}

.sidebar-menu > li > a i:first-child {
    width: 20px;
    margin-right: 10px;
    text-align: center;
    font-size: 15px !important;
}

/* Parent Menu (Bold Header) */
.sidebar-menu .menu-parent {
    margin-top: 0.5rem;
}

.sidebar-menu .menu-parent > .menu-parent-link {
    font-weight: bold !important;
    font-size: 0.9rem !important;
    color: rgba(255, 255, 255, 0.9) !important;
    background-color: rgba(255, 255, 255, 0.08) !important;
    cursor: not-allowed !important;
    pointer-events: none !important;
    padding-top: 1rem;
    padding-bottom: 0.75rem;
    margin-bottom: 0.25rem;
    opacity: 0.85;
    user-select: none;
    -webkit-user-select: none;
    -moz-user-select: none;
    -ms-user-select: none;
}

.sidebar-menu .menu-parent > .menu-parent-link:hover {
    background-color: rgba(255, 255, 255, 0.08) !important;
    cursor: not-allowed !important;
}

.sidebar-menu .menu-parent > .menu-parent-link:active {
    pointer-events: none !important;
}

/* Submenu */
.sidebar-menu .menu-parent > .submenu {
    list-style: none;
    margin: 0;
    padding: 0;
    background-color: rgba(0, 0, 0, 0.15);
}

.sidebar-menu .menu-parent > .submenu > .submenu-item {
    list-style: none;
    margin: 0;
    padding: 0;
}

.sidebar-menu .menu-parent > .submenu > .submenu-item > a {
    padding-left: 2.5rem;
    padding-top: 0.6rem;
    padding-bottom: 0.6rem;
    font-size: 0.85rem !important;
    display: flex;
    align-items: center;
    color: rgba(255, 255, 255, 0.85);
}

.sidebar-menu .menu-parent > .submenu > .submenu-item > a i {
    width: 18px;
    margin-right: 10px;
    text-align: center;
    font-size: 0.8rem !important;
}

/* Active States */
.sidebar-menu > li.active > a,
.sidebar-menu > li.active > a.nav-link {
    background-color: rgba(255, 255, 255, 0.25) !important;
    color: #ffffff !important;
    font-weight: 600 !important;
    border-left: 4px solid #ffffff !important;
}

.sidebar-menu > li > a:hover:not(.menu-parent-link) {
    background-color: rgba(255, 255, 255, 0.15);
    color: #ffffff;
}

.sidebar-menu .menu-parent > .submenu > .submenu-item.active > a {
    background-color: rgba(255, 255, 255, 0.25) !important;
    color: #ffffff !important;
    font-weight: 600 !important;
    border-left: 4px solid #ffffff !important;
}

.sidebar-menu .menu-parent > .submenu > .submenu-item > a:hover {
    background-color: rgba(255, 255, 255, 0.15);
    color: #ffffff;
}
</style>
