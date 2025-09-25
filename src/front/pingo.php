<!-- cek sekarang ada di halaman apa -->
<?php 
session_start();
$currentPage = 'pingo'; 

// Check if user is logged in
if (!isset($_SESSION['user'])) {
    header("Location: ../../login.php");
    exit();
}
?>
<!-- includes -->
<?php require '../component/sidebar.php'; ?>
<?php require '../component/menu-bar-mobile.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php require '../../assets/head.php'; ?>
    <link rel="stylesheet" href="../pingo/chat.css">
    <title>Pingo</title>
</head>
<body class="">

    <!-- Main Content -->
    <div data-main-content class="md:ml-64 h-screen pb-16 md:pb-0 transition-all duration-300 ease-in-out flex flex-col main-content-blur">
        <!-- Header -->
        <header class="bg-white p-3 md:p-6 flex-shrink-0 border-b border-gray-200">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-lg md:text-2xl font-bold text-gray-800">Pingo</h1>
                    <p class="text-xs md:text-sm text-gray-600">Tanya apapun tentang pembelajaran</p>
                </div>
                <div class="chat-actions">
                    <button id="clear-button" class="chat-button" title="Hapus Chat">
                        <i class="ti ti-trash text-base md:text-lg"></i>
                        <span class="hidden sm:inline">Hapus Chat</span>
                    </button>
                    <button class="p-1.5 md:p-2 text-gray-400 hover:text-gray-600 transition-colors">
                        <i class="ti ti-bell text-base md:text-xl"></i>
                    </button>
                    <button class="p-1.5 md:p-2 text-gray-400 hover:text-gray-600 transition-colors">
                        <i class="ti ti-search text-base md:text-xl"></i>
                    </button>
                </div>
            </div>
        </header>

        <!-- Chat Container -->
        <main class="flex-1 flex flex-col overflow-hidden">
            <!-- Chat Messages Area -->
            <div class="flex-1 bg-white mx-3 md:mx-6 mb-2 md:mb-4 flex flex-col overflow-hidden relative">
                
                <!-- Empty State -->
                <div id="chat-empty-state" class="chat-empty-state">
                    <div class="empty-state-content">
                        <div class="empty-state-greeting">
                            <div class="pingo-icon">
                                AI
                            </div>
                            <h1>Halo, saya Pingo!</h1>
                            <p>Saya siap membantu Anda dengan pertanyaan seputar pembelajaran. Tanya apa saja yang ingin Anda ketahui!</p>
                        </div>
                        
                        <!-- Empty State Input -->
                        <div class="empty-state-input">
                            <!-- Document Preview Area for Empty State -->
                            <div id="document-preview-area-empty" class="document-preview-area hidden">
                                <div class="document-thumbnails">
                                    <!-- Document thumbnails will be added here -->
                                </div>
                            </div>
                            
                            <div class="input-wrapper">
                                <div class="attachment-button-wrapper">
                                    <button id="attachment-button" type="button" title="Tambah dokumen, gambar, atau tugas">
                                        <i class="ti ti-plus"></i>
                                    </button>
                                </div>
                                <textarea 
                                    id="chat-input" 
                                    placeholder="Tanya sesuatu tentang pembelajaran..."
                                    rows="1"
                                ></textarea>
                                <div class="send-button-wrapper">
                                    <button id="send-button" type="button">
                                        <i class="ti ti-send"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Chat Messages -->
                <div id="chat-messages" class="flex-1 overflow-y-auto">
                    <!-- Chat messages akan dimuat di sini via JavaScript -->
                </div>

                <!-- Chat Input (Hidden initially) -->
                <div id="chat-input-container" class="chat-input-container" style="display: none;">
                    <div class="input-group">
                        <!-- Document Preview Area -->
                        <div id="document-preview-area" class="document-preview-area hidden">
                            <div class="document-thumbnails">
                                <!-- Document thumbnails will be added here -->
                            </div>
                        </div>
                        
                        <div class="input-wrapper">
                            <div class="attachment-button-wrapper">
                                <button id="attachment-button-active" type="button" title="Tambah dokumen, gambar, atau tugas">
                                    <i class="ti ti-plus"></i>
                                </button>
                            </div>
                            <textarea 
                                id="chat-input-active" 
                                placeholder="Ketik pesan Anda di sini..."
                                rows="1"
                            ></textarea>
                            <div class="send-button-wrapper">
                                <button id="send-button-active" type="button">
                                    <i class="ti ti-send"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Floating Attachment Modal Container - Outside of all other elements -->
    <div id="floating-attachment-container" class="fixed top-0 left-0 w-full h-full pointer-events-none z-[10000]" style="display: none;">
        <div id="attachment-modal" class="attachment-modal pointer-events-auto">
            <div class="attachment-modal-content">
                <div class="attachment-options">
                    <div class="attachment-option" data-type="document">
                        <div class="attachment-icon">
                            <i class="ti ti-file-text"></i>
                        </div>
                        <span>Dokumen</span>
                    </div>
                    <div class="attachment-option" data-type="image">
                        <div class="attachment-icon">
                            <i class="ti ti-photo"></i>
                        </div>
                        <span>Gambar</span>
                    </div>
                    <div class="attachment-option" data-type="task">
                        <div class="attachment-icon">
                            <i class="ti ti-clipboard-list"></i>
                        </div>
                        <span>Tugas</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Task Selection Modal (mobileProfileContent style) -->
    <div id="taskSelectionModal" class="fixed inset-0 z-50 hidden">
        <div id="taskSelectionBackdrop" class="fixed inset-0 bg-gray-500/75 opacity-0 transition-opacity duration-300 ease-out" onclick="closeTaskModal()"></div>
        <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
            <div id="taskSelectionContent" class="relative w-full max-w-sm sm:max-w-md transform overflow-hidden rounded-xl bg-white text-left shadow-xl transition-all duration-300 ease-out translate-y-full">
                <div class="bg-white px-4 pt-5 pb-4 sm:px-7 sm:pt-7 sm:pb-5">
                    <div class="flex items-start">
                        <div class="flex items-center justify-center w-12 h-12 rounded-full bg-orange-100 sm:w-12 sm:h-12">
                            <i class="ti ti-clipboard-list text-orange-600 text-xl"></i>
                        </div>
                        <div class="ml-4 text-left">
                            <h3 class="text-base font-semibold text-gray-900">Pilih Tugas</h3>
                            <p class="text-sm text-gray-500">Pilih tugas yang ingin dilampirkan</p>
                        </div>
                        <button onclick="closeTaskModal()" class="ml-auto p-2 text-gray-400 hover:text-gray-600">
                            <i class="ti ti-x text-lg"></i>
                        </button>
                    </div>

                    <div class="mt-6 space-y-2" id="taskList">
                        <!-- Dynamic task list will be loaded here -->
                        <div class="task-item flex items-center space-x-3 px-3 py-3 rounded-md hover:bg-gray-50 transition cursor-pointer" onclick="selectTask(1, 'Tugas Matematika - Aljabar', 'Matematika')">
                            <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
                                <i class="ti ti-math text-blue-600"></i>
                            </div>
                            <div class="flex-1">
                                <div class="text-sm font-medium text-gray-900">Tugas Matematika - Aljabar</div>
                                <div class="text-xs text-gray-500">Matematika • Deadline: 25 Sep 2025</div>
                            </div>
                        </div>
                        
                        <div class="task-item flex items-center space-x-3 px-3 py-3 rounded-md hover:bg-gray-50 transition cursor-pointer" onclick="selectTask(2, 'Essay Sejarah Indonesia', 'Sejarah')">
                            <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center">
                                <i class="ti ti-book text-green-600"></i>
                            </div>
                            <div class="flex-1">
                                <div class="text-sm font-medium text-gray-900">Essay Sejarah Indonesia</div>
                                <div class="text-xs text-gray-500">Sejarah • Deadline: 28 Sep 2025</div>
                            </div>
                        </div>
                        
                        <div class="task-item flex items-center space-x-3 px-3 py-3 rounded-md hover:bg-gray-50 transition cursor-pointer" onclick="selectTask(3, 'Praktikum Fisika - Gerak', 'Fisika')">
                            <div class="w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center">
                                <i class="ti ti-atom text-purple-600"></i>
                            </div>
                            <div class="flex-1">
                                <div class="text-sm font-medium text-gray-900">Praktikum Fisika - Gerak</div>
                                <div class="text-xs text-gray-500">Fisika • Deadline: 30 Sep 2025</div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Footer -->
                <div class="bg-gray-50 px-4 py-3 sm:px-7 sm:py-4">
                    <button onclick="closeTaskModal()" class="w-full px-4 py-2 text-gray-600 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors text-sm">
                        Batal
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- File Attachment Preview Container -->
    <div id="filePreviewContainer" class="fixed inset-x-0 bottom-0 z-40 hidden">
        <div class="bg-white border-t border-gray-200 shadow-lg">
            <div class="max-w-2xl mx-auto px-4 py-3">
                <div class="flex items-center justify-between mb-3">
                    <h4 class="text-sm font-medium text-gray-900">File Terlampir</h4>
                    <button onclick="clearAttachments()" class="text-xs text-red-600 hover:text-red-800">
                        Hapus Semua
                    </button>
                </div>
                <div id="attachmentList" class="space-y-2">
                    <!-- Attachment items will be added here -->
                </div>
            </div>
        </div>
    </div>

    <script src="../script/menu-bar-script.js"></script>
    <script src="../pingo/chat.js"></script>
    
    <script>
        // Attachment Modal Functionality
        document.addEventListener('DOMContentLoaded', function() {
            const attachmentButtons = [
                document.getElementById('attachment-button'),
                document.getElementById('attachment-button-active')
            ];
            const floatingContainer = document.getElementById('floating-attachment-container');
            const attachmentModal = document.getElementById('attachment-modal');
            const attachmentOptions = document.querySelectorAll('.attachment-option');
            
            // Show modal when attachment button is clicked
            attachmentButtons.forEach(button => {
                if (button) {
                    button.addEventListener('click', function(e) {
                        e.preventDefault();
                        e.stopPropagation();
                        
                        // Show the floating container
                        floatingContainer.style.display = 'block';
                        
                        // Calculate position similar to beranda-guru.php dropdown
                        const buttonRect = button.getBoundingClientRect();
                        const modalContent = attachmentModal.querySelector('.attachment-modal-content');
                        
                        // Position the modal content near the button
                        let left = buttonRect.left - 50; // Better offset
                        let top = buttonRect.bottom + 8; // Position below button
                        
                        // Adjust if modal goes off-screen
                        const modalWidth = 200; // Approximate width
                        const modalHeight = 150; // Approximate height
                        
                        if (left < 8) left = 8;
                        if (left + modalWidth > window.innerWidth - 8) {
                            left = window.innerWidth - modalWidth - 8;
                        }
                        if (top + modalHeight > window.innerHeight - 8) {
                            top = buttonRect.top - modalHeight - 8; // Position above if no space below
                        }
                        
                        // Apply positioning
                        modalContent.style.position = 'fixed';
                        modalContent.style.left = left + 'px';
                        modalContent.style.top = top + 'px';
                        modalContent.style.bottom = 'auto';
                        modalContent.style.transform = 'none';
                    });
                }
            });
            
            // Hide modal when clicking outside
            floatingContainer.addEventListener('click', function(e) {
                if (e.target === floatingContainer) {
                    floatingContainer.style.display = 'none';
                }
            });
            
            // Also close when clicking outside the modal content
            document.addEventListener('click', function(e) {
                const isClickInsideModal = attachmentModal.contains(e.target);
                const isClickOnButton = attachmentButtons.some(button => button && button.contains(e.target));
                
                if (!isClickInsideModal && !isClickOnButton && floatingContainer.style.display === 'block') {
                    floatingContainer.style.display = 'none';
                }
            });
            
            // Handle attachment option clicks
            attachmentOptions.forEach(option => {
                option.addEventListener('click', function(e) {
                    const type = this.getAttribute('data-type');
                    
                    if (type === 'document') {
                        handleDocumentUpload();
                        floatingContainer.style.display = 'none';
                    } else if (type === 'image') {
                        handleImageUpload();
                        floatingContainer.style.display = 'none';
                    } else if (type === 'task') {
                        // Show task selection modal
                        floatingContainer.style.display = 'none';
                        showTaskModal();
                    }
                });
            });
            
            // Handle task selection - remove old handlers
            // (task selection now handled by modal)
            
            // Close modal with Escape key
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') {
                    floatingContainer.style.display = 'none';
                    closeTaskModal();
                    
                    // Clear document previews
                    if (window.pingoChat) {
                        window.pingoChat.clearDocumentPreviews();
                    }
                }
            });
            
            // Close modal on scroll or resize
            window.addEventListener('scroll', () => {
                floatingContainer.style.display = 'none';
            });
            
            window.addEventListener('resize', () => {
                floatingContainer.style.display = 'none';
            });
        });

        // Task Modal Event Listeners
        document.addEventListener('DOMContentLoaded', function() {
            // Close task modal when clicking outside
            const taskModal = document.getElementById('taskSelectionModal');
            if (taskModal) {
                taskModal.addEventListener('click', function(e) {
                    if (e.target === taskModal) {
                        closeTaskModal();
                    }
                });
            }
        });
        
        // Task Modal Functions
        window.showTaskModal = function() {
            const modal = document.getElementById('taskSelectionModal');
            modal.style.display = 'block';
            document.body.style.overflow = 'hidden';
        };

        window.closeTaskModal = function() {
            const modal = document.getElementById('taskSelectionModal');
            modal.style.display = 'none';
            document.body.style.overflow = 'auto';
        };

        window.selectTask = function(taskId, taskTitle) {
            // Add task to preview container
            const previewContainer = document.querySelector('.file-preview-container');
            const attachmentList = previewContainer.querySelector('.attachment-list');
            
            // Create task attachment item
            const taskItem = document.createElement('div');
            taskItem.className = 'attachment-item task-attachment';
            taskItem.innerHTML = `
                <div class="flex items-center space-x-3 p-3 bg-blue-50 rounded-lg border border-blue-200">
                    <div class="flex-shrink-0">
                        <i class="ti ti-clipboard-list text-blue-600 text-xl"></i>
                    </div>
                    <div class="flex-grow min-w-0">
                        <p class="text-sm font-medium text-blue-900 truncate">${taskTitle}</p>
                        <p class="text-xs text-blue-600">Tugas dipilih</p>
                    </div>
                    <button onclick="removeAttachment(this)" class="flex-shrink-0 text-red-500 hover:text-red-700">
                        <i class="ti ti-x text-lg"></i>
                    </button>
                </div>
            `;
            
            attachmentList.appendChild(taskItem);
            previewContainer.style.display = 'block';
            
            // Close task modal
            closeTaskModal();
            
            // Close attachment modal
            const floatingContainer = document.getElementById('floatingAttachmentContainer');
            floatingContainer.style.display = 'none';
        };

        // File Preview Functions
        window.clearAttachments = function() {
            const attachmentList = document.querySelector('.attachment-list');
            attachmentList.innerHTML = '';
            const previewContainer = document.querySelector('.file-preview-container');
            previewContainer.style.display = 'none';
        };

        window.removeAttachment = function(button) {
            const attachmentItem = button.closest('.attachment-item');
            attachmentItem.remove();
            
            // Hide preview container if no attachments left
            const attachmentList = document.querySelector('.attachment-list');
            if (attachmentList.children.length === 0) {
                const previewContainer = document.querySelector('.file-preview-container');
                previewContainer.style.display = 'none';
            }
        };
        
        // Handler functions for different attachment types
        function handleDocumentUpload() {
            // Create file input for documents
            const input = document.createElement('input');
            input.type = 'file';
            input.accept = '.pdf,.doc,.docx,.txt,.xlsx,.xls,.ppt,.pptx';
            input.style.display = 'none';
            
            input.onchange = function(e) {
                const file = e.target.files[0];
                if (file) {
                    // Use the chat instance method
                    if (window.pingoChat) {
                        window.pingoChat.handleDocumentUpload(file);
                    }
                }
            };
            
            document.body.appendChild(input);
            input.click();
            document.body.removeChild(input);
        }

        function handleImageUpload() {
            // Create file input for images
            const input = document.createElement('input');
            input.type = 'file';
            input.accept = 'image/*';
            input.style.display = 'none';
            
            input.onchange = function(e) {
                const file = e.target.files[0];
                if (file) {
                    // Use the chat instance method like documents
                    if (window.pingoChat) {
                        window.pingoChat.handleImageUpload(file);
                    }
                }
            };
            
            document.body.appendChild(input);
            input.click();
            document.body.removeChild(input);
            
            // Close attachment modal
            const floatingContainer = document.getElementById('floating-attachment-container');
            if (floatingContainer) {
                floatingContainer.style.display = 'none';
            }
        }

        // Add file to preview container
        function addFileToPreview(type, file) {
            const previewContainer = document.querySelector('.file-preview-container');
            const attachmentList = previewContainer.querySelector('.attachment-list');
            
            // Create file attachment item
            const fileItem = document.createElement('div');
            fileItem.className = `attachment-item ${type}-attachment`;
            
            let icon = type === 'image' ? 'ti ti-photo' : 'ti ti-file-text';
            let bgColor = type === 'image' ? 'bg-green-50 border-green-200' : 'bg-orange-50 border-orange-200';
            let textColor = type === 'image' ? 'text-green-900' : 'text-orange-900';
            let iconColor = type === 'image' ? 'text-green-600' : 'text-orange-600';
            let subText = type === 'image' ? 'Gambar dipilih' : 'Dokumen dipilih';
            
            fileItem.innerHTML = `
                <div class="flex items-center space-x-3 p-3 ${bgColor} rounded-lg border">
                    <div class="flex-shrink-0">
                        <i class="${icon} ${iconColor} text-xl"></i>
                    </div>
                    <div class="flex-grow min-w-0">
                        <p class="text-sm font-medium ${textColor} truncate">${file.name}</p>
                        <p class="text-xs ${iconColor}">${subText} (${formatFileSize(file.size)})</p>
                    </div>
                    <button onclick="removeAttachment(this)" class="flex-shrink-0 text-red-500 hover:text-red-700">
                        <i class="ti ti-x text-lg"></i>
                    </button>
                </div>
            `;
            
            attachmentList.appendChild(fileItem);
            previewContainer.style.display = 'block';
        }

        // Format file size
        function formatFileSize(bytes) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        }
        
        // Legacy function for file reference insertion (kept for compatibility)
        function insertFileReference(type, name, file, taskId = null) {
            const activeInput = document.getElementById('chat-input-active');
            const emptyStateInput = document.getElementById('chat-input');
            const currentInput = activeInput.style.display !== 'none' ? activeInput : emptyStateInput;
            
            let referenceText = '';
            
            if (type === 'document') {
                referenceText = `📄 ${name}\n`;
            } else if (type === 'image') {
                referenceText = `🖼️ ${name}\n`;
            } else if (type === 'task') {
                referenceText = `📋 ${name}\n`;
            }
            
            // Add reference to current input value
            const currentValue = currentInput.value;
            currentInput.value = referenceText + currentValue;
            currentInput.focus();
            
            // Auto-resize textarea
            currentInput.style.height = 'auto';
            currentInput.style.height = currentInput.scrollHeight + 'px';
            
            console.log(`${type} selected:`, { name, file, taskId });
        }
        
        // Chat functionality is now handled by backend API switcher system
        
        // Pass user info to JavaScript
        window.userName = '<?php echo addslashes($_SESSION['user']['namaLengkap'] ?? 'User'); ?>';
        window.userEmail = '<?php echo addslashes($_SESSION['user']['email'] ?? ''); ?>';
    </script>
</body>
</html>