<el-dialog>
    <dialog id="notifications-modal" aria-labelledby="notifications-dialog-title" class="fixed inset-0 size-auto max-h-none max-w-none overflow-y-auto bg-transparent backdrop:bg-transparent">
        <el-dialog-backdrop class="fixed inset-0 bg-gray-500/75 transition-opacity data-closed:opacity-0 data-enter:duration-300 data-enter:ease-out data-leave:duration-200 data-leave:ease-in"></el-dialog-backdrop>

        <div tabindex="0" class="flex min-h-full items-end justify-center p-4 text-center focus:outline-none sm:items-center sm:p-0">
            <el-dialog-panel class="relative w-full max-w-2xl transform overflow-hidden rounded-xl bg-white text-left shadow-xl transition-all data-closed:translate-y-4 data-closed:opacity-0 data-enter:duration-300 data-enter:ease-out data-leave:duration-200 data-leave:ease-in sm:my-8 data-closed:sm:translate-y-0 data-closed:sm:scale-95">
                <!-- Modal Header -->
                <div class="bg-white px-4 pt-5 pb-4 sm:px-6 sm:pt-6 sm:pb-4 border-b border-gray-200">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <div class="flex items-center justify-center w-10 h-10 rounded-full bg-orange-100 mr-3">
                                <i class="ti ti-bell text-lg text-orange-600"></i>
                            </div>
                            <div>
                                <h3 id="notifications-dialog-title" class="text-lg font-semibold text-gray-900">Pemberitahuan</h3>
                                <p class="text-sm text-gray-500">Kelola semua notifikasi Anda</p>
                            </div>
                        </div>
                        <button type="button" onclick="closeNotificationsModal()" class="text-gray-400 hover:text-gray-600">
                            <i class="ti ti-x text-xl"></i>
                        </button>
                    </div>
                </div>

                <!-- Modal Actions -->
                <div class="bg-gray-50 px-4 py-3 sm:px-6 border-b border-gray-200">
                    <div class="flex flex-wrap gap-2 justify-between items-center">
                        <div class="flex gap-2">
                            <button type="button" onclick="markAllNotificationsRead()" 
                                class="inline-flex items-center px-3 py-1.5 bg-blue-600 text-white text-sm font-medium rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <i class="ti ti-check-all mr-1 text-sm"></i>
                                Tandai Semua Dibaca
                            </button>
                            <button type="button" onclick="deleteReadNotifications()" 
                                class="inline-flex items-center px-3 py-1.5 bg-red-600 text-white text-sm font-medium rounded-md hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500">
                                <i class="ti ti-trash mr-1 text-sm"></i>
                                Hapus yang Dibaca
                            </button>
                        </div>
                        <div class="text-sm text-gray-500">
                            <span id="notifications-count">0</span> notifikasi
                        </div>
                    </div>
                </div>

                <!-- Modal Content -->
                <div class="bg-white max-h-96 overflow-y-auto">
                    <!-- Loading State -->
                    <div id="notifications-loading" class="hidden p-6 text-center">
                        <div class="inline-flex items-center">
                            <div class="animate-spin rounded-full h-6 w-6 border-b-2 border-orange-600 mr-3"></div>
                            <span class="text-gray-600">Memuat notifikasi...</span>
                        </div>
                    </div>

                    <!-- Notifications List -->
                    <div id="notifications-list" class="divide-y divide-gray-100">
                        <!-- Notifications will be loaded here via JavaScript -->
                    </div>

                    <!-- Empty State -->
                    <div id="notifications-empty" class="hidden p-8 text-center">
                        <div class="w-16 h-16 mx-auto mb-4 bg-gray-100 rounded-full flex items-center justify-center">
                            <i class="ti ti-bell-off text-2xl text-gray-400"></i>
                        </div>
                        <h3 class="text-lg font-medium text-gray-900 mb-2">Tidak ada notifikasi</h3>
                        <p class="text-sm text-gray-500">Semua notifikasi akan muncul di sini</p>
                    </div>
                </div>

                <!-- Modal Footer -->
                <div class="bg-gray-50 px-4 py-3 sm:px-6 border-t border-gray-100">
                    <div class="flex justify-end">
                        <button type="button" onclick="closeNotificationsModal()" 
                            class="inline-flex justify-center items-center rounded-md px-4 py-2 bg-gray-200 text-sm font-medium text-gray-700 hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-gray-500">
                            Tutup
                        </button>
                    </div>
                    <p class="mt-2 text-center text-xs text-gray-400">Tap area selain modal untuk tutup</p>
                </div>
            </el-dialog-panel>
        </div>
    </dialog>
</el-dialog>

<!-- Notification Item Template (Hidden, used by JavaScript) -->
<template id="notification-item-template">
    <div class="notification-item p-4 hover:bg-gray-50 cursor-pointer" data-notification-id="">
        <div class="flex items-start space-x-3">
            <!-- Notification Icon -->
            <div class="flex-shrink-0 mt-0.5">
                <i class="notification-icon text-lg"></i>
            </div>
            
            <!-- Notification Content -->
            <div class="flex-1 min-w-0">
                <div class="flex items-start justify-between">
                    <div class="flex-1">
                        <h4 class="notification-title text-sm font-medium text-gray-900 mb-1"></h4>
                        <p class="notification-message text-sm text-gray-600 mb-2"></p>
                        <div class="flex items-center text-xs text-gray-500">
                            <span class="notification-time mr-3"></span>
                            <span class="notification-class"></span>
                        </div>
                    </div>
                    
                    <!-- Actions -->
                    <div class="flex items-center space-x-2 ml-3">
                        <!-- Unread indicator -->
                        <div class="unread-indicator w-2 h-2 bg-orange-500 rounded-full"></div>
                        
                        <!-- Actions dropdown -->
                        <div class="relative">
                            <button type="button" class="notification-actions-btn text-gray-400 hover:text-gray-600 p-1" onclick="toggleNotificationActions(this)">
                                <i class="ti ti-dots-vertical text-sm"></i>
                            </button>
                            <div class="notification-actions-menu hidden absolute right-0 mt-1 w-32 bg-white rounded-md shadow-lg border border-gray-200 z-10">
                                <button type="button" onclick="markNotificationRead(this)" class="mark-read-btn block w-full text-left px-3 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                    <i class="ti ti-check mr-2"></i>Tandai Dibaca
                                </button>
                                <button type="button" onclick="deleteNotification(this)" class="block w-full text-left px-3 py-2 text-sm text-red-600 hover:bg-red-50">
                                    <i class="ti ti-trash mr-2"></i>Hapus
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
// Global variables for notifications
let notificationsModal = null;
let notificationLogic = null;

// Determine API base path based on current location
function getApiBasePath() {
    const currentPath = window.location.pathname;
    
    // If we're in front/ directory, API is in ../logic/
    if (currentPath.includes('/front/')) {
        return '../logic/';
    }
    // If we're in root or other directory, try src/logic/
    else if (currentPath.includes('/lms/')) {
        return 'src/logic/';
    }
    // Default fallback
    return '../logic/';
}

// Alternative function to try multiple paths
async function fetchNotificationsWithFallback() {
    const possiblePaths = [
        '../logic/get-notifications.php',
        'src/logic/get-notifications.php',
        '/lms/src/logic/get-notifications.php'
    ];
    
    for (const path of possiblePaths) {
        try {
            console.log('🔗 Trying path:', path);
            const response = await fetch(path);
            
            if (response.ok) {
                console.log('✅ Success with path:', path);
                return response;
            }
        } catch (error) {
            console.log('❌ Failed with path:', path, error);
        }
    }
    
    throw new Error('All notification API paths failed');
}

// Initialize notification modal when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    // Initialize notification functionality
    initializeNotificationModal();
});

function initializeNotificationModal() {
    notificationsModal = document.getElementById('notifications-modal');
    
    // Add click outside to close
    notificationsModal.addEventListener('click', function(e) {
        if (e.target === notificationsModal) {
            closeNotificationsModal();
        }
    });
}

// Open notifications modal
function openNotificationsModal() {
    if (!notificationsModal) return;
    
    notificationsModal.showModal();
    loadAllNotifications();
    
    // Focus trap
    const focusableElements = notificationsModal.querySelectorAll('button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])');
    if (focusableElements.length > 0) {
        focusableElements[0].focus();
    }
}

// Close notifications modal
function closeNotificationsModal() {
    if (notificationsModal) {
        notificationsModal.close();
    }
}

// Load all notifications
async function loadAllNotifications() {
    const loadingEl = document.getElementById('notifications-loading');
    const listEl = document.getElementById('notifications-list');
    const emptyEl = document.getElementById('notifications-empty');
    const countEl = document.getElementById('notifications-count');
    
    console.log('🔍 Modal DOM elements:', {
        loadingEl, listEl, emptyEl, countEl
    });

    // Clear previous content
    if (listEl) listEl.innerHTML = '';
    if (emptyEl) emptyEl.classList.add('hidden');

    // First check if we have cached notifications from beranda
    if (window.berandaNotificationsCache && window.berandaNotificationsCache.length > 0) {
        console.log('� Modal: Using cached notifications from beranda');
        const cachedNotifications = window.berandaNotificationsCache;
        
        if (countEl) countEl.textContent = cachedNotifications.length;
        
        if (listEl) {
            cachedNotifications.forEach((notification, index) => {
                console.log(`📝 Modal (cached): Processing notification ${index + 1}:`, notification.title);
                const notificationEl = createNotificationElement(notification);
                listEl.appendChild(notificationEl);
            });
        }
        
        if (loadingEl) loadingEl.classList.add('hidden');
        return; // Exit early with cached data
    }

    // Show loading state only if no cache available
    if (loadingEl) loadingEl.classList.remove('hidden');

    try {
        console.log('🚀 Modal: No cache available, loading from API...');
        
        const response = await fetch('../logic/get-notifications.php', {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            credentials: 'same-origin'
        });
        
        console.log('📡 Modal Response status:', response.status);
        console.log('📡 Modal Response type:', response.type);
        
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }
        
        const responseText = await response.text();
        console.log('� Modal Raw response (first 200 chars):', responseText.substring(0, 200));
        
        // Check if response looks like HTML (error page)
        if (responseText.trim().startsWith('<')) {
            console.error('❌ Modal: Received HTML instead of JSON');
            console.log('🔍 Modal: Full HTML response:', responseText);
            throw new Error('Received HTML response instead of JSON');
        }
        
        let data;
        try {
            data = JSON.parse(responseText);
        } catch (parseError) {
            console.error('❌ Modal JSON Parse Error:', parseError);
            console.log('❌ Modal: Failed to parse:', responseText);
            throw parseError;
        }
        
        console.log('✅ Modal: Successfully parsed data:', data);

        if (data.success && Array.isArray(data.notifications)) {
            const notifications = data.notifications;
            console.log(`📋 Modal: Found ${notifications.length} notifications`);
            
            if (countEl) countEl.textContent = notifications.length;
            
            if (notifications.length > 0) {
                notifications.forEach((notification, index) => {
                    console.log(`📝 Modal: Processing notification ${index + 1}:`, notification.title);
                    
                    try {
                        const notificationEl = createNotificationElement(notification);
                        if (notificationEl && listEl) {
                            console.log('➕ Modal: Appending notification element to list');
                            listEl.appendChild(notificationEl);
                            console.log('✅ Modal: Successfully added notification to DOM');
                        } else {
                            console.error('❌ Modal: Failed to create notification element or list not found');
                        }
                    } catch (error) {
                        console.error('❌ Modal: Error creating/adding notification:', error);
                    }
                });
                if (emptyEl) emptyEl.classList.add('hidden');
            } else {
                console.log('📭 Modal: No notifications to display');
                if (emptyEl) emptyEl.classList.remove('hidden');
            }
        } else {
            console.error('❌ Modal: Invalid response structure:', data);
            if (emptyEl) emptyEl.classList.remove('hidden');
        }
        
    } catch (error) {
        console.error('❌ Modal: Error loading notifications:', error);
        
        // Try to use cached notifications from beranda
        if (window.berandaNotificationsCache && window.berandaNotificationsCache.length > 0) {
            console.log('🎯 Modal: Using cached notifications from beranda');
            const cachedNotifications = window.berandaNotificationsCache;
            
            if (countEl) countEl.textContent = cachedNotifications.length;
            
            cachedNotifications.forEach((notification, index) => {
                console.log(`📝 Modal (cached): Processing notification ${index + 1}:`, notification.title);
                const notificationEl = createNotificationElement(notification);
                if (listEl) listEl.appendChild(notificationEl);
            });
            
            if (emptyEl) emptyEl.classList.add('hidden');
        } else {
            console.log('❌ Modal: No cached notifications available');
            if (emptyEl) emptyEl.classList.remove('hidden');
        }
    } finally {
        if (loadingEl) loadingEl.classList.add('hidden');
    }
}// Create notification element from template
function createNotificationElement(notification) {
    console.log('🔧 Creating notification element for:', notification);
    
    const template = document.getElementById('notification-item-template');
    if (!template) {
        console.error('❌ Template not found: notification-item-template');
        return null;
    }
    console.log('✅ Template found:', template);
    
    const clone = template.content.cloneNode(true);
    console.log('✅ Template cloned:', clone);
    
    const container = clone.querySelector('.notification-item');
    const icon = clone.querySelector('.notification-icon');
    const title = clone.querySelector('.notification-title');
    const message = clone.querySelector('.notification-message');
    const time = clone.querySelector('.notification-time');
    const classText = clone.querySelector('.notification-class');
    const unreadIndicator = clone.querySelector('.unread-indicator');
    const markReadBtn = clone.querySelector('.mark-read-btn');
    
    console.log('🔍 Template elements found:', {
        container, icon, title, message, time, classText, unreadIndicator, markReadBtn
    });
    
    // Set notification data
    container.setAttribute('data-notification-id', notification.id);
    
    // Set icon and color based on type or from API
    let iconClass;
    if (notification.icon) {
        // Icon dari database (global notification) - tambahkan ti- prefix
        iconClass = `${notification.icon}`;
    } else {
        // Icon dari getNotificationIcon untuk tipe lain (sudah ada ti- prefix)
        iconClass = getNotificationIcon(notification.type);
    }
    
    const colorClass = notification.color || getNotificationColor(notification.type);
    
    // Format final: ti ti-[nama-icon]
    icon.className = `notification-icon ti ${iconClass} text-lg ${colorClass}`;
    
    // Set content
    title.textContent = notification.title;
    message.textContent = notification.message;
    time.textContent = notification.time_ago || formatTimeAgo(notification.created_at);
    
    if (notification.nama_kelas) {
        classText.textContent = notification.nama_kelas;
    } else {
        classText.style.display = 'none';
    }
    
    // Handle read status
    if (notification.is_read == '1') {
        unreadIndicator.style.display = 'none';
        container.classList.add('opacity-75');
        markReadBtn.style.display = 'none';
    }
    
    // Add click handler for redirect functionality
    const redirectUrl = getNotificationRedirectUrl(notification);
    const hasValidRedirect = hasValidRedirectTarget(notification);
    
    container.onclick = function(e) {
        // Prevent click if clicking on action buttons
        if (e.target.closest('.notification-actions-btn') || e.target.closest('.notification-actions-menu')) {
            return;
        }
        
        handleModalNotificationClick(notification.id, redirectUrl, hasValidRedirect, container);
    };
    
    return clone;
}

// Get notification redirect URL (same logic as in NotificationLogic.php)
function getNotificationRedirectUrl(notification) {
    // Determine base URL based on current location
    let baseUrl = '';
    const currentPath = window.location.pathname;
    
    if (currentPath.includes('/front/')) {
        // Already in front directory
        baseUrl = './';
    } else {
        // From other directories, go to front
        baseUrl = 'src/front/';
    }
    
    switch (notification.type) {
        case 'postingan_baru':
            if (notification.related_class_id && notification.related_id) {
                return `${baseUrl}kelas-user.php?id=${notification.related_class_id}#post-${notification.related_id}`;
            }
            break;
            
        case 'tugas_baru':
            if (notification.related_class_id && notification.related_id) {
                return `${baseUrl}kelas-user.php?id=${notification.related_class_id}&tab=assignments#assignment-${notification.related_id}`;
            }
            break;
            
        case 'ujian_baru':
            if (notification.related_id) {
                return `${baseUrl}ujian-user.php#ujian-${notification.related_id}`;
            }
            break;
            
        case 'pengingat_ujian':
            if (notification.related_id) {
                return `${baseUrl}ujian-user.php#ujian-${notification.related_id}`;
            }
            break;
    }
    
    return `${baseUrl}beranda-user.php`;
}

// Check if notification has valid redirect target
function hasValidRedirectTarget(notification) {
    switch (notification.type) {
        case 'postingan_baru':
            return notification.related_class_id && notification.related_id;
            
        case 'tugas_baru':
            return notification.related_class_id && notification.related_id;
            
        case 'ujian_baru':
        case 'pengingat_ujian':
            return notification.related_id;
            
        case 'global_notification':
            return false; // Global notifications don't have specific redirect targets
            
        default:
            return false;
    }
}

// Handle notification click in modal with redirect
async function handleModalNotificationClick(notificationId, redirectUrl, hasValidRedirect, element) {
    console.log('🔔 Modal notification clicked:', {notificationId, redirectUrl, hasValidRedirect});
    
    // First, mark as read if not already read
    if (!element.classList.contains('opacity-75')) {
        try {
            const response = await fetch(getApiBasePath() + 'mark-notification-read.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ notification_id: notificationId })
            });
            
            const data = await response.json();
            
            if (data.success) {
                // Update modal UI
                element.classList.add('opacity-75');
                const unreadDot = element.querySelector('.unread-indicator');
                if (unreadDot) unreadDot.style.display = 'none';
                const markReadBtn = element.querySelector('.mark-read-btn');
                if (markReadBtn) markReadBtn.style.display = 'none';
                
                // Update beranda notifications
                updateBerandaNotifications();
            }
        } catch (error) {
            console.error('Error marking notification as read:', error);
        }
    }
    
    // Close modal first
    closeNotificationsModal();
    
    // Then redirect if valid target exists
    if (hasValidRedirect && redirectUrl && redirectUrl !== '../front/beranda-user.php') {
        console.log('🔗 Redirecting to:', redirectUrl);
        // Add a small delay to ensure modal closes properly
        setTimeout(() => {
            window.location.href = redirectUrl;
        }, 300);
    } else {
        console.log('📝 No valid redirect, staying on beranda');
        // If no valid redirect, just show a message
        showToast('Notifikasi ditandai sebagai dibaca');
    }
}

// Get notification icon based on type
function getNotificationIcon(type) {
    switch (type) {
        case 'tugas_baru': return 'ti-clipboard-plus';
        case 'postingan_baru': return 'ti-message-circle';
        case 'ujian_baru': return 'ti-file-text';
        case 'pengingat_ujian': return 'ti-bell';
        case 'like_postingan': return 'ti-heart';
        case 'komentar_postingan': return 'ti-message-2';
        case 'global_notification': return 'ti-speakerphone';
        default: return 'ti-info-circle';
    }
}

// Get notification color based on type
function getNotificationColor(type) {
    switch (type) {
        case 'tugas_baru': return 'text-blue-500';
        case 'postingan_baru': return 'text-green-500';
        case 'ujian_baru': return 'text-purple-500';
        case 'pengingat_ujian': return 'text-orange-500';
        case 'like_postingan': return 'text-red-500';
        case 'komentar_postingan': return 'text-indigo-500';
        case 'global_notification': return 'text-orange-600';
        default: return 'text-gray-500';
    }
}

// Toggle notification actions menu
function toggleNotificationActions(button) {
    const menu = button.nextElementSibling;
    const allMenus = document.querySelectorAll('.notification-actions-menu');
    
    // Close all other menus
    allMenus.forEach(m => {
        if (m !== menu) m.classList.add('hidden');
    });
    
    // Toggle current menu
    menu.classList.toggle('hidden');
    
    // Close menu when clicking outside
    setTimeout(() => {
        document.addEventListener('click', function closeMenu(e) {
            if (!button.contains(e.target) && !menu.contains(e.target)) {
                menu.classList.add('hidden');
                document.removeEventListener('click', closeMenu);
            }
        });
    }, 0);
}

// Mark notification as read
async function markNotificationRead(button) {
    const notificationItem = button.closest('.notification-item');
    const notificationId = notificationItem.getAttribute('data-notification-id');
    
    try {
        const response = await fetch(getApiBasePath() + 'mark-notification-read.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ notification_id: notificationId })
        });
        
        const data = await response.json();
        
        if (data.success) {
            // Update UI
            const unreadIndicator = notificationItem.querySelector('.unread-indicator');
            const markReadBtn = notificationItem.querySelector('.mark-read-btn');
            
            unreadIndicator.style.display = 'none';
            markReadBtn.style.display = 'none';
            notificationItem.classList.add('opacity-75');
            
            // Update beranda notifications
            updateBerandaNotifications();
            
            showToast('Notifikasi ditandai sebagai dibaca');
        } else {
            showToast('Gagal menandai notifikasi', 'error');
        }
    } catch (error) {
        console.error('Error marking notification as read:', error);
        showToast('Terjadi kesalahan', 'error');
    }
}

// Delete notification
async function deleteNotification(button) {
    const notificationItem = button.closest('.notification-item');
    const notificationId = notificationItem.getAttribute('data-notification-id');
    
    if (!confirm('Hapus notifikasi ini?')) return;
    
    try {
        const response = await fetch(getApiBasePath() + 'delete-notification.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ notification_id: notificationId })
        });
        
        const data = await response.json();
        
        if (data.success) {
            // Remove from UI
            notificationItem.remove();
            
            // Update count
            const countEl = document.getElementById('notifications-count');
            const currentCount = parseInt(countEl.textContent);
            countEl.textContent = Math.max(0, currentCount - 1);
            
            // Check if empty
            const listEl = document.getElementById('notifications-list');
            if (listEl.children.length === 0) {
                document.getElementById('notifications-empty').classList.remove('hidden');
            }
            
            // Update beranda notifications
            updateBerandaNotifications();
            
            showToast('Notifikasi dihapus');
        } else {
            showToast('Gagal menghapus notifikasi', 'error');
        }
    } catch (error) {
        console.error('Error deleting notification:', error);
        showToast('Terjadi kesalahan', 'error');
    }
}

// Mark all notifications as read
async function markAllNotificationsRead() {
    try {
        const response = await fetch(getApiBasePath() + 'mark-all-notifications-read.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            }
        });
        
        const data = await response.json();
        
        if (data.success) {
            // Update all notification items
            const notificationItems = document.querySelectorAll('.notification-item');
            notificationItems.forEach(item => {
                const unreadIndicator = item.querySelector('.unread-indicator');
                const markReadBtn = item.querySelector('.mark-read-btn');
                
                unreadIndicator.style.display = 'none';
                markReadBtn.style.display = 'none';
                item.classList.add('opacity-75');
            });
            
            // Update beranda notifications
            updateBerandaNotifications();
            
            showToast('Semua notifikasi ditandai sebagai dibaca');
        } else {
            showToast('Gagal menandai semua notifikasi', 'error');
        }
    } catch (error) {
        console.error('Error marking all notifications as read:', error);
        showToast('Terjadi kesalahan', 'error');
    }
}

// Delete all read notifications
async function deleteReadNotifications() {
    if (!confirm('Hapus semua notifikasi yang sudah dibaca?')) return;
    
    try {
        const response = await fetch(getApiBasePath() + 'delete-read-notifications.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            }
        });
        
        const data = await response.json();
        
        if (data.success) {
            // Remove read notifications from UI
            const notificationItems = document.querySelectorAll('.notification-item.opacity-75');
            notificationItems.forEach(item => item.remove());
            
            // Update count
            const countEl = document.getElementById('notifications-count');
            const listEl = document.getElementById('notifications-list');
            countEl.textContent = listEl.children.length;
            
            // Check if empty
            if (listEl.children.length === 0) {
                document.getElementById('notifications-empty').classList.remove('hidden');
            }
            
            // Update beranda notifications
            updateBerandaNotifications();
            
            showToast('Notifikasi yang dibaca telah dihapus');
        } else {
            showToast('Gagal menghapus notifikasi', 'error');
        }
    } catch (error) {
        console.error('Error deleting read notifications:', error);
        showToast('Terjadi kesalahan', 'error');
    }
}

// Update beranda notifications (refresh the sidebar notifications)
function updateBerandaNotifications() {
    // This will reload the notifications in beranda sidebar
    if (typeof loadBerandaNotifications === 'function') {
        loadBerandaNotifications();
    }
    
    // Update notification badge if function exists
    if (typeof loadNotificationBadge === 'function') {
        loadNotificationBadge();
    }
}

// Format time ago
function formatTimeAgo(dateString) {
    const date = new Date(dateString);
    const now = new Date();
    const diffInSeconds = Math.floor((now - date) / 1000);
    
    if (diffInSeconds < 60) return 'Baru saja';
    if (diffInSeconds < 3600) return Math.floor(diffInSeconds / 60) + ' menit lalu';
    if (diffInSeconds < 86400) return Math.floor(diffInSeconds / 3600) + ' jam lalu';
    if (diffInSeconds < 2592000) return Math.floor(diffInSeconds / 86400) + ' hari lalu';
    
    return Math.floor(diffInSeconds / 2592000) + ' bulan lalu';
}

// Show toast notification
function showToast(message, type = 'success') {
    const toast = document.createElement('div');
    const bgColor = type === 'error' ? 'bg-red-600' : 'bg-green-600';
    
    toast.className = `fixed top-4 right-4 ${bgColor} text-white text-sm px-4 py-2 rounded shadow-lg z-50 transition-opacity duration-300`;
    toast.textContent = message;
    
    document.body.appendChild(toast);
    
    setTimeout(() => {
        toast.style.opacity = '0';
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}
</script>