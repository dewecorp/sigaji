<?php
$page_title = 'Data Tunjangan';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';

// Get current period
$sql = "SELECT periode_aktif FROM settings LIMIT 1";
$result = $conn->query($sql);
$settings = $result->fetch_assoc();
$periode_aktif = $settings['periode_aktif'] ?? date('Y-m');

// Get tunjangan with jumlah_tunjangan and list of gurus
// Show all gurus who have ever received this tunjangan (from any period)
// Priority: current period first, then latest period if current has no data
$sql = "SELECT t.*, 
        COALESCE(
            (SELECT GROUP_CONCAT(DISTINCT g1.nama_lengkap ORDER BY g1.nama_lengkap SEPARATOR ', ')
             FROM tunjangan_detail td1
             JOIN guru g1 ON td1.guru_id = g1.id
             WHERE td1.tunjangan_id = t.id AND td1.periode = ?),
            (SELECT GROUP_CONCAT(DISTINCT g2.nama_lengkap ORDER BY g2.nama_lengkap SEPARATOR ', ')
             FROM tunjangan_detail td2
             JOIN guru g2 ON td2.guru_id = g2.id
             WHERE td2.tunjangan_id = t.id
             AND td2.periode = (
                 SELECT MAX(td3.periode) 
                 FROM tunjangan_detail td3 
                 WHERE td3.tunjangan_id = t.id
             ))
        ) as nama_guru_list
        FROM tunjangan t
        ORDER BY t.nama_tunjangan ASC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $periode_aktif);
$stmt->execute();
$result = $stmt->get_result();
$tunjangan = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Get all teachers for dropdown
$sql = "SELECT id, nama_lengkap FROM guru ORDER BY nama_lengkap ASC";
$all_guru = $conn->query($sql)->fetch_all(MYSQLI_ASSOC);
?>

            <div class="main-content">
                <section class="section">
                    <div class="section-header">
                        <h1>Data Tunjangan</h1>
                        <div class="section-header-breadcrumb">
                            <div class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>pages/dashboard.php">Dashboard</a></div>
                            <div class="breadcrumb-item active">Data Tunjangan</div>
                        </div>
                    </div>

                    <div class="section-body">
                        <div class="card">
                            <div class="card-header">
                                <h4>Data Tunjangan</h4>
                                <div class="card-header-action" style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
                                    <!-- Buttons container for DataTable export buttons -->
                                    <div id="tableTunjangan_buttons" style="display: flex; gap: 5px; flex-wrap: wrap;"></div>
                                    <button class="btn btn-primary" data-toggle="modal" data-target="#modalTambah">
                                        <i class="fas fa-plus"></i> Tambah Tunjangan
                                    </button>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-striped" id="tableTunjangan">
                                        <thead>
                                            <tr>
                                                <th>No</th>
                                                <th>Nama Tunjangan</th>
                                                <th>Jumlah Tunjangan</th>
                                                <th>Nama Guru</th>
                                                <th>Status</th>
                                                <th>Aksi</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php $no = 1; foreach ($tunjangan as $t): ?>
                                                <tr>
                                                    <td><?php echo $no++; ?></td>
                                                    <td><?php echo htmlspecialchars($t['nama_tunjangan']); ?></td>
                                                    <td><?php echo formatRupiah($t['jumlah_tunjangan'] ?? 0); ?></td>
                                                    <td class="nama-guru-cell">
                                                        <?php 
                                                        $nama_guru = $t['nama_guru_list'] ?? '';
                                                        if (empty($nama_guru)) {
                                                            echo '<span class="text-muted">-</span>';
                                                        } else {
                                                            // Display all names without truncation
                                                            echo '<span class="nama-guru-text">' . htmlspecialchars($nama_guru) . '</span>';
                                                        }
                                                        ?>
                                                    </td>
                                                    <td>
                                                        <span class="badge badge-<?php echo $t['aktif'] ? 'success' : 'danger'; ?>">
                                                            <?php echo $t['aktif'] ? 'Aktif' : 'Tidak Aktif'; ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <button class="btn btn-sm btn-info btn-edit-tunjangan" data-id="<?php echo $t['id']; ?>" onclick="if(typeof editTunjangan==='function'){editTunjangan(<?php echo $t['id']; ?>);}else{console.error('editTunjangan not defined');}" data-toggle="tooltip" title="Edit">
                                                            <i class="fas fa-edit"></i>
                                                        </button>
                                                        <button class="btn btn-sm btn-danger" onclick="confirmDelete('<?php echo BASE_URL; ?>pages/tunjangan/delete.php?id=<?php echo $t['id']; ?>')" data-toggle="tooltip" title="Hapus">
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
                <div class="modal-dialog" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="modalTitle">Tambah Tunjangan</h5>
                            <button type="button" class="close" data-dismiss="modal">
                                <span>&times;</span>
                            </button>
                        </div>
                        <form id="formTunjangan" method="POST" action="save.php">
                            <input type="hidden" name="id" id="tunjangan_id">
                            <div class="modal-body">
                                <div class="form-group">
                                    <label>Nama Tunjangan <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="nama_tunjangan" id="nama_tunjangan" required>
                                </div>
                                <div class="form-group">
                                    <label>Jumlah Tunjangan <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text">Rp</span>
                                        </div>
                                        <input type="text" class="form-control" name="jumlah_tunjangan" id="jumlah_tunjangan" placeholder="0" required>
                                        <input type="hidden" name="jumlah_tunjangan_hidden" id="jumlah_tunjangan_hidden" value="0">
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label>Nama Guru <span class="text-danger">*</span></label>
                                    <div class="dropdown-checkbox-wrapper" style="position: relative;">
                                        <button type="button" class="btn btn-secondary dropdown-toggle w-100 text-left" id="guruDropdownBtn" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" style="text-align: left;">
                                            <span id="guruSelectedText">Pilih Guru</span>
                                            <span class="float-right"><i class="fas fa-chevron-down"></i></span>
                                        </button>
                                        <div class="dropdown-menu w-100" id="guruDropdownMenu" style="max-height: 300px; overflow-y: auto;">
                                            <!-- Search input -->
                                            <div class="px-3 py-2 border-bottom">
                                                <input type="text" class="form-control form-control-sm" id="guruSearchInput" placeholder="Cari nama guru..." autocomplete="off">
                                            </div>
                                            <div class="px-3 py-2 border-bottom">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" id="selectAllGuru">
                                                    <label class="form-check-label font-weight-bold" for="selectAllGuru">
                                                        Pilih Semua
                                                    </label>
                                                </div>
                                            </div>
                                            <div id="guruListContainer">
                                                <?php foreach ($all_guru as $guru): ?>
                                                <div class="dropdown-item guru-item" data-name="<?php echo strtolower(htmlspecialchars($guru['nama_lengkap'])); ?>">
                                                    <div class="form-check">
                                                        <input class="form-check-input guru-checkbox" type="checkbox" name="guru_ids[]" value="<?php echo $guru['id']; ?>" id="guru_<?php echo $guru['id']; ?>" checked>
                                                        <label class="form-check-label" for="guru_<?php echo $guru['id']; ?>">
                                                            <?php echo htmlspecialchars($guru['nama_lengkap']); ?>
                                                        </label>
                                                    </div>
                                                </div>
                                                <?php endforeach; ?>
                                            </div>
                                            <div id="guruNoResults" class="px-3 py-2 text-muted text-center" style="display: none;">
                                                <small>Tidak ada guru yang ditemukan</small>
                                            </div>
                                        </div>
                                    </div>
                                    <small class="text-muted">Pilih guru yang mendapat tunjangan ini</small>
                                </div>
                                <div class="form-group">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="aktif" id="aktif" value="1" checked>
                                        <label class="form-check-label" for="aktif">Aktif</label>
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

<script>
// Format Rupiah function (must be available globally)
function formatRupiah(angka) {
    // Convert to string and remove any non-numeric characters
    var number_string = angka.toString().replace(/[^\d]/g, '');
    if (number_string === '' || number_string === '0') return '0';
    
    // Remove leading zeros
    number_string = number_string.replace(/^0+/, '') || '0';
    
    var sisa = number_string.length % 3;
    var rupiah = number_string.substr(0, sisa);
    var ribuan = number_string.substr(sisa).match(/\d{3}/g);
    
    if (ribuan) {
        var separator = sisa ? '.' : '';
        rupiah += separator + ribuan.join('.');
    }
    
    return rupiah;
}

// Unformat Rupiah function (must be available globally)
function unformatRupiah(rupiah) {
    if (!rupiah || rupiah === '') return 0;
    var cleaned = rupiah.toString().replace(/\./g, '').replace(/[^0-9]/g, '');
    return parseFloat(cleaned) || 0;
}

// Define editTunjangan function immediately (will be available globally)
function editTunjangan(id) {
    console.log('editTunjangan function called with ID:', id);
    
    // Wait for jQuery if not ready
    if (typeof jQuery === 'undefined') {
        console.error('jQuery not loaded yet, retrying...');
        setTimeout(function() {
            editTunjangan(id);
        }, 100);
        return;
    }
    
    var $ = jQuery;
    
    console.log('editTunjangan executing with ID:', id);
    
    $.ajax({
        url: 'get.php?id=' + id,
        type: 'GET',
        dataType: 'json',
        success: function(data) {
            console.log('Data received from get.php:', data);
            
            if (data.error) {
                Swal.fire('Error', data.error, 'error');
                return;
            }
            
            // Validate data exists
            if (!data || !data.id) {
                console.error('Invalid data received:', data);
                Swal.fire('Error', 'Data tidak valid', 'error');
                return;
            }
            
            // Clear form first to avoid conflicts (but preserve values we're about to set)
            // Don't use reset() as it might clear values we're setting
            $('#nama_tunjangan').val('');
            $('#jumlah_tunjangan').val('');
            $('#jumlah_tunjangan_hidden').val('');
            $('#aktif').prop('checked', false);
            
            // Set form values - CRITICAL: Set ID first and ensure it's not empty
            var tunjanganId = data.id ? parseInt(data.id) : '';
            if (tunjanganId && tunjanganId > 0) {
                $('#tunjangan_id').val(tunjanganId.toString());
                console.log('Setting tunjangan_id to:', tunjanganId, '(type:', typeof tunjanganId, ')');
            } else {
                $('#tunjangan_id').val('');
                console.warn('Invalid tunjangan ID:', data.id);
            }
            $('#nama_tunjangan').val(data.nama_tunjangan || '');
            
            // Format jumlah_tunjangan as Rupiah
            var jumlah = parseFloat(data.jumlah_tunjangan || 0);
            if (isNaN(jumlah)) {
                jumlah = 0;
            }
            
            // Store raw value in hidden input
            $('#jumlah_tunjangan_hidden').val(jumlah.toString());
            
            // Format for display (remove decimals for formatting)
            var jumlahString = Math.floor(jumlah).toString();
            var formatted = formatRupiah(jumlahString);
            $('#jumlah_tunjangan').val(formatted);
            
            // Set aktif checkbox
            var aktif = data.aktif == 1 || data.aktif == '1' || data.aktif === 1;
            $('#aktif').prop('checked', aktif);
            
            console.log('Form filled - ID:', data.id, 'Nama:', data.nama_tunjangan, 'Jumlah:', jumlah, 'Aktif:', aktif);
            
            // Load selected gurus for this tunjangan
            $.ajax({
                url: 'get_guru.php?tunjangan_id=' + data.id,
                type: 'GET',
                dataType: 'json',
                success: function(guruData) {
                    // Uncheck all first
                    $('.guru-checkbox').prop('checked', false);
                    $('#selectAllGuru').prop('checked', false);
                    
                    // Check selected gurus
                    if (guruData && guruData.guru_ids && guruData.guru_ids.length > 0) {
                        guruData.guru_ids.forEach(function(guruId) {
                            $('#guru_' + guruId).prop('checked', true);
                        });
                        
                        // Update select all checkbox
                        var total = $('.guru-checkbox').length;
                        var checked = $('.guru-checkbox:checked').length;
                        $('#selectAllGuru').prop('checked', checked === total);
                    } else {
                        // If no data, select all by default
                        $('.guru-checkbox').prop('checked', true);
                        $('#selectAllGuru').prop('checked', true);
                    }
                    if (typeof updateGuruSelectedText === 'function') {
                        updateGuruSelectedText();
                    }
                    
                    // Set modal title and show after guru data is loaded
                    $('#modalTitle').text('Edit Tunjangan');
                    $('#modalTambah').modal('show');
                },
                error: function(xhr, status, error) {
                    console.error('Error loading guru data:', error, xhr.responseText);
                    // On error, don't select all - leave empty
                    if (typeof updateGuruSelectedText === 'function') {
                        updateGuruSelectedText();
                    }
                    
                    // Set modal title and show anyway
                    $('#modalTitle').text('Edit Tunjangan');
                    $('#modalTambah').modal('show');
                }
            });
        },
        error: function(xhr, status, error) {
            console.error('Error loading tunjangan:', error, xhr.responseText);
            Swal.fire('Error', 'Gagal memuat data tunjangan: ' + error, 'error');
        }
    });
}

// Wait for jQuery to be loaded for form handlers
(function() {
    var retryCount = 0;
    var maxRetries = 20; // Maximum 20 retries (1 second)
    
    function initTunjanganForm() {
        if (typeof jQuery === 'undefined') {
            retryCount++;
            if (retryCount < maxRetries) {
                setTimeout(initTunjanganForm, 50);
            } else {
                console.error('jQuery failed to load for form handlers after ' + maxRetries + ' retries');
            }
            return;
        }
        
        // Reset retry count on success
        retryCount = 0;
        
        var $ = jQuery;
        
        // Functions are already defined globally above, just use them
        $(document).ready(function() {
            // Event delegation for edit buttons (works with DataTable pagination)
            $(document).on('click', '.btn-edit-tunjangan', function() {
                var id = $(this).data('id');
                console.log('Edit button clicked via delegation, ID:', id);
                if (id) {
                    editTunjangan(id);
                } else {
                    console.error('No ID found on edit button');
                }
            });
            
            // Also ensure window.editTunjangan is available
            if (typeof window.editTunjangan === 'undefined') {
                window.editTunjangan = editTunjangan;
            }
            
            // Modal reset handler - only reset when modal is closed (not when opening for edit)
            $('#modalTambah').on('hidden.bs.modal', function () {
                // Only reset if it's not an edit operation (check if ID is empty)
                var isEdit = $('#tunjangan_id').val() && $('#tunjangan_id').val() !== '';
                if (!isEdit) {
                    $('#formTunjangan')[0].reset();
                    $('#tunjangan_id').val('');
                    $('#jumlah_tunjangan').val('');
                    $('#jumlah_tunjangan_hidden').val('');
                    $('#aktif').prop('checked', true);
                    // Reset guru selection - uncheck all by default (user must explicitly select)
                    $('.guru-checkbox').prop('checked', false);
                    $('#selectAllGuru').prop('checked', false);
                    if (typeof updateGuruSelectedText === 'function') {
                        updateGuruSelectedText();
                    }
                    $('#modalTitle').text('Tambah Tunjangan');
                }
            });
            
            // Guru dropdown functionality - make it global
            window.updateGuruSelectedText = function() {
                var selected = $('.guru-checkbox:checked');
                var total = $('.guru-checkbox').length;
                var selectedCount = selected.length;
                
                if (selectedCount === 0) {
                    $('#guruSelectedText').text('Pilih Guru');
                } else if (selectedCount === total) {
                    $('#guruSelectedText').text('Semua Guru (' + total + ')');
                } else {
                    // Get names of selected gurus
                    var selectedNames = [];
                    selected.each(function() {
                        var label = $(this).closest('.guru-item').find('label').text().trim();
                        if (label) {
                            selectedNames.push(label);
                        }
                    });
                    
                    // Display names, limit to 3 names max, then show count
                    if (selectedNames.length <= 3) {
                        $('#guruSelectedText').text(selectedNames.join(', '));
                    } else {
                        var displayNames = selectedNames.slice(0, 3).join(', ');
                        $('#guruSelectedText').text(displayNames + ' dan ' + (selectedCount - 3) + ' lainnya');
                    }
                }
            };
            
            // Select all guru checkbox - only select visible items
            $(document).on('change', '#selectAllGuru', function() {
                var isChecked = $(this).prop('checked');
                // Only check/uncheck visible items
                $('.guru-item:visible .guru-checkbox').prop('checked', isChecked);
                updateGuruSelectedText();
            });
            
            // Individual guru checkbox
            $(document).on('change', '.guru-checkbox', function() {
                var visibleCheckboxes = $('.guru-item:visible .guru-checkbox');
                var total = visibleCheckboxes.length;
                var checked = visibleCheckboxes.filter(':checked').length;
                // Update "Pilih Semua" checkbox based on visible items
                $('#selectAllGuru').prop('checked', total > 0 && checked === total);
                updateGuruSelectedText();
            });
            
            // Initialize on modal show - only for new entries (not edit)
            $('#modalTambah').on('shown.bs.modal', function() {
                // Only auto-select all if it's a new entry (no ID)
                var isEdit = $('#tunjangan_id').val() && $('#tunjangan_id').val() !== '';
                if (!isEdit) {
                    // For new entries, select all by default
                    $('.guru-checkbox').prop('checked', true);
                    $('#selectAllGuru').prop('checked', true);
                    updateGuruSelectedText();
                }
            });
            
            // Prevent dropdown from closing when clicking inside
            $(document).on('click', '#guruDropdownMenu', function(e) {
                e.stopPropagation();
            });
            
            // Search functionality for guru dropdown
            $('#guruSearchInput').on('input', function() {
                var searchTerm = $(this).val().toLowerCase().trim();
                var visibleCount = 0;
                var visibleCheckedCount = 0;
                
                if (searchTerm === '') {
                    // Show all items
                    $('.guru-item').show();
                    $('#guruNoResults').hide();
                    // Update "Pilih Semua" checkbox based on all items
                    var total = $('.guru-checkbox').length;
                    var checked = $('.guru-checkbox:checked').length;
                    $('#selectAllGuru').prop('checked', total > 0 && checked === total);
                } else {
                    // Filter items and uncheck hidden ones
                    $('.guru-item').each(function() {
                        var guruName = $(this).data('name');
                        if (guruName.indexOf(searchTerm) !== -1) {
                            $(this).show();
                            visibleCount++;
                            // Count checked visible items
                            if ($(this).find('.guru-checkbox').is(':checked')) {
                                visibleCheckedCount++;
                            }
                        } else {
                            $(this).hide();
                            // Uncheck hidden items to ensure only visible selections are saved
                            $(this).find('.guru-checkbox').prop('checked', false);
                        }
                    });
                    
                    // Update "Pilih Semua" checkbox based on visible items
                    $('#selectAllGuru').prop('checked', visibleCount > 0 && visibleCheckedCount === visibleCount);
                    
                    // Show/hide "no results" message
                    if (visibleCount === 0) {
                        $('#guruNoResults').show();
                    } else {
                        $('#guruNoResults').hide();
                    }
                    
                    // Update selected text
                    if (typeof updateGuruSelectedText === 'function') {
                        updateGuruSelectedText();
                    }
                }
            });
            
            // Clear search when dropdown is closed
            $('#guruDropdownBtn').on('hidden.bs.dropdown', function() {
                $('#guruSearchInput').val('');
                $('.guru-item').show();
                $('#guruNoResults').hide();
                
                // Update select all checkbox and selected text
                var total = $('.guru-checkbox').length;
                var checked = $('.guru-checkbox:checked').length;
                $('#selectAllGuru').prop('checked', total > 0 && checked === total);
                if (typeof updateGuruSelectedText === 'function') {
                    updateGuruSelectedText();
                }
            });
            
            // Prevent search input from closing dropdown
            $('#guruSearchInput').on('click', function(e) {
                e.stopPropagation();
            });
            
            // Format on input - prevent non-numeric input
            $('#jumlah_tunjangan').on('input', function(e) {
                var value = $(this).val();
                var unformatted = unformatRupiah(value);
                var formatted = formatRupiah(unformatted);
                $(this).val(formatted);
                $('#jumlah_tunjangan_hidden').val(unformatted);
            });
            
            // Format on blur
            $('#jumlah_tunjangan').on('blur', function() {
                var value = $(this).val();
                var unformatted = unformatRupiah(value);
                var formatted = formatRupiah(unformatted);
                $(this).val(formatted);
                $('#jumlah_tunjangan_hidden').val(unformatted);
            });
            
            // Handle form submission with AJAX
            $('#formTunjangan').on('submit', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                console.log('Form submit triggered!');
                
                // Ensure hidden input is updated before submission
                var formatted = $('#jumlah_tunjangan').val();
                var unformatted = unformatRupiah(formatted);
                $('#jumlah_tunjangan_hidden').val(unformatted);
                
                // Validate jumlah tunjangan
                if (!unformatted || unformatted <= 0) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Validasi Gagal',
                        text: 'Jumlah tunjangan harus lebih besar dari 0'
                    });
                    return false;
                }
                
                // Get selected guru IDs - get ALL checked checkboxes
                // This ensures all selected gurus are saved regardless of visibility
                var guruIds = [];
                $('.guru-checkbox:checked').each(function() {
                    var guruId = $(this).val();
                    if (guruId && guruId !== '' && guruId !== '0') {
                        var id = parseInt(guruId);
                        if (id > 0 && guruIds.indexOf(id) === -1) {
                            guruIds.push(id);
                        }
                    }
                });
                
                console.log('Form submission - Selected guru IDs:', guruIds);
                console.log('Form submission - Total checkboxes:', $('.guru-checkbox').length);
                console.log('Form submission - Checked checkboxes:', $('.guru-checkbox:checked').length);
                console.log('Form submission - Visible checked:', $('.guru-item:visible .guru-checkbox:checked').length);
                
                // Validate: at least one guru must be selected
                if (guruIds.length === 0) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Validasi',
                        text: 'Pilih minimal satu guru'
                    });
                    return false;
                }
                
                // Prepare form data
                var tunjanganId = $('#tunjangan_id').val();
                // Ensure ID is sent correctly - if it's empty or 0, send empty string for new entry
                if (!tunjanganId || tunjanganId === '' || tunjanganId === '0') {
                    tunjanganId = '';
                }
                
                // Build form data - simple object approach
                var formData = {
                    id: tunjanganId,
                    nama_tunjangan: $('#nama_tunjangan').val().trim(),
                    jumlah_tunjangan_hidden: $('#jumlah_tunjangan_hidden').val() || '0',
                    aktif: $('#aktif').is(':checked') ? 1 : 0
                };
                
                // Build data string with guru_ids[] array
                var dataString = $.param(formData);
                guruIds.forEach(function(guruId) {
                    dataString += '&guru_ids[]=' + encodeURIComponent(guruId);
                });
                
                // Debug: log values before submission
                console.log('=== FORM SUBMISSION DEBUG ===');
                console.log('Tunjangan ID:', tunjanganId);
                console.log('Is Edit Mode:', tunjanganId && tunjanganId !== '');
                console.log('Nama Tunjangan:', formData.nama_tunjangan);
                console.log('Jumlah Tunjangan Hidden:', formData.jumlah_tunjangan_hidden);
                console.log('Aktif:', formData.aktif);
                console.log('Guru IDs:', guruIds);
                console.log('Guru IDs Count:', guruIds.length);
                
                // Validate
                if (!formData.nama_tunjangan || formData.nama_tunjangan.trim() === '') {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Validasi',
                        text: 'Nama tunjangan tidak boleh kosong'
                    });
                    return false;
                }
                
                // Show loading
                Swal.fire({
                    title: 'Memproses...',
                    text: 'Mohon tunggu',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });
                
                // Submit via AJAX
                $.ajax({
                    url: 'save.php',
                    type: 'POST',
                    data: dataString,
                    contentType: 'application/x-www-form-urlencoded; charset=UTF-8',
                    dataType: 'json',
                    beforeSend: function() {
                        console.log('Sending AJAX request');
                        console.log('Form Data:', formData);
                        console.log('Guru IDs being sent:', guruIds);
                        console.log('Data String:', dataString);
                    },
                    success: function(response) {
                        console.log('Response received:', response);
                        Swal.close();
                        if (response && response.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Berhasil',
                                text: response.message || 'Data tunjangan berhasil disimpan',
                                timer: 2000,
                                showConfirmButton: false
                            }).then(() => {
                                window.location.reload();
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: (response && response.message) ? response.message : 'Gagal menyimpan data tunjangan'
                            });
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('AJAX Error:', error, xhr.responseText);
                        Swal.close();
                        var errorMsg = 'Gagal menyimpan data: ' + error;
                        try {
                            var response = JSON.parse(xhr.responseText);
                            if (response && response.message) {
                                errorMsg = response.message;
                            }
                        } catch(e) {
                            // Use default error message
                            if (xhr.responseText) {
                                errorMsg += '\n' + xhr.responseText.substring(0, 200);
                            }
                        }
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: errorMsg
                        });
                    }
                });
                
                return false;
            });
        });
    }
    
    // Start initialization for form handlers
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initTunjanganForm);
    } else {
        initTunjanganForm();
    }
})();

// Wait for jQuery to be loaded
(function() {
    var retryCount = 0;
    var maxRetries = 20; // Maximum 20 retries (10 seconds)
    
    function initTunjanganPage() {
        if (typeof jQuery === 'undefined') {
            retryCount++;
            if (retryCount < maxRetries) {
                setTimeout(initTunjanganPage, 50);
            } else {
                console.error('jQuery failed to load after ' + maxRetries + ' retries');
            }
            return;
        }
        
        var $ = jQuery;
        
        $(document).ready(function() {
            // Check if DataTable is available
            if (typeof $.fn.DataTable === 'undefined') {
                retryCount++;
                if (retryCount < maxRetries) {
                    console.error('DataTable is not loaded, retrying...');
                    setTimeout(function() {
                        initTunjanganPage();
                    }, 200);
                } else {
                    console.error('DataTable failed to load after ' + maxRetries + ' retries');
                }
                return;
            }
            
            // Reset retry count on success
            retryCount = 0;
            
            // Delay to ensure main.js finishes first
            setTimeout(function() {
                // Aggressively clear ALL DataTable state
                if (typeof(Storage) !== "undefined") {
                    // Clear all DataTable keys, not just specific ones
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
                
                // Destroy existing instance
                if ($.fn.DataTable.isDataTable('#tableTunjangan')) {
                    $('#tableTunjangan').DataTable().destroy();
                }
                
                // Small delay to ensure cleanup is complete
                setTimeout(function() {
                    // Double check DataTable is available
                    if (typeof $.fn.DataTable === 'undefined') {
                        console.error('DataTable still not available');
                        return;
                    }
                    
                    var table = $('#tableTunjangan').DataTable({
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
                    autoWidth: false,
                    columnDefs: [
                        {
                            targets: 0, // No column
                            orderable: false,
                            searchable: false
                        },
                        {
                            targets: '_all',
                            orderable: true
                        }
                    ],
                    drawCallback: function(settings) {
                        // Remove drawCallback to prevent infinite loop
                        // Order is already set in initial configuration
                    }
                });
                
                // Move buttons to custom container
                setTimeout(function() {
                    var buttonsContainer = table.buttons().container();
                    var targetContainer = $('#tableTunjangan_buttons');
                    
                    if (buttonsContainer.length > 0 && targetContainer.length > 0) {
                        if (buttonsContainer.parent().attr('id') !== 'tableTunjangan_buttons') {
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
                        if (defaultButtons.parent().attr('id') !== 'tableTunjangan_buttons') {
                            defaultButtons.appendTo('#tableTunjangan_buttons');
                        }
                        defaultButtons.css({
                            'display': 'flex',
                            'flex-wrap': 'wrap',
                            'gap': '5px'
                        });
                    }
                    
                    console.log('Export buttons initialized');
                }, 500);
                }, 100);
            }, 1000); // Delay to ensure main.js doesn't interfere
        });
    }
    
    // Start initialization
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initTunjanganPage);
    } else {
        initTunjanganPage();
    }
})();
</script>

<style>
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

/* Styling untuk button dropdown agar teks tidak keluar box */
#guruDropdownBtn {
    position: relative;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    padding-right: 35px !important;
}

#guruSelectedText {
    display: inline-block;
    max-width: calc(100% - 30px);
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    vertical-align: middle;
}

#guruDropdownBtn .float-right {
    position: absolute;
    right: 10px;
    top: 50%;
    transform: translateY(-50%);
    flex-shrink: 0;
}

/* Styling untuk kolom Nama Guru agar semua nama terlihat */
.nama-guru-cell {
    min-width: 200px;
    max-width: 400px;
    white-space: normal;
    word-wrap: break-word;
    vertical-align: top;
    padding: 10px 8px !important;
}

.nama-guru-text {
    display: block;
    line-height: 1.6;
    word-break: break-word;
}

/* Memastikan baris tabel bisa menyesuaikan tinggi dengan konten */
#tableTunjangan tbody tr {
    height: auto !important;
}

#tableTunjangan tbody td {
    height: auto !important;
}
</style>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>

