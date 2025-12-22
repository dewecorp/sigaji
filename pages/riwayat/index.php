<?php
$page_title = 'Riwayat';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';

// Get all activities
$sql = "SELECT * FROM activities ORDER BY created_at DESC";
$activities = $conn->query($sql)->fetch_all(MYSQLI_ASSOC);
?>

            <div class="main-content">
                <section class="section">
                    <div class="section-header">
                        <h1>Riwayat Aktivitas</h1>
                        <div class="section-header-breadcrumb">
                            <div class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>pages/dashboard.php">Dashboard</a></div>
                            <div class="breadcrumb-item active">Riwayat</div>
                        </div>
                    </div>

                    <div class="section-body">
                        <div class="card">
                            <div class="card-header">
                                <h4>Data Aktivitas</h4>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-striped" id="tableRiwayat">
                                        <thead>
                                            <tr>
                                                <th>No</th>
                                                <th>Waktu</th>
                                                <th>Pengguna</th>
                                                <th>Aktivitas</th>
                                                <th>Tipe</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php $no = 1; foreach ($activities as $a): ?>
                                                <tr>
                                                    <td><?php echo $no++; ?></td>
                                                    <td><?php echo date('d/m/Y H:i:s', strtotime($a['created_at'])); ?></td>
                                                    <td><?php echo htmlspecialchars($a['username']); ?></td>
                                                    <td><?php echo htmlspecialchars($a['activity']); ?></td>
                                                    <td>
                                                        <span class="badge badge-<?php 
                                                            echo $a['type'] == 'success' ? 'success' : 
                                                                ($a['type'] == 'danger' ? 'danger' : 
                                                                ($a['type'] == 'warning' ? 'warning' : 'info')); 
                                                        ?>">
                                                            <?php echo ucfirst($a['type']); ?>
                                                        </span>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>
            </div>

<script>
// Wait for jQuery to be loaded
(function() {
    function initRiwayatPage() {
        if (typeof jQuery === 'undefined') {
            setTimeout(initRiwayatPage, 50);
            return;
        }
        
        var $ = jQuery;
        
        $(document).ready(function() {
            // Clear DataTable state
            if (typeof(Storage) !== "undefined") {
                Object.keys(localStorage).forEach(function(key) {
                    if (key.indexOf('DataTables_tableRiwayat') === 0 || key.indexOf('DataTables_') === 0) {
                        localStorage.removeItem(key);
                    }
                });
                Object.keys(sessionStorage).forEach(function(key) {
                    if (key.indexOf('DataTables_tableRiwayat') === 0 || key.indexOf('DataTables_') === 0) {
                        sessionStorage.removeItem(key);
                    }
                });
            }
            
            // Destroy existing instance
            if ($.fn.DataTable.isDataTable('#tableRiwayat')) {
                $('#tableRiwayat').DataTable().destroy();
            }
            
            $('#tableRiwayat').DataTable({
        dom: 'Bfrtip',
        buttons: [
            { extend: 'excel', text: '<i class="fas fa-file-excel"></i> Excel', className: 'btn btn-success btn-sm' },
            { extend: 'pdf', text: '<i class="fas fa-file-pdf"></i> PDF', className: 'btn btn-danger btn-sm' },
            { extend: 'print', text: '<i class="fas fa-print"></i> Print', className: 'btn btn-info btn-sm' }
        ],
                language: { url: 'https://cdn.datatables.net/plug-ins/1.11.5/i18n/id.json' },
                order: [[1, 'asc']],
                stateSave: false,
                stateDuration: -1,
                retrieve: false,
                drawCallback: function(settings) {
                    // Remove drawCallback to prevent infinite loop
                    // Order is already set in initial configuration
                }
            });
        });
    }
    
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initRiwayatPage);
    } else {
        initRiwayatPage();
    }
})();
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>


