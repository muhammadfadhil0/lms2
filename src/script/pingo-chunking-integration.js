/**
 * Enhanced PingoChat Integration with Document Chunking
 * File: src/script/pingo-chunking-integration.js
 */

// Enhanced PingoChat integration
document.addEventListener('DOMContentLoaded', function() {
    // Wait for both systems to be ready
    setTimeout(initializeChunkingIntegration, 1500);
});

function initializeChunkingIntegration() {
    if (!window.pingoChat || !window.documentChunking) {
        console.log('⏳ Waiting for PingoChat and DocumentChunking...');
        setTimeout(initializeChunkingIntegration, 500);
        return;
    }
    
    console.log('🔗 Integrating Document Chunking with PingoChat...');
    
    // Store original methods
    const originalSendMessage = window.pingoChat.sendMessage.bind(window.pingoChat);
    const originalHandleDocumentUpload = window.pingoChat.handleDocumentUpload?.bind(window.pingoChat);
    
    // Override document upload to use chunking
    if (originalHandleDocumentUpload) {
        window.pingoChat.handleDocumentUpload = async function(file) {
            console.log('📄 Using chunked document upload for:', file.name);
            return await window.documentChunking.handleDocumentUpload(file);
        };
    }
    
    // Override sendMessage to integrate chunking
    window.pingoChat.sendMessage = async function() {
        const isEmptyState = this.chatEmptyState.style.display !== 'none';
        const currentInput = isEmptyState ? this.chatInput : this.chatInputActive;
        const originalMessage = currentInput.value.trim();
        
        if (!originalMessage) {
            return;
        }
        
        console.log('🚀 Enhanced sendMessage called with chunking integration');
        
        // Get chunked document IDs
        const documentIds = window.documentChunking.getCurrentDocumentIds();
        
        // Priority 1: Chunked Documents
        if (documentIds.length > 0) {
            console.log('📄 Processing with chunked documents:', documentIds);
            
            // Clear input and add user message
            currentInput.value = '';
            this.autoResize(currentInput);
            this.updateSendButtonState();
            
            // Show chat interface if needed
            if (isEmptyState) {
                this.showChatInterface();
            }
            
            // Get document info for attachment display
            let documentAttachment = null;
            if (documentIds.length > 0) {
                const docData = window.documentChunking.uploadedDocuments.get(documentIds[0]);
                if (docData) {
                    documentAttachment = {
                        type: 'document',
                        name: docData.filename,
                        content: `📊 Dokumen berhasil diproses dengan ${docData.totalChunks} chunks (${docData.totalWords} kata)`, 
                        chunks: docData.totalChunks,
                        words: docData.totalWords
                    };
                }
            }
            
            // Add user message with document attachment info
            this.addMessage('user', originalMessage, documentAttachment);
            this.showTypingIndicator();
            
            try {
                // Create optimized content
                const systemPrompt = `INSTRUKSI PENTING:
- JANGAN GUNAKAN TABEL dalam jawaban
- Jawab SINGKAT dan PADAT, maksimal 3-4 paragraf  
- Fokus pada ANALISIS dan JAWABAN LANGSUNG
- Gunakan format bullet points (•) untuk daftar jika perlu`;

                const optimizedContent = await window.documentChunking.createOptimizedAIContent(
                    documentIds, 
                    originalMessage, 
                    systemPrompt
                );
                
                console.log('📊 Using optimized content with chunks:', optimizedContent.metadata);
                
                // Send to AI
                const response = await this.sendToAI(optimizedContent.content, null, originalMessage);
                
                // Format response with metadata
                this.hideTypingIndicator();
                let aiMessage = response.message || response;
                
                if (optimizedContent.metadata.chunks_used > 0) {
                    aiMessage += `\n\n---\n*📊 Dianalisis dari ${optimizedContent.metadata.chunks_used} bagian teks (${optimizedContent.metadata.documents_processed} dokumen, ~${optimizedContent.metadata.total_words} kata)*`;
                }
                
                this.addMessage('ai', aiMessage);
                return;
                
            } catch (error) {
                console.error('❌ Error processing chunked documents:', error);
                this.hideTypingIndicator();
                
                const errorInfo = this.handleAPIError ? this.handleAPIError(error) : 
                    { userMessage: 'Terjadi kesalahan saat memproses dokumen.' };
                this.addMessage('ai', errorInfo.userMessage);
                return;
            }
        }
        
        // Priority 2: Assignment Data (existing functionality)
        if (window.currentAssignmentData?.analysisPrompt) {
            console.log('📋 Processing with assignment data');
            
            currentInput.value = '';
            this.autoResize(currentInput);
            this.updateSendButtonState();
            
            if (isEmptyState) {
                this.showChatInterface();
            }
            
            this.addMessage('user', originalMessage);
            this.showTypingIndicator();
            
            try {
                const response = await this.sendToAI(window.currentAssignmentData.analysisPrompt);
                
                this.hideTypingIndicator();
                this.addMessage('ai', response.message || response);
                
                // Clear assignment data
                window.currentAssignmentData = null;
                document.querySelectorAll('.task-thumbnail').forEach(thumb => thumb.remove());
                
                return;
                
            } catch (error) {
                console.error('❌ Error processing assignment:', error);
                this.hideTypingIndicator();
                
                const errorInfo = this.handleAPIError ? this.handleAPIError(error) : 
                    { userMessage: 'Terjadi kesalahan saat memproses tugas.' };
                this.addMessage('ai', errorInfo.userMessage);
                return;
            }
        }
        
        // Priority 3: Regular message (no special context)
        console.log('💬 Processing regular message');
        return originalSendMessage();
    };
    
    console.log('✅ Document Chunking integration completed');
}