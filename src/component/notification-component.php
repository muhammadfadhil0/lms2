<!-- Notification Component untuk dashboard user -->
<style>
    .notification-container {
        position: relative;
    }

    .notification-bell {
        position: relative;
        padding: 8px;
        border-radius: 8px;
        background: #f8fafc;
        border: 1px solid #e5e7eb;
        cursor: pointer;
        transition: all 0.2s ease;
    }

    .notification-bell:hover {
        background: #f1f5f9;
        border-color: #d1d5db;
    }

    .notification-badge {
        position: absolute;
        top: -2px;
        right: -2px;
        background: #ef4444;
        color: white;
        border-radius: 50%;
        width: 18px;
        height: 18px;
        font-size: 10px;
        font-weight: 600;
        display: flex;
        align-items: center;
        justify-content: center;
        animation: pulse 2s infinite;
    }

    .notification-dropdown {
        position: absolute;
        top: 100%;
        right: 0;
        width: 320px;
        max-height: 400px;
        background: white;
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        z-index: 1000;
        opacity: 0;
        visibility: hidden;
        transform: translateY(-10px);
        transition: all 0.3s ease;
    }

    .notification-dropdown.show {
        opacity: 1;
        visibility: visible;
        transform: translateY(0);
    }

    .notification-header {
        padding: 16px;
        border-bottom: 1px solid #f3f4f6;
        display: flex;
        justify-content: between;
        align-items: center;
    }

    .notification-title {
        font-weight: 600;
        color: #374151;
        flex: 1;
    }

    .mark-all-read {
        font-size: 12px;
        color: #6366f1;
        cursor: pointer;
        text-decoration: none;
    }

    .mark-all-read:hover {
        text-decoration: underline;
    }

    .notification-list {
        max-height: 300px;
        overflow-y: auto;
    }

    .notification-item {
        padding: 12px 16px;
        border-bottom: 1px solid #f9fafb;
        cursor: pointer;
        transition: background-color 0.2s;
        position: relative;
    }

    .notification-item:hover {
        background: #f9fafb;
    }

    .notification-item.unread {
        background: #fef7ed;
        border-left: 3px solid #f97316;
    }

    .notification-item.unread::before {
        content: '';
        position: absolute;
        top: 50%;
        left: 8px;
        transform: translateY(-50%);
        width: 6px;
        height: 6px;
        background: #f97316;
        border-radius: 50%;
    }

    .notification-content {
        margin-left: 12px;
    }

    .notification-item-title {
        font-weight: 500;
        font-size: 14px;
        color: #374151;
        margin-bottom: 4px;
        display: flex;
        align-items: center;
        gap: 6px;
    }

    .notification-item-desc {
        font-size: 12px;
        color: #6b7280;
        line-height: 1.4;
        margin-bottom: 4px;
    }

    .notification-item-time {
        font-size: 11px;
        color: #9ca3af;
    }

    .notification-priority-urgent .notification-item-title {
        color: #991b1b;
    }

    .notification-priority-high .notification-item-title {
        color: #92400e;
    }

    .notification-empty {
        padding: 40px 16px;
        text-align: center;
        color: #6b7280;
    }

    .notification-empty i {
        font-size: 24px;
        margin-bottom: 8px;
        display: block;
    }

    @keyframes pulse {
        0% {
            transform: scale(1);
        }
        50% {
            transform: scale(1.1);
        }
        100% {
            transform: scale(1);
        }
    }
</style>

<div class="notification-container">
    <!-- Notification Bell -->
    <div class="notification-bell" onclick="toggleNotifications()">
        <i class="ti ti-bell" style="font-size: 18px; color: #6b7280;"></i>
        <span class="notification-badge" id="notification-count" style="display: none;">0</span>
    </div>

    <!-- Notification Dropdown -->
    <div class="notification-dropdown" id="notification-dropdown">
        <div class="notification-header">
            <div class="notification-title">Notifikasi</div>
            <a href="#" class="mark-all-read" onclick="markAllAsRead(event)">Tandai semua dibaca</a>
        </div>
        
        <div class="notification-list" id="notification-list">
            <!-- Notifications will be loaded here -->
        </div>
        
        <div class="notification-footer" style="padding: 12px 16px; text-align: center; border-top: 1px solid #f3f4f6;">
            <button onclick="loadMoreNotifications()" class="text-sm text-gray-600 hover:text-gray-800">
                Muat lebih banyak
            </button>
        </div>
    </div>
</div>

<script>
let currentPage = 1;
let isLoadingNotifications = false;

// Initialize notifications
document.addEventListener('DOMContentLoaded', function() {
    loadNotifications();
    updateUnreadCount();
    
    // Auto refresh every 30 seconds
    setInterval(updateUnreadCount, 30000);
    
    // Close dropdown when clicking outside
    document.addEventListener('click', function(event) {
        const container = document.querySelector('.notification-container');
        if (!container.contains(event.target)) {
            document.getElementById('notification-dropdown').classList.remove('show');
        }
    });
});

function toggleNotifications() {
    const dropdown = document.getElementById('notification-dropdown');
    const isVisible = dropdown.classList.contains('show');
    
    if (isVisible) {
        dropdown.classList.remove('show');
    } else {
        dropdown.classList.add('show');
        if (currentPage === 1) {
            loadNotifications();
        }
    }
}

async function loadNotifications(page = 1) {
    if (isLoadingNotifications) return;
    isLoadingNotifications = true;
    
    try {
        const response = await fetch(`../logic/user-notifications-api.php?action=get_user_notifications&page=${page}&per_page=10`);
        const result = await response.json();
        
        if (result.success) {
            const container = document.getElementById('notification-list');
            
            if (page === 1) {
                container.innerHTML = '';
                currentPage = 1;
            }
            
            if (result.data.length === 0 && page === 1) {
                container.innerHTML = `
                    <div class="notification-empty">
                        <i class="ti ti-bell-off"></i>
                        <div>Tidak ada notifikasi</div>
                    </div>
                `;
            } else {
                result.data.forEach(notification => {
                    container.innerHTML += createNotificationItem(notification);
                });
            }
        }
    } catch (error) {
        console.error('Error loading notifications:', error);
    } finally {
        isLoadingNotifications = false;
    }
}

function createNotificationItem(notification) {
    const isUnread = !notification.is_read;
    const timeAgo = getTimeAgo(notification.created_at);
    const priorityClass = `notification-priority-${notification.priority}`;
    
    return `
        <div class="notification-item ${isUnread ? 'unread' : ''} ${priorityClass}" 
             onclick="markAsRead(${notification.id})">
            <div class="notification-content">
                <div class="notification-item-title">
                    <i class="ti ti-${notification.icon}"></i>
                    ${escapeHtml(notification.title)}
                </div>
                <div class="notification-item-desc">
                    ${escapeHtml(notification.description)}
                </div>
                <div class="notification-item-time">
                    ${timeAgo} • ${notification.created_by_name}
                </div>
            </div>
        </div>
    `;
}

async function markAsRead(notificationId) {
    try {
        const formData = new FormData();
        formData.append('action', 'mark_as_read');
        formData.append('notification_id', notificationId);
        
        const response = await fetch('../logic/user-notifications-api.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        if (result.success) {
            // Update UI
            const item = document.querySelector(`[onclick="markAsRead(${notificationId})"]`);
            if (item) {
                item.classList.remove('unread');
            }
            updateUnreadCount();
        }
    } catch (error) {
        console.error('Error marking as read:', error);
    }
}

async function markAllAsRead(event) {
    event.preventDefault();
    
    try {
        const formData = new FormData();
        formData.append('action', 'mark_all_read');
        
        const response = await fetch('../logic/user-notifications-api.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        if (result.success) {
            // Update UI
            document.querySelectorAll('.notification-item.unread').forEach(item => {
                item.classList.remove('unread');
            });
            updateUnreadCount();
        }
    } catch (error) {
        console.error('Error marking all as read:', error);
    }
}

async function updateUnreadCount() {
    try {
        const response = await fetch('../logic/user-notifications-api.php?action=get_unread_count');
        const result = await response.json();
        
        if (result.success) {
            const badge = document.getElementById('notification-count');
            const count = result.unread_count;
            
            if (count > 0) {
                badge.textContent = count > 99 ? '99+' : count;
                badge.style.display = 'flex';
            } else {
                badge.style.display = 'none';
            }
        }
    } catch (error) {
        console.error('Error updating unread count:', error);
    }
}

function loadMoreNotifications() {
    currentPage++;
    loadNotifications(currentPage);
}

// Utility functions
function getTimeAgo(dateString) {
    const now = new Date();
    const date = new Date(dateString);
    const diffMs = now - date;
    const diffMins = Math.floor(diffMs / 60000);
    const diffHours = Math.floor(diffMs / 3600000);
    const diffDays = Math.floor(diffMs / 86400000);
    
    if (diffMins < 1) return 'Baru saja';
    if (diffMins < 60) return `${diffMins} menit yang lalu`;
    if (diffHours < 24) return `${diffHours} jam yang lalu`;
    if (diffDays < 7) return `${diffDays} hari yang lalu`;
    
    return date.toLocaleDateString('id-ID');
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}
</script>