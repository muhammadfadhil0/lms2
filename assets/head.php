<script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
<script src="https://cdn.jsdelivr.net/npm/@tailwindplus/elements@1" type="module"></script>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/dist/tabler-icons.min.css" />
<link rel="preconnect" href="https://rsms.me/">
<link rel="stylesheet" href="https://rsms.me/inter/inter.css">
<!-- Role-based Sidebar Styles -->
<link rel="stylesheet" href="../css/sidebar-roles.css">

<style>
    body {
        font-family: 'Inter', sans-serif;
    }

    .bg-orange {
        background-color: rgb(255, 99, 71);
    }

    .bg-orange-tipis {
        background-color: rgba(255, 99, 71, 0.1);
        backdrop-filter: blur(4px);
    }

    .text-orange {
        color: rgb(255, 99, 71);
    }

    .bg-orange-tipis.rounded-lg {
        border-radius: 0.5rem;
    }

    .tab-btn {
        transition: all 0.2s ease;
    }

    .tab-btn.active {
        border-color: rgb(255, 99, 71) !important;
        color: rgb(255, 99, 71) !important;
    }

    .tab-btn:not(.active) {
        border-color: transparent;
        color: #6b7280;
    }

    .tab-btn:not(.active):hover {
        color: #374151;
    }

    .bg-orange-600 {
        background-color: rgb(234, 88, 63);
    }

    .border-orange-200 {
        border-color: rgba(255, 99, 71, 0.2);
    }

    .ring-orange-500 {
        --tw-ring-color: rgb(255, 99, 71);
    }

    /* Dark mode styles removed per request */
</style>

<!-- Global Notifications Modal -->
<?php 
// Determine correct path for modal-notifications.php based on current directory
$modal_path = '';
if (file_exists('../src/component/modal-notifications.php')) {
    // Called from assets/ directory (normal case)
    $modal_path = '../src/component/modal-notifications.php';
} elseif (file_exists('src/component/modal-notifications.php')) {
    // Called from root directory
    $modal_path = 'src/component/modal-notifications.php';
} elseif (file_exists('../../src/component/modal-notifications.php')) {
    // Called from subdirectory like front/
    $modal_path = '../../src/component/modal-notifications.php';
}

if ($modal_path && file_exists($modal_path)) {
    include $modal_path;
} else {
    // Fallback: try to include with absolute path from document root
    $absolute_path = $_SERVER['DOCUMENT_ROOT'] . '/lms/src/component/modal-notifications.php';
    if (file_exists($absolute_path)) {
        include $absolute_path;
    }
}
?>

<!-- Global Notification Handler Script -->
<script>
// Global notification click handler
document.addEventListener('DOMContentLoaded', function() {
    // Add click event listener for notification icon elements
    document.addEventListener('click', function(e) {
        // Check if clicked element or its parent has the notification trigger classes
        const notificationTrigger = e.target.closest('.p-2.text-gray-400.hover\\:text-gray-600.transition-colors');
        
        if (notificationTrigger) {
            // Check if it's a notification icon (bell icon)
            const bellIcon = notificationTrigger.querySelector('.ti-bell, .ti-bell-filled');
            
            if (bellIcon) {
                e.preventDefault();
                e.stopPropagation();
                
                // Open notifications modal
                if (typeof openNotificationsModal === 'function') {
                    openNotificationsModal();
                } else {
                    console.warn('openNotificationsModal function not found');
                }
            }
        }
    });
    
    // Alternative selector for more specific targeting
    document.addEventListener('click', function(e) {
        // Check for elements with notification-trigger attribute or data attribute
        const triggerElement = e.target.closest('[data-notification-trigger="true"]') || 
                              e.target.closest('.notification-trigger');
        
        if (triggerElement) {
            e.preventDefault();
            e.stopPropagation();
            
            if (typeof openNotificationsModal === 'function') {
                openNotificationsModal();
            } else {
                console.warn('openNotificationsModal function not found');
            }
        }
    });
});

// Global helper function to trigger notifications modal
function triggerNotificationsModal() {
    if (typeof openNotificationsModal === 'function') {
        openNotificationsModal();
    } else {
        console.warn('Notifications modal not available');
    }
}

// Theme management removed. Keep a tiny helper to apply saved font-size early to avoid FOUC.
(function() {
    try {
        const savedFontSize = localStorage.getItem('userFontSize') || '100';
        document.documentElement.style.fontSize = (parseInt(savedFontSize, 10) / 100) + 'rem';
    } catch (e) {
        // ignore
    }
})();
</script>