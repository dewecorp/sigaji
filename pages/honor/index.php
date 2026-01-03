<?php
$page_title = 'Data Honor';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';

// Get all honor with pembina name
$sql = "SELECT h.*, p.nama_pembina 
        FROM honor h
        LEFT JOIN pembina p ON h.pembina_id = p.id
        ORDER BY p.nama_pembina ASC, h.id ASC";
$result = $conn->query($sql);
$honor = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];

// Get all pembina for dropdown
$sql_pembina = "SELECT * FROM pembina ORDER BY nama_pembina ASC";
$result_pembina = $conn->query($sql_pembina);
$pembina_list = $result_pembina ? $result_pembina->fetch_all(MYSQLI_ASSOC) : [];
?>


            <div class="main-content">
                <section class="section">
                    <div class="section-header">
                        <h1>Data Honor</h1>
                        <div class="section-header-breadcrumb">
                            <div class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>pages/dashboard.php">Dashboard</a></div>
                            <div class="breadcrumb-item active">Data Honor</div>
                        </div>
                    </div>

                    <div class="section-body">
                        <div class="card">
                            <div class="card-header">
                                <h4>Data Honor</h4>
                                <div class="card-header-action">
                                    <button class="btn btn-primary" data-toggle="modal" data-target="#modalTambah">
                                        <i class="fas fa-plus"></i> Tambah Honor
                                    </button>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-striped" id="tableHonor">
                                        <thead>
                                            <tr>
                                                <th>No</th>
                                                <th>Nama Pembina</th>
                                                <th>Jabatan</th>
                                                <th>Jumlah Honor</th>
                                                <th>Jumlah Pertemuan</th>
                                                <th>Status</th>
                                                <th>Aksi</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php $no = 1; foreach ($honor as $h): ?>
                                                <tr>
                                                    <td><?php echo $no++; ?></td>
                                                    <td><?php echo htmlspecialchars($h['nama_pembina'] ?? '-'); ?></td>
                                                    <td><?php echo htmlspecialchars($h['jabatan']); ?></td>
                                                    <td style="text-align: right;"><?php echo formatRupiah($h['jumlah_honor']); ?></td>
                                                    <td style="text-align: center;"><?php echo $h['jumlah_pertemuan'] ?? 0; ?></td>
                                                    <td>
                                                        <span class="badge badge-<?php echo $h['aktif'] ? 'success' : 'danger'; ?>">
                                                            <?php echo $h['aktif'] ? 'Aktif' : 'Tidak Aktif'; ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <button class="btn btn-sm btn-info" onclick="editHonor(<?php echo intval($h['id']); ?>)" data-toggle="tooltip" title="Edit">
                                                            <i class="fas fa-edit"></i>
                                                        </button>
                                                        <button class="btn btn-sm btn-danger" onclick="confirmDelete('<?php echo BASE_URL; ?>pages/honor/delete.php?id=<?php echo intval($h['id']); ?>')" data-toggle="tooltip" title="Hapus">
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
                            <h5 class="modal-title" id="modalTitle">Tambah Honor</h5>
                            <button type="button" class="close" data-dismiss="modal">
                                <span>&times;</span>
                            </button>
                        </div>
                        <form id="formHonor">
                            <input type="hidden" name="id" id="honor_id">
                            <div class="modal-body">
                                <div class="form-group">
                                    <label>Nama Pembina <span class="text-danger">*</span></label>
                                    <select class="form-control" name="pembina_id" id="pembina_id" required>
                                        <option value="">-- Pilih Pembina --</option>
                                        <?php foreach ($pembina_list as $p): ?>
                                            <option value="<?php echo $p['id']; ?>"><?php echo htmlspecialchars($p['nama_pembina']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Jabatan <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="jabatan" id="jabatan" required>
                                </div>
                                <div class="form-group">
                                    <label>Jumlah Honor</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text">Rp</span>
                                        </div>
                                        <input type="text" class="form-control currency-input" name="jumlah_honor" id="jumlah_honor" placeholder="0" required>
                                        <input type="hidden" name="jumlah_honor_hidden" id="jumlah_honor_hidden" value="0">
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label>Jumlah Pertemuan <span class="text-danger">*</span></label>
                                    <input type="number" class="form-control" name="jumlah_pertemuan" id="jumlah_pertemuan" min="0" required>
                                </div>
                                <div class="form-group">
                                    <label>
                                        <input type="checkbox" name="aktif" id="aktif" value="1" checked> Aktif
                                    </label>
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

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>



<style>
/* Paksa kolom No dan Nama Pembina selalu ascending - sembunyikan indikator descending */
#tableHonor thead th:nth-child(1).sorting_desc,
#tableHonor thead th:nth-child(2).sorting_desc {
    background-image: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 12 12"><path fill="%23666" d="M6 0L2 4h8z"/></svg>') !important;
    background-position: right 8px center !important;
    background-repeat: no-repeat !important;
}

/* Pastikan cursor pointer */
#tableHonor thead th:nth-child(1),
#tableHonor thead th:nth-child(2) {
    cursor: pointer !important;
}
</style>

<script>
// All initialization code goes here

// Format Rupiah function (must be available globally)
function formatRupiah(angka) {
    var number_string = angka.toString().replace(/[^\d]/g, '');
    if (number_string === '' || number_string === '0') return '0';
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

// Define editHonor function immediately (will be available globally)
function editHonor(id) {
    if (typeof jQuery === 'undefined') {
        setTimeout(function() { editHonor(id); }, 100);
        return;
    }
    var $ = window.jQuery;
    
    $.ajax({
        url: '<?php echo BASE_URL; ?>pages/honor/get.php',
        type: 'GET',
        data: { id: id },
        dataType: 'json',
        success: function(response) {
            if (response.success && response.data) {
                $('#honor_id').val(response.data.id);
                $('#pembina_id').val(response.data.pembina_id || '');
                $('#jabatan').val(response.data.jabatan || '');
                
                // Format jumlah_honor as Rupiah
                var jumlah = parseFloat(response.data.jumlah_honor || 0);
                if (isNaN(jumlah)) {
                    jumlah = 0;
                }
                
                // Store raw value in hidden input
                $('#jumlah_honor_hidden').val(jumlah.toString());
                
                // Format for display
                var jumlahString = Math.floor(jumlah).toString();
                var formatted = typeof formatRupiah === 'function' ? formatRupiah(jumlahString) : jumlahString;
                $('#jumlah_honor').val(formatted);
                
                // Set jumlah_pertemuan
                $('#jumlah_pertemuan').val(response.data.jumlah_pertemuan || 0);
                
                $('#aktif').prop('checked', response.data.aktif == 1);
                $('#modalTitle').text('Edit Honor');
                
                // Ensure all fields are enabled and editable
                $('#pembina_id').prop('disabled', false).removeAttr('disabled');
                $('#jabatan').prop('disabled', false).removeAttr('disabled').prop('readonly', false).removeAttr('readonly');
                $('#jumlah_honor').prop('disabled', false).removeAttr('disabled').prop('readonly', false).removeAttr('readonly');
                $('#jumlah_pertemuan').prop('disabled', false).removeAttr('disabled').prop('readonly', false).removeAttr('readonly');
                $('#aktif').prop('disabled', false).removeAttr('disabled');
                
                $('#modalTambah').modal('show');
                
                // Ensure format is applied after modal is shown
                setTimeout(function() {
                    $('#pembina_id').prop('disabled', false).removeAttr('disabled');
                    $('#jabatan').prop('disabled', false).removeAttr('disabled').prop('readonly', false).removeAttr('readonly');
                    $('#jumlah_honor').prop('disabled', false).removeAttr('disabled').prop('readonly', false).removeAttr('readonly');
                    $('#jumlah_pertemuan').prop('disabled', false).removeAttr('disabled').prop('readonly', false).removeAttr('readonly');
                    $('#aktif').prop('disabled', false).removeAttr('disabled');
                    
                    var currentValue = $('#jumlah_honor').val();
                    if (currentValue && currentValue !== '0' && currentValue !== '') {
                        if (typeof unformatRupiah === 'function' && typeof formatRupiah === 'function') {
                            var unformatted = unformatRupiah(currentValue);
                            var formatted = formatRupiah(unformatted.toString());
                            $('#jumlah_honor').val(formatted);
                            $('#jumlah_honor_hidden').val(unformatted);
                        }
                    }
                }, 100);
            } else {
                var errorMsg = response.message || 'Gagal mengambil data honor';
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: errorMsg
                    });
                } else {
                    alert(errorMsg);
                }
            }
        },
        error: function(xhr, status, error) {
            console.error('AJAX Error:', status, error);
            var errorMsg = 'Terjadi kesalahan saat mengambil data honor';
            try {
                var response = JSON.parse(xhr.responseText);
                errorMsg = response.message || errorMsg;
            } catch(e) {
                errorMsg = xhr.responseText || errorMsg;
            }
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: errorMsg
                });
            } else {
                alert(errorMsg);
            }
        }
    });
}

// Wait for jQuery to be loaded
(function() {
    function initHonor() {
        if (typeof jQuery === 'undefined' || typeof window.jQuery === 'undefined') {
            setTimeout(initHonor, 50);
            return;
        }
        
        var $ = window.jQuery;
        
        $(document).ready(function() {
            // Currency input formatting (same as tunjangan)
            $('#jumlah_honor').on('input', function(e) {
                var value = $(this).val();
                var unformatted = unformatRupiah(value);
                var formatted = formatRupiah(unformatted);
                $(this).val(formatted);
                $('#jumlah_honor_hidden').val(unformatted);
            });
            
            // Format on blur
            $('#jumlah_honor').on('blur', function() {
                var value = $(this).val();
                var unformatted = unformatRupiah(value);
                var formatted = formatRupiah(unformatted);
                $(this).val(formatted);
                $('#jumlah_honor_hidden').val(unformatted);
            });

            // Clear DataTable state from localStorage and sessionStorage
            if (typeof(Storage) !== "undefined") {
                Object.keys(localStorage).forEach(function(key) {
                    if (key.indexOf('DataTables_tableHonor') === 0 || key.indexOf('DataTables_') === 0) {
                        localStorage.removeItem(key);
                    }
                });
                Object.keys(sessionStorage).forEach(function(key) {
                    if (key.indexOf('DataTables_tableHonor') === 0 || key.indexOf('DataTables_') === 0) {
                        sessionStorage.removeItem(key);
                    }
                });
            }
            
            // Destroy existing instance if it exists
            if ($.fn.DataTable.isDataTable('#tableHonor')) {
                $('#tableHonor').DataTable().destroy();
            }

            // Tunggu sedikit untuk memastikan semua script sudah loaded
            setTimeout(function() {
                // Pastikan tidak ada instance DataTable yang sudah ada
                if ($.fn.DataTable.isDataTable('#tableHonor')) {
                    $('#tableHonor').DataTable().destroy();
                }
                
                var table = $('#tableHonor').DataTable({
                    language: { url: 'https://cdn.datatables.net/plug-ins/1.11.5/i18n/id.json' },
                    order: [[1, 'asc'], [0, 'asc']], // Urutkan kolom Nama Pembina (1) dulu, lalu kolom No (0)
                    stateSave: false,
                    stateDuration: -1,
                    retrieve: false,
                    columnDefs: [
                        {
                            targets: [0, 1], // Kolom No dan Nama Pembina
                            orderable: true,
                            orderSequence: ['asc', 'asc'] // Hanya bisa ascending (2x untuk mencegah descending)
                        }
                    ]
                });
            }, 100);
            
            $('#formHonor').on('submit', function(e) {
                e.preventDefault();
                
                // Ensure all fields are enabled before serialize (disabled fields are not serialized)
                $('#pembina_id').prop('disabled', false);
                $('#jabatan').prop('disabled', false).prop('readonly', false);
                $('#jumlah_honor').prop('disabled', false).prop('readonly', false);
                $('#jumlah_pertemuan').prop('disabled', false).prop('readonly', false);
                $('#aktif').prop('disabled', false);
                
                // Ensure hidden value is set before submit - same as tunjangan
                var formatted = $('#jumlah_honor').val();
                var unformatted = unformatRupiah(formatted);
                // Ensure it's a valid number
                unformatted = parseFloat(unformatted) || 0;
                $('#jumlah_honor_hidden').val(unformatted.toString());
                
                // Validate jumlah honor
                if (!unformatted || unformatted <= 0) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Validasi Gagal',
                        text: 'Jumlah honor harus lebih besar dari 0'
                    });
                    return false;
                }
                
                // Prepare form data
                var formData = $(this).serialize();
                var honorId = $('#honor_id').val();
                
                console.log('Form data being sent:', formData);
                console.log('Honor ID:', honorId);
                console.log('Is edit mode:', honorId && honorId !== '');
                
                // Show loading indicator
                Swal.fire({
                    title: 'Menyimpan...',
                    text: 'Mohon tunggu',
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    showConfirmButton: false,
                    didOpen: function() {
                        Swal.showLoading();
                    }
                });
                
                $.ajax({
                    url: '<?php echo BASE_URL; ?>pages/honor/save.php',
                    type: 'POST',
                    data: formData,
                    contentType: 'application/x-www-form-urlencoded; charset=UTF-8',
                    dataType: 'json',
                    beforeSend: function() {
                        console.log('Sending AJAX request');
                        console.log('Form Data:', formData);
                        console.log('URL: pages/honor/save.php');
                    },
                    success: function(response) {
                        console.log('=== SUCCESS HANDLER CALLED ===');
                        console.log('Response from save.php:', response);
                        console.log('Response type:', typeof response);
                        console.log('Response.success:', response ? response.success : 'undefined');
                        console.log('Response stringified:', JSON.stringify(response));
                        
                        Swal.close();
                        
                        // Handle both string and object responses
                        var responseObj = response;
                        if (typeof response === 'string') {
                            try {
                                responseObj = JSON.parse(response);
                            } catch(e) {
                                console.error('Failed to parse response:', e);
                            }
                        }
                        
                        if (responseObj && responseObj.success === true) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Berhasil!',
                                text: responseObj.message || 'Data berhasil disimpan',
                                timer: 2000,
                                showConfirmButton: false
                            }).then(function() {
                                window.location.reload();
                            });
                        } else {
                            var errorMsg = (responseObj && responseObj.message) ? responseObj.message : 'Terjadi kesalahan saat menyimpan data';
                            console.error('Save failed:', errorMsg);
                            console.error('Full response:', responseObj);
                            Swal.fire({
                                icon: 'error',
                                title: 'Gagal!',
                                text: errorMsg
                            });
                        }
                    },
                    error: function(xhr, status, error) {
                        Swal.close();
                        console.error('=== ERROR HANDLER CALLED ===');
                        console.error('AJAX Error:', status, error);
                        console.error('Status Code:', xhr.status);
                        console.error('Response Text:', xhr.responseText);
                        console.error('Ready State:', xhr.readyState);
                        
                        var errorMsg = 'Terjadi kesalahan saat menyimpan data';
                        if (xhr.status === 0) {
                            errorMsg = 'Tidak dapat terhubung ke server. Periksa koneksi internet Anda.';
                        } else if (xhr.status === 404) {
                            errorMsg = 'File save.php tidak ditemukan.';
                        } else if (xhr.status === 500) {
                            errorMsg = 'Terjadi kesalahan di server.';
                        }
                        
                        try {
                            var response = JSON.parse(xhr.responseText);
                            if (response && response.message) {
                                errorMsg = response.message;
                            }
                        } catch(e) {
                            if (xhr.responseText) {
                                var responseText = xhr.responseText.substring(0, 200);
                                if (responseText.trim()) {
                                    errorMsg += '\n' + responseText;
                                }
                            }
                        }
                        Swal.fire({
                            icon: 'error',
                            title: 'Error!',
                            text: errorMsg,
                            width: '600px'
                        });
                    },
                    complete: function(xhr, status) {
                        console.log('=== AJAX COMPLETE ===');
                        console.log('Status:', status);
                        console.log('Ready State:', xhr.readyState);
                        console.log('Status Code:', xhr.status);
                    }
                });
            });
            
            // Format on modal shown (to ensure format is applied)
            $('#modalTambah').on('shown.bs.modal', function() {
                // Ensure all fields are enabled when modal is shown
                $('#pembina_id').prop('disabled', false).removeAttr('disabled');
                $('#jabatan').prop('disabled', false).removeAttr('disabled').prop('readonly', false).removeAttr('readonly');
                $('#jumlah_honor').prop('disabled', false).removeAttr('disabled').prop('readonly', false).removeAttr('readonly');
                $('#jumlah_pertemuan').prop('disabled', false).removeAttr('disabled').prop('readonly', false).removeAttr('readonly');
                $('#aktif').prop('disabled', false).removeAttr('disabled');
                
                // Format jumlah_honor if it has value
                var currentValue = $('#jumlah_honor').val();
                if (currentValue && currentValue !== '0' && currentValue !== '') {
                    var unformatted = unformatRupiah(currentValue);
                    var formatted = formatRupiah(unformatted);
                    $('#jumlah_honor').val(formatted);
                    $('#jumlah_honor_hidden').val(unformatted);
                }
            });
            
            $('#modalTambah').on('hidden.bs.modal', function() {
                $('#formHonor')[0].reset();
                $('#honor_id').val('');
                $('#pembina_id').val('');
                $('#jumlah_honor').val('');
                $('#jumlah_honor_hidden').val('0');
                $('#jumlah_pertemuan').val('');
                $('#aktif').prop('checked', true);
                $('#modalTitle').text('Tambah Honor');
            });
        });
    }
    
    initHonor();
})();

</script>

