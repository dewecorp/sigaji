// Stisla Template JS Functions

// Only initialize if jQuery is loaded
if (typeof jQuery !== 'undefined' && typeof $ !== 'undefined') {
    $(document).ready(function() {
        // Create overlay for mobile sidebar
        var $overlay = $('<div class="sidebar-overlay"></div>');
        $('body').append($overlay);
        
        // Sidebar toggle function
        function toggleSidebar() {
            var $sidebar = $('.main-sidebar');
            var $content = $('.main-content');
            var isMobile = $(window).width() <= 768;
            
            if (isMobile) {
                // Mobile: toggle show class and overlay
                $sidebar.toggleClass('show');
                $overlay.toggleClass('active');
            } else {
                // Desktop: toggle sidebar-hidden class
                $sidebar.toggleClass('sidebar-hidden');
                $content.toggleClass('sidebar-hidden');
                $overlay.removeClass('active');
            }
        }
        
        // Close sidebar when clicking overlay
        $overlay.on('click', function() {
            $('.main-sidebar').removeClass('show');
            $overlay.removeClass('active');
        });
        
        // Sidebar toggle - use off() first to prevent multiple bindings
        $(document).off('click', '[data-toggle="sidebar"]').on('click', '[data-toggle="sidebar"]', function(e) {
            e.preventDefault();
            e.stopPropagation();
            toggleSidebar();
        });
        
        // Handle window resize to adjust sidebar behavior
        $(window).on('resize', function() {
            var $sidebar = $('.main-sidebar');
            var $content = $('.main-content');
            var isMobile = $(window).width() <= 768;
            
            if (isMobile) {
                // On mobile, remove sidebar-hidden class and use show class instead
                $sidebar.removeClass('sidebar-hidden');
                $content.removeClass('sidebar-hidden');
            } else {
                // On desktop, remove show class (mobile specific) and hide overlay
                $sidebar.removeClass('show');
                $overlay.removeClass('active');
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



