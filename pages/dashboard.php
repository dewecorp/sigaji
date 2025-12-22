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
$sql = "SELECT periode_aktif, jumlah_periode FROM settings LIMIT 1";
$result = $conn->query($sql);
$settings = $result->fetch_assoc();
$periode_aktif = $settings['periode_aktif'] ?? date('Y-m');
$jumlah_periode = isset($settings['jumlah_periode']) ? intval($settings['jumlah_periode']) : 1;

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

if ($legger_stats && ($legger_stats['total_gaji_pokok'] > 0 || $legger_stats['total_tunjangan'] > 0 || $legger_stats['total_potongan'] > 0)) {
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
    
    // Total Gaji Pokok
    $sql = "SELECT COALESCE(SUM(jumlah), 0) as total FROM gaji_pokok WHERE periode = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $periode_aktif);
    $stmt->execute();
    $result = $stmt->get_result();
    $gaji_pokok_base = floatval($result->fetch_assoc()['total'] ?? 0);
    $stats['total_gaji_pokok'] = $gaji_pokok_base * $jumlah_periode;
    $stmt->close();
    
    // Total Tunjangan
    $sql = "SELECT COALESCE(SUM(jumlah), 0) as total FROM tunjangan_detail WHERE periode = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $periode_aktif);
    $stmt->execute();
    $result = $stmt->get_result();
    $tunjangan_base = floatval($result->fetch_assoc()['total'] ?? 0);
    $stats['total_tunjangan'] = $tunjangan_base * $jumlah_periode;
    $stmt->close();
    
    // Total Potongan
    $sql = "SELECT COALESCE(SUM(jumlah), 0) as total FROM potongan_detail WHERE periode = ?";
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

// Delete activities older than 24 hours
$delete_sql = "DELETE FROM activities WHERE created_at < DATE_SUB(NOW(), INTERVAL 24 HOUR)";
$conn->query($delete_sql);

// Get total count of activities
$sql_count = "SELECT COUNT(*) as total FROM activities";
$total_activities = $conn->query($sql_count)->fetch_assoc()['total'];

// Get recent activities
$sql = "SELECT * FROM activities ORDER BY created_at DESC LIMIT 20";
$activities = $conn->query($sql)->fetch_all(MYSQLI_ASSOC);
?>

            <div class="main-content">
                <section class="section">
                    <div class="section-header">
                        <h1>Dashboard</h1>
                    </div>

                    <div class="section-body">
                        <!-- Statistics Widgets -->
                        <div class="row">
                            <div class="col-lg-3 col-md-6 col-sm-6 col-12 mb-4">
                                <div class="card stat-card stat-card-primary">
                                    <div class="card-body">
                                        <div class="stat-icon-wrapper">
                                            <div class="stat-icon">
                                                <i class="fas fa-users"></i>
                                            </div>
                                        </div>
                                        <div class="stat-content">
                                            <h3 class="stat-value"><?php echo number_format($stats['total_guru'], 0, ',', '.'); ?></h3>
                                            <p class="stat-label">Total Guru</p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-lg-3 col-md-6 col-sm-6 col-12 mb-4">
                                <div class="card stat-card stat-card-success">
                                    <div class="card-body">
                                        <div class="stat-icon-wrapper">
                                            <div class="stat-icon">
                                                <i class="fas fa-money-bill-wave"></i>
                                            </div>
                                        </div>
                                        <div class="stat-content">
                                            <h3 class="stat-value"><?php echo formatRupiah($stats['total_gaji_pokok']); ?></h3>
                                            <p class="stat-label">Total Gaji Pokok</p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-lg-3 col-md-6 col-sm-6 col-12 mb-4">
                                <div class="card stat-card stat-card-info">
                                    <div class="card-body">
                                        <div class="stat-icon-wrapper">
                                            <div class="stat-icon">
                                                <i class="fas fa-hand-holding-usd"></i>
                                            </div>
                                        </div>
                                        <div class="stat-content">
                                            <h3 class="stat-value"><?php echo formatRupiah($stats['total_tunjangan']); ?></h3>
                                            <p class="stat-label">Total Tunjangan</p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-lg-3 col-md-6 col-sm-6 col-12 mb-4">
                                <div class="card stat-card stat-card-warning">
                                    <div class="card-body">
                                        <div class="stat-icon-wrapper">
                                            <div class="stat-icon">
                                                <i class="fas fa-minus-circle"></i>
                                            </div>
                                        </div>
                                        <div class="stat-content">
                                            <h3 class="stat-value"><?php echo formatRupiah($stats['total_potongan']); ?></h3>
                                            <p class="stat-label">Total Potongan</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-lg-12 col-md-12 col-sm-12 col-12 mb-4">
                                <div class="card stat-card stat-card-danger stat-card-large">
                                    <div class="card-body">
                                        <div class="stat-icon-wrapper">
                                            <div class="stat-icon">
                                                <i class="fas fa-calculator"></i>
                                            </div>
                                        </div>
                                        <div class="stat-content">
                                            <h3 class="stat-value"><?php echo formatRupiah($stats['total_gaji_bersih']); ?></h3>
                                            <p class="stat-label">Total Gaji Bersih</p>
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
/* Stat Card Styles */
.stat-card {
    border: none;
    border-radius: 12px;
    box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
    transition: all 0.3s ease;
    overflow: hidden;
    position: relative;
    background: #fff;
    height: 100%;
    display: flex;
    flex-direction: column;
}

.stat-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
}

.stat-card .card-body {
    padding: 25px;
    display: flex;
    align-items: center;
    gap: 20px;
    flex: 1;
    min-height: 120px;
}

.stat-card-large .card-body {
    padding: 35px;
    min-height: 140px;
}

.stat-icon-wrapper {
    flex-shrink: 0;
}

.stat-icon {
    width: 70px;
    height: 70px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 28px;
    color: #fff;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    transition: all 0.3s ease;
}

.stat-card-large .stat-icon {
    width: 90px;
    height: 90px;
    font-size: 36px;
    border-radius: 16px;
}

.stat-card:hover .stat-icon {
    transform: scale(1.1) rotate(5deg);
}

.stat-content {
    flex: 1;
    min-width: 0;
}

.stat-value {
    font-size: 1.75rem;
    font-weight: 700;
    margin: 0 0 8px 0;
    color: #2d3748;
    line-height: 1.2;
    word-break: break-word;
}

.stat-card-large .stat-value {
    font-size: 2.25rem;
}

.stat-label {
    font-size: 0.9rem;
    color: #718096;
    margin: 0;
    font-weight: 500;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.stat-card-large .stat-label {
    font-size: 1rem;
}

/* Color Themes */
.stat-card-primary .stat-icon {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.stat-card-success .stat-icon {
    background: linear-gradient(135deg, #48bb78 0%, #38a169 100%);
}

.stat-card-info .stat-icon {
    background: linear-gradient(135deg, #4299e1 0%, #3182ce 100%);
}

.stat-card-warning .stat-icon {
    background: linear-gradient(135deg, #ed8936 0%, #dd6b20 100%);
}

.stat-card-danger .stat-icon {
    background: linear-gradient(135deg, #f56565 0%, #e53e3e 100%);
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
    border-left: 4px solid #667eea;
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
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border: none;
}

/* Ensure equal height for widgets in row */
.row > [class*='col-'] {
    display: flex;
    flex-direction: column;
}

.row > [class*='col-'] > .stat-card {
    flex: 1;
}

/* Responsive */
@media (max-width: 768px) {
    .stat-card .card-body {
        padding: 20px;
        gap: 15px;
        min-height: 110px;
    }
    
    .stat-card-large .card-body {
        min-height: 130px;
    }
    
    .stat-icon {
        width: 60px;
        height: 60px;
        font-size: 24px;
    }
    
    .stat-value {
        font-size: 1.5rem;
    }
    
    .stat-card-large .stat-icon {
        width: 70px;
        height: 70px;
        font-size: 28px;
    }
    
    .stat-card-large .stat-value {
        font-size: 1.75rem;
    }
    
    .timeline-scrollable {
        max-height: 400px;
    }
}

/* Time ago styling */
.time-ago {
    font-weight: 600;
    color: #667eea !important;
    display: block;
    margin-bottom: 4px;
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


