<?php
$page_title = 'Dashboard';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';

// Get statistics
$stats = [];

// Total Guru
$sql = "SELECT COUNT(*) as total FROM guru";
$result = $conn->query($sql);
$stats['total_guru'] = $result->fetch_assoc()['total'];

// Get current period from settings
$sql = "SELECT periode_aktif, jumlah_periode, periode_mulai, periode_akhir FROM settings LIMIT 1";
$result = $conn->query($sql);
$settings = $result->fetch_assoc();
$periode_aktif = $settings['periode_aktif'] ?? date('Y-m');
$jumlah_periode = isset($settings['jumlah_periode']) ? intval($settings['jumlah_periode']) : 1;
$periode_mulai = $settings['periode_mulai'] ?? '';
$periode_akhir = $settings['periode_akhir'] ?? '';

// Get statistics from legger_gaji (source of truth - already multiplied by jumlah_periode)
// This ensures consistency with the legger display
$sql = "SELECT 
        COALESCE(SUM(gaji_pokok), 0) as total_gaji_pokok,
        COALESCE(SUM(total_tunjangan), 0) as total_tunjangan,
        COALESCE(SUM(total_potongan), 0) as total_potongan,
        COALESCE(SUM(gaji_bersih), 0) as total_gaji_bersih
        FROM legger_gaji 
        WHERE periode = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $periode_aktif);
$stmt->execute();
$result = $stmt->get_result();
$legger_stats = $result->fetch_assoc();

// Check if legger_gaji has data for this period (even if all values are 0)
// If exists, use it as source of truth (already multiplied by jumlah_periode)
// This ensures consistency with the legger display
$sql_check = "SELECT COUNT(*) as count FROM legger_gaji WHERE periode = ?";
$stmt_check = $conn->prepare($sql_check);
$stmt_check->bind_param("s", $periode_aktif);
$stmt_check->execute();
$check_result = $stmt_check->get_result();
$legger_exists = ($check_result->fetch_assoc()['count'] ?? 0) > 0;
$stmt_check->close();

if ($legger_exists && $legger_stats) {
    // Use data from legger_gaji (already multiplied by jumlah_periode)
    // This is the source of truth and ensures consistency with legger display
    $stats['total_gaji_pokok'] = floatval($legger_stats['total_gaji_pokok'] ?? 0);
    $stats['total_tunjangan'] = floatval($legger_stats['total_tunjangan'] ?? 0);
    $stats['total_potongan'] = floatval($legger_stats['total_potongan'] ?? 0);
    $stats['total_gaji_bersih'] = floatval($legger_stats['total_gaji_bersih'] ?? 0);
    $stmt->close();
} else {
    // Fallback: calculate from detail tables and multiply by jumlah_periode
    // This is used when legger hasn't been generated yet
    $stmt->close();
    
    // Total Gaji Pokok (gaji pokok tidak tergantung periode)
    $sql = "SELECT COALESCE(SUM(jumlah), 0) as total FROM gaji_pokok";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $result = $stmt->get_result();
    $gaji_pokok_base = floatval($result->fetch_assoc()['total'] ?? 0);
    // Kalikan dengan jumlah_periode karena ini untuk periode tertentu
    $stats['total_gaji_pokok'] = $gaji_pokok_base * $jumlah_periode;
    $stmt->close();
    
    // Total Tunjangan
    $sql = "SELECT COALESCE(SUM(td.jumlah), 0) as total
            FROM tunjangan_detail td
            JOIN tunjangan t ON td.tunjangan_id = t.id
            WHERE td.periode = ?
            AND t.aktif = 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $periode_aktif);
    $stmt->execute();
    $result = $stmt->get_result();
    $tunjangan_base = floatval($result->fetch_assoc()['total'] ?? 0);
    $stats['total_tunjangan'] = $tunjangan_base * $jumlah_periode;
    $stmt->close();
    
    // Total Potongan
    $sql = "SELECT COALESCE(SUM(pd.jumlah), 0) as total
            FROM potongan_detail pd
            JOIN potongan p ON pd.potongan_id = p.id
            WHERE pd.periode = ?
            AND p.aktif = 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $periode_aktif);
    $stmt->execute();
    $result = $stmt->get_result();
    $potongan_base = floatval($result->fetch_assoc()['total'] ?? 0);
    $stats['total_potongan'] = $potongan_base * $jumlah_periode;
    $stmt->close();
    
    // Total Gaji Bersih
    $stats['total_gaji_bersih'] = $stats['total_gaji_pokok'] + $stats['total_tunjangan'] - $stats['total_potongan'];
}

$stats['total_insentif'] = 0;
$check_insentif = $conn->query("SHOW TABLES LIKE 'insentif_detail'");
if ($check_insentif && ($check_insentif->num_rows ?? 0) > 0) {
    $check_insentif_master = $conn->query("SHOW TABLES LIKE 'insentif'");
    if ($check_insentif_master && ($check_insentif_master->num_rows ?? 0) > 0) {
        $result_insentif = $conn->query("SELECT COALESCE(SUM(idt.jumlah), 0) as total FROM insentif_detail idt JOIN insentif i ON idt.insentif_id = i.id WHERE i.aktif = 1");
    } else {
        $result_insentif = $conn->query("SELECT COALESCE(SUM(jumlah), 0) as total FROM insentif_detail");
    }
    if ($result_insentif) {
        $stats['total_insentif'] = floatval($result_insentif->fetch_assoc()['total'] ?? 0);
    }
}

$stats['total_pengeluaran'] = floatval($stats['total_gaji_bersih'] ?? 0) + floatval($stats['total_insentif'] ?? 0);

// Get total count of activities
$sql_count = "SELECT COUNT(*) as total FROM activities";
$total_activities = $conn->query($sql_count)->fetch_assoc()['total'];

// Get recent activities
$sql = "SELECT * FROM activities ORDER BY created_at DESC LIMIT 20";
$activities = $conn->query($sql)->fetch_all(MYSQLI_ASSOC);

function formatPeriodeIndonesia($periode) {
    $bulan = [
        '01' => 'Januari',
        '02' => 'Februari',
        '03' => 'Maret',
        '04' => 'April',
        '05' => 'Mei',
        '06' => 'Juni',
        '07' => 'Juli',
        '08' => 'Agustus',
        '09' => 'September',
        '10' => 'Oktober',
        '11' => 'November',
        '12' => 'Desember'
    ];

    if (preg_match('/^(\d{4})-(\d{2})$/', $periode, $matches)) {
        return ($bulan[$matches[2]] ?? $matches[2]) . ' ' . $matches[1];
    }

    return $periode;
}

if ($jumlah_periode > 1 && !empty($periode_mulai) && !empty($periode_akhir)) {
    $periode_aktif_label = formatPeriodeIndonesia($periode_mulai) . ' - ' . formatPeriodeIndonesia($periode_akhir);
} else {
    $periode_aktif_label = formatPeriodeIndonesia($periode_aktif);
}

function dashboardStatIcon($name) {
    $paths = [
        'users' => '<path d="M17 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9.5" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>',
        'salary' => '<rect x="3" y="6" width="18" height="12" rx="2"/><circle cx="12" cy="12" r="3"/><path d="M7 9h.01M17 15h.01"/>',
        'allowance' => '<path d="M12 3v12"/><path d="M8 7h6a2 2 0 0 1 0 4h-4a2 2 0 0 0 0 4h6"/><path d="M4 19h16"/>',
        'deduction' => '<circle cx="12" cy="12" r="9"/><path d="M8 12h8"/>',
        'coins' => '<ellipse cx="12" cy="5" rx="7" ry="3"/><path d="M5 5v10c0 1.66 3.13 3 7 3s7-1.34 7-3V5"/><path d="M5 10c0 1.66 3.13 3 7 3s7-1.34 7-3"/>',
        'net' => '<rect x="5" y="3" width="14" height="18" rx="2"/><path d="M9 7h6M9 11h.01M12 11h.01M15 11h.01M9 15h.01M12 15h.01M15 15h.01"/>',
        'wallet' => '<path d="M3 7a2 2 0 0 1 2-2h14v14H5a2 2 0 0 1-2-2Z"/><path d="M16 11h5v4h-5a2 2 0 0 1 0-4Z"/><path d="M17.5 13h.01"/>'
    ];

    return '<svg class="dashboard-stat-icon" viewBox="0 0 24 24" aria-hidden="true" focusable="false" style="width:52px;height:52px;display:block;fill:none;stroke:currentColor;stroke-width:2.4;stroke-linecap:round;stroke-linejoin:round;flex-shrink:0;">' . ($paths[$name] ?? $paths['salary']) . '</svg>';
}
?>

            <div class="main-content">
                <section class="section">
                    <div class="section-header">
                        <div class="dashboard-heading">
                            <div>
                                <h1>Dashboard</h1>
                                <p>Ringkasan gaji dan aktivitas periode <?php echo htmlspecialchars($periode_aktif_label); ?></p>
                            </div>
                            <div class="dashboard-period-badge">
                                <span>Periode Aktif</span>
                                <strong><?php echo htmlspecialchars($periode_aktif_label); ?></strong>
                                <small><?php echo (int) $jumlah_periode; ?> periode</small>
                            </div>
                        </div>
                    </div>

                    <div class="section-body">
                        <!-- Statistics Widgets -->
                        <div class="row g-3 align-items-stretch">
                            <div class="col-lg-3 col-md-6 col-sm-6 col-6 d-flex">
                                <div class="card stat-card stat-card-primary flex-fill w-100">
                                    <div class="card-body">
                                        <div class="stat-icon-wrapper">
                                            <div class="stat-icon">
                                                <?php echo dashboardStatIcon('users'); ?>
                                            </div>
                                        </div>
                                        <div class="stat-content">
                                            <h3 class="stat-value stat-value-count"><?php echo number_format($stats['total_guru'], 0, ',', '.'); ?></h3>
                                            <p class="stat-label">Total Guru</p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-lg-3 col-md-6 col-sm-6 col-6 d-flex">
                                <div class="card stat-card stat-card-success flex-fill w-100">
                                    <div class="card-body">
                                        <div class="stat-icon-wrapper">
                                            <div class="stat-icon">
                                                <?php echo dashboardStatIcon('salary'); ?>
                                            </div>
                                        </div>
                                        <div class="stat-content">
                                            <h3 class="stat-value stat-value-money"><?php echo formatRupiah($stats['total_gaji_pokok']); ?></h3>
                                            <p class="stat-label">Total Gaji Pokok</p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-lg-3 col-md-6 col-sm-6 col-6 d-flex">
                                <div class="card stat-card stat-card-info flex-fill w-100">
                                    <div class="card-body">
                                        <div class="stat-icon-wrapper">
                                            <div class="stat-icon">
                                                <?php echo dashboardStatIcon('allowance'); ?>
                                            </div>
                                        </div>
                                        <div class="stat-content">
                                            <h3 class="stat-value stat-value-money"><?php echo formatRupiah($stats['total_tunjangan']); ?></h3>
                                            <p class="stat-label">Total Tunjangan</p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-lg-3 col-md-6 col-sm-6 col-6 d-flex">
                                <div class="card stat-card stat-card-warning flex-fill w-100">
                                    <div class="card-body">
                                        <div class="stat-icon-wrapper">
                                            <div class="stat-icon">
                                                <?php echo dashboardStatIcon('deduction'); ?>
                                            </div>
                                        </div>
                                        <div class="stat-content">
                                            <h3 class="stat-value stat-value-money"><?php echo formatRupiah($stats['total_potongan']); ?></h3>
                                            <p class="stat-label">Total Potongan</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row g-3 align-items-stretch">
                            <div class="col-lg-4 col-md-6 col-sm-6 col-6 d-flex">
                                <div class="card stat-card stat-card-info flex-fill w-100">
                                    <div class="card-body">
                                        <div class="stat-icon-wrapper">
                                            <div class="stat-icon">
                                                <?php echo dashboardStatIcon('coins'); ?>
                                            </div>
                                        </div>
                                        <div class="stat-content">
                                            <h3 class="stat-value stat-value-money"><?php echo formatRupiah($stats['total_insentif']); ?></h3>
                                            <p class="stat-label">Total Insentif</p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-lg-4 col-md-6 col-sm-6 col-6 d-flex">
                                <div class="card stat-card stat-card-primary flex-fill w-100">
                                    <div class="card-body">
                                        <div class="stat-icon-wrapper">
                                            <div class="stat-icon">
                                                <?php echo dashboardStatIcon('net'); ?>
                                            </div>
                                        </div>
                                        <div class="stat-content">
                                            <h3 class="stat-value stat-value-money"><?php echo formatRupiah($stats['total_gaji_bersih']); ?></h3>
                                            <p class="stat-label">Total Gaji Bersih</p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-lg-4 col-md-12 col-sm-12 col-12 d-flex">
                                <div class="card stat-card stat-card-danger flex-fill w-100">
                                    <div class="card-body">
                                        <div class="stat-icon-wrapper">
                                            <div class="stat-icon">
                                                <?php echo dashboardStatIcon('wallet'); ?>
                                            </div>
                                        </div>
                                        <div class="stat-content">
                                            <h3 class="stat-value stat-value-money"><?php echo formatRupiah($stats['total_pengeluaran']); ?></h3>
                                            <p class="stat-label">Total Pengeluaran</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Activity Timeline -->
                        <div class="row">
                            <div class="col-12">
                                <div class="card">
                                    <div class="card-header">
                                        <h4>
                                            Aktivitas Terbaru
                                            <?php if ($total_activities > 0): ?>
                                                <span class="badge badge-primary ml-2" id="activity-count"><?php echo $total_activities; ?></span>
                                            <?php endif; ?>
                                        </h4>
                                    </div>
                                    <div class="card-body">
                                        <div class="timeline timeline-scrollable">
                                            <?php if (empty($activities)): ?>
                                                <div class="text-center text-muted py-4">
                                                    <i class="fas fa-inbox fa-3x mb-3"></i>
                                                    <p>Tidak ada aktivitas</p>
                                                </div>
                                            <?php else: ?>
                                                <?php foreach ($activities as $activity): ?>
                                                    <div class="timeline-item">
                                                        <div class="timeline-marker bg-<?php 
                                                            echo $activity['type'] == 'success' ? 'success' : 
                                                                ($activity['type'] == 'danger' ? 'danger' : 
                                                                ($activity['type'] == 'warning' ? 'warning' : 'info')); 
                                                        ?>"></div>
                                                        <div class="timeline-content">
                                                            <div class="d-flex justify-content-between align-items-start">
                                                                <div>
                                                                    <h6 class="mb-1"><?php echo htmlspecialchars($activity['activity']); ?></h6>
                                                                    <p class="text-muted mb-0">
                                                                        <small>
                                                                            <i class="fas fa-user"></i> <?php echo htmlspecialchars($activity['username']); ?>
                                                                        </small>
                                                                    </p>
                                                                </div>
                                                                <div class="text-right">
                                                                    <small class="text-muted time-ago" data-timestamp="<?php echo strtotime($activity['created_at']); ?>">
                                                                        <?php echo timeAgo($activity['created_at']); ?>
                                                                    </small>
                                                                    <br>
                                                                    <small class="text-muted" style="font-size: 0.75rem;">
                                                                        <?php 
                                                                        $date = new DateTime($activity['created_at']);
                                                                        echo $date->format('d/m/Y H:i:s');
                                                                        ?>
                                                                    </small>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>
            </div>

<style>
/* Fix Section Body, Row, and Column Width to Be Full Width */
.section-body {
    width: 100% !important;
    padding: 1.5rem 0 0 0 !important;
    margin: 0 !important;
    box-sizing: border-box !important;
}

.row.g-3,
.row {
    width: calc(100% + 24px) !important;
    margin-left: -12px !important;
    margin-right: -12px !important;
    padding: 0 !important;
    box-sizing: border-box !important;
}

.row.g-3 > [class*='col-'],
.row > [class*='col-'] {
    display: flex !important;
    padding-left: 12px !important;
    padding-right: 12px !important;
    margin-bottom: 24px !important;
}

.row.g-3 > [class*='col-'] > .card,
.row.g-3 > [class*='col-'] > .stat-card,
.row > [class*='col-'] > .card {
    flex: 1 !important;
    width: 100% !important;
    box-sizing: border-box !important;
}

.dashboard-heading {
    width: 100%;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 18px;
}

/* Mobile Responsive Styles for Neat 2 Column Cards */
@media (max-width: 768px) {
    .row.g-3,
    .row {
        width: calc(100% + 20px) !important;
        margin-left: -10px !important;
        margin-right: -10px !important;
    }
    
    .row.g-3 > [class*='col-'],
    .row > [class*='col-'] {
        padding-left: 10px !important;
        padding-right: 10px !important;
        margin-bottom: 16px !important;
    }
    
    /* Fix col-6 to show 2 columns on mobile */
    .row.g-3 > .col-6,
    .row > .col-6 {
        flex: 0 0 50% !important;
        max-width: 50% !important;
        width: 50% !important;
    }
    
    /* Fix col-12 to stay 100% on mobile */
    .row.g-3 > .col-12,
    .row > .col-12 {
        flex: 0 0 100% !important;
        max-width: 100% !important;
        width: 100% !important;
    }
    
    body .main-content .section .section-body .stat-card .card-body {
        gap: 12px !important;
        min-height: 100px !important;
        padding: 1rem !important;
    }
    
    body .main-content .section .section-body .stat-card .card-body .stat-icon {
        width: 56px !important;
        height: 56px !important;
        font-size: 36px !important;
    }
    
    body .main-content .section .section-body .stat-card .card-body .stat-icon svg.dashboard-stat-icon {
        width: 36px !important;
        min-width: 36px !important;
        height: 36px !important;
    }
    
    body .main-content .section .section-body .stat-card .stat-value {
        font-size: 0.95rem !important;
    }
    
    body .main-content .section .section-body .stat-card .stat-value-money {
        font-size: 0.85rem !important;
    }
    
    body .main-content .section .section-body .stat-card .stat-label {
        font-size: 0.65rem !important;
    }
}

.dashboard-heading h1 {
    margin-bottom: 5px;
    color: #0f172a;
    font-weight: 800;
}

.dashboard-heading p {
    margin: 0;
    color: #64748b;
    font-weight: 600;
}

.dashboard-period-badge {
    min-width: 175px;
    padding: 12px 16px;
    border-radius: 12px;
    background: linear-gradient(135deg, rgba(37, 99, 235, 0.08), rgba(16, 185, 129, 0.11));
    border: 1px solid rgba(14, 165, 233, 0.16);
    box-shadow: 0 10px 24px rgba(15, 118, 110, 0.08);
    text-align: right;
}

.dashboard-period-badge span,
.dashboard-period-badge small {
    display: block;
    color: #64748b;
    font-size: 0.72rem;
    font-weight: 800;
    line-height: 1.2;
}

.dashboard-period-badge strong {
    display: block;
    color: #0f766e;
    font-size: 1rem;
    font-weight: 900;
    line-height: 1.25;
    margin: 3px 0;
}

/* Stat Card Styles */
.stat-card {
    border: none;
    border-radius: 12px;
    box-shadow: 0 10px 28px rgba(15, 118, 110, 0.08), 0 2px 8px rgba(37, 99, 235, 0.06);
    transition: all 0.3s ease;
    overflow: hidden;
    position: relative;
    background: #fff;
    height: 100%;
    display: flex;
    flex-direction: column;
    width: 100%;
    max-width: 100%;
    box-sizing: border-box;
}

.stat-card::before {
    content: '';
    position: absolute;
    inset: 0;
    background: linear-gradient(135deg, rgba(37, 99, 235, 0.035), rgba(16, 185, 129, 0.045));
    opacity: 0.85;
    pointer-events: none;
}

.stat-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 18px 36px rgba(15, 118, 110, 0.14), 0 8px 18px rgba(37, 99, 235, 0.1);
}

.stat-card .card-body {
    padding: 24px 26px;
    display: flex;
    align-items: center;
    gap: 22px;
    flex: 1;
    min-height: 122px;
    width: 100%;
    max-width: 100%;
    box-sizing: border-box;
    flex-wrap: nowrap;
    position: relative;
    z-index: 1;
}

.stat-card-large .card-body {
    padding: 35px;
    min-height: 140px;
}

.stat-icon-wrapper {
    flex-shrink: 0;
    display: flex;
    align-items: center;
    justify-content: center;
}

.stat-card .stat-icon {
    width: 76px;
    height: 76px;
    border-radius: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 52px !important;
    background: transparent !important;
    box-shadow: none;
    transition: all 0.3s ease;
    flex-shrink: 0;
}

.stat-card .stat-icon i {
    font-size: inherit !important;
    line-height: 1;
}

body .main-content .section .section-body .stat-card .stat-icon svg.dashboard-stat-icon {
    width: 52px !important;
    min-width: 52px !important;
    height: 52px !important;
    display: block !important;
}

.stat-card-large .stat-icon {
    width: 86px;
    height: 86px;
    font-size: 60px !important;
    border-radius: 0;
}

.stat-card:hover .stat-icon {
    transform: scale(1.08);
}

.stat-content {
    flex: 1;
    min-width: 0;
    width: 100%;
    max-width: 100%;
    overflow: visible;
    box-sizing: border-box;
    display: flex;
    flex-direction: column;
    justify-content: center;
    gap: 6px;
}

.stat-value {
    font-size: 1.28rem;
    font-weight: 800;
    margin: 0;
    color: #102033;
    line-height: 1.14;
    word-break: normal;
    overflow-wrap: anywhere;
    hyphens: auto;
    width: 100%;
    letter-spacing: 0;
}

.stat-value-money {
    font-size: clamp(0.88rem, 1vw, 1.08rem);
    max-width: 100%;
}

.stat-value-count {
    font-size: clamp(1.02rem, 1.18vw, 1.24rem);
}

.stat-card-large .stat-value {
    font-size: 2.25rem;
}

.stat-label {
    font-size: 0.7rem;
    color: #4f6480;
    margin: 0;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0;
    word-break: normal;
    overflow-wrap: anywhere;
    line-height: 1.22;
    max-width: 100%;
}

/* Color Themes */
.stat-card-primary .stat-icon {
    color: #0ea5e9;
}

.stat-card-success .stat-icon {
    color: #38a169;
}

.stat-card-info .stat-icon {
    color: #3182ce;
}

.stat-card-warning .stat-icon {
    color: #dd6b20;
}

.stat-card-danger .stat-icon {
    color: #e53e3e;
}

/* Timeline Styles */
.timeline {
    position: relative;
    padding-left: 30px;
}

.timeline-item {
    position: relative;
    padding-bottom: 25px;
    padding-left: 40px;
}

.timeline-item::before {
    content: '';
    position: absolute;
    left: 8px;
    top: 0;
    bottom: -25px;
    width: 2px;
    background: #e2e8f0;
}

.timeline-item:last-child::before {
    display: none;
}

.timeline-marker {
    position: absolute;
    left: 0;
    top: 5px;
    width: 18px;
    height: 18px;
    border-radius: 50%;
    border: 3px solid #fff;
    z-index: 1;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.15);
}

.timeline-content {
    background: #f7fafc;
    padding: 18px 20px;
    border-radius: 10px;
    border-left: 4px solid #0f766e;
    transition: all 0.2s ease;
}

.timeline-content:hover {
    background: #edf2f7;
    transform: translateX(5px);
}

.timeline-content h6 {
    font-size: 0.95rem;
    font-weight: 600;
    color: #2d3748;
    margin-bottom: 6px;
}

.timeline-content .text-muted {
    font-size: 0.85rem;
    color: #718096;
}

/* Timeline Scrollable */
.timeline-scrollable {
    max-height: 500px;
    overflow-y: auto;
    overflow-x: hidden;
    padding-right: 10px;
}

.timeline-scrollable::-webkit-scrollbar {
    width: 8px;
}

.timeline-scrollable::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 10px;
}

.timeline-scrollable::-webkit-scrollbar-thumb {
    background: #cbd5e0;
    border-radius: 10px;
    transition: background 0.3s ease;
}

.timeline-scrollable::-webkit-scrollbar-thumb:hover {
    background: #a0aec0;
}

/* Activity Count Badge */
.card-header h4 {
    display: flex;
    align-items: center;
    margin: 0;
}

#activity-count {
    font-size: 0.85rem;
    padding: 4px 10px;
    font-weight: 600;
    background: linear-gradient(135deg, #0ea5e9 0%, #10b981 100%);
    border: none;
}

/* Ensure equal height for widgets in row */
.row {
    display: flex;
    flex-wrap: wrap;
    margin-left: -15px;
    margin-right: -15px;
}

.row > [class*='col-'] {
    display: flex;
    flex-direction: column;
    padding-left: 15px;
    padding-right: 15px;
}

.row > [class*='col-'] > .stat-card {
    flex: 1;
    display: flex;
    flex-direction: column;
}

/* Ensure widgets have consistent spacing and height */
.stat-card {
    margin-bottom: 0;
}

.row {
    margin-left: -15px;
    margin-right: -15px;
}

/* Custom gap utility for consistent spacing */
.row.g-3 {
    margin-left: -12px;
    margin-right: -12px;
}

.row.g-3 > [class*='col-'] {
    padding-left: 12px;
    padding-right: 12px;
    margin-bottom: 24px;
}

/* Ensure all widgets in a row have same height */
.row:not(.g-3) > [class*='col-'] {
    margin-bottom: 24px;
}

/* Make sure all stat cards have consistent appearance */
.stat-card .card-body {
    display: flex;
    align-items: center;
    justify-content: flex-start;
}

/* Responsive - Tablet */
@media (max-width: 991px) and (min-width: 769px) {
    .stat-card .card-body {
        padding: 20px;
        gap: 15px;
        min-height: 110px;
    }
    
    .stat-card .stat-icon {
        width: 70px;
        height: 70px;
        font-size: 48px !important;
    }

    body .main-content .section .section-body .stat-card .stat-icon svg.dashboard-stat-icon {
        width: 48px !important;
        min-width: 48px !important;
        height: 48px !important;
    }
    
    .stat-value {
        font-size: 1.5rem;
    }
    
    .stat-card-large .stat-icon {
        width: 80px;
        height: 80px;
        font-size: 44px !important;
    }
    
    .stat-card-large .stat-value {
        font-size: 2rem;
    }
}

/* Responsive - Mobile */
@media (max-width: 768px) {
    .dashboard-heading {
        align-items: flex-start;
        flex-direction: column;
    }

    .dashboard-period-badge {
        width: 100%;
        text-align: left;
    }

    /* Ensure proper spacing between widgets */
    .row {
        margin-left: -10px;
        margin-right: -10px;
    }
    
    .row > [class*='col-'] {
        padding-left: 10px;
        padding-right: 10px;
        margin-bottom: 15px;
    }
    
    .stat-card {
        height: 100%;
        min-height: 110px;
    }
    
    .stat-card .card-body {
        padding: 18px;
        gap: 14px;
        min-height: 110px;
        flex-wrap: nowrap;
    }
    
    .stat-card-large .card-body {
        padding: 25px 18px;
        min-height: 120px;
    }
    
    .stat-card .stat-icon {
        width: 62px;
        height: 62px;
        font-size: 42px !important;
        flex-shrink: 0;
    }

    body .main-content .section .section-body .stat-card .stat-icon svg.dashboard-stat-icon {
        width: 42px !important;
        min-width: 42px !important;
        height: 42px !important;
    }
    
    .stat-card-large .stat-icon {
        width: 65px;
        height: 65px;
        font-size: 40px !important;
    }
    
    .stat-value {
        font-size: 1.35rem;
        line-height: 1.2;
        word-break: break-word;
        overflow-wrap: break-word;
    }
    
    .stat-card-large .stat-value {
        font-size: 1.6rem;
    }
    
    .stat-label {
        font-size: 0.85rem;
        line-height: 1.3;
    }
    
    .stat-card-large .stat-label {
        font-size: 0.9rem;
    }
    
    .timeline-scrollable {
        max-height: 400px;
    }
}

/* Responsive - Small Mobile */
@media (max-width: 480px) {
    .row {
        margin-left: -8px;
        margin-right: -8px;
    }
    
    .row > [class*='col-'] {
        padding-left: 8px;
        padding-right: 8px;
        margin-bottom: 12px;
    }
    
    .stat-card .card-body {
        padding: 14px;
        gap: 12px;
        min-height: 104px;
    }
    
    .stat-card-large .card-body {
        padding: 20px 15px;
        min-height: 110px;
    }
    
    .stat-card .stat-icon {
        width: 56px;
        height: 56px;
        font-size: 38px !important;
    }

    body .main-content .section .section-body .stat-card .stat-icon svg.dashboard-stat-icon {
        width: 38px !important;
        min-width: 38px !important;
        height: 38px !important;
    }
    
    .stat-card-large .stat-icon {
        width: 60px;
        height: 60px;
        font-size: 36px !important;
    }
    
    .stat-value {
        font-size: 1.2rem;
    }
    
    .stat-card-large .stat-value {
        font-size: 1.4rem;
    }
    
    .stat-label {
        font-size: 0.8rem;
    }
    
    .stat-card-large .stat-label {
        font-size: 0.85rem;
    }
}

/* Responsive - Projector & Large Display (1024px - 1600px) */
@media (min-width: 1024px) and (max-width: 1600px) {
    .stat-card .card-body {
        padding: 20px;
        gap: 15px;
    }
    
    .stat-card .stat-icon {
        width: 70px;
        height: 70px;
        font-size: 48px !important;
    }

    body .main-content .section .section-body .stat-card .stat-icon svg.dashboard-stat-icon {
        width: 48px !important;
        min-width: 48px !important;
        height: 48px !important;
    }
    
    .stat-value {
        font-size: 1.5rem;
    }
    
    .stat-label {
        font-size: 0.85rem;
    }
}

/* Responsive - Wide Projector & Ultrawide Display (1601px+) */
@media (min-width: 1601px) {
    .stat-card .card-body {
        padding: 25px 30px;
        gap: 20px;
    }
    
    .stat-card .stat-icon {
        width: 82px;
        height: 82px;
        font-size: 56px !important;
    }

    body .main-content .section .section-body .stat-card .stat-icon svg.dashboard-stat-icon {
        width: 56px !important;
        min-width: 56px !important;
        height: 56px !important;
    }
    
    .stat-value {
        font-size: 1.75rem;
    }
    
    .stat-label {
        font-size: 0.95rem;
    }
}

/* Time ago styling */
.time-ago {
    font-weight: 600;
    color: #0f766e !important;
    display: block;
    margin-bottom: 4px;
}

/* Compact dashboard cards, mengikuti contoh card ringkas. */
.section-body > .row.g-3 {
    align-items: stretch;
}

.section-body > .row.g-3 > [class*='col-'] {
    display: flex;
}

body .main-content .section .section-body .stat-card {
    border: 1px solid #d9e2ec !important;
    border-radius: 8px !important;
    background: #ffffff !important;
    box-shadow: 0 2px 5px rgba(15, 23, 42, 0.08) !important;
    min-height: 82px !important;
    overflow: visible !important;
}

body .main-content .section .section-body .stat-card::before {
    display: none !important;
}

body .main-content .section .section-body .stat-card:hover {
    transform: translateY(-2px) !important;
    box-shadow: 0 6px 14px rgba(15, 23, 42, 0.1) !important;
}

body .main-content .section .section-body .stat-card .card-body {
    display: grid !important;
    grid-template-columns: 44px minmax(0, 1fr) !important;
    align-items: center !important;
    gap: 12px !important;
    min-height: 82px !important;
    padding: 18px 18px !important;
}

body .main-content .section .section-body .stat-card .stat-icon-wrapper {
    width: 44px !important;
    height: 44px !important;
}

body .main-content .section .section-body .stat-card .card-body .stat-icon {
    width: 44px !important;
    height: 44px !important;
    border-radius: 8px !important;
    color: #ffffff !important;
    box-shadow: none !important;
}

body .main-content .section .section-body .stat-card .card-body .stat-icon svg.dashboard-stat-icon {
    width: 23px !important;
    min-width: 23px !important;
    height: 23px !important;
    stroke-width: 2.5 !important;
}

body .main-content .section .section-body .stat-card .stat-content {
    min-width: 0 !important;
    gap: 4px !important;
    overflow: hidden !important;
}

body .main-content .section .section-body .stat-card .stat-label {
    order: 1 !important;
    color: #516789 !important;
    font-size: 0.82rem !important;
    font-weight: 500 !important;
    line-height: 1.2 !important;
    text-transform: none !important;
}

body .main-content .section .section-body .stat-card .stat-value {
    order: 2 !important;
    color: #020617 !important;
    font-size: 1.12rem !important;
    font-weight: 800 !important;
    line-height: 1.25 !important;
    white-space: normal !important;
    overflow-wrap: anywhere !important;
}

body .main-content .section .section-body .stat-card .stat-value-money {
    font-size: clamp(0.96rem, 0.95vw, 1.12rem) !important;
}

body .main-content .section .section-body .stat-card-primary .card-body .stat-icon {
    background: #2563eb !important;
}

body .main-content .section .section-body .stat-card-success .card-body .stat-icon {
    background: #10b981 !important;
}

body .main-content .section .section-body .stat-card-info .card-body .stat-icon {
    background: #0ea5e9 !important;
}

body .main-content .section .section-body .stat-card-warning .card-body .stat-icon {
    background: #f59e0b !important;
}

body .main-content .section .section-body .stat-card-danger .card-body .stat-icon {
    background: #ef4444 !important;
}

@media (min-width: 1200px) {
    .section-body > .row.g-3:first-of-type > .col-lg-3 {
        flex: 0 0 25%;
        max-width: 25%;
    }

    .section-body > .row.g-3:nth-of-type(2) > .col-lg-4 {
        flex: 0 0 33.333333%;
        max-width: 33.333333%;
    }
}

/* Make sure row takes full width */
.section-body > .row.g-3 {
    width: 100%;
    margin: 0 -12px;
}

.section-body > .row.g-3 > [class*='col-'] {
    padding: 0 12px;
    margin-bottom: 24px;
}

/* Ensure stat card takes full width of column */
body .main-content .section .section-body .stat-card {
    width: 100% !important;
    flex-grow: 1 !important;
}

@media (max-width: 575px) {
    .section-body > .row.g-3 > .col-6 {
        flex: 0 0 100%;
        max-width: 100%;
    }

    body .main-content .section .section-body .stat-card .card-body {
        padding: 16px !important;
    }
}
</style>

<script>
// Function to calculate time ago
function getTimeAgo(timestamp) {
    var now = Math.floor(Date.now() / 1000);
    var diff = now - timestamp;
    
    if (diff < 60) {
        return 'Baru saja';
    } else if (diff < 3600) {
        var minutes = Math.floor(diff / 60);
        return minutes + ' menit yang lalu';
    } else if (diff < 86400) {
        var hours = Math.floor(diff / 3600);
        return hours + ' jam yang lalu';
    } else if (diff < 604800) {
        var days = Math.floor(diff / 86400);
        return days + ' hari yang lalu';
    } else {
        var date = new Date(timestamp * 1000);
        var day = String(date.getDate()).padStart(2, '0');
        var month = String(date.getMonth() + 1).padStart(2, '0');
        var year = date.getFullYear();
        var hours = String(date.getHours()).padStart(2, '0');
        var minutes = String(date.getMinutes()).padStart(2, '0');
        return day + '/' + month + '/' + year + ' ' + hours + ':' + minutes;
    }
}

// Update time ago every minute
(function() {
    function updateTimeAgo() {
        if (typeof jQuery === 'undefined') {
            setTimeout(updateTimeAgo, 100);
            return;
        }
        
        var $ = jQuery;
        $('.time-ago').each(function() {
            var timestamp = $(this).data('timestamp');
            if (timestamp) {
                $(this).text(getTimeAgo(timestamp));
            }
        });
    }
    
    // Update immediately
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(updateTimeAgo, 500);
            // Update every minute
            setInterval(updateTimeAgo, 60000);
        });
    } else {
        setTimeout(updateTimeAgo, 500);
        // Update every minute
        setInterval(updateTimeAgo, 60000);
    }
})();
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
