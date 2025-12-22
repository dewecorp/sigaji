<?php
$page_title = 'Edit Multiple Guru';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';

$ids = $_GET['ids'] ?? '';

if (empty($ids)) {
    $_SESSION['error'] = 'Tidak ada data yang dipilih';
    header('Location: ' . BASE_URL . 'pages/guru/index.php');
    exit();
}

$id_array = explode(',', $ids);
$id_array = array_map('intval', $id_array);
$id_array = array_filter($id_array);

if (empty($id_array)) {
    $_SESSION['error'] = 'ID tidak valid';
    header('Location: ' . BASE_URL . 'pages/guru/index.php');
    exit();
}

// Get selected records
$placeholders = str_repeat('?,', count($id_array) - 1) . '?';
$sql = "SELECT * FROM guru WHERE id IN ($placeholders) ORDER BY nama_lengkap ASC";
$stmt = $conn->prepare($sql);
$stmt->bind_param(str_repeat('i', count($id_array)), ...$id_array);
$stmt->execute();
$result = $stmt->get_result();
$guru_list = $result->fetch_all(MYSQLI_ASSOC);
?>

<div class="main-content">
    <section class="section">
        <div class="section-header">
            <h1>Edit Multiple Guru</h1>
            <div class="section-header-breadcrumb">
                <div class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>pages/dashboard.php">Dashboard</a></div>
                <div class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>pages/guru/index.php">Data Guru</a></div>
                <div class="breadcrumb-item active">Edit Multiple</div>
            </div>
        </div>

        <div class="section-body">
            <div class="card">
                <div class="card-header">
                    <h4>Edit <?php echo count($guru_list); ?> Data Guru</h4>
                </div>
                <div class="card-body">
                    <form method="POST" action="save_multiple.php">
                        <input type="hidden" name="ids" value="<?php echo htmlspecialchars($ids); ?>">
                        
                        <div class="alert alert-info">
                            <strong>Catatan:</strong> Isi field yang ingin diubah untuk semua data terpilih. 
                            Biarkan kosong jika tidak ingin mengubah field tersebut.
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>TMT (Tahun Mulai Tugas)</label>
                                    <input type="number" class="form-control" name="tmt" id="tmt" min="1950" max="<?php echo date('Y'); ?>" placeholder="Kosongkan jika tidak diubah">
                                    <small class="text-muted">Masukkan tahun mulai tugas</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Jumlah Jam Mengajar</label>
                                    <input type="number" class="form-control" name="jumlah_jam_mengajar" min="0" placeholder="Kosongkan jika tidak diubah">
                                    <small class="text-muted">Jam per minggu</small>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Jabatan</label>
                                    <select class="form-control" name="jabatan">
                                        <option value="">-- Tidak Diubah --</option>
                                        <?php
                                        // Get all tunjangan for jabatan dropdown
                                        $sql_tunjangan = "SELECT DISTINCT nama_tunjangan FROM tunjangan WHERE nama_tunjangan IS NOT NULL AND nama_tunjangan != '' ORDER BY nama_tunjangan ASC";
                                        $result_tunjangan = $conn->query($sql_tunjangan);
                                        $tunjangan_list = $result_tunjangan ? $result_tunjangan->fetch_all(MYSQLI_ASSOC) : [];
                                        foreach ($tunjangan_list as $tunjangan):
                                        ?>
                                            <option value="<?php echo htmlspecialchars($tunjangan['nama_tunjangan']); ?>">
                                                <?php echo htmlspecialchars($tunjangan['nama_tunjangan']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label>Status Pegawai</label>
                            <select class="form-control" name="status_pegawai">
                                <option value="">-- Tidak Diubah --</option>
                                <option value="Honor">Honor</option>
                                <option value="PNS">PNS</option>
                                <option value="Kontrak">Kontrak</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Simpan Perubahan
                            </button>
                            <a href="<?php echo BASE_URL; ?>pages/guru/index.php" class="btn btn-secondary">
                                <i class="fas fa-times"></i> Batal
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </section>
</div>

<script>
// Auto calculate masa bakti when TMT changes
$(document).on('input keyup paste change blur', '#tmt', function() {
    calculateMasaBakti();
});

function calculateMasaBakti() {
    var tmt = $('#tmt').val();
    var tahunSekarang = new Date().getFullYear();
    
    if (tmt) {
        tmt = tmt.toString().replace(/\D/g, '').trim();
    }
    
    if (tmt && tmt.length >= 4) {
        var tahunTmt = parseInt(tmt);
        if (!isNaN(tahunTmt) && tahunTmt >= 1950 && tahunTmt <= tahunSekarang) {
            var masaBakti = tahunSekarang - tahunTmt;
            if (masaBakti >= 0) {
                // Masa bakti akan dihitung otomatis di server
            }
        }
    }
}
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>


