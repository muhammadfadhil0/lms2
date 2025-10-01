/**
 * Simple Document Thumbnail Display
 * Menambahkan thumbnail dokumen di atas chat message user
 */

// Fungsi sederhana untuk menampilkan thumbnail dokumen
function addSimpleDocumentThumbnail(fileName, fileType) {
    // Tentukan icon dan warna berdasarkan tipe file
    let icon = 'ti-file';
    let bgColor = '#f3f4f6';
    let iconColor = '#6b7280';
    
    if (fileType === 'PDF') {
        icon = 'ti-file-type-pdf';
        bgColor = '#fef2f2';
        iconColor = '#ef4444';
    } else if (['DOC', 'DOCX'].includes(fileType)) {
        icon = 'ti-file-type-doc';
        bgColor = '#eff6ff';
        iconColor = '#3b82f6';
    } else {
        icon = 'ti-file-text';
    }
    
    return `
        <div class="mb-1 mt-1">
            <div class="gap-2 mx-0.5 mb-3 flex flex-wrap">
                <div class="relative">
                    <div class="group/thumbnail">
                        <div class="document-thumbnail rounded-lg text-left cursor-pointer transition-all border border-gray-200/25 flex flex-col justify-between gap-2.5 overflow-hidden px-2.5 py-2 bg-white hover:border-gray-300/50 hover:shadow-lg shadow-sm" style="width: 120px; height: 120px; min-width: 120px;">
                            <div class="flex items-center justify-center" style="background: ${bgColor}; height: 60px; border-radius: 6px; margin: -4px -4px 0 -4px;">
                                <i class="ti ${icon}" style="font-size: 24px; color: ${iconColor};"></i>
                            </div>
                            <div class="relative flex flex-col gap-1 min-h-0">
                                <h3 class="text-xs break-words text-gray-900 overflow-hidden display-webkit-box webkit-line-clamp-2 webkit-box-orient-vertical font-medium">${fileName}</h3>
                            </div>
                            <div class="relative flex flex-row items-center gap-1 justify-between">
                                <div class="flex flex-row gap-1 shrink min-w-0">
                                    <div class="min-w-0 h-[18px] flex flex-row items-center justify-center gap-0.5 px-1 border border-gray-200/25 shadow-sm rounded bg-white/70 backdrop-blur-sm font-medium">
                                        <p class="uppercase truncate text-gray-600 text-[11px] leading-[13px]">${fileType}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `;
}

// Hook untuk menambahkan thumbnail setelah message dikirim
function addThumbnailToLastUserMessage() {
    console.log('🔍 Checking for documents in thumbnails...');
    
    // Cari thumbnail dokumen di preview area
    const thumbnails = document.querySelectorAll('.document-thumbnail:not(.image-thumbnail), .task-thumbnail');
    if (thumbnails.length === 0) {
        console.log('📝 No document thumbnails found');
        return;
    }
    
    // Cari chat message user terakhir
    const chatMessages = document.getElementById('chat-messages');
    const userMessages = chatMessages.querySelectorAll('.chat-message.user');
    if (userMessages.length === 0) {
        console.log('📝 No user messages found');
        return;
    }
    
    const lastUserMessage = userMessages[userMessages.length - 1];
    
    // Cek apakah sudah ada thumbnail
    if (lastUserMessage.querySelector('.document-thumbnail')) {
        console.log('📝 Thumbnail already exists in message');
        return;
    }
    
    // Buat HTML thumbnail untuk semua dokumen/tugas
    let thumbnailsHtml = '';
    thumbnails.forEach(thumb => {
        let fileName = '';
        let fileType = '';
        
        if (thumb.classList.contains('task-thumbnail')) {
            // Ini adalah tugas
            fileName = thumb.dataset.assignmentId ? 'Tugas Assignment' : 'Tugas';
            fileType = 'TASK';
        } else {
            // Ini adalah dokumen
            fileName = thumb.dataset.fileName || 'Document';
            if (thumb.dataset.fileType === 'chunked-document') {
                fileType = 'PDF';
            } else {
                fileType = fileName.split('.').pop().toUpperCase() || 'FILE';
            }
        }
        
        thumbnailsHtml += addSimpleDocumentThumbnail(fileName, fileType);
    });
    
    // Tambahkan thumbnail ke message
    const messageContent = lastUserMessage.querySelector('.flex.flex-row-reverse');
    if (messageContent && thumbnailsHtml) {
        messageContent.insertAdjacentHTML('beforebegin', thumbnailsHtml);
        console.log('✅ Added thumbnail to user message');
    }
}

// Override sendMessageWithDocuments untuk menambahkan thumbnail
document.addEventListener('DOMContentLoaded', function() {
    // Hook ke fungsi sendMessageWithDocuments jika ada
    if (window.pingoChat && window.pingoChat.sendMessageWithDocuments) {
        const originalSendWithDocs = window.pingoChat.sendMessageWithDocuments.bind(window.pingoChat);
        
        window.pingoChat.sendMessageWithDocuments = async function() {
            // Panggil fungsi asli
            await originalSendWithDocs();
            
            // Tambahkan thumbnail setelah delay kecil
            setTimeout(() => {
                addThumbnailToLastUserMessage();
            }, 100);
        };
    }
});

// Export functions
window.addSimpleDocumentThumbnail = addSimpleDocumentThumbnail;
window.addThumbnailToLastUserMessage = addThumbnailToLastUserMessage;

console.log('✅ Simple document thumbnail helper loaded');