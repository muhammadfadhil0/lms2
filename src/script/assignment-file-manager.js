/**
 * Assignment File Manage        // Validate file size
        if (file.size > MAX_FILE_SIZE) {
            alert(`File ${file.name} terlalu besar. Maksimal 15MB per file.`);
            continue;
        }Handles multiple file upload preview for assignment creation and editing
 */

// Global variables to track files
let assignmentFilesArray = [];
let editAssignmentFilesArray = [];
let currentAssignmentFiles = []; // For existing files in edit mode

// Expose assignmentFilesArray to global scope
window.assignmentFilesArray = assignmentFilesArray;

const MAX_FILES = 4;
const MAX_FILE_SIZE = 15 * 1024 * 1024; // 15MB

// File preview handlers for create assignment modal
function handleAssignmentFilesSelect(input) {
    const files = Array.from(input.files);
    console.log('✏️ [DEBUG] handleAssignmentFilesSelect called with', files.length, 'files');
    console.log('✏️ [DEBUG] Current assignmentFilesArray length before processing:', assignmentFilesArray.length);
    
    for (const file of files) {
        console.log('✏️ [DEBUG] Processing file:', file.name, 'size:', file.size);
        
        // Check file count limit
        if (assignmentFilesArray.length >= MAX_FILES) {
            console.log('✏️ [DEBUG] File limit reached, max:', MAX_FILES);
            alert(`Maksimal ${MAX_FILES} file yang dapat diupload`);
            break;
        }

        // Validate file size
        if (file.size > MAX_FILE_SIZE) {
            alert(`File ${file.name} terlalu besar. Maksimal 10MB per file`);
            continue;
        }

        // Add to array and show preview
        assignmentFilesArray.push(file);
        console.log('✏️ [DEBUG] File added to array, total files now:', assignmentFilesArray.length);
        showAssignmentFilePreview(file, assignmentFilesArray.length - 1);
    }

    // Clear input for next selection
    input.value = '';
    updateAssignmentFileCounter();
    console.log('✏️ [DEBUG] Final assignmentFilesArray length:', assignmentFilesArray.length);
    
    // Ensure global access
    window.assignmentFilesArray = assignmentFilesArray;
}

function showAssignmentFilePreview(file, index) {
    const previewContainer = document.getElementById('assignmentFilesPreview');
    if (!previewContainer) return;

    const icon = getFileIconClass(file.name);
    
    const filePreviewElement = document.createElement('div');
    filePreviewElement.className = 'p-3 bg-gray-50 rounded-lg border border-gray-200';
    filePreviewElement.dataset.fileIndex = index;
    
    filePreviewElement.innerHTML = `
        <div class="flex items-center justify-between">
            <div class="flex items-center space-x-3">
                <div class="flex-shrink-0">
                    <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
                        <i class="${icon} text-blue-600"></i>
                    </div>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-gray-900 truncate">${file.name}</p>
                    <p class="text-xs text-gray-500">${formatFileSize(file.size)}</p>
                </div>
            </div>
            <button type="button" onclick="removeAssignmentFile(${index})" 
                class="flex-shrink-0 ml-3 text-gray-400 hover:text-red-500 transition-colors">
                <i class="ti ti-x text-lg"></i>
            </button>
        </div>
    `;
    
    previewContainer.appendChild(filePreviewElement);
}

function removeAssignmentFile(index) {
    // Remove from array
    assignmentFilesArray.splice(index, 1);
    window.assignmentFilesArray = assignmentFilesArray; // Update global reference
    
    // Remove from DOM and re-render all previews
    refreshAssignmentFilePreviews();
    updateAssignmentFileCounter();
}

function refreshAssignmentFilePreviews() {
    const previewContainer = document.getElementById('assignmentFilesPreview');
    if (!previewContainer) return;
    
    previewContainer.innerHTML = '';
    
    assignmentFilesArray.forEach((file, index) => {
        showAssignmentFilePreview(file, index);
    });
}

function updateAssignmentFileCounter() {
    const counter = document.getElementById('fileCountIndicator');
    const addBtn = document.getElementById('assignmentAddFileBtn');
    
    if (counter) {
        counter.textContent = `(${assignmentFilesArray.length}/${MAX_FILES})`;
    }
    
    if (addBtn) {
        if (assignmentFilesArray.length >= MAX_FILES) {
            addBtn.classList.add('opacity-50', 'cursor-not-allowed');
            addBtn.disabled = true;
        } else {
            addBtn.classList.remove('opacity-50', 'cursor-not-allowed');
            addBtn.disabled = false;
        }
    }
}

// Clear assignment files
function clearAssignmentFiles() {
    assignmentFilesArray = [];
    window.assignmentFilesArray = assignmentFilesArray; // Update global reference
    refreshAssignmentFilePreviews();
    updateAssignmentFileCounter();
}

// File preview handlers for edit assignment modal
function handleEditAssignmentFilesSelect(input) {
    const files = Array.from(input.files);
    const totalFiles = currentAssignmentFiles.length + editAssignmentFilesArray.length;
    
    for (let file of files) {
        if (totalFiles + editAssignmentFilesArray.length >= MAX_FILES) {
            alert(`Maksimal ${MAX_FILES} file total yang dapat diupload`);
            break;
        }

        // Validate file size
        if (file.size > MAX_FILE_SIZE) {
            alert(`File ${file.name} terlalu besar. Maksimal 10MB per file`);
            continue;
        }

        // Add to array and show preview
        editAssignmentFilesArray.push(file);
        showEditAssignmentFilePreview(file, editAssignmentFilesArray.length - 1);
    }

    // Clear input for next selection
    input.value = '';
    updateEditAssignmentFileCounter();
}

function showEditAssignmentFilePreview(file, index) {
    const previewContainer = document.getElementById('editAssignmentFilesPreview');
    if (!previewContainer) return;

    const icon = getFileIconClass(file.name);
    
    const filePreviewElement = document.createElement('div');
    filePreviewElement.className = 'p-3 bg-gray-50 rounded-lg border border-gray-200';
    filePreviewElement.dataset.fileIndex = index;
    
    filePreviewElement.innerHTML = `
        <div class="flex items-center justify-between">
            <div class="flex items-center space-x-3">
                <div class="flex-shrink-0">
                    <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
                        <i class="${icon} text-blue-600"></i>
                    </div>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-gray-900 truncate">${file.name}</p>
                    <p class="text-xs text-gray-500">${formatFileSize(file.size)} - <span class="text-blue-600">File Baru</span></p>
                </div>
            </div>
            <button type="button" onclick="removeEditAssignmentFile(${index})" 
                class="flex-shrink-0 ml-3 text-gray-400 hover:text-red-500 transition-colors">
                <i class="ti ti-x text-lg"></i>
            </button>
        </div>
    `;
    
    previewContainer.appendChild(filePreviewElement);
}

function removeEditAssignmentFile(index) {
    // Remove from array
    editAssignmentFilesArray.splice(index, 1);
    
    // Remove from DOM and re-render all previews
    refreshEditAssignmentFilePreviews();
    updateEditAssignmentFileCounter();
}

function refreshEditAssignmentFilePreviews() {
    const previewContainer = document.getElementById('editAssignmentFilesPreview');
    if (!previewContainer) return;
    
    previewContainer.innerHTML = '';
    
    editAssignmentFilesArray.forEach((file, index) => {
        showEditAssignmentFilePreview(file, index);
    });
}

function updateEditAssignmentFileCounter() {
    const counter = document.getElementById('editFileCountIndicator');
    const addBtn = document.getElementById('editAssignmentAddFileBtn');
    const totalFiles = currentAssignmentFiles.length + editAssignmentFilesArray.length;
    
    if (counter) {
        counter.textContent = `(${totalFiles}/${MAX_FILES})`;
    }
    
    if (addBtn) {
        if (totalFiles >= MAX_FILES) {
            addBtn.classList.add('opacity-50', 'cursor-not-allowed');
            addBtn.disabled = true;
        } else {
            addBtn.classList.remove('opacity-50', 'cursor-not-allowed');
            addBtn.disabled = false;
        }
    }
}

// Clear edit assignment files
function clearEditAssignmentFiles() {
    editAssignmentFilesArray = [];
    refreshEditAssignmentFilePreviews();
    updateEditAssignmentFileCounter();
}

// Function to show existing files in edit mode
function showCurrentAssignmentFiles(files) {
    currentAssignmentFiles = files || [];
    updateEditAssignmentFileCounter();
}

// Function to remove existing file
function removeCurrentAssignmentFile(fileId, element) {
    if (confirm('Hapus file ini? File akan dihapus permanen.')) {
        // Add to deletion array
        if (!window.filesToDelete) window.filesToDelete = [];
        window.filesToDelete.push(fileId);
        
        // Remove from current files array
        currentAssignmentFiles = currentAssignmentFiles.filter(f => f.id !== fileId);
        
        // Remove from DOM
        element.closest('.file-item').remove();
        updateEditAssignmentFileCounter();
        
        console.log('File marked for deletion:', fileId);
    }
}

// Utility functions
function getFileIconClass(filename) {
    if (!filename) return 'ti ti-file';
    
    const ext = filename.toLowerCase().split('.').pop();
    const iconMap = {
        'pdf': 'ti ti-file-type-pdf',
        'doc': 'ti ti-file-type-doc',
        'docx': 'ti ti-file-type-doc',
        'xls': 'ti ti-file-type-xls',
        'xlsx': 'ti ti-file-type-xls', 
        'ppt': 'ti ti-file-type-ppt',
        'pptx': 'ti ti-file-type-ppt',
        'txt': 'ti ti-file-text',
        'jpg': 'ti ti-photo',
        'jpeg': 'ti ti-photo',
        'png': 'ti ti-photo',
        'gif': 'ti ti-photo',
        'mp4': 'ti ti-video',
        'mp3': 'ti ti-music',
        'avi': 'ti ti-video',
        'mov': 'ti ti-video',
        'zip': 'ti ti-file-zip',
        'rar': 'ti ti-file-zip'
    };
    
    return iconMap[ext] || 'ti ti-file';
}

function formatFileSize(bytes) {
    if (bytes === 0) return '0 Bytes';
    
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}

// Enhanced assignment content rendering for multiple file types
function enhanceAssignmentFileDisplay() {
    // This function will be called after posts are loaded to enhance file displays
    const assignmentFiles = document.querySelectorAll('[data-assignment-file]');
    
    assignmentFiles.forEach(fileElement => {
        const filePath = fileElement.dataset.assignmentFile;
        const fileName = filePath.split('/').pop();
        const fileIcon = getFileIconClass(fileName);
        
        // Find icon element and update it
        const iconElement = fileElement.querySelector('.file-icon');
        if (iconElement) {
            iconElement.className = `file-icon ${fileIcon} text-blue-600 text-xl`;
        }
    });
}

// Form submission helpers
function prepareAssignmentFilesForSubmission() {
    console.log('✏️ [DEBUG] prepareAssignmentFilesForSubmission called with', assignmentFilesArray.length, 'files');
    // Create FormData and append files
    const formData = new FormData();
    
    assignmentFilesArray.forEach((file, index) => {
        console.log('✏️ [DEBUG] Appending file to FormData:', file.name);
        formData.append(`assignment_files[]`, file);
    });
    
    console.log('✏️ [DEBUG] FormData prepared for submission');
    return formData;
}

function prepareEditAssignmentFilesForSubmission() {
    // Create FormData for new files
    const formData = new FormData();
    
    editAssignmentFilesArray.forEach((file, index) => {
        formData.append(`assignment_files[]`, file);
    });
    
    // Add files to delete
    if (window.filesToDelete && window.filesToDelete.length > 0) {
        formData.append('files_to_delete', JSON.stringify(window.filesToDelete));
    }
    
    return formData;
}

// Modal close handlers
function resetAssignmentFileManager() {
    clearAssignmentFiles();
    // Clear the file input
    const input = document.getElementById('assignment-files');
    if (input) input.value = '';
}

function resetEditAssignmentFileManager() {
    clearEditAssignmentFiles();
    currentAssignmentFiles = [];
    window.filesToDelete = [];
    // Clear the file input
    const input = document.getElementById('editAssignmentFiles');
    if (input) input.value = '';
}

// Call enhancement after page loads
document.addEventListener('DOMContentLoaded', function() {
    // Initialize file counters
    updateAssignmentFileCounter();
    updateEditAssignmentFileCounter();
    
    // Delay to ensure posts are loaded
    setTimeout(enhanceAssignmentFileDisplay, 1000);
});