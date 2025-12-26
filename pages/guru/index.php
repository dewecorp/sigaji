<?php
$page_title = 'Data Guru';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';

// Get all teachers - sorted by nama_lengkap ascending (case insensitive)
$sql = "SELECT * FROM guru ORDER BY LOWER(TRIM(nama_lengkap)) ASC, nama_lengkap ASC";
$result = $conn->query($sql);
$guru = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
$total_guru = count($guru);

// Double check: Sort in PHP as well to ensure correct order (case insensitive)
usort($guru, function($a, $b) {
    return strcasecmp(trim($a['nama_lengkap']), trim($b['nama_lengkap']));
});

// Get all tunjangan for jabatan dropdown
$sql_tunjangan = "SELECT DISTINCT nama_tunjangan FROM tunjangan WHERE nama_tunjangan IS NOT NULL AND nama_tunjangan != '' ORDER BY nama_tunjangan ASC";
$result_tunjangan = $conn->query($sql_tunjangan);
$tunjangan_list = $result_tunjangan ? $result_tunjangan->fetch_all(MYSQLI_ASSOC) : [];
?>

            <div class="main-content">
                <section class="section">
                    <div class="section-header">
                        <h1>Data Guru</h1>
                        <div class="section-header-breadcrumb">
                            <span class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>pages/dashboard.php">Dashboard</a></span>
                            <span class="breadcrumb-item active">Data Guru</span>
                        </div>
                    </div>

                    <div class="section-body">
                        <div class="card">
                            <div class="card-header">
                                <h4>Data Guru</h4>
                            </div>
                            <div class="card-body">
                                <!-- Summary and Action Buttons -->
                                <div class="d-flex justify-content-between align-items-center flex-wrap mb-3" style="gap: 10px;">
                                    <div>
                                        <strong id="summaryText">Menampilkan: <span id="totalGuru"><?php echo $total_guru; ?></span> guru | Terpilih: <span id="selectedCount">0</span></strong>
                                    </div>
                                    <div class="d-flex flex-wrap" style="gap: 5px;">
                                        <button type="button" class="btn btn-info btn-sm" id="btnDownloadTemplate">
                                            <i class="fas fa-download"></i> Download Template
                                        </button>
                                        <button type="button" class="btn btn-danger btn-sm" id="btnBulkDelete" disabled>
                                            <i class="fas fa-trash"></i> Hapus Terpilih
                                        </button>
                                        <button type="button" class="btn btn-warning btn-sm" id="btnBulkEdit" disabled>
                                            <i class="fas fa-edit"></i> Edit Terpilih
                                        </button>
                                        <button type="button" class="btn btn-success btn-sm" data-toggle="modal" data-target="#modalImport">
                                            <i class="fas fa-file-upload"></i> Import Excel
                                        </button>
                                        <button type="button" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#modalTambah">
                                            <i class="fas fa-plus"></i> Tambah Guru
                                        </button>
                                    </div>
                                </div>
                                
                                <!-- Buttons container for DataTable export buttons -->
                                <div id="tableGuru_buttons" class="mb-3" style="display: flex; gap: 5px; flex-wrap: wrap;"></div>
                                <div class="table-responsive">
                                    <style>
                                        /* Ensure bulk actions panel can be shown */
                                        #bulkActionsContainer {
                                            transition: all 0.3s ease;
                                            margin-bottom: 15px;
                                        }
                                        #bulkActionsContainer.show {
                                            display: block !important;
                                            visibility: visible !important;
                                        }
                                        
                                        /* Style untuk tombol export */
                                        #tableGuru_buttons .dt-button {
                                            margin-right: 5px !important;
                                            margin-bottom: 5px !important;
                                        }
                                        
                                        /* Pastikan checkbox bisa diklik */
                                        #selectAll, .row-checkbox {
                                            cursor: pointer !important;
                                            pointer-events: auto !important;
                                        }
                                        
                                        /* Pastikan th yang berisi checkbox bisa diklik */
                                        #tableGuru thead th:has(#selectAll) {
                                            pointer-events: auto !important;
                                            cursor: pointer !important;
                                        }
                                        
                                        /* Style untuk tabel utama */
                                        #tableGuru {
                                            font-family: 'Nunito', sans-serif;
                                            font-size: 16px;
                                        }
                                        #tableGuru thead th {
                                            background-color: #667eea !important;
                                            color: white !important;
                                            font-family: 'Nunito', sans-serif;
                                            font-size: 16px;
                                            font-weight: 600;
                                            text-align: center;
                                            padding: 12px 10px;
                                            border: 1px solid #5568d3;
                                            white-space: nowrap;
                                            cursor: default !important;
                                            pointer-events: none;
                                        }
                                        /* Exception: allow checkbox column to be clickable */
                                        #tableGuru thead th:first-child {
                                            pointer-events: auto !important;
                                            cursor: pointer !important;
                                        }
                                        #tableGuru tbody td {
                                            font-family: 'Nunito', sans-serif;
                                            font-size: 16px;
                                            padding: 10px;
                                            vertical-align: middle;
                                            border: 1px solid #dee2e6;
                                        }
                                        #tableGuru tbody tr:hover {
                                            background-color: #f8f9fa;
                                        }
                                        /* Pastikan semua elemen dalam tabel menggunakan font yang sama */
                                        #tableGuru tbody td strong,
                                        #tableGuru tbody td span,
                                        #tableGuru tbody td .badge,
                                        #tableGuru tbody td button {
                                            font-family: 'Nunito', sans-serif;
                                            font-size: 16px;
                                        }
                                        
                                        /* Hide all sorting indicators and disable sorting */
                                        #tableGuru thead th.sorting,
                                        #tableGuru thead th.sorting_asc,
                                        #tableGuru thead th.sorting_desc {
                                            background-image: none !important;
                                        }
                                    </style>
                                    <table class="table table-striped table-bordered" id="tableGuru">
                                        <thead>
                                            <tr>
                                                <th width="40" style="text-align: center;">
                                                    <input type="checkbox" id="selectAll" title="Pilih Semua">
                                                </th>
                                                <th width="50" style="text-align: center;">No</th>
                                                <th style="min-width: 200px;">Nama Lengkap</th>
                                                <th width="80" style="text-align: center;">TMT</th>
                                                <th width="100" style="text-align: center;">Masa Bakti</th>
                                                <th width="100" style="text-align: center;">Jumlah Jam</th>
                                                <th style="min-width: 150px;">Jabatan</th>
                                                <th width="100" style="text-align: center;">Status</th>
                                                <th width="120" style="text-align: center;">Aksi</th>
                                            </tr>
                                        </thead>
                                        <tbody data-order="fixed">
                                            <?php $no = 1; foreach ($guru as $g): ?>
                                                <tr data-sort-name="<?php echo strtolower(trim($g['nama_lengkap'])); ?>">
                                                    <td>
                                                        <input type="checkbox" class="row-checkbox" value="<?php echo $g['id']; ?>" data-id="<?php echo $g['id']; ?>">
                                                    </td>
                                                    <td style="text-align: center;"><?php echo $no++; ?></td>
                                                    <td><strong><?php echo htmlspecialchars($g['nama_lengkap']); ?></strong></td>
                                                    <td style="text-align: center;"><?php echo $g['tmt'] ? $g['tmt'] : '-'; ?></td>
                                                    <td style="text-align: center;"><?php 
                                                        if ($g['tmt'] !== null) {
                                                            $tahun_sekarang = (int)date('Y');
                                                            $tahun_tmt = (int)$g['tmt'];
                                                            $masa_bakti = $tahun_sekarang - $tahun_tmt;
                                                            if ($masa_bakti >= 0) {
                                                                echo $masa_bakti . ' tahun';
                                                            } else {
                                                                echo '-';
                                                            }
                                                        } else {
                                                            echo '-';
                                                        }
                                                    ?></td>
                                                    <td style="text-align: center;"><?php echo $g['jumlah_jam_mengajar'] ?? 0; ?> jam</td>
                                                    <td class="jabatan-cell">
                                                        <?php 
                                                        $jabatan_display = '-';
                                                        if (!empty($g['jabatan'])) {
                                                            try {
                                                                $jabatan_array = json_decode($g['jabatan'], true);
                                                                if (is_array($jabatan_array) && !empty($jabatan_array)) {
                                                                    $jabatan_display = implode(', ', array_map('htmlspecialchars', $jabatan_array));
                                                                } else {
                                                                    // Fallback: if not JSON, treat as single value
                                                                    $jabatan_display = htmlspecialchars($g['jabatan']);
                                                                }
                                                            } catch (Exception $e) {
                                                                // If JSON decode fails, treat as single value
                                                                $jabatan_display = htmlspecialchars($g['jabatan']);
                                                            }
                                                        }
                                                        echo $jabatan_display;
                                                        ?>
                                                    </td>
                                                    <td style="text-align: center;">
                                                        <span class="badge badge-<?php 
                                                            echo $g['status_pegawai'] == 'PNS' ? 'success' : 
                                                                ($g['status_pegawai'] == 'Honor' ? 'warning' : 'info'); 
                                                        ?>">
                                                            <?php echo htmlspecialchars($g['status_pegawai']); ?>
                                                        </span>
                                                    </td>
                                                    <td style="text-align: center;">
                                                        <button class="btn btn-sm btn-info" onclick="editGuru(<?php echo $g['id']; ?>)" data-toggle="tooltip" title="Edit">
                                                            <i class="fas fa-edit"></i>
                                                        </button>
                                                        <button class="btn btn-sm btn-danger" onclick="confirmDelete('<?php echo BASE_URL; ?>pages/guru/delete.php?id=<?php echo $g['id']; ?>')" data-toggle="tooltip" title="Hapus">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
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

            <!-- Modal Tambah/Edit -->
            <div class="modal fade" id="modalTambah" tabindex="-1" role="dialog">
                <div class="modal-dialog modal-lg" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="modalTitle">Tambah Guru</h5>
                            <button type="button" class="close" data-dismiss="modal">
                                <span>&times;</span>
                            </button>
                        </div>
                        <form id="formGuru" method="POST" action="save.php">
                            <input type="hidden" name="id" id="guru_id">
                            <div class="modal-body">
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <label>Nama Lengkap <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" name="nama_lengkap" id="nama_lengkap" required>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>TMT (Tahun Mulai Tugas)</label>
                                            <input type="number" class="form-control" name="tmt" id="tmt" min="1950" max="<?php echo date('Y'); ?>" placeholder="Contoh: 2020">
                                            <small class="text-muted">Masukkan tahun mulai tugas. Masa bakti akan dihitung otomatis di tabel.</small>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Jumlah Jam Mengajar</label>
                                            <input type="number" class="form-control" name="jumlah_jam_mengajar" id="jumlah_jam_mengajar" min="0" value="0">
                                            <small class="text-muted">Jam per minggu</small>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Jabatan</label>
                                            <div class="dropdown-checkbox-wrapper" style="position: relative;">
                                                <button type="button" class="btn btn-secondary dropdown-toggle w-100 text-left" id="jabatanDropdownBtn" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" style="text-align: left;">
                                                    <span id="jabatanSelectedText">Pilih Jabatan</span>
                                                    <span class="float-right"><i class="fas fa-chevron-down"></i></span>
                                                </button>
                                                <div class="dropdown-menu w-100" id="jabatanDropdownMenu" style="max-height: 300px; overflow-y: auto;">
                                                    <!-- Search input -->
                                                    <div class="px-3 py-2 border-bottom">
                                                        <input type="text" class="form-control form-control-sm" id="jabatanSearchInput" placeholder="Cari jabatan..." autocomplete="off">
                                                    </div>
                                                    <div class="px-3 py-2 border-bottom">
                                                        <div class="form-check">
                                                            <input class="form-check-input" type="checkbox" id="selectAllJabatan">
                                                            <label class="form-check-label font-weight-bold" for="selectAllJabatan">
                                                                Pilih Semua
                                                            </label>
                                                        </div>
                                                    </div>
                                                    <div id="jabatanListContainer">
                                                        <?php foreach ($tunjangan_list as $tunjangan): ?>
                                                        <div class="dropdown-item jabatan-item" data-name="<?php echo strtolower(htmlspecialchars($tunjangan['nama_tunjangan'])); ?>">
                                                            <div class="form-check">
                                                                <input class="form-check-input jabatan-checkbox" type="checkbox" name="jabatan[]" value="<?php echo htmlspecialchars($tunjangan['nama_tunjangan']); ?>" id="jabatan_<?php echo $tunjangan['nama_tunjangan']; ?>">
                                                                <label class="form-check-label" for="jabatan_<?php echo $tunjangan['nama_tunjangan']; ?>">
                                                                    <?php echo htmlspecialchars($tunjangan['nama_tunjangan']); ?>
                                                                </label>
                                                            </div>
                                                        </div>
                                                        <?php endforeach; ?>
                                                    </div>
                                                    <div id="jabatanNoResults" class="px-3 py-2 text-muted text-center" style="display: none;">
                                                        <small>Tidak ada jabatan yang ditemukan</small>
                                                    </div>
                                                </div>
                                            </div>
                                            <small class="text-muted">Pilih satu atau lebih jabatan</small>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Status Pegawai</label>
                                            <select class="form-control" name="status_pegawai" id="status_pegawai">
                                                <option value="Honor">Honor</option>
                                                <option value="PNS">PNS</option>
                                                <option value="Kontrak">Kontrak</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                                <button type="submit" class="btn btn-primary">Simpan</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Modal Edit Multiple -->
            <div class="modal fade" id="modalEditMultiple" tabindex="-1" role="dialog">
                <div class="modal-dialog modal-xl" role="document">
                    <div class="modal-content">
                        <div class="modal-header" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
                            <h5 class="modal-title">Edit Multiple Guru - <span id="editMultipleCount">0</span> guru</h5>
                            <button type="button" class="close" data-dismiss="modal" style="color: white; opacity: 0.8;">
                                <span>&times;</span>
                            </button>
                        </div>
                        <form id="formEditMultiple" method="POST" action="save_multiple.php">
                            <input type="hidden" name="ids" id="editMultipleIds">
                            <div class="modal-body" style="max-height: 70vh; overflow-y: auto;">
                                <style>
                                    #tableEditMultiple {
                                        font-family: 'Nunito', sans-serif;
                                        font-size: 16px;
                                    }
                                    #tableEditMultiple thead th {
                                        background-color: #667eea !important;
                                        color: white !important;
                                        font-family: 'Nunito', sans-serif;
                                        font-size: 16px;
                                        font-weight: 600;
                                        text-align: center;
                                        padding: 12px 8px;
                                        border: 1px solid #5568d3;
                                        white-space: nowrap;
                                    }
                                    #tableEditMultiple tbody td {
                                        font-family: 'Nunito', sans-serif;
                                        font-size: 16px;
                                        padding: 10px 8px;
                                        vertical-align: middle;
                                        border: 1px solid #dee2e6;
                                    }
                                    #tableEditMultiple tbody tr:hover {
                                        background-color: #f8f9fa;
                                    }
                                    /* Pastikan semua elemen dalam tabel edit multiple menggunakan font yang sama */
                                    #tableEditMultiple tbody td input,
                                    #tableEditMultiple tbody td select {
                                        font-family: 'Nunito', sans-serif;
                                    }
                                    #tableEditMultiple .form-control-sm {
                                        font-family: 'Nunito', sans-serif;
                                        font-size: 15px;
                                        padding: 6px 10px;
                                        border: 1px solid #ced4da;
                                        width: 100%;
                                    }
                                    #tableEditMultiple .form-control-sm:focus {
                                        border-color: #667eea;
                                        box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
                                    }
                                    #tableEditMultiple tbody td {
                                        min-width: 100px;
                                    }
                                    #tableEditMultiple th:nth-child(1) { width: 20%; } /* Nama Lengkap */
                                    #tableEditMultiple th:nth-child(2) { width: 10%; } /* TMT */
                                    #tableEditMultiple th:nth-child(3) { width: 12%; } /* Masa Bakti */
                                    #tableEditMultiple th:nth-child(4) { width: 10%; } /* Jumlah Jam */
                                    #tableEditMultiple th:nth-child(5) { width: 20%; } /* Jabatan */
                                    #tableEditMultiple th:nth-child(6) { width: 15%; } /* Status */
                                </style>
                                <div class="table-responsive">
                                    <table class="table table-bordered table-hover" id="tableEditMultiple">
                                        <thead>
                                            <tr>
                                                <th><i class="fas fa-user"></i> Nama Lengkap</th>
                                                <th><i class="fas fa-calendar"></i> TMT</th>
                                                <th><i class="fas fa-clock"></i> Masa Bakti</th>
                                                <th><i class="fas fa-hourglass-half"></i> Jumlah Jam</th>
                                                <th><i class="fas fa-briefcase"></i> Jabatan</th>
                                                <th><i class="fas fa-info-circle"></i> Status</th>
                                            </tr>
                                        </thead>
                                        <tbody id="editMultipleTableBody">
                                            <!-- Data akan diisi via JavaScript -->
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-dismiss="modal">
                                    <i class="fas fa-times"></i> Batal
                                </button>
                                <button type="submit" class="btn btn-success">
                                    <i class="fas fa-save"></i> Simpan Semua
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Modal Import Excel -->
            <div class="modal fade" id="modalImport" tabindex="-1" role="dialog">
                <div class="modal-dialog" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Import Data Guru dari Excel</h5>
                            <button type="button" class="close" data-dismiss="modal">
                                <span>&times;</span>
                            </button>
                        </div>
                        <form method="POST" action="import.php" enctype="multipart/form-data">
                            <div class="modal-body">
                                <div class="form-group">
                                    <label>File Excel (.xlsx, .xls)</label>
                                    <input type="file" class="form-control" name="excel_file" accept=".xlsx,.xls,.csv" required>
                                    <small class="text-muted">
                                        Format: Nama Lengkap | TMT | Jumlah Jam Mengajar | Jabatan | Status Pegawai<br>
                                        Baris pertama adalah header, data mulai dari baris kedua<br>
                                        Format file: Excel (.xlsx, .xls) atau CSV (.csv)
                                    </small>
                                </div>
                                <div class="alert alert-info">
                                    <strong>Catatan:</strong>
                                    <ul class="mb-0">
                                        <li>File harus berformat Excel (.xlsx atau .xls)</li>
                                        <li>Kolom wajib: Nama Lengkap</li>
                                        <li>Kolom opsional: TMT, Jumlah Jam Mengajar, Jabatan, Status Pegawai</li>
                                        <li>Masa Bakti akan dihitung otomatis dari TMT di tabel</li>
                                    </ul>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                                <button type="submit" class="btn btn-primary">Import</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

<script>
// Store selected IDs across pages - MUST be defined at global scope
var selectedIds = new Set();

// Wait for jQuery to be loaded - use multiple methods
(function() {
    var retryCount = 0;
    var maxRetries = 20; // Maximum 20 retries (2 seconds)
    
    function initGuruPage() {
        // Check if jQuery is loaded
        if (typeof jQuery === 'undefined' || typeof $ === 'undefined') {
            retryCount++;
            if (retryCount < maxRetries) {
                console.error('jQuery belum ter-load! Retry ' + retryCount);
                setTimeout(initGuruPage, 100);
            } else {
                console.error('jQuery failed to load after ' + maxRetries + ' retries');
            }
            return;
        }
        
        // Reset retry count on success
        retryCount = 0;
        
        // Now jQuery is ready
        $(document).ready(function() {
    // Function to edit guru - must be global for onclick
    window.editGuru = function(id) {
        $.ajax({
            url: 'get.php?id=' + id,
            type: 'GET',
            dataType: 'json',
            success: function(data) {
                if (data.error) {
                    Swal.fire('Error', data.error, 'error');
                    return;
                }
                $('#guru_id').val(data.id || '');
                $('#nama_lengkap').val(data.nama_lengkap || '');
                $('#tmt').val(data.tmt || '');
                $('#jumlah_jam_mengajar').val(data.jumlah_jam_mengajar || 0);
                $('#status_pegawai').val(data.status_pegawai || 'Honor');
                
                // Handle multiple jabatan
                $('.jabatan-checkbox').prop('checked', false);
                $('#selectAllJabatan').prop('checked', false);
                
                var jabatanArray = [];
                if (data.jabatan) {
                    // Try to parse as JSON first, if fails treat as single value
                    try {
                        jabatanArray = JSON.parse(data.jabatan);
                        if (!Array.isArray(jabatanArray)) {
                            jabatanArray = [data.jabatan];
                        }
                    } catch(e) {
                        // If not JSON, treat as single value or comma-separated
                        if (data.jabatan.includes(',')) {
                            jabatanArray = data.jabatan.split(',').map(function(j) { return j.trim(); });
                        } else {
                            jabatanArray = [data.jabatan];
                        }
                    }
                }
                
                jabatanArray.forEach(function(jabatan) {
                    $('.jabatan-checkbox[value="' + jabatan + '"]').prop('checked', true);
                });
                
                // Update select all checkbox
                var total = $('.jabatan-checkbox').length;
                var checked = $('.jabatan-checkbox:checked').length;
                $('#selectAllJabatan').prop('checked', total > 0 && checked === total);
                
                if (typeof updateJabatanSelectedText === 'function') {
                    updateJabatanSelectedText();
                }
                
                $('#modalTitle').text('Edit Guru');
                $('#modalTambah').modal('show');
            },
            error: function() {
                Swal.fire('Error', 'Gagal memuat data guru', 'error');
            }
        });
    };


    // Also calculate when modal is shown (in case TMT already has value)
    $('#modalTambah').on('shown.bs.modal', function () {
        // Small delay to ensure form fields are ready
        setTimeout(function() {
            calculateMasaBakti();
        }, 100);
    });

    // Calculate when button "Tambah Guru" is clicked
    $(document).on('click', '[data-target="#modalTambah"]', function() {
        setTimeout(function() {
            calculateMasaBakti();
        }, 300);
    });

    $('#modalTambah').on('hidden.bs.modal', function () {
        $('#formGuru')[0].reset();
        $('#guru_id').val('');
        $('.jabatan-checkbox').prop('checked', false);
        $('#selectAllJabatan').prop('checked', false);
        if (typeof updateJabatanSelectedText === 'function') {
            updateJabatanSelectedText();
        }
        $('#modalTitle').text('Tambah Guru');
    });
    
    // Jabatan dropdown functionality
    window.updateJabatanSelectedText = function() {
        var selected = $('.jabatan-checkbox:checked');
        var total = $('.jabatan-checkbox').length;
        var selectedCount = selected.length;
        
        if (selectedCount === 0) {
            $('#jabatanSelectedText').text('Pilih Jabatan');
        } else if (selectedCount === total) {
            $('#jabatanSelectedText').text('Semua Jabatan (' + total + ')');
        } else {
            var selectedNames = [];
            selected.each(function() {
                var label = $(this).closest('.jabatan-item').find('label').text().trim();
                if (label) {
                    selectedNames.push(label);
                }
            });
            
            if (selectedNames.length <= 3) {
                $('#jabatanSelectedText').text(selectedNames.join(', '));
            } else {
                var displayNames = selectedNames.slice(0, 3).join(', ');
                $('#jabatanSelectedText').text(displayNames + ' dan ' + (selectedCount - 3) + ' lainnya');
            }
        }
    };
    
    // Select all jabatan checkbox
    $(document).on('change', '#selectAllJabatan', function() {
        var isChecked = $(this).prop('checked');
        $('.jabatan-item:visible .jabatan-checkbox').prop('checked', isChecked);
        updateJabatanSelectedText();
    });
    
    // Individual jabatan checkbox
    $(document).on('change', '.jabatan-checkbox', function() {
        var visibleCheckboxes = $('.jabatan-item:visible .jabatan-checkbox');
        var total = visibleCheckboxes.length;
        var checked = visibleCheckboxes.filter(':checked').length;
        $('#selectAllJabatan').prop('checked', total > 0 && checked === total);
        updateJabatanSelectedText();
    });
    
    // Prevent dropdown from closing when clicking inside
    $(document).on('click', '#jabatanDropdownMenu', function(e) {
        e.stopPropagation();
    });
    
    // Search functionality for jabatan dropdown
    $('#jabatanSearchInput').on('input', function() {
        var searchTerm = $(this).val().toLowerCase().trim();
        var visibleCount = 0;
        var visibleCheckedCount = 0;
        
        if (searchTerm === '') {
            $('.jabatan-item').show();
            $('#jabatanNoResults').hide();
            var total = $('.jabatan-checkbox').length;
            var checked = $('.jabatan-checkbox:checked').length;
            $('#selectAllJabatan').prop('checked', total > 0 && checked === total);
        } else {
            $('.jabatan-item').each(function() {
                var jabatanName = $(this).data('name');
                if (jabatanName.indexOf(searchTerm) !== -1) {
                    $(this).show();
                    visibleCount++;
                    if ($(this).find('.jabatan-checkbox').is(':checked')) {
                        visibleCheckedCount++;
                    }
                } else {
                    $(this).hide();
                }
            });
            
            $('#selectAllJabatan').prop('checked', visibleCount > 0 && visibleCheckedCount === visibleCount);
            
            if (visibleCount === 0) {
                $('#jabatanNoResults').show();
            } else {
                $('#jabatanNoResults').hide();
            }
            
            if (typeof updateJabatanSelectedText === 'function') {
                updateJabatanSelectedText();
            }
        }
    });
    
    // Clear search when dropdown is closed
    $('#jabatanDropdownBtn').on('hidden.bs.dropdown', function() {
        $('#jabatanSearchInput').val('');
        $('.jabatan-item').show();
        $('#jabatanNoResults').hide();
        var total = $('.jabatan-checkbox').length;
        var checked = $('.jabatan-checkbox:checked').length;
        $('#selectAllJabatan').prop('checked', total > 0 && checked === total);
        if (typeof updateJabatanSelectedText === 'function') {
            updateJabatanSelectedText();
        }
    });
    
    // Prevent search input from closing dropdown
    $('#jabatanSearchInput').on('click', function(e) {
        e.stopPropagation();
    });
    
    // Initialize on modal show
    $('#modalTambah').on('shown.bs.modal', function() {
        if (typeof updateJabatanSelectedText === 'function') {
            updateJabatanSelectedText();
        }
    });
    // Wait a bit to ensure main.js has finished (if it auto-initialized)
    setTimeout(function() {
        // Aggressively clear ALL DataTable state from localStorage and sessionStorage
        if (typeof(Storage) !== "undefined") {
            // Clear all DataTable related keys (not just tableGuru)
            Object.keys(localStorage).forEach(function(key) {
                if (key.indexOf('DataTables_') === 0) {
                    localStorage.removeItem(key);
                }
            });
            Object.keys(sessionStorage).forEach(function(key) {
                if (key.indexOf('DataTables_') === 0) {
                    sessionStorage.removeItem(key);
                }
            });
        }
        
        // Destroy existing DataTable instance if it exists (from main.js auto-init)
        if ($.fn.DataTable.isDataTable('#tableGuru')) {
            $('#tableGuru').DataTable().destroy();
        }
    
    // Check if DataTables Buttons is loaded
    if (typeof $.fn.dataTable.Buttons === 'undefined') {
        console.error('DataTables Buttons library tidak ditemukan!');
        alert('DataTables Buttons library tidak ditemukan! Pastikan library ter-load dengan benar.');
    }
    
    // Initialize DataTable WITHOUT any sorting capability
    var table = $('#tableGuru').DataTable({
        dom: '<"row"<"col-sm-12 col-md-6"B><"col-sm-12 col-md-6"f>>rtip',
        paging: true,
        searching: true,
        ordering: false, // CRITICAL: Completely disable ordering
        info: true,
        pageLength: 10,
        stateSave: false,
        stateDuration: -1,
        retrieve: false,
        autoWidth: false,
        buttons: {
            dom: {
                button: {
                    className: 'btn btn-sm'
                }
            },
            buttons: [
                { 
                    extend: 'excel', 
                    text: '<i class="fas fa-file-excel"></i> Excel', 
                    className: 'btn btn-success btn-sm',
                    exportOptions: {
                        columns: [1, 2, 3, 4, 5, 6, 7, 8], // Exclude checkbox (0) and action (9)
                        format: {
                            body: function(data, row, column, node) {
                                return data ? data.replace(/<[^>]*>/g, '').trim() : '';
                            }
                        }
                    },
                    filename: 'Data_Guru_' + new Date().toISOString().split('T')[0],
                    title: 'Data Guru'
                },
                { 
                    extend: 'pdf', 
                    text: '<i class="fas fa-file-pdf"></i> PDF', 
                    className: 'btn btn-danger btn-sm',
                    exportOptions: {
                        columns: [1, 2, 3, 4, 5, 6, 7, 8] // Exclude checkbox (0) and action (9)
                    },
                    filename: 'Data_Guru_' + new Date().toISOString().split('T')[0],
                    title: 'Data Guru',
                    orientation: 'landscape',
                    pageSize: 'A4',
                    customize: function(doc) {
                        doc.defaultStyle.fontSize = 9;
                        doc.styles.tableHeader.fontSize = 10;
                    }
                },
                { 
                    extend: 'print', 
                    text: '<i class="fas fa-print"></i> Print', 
                    className: 'btn btn-info btn-sm',
                    exportOptions: {
                        columns: [1, 2, 3, 4, 5, 6, 7, 8] // Exclude checkbox (0) and action (9)
                    }
                }
            ]
        },
        language: {
            url: 'https://cdn.datatables.net/plug-ins/1.11.5/i18n/id.json'
        },
        columnDefs: [
            {
                targets: '_all',
                orderable: false, // Disable sorting on ALL columns
                searchable: true
            },
            {
                targets: 0, // No column
                searchable: false
            }
        ],
        // Recalculate row numbers after filtering or pagination
        drawCallback: function(settings) {
            var api = this.api();
            var pageInfo = api.page.info();
            api.column(1, {search: 'applied'}).nodes().each(function(cell, i) {
                cell.innerHTML = pageInfo.start + i + 1;
            });
            // Restore checkbox states after pagination
            $('.row-checkbox').each(function() {
                var id = $(this).val();
                $(this).prop('checked', selectedIds.has(id));
            });
            // Update select all checkbox state for current page
            var totalCheckboxes = $('.row-checkbox:visible').length;
            var checkedCheckboxes = $('.row-checkbox:visible:checked').length;
            $('#selectAll').prop('checked', totalCheckboxes > 0 && totalCheckboxes === checkedCheckboxes);
            // Update summary
            updateBulkActions();
        }
    });
    
    // Move buttons to custom container
    table.buttons().container().appendTo('#tableGuru_buttons');
    
    // Completely disable all sorting interactions - BUT allow checkbox to work
    $('#tableGuru thead th').removeClass('sorting sorting_asc sorting_desc');
    $('#tableGuru thead th').not(':has(#selectAll)').css({
        'cursor': 'default',
        'pointer-events': 'none',
        'background-image': 'none !important'
    });
    // Ensure checkbox column is clickable
    $('#tableGuru thead th:has(#selectAll)').css({
        'cursor': 'pointer',
        'pointer-events': 'auto'
    });
    
    // Remove all click handlers except for checkbox
    $('#tableGuru thead th').not(':has(#selectAll)').off('click.sort');
    
    // Lock the table order - prevent any changes
    table.on('order.dt', function() {
        return false; // Prevent any order changes
    });
    
    // Checkbox functionality - SIMPLE AND DIRECT
    // Make it accessible globally
    window.updateBulkActions = function() {
        var selected = selectedIds.size;
        var totalRows = 0;
        
        // Get total rows from DataTable if available
        if (typeof table !== 'undefined' && table) {
            totalRows = table.rows({search: 'applied'}).count();
        } else {
            // Fallback: count from DOM
            totalRows = $('.row-checkbox').length;
        }
        
        // Update summary text
        $('#selectedCount').text(selected);
        $('#totalGuru').text(totalRows);
        
        // Update button states - FORCE update dengan multiple methods
        var btnEdit = $('#btnBulkEdit');
        var btnDelete = $('#btnBulkDelete');
        
        if (btnEdit.length === 0 || btnDelete.length === 0) {
            console.error('Tombol edit/hapus tidak ditemukan!');
            return;
        }
        
        if (selected > 0) {
            // Force enable dengan multiple methods
            btnEdit.prop('disabled', false)
                   .removeClass('disabled')
                   .removeAttr('disabled')
                   .css({'opacity': '1', 'pointer-events': 'auto'})
                   .show();
            btnDelete.prop('disabled', false)
                     .removeClass('disabled')
                     .removeAttr('disabled')
                     .css({'opacity': '1', 'pointer-events': 'auto'})
                     .show();
            console.log('Buttons enabled, selected:', selected);
        } else {
            // Disable tapi tetap visible
            btnEdit.prop('disabled', true)
                   .addClass('disabled')
                   .css({'opacity': '0.6', 'pointer-events': 'none'})
                   .show();
            btnDelete.prop('disabled', true)
                     .addClass('disabled')
                     .css({'opacity': '0.6', 'pointer-events': 'none'})
                     .show();
            console.log('Buttons disabled');
        }
    };
    
    // Checkbox functionality - SIMPLE AND DIRECT approach
    // Use change event untuk memastikan state checkbox sudah ter-update
    $(document).off('change', '#selectAll').on('change', '#selectAll', function(e) {
        e.stopPropagation();
        var checkbox = $(this);
        // Gunakan is(':checked') untuk mendapatkan state setelah change
        var isChecked = checkbox.is(':checked');
        
        console.log('Select All changed:', isChecked);
        
        // Clear all selected IDs first if unchecking
        if (!isChecked) {
            selectedIds.clear();
            console.log('Cleared all selected IDs');
        }
        
        // Handle all rows in DataTable (including filtered and all pages)
        table.rows({search: 'applied'}).every(function() {
            var row = this.node();
            var rowCheckbox = $(row).find('.row-checkbox');
            var id = rowCheckbox.val();
            if (id) {
                // Force update checkbox state
                rowCheckbox.prop('checked', isChecked);
                if (isChecked) {
                    selectedIds.add(id);
                } else {
                    selectedIds.delete(id);
                }
            }
        });
        
        // Also handle all visible checkboxes on current page (backup method)
        $('.row-checkbox:visible').each(function() {
            var id = $(this).val();
            if (id) {
                $(this).prop('checked', isChecked);
                if (isChecked) {
                    selectedIds.add(id);
                } else {
                    selectedIds.delete(id);
                }
            }
        });
        
        // Also handle all checkboxes in DOM (including hidden ones from other pages)
        $('.row-checkbox').each(function() {
            var id = $(this).val();
            if (id) {
                $(this).prop('checked', isChecked);
                if (isChecked) {
                    selectedIds.add(id);
                } else {
                    selectedIds.delete(id);
                }
            }
        });
        
        console.log('Selected IDs after select all:', Array.from(selectedIds), 'Size:', selectedIds.size);
        updateBulkActions();
    });
    
    // Handle individual row checkboxes
    $(document).off('click change', '.row-checkbox').on('click change', '.row-checkbox', function(e) {
        e.stopPropagation();
        var checkbox = $(this);
        var id = checkbox.val();
        // Gunakan is(':checked') setelah event, bukan prop('checked') karena prop mungkin belum update
        var isChecked = checkbox.is(':checked');
        
        console.log('Row checkbox changed:', id, isChecked);
        
        if (id) {
            if (isChecked) {
                selectedIds.add(id);
            } else {
                selectedIds.delete(id);
            }
        }
        
        console.log('Selected IDs:', Array.from(selectedIds));
        updateBulkActions();
        
        // Update select all checkbox state for current page
        // Gunakan setTimeout untuk memastikan semua checkbox sudah ter-update
        setTimeout(function() {
            var totalCheckboxes = $('.row-checkbox:visible').length;
            var checkedCheckboxes = $('.row-checkbox:visible:checked').length;
            $('#selectAll').prop('checked', totalCheckboxes > 0 && totalCheckboxes === checkedCheckboxes);
        }, 10);
    });
    
    // Clear selection - use event delegation
    $(document).on('click', '#btnClearSelection', function(e) {
        e.preventDefault();
        e.stopPropagation();
        selectedIds.clear();
        $('.row-checkbox').prop('checked', false);
        $('#selectAll').prop('checked', false);
        updateBulkActions();
    });
    
    // Download template Excel
    $(document).on('click', '#btnDownloadTemplate', function(e) {
        e.preventDefault();
        // Download template Excel dari server
        window.location.href = 'download_template.php';
    });
    
    // Bulk delete - use event delegation
    $(document).on('click', '#btnBulkDelete', function(e) {
        e.preventDefault();
        e.stopPropagation();
        var idsArray = Array.from(selectedIds);
        console.log('Bulk delete clicked, selected:', idsArray);
        
        if (idsArray.length > 0) {
            Swal.fire({
                title: 'Konfirmasi Hapus',
                text: 'Apakah Anda yakin ingin menghapus ' + idsArray.length + ' data yang dipilih?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Ya, Hapus!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = 'delete_multiple.php?ids=' + idsArray.join(',');
                }
            });
        } else {
            Swal.fire({
                icon: 'warning',
                title: 'Tidak ada data dipilih',
                text: 'Silakan pilih data yang ingin dihapus terlebih dahulu.'
            });
        }
    });
    
    // Bulk edit - use event delegation
    $(document).on('click', '#btnBulkEdit', function(e) {
        e.preventDefault();
        e.stopPropagation();
        var idsArray = Array.from(selectedIds);
        console.log('Bulk edit clicked, selected:', idsArray);
        
        if (idsArray.length > 0) {
            // Set IDs to hidden input
            $('#editMultipleIds').val(idsArray.join(','));
            $('#editMultipleCount').text(idsArray.length);
            
            // Show loading
            Swal.fire({
                title: 'Memuat data...',
                text: 'Mohon tunggu',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });
            
            // Load data via AJAX
            $.ajax({
                url: 'get_multiple.php?ids=' + idsArray.join(','),
                type: 'GET',
                dataType: 'json',
                success: function(response) {
                    Swal.close();
                    if (response.success && response.data) {
                        // Populate table
                        var tbody = $('#editMultipleTableBody');
                        tbody.empty();
                        
                        response.data.forEach(function(guru) {
                            var tahunSekarang = new Date().getFullYear();
                            var masaBakti = guru.tmt ? (tahunSekarang - parseInt(guru.tmt)) + ' tahun' : '-';
                            
                            // Escape HTML untuk keamanan
                            function escapeHtml(text) {
                                if (!text) return '';
                                var map = {
                                    '&': '&amp;',
                                    '<': '&lt;',
                                    '>': '&gt;',
                                    '"': '&quot;',
                                    "'": '&#039;'
                                };
                                return text.toString().replace(/[&<>"']/g, function(m) { return map[m]; });
                            }
                            
                            var row = $('<tr>').attr('data-id', guru.id);
                            row.append($('<td>').html('<input type="text" class="form-control form-control-sm" name="nama_lengkap[' + guru.id + ']" value="' + escapeHtml(guru.nama_lengkap || '') + '">'));
                            row.append($('<td>').html('<input type="number" class="form-control form-control-sm tmt-input" name="tmt[' + guru.id + ']" value="' + (guru.tmt || '') + '" min="1950" max="' + tahunSekarang + '" data-id="' + guru.id + '">'));
                            row.append($('<td>').html('<input type="text" class="form-control form-control-sm masa-bakti-display" value="' + escapeHtml(masaBakti) + '" readonly>'));
                            row.append($('<td>').html('<input type="number" class="form-control form-control-sm" name="jumlah_jam_mengajar[' + guru.id + ']" value="' + (guru.jumlah_jam_mengajar || 0) + '" min="0">'));
                            
                            // Create jabatan multi-select (simplified as text input for edit multiple)
                            // Parse jabatan from JSON or comma-separated
                            var jabatanValue = '';
                            if (guru.jabatan) {
                                try {
                                    var jabatanArray = JSON.parse(guru.jabatan);
                                    if (Array.isArray(jabatanArray)) {
                                        jabatanValue = jabatanArray.join(', ');
                                    } else {
                                        jabatanValue = guru.jabatan;
                                    }
                                } catch(e) {
                                    jabatanValue = guru.jabatan;
                                }
                            }
                            row.append($('<td>').html('<input type="text" class="form-control form-control-sm" name="jabatan[' + guru.id + ']" value="' + escapeHtml(jabatanValue) + '" placeholder="Pisahkan dengan koma untuk multiple jabatan">'));
                            
                            var statusSelect = $('<select>').addClass('form-control form-control-sm').attr('name', 'status_pegawai[' + guru.id + ']');
                            statusSelect.append($('<option>').attr('value', 'Honor').text('Honor').prop('selected', guru.status_pegawai === 'Honor'));
                            statusSelect.append($('<option>').attr('value', 'PNS').text('PNS').prop('selected', guru.status_pegawai === 'PNS'));
                            statusSelect.append($('<option>').attr('value', 'Kontrak').text('Kontrak').prop('selected', guru.status_pegawai === 'Kontrak'));
                            row.append($('<td>').append(statusSelect));
                            
                            tbody.append(row);
                        });
                        
                        // Show modal
                        $('#modalEditMultiple').modal('show');
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: response.error || 'Gagal memuat data'
                        });
                    }
                },
                error: function() {
                    Swal.close();
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Gagal memuat data guru'
                    });
                }
            });
        } else {
            Swal.fire({
                icon: 'warning',
                title: 'Tidak ada data dipilih',
                text: 'Silakan pilih data yang ingin diedit terlebih dahulu.'
            });
        }
    });
    
    // Auto calculate masa bakti when TMT changes in edit multiple modal
    $(document).on('input change', '.tmt-input', function() {
        var tmt = $(this).val();
        var row = $(this).closest('tr');
        var masaBaktiDisplay = row.find('.masa-bakti-display');
        var tahunSekarang = new Date().getFullYear();
        
        if (tmt && tmt.length >= 4) {
            var tahunTmt = parseInt(tmt);
            if (!isNaN(tahunTmt) && tahunTmt >= 1950 && tahunTmt <= tahunSekarang) {
                var masaBakti = tahunSekarang - tahunTmt;
                if (masaBakti >= 0) {
                    masaBaktiDisplay.val(masaBakti + ' tahun');
                } else {
                    masaBaktiDisplay.val('-');
                }
            } else {
                masaBaktiDisplay.val('-');
            }
        } else {
            masaBaktiDisplay.val('-');
        }
    });
    
    // Reset form when modal is closed
    $('#modalEditMultiple').on('hidden.bs.modal', function() {
        $('#formEditMultiple')[0].reset();
        $('#editMultiple_status_pegawai').val('');
        $('#editMultipleIds').val('');
    });
    
    // Handle form submit for multiple edit
    $('#formEditMultiple').on('submit', function(e) {
        e.preventDefault();
        
        var idsArray = Array.from(selectedIds);
        
        if (idsArray.length === 0) {
            Swal.fire({
                icon: 'warning',
                title: 'Tidak ada data dipilih',
                text: 'Silakan pilih data yang ingin diedit terlebih dahulu.'
            });
            return;
        }
        
        // Collect data from table
        var formData = {
            ids: idsArray.join(','),
            nama_lengkap: {},
            tmt: {},
            jumlah_jam_mengajar: {},
            jabatan: {},
            status_pegawai: {}
        };
        
        $('#editMultipleTableBody tr').each(function() {
            var id = $(this).data('id');
            if (id) {
                formData.nama_lengkap[id] = $(this).find('input[name^="nama_lengkap"]').val() || '';
                formData.tmt[id] = $(this).find('input[name^="tmt"]').val() || '';
                formData.jumlah_jam_mengajar[id] = $(this).find('input[name^="jumlah_jam_mengajar"]').val() || '';
                formData.jabatan[id] = $(this).find('select[name^="jabatan"]').val() || '';
                formData.status_pegawai[id] = $(this).find('select[name^="status_pegawai"]').val() || '';
            }
        });
        
        // Show loading
        Swal.fire({
            title: 'Menyimpan...',
            text: 'Mohon tunggu',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });
        
        // Submit via AJAX
        $.ajax({
            url: 'save_multiple.php',
            type: 'POST',
            data: formData,
            dataType: 'json',
            success: function(response) {
                Swal.close();
                if (response.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil',
                        text: response.message || 'Data berhasil diubah',
                        timer: 2000,
                        showConfirmButton: false
                    }).then(function() {
                        // Close modal
                        $('#modalEditMultiple').modal('hide');
                        // Clear selection
                        selectedIds.clear();
                        $('.row-checkbox').prop('checked', false);
                        $('#selectAll').prop('checked', false);
                        updateBulkActions();
                        // Reload page after short delay
                        setTimeout(function() {
                            window.location.reload();
                        }, 500);
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: response.message || 'Gagal mengubah data'
                    });
                }
            },
            error: function(xhr, status, error) {
                Swal.close();
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Terjadi kesalahan saat menyimpan data: ' + error
                });
            }
        });
    });
    
    // Initialize bulk actions on page load
    updateBulkActions();
    
    // Re-attach handlers after table is drawn to restore checkbox states
    table.on('draw', function() {
        // Restore checkbox states after pagination
        $('.row-checkbox').each(function() {
            var id = $(this).val();
            $(this).prop('checked', selectedIds.has(id));
        });
        
        // Update select all checkbox state
        var totalCheckboxes = $('.row-checkbox:visible').length;
        var checkedCheckboxes = $('.row-checkbox:visible:checked').length;
        $('#selectAll').prop('checked', totalCheckboxes > 0 && totalCheckboxes === checkedCheckboxes);
        
        updateBulkActions();
    });
    
    // Force update on page load
    setTimeout(function() {
        updateBulkActions();
        console.log('Initial updateBulkActions called');
    }, 1000);
    
    // Ensure buttons are visible and properly styled
    setTimeout(function() {
        var buttonsContainer = table.buttons().container();
        var targetContainer = $('#tableGuru_buttons');
        
        if (buttonsContainer.length > 0 && targetContainer.length > 0) {
            if (buttonsContainer.parent().attr('id') !== 'tableGuru_buttons') {
                buttonsContainer.appendTo(targetContainer);
            }
            buttonsContainer.css({
                'display': 'flex',
                'flex-wrap': 'wrap',
                'gap': '5px'
            });
            buttonsContainer.find('.dt-button').css({
                'margin': '0',
                'display': 'inline-block'
            });
        }
        
        // Also check for default location
        var defaultButtons = $('.dt-buttons');
        if (defaultButtons.length > 0) {
            if (defaultButtons.parent().attr('id') !== 'tableGuru_buttons') {
                defaultButtons.appendTo('#tableGuru_buttons');
            }
            defaultButtons.css({
                'display': 'flex',
                'flex-wrap': 'wrap',
                'gap': '5px'
            });
        }
        
        console.log('Export buttons initialized');
    }, 500);
    
    // Initialize bulk actions on page load
    updateBulkActions();
    
    }, 100); // Delay to ensure main.js doesn't interfere
        }); // End of $(document).ready
    } // End of initGuruPage
    
    // Start initialization
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initGuruPage);
    } else {
        initGuruPage();
    }
})(); // End of IIFE
</script>

<style>
/* Styling untuk dropdown jabatan */
.dropdown-checkbox-wrapper .dropdown-menu {
    padding: 0;
    border: 1px solid #ddd;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.dropdown-checkbox-wrapper .dropdown-item {
    padding: 8px 15px;
    cursor: pointer;
}

.dropdown-checkbox-wrapper .dropdown-item:hover {
    background-color: #f8f9fa;
}

.dropdown-checkbox-wrapper .dropdown-item .form-check {
    margin: 0;
}

.dropdown-checkbox-wrapper .dropdown-item .form-check-label {
    cursor: pointer;
    width: 100%;
    margin-left: 5px;
}

.dropdown-checkbox-wrapper .dropdown-item:first-child {
    border-bottom: 1px solid #ddd;
    background-color: #f8f9fa;
}

/* Styling untuk kolom Jabatan di tabel */
.jabatan-cell {
    white-space: normal;
    word-wrap: break-word;
    max-width: 200px;
}
</style>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>


