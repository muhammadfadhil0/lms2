/**
 * Document Chunking Frontend Integration
 * File: src/script/document-chunking.js
 */

class DocumentChunking {
    constructor() {
        this.uploadedDocuments = new Map(); // Store document metadata
        this.processingStatus = new Map();  // Track processing status
        this.chunkingAPI = '../api/document-chunking-api.php';
        
        this.initializeEventListeners();
    }
    
    initializeEventListeners() {
        // Override existing document upload handlers
        this.overrideDocumentHandlers();
        
        // Add document management UI
        this.createDocumentManagerUI();
    }
    
    /**
     * Override existing document upload to use chunking
     */
    overrideDocumentHandlers() {
        // Store original PingoChat methods if they exist
        if (window.pingoChat) {
            window.pingoChat.originalHandleDocumentUpload = window.pingoChat.handleDocumentUpload;
            window.pingoChat.handleDocumentUpload = this.handleDocumentUpload.bind(this);
        }
    }
    
    /**
     * Handle document upload with chunking
     */
    async handleDocumentUpload(file) {
        console.log('📄 DocumentChunking: Handling document upload with chunking:', file.name);
        
        try {
            // Show upload progress
            this.showUploadProgress(file.name);
            
            // Upload and process document
            const result = await this.uploadAndProcessDocument(file);
            
            if (result.success) {
                // Store document info
                this.uploadedDocuments.set(result.data.document_id, {
                    id: result.data.document_id,
                    filename: file.name,
                    totalChunks: result.data.total_chunks,
                    totalWords: result.data.total_words,
                    uploadTime: new Date()
                });
                
                // Add to document thumbnails with chunking info
                this.addChunkedDocumentThumbnail(file, result.data);
                
                // Hide progress and show success
                this.hideUploadProgress();
                this.showProcessingSuccess(file.name, result.data);
                
            } else {
                throw new Error(result.message || 'Upload failed');
            }
            
        } catch (error) {
            console.error('❌ Document chunking upload failed:', error);
            this.hideUploadProgress();
            this.showProcessingError(file.name, error.message);
        }
    }
    
    /**
     * Upload and process document
     */
    async uploadAndProcessDocument(file) {
        const formData = new FormData();
        formData.append('action', 'upload_document');
        formData.append('document', file);
        
        const response = await fetch(this.chunkingAPI, {
            method: 'POST',
            body: formData
        });
        
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }
        
        return await response.json();
    }
    
    /**
     * Find relevant chunks for AI query
     */
    async findRelevantChunks(documentIds, query) {
        try {
            const results = [];
            
            for (const documentId of documentIds) {
                const formData = new FormData();
                formData.append('action', 'search_chunks');
                formData.append('document_id', documentId);
                formData.append('query', query);
                formData.append('limit', '3');
                
                const response = await fetch(this.chunkingAPI, {
                    method: 'POST',
                    body: formData
                });
                
                if (response.ok) {
                    const result = await response.json();
                    if (result.success) {
                        results.push(result.data);
                    }
                }
            }
            
            return results;
            
        } catch (error) {
            console.error('❌ Error finding relevant chunks:', error);
            return [];
        }
    }
    
    /**
     * Create optimized content for AI
     */
    async createOptimizedAIContent(documentIds, userMessage, systemPrompt = '') {
        if (!documentIds.length) {
            return {
                content: userMessage,
                metadata: { chunks_used: 0, documents_processed: 0 }
            };
        }
        
        try {
            console.log('🔍 Creating optimized content for documents:', documentIds);
            
            // Find relevant chunks across all documents
            const chunkResults = await this.findRelevantChunks(documentIds, userMessage);
            
            if (!chunkResults.length) {
                return {
                    content: `${systemPrompt}\n\nDokumen telah diupload tapi tidak ditemukan konten yang relevan.\n\nPertanyaan: ${userMessage}`,
                    metadata: { chunks_used: 0, documents_processed: documentIds.length }
                };
            }
            
            // Combine all chunks
            let allChunks = [];
            chunkResults.forEach(result => {
                allChunks = allChunks.concat(result.chunks);
            });
            
            // Sort by relevance if available
            allChunks.sort((a, b) => {
                const scoreA = (a.content_score || 0) + (a.keyword_score || 0);
                const scoreB = (b.content_score || 0) + (b.keyword_score || 0);
                return scoreB - scoreA;
            });
            
            // Take top 3 chunks and create context
            const topChunks = allChunks.slice(0, 3);
            const contextText = this.buildContextFromChunks(topChunks, documentIds);
            
            const optimizedContent = `${systemPrompt}

${contextText}

INSTRUKSI PENTING:
- Jawab berdasarkan konteks dokumen di atas
- Kutip bagian relevan jika diperlukan  
- JANGAN gunakan tabel dalam jawaban
- Jawab singkat dan fokus pada informasi penting
- Jika info tidak ada dalam konteks, katakan dengan jelas

PERTANYAAN: ${userMessage}`;

            return {
                content: optimizedContent,
                metadata: {
                    chunks_used: topChunks.length,
                    documents_processed: documentIds.length,
                    total_words: topChunks.reduce((sum, chunk) => sum + (chunk.word_count || 0), 0)
                }
            };
            
        } catch (error) {
            console.error('❌ Error creating optimized content:', error);
            return {
                content: userMessage,
                metadata: { chunks_used: 0, documents_processed: 0, error: error.message }
            };
        }
    }
    
    /**
     * Build context text from chunks
     */
    buildContextFromChunks(chunks, documentIds) {
        if (!chunks.length) return '';
        
        let contextText = 'KONTEKS DOKUMEN:\n\n';
        
        chunks.forEach((chunk, index) => {
            const docInfo = this.uploadedDocuments.get(parseInt(chunk.document_id)) || 
                           { filename: `Dokumen ${chunk.document_id}` };
            
            contextText += `--- BAGIAN ${index + 1} dari "${docInfo.filename}" ---\n`;
            contextText += `${chunk.content}\n\n`;
        });
        
        return contextText;
    }
    
    /**
     * Add chunked document thumbnail
     */
    addChunkedDocumentThumbnail(file, processData) {
        // Find thumbnail containers
        const emptyStatePreview = document.getElementById('document-preview-area-empty');
        const activeStatePreview = document.getElementById('document-preview-area');
        const emptyState = document.getElementById('chat-empty-state');
        
        const isEmptyStateVisible = emptyState && emptyState.style.display !== 'none';
        
        let targetContainer;
        if (isEmptyStateVisible && emptyStatePreview) {
            targetContainer = emptyStatePreview.querySelector('.document-thumbnails');
            emptyStatePreview.classList.remove('hidden');
        } else if (activeStatePreview) {
            targetContainer = activeStatePreview.querySelector('.document-thumbnails');
            activeStatePreview.classList.remove('hidden');
        }
        
        if (!targetContainer) {
            console.warn('❌ Could not find thumbnail container');
            return;
        }
        
        // Create chunked document thumbnail
        const thumbnail = this.createChunkedThumbnail(file, processData);
        targetContainer.appendChild(thumbnail);
    }
    
    /**
     * Create chunked document thumbnail
     */
    createChunkedThumbnail(file, processData) {
        const thumbnail = document.createElement('div');
        thumbnail.className = 'document-thumbnail chunked-document';
        thumbnail.dataset.documentId = processData.document_id;
        thumbnail.dataset.fileName = file.name;
        thumbnail.dataset.fileType = 'chunked-document';
        thumbnail.dataset.totalChunks = processData.total_chunks;
        thumbnail.dataset.totalWords = processData.total_words;
        
        const extension = file.name.split('.').pop().toUpperCase();
        
        // Determine colors based on file type
        let iconColor, bgColor;
        if (['PDF'].includes(extension)) {
            iconColor = '#ef4444';
            bgColor = '#fef2f2';
        } else if (['DOC', 'DOCX'].includes(extension)) {
            iconColor = '#3b82f6';
            bgColor = '#eff6ff';
        } else {
            iconColor = '#6b7280';
            bgColor = '#f9fafb';
        }
        
        thumbnail.innerHTML = `
            <div class="document-thumbnail-preview" style="background: ${bgColor}; display: flex; align-items: center; justify-content: center; height: 120px; border-radius: 8px 8px 0 0;">
                <i class="ti ti-file-text" style="font-size: 32px; color: ${iconColor};"></i>
            </div>
            <div class="document-thumbnail-header">
                <h3 class="document-thumbnail-title" title="${file.name}">${file.name}</h3>
            </div>
            <div class="document-thumbnail-footer">
                <div class="document-thumbnail-type">${extension}</div>
            </div>
            <button class="document-remove-btn" onclick="removeChunkedDocument('${processData.document_id}')">
                <i class="ti ti-x"></i>
            </button>
        `;
        
        return thumbnail;
    }
    
    /**
     * Show upload progress
     */
    showUploadProgress(filename) {
        const notification = document.createElement('div');
        notification.id = 'upload-progress-notification';
        notification.className = 'upload-progress-notification';
        notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            padding: 16px 20px;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1);
            z-index: 10000;
            max-width: 350px;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        `;
        
        notification.innerHTML = `
            <div style="display: flex; align-items: center; gap: 12px;">
                <div class="processing-spinner" style="
                    width: 24px;
                    height: 24px;
                    border: 2px solid #e5e7eb;
                    border-top: 2px solid #3b82f6;
                    border-radius: 50%;
                    animation: spin 1s linear infinite;
                "></div>
                <div>
                    <div style="font-weight: 600; margin-bottom: 4px;">Processing Document</div>
                    <div style="font-size: 14px; color: #6b7280;">${filename}</div>
                    <div style="font-size: 12px; color: #9ca3af; margin-top: 2px;">Chunking untuk optimasi AI...</div>
                </div>
            </div>
        `;
        
        // Add spinner animation if not exists
        if (!document.querySelector('#spinner-style')) {
            const style = document.createElement('style');
            style.id = 'spinner-style';
            style.textContent = `
                @keyframes spin {
                    0% { transform: rotate(0deg); }
                    100% { transform: rotate(360deg); }
                }
            `;
            document.head.appendChild(style);
        }
        
        document.body.appendChild(notification);
    }
    
    /**
     * Hide upload progress
     */
    hideUploadProgress() {
        const notification = document.getElementById('upload-progress-notification');
        if (notification) {
            notification.remove();
        }
    }
    
    /**
     * Show processing success
     */
    showProcessingSuccess(filename, data) {
        this.showNotification('success', `📄 ${filename}`, 
            `Berhasil diproses menjadi ${data.total_chunks} chunks (${data.total_words} kata)`);
    }
    
    /**
     * Show processing error
     */
    showProcessingError(filename, error) {
        this.showNotification('error', `❌ Error: ${filename}`, 
            `Gagal memproses dokumen: ${error}`);
    }
    
    /**
     * Generic notification system
     */
    showNotification(type, title, message) {
        const notification = document.createElement('div');
        const bgColor = type === 'success' ? '#10b981' : '#ef4444';
        
        notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            background: ${bgColor};
            color: white;
            border-radius: 12px;
            padding: 16px 20px;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.2);
            z-index: 10000;
            max-width: 350px;
            opacity: 0;
            transform: translateX(100%);
            transition: all 0.3s ease;
        `;
        
        notification.innerHTML = `
            <div style="display: flex; justify-content: space-between; align-items: start;">
                <div>
                    <div style="font-weight: 600; margin-bottom: 4px;">${title}</div>
                    <div style="font-size: 14px; opacity: 0.9;">${message}</div>
                </div>
                <button onclick="this.parentElement.parentElement.remove()" style="
                    background: none; border: none; color: white; font-size: 18px; 
                    cursor: pointer; opacity: 0.7; margin-left: 12px;
                ">×</button>
            </div>
        `;
        
        document.body.appendChild(notification);
        
        // Animate in
        requestAnimationFrame(() => {
            notification.style.opacity = '1';
            notification.style.transform = 'translateX(0)';
        });
        
        // Auto remove
        setTimeout(() => {
            notification.style.opacity = '0';
            notification.style.transform = 'translateX(100%)';
            setTimeout(() => notification.remove(), 300);
        }, type === 'error' ? 5000 : 3000);
    }
    
    /**
     * Create document management UI
     */
    createDocumentManagerUI() {
        // This can be expanded later for document management features
        console.log('📋 DocumentChunking: UI initialized');
    }
    
    /**
     * Get document IDs from current thumbnails
     */
    getCurrentDocumentIds() {
        const documentIds = [];
        const thumbnails = document.querySelectorAll('.document-thumbnail.chunked-document');
        
        thumbnails.forEach(thumb => {
            const docId = thumb.dataset.documentId;
            if (docId) {
                documentIds.push(parseInt(docId));
            }
        });
        
        return documentIds;
    }
}

/**
 * Remove chunked document
 */
window.removeChunkedDocument = function(documentId) {
    // Remove from thumbnails
    const thumbnails = document.querySelectorAll(`[data-document-id="${documentId}"]`);
    thumbnails.forEach(thumb => thumb.remove());
    
    // Remove from memory
    if (window.documentChunking) {
        window.documentChunking.uploadedDocuments.delete(parseInt(documentId));
    }
    
    // Check if preview areas should be hidden
    const emptyStatePreview = document.getElementById('document-preview-area-empty');
    const activeStatePreview = document.getElementById('document-preview-area');
    
    [emptyStatePreview, activeStatePreview].forEach(preview => {
        if (preview) {
            const container = preview.querySelector('.document-thumbnails');
            if (container && container.children.length === 0) {
                preview.classList.add('hidden');
            }
        }
    });
};

// Initialize document chunking system
document.addEventListener('DOMContentLoaded', function() {
    console.log('🔧 Initializing Document Chunking System...');
    window.documentChunking = new DocumentChunking();
    console.log('✅ Document Chunking System ready');
});