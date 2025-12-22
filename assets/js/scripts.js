// Additional Scripts

// Initialize tooltips - only if jQuery is loaded
if (typeof jQuery !== 'undefined' && typeof $ !== 'undefined') {
    $(document).ready(function() {
        // Initialize tooltips only once
        if ($('[data-toggle="tooltip"]').length > 0 && !$('[data-toggle="tooltip"]').data('bs.tooltip')) {
            $('[data-toggle="tooltip"]').tooltip();
        }
    });
}

// Form validation
function validateForm(formId) {
    const form = document.getElementById(formId);
    if (!form) return false;
    if (form.checkValidity() === false) {
        if (typeof event !== 'undefined') {
            event.preventDefault();
            event.stopPropagation();
        }
    }
    form.classList.add('was-validated');
    return form.checkValidity();
}

// Number format for currency inputs - only if jQuery is loaded
if (typeof jQuery !== 'undefined' && typeof $ !== 'undefined') {
    $(document).ready(function() {
        // Use event delegation to prevent multiple bindings
        $(document).off('input', '.currency-input').on('input', '.currency-input', function() {
            let value = $(this).val().replace(/[^\d]/g, '');
            $(this).val(value);
        });

        // Format currency on blur
        $(document).off('blur', '.currency-input').on('blur', '.currency-input', function() {
            let value = parseInt($(this).val()) || 0;
            $(this).val(value.toLocaleString('id-ID'));
        });
    });
}



