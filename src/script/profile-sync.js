// Profile Synchronization Script
// This script ensures profile photos are updated across all UI components

class ProfileSync {
    constructor() {
        this.init();
    }

    init() {
        // Listen for profile photo updates
        window.addEventListener('profilePhotoUpdated', (event) => {
            this.updateAllProfileElements(event.detail.photoUrl);
        });

        // Listen for storage changes (for cross-tab updates)
        window.addEventListener('storage', (event) => {
            if (event.key === 'currentUserPhoto') {
                const newPhotoUrl = `../../uploads/profile/${event.newValue}`;
                this.updateAllProfileElements(newPhotoUrl);
            }
        });

        // Check for updates when page becomes visible
        document.addEventListener('visibilitychange', () => {
            if (!document.hidden) {
                this.checkForPhotoUpdates();
            }
        });
    }

    updateAllProfileElements(photoUrl) {
        console.log('ProfileSync: Updating all profile elements with URL:', photoUrl);

        // Update sidebar profile photo
        this.updateSidebarProfile(photoUrl);
        
        // Update mobile menu profile photo
        this.updateMobileProfile(photoUrl);
        
        // Update any post profile photos for current user
        this.updatePostProfilePhotos(photoUrl);
        
        // Update settings page profile preview
        this.updateSettingsProfile(photoUrl);
        
        // Update any other profile elements
        this.updateGenericProfileElements(photoUrl);
    }

    updateSidebarProfile(photoUrl) {
        // Update the profile photo in sidebar main button
        const sidebarProfileContainer = document.querySelector('#sidebar .w-10.h-10.rounded-full.overflow-hidden');
        if (sidebarProfileContainer) {
            // Clear existing content
            sidebarProfileContainer.innerHTML = '';
            
            // Add new image
            const img = document.createElement('img');
            img.src = photoUrl;
            img.alt = 'Profile Photo';
            img.className = 'w-full h-full object-cover';
            sidebarProfileContainer.appendChild(img);
            
            console.log('ProfileSync: Updated sidebar profile photo');
        }

        // Update sidebar dropdown profile photo if it exists
        const dropdownProfileContainer = document.querySelector('#profileDropdown .w-10.h-10.rounded-full');
        if (dropdownProfileContainer) {
            dropdownProfileContainer.innerHTML = `
                <img src="${photoUrl}" alt="Profile Photo" class="w-full h-full object-cover">
            `;
            console.log('ProfileSync: Updated sidebar dropdown profile photo');
        }
    }

    updateMobileProfile(photoUrl) {
        // Update mobile menu profile photos
        const mobileProfileContainers = document.querySelectorAll('.mobile-profile-container');
        mobileProfileContainers.forEach(container => {
            const img = container.querySelector('img');
            if (img) {
                img.src = photoUrl;
            } else {
                // Create new image if doesn't exist
                const newImg = document.createElement('img');
                newImg.src = photoUrl;
                newImg.alt = 'Profile Photo';
                newImg.className = 'w-full h-full object-cover';
                container.appendChild(newImg);
                
                // Hide fallback icon if exists
                const icon = container.querySelector('i');
                if (icon) {
                    icon.style.display = 'none';
                }
            }
        });
    }

    updatePostProfilePhotos(photoUrl) {
        // Update profile photos in posts made by current user
        // We need to identify which posts belong to current user
        const currentUserId = this.getCurrentUserId();
        if (currentUserId) {
            // Find posts with matching user ID and update their profile photos
            const userPosts = document.querySelectorAll(`[data-user-id="${currentUserId}"]`);
            userPosts.forEach(post => {
                const profilePhotoContainer = post.querySelector('.w-10.h-10.rounded-full.overflow-hidden');
                if (profilePhotoContainer) {
                    profilePhotoContainer.innerHTML = `
                        <img src="${photoUrl}" 
                             alt="Profile Photo" 
                             class="w-full h-full object-cover post-profile-photo">
                    `;
                }
            });
            
            console.log(`ProfileSync: Updated ${userPosts.length} post profile photos for user ${currentUserId}`);
        }
        
        // Also update any generic post profile photos for current user
        const postProfileImages = document.querySelectorAll('.post-profile-photo');
        postProfileImages.forEach(img => {
            // Check if this image belongs to current user's post
            const postContainer = img.closest('[data-user-id]');
            if (postContainer && postContainer.dataset.userId === currentUserId) {
                img.src = photoUrl;
            }
        });
    }

    updateSettingsProfile(photoUrl) {
        const settingsPreview = document.getElementById('profile-preview');
        if (settingsPreview) {
            settingsPreview.src = photoUrl;
        }
    }

    updateGenericProfileElements(photoUrl) {
        // Update any other profile images that might exist
        const profileImages = document.querySelectorAll('.user-profile-image, .profile-photo-current-user');
        profileImages.forEach(img => {
            if (img.tagName === 'IMG') {
                img.src = photoUrl;
            }
        });
    }

    getCurrentUserId() {
        // Try to get user ID from various sources
        const sidebar = document.getElementById('sidebar');
        if (sidebar && sidebar.dataset.userId) {
            return sidebar.dataset.userId;
        }

        // Try from meta tag
        const userIdMeta = document.querySelector('meta[name="user-id"]');
        if (userIdMeta) {
            return userIdMeta.getAttribute('content');
        }

        // Try from global variable
        if (window.currentUserId) {
            return window.currentUserId;
        }

        return null;
    }

    checkForPhotoUpdates() {
        // Check if there's a newer photo in localStorage
        const storedPhoto = localStorage.getItem('currentUserPhoto');
        if (storedPhoto) {
            const photoUrl = `../../uploads/profile/${storedPhoto}`;
            this.updateAllProfileElements(photoUrl);
        }
    }

    // Method to manually trigger profile update
    static updateProfile(photoUrl, fileName = null) {
        if (fileName) {
            localStorage.setItem('currentUserPhoto', fileName);
        }
        
        window.dispatchEvent(new CustomEvent('profilePhotoUpdated', {
            detail: { photoUrl: photoUrl, fileName: fileName }
        }));
    }
}

// Initialize ProfileSync when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    window.profileSync = new ProfileSync();
});

// Export for use in other scripts
window.ProfileSync = ProfileSync;
