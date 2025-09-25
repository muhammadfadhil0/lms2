// Profile Photo Dropdown and Crop Functionality
let cropper = null;
let selectedFile = null;

// Show profile photo dropdown with fade animation
function openProfilePhotoDropdown() {
    const dropdown = document.getElementById('profilePhotoDropdown');
    const deleteBtn = document.getElementById('deletePhotoBtn');
    
    // Check if user has profile photo to enable/disable delete button
    const profileImg = document.getElementById('profile-preview');
    const hasPhoto = profileImg && profileImg.src && !profileImg.src.includes('data:image/svg+xml');
    
    if (deleteBtn) {
        deleteBtn.style.display = hasPhoto ? 'flex' : 'none';
    }
    
    // Show modal with fade animation
    dropdown.style.display = 'flex';
    document.body.style.overflow = 'hidden';
    
    // Trigger fade animation
    setTimeout(() => {
        dropdown.classList.add('show');
    }, 10);
}

// Close profile photo dropdown with fade animation
function closeProfileDropdown() {
    const dropdown = document.getElementById('profilePhotoDropdown');
    
    // Start fade out animation
    dropdown.classList.remove('show');
    
    // Hide modal after animation completes
    setTimeout(() => {
        dropdown.style.display = 'none';
        document.body.style.overflow = 'auto';
    }, 300);
}

// Select new photo
function selectNewPhoto() {
    closeProfileDropdown();
    document.getElementById('photoFileInput').click();
}

// Handle file selection
function handleFileSelect(event) {
    console.log('handleFileSelect called');
    const file = event.target.files[0];
    console.log('Selected file:', file);
    
    if (!file) {
        console.log('No file selected');
        return;
    }
    
    console.log('File details:', {
        name: file.name,
        size: file.size,
        type: file.type
    });
    
    // Validate file type
    if (!file.type.match(/^image\/(jpeg|jpg|png|gif)$/i)) {
        console.log('Invalid file type:', file.type);
        showAlert('Format file tidak didukung. Gunakan JPG, PNG, atau GIF', 'error');
        event.target.value = ''; // Reset input
        return;
    }
    
    // Validate file size (2MB)
    if (file.size > 2 * 1024 * 1024) {
        console.log('File too large:', file.size);
        showAlert('Ukuran file terlalu besar. Maksimal 2MB', 'error');
        event.target.value = ''; // Reset input
        return;
    }
    
    selectedFile = file;
    console.log('File validation passed, showing crop modal');
    showCropModal(file);
}

// Show crop modal with fade animation
function showCropModal(file) {
    const modal = document.getElementById('cropPhotoModal');
    const cropImage = document.getElementById('cropImage');
    const loading = document.getElementById('cropLoading');
    const saveBtn = document.getElementById('saveCropBtn');
    
    // Show modal with fade animation
    modal.style.display = 'flex';
    loading.style.display = 'block';
    cropImage.style.display = 'none';
    saveBtn.disabled = true;
    document.body.style.overflow = 'hidden';
    
    // Trigger fade animation
    setTimeout(() => {
        modal.classList.add('show');
    }, 10);
    
    // Create file reader
    const reader = new FileReader();
    reader.onload = function(e) {
        cropImage.src = e.target.result;
        cropImage.style.display = 'block';
        loading.style.display = 'none';
        
        // Initialize cropper
        if (cropper) {
            cropper.destroy();
        }
        
        cropper = new Cropper(cropImage, {
            aspectRatio: 1, // Square crop
            viewMode: 1,
            dragMode: 'move',
            autoCropArea: 0.8,
            restore: false,
            guides: true,
            center: true,
            highlight: false,
            cropBoxMovable: true,
            cropBoxResizable: true,
            toggleDragModeOnDblclick: false,
            ready: function() {
                saveBtn.disabled = false;
            }
        });
    };
    
    reader.readAsDataURL(file);
}

// Close crop modal with fade animation
function closeCropModal() {
    const modal = document.getElementById('cropPhotoModal');
    
    // Start fade out animation
    modal.classList.remove('show');
    
    // Hide modal after animation completes
    setTimeout(() => {
        modal.style.display = 'none';
        document.body.style.overflow = 'auto';
        
        if (cropper) {
            cropper.destroy();
            cropper = null;
        }
        
        selectedFile = null;
        
        // Reset file input
        document.getElementById('photoFileInput').value = '';
    }, 300);
}

// Save cropped photo
function saveCroppedPhoto() {
    console.log('saveCroppedPhoto called');
    console.log('cropper exists:', !!cropper);
    console.log('selectedFile exists:', !!selectedFile);
    
    if (!cropper || !selectedFile) {
        showAlert('Tidak ada foto untuk disimpan', 'error');
        return;
    }
    
    const saveBtn = document.getElementById('saveCropBtn');
    const originalText = saveBtn.innerHTML;
    
    // Show loading
    saveBtn.disabled = true;
    saveBtn.innerHTML = '<i class="ti ti-loader animate-spin mr-2"></i>Menyimpan...';
    
    // Get cropped canvas
    console.log('Getting cropped canvas...');
    const canvas = cropper.getCroppedCanvas({
        width: 300,
        height: 300,
        imageSmoothingQuality: 'high'
    });
    
    console.log('Canvas created:', !!canvas);
    
    // Convert to blob
    canvas.toBlob(function(blob) {
        console.log('Blob created:', blob);
        console.log('Blob size:', blob ? blob.size : 'null');
        console.log('Blob type:', blob ? blob.type : 'null');
        
        const formData = new FormData();
        formData.append('action', 'upload_photo');
        
        // Make sure we're using a proper filename with extension
        const fileExtension = selectedFile.name.split('.').pop().toLowerCase();
        const fileName = `profile_photo_${Date.now()}.${fileExtension}`;
        formData.append('profile_photo', blob, fileName);
        
        // Check if CSRF token exists
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        if (csrfToken) {
            formData.append('csrf_token', csrfToken);
            console.log('CSRF token included in request');
        }
        
        console.log('FormData created, sending request...');
        console.log('Request URL:', '../logic/settings-api.php');
        
        // Debug: Log form data entries
        for (let pair of formData.entries()) {
            console.log('FormData entry:', pair[0], pair[1]);
        }
        
        // Upload to server with improved headers
        fetch('../logic/settings-api.php', {
            method: 'POST',
            body: formData,
            headers: {
                'Accept': 'application/json',
                // Don't set Content-Type header when using FormData as it will be set automatically with the correct boundary
            },
            credentials: 'same-origin' // Include cookies in the request
        })
        .then(response => {
            console.log('Response received:', response);
            console.log('Response status:', response.status);
            console.log('Response ok:', response.ok);
            
            // Get all response headers for debugging
            const headers = {};
            response.headers.forEach((value, key) => {
                headers[key] = value;
            });
            console.log('Response headers:', headers);
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.text();
        })
        .then(text => {
            console.log('Response text received:', text);
            
            // Remove any potential whitespace or BOM
            const cleanText = text.trim();
            console.log('Clean response text:', cleanText);
            
            try {
                const data = JSON.parse(cleanText);
                console.log('Parsed response data:', data);
                
                if (data.success) {
                    showAlert('Foto profil berhasil diperbarui', 'success');
                    
                    // Get the new photo URL
                    const newPhotoUrl = data.data && data.data.fileName ? `../../uploads/profile/${data.data.fileName}` : canvas.toDataURL();
                    
                    // Update profile image preview in settings
                    updateProfilePreview(newPhotoUrl);
                    
                    // Update all profile photos in UI using ProfileSync
                    if (window.ProfileSync) {
                        ProfileSync.updateProfile(newPhotoUrl, data.data ? data.data.fileName : null);
                    } else {
                        // Fallback method
                        updateAllProfilePhotos(newPhotoUrl);
                    }
                    
                    // Update session storage for real-time updates
                    if (data.data && data.data.fileName) {
                        localStorage.setItem('currentUserPhoto', data.data.fileName);
                        // Trigger event to update other tabs/windows
                        window.dispatchEvent(new CustomEvent('profilePhotoUpdated', {
                            detail: { photoUrl: newPhotoUrl, fileName: data.data.fileName }
                        }));
                    }
                    
                    // Close modal
                    closeCropModal();
                } else {
                    console.log('Upload failed:', data.message);
                    
                    // Show more detailed error message if available
                    const errorDetails = data.data ? `: ${JSON.stringify(data.data)}` : '';
                    showAlert(`Gagal menyimpan foto profil: ${data.message}${errorDetails}`, 'error');
                }
            } catch (parseError) {
                console.error('JSON Parse Error:', parseError);
                console.error('Response text that failed to parse:', text);
                if (text.includes('Action tidak valid')) {
                    showAlert('API endpoint tidak ditemukan', 'error');
                } else {
                    showAlert('Response tidak valid dari server: ' + text, 'error');
                }
            }
        })
        .catch(error => {
            console.error('Fetch Error:', error);
            showAlert('Terjadi kesalahan saat menyimpan foto: ' + error.message, 'error');
        })
        .finally(() => {
            saveBtn.disabled = false;
            saveBtn.innerHTML = originalText;
        });
    }, 'image/jpeg', 0.9); // Explicitly set JPEG format with 90% quality
}

// Delete current photo
function deleteCurrentPhoto() {
    if (!confirm('Apakah Anda yakin ingin menghapus foto profil?')) {
        return;
    }
    
    closeProfileDropdown();
    
    const formData = new FormData();
    formData.append('action', 'delete_photo');
    
    fetch('../logic/settings-api.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert('Foto profil berhasil dihapus', 'success');
            
            // Update profile image to default
            updateProfilePreviewToDefault();
            
            // Update all profile photos to default using ProfileSync
            if (window.ProfileSync) {
                const defaultAvatar = "data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='96' height='96' viewBox='0 0 96 96'%3E%3Crect width='96' height='96' fill='%23ff6347'/%3E%3Ctext x='48' y='56' text-anchor='middle' fill='white' font-size='32' font-family='Arial'%3EU%3C/text%3E%3C/svg%3E";
                ProfileSync.updateProfile(defaultAvatar, null);
            } else {
                // Fallback method
                resetAllProfilePhotosToDefault();
            }
            
            // Clear localStorage
            localStorage.removeItem('currentUserPhoto');
            
            // Trigger event for other tabs
            window.dispatchEvent(new CustomEvent('profilePhotoUpdated', {
                detail: { photoUrl: null, fileName: null }
            }));
        } else {
            showAlert(data.message || 'Gagal menghapus foto profil', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('Terjadi kesalahan saat menghapus foto', 'error');
    });
}

// Update profile preview
function updateProfilePreview(dataUrl) {
    const profilePreviews = document.querySelectorAll('#profile-preview, .profile-image');
    profilePreviews.forEach(img => {
        if (img) {
            img.src = dataUrl;
        }
    });
}

// Update profile preview to default
function updateProfilePreviewToDefault() {
    const profilePreviews = document.querySelectorAll('#profile-preview, .profile-image');
    const defaultSvg = "data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='96' height='96' viewBox='0 0 96 96'%3E%3Crect width='96' height='96' fill='%23ff6347'/%3E%3Ctext x='48' y='56' text-anchor='middle' fill='white' font-size='32' font-family='Arial'%3EU%3C/text%3E%3C/svg%3E";
    
    profilePreviews.forEach(img => {
        if (img) {
            img.src = defaultSvg;
        }
    });
}

// Show alert function
function showAlert(message, type = 'info') {
    // Create alert element
    const alert = document.createElement('div');
    alert.className = `fixed top-4 right-4 z-50 p-4 rounded-lg shadow-lg`;

    // Make it responsive for mobile: set left/right to span full width with small margins
    if (window.innerWidth < 640) {
        alert.style.left = '0.5rem';
        alert.style.right = '0.5rem';
        alert.style.maxWidth = 'calc(100% - 1rem)';
    }

    alert.style.transition = 'transform 0.28s ease, opacity 0.28s ease';
    alert.style.transform = 'translateX(100%)';
    alert.style.opacity = '1';

    // Set colors based on type
    if (type === 'success') {
        alert.className += ' bg-green-100 border border-green-400 text-green-700';
    } else if (type === 'error') {
        alert.className += ' bg-red-100 border border-red-400 text-red-700';
    } else {
        alert.className += ' bg-blue-100 border border-blue-400 text-blue-700';
    }

    alert.innerHTML = `
        <div class="flex items-center">
            <div class="flex-1">
                <p class="text-sm font-medium">${message}</p>
            </div>
            <button class="ml-3 text-gray-400 hover:text-gray-600" aria-label="close-toast">
                <i class="ti ti-x"></i>
            </button>
        </div>
    `;

    document.body.appendChild(alert);

    // Animate in
    setTimeout(() => {
        alert.style.transform = 'translateX(0)';
    }, 20);

    // Close button
    const closeBtn = alert.querySelector('[aria-label="close-toast"]');
    if (closeBtn) {
        closeBtn.addEventListener('click', () => {
            alert.style.transform = 'translateX(100%)';
            setTimeout(() => alert.remove(), 280);
        });
    }

    // Auto remove after 5 seconds
    setTimeout(() => {
        if (alert.parentElement) {
            alert.style.transform = 'translateX(100%)';
            setTimeout(() => {
                if (alert.parentElement) {
                    alert.remove();
                }
            }, 300);
        }
    }, 5000);
}

// Close modals when clicking outside
document.addEventListener('click', function(event) {
    const dropdownModal = document.getElementById('profilePhotoDropdown');
    const cropModal = document.getElementById('cropPhotoModal');
    
    if (dropdownModal && dropdownModal.style.display === 'flex') {
        if (event.target === dropdownModal) {
            closeProfileDropdown();
        }
    }
    
    if (cropModal && cropModal.style.display === 'flex') {
        if (event.target === cropModal) {
            closeCropModal();
        }
    }
});

// Close modals with Escape key
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        const dropdownModal = document.getElementById('profilePhotoDropdown');
        const cropModal = document.getElementById('cropPhotoModal');
        
        if (cropModal && cropModal.style.display === 'flex') {
            closeCropModal();
        } else if (dropdownModal && dropdownModal.style.display === 'flex') {
            closeProfileDropdown();
        }
    }
});

// Function to update all profile photos in the UI
function updateAllProfilePhotos(photoUrl) {
    console.log('Updating all profile photos with URL:', photoUrl);
    
    // Update main profile preview in settings
    const profilePreview = document.getElementById('profile-preview');
    if (profilePreview) {
        profilePreview.src = photoUrl;
        console.log('Updated profile preview in settings');
    }
    
    // Update sidebar profile photo - look for the actual img element in sidebar
    const sidebarProfileContainer = document.querySelector('#sidebar .w-10.h-10.rounded-full');
    if (sidebarProfileContainer) {
        // Remove existing content and add new image
        sidebarProfileContainer.innerHTML = `
            <img src="${photoUrl}" alt="Profile Photo" class="w-full h-full object-cover">
        `;
        console.log('Updated sidebar profile photo');
    }
    
    // Also update any other profile photos in sidebar dropdown
    const sidebarDropdownContainer = document.querySelector('#profileDropdown .w-10.h-10.rounded-full');
    if (sidebarDropdownContainer) {
        sidebarDropdownContainer.innerHTML = `
            <img src="${photoUrl}" alt="Profile Photo" class="w-full h-full object-cover">
        `;
        console.log('Updated sidebar dropdown profile photo');
    }
    
    // Update mobile menu profile photo if exists
    const mobileProfileContainers = document.querySelectorAll('.mobile-profile-photo');
    mobileProfileContainers.forEach(container => {
        container.innerHTML = `
            <img src="${photoUrl}" alt="Profile Photo" class="w-full h-full object-cover">
        `;
    });
    
    // Update any profile photos in posts or other areas
    const allProfileImages = document.querySelectorAll('.user-profile-image, .profile-photo');
    allProfileImages.forEach(img => {
        if (img.tagName === 'IMG') {
            img.src = photoUrl;
        }
    });
    
    // Force refresh of any cached profile images
    setTimeout(() => {
        const allImages = document.querySelectorAll('img[alt*="Profile"], img[alt*="profile"]');
        allImages.forEach(img => {
            if (img.src.includes('uploads/profile/')) {
                img.src = photoUrl + '?t=' + Date.now(); // Add timestamp to force refresh
            }
        });
    }, 100);
}

// Function to update profile preview
function updateProfilePreview(dataUrl) {
    const profilePreview = document.getElementById('profile-preview');
    if (profilePreview) {
        profilePreview.src = dataUrl;
    }
}

// Function to update profile preview to default
function updateProfilePreviewToDefault() {
    const profilePreview = document.getElementById('profile-preview');
    if (profilePreview) {
        profilePreview.src = "data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='96' height='96' viewBox='0 0 96 96'%3E%3Crect width='96' height='96' fill='%23ff6347'/%3E%3Ctext x='48' y='56' text-anchor='middle' fill='white' font-size='32' font-family='Arial'%3EU%3C/text%3E%3C/svg%3E";
    }
}

// Function to reset all profile photos to default/fallback
function resetAllProfilePhotosToDefault() {
    // Remove images and show fallback icons
    const sidebarProfileImg = document.querySelector('#sidebar button img');
    if (sidebarProfileImg) {
        sidebarProfileImg.remove();
    }
    
    const mobileProfileImg = document.querySelector('[onclick="toggleMobileProfile()"] img');
    if (mobileProfileImg) {
        mobileProfileImg.remove();
    }
    
    const mobileModalImg = document.querySelector('#mobileProfileModal img');
    if (mobileModalImg) {
        mobileModalImg.remove();
    }
    
    // Show fallback icons
    const fallbackIcons = document.querySelectorAll('[onclick="toggleMobileProfile()"] i.ti-user');
    fallbackIcons.forEach(icon => {
        icon.style.display = 'block';
    });
}
