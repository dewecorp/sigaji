<?php
$page_title = 'Pengguna';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';

$sql = "SELECT * FROM users ORDER BY nama_lengkap";
$users = $conn->query($sql)->fetch_all(MYSQLI_ASSOC);
?>

            <div class="main-content">
                <section class="section">
                    <div class="section-header">
                        <h1>Data Pengguna</h1>
                        <div class="section-header-breadcrumb">
                            <div class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>pages/dashboard.php">Dashboard</a></div>
                            <div class="breadcrumb-item active">Pengguna</div>
                        </div>
                    </div>

                    <div class="section-body">
                        <div class="card">
                            <div class="card-header">
                                <h4>Data Pengguna</h4>
                                <div class="card-header-action">
                                    <button class="btn btn-primary" id="btnTambahPengguna" data-toggle="modal" data-target="#modalTambah">
                                        <i class="fas fa-plus"></i> Tambah Pengguna
                                    </button>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-striped" id="tablePengguna">
                                        <thead>
                                            <tr>
                                                <th>No</th>
                                                <th>Foto</th>
                                                <th>Username</th>
                                                <th>Nama Lengkap</th>
                                                <th>Email</th>
                                                <th>Role</th>
                                                <th>Aksi</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php $no = 1; foreach ($users as $u): 
                                                $foto_user = $u['foto'] ?? 'default.jpg';
                                                $foto_path = __DIR__ . '/../../assets/img/users/' . $foto_user;
                                                $foto_url = BASE_URL . 'assets/img/users/' . $foto_user;
                                                $default_foto_url = BASE_URL . 'assets/img/users/default.jpg';
                                                
                                                if (!file_exists($foto_path) || empty($u['foto'])) {
                                                    $foto_url = $default_foto_url;
                                                }
                                            ?>
                                                <tr>
                                                    <td><?php echo $no++; ?></td>
                                                    <td>
                                                        <img src="<?php echo $foto_url; ?>" alt="Foto User" 
                                                             style="width: 50px; height: 50px; object-fit: cover; border-radius: 50%; border: 2px solid #dee2e6;"
                                                             onerror="this.src='<?php echo $default_foto_url; ?>'">
                                                    </td>
                                                    <td><?php echo htmlspecialchars($u['username']); ?></td>
                                                    <td><?php echo htmlspecialchars($u['nama_lengkap']); ?></td>
                                                    <td><?php echo htmlspecialchars($u['email'] ?? '-'); ?></td>
                                                    <td>
                                                        <span class="badge badge-<?php echo $u['role'] == 'admin' ? 'danger' : 'info'; ?>">
                                                            <?php echo ucfirst($u['role']); ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <button class="btn btn-sm btn-info" onclick="editPengguna(<?php echo $u['id']; ?>)" data-toggle="tooltip" title="Edit">
                                                            <i class="fas fa-edit"></i>
                                                        </button>
                                                        <?php 
                                                        // Tampilkan tombol hapus jika:
                                                        // 1. Bukan akun sendiri (tidak bisa hapus diri sendiri)
                                                        // 2. Bukan administrator (admin tidak bisa dihapus)
                                                        // Bendahara bisa dihapus oleh admin atau user lain
                                                        if ($u['id'] != $_SESSION['user_id'] && $u['role'] != 'admin'): 
                                                        ?>
                                                            <button class="btn btn-sm btn-danger" onclick="confirmDelete('<?php echo BASE_URL; ?>pages/pengguna/delete.php?id=<?php echo $u['id']; ?>')" data-toggle="tooltip" title="Hapus">
                                                                <i class="fas fa-trash"></i>
                                                            </button>
                                                        <?php endif; ?>
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
                            <h5 class="modal-title" id="modalTitle">Tambah Pengguna</h5>
                            <button type="button" class="close" data-dismiss="modal">
                                <span>&times;</span>
                            </button>
                        </div>
                        <form id="formPengguna" method="POST" action="save.php" enctype="multipart/form-data">
                            <input type="hidden" name="id" id="user_id">
                            <div class="modal-body">
                                <div class="form-group">
                                    <label>Username <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="username" id="username" required>
                                </div>
                                <div class="form-group">
                                    <label>Password <span class="text-danger" id="password_label">*</span></label>
                                    <div class="input-group">
                                        <input type="password" class="form-control" name="password" id="password" required>
                                        <div class="input-group-append">
                                            <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                                <i class="fas fa-eye" id="togglePasswordIcon"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <small class="text-muted" id="password_help" style="display: none;">Kosongkan jika tidak ingin mengubah password</small>
                                </div>
                                <div class="form-group">
                                    <label>Nama Lengkap <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="nama_lengkap" id="nama_lengkap" required>
                                </div>
                                <div class="form-group">
                                    <label>Email</label>
                                    <input type="email" class="form-control" name="email" id="email">
                                </div>
                                <div class="form-group">
                                    <label>Role</label>
                                    <select class="form-select" name="role" id="role">
                                        <option value="bendahara">Bendahara</option>
                                        <option value="admin">Admin</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Foto</label>
                                    <input type="file" class="form-control" name="foto" id="foto" accept="image/*">
                                    <div id="previewFoto" class="mt-2"></div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                                <button type="button" class="btn btn-warning" id="btnResetForm" onclick="resetFormPengguna()">
                                    <i class="fas fa-redo"></i> Reset
                                </button>
                                <button type="submit" class="btn btn-primary">Simpan</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

<script>
// Global function for toggle password - available immediately
function togglePasswordVisibility() {
    var passwordInput = document.getElementById('password');
    var passwordIcon = document.getElementById('togglePasswordIcon');
    
    if (passwordInput && passwordIcon) {
        if (passwordInput.type === 'password') {
            passwordInput.type = 'text';
            passwordIcon.classList.remove('fa-eye');
            passwordIcon.classList.add('fa-eye-slash');
        } else {
            passwordInput.type = 'password';
            passwordIcon.classList.remove('fa-eye-slash');
            passwordIcon.classList.add('fa-eye');
        }
    }
}

// Global function to reset form
function resetFormPengguna() {
    var form = document.getElementById('formPengguna');
    var userId = document.getElementById('user_id');
    var previewFoto = document.getElementById('previewFoto');
    var password = document.getElementById('password');
    var togglePasswordIcon = document.getElementById('togglePasswordIcon');
    var passwordLabel = document.getElementById('password_label');
    var passwordHelp = document.getElementById('password_help');
    var modalTitle = document.getElementById('modalTitle');
    
    if (form) {
        form.reset();
    }
    
    if (userId) {
        userId.value = '';
    }
    
    if (previewFoto) {
        previewFoto.innerHTML = '';
    }
    
    if (password) {
        password.value = '';
        password.setAttribute('type', 'password');
        password.setAttribute('required', 'required');
    }
    
    if (togglePasswordIcon) {
        togglePasswordIcon.classList.remove('fa-eye-slash');
        togglePasswordIcon.classList.add('fa-eye');
    }
    
    if (passwordLabel) {
        passwordLabel.textContent = '*';
    }
    
    if (passwordHelp) {
        passwordHelp.style.display = 'none';
    }
    
    if (modalTitle) {
        modalTitle.textContent = 'Tambah Pengguna';
    }
    
    // Show success message with SweetAlert if available
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            icon: 'success',
            title: 'Form Direset',
            text: 'Semua field telah direset',
            timer: 1500,
            showConfirmButton: false
        });
    }
}

// Wait for jQuery for edit function
(function() {
    function initEditFunction() {
        if (typeof jQuery === 'undefined') {
            setTimeout(initEditFunction, 50);
            return;
        }
        
        var $ = jQuery;
        
        // Make editPengguna available globally
        window.editPengguna = function(id) {
            $.ajax({
                url: 'get.php?id=' + id,
                type: 'GET',
                dataType: 'json',
                success: function(data) {
                    $('#user_id').val(data.id);
                    $('#username').val(data.username);
                    $('#nama_lengkap').val(data.nama_lengkap);
                    $('#email').val(data.email);
                    $('#role').val(data.role);
                    
                    $('#password').val('').removeAttr('required');
                    $('#password_label').text('');
                    $('#password_help').show();
                    $('#togglePasswordIcon').removeClass('fa-eye-slash').addClass('fa-eye');
                    
                    if (data.foto) {
                        $('#previewFoto').html('<img src="<?php echo BASE_URL; ?>assets/img/users/' + data.foto + '" width="100" class="img-thumbnail">');
                    } else {
                        $('#previewFoto').html('');
                    }
                    
                    $('#modalTitle').text('Edit Pengguna');
                    $('#modalTambah').modal('show');
                }
            });
        };
        
        // Reset form when modal is closed
        $('#modalTambah').on('hidden.bs.modal', function () {
            $('#formPengguna')[0].reset();
            $('#user_id').val('');
            $('#previewFoto').html('');
            $('#password').attr('required', 'required').attr('type', 'password');
            $('#togglePasswordIcon').removeClass('fa-eye-slash').addClass('fa-eye');
            $('#password_label').text('*');
            $('#password_help').hide();
            $('#modalTitle').text('Tambah Pengguna');
        });
        
        // Reset when button Tambah Pengguna is clicked
        $(document).on('click', '#btnTambahPengguna', function() {
            $('#formPengguna')[0].reset();
            $('#user_id').val('');
            $('#previewFoto').html('');
            $('#password').attr('required', 'required').attr('type', 'password');
            $('#togglePasswordIcon').removeClass('fa-eye-slash').addClass('fa-eye');
            $('#password_label').text('*');
            $('#password_help').hide();
            $('#modalTitle').text('Tambah Pengguna');
        });
        
        // Reset button handler (jQuery backup)
        $(document).on('click', '#btnResetForm', function(e) {
            e.preventDefault();
            resetFormPengguna();
        });
        
        // Reset when modal is opened for add
        $('#modalTambah').on('show.bs.modal', function () {
            var userId = $('#user_id').val();
            if (!userId || userId === '') {
                $('#formPengguna')[0].reset();
                $('#user_id').val('');
                $('#previewFoto').html('');
                $('#password').attr('required', 'required').attr('type', 'password');
                $('#togglePasswordIcon').removeClass('fa-eye-slash').addClass('fa-eye');
                $('#password_label').text('*');
                $('#password_help').hide();
                $('#modalTitle').text('Tambah Pengguna');
            }
        });
    }
    
    initEditFunction();
})();

// Wait for jQuery to be loaded
(function() {
    var retryCount = 0;
    var maxRetries = 20; // Maximum 20 retries (1 second)
    
    function initPenggunaPage() {
        if (typeof jQuery === 'undefined') {
            retryCount++;
            if (retryCount < maxRetries) {
                setTimeout(initPenggunaPage, 50);
            } else {
                console.error('jQuery failed to load after ' + maxRetries + ' retries');
            }
            return;
        }
        
        // Reset retry count on success
        retryCount = 0;
        
        var $ = jQuery;
        
        $(document).ready(function() {
            // Toggle password visibility - also use event delegation for dynamic elements
            $(document).on('click', '#togglePassword', function(e) {
                e.preventDefault();
                togglePasswordVisibility();
            });
            
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
            if ($.fn.DataTable.isDataTable('#tablePengguna')) {
                $('#tablePengguna').DataTable().destroy();
            }
            
            // Small delay to ensure cleanup is complete
            setTimeout(function() {
                var table = $('#tablePengguna').DataTable({
                    dom: 'Bfrtip',
                    buttons: [
                        { extend: 'excel', text: '<i class="fas fa-file-excel"></i> Excel', className: 'btn btn-success btn-sm' },
                        { extend: 'pdf', text: '<i class="fas fa-file-pdf"></i> PDF', className: 'btn btn-danger btn-sm' },
                        { extend: 'print', text: '<i class="fas fa-print"></i> Print', className: 'btn btn-info btn-sm' }
                    ],
                    language: { url: 'https://cdn.datatables.net/plug-ins/1.11.5/i18n/id.json' },
                    order: [[2, 'asc']], // Sort by nama_lengkap (column index 2)
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
            }, 100);
        });
    }
    
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initPenggunaPage);
    } else {
        initPenggunaPage();
    }
})();
</script>

<style>
/* Input group styling untuk menyatukan toggle dengan field */
.input-group {
    display: flex;
    width: 100%;
}

.input-group .form-control {
    border-top-right-radius: 0;
    border-bottom-right-radius: 0;
    border-right: none;
}

.input-group .form-control:focus {
    border-right: none;
    box-shadow: none;
}

.input-group-append {
    display: flex;
}

.input-group-append .btn {
    border-top-left-radius: 0;
    border-bottom-left-radius: 0;
    border-left: 1px solid #ced4da;
    padding: 0.375rem 0.75rem;
}

.input-group .form-control:focus + .input-group-append .btn {
    border-color: #80bdff;
}

/* Password toggle button styling */
#togglePassword {
    cursor: pointer;
}

#togglePassword:hover {
    background-color: #e9ecef;
}
</style>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>


