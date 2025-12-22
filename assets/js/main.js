// Main JavaScript for SIGaji

// Store clock interval ID to prevent multiple intervals
var clockInterval = null;

// Initialize DataTables - only initialize once
var datatablesInitialized = false;
var mainJsInitialized = false;

// Update clock function with modern display - define early
function updateClock() {
    // Check if jQuery is available
    if (typeof jQuery === 'undefined' || typeof $ === 'undefined') {
        return;
    }
    
    // Check if element exists before updating
    var $clockElement = $('#current-datetime');
    if ($clockElement.length === 0) {
        return;
    }
    
    try {
        var now = new Date();
        
        // Format date (Indonesian)
        var days = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
        var months = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 
                       'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
        
        var dayName = days[now.getDay()];
        var day = now.getDate();
        var month = months[now.getMonth()];
        var year = now.getFullYear();
        
        // Format time
        var hours = String(now.getHours()).padStart(2, '0');
        var minutes = String(now.getMinutes()).padStart(2, '0');
        var seconds = String(now.getSeconds()).padStart(2, '0');
        
        // Create modern HTML format with better structure
        var datePart = '<span class="date-part"><span class="day-name">' + dayName + '</span>, ' + day + ' ' + month + ' ' + year + '</span>';
        var timePart = '<span class="time-part">' + hours + '<span class="separator">:</span>' + minutes + '<span class="separator">:</span>' + seconds + '</span>';
        
        $clockElement.html(datePart + timePart);
    } catch (error) {
        console.error('Error updating clock:', error);
    }
}

// Make updateClock globally accessible
window.updateClock = updateClock;

// Only run once - prevent multiple initializations using window object
if (typeof window.mainJsInitialized === 'undefined') {
    window.mainJsInitialized = true;
    
    // Wait for jQuery to be available
    (function waitForJQuery() {
        var retryCount = 0;
        var maxRetries = 20;
        
        function checkJQuery() {
            if (typeof jQuery === 'undefined' || typeof $ === 'undefined') {
                retryCount++;
                if (retryCount < maxRetries) {
                    setTimeout(checkJQuery, 50);
                } else {
                    console.error('jQuery failed to load in main.js after ' + maxRetries + ' retries');
                }
                return;
            }
            
            // Only run ready handler once
            if (typeof window.mainJsReadyExecuted === 'undefined') {
                window.mainJsReadyExecuted = true;
                
                $(document).ready(function() {
                    // Auto-initialize all tables with class 'datatable' - only once
                    if ($.fn.DataTable && !datatablesInitialized) {
                        $('.datatable').each(function() {
                            var $table = $(this);
                            // Only initialize if not already initialized
                            if (!$.fn.DataTable.isDataTable($table)) {
                                try {
                                    $table.DataTable({
                                        "language": {
                                            "url": "https://cdn.datatables.net/plug-ins/1.11.5/i18n/id.json"
                                        },
                                        "responsive": true,
                                        "pageLength": 25,
                                        "order": [[0, "asc"]],
                                        "stateSave": false
                                    });
                                } catch (e) {
                                    console.error('Error initializing DataTable:', e);
                                }
                            }
                        });
                        datatablesInitialized = true;
                    }
                    
                    // Real-time clock - ensure it runs continuously
                    // Clear any existing interval first to prevent duplicates
                    if (clockInterval !== null) {
                        clearInterval(clockInterval);
                        clockInterval = null;
                    }
                    if (window.clockInterval !== null && window.clockInterval !== undefined) {
                        clearInterval(window.clockInterval);
                        window.clockInterval = null;
                    }
                    
                    // Initial update immediately
                    updateClock();
                    
                    // Set interval and store ID - update every second
                    clockInterval = setInterval(function() {
                        updateClock();
                    }, 1000);
                    
                    // Store interval ID globally to prevent conflicts
                    window.clockInterval = clockInterval;
                });
            }
        }
        
        checkJQuery();
    })();
}


// Toastr configuration
toastr.options = {
    "closeButton": true,
    "debug": false,
    "newestOnTop": true,
    "progressBar": true,
    "positionClass": "toast-top-right",
    "preventDuplicates": false,
    "onclick": null,
    "showDuration": "300",
    "hideDuration": "1000",
    "timeOut": "5000",
    "extendedTimeOut": "1000",
    "showEasing": "swing",
    "hideEasing": "linear",
    "showMethod": "fadeIn",
    "hideMethod": "fadeOut"
};

// Show toast notification
function showToast(type, message, title = '') {
    toastr[type](message, title);
}

// Confirm delete with SweetAlert
function confirmDelete(url, message = 'Apakah Anda yakin ingin menghapus data ini?') {
    Swal.fire({
        title: 'Konfirmasi Hapus',
        text: message,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Ya, Hapus!',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = url;
        }
    });
}

// Format currency input
function formatCurrency(input) {
    let value = input.value.replace(/[^\d]/g, '');
    input.value = value;
}

// Format currency display
function formatCurrencyDisplay(value) {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0
    }).format(value);
}

// Export to Excel
function exportExcel(tableId, filename) {
    const table = document.getElementById(tableId);
    const wb = XLSX.utils.table_to_book(table, {sheet: "Sheet1"});
    XLSX.writeFile(wb, filename + '.xlsx');
}

// Export to PDF
function exportPDF(tableId, filename, title) {
    const element = document.getElementById(tableId);
    html2pdf()
        .set({
            margin: 1,
            filename: filename + '.pdf',
            image: { type: 'jpeg', quality: 0.98 },
            html2canvas: { scale: 2 },
            jsPDF: { unit: 'in', format: 'a4', orientation: 'portrait' }
        })
        .from(element)
        .save();
}

// Print table
function printTable(tableId, title) {
    const printWindow = window.open('', '_blank');
    const table = document.getElementById(tableId).cloneNode(true);
    
    printWindow.document.write(`
        <html>
            <head>
                <title>${title}</title>
                <style>
                    body { font-family: Arial, sans-serif; }
                    table { width: 100%; border-collapse: collapse; }
                    th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                    th { background-color: #f2f2f2; }
                </style>
            </head>
            <body>
                <h2>${title}</h2>
                ${table.outerHTML}
            </body>
        </html>
    `);
    printWindow.document.close();
    printWindow.print();
}

// Auto-hide alerts - only if jQuery is loaded
if (typeof jQuery !== 'undefined' && typeof $ !== 'undefined') {
    $(document).ready(function() {
        setTimeout(function() {
            if ($('.alert').length > 0) {
                $('.alert').fadeOut('slow');
            }
        }, 5000);
    });
}


