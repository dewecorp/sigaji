            </div>
        </div>
    </div>

    <?php
    if (!function_exists('csrfToken')) {
        function csrfToken($key = 'default') {
            if (empty($_SESSION['csrf_tokens']) || !is_array($_SESSION['csrf_tokens'])) {
                $_SESSION['csrf_tokens'] = [];
            }

            if (empty($_SESSION['csrf_tokens'][$key])) {
                if (function_exists('random_bytes')) {
                    $_SESSION['csrf_tokens'][$key] = bin2hex(random_bytes(32));
                } elseif (function_exists('openssl_random_pseudo_bytes')) {
                    $_SESSION['csrf_tokens'][$key] = bin2hex(openssl_random_pseudo_bytes(32));
                } else {
                    $_SESSION['csrf_tokens'][$key] = md5(uniqid(mt_rand(), true)) . md5(uniqid(mt_rand(), true));
                }
            }

            return $_SESSION['csrf_tokens'][$key];
        }
    }
    ?>

    <!-- General JS Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.7/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.nicescroll/3.7.6/jquery.nicescroll.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.24.0/moment.min.js"></script>
    
    <!-- JS Libraries -->
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap4.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.2.2/js/dataTables.buttons.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.2.2/js/buttons.bootstrap4.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.2.2/js/buttons.html5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.2.2/js/buttons.print.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    
    <!-- Template JS -->
    <script src="<?php echo BASE_URL; ?>assets/js/stisla.js"></script>
    <script src="<?php echo BASE_URL; ?>assets/js/scripts.js"></script>
    <script src="<?php echo BASE_URL; ?>assets/js/main.js"></script>
    
    <!-- Custom JS -->
    <?php if (isset($page_scripts)): ?>
        <?php foreach ($page_scripts as $script): ?>
            <script src="<?php echo BASE_URL . $script; ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>
    
    <!-- CSRF Token for JavaScript -->
    <script>
        window.CSRF_TOKEN = '<?php echo htmlspecialchars(csrfToken(), ENT_QUOTES, 'UTF-8'); ?>';
    </script>

    <!-- Logout Confirmation -->
    <script>
        function confirmLogout(event) {
            event.preventDefault();
            Swal.fire({
                title: 'Konfirmasi Logout',
                text: 'Apakah Anda yakin ingin logout?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Ya, Logout',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = '<?php echo BASE_URL; ?>logout.php';
                }
            });
        }

        function confirmUpdate(event) {
            event.preventDefault();
            Swal.fire({
                title: 'Konfirmasi Update Sistem',
                html: 'Anda akan memperbarui sistem dari paket ZIP GitHub.<br><strong>Pastikan:</strong><ul style="text-align: left; margin-top: 10px; margin-bottom: 0;"><li>Koneksi internet hosting stabil</li><li>Folder aplikasi bisa ditulis oleh PHP</li><li>Extension PHP ZipArchive aktif</li></ul>',
                icon: 'info',
                showCancelButton: true,
                confirmButtonColor: '#0f766e',
                cancelButtonColor: '#dc3545',
                confirmButtonText: 'Lanjutkan Update',
                cancelButtonText: 'Batal',
                showLoaderOnConfirm: true,
                preConfirm: () => {
                    return fetch('<?php echo BASE_URL; ?>pages/pengaturan/ajax_update.php', {
                        method: 'POST',
                        credentials: 'same-origin',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8'
                        },
                        body: new URLSearchParams({
                            csrf_token: '<?php echo htmlspecialchars(csrfToken('system_update'), ENT_QUOTES, 'UTF-8'); ?>'
                        }).toString()
                    })
                    .then(response => {
                        if (!response.ok) {
                            throw new Error(response.statusText);
                        }
                        return response.json();
                    })
                    .catch(error => {
                        Swal.showValidationMessage(`Gagal menghubungi server: ${error}`);
                    });
                },
                allowOutsideClick: () => !Swal.isLoading()
            }).then((result) => {
                if (result.isConfirmed) {
                    if (result.value.success) {
                        let message = result.value.message;
                        let isUpToDate = message.toLowerCase().includes('already up to date');
                        
                        Swal.fire({
                            icon: isUpToDate ? 'info' : 'success',
                            title: isUpToDate ? 'Sistem Sudah Versi Terbaru!' : 'Update Berhasil',
                            text: isUpToDate ? 'Sistem Anda sudah menggunakan versi terbaru dari GitHub.' : 'Sistem telah berhasil diperbarui ke versi terbaru!',
                        }).then(() => {
                            if (!isUpToDate) {
                                window.location.reload();
                            }
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Update Gagal',
                            text: result.value.message || 'Gagal memperbarui sistem. Silakan coba lagi nanti.',
                        });
                    }
                }
            });
        }

        // Global confirmDelete with CSRF protection (POST instead of GET)
        window.confirmDelete = function(url) {
            if (typeof Swal === 'undefined') {
                if (confirm('Apakah Anda yakin ingin menghapus data ini?')) {
                    var form = document.createElement('form');
                    form.method = 'POST';
                    form.action = url;
                    var input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'csrf_token';
                    input.value = window.CSRF_TOKEN || '';
                    form.appendChild(input);
                    document.body.appendChild(form);
                    form.submit();
                }
                return;
            }
            Swal.fire({
                title: 'Apakah Anda yakin?',
                text: "Data yang dihapus tidak dapat dikembalikan!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Ya, Hapus!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    var form = document.createElement('form');
                    form.method = 'POST';
                    form.action = url;
                    var input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'csrf_token';
                    input.value = window.CSRF_TOKEN || '';
                    form.appendChild(input);
                    document.body.appendChild(form);
                    form.submit();
                }
            });
        };
    </script>
    
    <!-- Notification Handler -->
    <?php 
    // Skip alerts if backup page has already handled them
    $skip_footer_alerts = isset($_SESSION['backup_page_handled']) && $_SESSION['backup_page_handled'];
    if ($skip_footer_alerts) {
        unset($_SESSION['backup_page_handled']);
    }
    ?>
    <?php if (isset($_SESSION['success']) && !$skip_footer_alerts): ?>
        <script>
            Swal.fire({
                icon: 'success',
                title: 'Berhasil',
                text: '<?php echo addslashes($_SESSION['success']); ?>',
                timer: 3000,
                showConfirmButton: false
            });
        </script>
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['error']) && !$skip_footer_alerts): ?>
        <script>
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: '<?php echo addslashes($_SESSION['error']); ?>',
                confirmButtonColor: '#6777ef',
                confirmButtonText: 'OK'
            });
        </script>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>
    
    
    <!-- Footer -->
    <footer class="main-footer" style="background-color: #f7f7f7; padding: 25px 0; margin-top: 50px; border-top: 1px solid #e0e0e0;">
        <div class="container">
            <div class="text-center">
                <div style="margin-bottom: 12px;">
                    <span style="color: #000; font-weight: 500; font-size: 13px;">&copy; <?php echo date('Y'); ?></span>
                    <span style="color: #667eea; font-weight: 600; margin-left: 5px; font-size: 13px;">Madrasah Ibtidaiyah Sultan Fattah Sukosono</span>
                </div>
                <div style="color: #666; font-weight: 500; font-size: 12px;">
                    Sistem Informasi Gaji | Versi <?php echo $APP_VERSION ?? APP_VERSION; ?> | Dibuat dengan <span style="color: #e74c3c;">❤️</span>
                </div>
            </div>
        </div>
        <div style="height: 2px; background-color: #333; margin-top: 20px;"></div>
    </footer>
</body>
</html>
