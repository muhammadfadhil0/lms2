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
    <style>
        /* Assignment Chooser Modal Styles */
        .assignment-item {
            transition: all 0.2s ease;
        }

        .assignment-item:hover {
            box-shadow: 0 1px 3px 0 rgb(0 0 0 / 0.1), 0 1px 2px -1px rgb(0 0 0 / 0.1);
        }

        .assignment-item.selected {
            box-shadow: 0 0 0 2px rgb(59 130 246 / 0.5);
        }

        .assignment-item:hover .ti-chevron-right {
            transform: translateX(4px);
            transition: transform 0.2s ease;
        }

        .assignment-deadline-urgent {
            background-color: rgb(254 242 242);
            color: rgb(220 38 38);
        }

        .assignment-deadline-soon {
            background-color: rgb(255 247 237);
            color: rgb(234 88 12);
        }

        .assignment-deadline-normal {
            background-color: rgb(243 244 246);
            color: rgb(75 85 99);
        }

        /* Custom scrollbar untuk assignment list */
        #assignmentItems::-webkit-scrollbar {
            width: 6px;
        }

        #assignmentItems::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 3px;
        }

        #assignmentItems::-webkit-scrollbar-thumb {
            background: #c1c1c1;
            border-radius: 3px;
        }

        #assignmentItems::-webkit-scrollbar-thumb:hover {
            background: #a1a1a1;
        }

        /* Line clamp utility */
        .line-clamp-2 {
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        /* Task thumbnail specific styles to match document thumbnails structure */
        .task-thumbnail .task-thumbnail-preview {
            position: relative;
            width: 100%;
            height: 120px;
            border-radius: 8px 8px 0 0;
            overflow: hidden;
        }
        
        /* Ensure task thumbnails use same layout as document thumbnails */
        .task-thumbnail {
            position: relative;
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 1px 3px 0 rgb(0 0 0 / 0.1);
            transition: all 0.2s ease;
        }
        
        .task-thumbnail:hover {
            box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -1px rgb(0 0 0 / 0.06);
        }

        /* Status colors for task icons */
        .task-thumbnail .text-red-600 {
            color: #dc2626 !important;
        }

        .task-thumbnail .text-orange-600 {
            color: #ea580c !important;
        }

        .task-thumbnail .text-blue-600 {
            color: #2563eb !important;
        }

        /* Drag and Drop Styles */
        .drag-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            background: rgba(0, 0, 0, 0.3);
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
            z-index: 99999;
            display: flex;
            align-items: center;
            justify-content: center;
            pointer-events: none;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .drag-overlay.active {
            opacity: 1;
            visibility: visible;
        }

        .drag-message {
            font-size: 2rem;
            font-weight: 600;
            color: white;
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.5);
            text-align: center;
            letter-spacing: 0.025em;
            transform: scale(0.9);
            transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .drag-overlay.active .drag-message {
            transform: scale(1);
        }

        .chat-container.drag-over {
            background: rgba(59, 130, 246, 0.05);
        }

        /* Enhanced Error Message Styles */
        .chat-message.error .message-content {
            background: linear-gradient(135deg, #fef2f2 0%, #fee2e2 100%);
            border-left: 4px solid #ef4444;
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: 0 4px 6px -1px rgba(239, 68, 68, 0.1);
        }

        .chat-message.error .message-content h3,
        .chat-message.error .message-content h4 {
            color: #dc2626;
            margin-bottom: 0.75rem;
        }

        .chat-message.error .message-content ul {
            margin: 0.5rem 0;
            padding-left: 1.5rem;
        }

        .chat-message.error .message-content li {
            margin: 0.25rem 0;
            color: #7f1d1d;
        }

        .chat-message.error .message-content p {
            color: #991b1b;
            line-height: 1.6;
        }

        /* Rate Limit Specific Styling */
        .rate-limit-message {
            background: linear-gradient(135deg, #fefce8 0%, #fef3c7 100%);
            border-left: 4px solid #f59e0b;
            color: #92400e;
        }

        .rate-limit-message h3,
        .rate-limit-message h4 {
            color: #d97706 !important;
        }

        /* Chunked Document Styles */
        .chunked-document .document-thumbnail-meta {
            color: #059669;
            font-weight: 500;
            font-size: 0.75rem;
        }

        .chunked-indicator {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 24px;
            height: 24px;
            background: #dcfce7;
            border-radius: 50%;
            color: #059669;
            font-size: 12px;
        }

        .document-thumbnail-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 12px;
        }

        /* Task Thumbnail Consistent Structure */
        .task-thumbnail {
            position: relative;
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 1px 3px 0 rgb(0 0 0 / 0.1);
            transition: all 0.2s ease;
            padding: 12px;
            min-height: 80px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .task-thumbnail:hover {
            box-shadow: 0 4px 12px 0 rgb(0 0 0 / 0.15);
        }

        .task-thumbnail .document-thumbnail-header {
            margin-bottom: 8px;
        }

        .task-thumbnail .document-thumbnail-title {
            font-size: 0.875rem;
            font-weight: 500;
            color: #1F2937;
            line-height: 1.3;
            overflow: hidden;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            line-clamp: 2;
            -webkit-box-orient: vertical;
            word-break: break-words;
            margin: 0;
        }

        .task-thumbnail .document-thumbnail-footer {
            padding: 0;
            margin-top: auto;
        }

        /* Document Thumbnail Consistent Structure */
        .chunked-document {
            padding: 0;
            min-height: 120px;
        }

        .chunked-document .document-thumbnail-header {
            padding: 12px;
            flex: 1;
        }

        .chunked-document .document-thumbnail-footer {
            padding: 8px 12px;
            border-top: 1px solid #f3f4f6;
            margin: 0;
        }

        .upload-progress-notification {
            animation: slideInRight 0.3s ease-out;
        }

        @keyframes slideInRight {
            from {
                opacity: 0;
                transform: translateX(100%);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }
    </style>
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
                        <i class="ti ti-circle-plus text-base md:text-lg"></i>
                        <span class="hidden sm:inline">Baru</span>
                    </button>
                    <!-- Debug: Test drag overlay button -->
                    <button onclick="testDragOverlay()" class="p-1.5 md:p-2 text-gray-400 hover:text-gray-600 transition-colors" title="Test Drag Overlay">
                        <i class="ti ti-upload text-base md:text-xl"></i>
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
                            
                            <!-- Claude-style input wrapper for empty state -->
                            <div class="claude-input-container">
                                <div class="claude-input-wrapper">
                                    <div class="claude-input-content">
                                        <div class="claude-textarea-container">
                                            <textarea 
                                                id="chat-input" 
                                                placeholder="Tanya sesuatu tentang pembelajaran..."
                                                rows="1"
                                                class="claude-textarea"
                                            ></textarea>
                                        </div>
                                        <div class="claude-controls">
                                            <div class="claude-left-controls">
                                                <button id="attachment-button" type="button" title="Tambah dokumen, gambar, atau tugas" class="claude-control-btn">
                                                    <i class="ti ti-plus"></i>
                                                </button>
                                                <button type="button" title="Tools" class="claude-control-btn">
                                                    <i class="ti ti-adjustments"></i>
                                                </button>
                                            </div>
                                            <div class="claude-right-controls">
                                                <div class="claude-model-selector">
                                                    <button type="button" class="claude-model-btn">
                                                        <span>Pingo AI</span>
                                                        <i class="ti ti-chevron-down"></i>
                                                    </button>
                                                </div>
                                                <button id="send-button" type="button" class="claude-send-btn" disabled>
                                                    <i class="ti ti-arrow-up"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="claude-input-footer"></div>
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
                        
                        <!-- Claude-style input wrapper -->
                        <div class="claude-input-container">
                            <div class="claude-input-wrapper">
                                <div class="claude-input-content">
                                    <div class="claude-textarea-container">
                                        <textarea 
                                            id="chat-input-active" 
                                            placeholder="Ketik pesan Anda di sini..."
                                            rows="1"
                                            class="claude-textarea"
                                        ></textarea>
                                    </div>
                                    <div class="claude-controls">
                                        <div class="claude-left-controls">
                                            <button id="attachment-button-active" type="button" title="Tambah dokumen, gambar, atau tugas" class="claude-control-btn">
                                                <i class="ti ti-plus"></i>
                                            </button>
                                            <button type="button" title="Tools" class="claude-control-btn">
                                                <i class="ti ti-adjustments"></i>
                                            </button>
                                        </div>
                                        <div class="claude-right-controls">
                                            <div class="claude-model-selector">
                                                <button type="button" class="claude-model-btn">
                                                    <span>Pingo AI</span>
                                                    <i class="ti ti-chevron-down"></i>
                                                </button>
                                            </div>
                                            <button id="send-button-active" type="button" class="claude-send-btn" disabled>
                                                <i class="ti ti-arrow-up"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="claude-input-footer"></div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Drag and Drop Overlay -->
    <div id="drag-overlay" class="drag-overlay">
        <div class="drag-message">
            Taruh dan lepas file di sini
        </div>
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
                        <div class="flex items-center gap-2">
                            <span>Tugas</span>
                            <span style="font-size: 0.7rem;" class="inline-flex items-center rounded-md bg-orange-50 px-1.5 py-0.5 text-xs font-small text-orange-700 ring-1 ring-inset ring-orange-600/20">
                                BETA
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Task Selection Modal - Enhanced Version -->
    <div id="taskSelectionModal" class="fixed inset-0 z-50 hidden">
        <div id="taskSelectionBackdrop" class="fixed inset-0 bg-gray-500/75 opacity-0 transition-opacity duration-300 ease-out" onclick="closeTaskModal()"></div>
        <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
            <div id="taskSelectionContent" class="relative w-full max-w-2xl transform overflow-hidden rounded-xl bg-white text-left shadow-xl transition-all duration-300 ease-out translate-y-full">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="sm:flex sm:items-start">
                        <div class="mx-auto flex size-12 shrink-0 items-center justify-center rounded-full bg-blue-100 sm:mx-0 sm:size-10">
                            <i class="ti ti-clipboard-text text-blue-600 text-xl"></i>
                        </div>
                        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
                                Pilih Tugas untuk Analisis AI
                                <span class="inline-flex items-center rounded-md bg-orange-50 px-2 py-1 text-xs font-medium text-orange-700 ring-1 ring-inset ring-orange-600/20">
                                    BETA
                                </span>
                            </h3>
                            
                            <!-- Loading State -->
                            <div id="assignmentLoadingState" class="flex items-center justify-center py-8 hidden">
                                <i class="ti ti-loader-2 text-2xl animate-spin text-gray-400 mr-2"></i>
                                <span class="text-gray-500">Memuat daftar tugas...</span>
                            </div>

                            <!-- Error State -->
                            <div id="assignmentErrorState" class="hidden">
                                <div class="bg-red-50 border border-red-200 rounded-md p-4">
                                    <div class="flex">
                                        <i class="ti ti-alert-circle text-red-400 text-lg mr-2"></i>
                                        <div>
                                            <h4 class="text-sm font-medium text-red-800">Gagal memuat tugas</h4>
                                            <p class="text-sm text-red-700 mt-1" id="assignmentErrorMessage">Terjadi kesalahan saat mengambil data tugas.</p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Assignment List -->
                            <div id="assignmentListContainer" class="mt-4">
                                <div class="max-h-96 overflow-y-auto">
                                    <!-- Filter Section -->
                                    <div class="mb-4 p-3 bg-gray-50 rounded-lg">
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Filter berdasarkan kelas:</label>
                                        <select id="classFilter" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                            <option value="">Semua Kelas</option>
                                            <!-- Options akan diisi via JavaScript -->
                                        </select>
                                    </div>

                                    <!-- Assignment Items Container -->
                                    <div id="assignmentItems" class="space-y-3">
                                        <!-- Assignment items akan diisi via JavaScript -->
                                    </div>

                                    <!-- Empty State -->
                                    <div id="assignmentEmptyState" class="hidden text-center py-8">
                                        <i class="ti ti-clipboard-off text-4xl text-gray-300 mb-2"></i>
                                        <p class="text-gray-500 text-sm">Tidak ada tugas yang ditemukan.</p>
                                        <p class="text-gray-400 text-xs mt-1">Anda belum memiliki tugas di kelas yang diikuti.</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <button onclick="closeTaskModal()" class="ml-auto p-2 text-gray-400 hover:text-gray-600">
                            <i class="ti ti-x text-lg"></i>
                        </button>
                    </div>
                </div>
                
                <div class="bg-gray-50 px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6">
                    <button id="selectAssignmentBtn" type="button" class="inline-flex w-full justify-center rounded-md bg-blue-600 px-3 py-2 text-sm font-semibold text-white shadow-xs hover:bg-blue-500 sm:ml-3 sm:w-auto items-center disabled:opacity-50 disabled:cursor-not-allowed" disabled onclick="handleTaskSelection()">
                        <span class="select-assignment-btn-text">Pilih Tugas</span>
                        <i class="ti ti-loader-2 text-sm ml-2 animate-spin hidden select-assignment-btn-loading"></i>
                    </button>
                    <button onclick="closeTaskModal()" type="button" class="mt-3 inline-flex w-full justify-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-xs ring-1 ring-inset ring-gray-300 hover:bg-gray-50 sm:mt-0 sm:w-auto">
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

    <!-- Delete Chat Modal -->
    <?php require '../component/modal-delete-chat.php'; ?>

    <script src="../script/menu-bar-script.js"></script>
    <script src="../pingo/chat.js"></script>
    <script src="../script/document-chunking.js"></script>
    <script src="../script/simple-document-thumbnail.js"></script>
    <script src="../script/pingo-chunking-integration.js"></script>
    
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
                        
                        // Don't auto-switch to chat mode when opening attachment modal
                        // Chat mode will be activated when message is sent
                        
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
                        openTaskModal();
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
        
        // Legacy task modal functions - kept for compatibility
        window.showTaskModal = function() {
            openTaskModal();
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
        
        // Delete Chat Modal Functions
        window.showDeleteChatModal = function() {
            const modal = document.getElementById('deleteChatModal');
            if (modal) {
                modal.showModal();
                document.body.style.overflow = 'hidden';
            }
        };

        window.hideDeleteChatModal = function() {
            const modal = document.getElementById('deleteChatModal');
            if (modal) {
                modal.close();
                document.body.style.overflow = 'auto';
            }
        };

        // Delete Chat Modal Event Listeners
        document.addEventListener('DOMContentLoaded', function() {
            const deleteChatModal = document.getElementById('deleteChatModal');
            const confirmDeleteChatBtn = document.getElementById('confirmDeleteChatBtn');
            const cancelDeleteChatBtn = document.getElementById('cancelDeleteChatBtn');
            
            // Close modal when clicking outside
            if (deleteChatModal) {
                deleteChatModal.addEventListener('click', function(e) {
                    if (e.target === deleteChatModal) {
                        hideDeleteChatModal();
                    }
                });
            }
            
            // Cancel button
            if (cancelDeleteChatBtn) {
                cancelDeleteChatBtn.addEventListener('click', function() {
                    hideDeleteChatModal();
                });
            }
            
            // Confirm delete button
            if (confirmDeleteChatBtn) {
                confirmDeleteChatBtn.addEventListener('click', async function() {
                    // Show loading state
                    const btnText = this.querySelector('.delete-chat-btn-text');
                    const btnLoading = this.querySelector('.delete-chat-btn-loading');
                    
                    if (btnText && btnLoading) {
                        btnText.textContent = 'Menghapus...';
                        btnLoading.classList.remove('hidden');
                        this.disabled = true;
                    }
                    
                    try {
                        // Call the clearChat method from PingoChat
                        if (window.pingoChat) {
                            await window.pingoChat.performClearChat();
                        }
                        
                        // Close modal
                        hideDeleteChatModal();
                        
                    } catch (error) {
                        console.error('Error clearing chat:', error);
                        alert('Terjadi kesalahan saat menghapus chat');
                    } finally {
                        // Reset button state
                        if (btnText && btnLoading) {
                            btnText.textContent = 'Hapus Semua';
                            btnLoading.classList.add('hidden');
                            this.disabled = false;
                        }
                    }
                });
            }
            
            // Close modal with Escape key
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape' && deleteChatModal && deleteChatModal.open) {
                    hideDeleteChatModal();
                }
            });
        });

        // Assignment Chooser Modal Logic
        let selectedAssignmentId = null;
        let assignments = [];
        let classes = [];
        let currentFilter = '';

        // Load assignments and classes
        async function loadAssignments() {
            const loadingState = document.getElementById('assignmentLoadingState');
            const errorState = document.getElementById('assignmentErrorState');
            const listContainer = document.getElementById('assignmentListContainer');

            showAssignmentLoading(true);
            showAssignmentError(false);

            try {
                // Load classes and assignments in parallel
                const [classesResponse, assignmentsResponse] = await Promise.all([
                    fetch('../api/get-assignments.php?action=get_classes'),
                    fetch('../api/get-assignments.php?action=get_assignments')
                ]);

                if (!classesResponse.ok || !assignmentsResponse.ok) {
                    throw new Error('Failed to fetch data');
                }

                const classesData = await classesResponse.json();
                const assignmentsData = await assignmentsResponse.json();

                if (!classesData.success) {
                    throw new Error(classesData.message || 'Failed to load classes');
                }

                if (!assignmentsData.success) {
                    throw new Error(assignmentsData.message || 'Failed to load assignments');
                }

                classes = classesData.classes || [];
                assignments = assignmentsData.assignments || [];

                populateClassFilter();
                displayAssignments();
                showAssignmentLoading(false);

            } catch (error) {
                console.error('Error loading assignments:', error);
                showAssignmentError(true, error.message);
                showAssignmentLoading(false);
            }
        }

        // Populate class filter
        function populateClassFilter() {
            const classFilter = document.getElementById('classFilter');
            if (!classFilter) return;

            classFilter.innerHTML = '<option value="">Semua Kelas</option>';

            classes.forEach(kelas => {
                const option = document.createElement('option');
                option.value = kelas.id;
                option.textContent = `${kelas.namaKelas}`;
                classFilter.appendChild(option);
            });

            // Add change event listener
            classFilter.addEventListener('change', function() {
                currentFilter = this.value;
                filterAndDisplayAssignments();
            });
        }

        // Filter and display assignments
        function filterAndDisplayAssignments() {
            let filteredAssignments = assignments;

            if (currentFilter) {
                filteredAssignments = assignments.filter(assignment => 
                    assignment.kelas.id == currentFilter
                );
            }

            displayAssignments(filteredAssignments);
        }

        // Display assignments
        function displayAssignments(assignmentsToShow = null) {
            const itemsContainer = document.getElementById('assignmentItems');
            const emptyState = document.getElementById('assignmentEmptyState');
            
            if (!itemsContainer) return;

            const assignmentsList = assignmentsToShow || assignments;

            itemsContainer.innerHTML = '';

            if (assignmentsList.length === 0) {
                showEmptyState(true);
                return;
            }

            showEmptyState(false);

            assignmentsList.forEach(assignment => {
                const item = createAssignmentItem(assignment);
                itemsContainer.appendChild(item);
            });
        }

        // Create assignment item element
        function createAssignmentItem(assignment) {
            const item = document.createElement('div');
            item.className = 'assignment-item border border-gray-200 rounded-lg p-4 cursor-pointer hover:bg-blue-50 hover:border-blue-300 transition-all duration-200';
            item.dataset.assignmentId = assignment.id;

            // Determine deadline class
            let deadlineClass = 'text-gray-600 bg-gray-100 px-2 py-1 rounded-md text-xs font-medium';
            if (assignment.deadline_status === 'urgent') {
                deadlineClass = 'text-red-600 bg-red-100 px-2 py-1 rounded-md text-xs font-medium';
            } else if (assignment.deadline_status === 'soon') {
                deadlineClass = 'text-orange-600 bg-orange-100 px-2 py-1 rounded-md text-xs font-medium';
            } else if (assignment.deadline_status === 'overdue') {
                deadlineClass = 'text-red-600 bg-red-100 px-2 py-1 rounded-md text-xs font-medium';
            }

            // Create submission info
            let submissionInfo = '';
            if (assignment.submission) {
                if (assignment.submission.is_submitted) {
                    const statusText = assignment.submission.status === 'dinilai' ? 'Sudah Dinilai' : 'Sudah Dikumpulkan';
                    const statusClass = assignment.submission.status === 'dinilai' ? 'text-green-600' : 'text-blue-600';
                    submissionInfo = `
                        <div class="flex items-center text-xs mt-2">
                            <i class="ti ti-check-circle ${statusClass} mr-1"></i>
                            <span class="${statusClass}">${statusText}</span>
                            ${assignment.submission.nilai ? `<span class="ml-2 text-gray-500">Nilai: ${assignment.submission.nilai}</span>` : ''}
                        </div>
                    `;
                } else {
                    submissionInfo = `
                        <div class="flex items-center text-xs mt-2">
                            <i class="ti ti-clock text-orange-500 mr-1"></i>
                            <span class="text-orange-600">Belum Dikumpulkan</span>
                        </div>
                    `;
                }
            } else if (assignment.submissions) {
                submissionInfo = `
                    <div class="flex items-center text-xs mt-2 text-gray-600">
                        <i class="ti ti-users mr-1"></i>
                        <span>${assignment.submissions.total} pengumpulan, ${assignment.submissions.graded} dinilai</span>
                    </div>
                `;
            }

            item.innerHTML = `
                <div class="flex items-start justify-between">
                    <div class="flex-1">
                        <div class="flex items-center mb-2">
                            <h4 class="font-semibold text-gray-900 text-sm">${escapeHtml(assignment.judul)}</h4>
                            <span class="${deadlineClass} ml-2">${assignment.deadline_formatted}</span>
                        </div>
                        <p class="text-xs text-gray-600 mb-2">${escapeHtml(assignment.kelas.nama)} - ${escapeHtml(assignment.kelas.mata_pelajaran)}</p>
                        <p class="text-xs text-gray-500 line-clamp-2">${escapeHtml(assignment.deskripsi)}</p>
                        ${submissionInfo}
                    </div>
                    <div class="ml-4 flex-shrink-0">
                        <i class="ti ti-chevron-right text-gray-400"></i>
                    </div>
                </div>
            `;

            // Add click event
            item.addEventListener('click', () => {
                selectAssignment(assignment.id);
            });

            return item;
        }

        // Select assignment
        function selectAssignment(assignmentId) {
            // Remove previous selection
            document.querySelectorAll('.assignment-item').forEach(item => {
                item.classList.remove('selected', 'bg-blue-50', 'border-blue-500', 'ring-2', 'ring-blue-200');
            });

            // Add selection to clicked item
            const selectedItem = document.querySelector(`[data-assignment-id="${assignmentId}"]`);
            if (selectedItem) {
                selectedItem.classList.add('selected', 'bg-blue-50', 'border-blue-500', 'ring-2', 'ring-blue-200');
                selectedAssignmentId = assignmentId;
                updateSelectButton();
            }
        }

        // Update select button state
        function updateSelectButton() {
            const selectBtn = document.getElementById('selectAssignmentBtn');
            if (!selectBtn) return;

            selectBtn.disabled = !selectedAssignmentId;
        }

        // Handle task selection
        async function handleTaskSelection() {
            console.log('👍 DEBUG: Starting handleTaskSelection');
            console.log('👍 DEBUG: selectedAssignmentId:', selectedAssignmentId);
            
            if (!selectedAssignmentId) {
                console.log('👍 DEBUG: No assignment selected, returning');
                return;
            }

            const selectedAssignment = assignments.find(a => a.id == selectedAssignmentId);
            console.log('👍 DEBUG: Found selectedAssignment:', selectedAssignment);
            console.log('👍 DEBUG: All assignments array:', assignments);
            
            if (!selectedAssignment) {
                console.log('👍 DEBUG: Assignment not found in array, returning');
                return;
            }

            const selectBtn = document.getElementById('selectAssignmentBtn');
            const btnText = selectBtn?.querySelector('.select-assignment-btn-text');
            const btnLoader = selectBtn?.querySelector('.select-assignment-btn-loading');

            // Show loading state
            if (selectBtn) selectBtn.disabled = true;
            if (btnText) btnText.textContent = 'Memproses...';
            if (btnLoader) btnLoader.classList.remove('hidden');

            try {
                console.log('👍 DEBUG: Adding task to preview...');
                // Add task thumbnail to preview container
                addTaskToPreview(selectedAssignment);
                
                console.log('👍 DEBUG: Closing task modal...');
                // Close task modal
                closeTaskModal();
                
                console.log('👍 DEBUG: Preparing assignment for AI...');
                // Prepare assignment data for AI (don't send automatically)
                await prepareAssignmentForAI(selectedAssignment);
                console.log('👍 DEBUG: Assignment preparation completed');

            } catch (error) {
                console.error('👍 DEBUG: Error in handleTaskSelection:', error);
                console.error('👍 DEBUG: Error stack:', error.stack);
                alert('Terjadi kesalahan saat memproses tugas yang dipilih.');
            } finally {
                // Reset button state
                if (selectBtn) selectBtn.disabled = !selectedAssignmentId;
                if (btnText) btnText.textContent = 'Pilih Tugas';
                if (btnLoader) btnLoader.classList.add('hidden');
                console.log('👍 DEBUG: handleTaskSelection completed');
            }
        }

        // Add task to preview container (only thumbnail)
        function addTaskToPreview(assignment) {
            // Determine which preview area to use
            const emptyState = document.getElementById('chat-empty-state');
            const emptyStatePreview = document.getElementById('document-preview-area-empty');
            const activeStatePreview = document.getElementById('document-preview-area');
            const isEmptyStateVisible = emptyState && emptyState.style.display !== 'none';
            
            if (isEmptyStateVisible && emptyStatePreview) {
                // Use empty state preview
                const thumbnailsContainer = emptyStatePreview.querySelector('.document-thumbnails');
                if (thumbnailsContainer) {
                    addTaskThumbnail(thumbnailsContainer, assignment);
                    emptyStatePreview.classList.remove('hidden');
                }
            } else if (activeStatePreview) {
                // Use active state preview
                const thumbnailsContainer = activeStatePreview.querySelector('.document-thumbnails');
                if (thumbnailsContainer) {
                    addTaskThumbnail(thumbnailsContainer, assignment);
                    activeStatePreview.classList.remove('hidden');
                }
            }
        }

        // Create task thumbnail like document thumbnails
        function addTaskThumbnail(container, assignment) {
            // Check if task already exists
            const existingTask = container.querySelector(`[data-assignment-id="${assignment.id}"]`);
            if (existingTask) {
                return; // Don't add duplicate
            }
            
            const thumbnail = document.createElement('div');
            thumbnail.className = 'document-thumbnail task-thumbnail';
            thumbnail.dataset.assignmentId = assignment.id;
            
            // Format deadline
            const deadline = new Date(assignment.deadline);
            const deadlineFormatted = deadline.toLocaleDateString('id-ID', { 
                day: 'numeric', 
                month: 'short' 
            });
            
            // Determine status color and icon
            let statusColor = 'blue';
            let statusIcon = 'ti-clipboard-text';
            if (assignment.deadline_status === 'urgent' || assignment.deadline_status === 'overdue') {
                statusColor = 'red';
                statusIcon = 'ti-alert-circle';
            } else if (assignment.deadline_status === 'soon') {
                statusColor = 'orange';
                statusIcon = 'ti-clock';
            }

            // Get task type/extension equivalent  
            let taskType = 'TASK';
            if (assignment.kelas?.mata_pelajaran) {
                const mapel = assignment.kelas.mata_pelajaran.toUpperCase();
                if (mapel.includes('MATEMATIKA') || mapel.includes('MATH')) {
                    taskType = 'MTK';
                } else if (mapel.includes('BAHASA INDONESIA') || mapel.includes('INDO')) {
                    taskType = 'INDO';
                } else if (mapel.includes('BAHASA INGGRIS') || mapel.includes('ENG')) {
                    taskType = 'ENG';
                } else if (mapel.includes('FISIKA') || mapel.includes('FIS')) {
                    taskType = 'FIS';
                } else if (mapel.includes('KIMIA') || mapel.includes('KIM')) {
                    taskType = 'KIM';
                } else if (mapel.includes('BIOLOGI') || mapel.includes('BIO')) {
                    taskType = 'BIO';
                } else if (mapel.includes('SEJARAH') || mapel.includes('SEJ')) {
                    taskType = 'SEJ';
                } else if (mapel.includes('GEOGRAFI') || mapel.includes('GEO')) {
                    taskType = 'GEO';
                } else if (mapel.length <= 4) {
                    taskType = mapel;
                } else {
                    taskType = mapel.substring(0, 4);
                }
            }

            thumbnail.innerHTML = `
                <div class="document-thumbnail-header">
                    <h3 class="document-thumbnail-title" title="${escapeHtml(assignment.judul)}">${escapeHtml(assignment.judul)}</h3>
                </div>
                <div class="document-thumbnail-footer">
                    <div class="document-thumbnail-type">${taskType}</div>
                </div>
                <button class="document-remove-btn" onclick="removeTaskThumbnail('${assignment.id}')">
                    <i class="ti ti-x"></i>
                </button>
            `;
            
            container.appendChild(thumbnail);
        }



        // Remove task thumbnail
        window.removeTaskThumbnail = function(assignmentId) {
            // Remove from document thumbnails
            const thumbnails = document.querySelectorAll(`.task-thumbnail[data-assignment-id="${assignmentId}"]`);
            thumbnails.forEach(thumbnail => thumbnail.remove());
            
            // Check if preview areas should be hidden
            const emptyStatePreview = document.getElementById('document-preview-area-empty');
            const activeStatePreview = document.getElementById('document-preview-area');
            
            if (emptyStatePreview) {
                const container = emptyStatePreview.querySelector('.document-thumbnails');
                if (container && container.children.length === 0) {
                    emptyStatePreview.classList.add('hidden');
                }
            }
            
            if (activeStatePreview) {
                const container = activeStatePreview.querySelector('.document-thumbnails');
                if (container && container.children.length === 0) {
                    activeStatePreview.classList.add('hidden');
                }
            }
        };



        // Fetch detailed assignment data
        async function fetchAssignmentDetails(assignmentId) {
            console.log('👍 DEBUG: fetchAssignmentDetails called with ID:', assignmentId);
            
            const url = `../api/get-assignments.php?action=get_assignment_details&assignment_id=${assignmentId}`;
            console.log('👍 DEBUG: Fetching URL:', url);
            
            const response = await fetch(url);
            console.log('👍 DEBUG: Response status:', response.status);
            console.log('👍 DEBUG: Response headers:', Object.fromEntries(response.headers.entries()));
            
            if (!response.ok) {
                console.log('👍 DEBUG: Response not ok, throwing error');
                throw new Error('Failed to fetch assignment details');
            }
            
            const data = await response.json();
            console.log('👍 DEBUG: Raw response data:', data);
            console.log('👍 DEBUG: Response success:', data.success);
            console.log('👍 DEBUG: Response message:', data.message);
            console.log('👍 DEBUG: Response data structure:', JSON.stringify(data, null, 2));
            
            if (!data.success) {
                console.log('👍 DEBUG: Data success is false, throwing error');
                throw new Error(data.message || 'Failed to load assignment details');
            }
            
            console.log('👍 DEBUG: Returning data successfully');
            return data;
        }

        // Generate AI analysis prompt from assignment data
        function generateAssignmentAnalysisPrompt(data) {
            console.log('👍 DEBUG: generateAssignmentAnalysisPrompt called with data:', data);
            
            if (!data) {
                console.log('👍 DEBUG: No data provided to generateAssignmentAnalysisPrompt');
                return null;
            }
            
            const assignment = data.data || data.assignment;
            const context = data.context;
            
            console.log('👍 DEBUG: Assignment object:', assignment);
            console.log('👍 DEBUG: Context object:', context);
            
            if (!assignment) {
                console.log('👍 DEBUG: No assignment found in data');
                return null;
            }
            
            let prompt = `Tolong analisis tugas berikut ini:\n\n`;
            prompt += `📋 **INFORMASI TUGAS:**\n`;
            prompt += `- Judul: ${assignment.judul || 'N/A'}\n`;
            prompt += `- Mata Pelajaran: ${assignment.kelas?.mata_pelajaran || 'N/A'}\n`;
            prompt += `- Kelas: ${assignment.kelas?.nama || 'N/A'}\n`;
            prompt += `- Guru: ${assignment.kelas?.guru_nama || 'N/A'}\n`;
            prompt += `- Deadline: ${assignment.deadline_formatted || assignment.deadline || 'N/A'}\n`;
            prompt += `- Nilai Maksimal: ${assignment.nilai_maksimal || 'N/A'}\n`;
            prompt += `- Status: ${assignment.is_overdue ? '🔴 Terlambat' : `⏰ ${assignment.days_until_deadline || 'N/A'} hari lagi`}\n\n`;
            
            prompt += `📝 **DESKRIPSI TUGAS:**\n${assignment.deskripsi || 'Tidak ada deskripsi'}\n\n`;
            
            if (context) {
                prompt += `📅 **KONTEKS WAKTU:**\n`;
                prompt += `- Hari ini: ${context.day_name || 'N/A'}, ${context.today_formatted || 'N/A'}\n\n`;
            }
            
            if (context.posts_before && context.posts_before.length > 0) {
                prompt += `📚 **POSTINGAN SEBELUM TUGAS:**\n`;
                context.posts_before.forEach((post, index) => {
                    if (index < 3) { // Limit to 3 posts
                        prompt += `${index + 1}. ${post.konten.substring(0, 100)}...\n`;
                    }
                });
                prompt += '\n';
            }
            
            if (context.posts_after && context.posts_after.length > 0) {
                prompt += `📬 **POSTINGAN SETELAH TUGAS:**\n`;
                context.posts_after.forEach((post, index) => {
                    if (index < 3) { // Limit to 3 posts
                        prompt += `${index + 1}. ${post.konten.substring(0, 100)}...\n`;
                    }
                });
                prompt += '\n';
            }
            
            if (data.submission && data.user_role === 'siswa') {
                prompt += `✅ **STATUS PENGUMPULAN:**\n`;
                if (data.submission) {
                    prompt += `- Sudah dikumpulkan pada: ${new Date(data.submission.tanggal_pengumpulan).toLocaleDateString('id-ID')}\n`;
                    if (data.submission.nilai) {
                        prompt += `- Nilai: ${data.submission.nilai}\n`;
                    }
                    if (data.submission.feedback) {
                        prompt += `- Feedback: ${data.submission.feedback}\n`;
                    }
                } else {
                    prompt += `- Belum dikumpulkan\n`;
                }
                prompt += '\n';
            }
            
            if (context.class_stats && data.user_role === 'guru') {
                prompt += `📊 **STATISTIK KELAS:**\n`;
                prompt += `- Total siswa: ${context.class_stats.total_students}\n`;
                prompt += `- Sudah mengumpulkan: ${context.class_stats.submitted_count}\n`;
                prompt += `- Sudah dinilai: ${context.class_stats.graded_count}\n\n`;
            }
            
            prompt += `🤖 **INSTRUKSI PENTING:**\n`;
            prompt += `- JANGAN GUNAKAN TABEL dalam jawaban\n`;
            prompt += `- Jawab SINGKAT dan PADAT, maksimal 3-4 paragraf\n`;
            prompt += `- Fokus pada ANALISIS TUGAS dan TIPS PRAKTIS saja\n`;
            prompt += `- Jangan berikan definisi umum atau teori panjang\n`;
            prompt += `- Gunakan format bullet points (•) untuk daftar\n\n`;
            
            prompt += `Berikan analisis singkat tentang tugas ini dan saran praktis untuk mengerjakannya!`;
            
            return prompt;
        }

        // Prepare assignment for AI (load data but don't send automatically)
        async function prepareAssignmentForAI(assignment) {
            console.log('👍 DEBUG: prepareAssignmentForAI called with:', assignment);
            
            try {
                // Show thinking indicator
                console.log('👍 DEBUG: Showing thinking indicator...');
                await showAIThinkingIndicator(true, 'Memuat data tugas...');
                
                // Fetch detailed assignment data
                console.log('👍 DEBUG: Fetching assignment details for ID:', assignment.id);
                const detailedData = await fetchAssignmentDetails(assignment.id);
                console.log('👍 DEBUG: Received detailed data:', detailedData);
                console.log('👍 DEBUG: Detailed data structure:', JSON.stringify(detailedData, null, 2));
                
                // Update thinking indicator
                await showAIThinkingIndicator(true, 'Menyiapkan konteks...');
                
                // Generate analysis message but don't send
                console.log('👍 DEBUG: Generating analysis prompt...');
                const analysisMessage = generateAssignmentAnalysisPrompt(detailedData);
                console.log('👍 DEBUG: Generated prompt length:', analysisMessage?.length || 'undefined');
                console.log('👍 DEBUG: Generated prompt preview:', analysisMessage?.substring(0, 200) || 'undefined');
                
                // Store assignment data for when user sends message
                window.currentAssignmentData = {
                    assignment: assignment,
                    detailedData: detailedData,
                    analysisPrompt: analysisMessage
                };
                console.log('👍 DEBUG: Stored currentAssignmentData:', window.currentAssignmentData);
                
                // Hide thinking indicator
                showAIThinkingIndicator(false);
                
                // Show success notification
                showTaskReadyNotification(assignment);
                
                console.log('👍 DEBUG: Assignment data preparation completed successfully');
                
            } catch (error) {
                console.error('👍 DEBUG: Error in prepareAssignmentForAI:', error);
                console.error('👍 DEBUG: Error stack:', error.stack);
                showAIThinkingIndicator(false);
                alert('Terjadi kesalahan saat memuat data tugas: ' + error.message);
            }
        }

        // Process assignment with AI when user actually sends message
        async function processAssignmentWithAI(assignmentData) {
            console.log('👍 DEBUG: processAssignmentWithAI called with:', assignmentData);
            
            try {
                // Ensure chat interface is visible
                ensureChatInterfaceVisible();
                
                // Use prepared data if available
                const dataToUse = assignmentData || window.currentAssignmentData;
                console.log('👍 DEBUG: dataToUse:', dataToUse);
                console.log('👍 DEBUG: window.currentAssignmentData exists:', !!window.currentAssignmentData);
                console.log('👍 DEBUG: dataToUse.analysisPrompt exists:', !!dataToUse?.analysisPrompt);
                console.log('👍 DEBUG: analysisPrompt content:', dataToUse?.analysisPrompt?.substring(0, 200));
                
                if (dataToUse && dataToUse.analysisPrompt) {
                    console.log('👍 DEBUG: About to send message to AI');
                    console.log('👍 DEBUG: Full prompt to send:', dataToUse.analysisPrompt);
                    
                    // Send the prepared analysis prompt
                    if (window.pingoChat && typeof window.pingoChat.sendMessage === 'function') {
                        console.log('👍 DEBUG: Using pingoChat.sendMessage');
                        await window.pingoChat.sendMessage(dataToUse.analysisPrompt);
                    } else if (window.pingoChat && typeof window.pingoChat.handleUserMessage === 'function') {
                        console.log('👍 DEBUG: Using pingoChat.handleUserMessage');
                        await window.pingoChat.handleUserMessage(dataToUse.analysisPrompt);
                    } else {
                        console.log('👍 DEBUG: Using fallback addMessageToInput');
                        // Fallback: add to input
                        addMessageToInput(dataToUse.analysisPrompt);
                    }
                } else {
                    console.log('👍 DEBUG: No analysis prompt available');
                }
                
            } catch (error) {
                console.error('👍 DEBUG: Error in processAssignmentWithAI:', error);
                console.error('👍 DEBUG: Error stack:', error.stack);
                alert('Terjadi kesalahan saat memproses tugas dengan AI: ' + error.message);
            }
        }

        // AI Thinking Indicator with fade animations
        let thinkingIndicatorElement = null;
        let thinkingMessages = [
            'Menganalisis tugas...',
            'Memahami konteks pembelajaran...',
            'Mengumpulkan informasi kelas...',
            'Menyiapkan analisis AI...',
            'Menggenerasi insight...'
        ];
        let currentThinkingIndex = 0;
        let thinkingInterval = null;

        async function showAIThinkingIndicator(show, message = null) {
            if (show) {
                // Create or show thinking indicator
                if (!thinkingIndicatorElement) {
                    createThinkingIndicator();
                }
                
                // Position near chat area
                positionThinkingIndicator();
                
                // Show with fade in
                thinkingIndicatorElement.style.display = 'flex';
                thinkingIndicatorElement.style.opacity = '0';
                
                // Fade in animation
                await new Promise(resolve => {
                    requestAnimationFrame(() => {
                        thinkingIndicatorElement.style.transition = 'opacity 0.3s ease-in-out';
                        thinkingIndicatorElement.style.opacity = '1';
                        setTimeout(resolve, 300);
                    });
                });
                
                // Set initial message or start cycling
                if (message) {
                    updateThinkingMessage(message);
                } else {
                    startThinkingAnimation();
                }
                
            } else {
                // Hide with fade out
                if (thinkingIndicatorElement) {
                    stopThinkingAnimation();
                    
                    // Fade out animation
                    await new Promise(resolve => {
                        thinkingIndicatorElement.style.transition = 'opacity 0.3s ease-in-out';
                        thinkingIndicatorElement.style.opacity = '0';
                        
                        setTimeout(() => {
                            thinkingIndicatorElement.style.display = 'none';
                            resolve();
                        }, 300);
                    });
                }
            }
        }

        function createThinkingIndicator() {
            thinkingIndicatorElement = document.createElement('div');
            thinkingIndicatorElement.className = 'ai-thinking-indicator';
            thinkingIndicatorElement.style.cssText = `
                position: fixed;
                top: 50%;
                left: 50%;
                transform: translate(-50%, -50%);
                background: rgba(255, 255, 255, 0.95);
                backdrop-filter: blur(10px);
                border: 1px solid rgba(59, 130, 246, 0.2);
                border-radius: 16px;
                padding: 24px 32px;
                box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
                z-index: 9999;
                display: none;
                align-items: center;
                gap: 16px;
                font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
                max-width: 400px;
                min-width: 280px;
            `;
            
            thinkingIndicatorElement.innerHTML = `
                <div class="thinking-spinner" style="
                    width: 32px;
                    height: 32px;
                    border: 3px solid #e5e7eb;
                    border-top: 3px solid #3b82f6;
                    border-radius: 50%;
                    animation: spin 1s linear infinite;
                "></div>
                <div class="thinking-content">
                    <div class="thinking-title" style="
                        font-weight: 600;
                        color: #1f2937;
                        font-size: 16px;
                        margin-bottom: 4px;
                    ">🤖 Pingo sedang berpikir...</div>
                    <div class="thinking-message" style="
                        color: #6b7280;
                        font-size: 14px;
                        line-height: 1.4;
                    ">Menganalisis tugas...</div>
                </div>
            `;
            
            // Add spinner animation
            const style = document.createElement('style');
            style.textContent = `
                @keyframes spin {
                    0% { transform: rotate(0deg); }
                    100% { transform: rotate(360deg); }
                }
            `;
            document.head.appendChild(style);
            
            document.body.appendChild(thinkingIndicatorElement);
        }

        function positionThinkingIndicator() {
            // Position relative to chat area
            const chatMessages = document.getElementById('chat-messages');
            if (chatMessages) {
                const rect = chatMessages.getBoundingClientRect();
                const centerX = rect.left + rect.width / 2;
                const centerY = rect.top + rect.height / 2;
                
                thinkingIndicatorElement.style.left = centerX + 'px';
                thinkingIndicatorElement.style.top = centerY + 'px';
            }
        }

        function updateThinkingMessage(message) {
            if (thinkingIndicatorElement) {
                const messageEl = thinkingIndicatorElement.querySelector('.thinking-message');
                if (messageEl) {
                    // Fade out current message
                    messageEl.style.opacity = '0.3';
                    
                    setTimeout(() => {
                        messageEl.textContent = message;
                        // Fade in new message
                        messageEl.style.transition = 'opacity 0.3s ease-in-out';
                        messageEl.style.opacity = '1';
                    }, 150);
                }
            }
        }

        function startThinkingAnimation() {
            currentThinkingIndex = 0;
            
            thinkingInterval = setInterval(() => {
                updateThinkingMessage(thinkingMessages[currentThinkingIndex]);
                currentThinkingIndex = (currentThinkingIndex + 1) % thinkingMessages.length;
            }, 2000);
        }

        function stopThinkingAnimation() {
            if (thinkingInterval) {
                clearInterval(thinkingInterval);
                thinkingInterval = null;
            }
        }

        // Ensure chat interface is visible
        function ensureChatInterfaceVisible() {
            const emptyState = document.getElementById('chat-empty-state');
            const chatInputContainer = document.getElementById('chat-input-container');
            
            if (emptyState && emptyState.style.display !== 'none') {
                // Hide empty state and show chat interface
                emptyState.style.display = 'none';
                if (chatInputContainer) {
                    chatInputContainer.style.display = 'block';
                }
            }
        }

        // Add message to input manually
        function addMessageToInput(message) {
            // Try active chat input first
            let targetInput = document.getElementById('chat-input-active');
            
            // If not visible, try empty state input
            if (!targetInput || targetInput.style.display === 'none') {
                targetInput = document.getElementById('chat-input');
            }
            
            if (targetInput) {
                // Ensure chat interface is visible
                ensureChatInterfaceVisible();
                
                targetInput.value = message;
                targetInput.focus();
                
                // Auto-resize textarea
                targetInput.style.height = 'auto';
                targetInput.style.height = targetInput.scrollHeight + 'px';
                
                // Enable send button if available
                const sendButton = document.getElementById('send-button-active') || document.getElementById('send-button');
                if (sendButton) {
                    sendButton.disabled = false;
                }
                
                // Add visual indication that message is ready
                targetInput.style.borderColor = '#3b82f6';
                setTimeout(() => {
                    targetInput.style.borderColor = '';
                }, 2000);
            }
        }

        // Show task ready notification (not processed yet)
        function showTaskReadyNotification(assignment) {
            // Create notification element
            const notification = document.createElement('div');
            notification.className = 'task-processed-notification';
            notification.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                background: linear-gradient(135deg, #10b981 0%, #059669 100%);
                color: white;
                padding: 16px 20px;
                border-radius: 12px;
                box-shadow: 0 10px 25px -5px rgba(16, 185, 129, 0.4);
                z-index: 10000;
                max-width: 350px;
                font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
                opacity: 0;
                transform: translateX(100%);
                transition: all 0.3s ease-out;
            `;
            
            notification.innerHTML = `
                <div style="display: flex; align-items: start; gap: 12px;">
                    <div style="font-size: 24px;">📋</div>
                    <div>
                        <div style="font-weight: 600; margin-bottom: 4px;">Tugas Terlampir!</div>
                        <div style="font-size: 14px; opacity: 0.9; line-height: 1.4;">
                            "${assignment.judul}" siap untuk dianalisis. 
                            Kirim pesan untuk memulai analisis AI.
                        </div>
                    </div>
                    <button onclick="this.parentElement.parentElement.remove()" style="
                        background: rgba(255,255,255,0.2);
                        border: none;
                        color: white;
                        width: 24px;
                        height: 24px;
                        border-radius: 50%;
                        cursor: pointer;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        font-size: 16px;
                        margin-left: auto;
                    ">×</button>
                </div>
            `;
            
            document.body.appendChild(notification);
            
            // Animate in
            requestAnimationFrame(() => {
                notification.style.opacity = '1';
                notification.style.transform = 'translateX(0)';
            });
            
            // Auto remove after 5 seconds
            setTimeout(() => {
                notification.style.opacity = '0';
                notification.style.transform = 'translateX(100%)';
                setTimeout(() => {
                    if (notification.parentElement) {
                        notification.parentElement.removeChild(notification);
                    }
                }, 300);
            }, 5000);
        }

        // Enhanced task selection for legacy compatibility
        window.selectTask = function(taskId, taskTitle, className = '') {
            // Find assignment data if available
            const assignment = assignments.find(a => a.id == taskId) || {
                id: taskId,
                judul: taskTitle,
                kelas: { nama: className }
            };
            
            // Add to preview using new method
            addTaskToPreview(assignment);
            
            // Close modals
            closeTaskModal();
            const floatingContainer = document.getElementById('floating-attachment-container');
            if (floatingContainer) {
                floatingContainer.style.display = 'none';
            }
            
            // Process with AI if detailed data available
            if (assignments.find(a => a.id == taskId)) {
                processAssignmentWithAI(assignment);
            }
        };

        // Utility functions
        function showAssignmentLoading(show) {
            const loadingState = document.getElementById('assignmentLoadingState');
            const listContainer = document.getElementById('assignmentListContainer');
            
            if (loadingState) loadingState.classList.toggle('hidden', !show);
            if (listContainer) listContainer.classList.toggle('hidden', show);
        }

        function showAssignmentError(show, message = '') {
            const errorState = document.getElementById('assignmentErrorState');
            const errorMessage = document.getElementById('assignmentErrorMessage');
            const listContainer = document.getElementById('assignmentListContainer');
            
            if (errorState) errorState.classList.toggle('hidden', !show);
            if (errorMessage && message) errorMessage.textContent = message;
            if (listContainer) listContainer.classList.toggle('hidden', show);
        }

        function showEmptyState(show) {
            const emptyState = document.getElementById('assignmentEmptyState');
            if (emptyState) emptyState.classList.toggle('hidden', !show);
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        // Enhanced task modal functions
        function openTaskModal() {
            const modal = document.getElementById('taskSelectionModal');
            const backdrop = document.getElementById('taskSelectionBackdrop');
            const content = document.getElementById('taskSelectionContent');
            
            if (modal && backdrop && content) {
                // Reset state
                selectedAssignmentId = null;
                updateSelectButton();
                
                // Show modal
                modal.classList.remove('hidden');
                
                // Animate in
                requestAnimationFrame(() => {
                    backdrop.classList.add('opacity-100');
                    backdrop.classList.remove('opacity-0');
                    content.classList.add('translate-y-0', 'sm:scale-100');
                    content.classList.remove('translate-y-full', 'sm:scale-95');
                });
                
                // Load assignments
                loadAssignments();
            }
        }

        function closeTaskModal() {
            const modal = document.getElementById('taskSelectionModal');
            const backdrop = document.getElementById('taskSelectionBackdrop');
            const content = document.getElementById('taskSelectionContent');
            
            if (modal && backdrop && content) {
                // Animate out
                backdrop.classList.add('opacity-0');
                backdrop.classList.remove('opacity-100');
                content.classList.add('translate-y-full', 'sm:scale-95');
                content.classList.remove('translate-y-0', 'sm:scale-100');
                
                // Hide modal after animation
                setTimeout(() => {
                    modal.classList.add('hidden');
                }, 300);
            }
        }

        // Pass user info to JavaScript
        window.userName = '<?php echo addslashes($_SESSION['user']['namaLengkap'] ?? 'User'); ?>';
        window.userEmail = '<?php echo addslashes($_SESSION['user']['email'] ?? ''); ?>';
        
        // Intercept PingoChat sendMessage after it's initialized
        function interceptPingoChatSendMessage() {
            if (!window.pingoChat || !window.pingoChat.sendMessage) {
                // Try again in 100ms if PingoChat not ready
                setTimeout(interceptPingoChatSendMessage, 100);
                return;
            }
            
            console.log('👍 DEBUG: Intercepting PingoChat sendMessage');
            
            // Store original sendMessage
            const originalSendMessage = window.pingoChat.sendMessage.bind(window.pingoChat);
            
            // Replace with our intercepted version
            window.pingoChat.sendMessage = async function() {
                console.log('👍 DEBUG: Intercepted sendMessage call');
                console.log('👍 DEBUG: Checking for assignment data...');
                console.log('👍 DEBUG: window.currentAssignmentData exists:', !!window.currentAssignmentData);
                
                // Check if we have assignment data to include
                if (window.currentAssignmentData && window.currentAssignmentData.analysisPrompt) {
                    console.log('👍 DEBUG: Found assignment data, enriching message...');
                    
                    // Get current input value
                    const isEmptyState = this.chatEmptyState.style.display !== 'none';
                    const currentInput = isEmptyState ? this.chatInput : this.chatInputActive;
                    const userMessage = currentInput.value.trim();
                    
                    console.log('👍 DEBUG: User message:', userMessage);
                    console.log('👍 DEBUG: Analysis prompt preview:', window.currentAssignmentData.analysisPrompt.substring(0, 200));
                    
                    if (userMessage) {
                        // Combine user message with assignment analysis
                        const combinedMessage = `${userMessage}\n\n${window.currentAssignmentData.analysisPrompt}`;
                        
                        console.log('👍 DEBUG: Sending combined message to AI');
                        console.log('👍 DEBUG: Combined message length:', combinedMessage.length);
                        
                        // Create task attachment data BEFORE clearing anything
                        const taskAttachment = window.currentAssignmentData ? {
                            type: 'mixed',
                            documents: [],
                            images: [],
                            tasks: [{
                                id: window.currentAssignmentData.assignment?.id || window.currentAssignmentData.detailedData?.data?.id,
                                name: window.currentAssignmentData.assignment?.judul || window.currentAssignmentData.detailedData?.data?.judul,
                                subject: window.currentAssignmentData.assignment?.kelas?.mata_pelajaran || window.currentAssignmentData.detailedData?.data?.kelas?.mata_pelajaran,
                                deadline: window.currentAssignmentData.assignment?.deadline_formatted || window.currentAssignmentData.detailedData?.data?.deadline_formatted,
                                analysisPrompt: window.currentAssignmentData.analysisPrompt
                            }],
                            totalCount: 1
                        } : null;
                        
                        console.log('👍 DEBUG: Created task attachment:', taskAttachment);
                        
                        // Clear the input 
                        currentInput.value = '';
                        this.autoResize(currentInput);
                        this.updateSendButtonState();
                        
                        // Show chat interface if it's the first message (from empty state)
                        if (this.chatEmptyState.style.display !== 'none') {
                            this.showChatInterface();
                        }
                        
                        // Clear document previews (including task thumbnails) IMMEDIATELY after input cleared
                        this.clearDocumentPreviews();
                        
                        // Clear assignment data immediately after thumbnails cleared
                        window.currentAssignmentData = null;
                        
                        // Send message with task attachment
                        await this.sendMessageWithContent(combinedMessage, userMessage, taskAttachment);
                        
                        console.log('👍 DEBUG: Message sent and thumbnails cleared immediately');
                        
                        return;
                    }
                }
                
                // No assignment data, proceed with normal message
                console.log('👍 DEBUG: No assignment data, proceeding with normal message');
                return originalSendMessage();
            };
            
            console.log('👍 DEBUG: PingoChat sendMessage successfully intercepted');
        }
        
        // Start intercepting after page loads
        document.addEventListener('DOMContentLoaded', () => {
            setTimeout(interceptPingoChatSendMessage, 500);
            
            // Initialize drag and drop functionality
            setTimeout(() => {
                initializeDragAndDrop();
                
                // Also try alternative simpler approach
                initializeSimpleDragAndDrop();
            }, 1000);
        });

        // Drag and Drop Functionality
        function initializeDragAndDrop() {
            console.log('🔄 Initializing drag and drop...');
            
            const dragOverlay = document.getElementById('drag-overlay');
            const chatContainer = document.querySelector('[data-main-content]');
            let dragCounter = 0;

            console.log('🔍 Drag overlay element:', dragOverlay);
            console.log('🔍 Chat container element:', chatContainer);

            if (!dragOverlay) {
                console.error('❌ Drag overlay not found!');
                return;
            }

            // Prevent default drag behaviors on document and body
            function preventDefaults(e) {
                e.preventDefault();
                e.stopPropagation();
            }

            ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
                document.addEventListener(eventName, preventDefaults, false);
                document.body.addEventListener(eventName, preventDefaults, false);
            });

            // Handle drag enter
            document.addEventListener('dragenter', function(e) {
                console.log('📥 Drag enter detected');
                
                // Check if it's a file drag (not text/element drag)
                if (e.dataTransfer.types.includes('Files')) {
                    dragCounter++;
                    console.log('📁 File drag detected, counter:', dragCounter);
                    
                    if (dragCounter === 1) {
                        console.log('✨ Showing drag overlay');
                        dragOverlay.classList.add('active');
                        if (chatContainer) {
                            chatContainer.classList.add('drag-over');
                        }
                    }
                }
            });

            // Handle drag over (required for drop to work)
            document.addEventListener('dragover', function(e) {
                if (e.dataTransfer.types.includes('Files')) {
                    e.dataTransfer.dropEffect = 'copy';
                }
            });

            // Handle drag leave
            document.addEventListener('dragleave', function(e) {
                // Only count if it's leaving the document body/window
                if (e.target === document.body || e.target === document.documentElement) {
                    dragCounter--;
                    console.log('📤 Drag leave detected, counter:', dragCounter);
                    
                    if (dragCounter <= 0) {
                        dragCounter = 0;
                        console.log('🔄 Hiding drag overlay');
                        dragOverlay.classList.remove('active');
                        if (chatContainer) {
                            chatContainer.classList.remove('drag-over');
                        }
                    }
                }
            });

            // Handle drop
            document.addEventListener('drop', function(e) {
                console.log('🎯 Drop detected');
                
                dragCounter = 0;
                dragOverlay.classList.remove('active');
                if (chatContainer) {
                    chatContainer.classList.remove('drag-over');
                }

                const files = e.dataTransfer.files;
                console.log('📁 Files dropped:', files.length);
                
                if (files.length > 0) {
                    handleDroppedFiles(files);
                } else {
                    console.warn('⚠️ No files detected in drop event');
                }
            });

            console.log('✅ Drag and drop initialized successfully');
        }

        // Simpler drag and drop approach (backup)
        function initializeSimpleDragAndDrop() {
            console.log('🔄 Initializing simple drag and drop backup...');
            
            const dragOverlay = document.getElementById('drag-overlay');
            
            if (!dragOverlay) {
                console.error('❌ Drag overlay not found for simple approach!');
                return;
            }

            // Add global drag event listeners with smooth animations
            let dragActive = false;
            let dragCounter = 0;

            // Listen on document instead of window for better compatibility
            document.addEventListener('dragenter', function(e) {
                console.log('📥 Document dragenter event:', e.dataTransfer?.types);
                
                // Check if dragging files
                if (e.dataTransfer && e.dataTransfer.types && e.dataTransfer.types.includes('Files')) {
                    console.log('✅ Files detected in drag');
                    dragCounter++;
                    
                    if (!dragActive) {
                        dragActive = true;
                        // Use requestAnimationFrame for smooth animation
                        requestAnimationFrame(() => {
                            dragOverlay.classList.add('active');
                        });
                    }
                    e.preventDefault();
                } else {
                    console.log('❌ No files in drag, types:', e.dataTransfer?.types);
                }
            }, true);

            document.addEventListener('dragover', function(e) {
                console.log('🔄 Document dragover');
                
                if (e.dataTransfer && e.dataTransfer.types && e.dataTransfer.types.includes('Files')) {
                    e.preventDefault();
                    e.dataTransfer.dropEffect = 'copy';
                } else {
                    e.preventDefault(); // Prevent anyway to avoid browser default
                }
            }, true);

            document.addEventListener('dragleave', function(e) {
                console.log('📤 Document dragleave, target:', e.target.tagName);
                
                // Decrease counter and hide overlay if no more drag events
                dragCounter--;
                
                if (dragCounter <= 0 && dragActive) {
                    dragCounter = 0;
                    dragActive = false;
                    console.log('🚪 Hiding drag overlay with fade out');
                    
                    // Use timeout to allow smooth fade out transition
                    requestAnimationFrame(() => {
                        dragOverlay.classList.remove('active');
                    });
                }
            }, true);

            document.addEventListener('drop', function(e) {
                console.log('🎯 Document drop event triggered!');
                console.log('🎯 Drop target:', e.target.tagName, e.target.className);
                console.log('🎯 DataTransfer files:', e.dataTransfer?.files?.length || 0);
                
                e.preventDefault();
                e.stopPropagation();
                
                // Reset drag state and hide overlay smoothly
                dragActive = false;
                dragCounter = 0;
                
                // Add slight delay before hiding to show drop feedback
                setTimeout(() => {
                    requestAnimationFrame(() => {
                        dragOverlay.classList.remove('active');
                    });
                }, 100);
                
                const files = e.dataTransfer?.files;
                if (files && files.length > 0) {
                    console.log('📁 Processing', files.length, 'dropped files');
                    handleDroppedFiles(files);
                } else {
                    console.warn('⚠️ No files in drop event');
                }
            }, true);

            // Also add body listeners as fallback
            document.body.addEventListener('drop', function(e) {
                console.log('🎯 Body drop event as fallback');
                e.preventDefault();
                
                const files = e.dataTransfer?.files;
                if (files && files.length > 0) {
                    console.log('📁 Body fallback: Processing', files.length, 'files');
                    handleDroppedFiles(files);
                }
            });

            console.log('✅ Simple drag and drop initialized with enhanced debugging');
        }

        // Handle dropped files
        function handleDroppedFiles(files) {
            console.log('📁 Files dropped:', files.length);
            
            for (let i = 0; i < files.length; i++) {
                const file = files[i];
                console.log('📁 Processing file:', file.name, 'Type:', file.type, 'Size:', file.size);
                
                // Detect file type and add to thumbnails
                const fileType = detectFileType(file);
                console.log('📁 Detected type:', fileType);
                
                if (fileType) {
                    addFileToThumbnails(file, fileType);
                    showFileProcessedNotification(file.name, fileType);
                } else {
                    showUnsupportedFileNotification(file.name);
                }
            }
        }

        // Detect file type based on MIME type and extension
        function detectFileType(file) {
            const mimeType = file.type.toLowerCase();
            const fileName = file.name.toLowerCase();
            const extension = fileName.split('.').pop();

            // Image types
            const imageTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml'];
            const imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'];

            // Document types
            const documentTypes = [
                'application/pdf',
                'application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'application/vnd.ms-excel',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'application/vnd.ms-powerpoint',
                'application/vnd.openxmlformats-officedocument.presentationml.presentation',
                'text/plain',
                'text/csv'
            ];
            const documentExtensions = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt', 'csv'];

            // Check by MIME type first
            if (imageTypes.includes(mimeType)) {
                return 'image';
            }
            if (documentTypes.includes(mimeType)) {
                return 'document';
            }

            // Fallback to extension check
            if (imageExtensions.includes(extension)) {
                return 'image';
            }
            if (documentExtensions.includes(extension)) {
                return 'document';
            }

            return null; // Unsupported file type
        }

        // Add file to document thumbnails
        function addFileToThumbnails(file, fileType) {
            console.log('🖼️ Adding file to thumbnails:', file.name, 'Type:', fileType);
            
            // Integrate with PingoChat system if available
            if (window.pingoChat) {
                if (fileType === 'document' && typeof window.pingoChat.handleDocumentUpload === 'function') {
                    console.log('📄 Using PingoChat document handler');
                    window.pingoChat.handleDocumentUpload(file);
                    return;
                } else if (fileType === 'image' && typeof window.pingoChat.handleImageUpload === 'function') {
                    console.log('🖼️ Using PingoChat image handler');
                    window.pingoChat.handleImageUpload(file);
                    return;
                }
            }
            
            // Fallback: Manual thumbnail creation
            console.log('📁 Using manual thumbnail creation');
            
            // Determine which preview area to use
            const emptyState = document.getElementById('chat-empty-state');
            const emptyStatePreview = document.getElementById('document-preview-area-empty');
            const activeStatePreview = document.getElementById('document-preview-area');
            const isEmptyStateVisible = emptyState && emptyState.style.display !== 'none';
            
            let targetContainer = null;
            
            if (isEmptyStateVisible && emptyStatePreview) {
                targetContainer = emptyStatePreview.querySelector('.document-thumbnails');
                emptyStatePreview.classList.remove('hidden');
            } else if (activeStatePreview) {
                targetContainer = activeStatePreview.querySelector('.document-thumbnails');
                activeStatePreview.classList.remove('hidden');
            }
            
            if (!targetContainer) {
                console.error('❌ Could not find thumbnail container');
                return;
            }
            
            // Create thumbnail element
            const thumbnail = createFileThumbnail(file, fileType);
            targetContainer.appendChild(thumbnail);
            
            console.log('✅ File thumbnail added successfully');
        }

        // Create file thumbnail element
        function createFileThumbnail(file, fileType) {
            const thumbnail = document.createElement('div');
            thumbnail.className = 'document-thumbnail';
            thumbnail.dataset.fileName = file.name;
            thumbnail.dataset.fileType = fileType;
            thumbnail.dataset.fileSize = file.size;
            
            // Generate unique ID for this file
            const fileId = 'file_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
            thumbnail.dataset.fileId = fileId;
            
            // Get file extension for display
            const extension = file.name.split('.').pop().toUpperCase();
            
            // Determine icon and colors based on file type
            let icon, bgColor, borderColor;
            if (fileType === 'image') {
                icon = 'ti ti-photo';
                bgColor = '#f0fdf4'; // green-50
                borderColor = '#22c55e'; // green-500
            } else {
                // Document type - determine by extension
                if (['PDF'].includes(extension)) {
                    icon = 'ti ti-file-type-pdf';
                    bgColor = '#fef2f2'; // red-50
                    borderColor = '#ef4444'; // red-500
                } else if (['DOC', 'DOCX'].includes(extension)) {
                    icon = 'ti ti-file-type-docx';
                    bgColor = '#eff6ff'; // blue-50
                    borderColor = '#3b82f6'; // blue-500
                } else if (['XLS', 'XLSX'].includes(extension)) {
                    icon = 'ti ti-file-spreadsheet';
                    bgColor = '#f0fdf4'; // green-50
                    borderColor = '#22c55e'; // green-500
                } else if (['PPT', 'PPTX'].includes(extension)) {
                    icon = 'ti ti-presentation';
                    bgColor = '#fff7ed'; // orange-50
                    borderColor = '#f97316'; // orange-500
                } else {
                    icon = 'ti ti-file-text';
                    bgColor = '#f9fafb'; // gray-50
                    borderColor = '#6b7280'; // gray-500
                }
            }
            
            thumbnail.innerHTML = `
                <div class="document-thumbnail-preview" style="background: ${bgColor}; display: flex; align-items: center; justify-content: center; height: 120px; border-radius: 8px 8px 0 0;">
                    <i class="${icon}" style="font-size: 32px; color: ${borderColor};"></i>
                </div>
                <div class="document-thumbnail-header">
                    <h3 class="document-thumbnail-title" title="${escapeHtml(file.name)}">${escapeHtml(file.name)}</h3>
                    <p class="document-thumbnail-meta">${formatFileSize(file.size)}</p>
                </div>
                <div class="document-thumbnail-footer">
                    <div class="document-thumbnail-type">${extension}</div>
                </div>
                <button class="document-remove-btn" onclick="removeFileThumbnail('${fileId}')">
                    <i class="ti ti-x"></i>
                </button>
            `;
            
            // Store file reference for later use
            thumbnail._fileReference = file;
            
            return thumbnail;
        }

        // Remove file thumbnail
        window.removeFileThumbnail = function(fileId) {
            const thumbnails = document.querySelectorAll(`[data-file-id="${fileId}"]`);
            thumbnails.forEach(thumbnail => thumbnail.remove());
            
            // Check if preview areas should be hidden
            const emptyStatePreview = document.getElementById('document-preview-area-empty');
            const activeStatePreview = document.getElementById('document-preview-area');
            
            if (emptyStatePreview) {
                const container = emptyStatePreview.querySelector('.document-thumbnails');
                if (container && container.children.length === 0) {
                    emptyStatePreview.classList.add('hidden');
                }
            }
            
            if (activeStatePreview) {
                const container = activeStatePreview.querySelector('.document-thumbnails');
                if (container && container.children.length === 0) {
                    activeStatePreview.classList.add('hidden');
                }
            }
        };

        // Show file processed notification
        function showFileProcessedNotification(fileName, fileType) {
            const notification = document.createElement('div');
            notification.className = 'file-processed-notification';
            notification.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                background: linear-gradient(135deg, #10b981 0%, #059669 100%);
                color: white;
                padding: 16px 20px;
                border-radius: 12px;
                box-shadow: 0 10px 25px -5px rgba(16, 185, 129, 0.4);
                z-index: 10000;
                max-width: 350px;
                font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
                opacity: 0;
                transform: translateX(100%);
                transition: all 0.3s ease-out;
            `;
            
            const typeIcon = fileType === 'image' ? '🖼️' : '📄';
            const typeText = fileType === 'image' ? 'Gambar' : 'Dokumen';
            
            notification.innerHTML = `
                <div style="display: flex; align-items: start; gap: 12px;">
                    <div style="font-size: 24px;">${typeIcon}</div>
                    <div>
                        <div style="font-weight: 600; margin-bottom: 4px;">${typeText} Ditambahkan</div>
                        <div style="font-size: 14px; opacity: 0.9; line-height: 1.4;">${fileName}</div>
                    </div>
                    <button onclick="this.parentElement.parentElement.remove()" style="
                        background: none;
                        border: none;
                        color: white;
                        font-size: 18px;
                        cursor: pointer;
                        opacity: 0.7;
                        transition: opacity 0.2s;
                        padding: 0;
                        width: 20px;
                        height: 20px;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        border-radius: 4px;
                        margin-left: auto;
                    " onmouseover="this.style.opacity='1'" onmouseout="this.style.opacity='0.7'">×</button>
                </div>
            `;
            
            document.body.appendChild(notification);
            
            // Animate in
            requestAnimationFrame(() => {
                notification.style.opacity = '1';
                notification.style.transform = 'translateX(0)';
            });
            
            // Auto remove after 3 seconds
            setTimeout(() => {
                notification.style.opacity = '0';
                notification.style.transform = 'translateX(100%)';
                setTimeout(() => {
                    if (notification.parentElement) {
                        notification.parentElement.removeChild(notification);
                    }
                }, 300);
            }, 3000);
        }

        // Show unsupported file notification
        function showUnsupportedFileNotification(fileName) {
            const notification = document.createElement('div');
            notification.className = 'file-error-notification';
            notification.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
                color: white;
                padding: 16px 20px;
                border-radius: 12px;
                box-shadow: 0 10px 25px -5px rgba(239, 68, 68, 0.4);
                z-index: 10000;
                max-width: 350px;
                font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
                opacity: 0;
                transform: translateX(100%);
                transition: all 0.3s ease-out;
            `;
            
            notification.innerHTML = `
                <div style="display: flex; align-items: start; gap: 12px;">
                    <div style="font-size: 24px;">❌</div>
                    <div>
                        <div style="font-weight: 600; margin-bottom: 4px;">File Tidak Didukung</div>
                        <div style="font-size: 14px; opacity: 0.9; line-height: 1.4;">${fileName}</div>
                        <div style="font-size: 12px; opacity: 0.7; margin-top: 4px;">Hanya gambar dan dokumen yang didukung</div>
                    </div>
                    <button onclick="this.parentElement.parentElement.remove()" style="
                        background: none;
                        border: none;
                        color: white;
                        font-size: 18px;
                        cursor: pointer;
                        opacity: 0.7;
                        transition: opacity 0.2s;
                        padding: 0;
                        width: 20px;
                        height: 20px;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        border-radius: 4px;
                        margin-left: auto;
                    " onmouseover="this.style.opacity='1'" onmouseout="this.style.opacity='0.7'">×</button>
                </div>
            `;
            
            document.body.appendChild(notification);
            
            // Animate in
            requestAnimationFrame(() => {
                notification.style.opacity = '1';
                notification.style.transform = 'translateX(0)';
            });
            
            // Auto remove after 4 seconds
            setTimeout(() => {
                notification.style.opacity = '0';
                notification.style.transform = 'translateX(100%)';
                setTimeout(() => {
                    if (notification.parentElement) {
                        notification.parentElement.removeChild(notification);
                    }
                }, 300);
            }, 4000);
        }

        // Test function for drag overlay
        window.testDragOverlay = function() {
            console.log('🧪 Testing drag overlay...');
            const dragOverlay = document.getElementById('drag-overlay');
            
            if (dragOverlay) {
                console.log('✅ Drag overlay found, showing...');
                console.log('📍 Current style:', dragOverlay.style.cssText);
                console.log('📍 Current classes:', dragOverlay.className);
                
                dragOverlay.classList.add('active');
                
                console.log('📍 After adding active:', dragOverlay.className);
                
                setTimeout(() => {
                    console.log('🔄 Hiding drag overlay...');
                    dragOverlay.classList.remove('active');
                }, 3000);
            } else {
                console.error('❌ Drag overlay not found!');
                alert('Drag overlay element tidak ditemukan!');
            }
        };

        // Manual test function for file drop simulation
        window.testFileDrop = function() {
            console.log('🧪 Simulating file drop...');
            
            // Create a fake file object for testing
            const fakeFile = new File(['test content'], 'test.txt', { type: 'text/plain' });
            const fakeFiles = [fakeFile];
            
            console.log('📁 Calling handleDroppedFiles with fake file...');
            handleDroppedFiles(fakeFiles);
        };
    </script>
</body>
</html>