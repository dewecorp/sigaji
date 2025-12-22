            </div>
        </div>
    </div>

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
            toastr.success('<?php echo addslashes($_SESSION['success']); ?>');
        </script>
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['error']) && !$skip_footer_alerts): ?>
        <script>
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: '<?php echo addslashes($_SESSION['error']); ?>',
                timer: 3000,
                showConfirmButton: false
            });
            toastr.error('<?php echo addslashes($_SESSION['error']); ?>');
        </script>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>
    
    
    <!-- Footer -->
    <footer class="main-footer" style="background-color: #f7f7f7; padding: 25px 0; margin-top: 50px; border-top: 1px solid #e0e0e0;">
        <div class="container">
            <div class="text-center">
                <div style="margin-bottom: 12px;">
                    <span style="color: #000; font-size: 16px; font-weight: 500;">&copy; <?php echo date('Y'); ?></span>
                    <span style="color: #667eea; font-size: 16px; font-weight: 600; margin-left: 5px;">Madrasah Ibtidaiyah Sultan Fattah Sukosono</span>
                </div>
                <div style="color: #666; font-size: 14px; font-weight: 500;">
                    Sistem Informasi Gaji | Versi <?php echo APP_VERSION; ?> | Dibuat dengan <span style="color: #e74c3c;">❤️</span>
                </div>
            </div>
        </div>
        <div style="height: 2px; background-color: #333; margin-top: 20px;"></div>
    </footer>
</body>
</html>

