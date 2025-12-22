// Stisla Template JS Functions

// Only initialize if jQuery is loaded
if (typeof jQuery !== 'undefined' && typeof $ !== 'undefined') {
    $(document).ready(function() {
        // Sidebar toggle - use off() first to prevent multiple bindings
        $(document).off('click', '[data-toggle="sidebar"]').on('click', '[data-toggle="sidebar"]', function(e) {
            e.preventDefault();
            $('.main-sidebar').toggleClass('show');
        });
        
        // Close sidebar when clicking outside on mobile - use namespace to prevent conflicts
        $(document).off('click.stisla').on('click.stisla', function(e) {
            if ($(window).width() <= 768) {
                if (!$(e.target).closest('.main-sidebar, [data-toggle="sidebar"]').length) {
                    $('.main-sidebar').removeClass('show');
                }
            }
        });
        
        // Nice scroll for sidebar - only initialize once
        if ($.fn.niceScroll && $('.main-sidebar').length > 0 && !$('.main-sidebar').getNiceScroll()) {
            $('.main-sidebar').niceScroll({
                cursorcolor: '#6777ef',
                cursorwidth: '5px'
            });
        }
    });
}



