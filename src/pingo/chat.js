/**
 * Pingo Chat JavaScript
 * Handles chat functionality for Pingo AI assistant
 */

class PingoChat {
    constructor() {
        console.log('🚀 PingoChat: Initializing chat system...');
        this.chatMessages = document.getElementById('chat-messages');
        this.chatEmptyState = document.getElementById('chat-empty-state');
        this.chatInputContainer = document.getElementById('chat-input-container');
        this.chatInput = document.getElementById('chat-input');
        this.chatInputActive = document.getElementById('chat-input-active');
        this.sendButton = document.getElementById('send-button');
        this.sendButtonActive = document.getElementById('send-button-active');
        this.clearButton = document.getElementById('clear-button');
        
        this.isLoading = false;
        this.chatHistory = [];
        
        console.log('📦 PingoChat: DOM elements found:', {
            chatMessages: !!this.chatMessages,
            chatEmptyState: !!this.chatEmptyState,
            chatInputContainer: !!this.chatInputContainer,
            chatInput: !!this.chatInput,
            chatInputActive: !!this.chatInputActive
        });
        
        // Debug DOM elements
        if (!this.chatInputContainer) {
            console.error('❌ PingoChat: chat-input-container element not found!');
        }
        if (!this.chatEmptyState) {
            console.error('❌ PingoChat: chat-empty-state element not found!');
        }
        
        // Initialize attachment storage
        this.initializeAttachmentStorage();
        
        // Clean up any corrupted chat history
        this.cleanupChatHistory();
        
        // Initialize with hidden input container
        this.hideInputContainer();
        
        this.initEventListeners();
        this.loadChatHistory();
    }
    
    initializeAttachmentStorage() {
        console.log('🗃️ PingoChat: Attachment storage now handled by filesystem and database');
    }
    
    // Method to clean up corrupted chat history
    cleanupChatHistory() {
        console.log('🧽 PingoChat: Cleaning up chat history...');
        try {
            const savedHistory = localStorage.getItem('pingo_chat_history');
            if (!savedHistory) {
                console.log('📝 PingoChat: No saved history found');
                return;
            }
            
            let chatHistory = JSON.parse(savedHistory);
            console.log('📚 PingoChat: Found chat history with', chatHistory.length, 'messages');
            let hasChanges = false;
            let corruptedCount = 0;
            
            // Filter out messages with corrupted attachments
            chatHistory = chatHistory.filter(message => {
                if (message.attachment && message.attachment.documents) {
                    const hasValidContent = message.attachment.documents.some(doc => 
                        doc.content && !doc.content.includes('Gagal memuat dokumen')
                    );
                    
                    if (!hasValidContent) {
                        console.warn('🗑️ PingoChat: Removing corrupted attachment message:', message.attachment.documents.map(d => d.name));
                        hasChanges = true;
                        corruptedCount++;
                        return false;
                    }
                }
                return true;
            });
            
            if (hasChanges) {
                localStorage.setItem('pingo_chat_history', JSON.stringify(chatHistory));
                console.log('✅ PingoChat: Cleaned up corrupted chat history -', corruptedCount, 'messages removed');
            } else {
                console.log('✨ PingoChat: Chat history is clean, no cleanup needed');
            }
        } catch (error) {
            console.error('❌ PingoChat: Error cleaning up chat history:', error);
        }
    }
    
    initEventListeners() {
        // Send button events
        this.sendButton?.addEventListener('click', () => this.sendMessage());
        this.sendButtonActive?.addEventListener('click', () => this.sendMessage());
        
        // Enter key events
        this.chatInput?.addEventListener('keypress', (e) => {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                this.sendMessage();
            }
        });
        
        this.chatInputActive?.addEventListener('keypress', (e) => {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                this.sendMessage();
            }
        });
        
        // Auto-resize textarea and update send button state
        this.chatInput?.addEventListener('input', () => {
            this.autoResize(this.chatInput);
            this.updateSendButtonState();
            
            // Don't auto-switch to chat mode while typing
            // Chat mode will be activated when send button is clicked
        });
        this.chatInputActive?.addEventListener('input', () => {
            this.autoResize(this.chatInputActive);
            this.updateSendButtonState();
        });
        
        // Clear chat button
        this.clearButton?.addEventListener('click', () => this.clearChat());
        
        // Initialize send button state
        this.updateSendButtonState();
    }
    
    autoResize(textarea) {
        textarea.style.height = 'auto';
        textarea.style.height = Math.min(textarea.scrollHeight, 120) + 'px';
    }
    
    updateSendButtonState() {
        // Determine which input is currently active
        const isEmptyStateVisible = this.chatEmptyState?.style.display !== 'none';
        const activeInput = isEmptyStateVisible ? this.chatInput : this.chatInputActive;
        const hasContent = activeInput?.value.trim().length > 0;
        
        // Update send button state for both buttons
        if (this.sendButton) {
            this.sendButton.disabled = !hasContent || this.isLoading;
        }
        if (this.sendButtonActive) {
            this.sendButtonActive.disabled = !hasContent || this.isLoading;
        }
    }
    
    async loadChatHistory() {
        console.log('📖 PingoChat: Loading chat history...');
        try {
            console.log('🌐 PingoChat: Attempting to load from server...');
            const response = await fetch('../pingo/chat-api.php', {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json'
                }
            });
            
            const data = await response.json();
            console.log('📡 PingoChat: Server response:', data);
            
            if (data.success && data.messages.length > 0) {
                console.log('✅ PingoChat: Loaded', data.messages.length, 'messages from server');
                this.chatHistory = data.messages;
                
                // Server data is already ordered chronologically, but let's verify and sort by timestamp just in case
                this.chatHistory.sort((a, b) => {
                    const timeA = new Date(a.timestamp || '1970-01-01').getTime();
                    const timeB = new Date(b.timestamp || '1970-01-01').getTime();
                    
                    // Primary sort: by timestamp
                    if (timeA !== timeB) {
                        return timeA - timeB; // Ascending order (oldest first)
                    }
                    
                    // Secondary sort: if timestamps are equal, user comes before assistant/ai
                    const roleOrder = { 'user': 0, 'assistant': 1, 'ai': 1 };
                    return (roleOrder[a.role] || 2) - (roleOrder[b.role] || 2);
                });
                console.log('🔄 PingoChat: Server messages verified and sorted by timestamp');
                
                // Debug: Log first few messages to check order
                console.log('🔍 PingoChat: Server message order check:');
                this.chatHistory.slice(0, Math.min(4, this.chatHistory.length)).forEach((msg, idx) => {
                    const timestamp = new Date(msg.timestamp).getTime();
                    console.log(`  ${idx + 1}. [${msg.timestamp}] (${timestamp}) ${msg.role}: ${(msg.content || msg.message || '').substring(0, 50)}...`);
                });
                
                // Additional debug: Check for timestamp issues
                if (this.chatHistory.length >= 2) {
                    const first = this.chatHistory[0];
                    const second = this.chatHistory[1];
                    console.log('🔍 PingoChat: First two messages comparison:');
                    console.log(`  Message 1 - Role: ${first.role}, Timestamp: ${first.timestamp}, Time: ${new Date(first.timestamp).getTime()}`);
                    console.log(`  Message 2 - Role: ${second.role}, Timestamp: ${second.timestamp}, Time: ${new Date(second.timestamp).getTime()}`);
                    
                    if (first.role === 'assistant' && second.role === 'user') {
                        console.warn('⚠️ PingoChat: ORDERING ISSUE DETECTED - AI message appears before user message!');
                    }
                }
                
                this.displayChatHistory();
                this.showChatInterface();
            } else {
                console.log('📱 PingoChat: No server history, trying localStorage...');
                // If no server history, try loading from localStorage
                this.loadLocalChatHistory();
            }
        } catch (error) {
            console.error('❌ PingoChat: Error loading chat history from server:', error);
            // Fallback to localStorage
            console.log('🔄 PingoChat: Falling back to localStorage...');
            this.loadLocalChatHistory();
        }
    }
    
    loadLocalChatHistory() {
        console.log('💾 PingoChat: Loading chat history from localStorage...');
        try {
            const savedHistory = localStorage.getItem('pingo_chat_history');
            if (savedHistory) {
                this.chatHistory = JSON.parse(savedHistory);
                console.log('📚 PingoChat: Loaded', this.chatHistory.length, 'messages from localStorage');
                
                // Sort messages by timestamp to ensure correct chronological order
                this.chatHistory.sort((a, b) => {
                    const timeA = new Date(a.timestamp || '1970-01-01').getTime();
                    const timeB = new Date(b.timestamp || '1970-01-01').getTime();
                    
                    // Primary sort: by timestamp
                    if (timeA !== timeB) {
                        return timeA - timeB; // Ascending order (oldest first)
                    }
                    
                    // Secondary sort: if timestamps are equal, user comes before assistant/ai
                    const roleOrder = { 'user': 0, 'assistant': 1, 'ai': 1 };
                    return (roleOrder[a.role] || 2) - (roleOrder[b.role] || 2);
                });
                console.log('🔄 PingoChat: Messages sorted by timestamp');
                
                // Debug: Log first few messages to check order
                console.log('🔍 PingoChat: Message order check:');
                this.chatHistory.slice(0, Math.min(4, this.chatHistory.length)).forEach((msg, idx) => {
                    console.log(`  ${idx + 1}. [${msg.timestamp}] ${msg.role}: ${(msg.content || msg.message || '').substring(0, 50)}...`);
                });
                
                if (this.chatHistory.length > 0) {
                    console.log('🎨 PingoChat: Displaying chat history...');
                    this.displayChatHistory();
                    this.showChatInterface();
                } else {
                    console.log('📝 PingoChat: No messages to display');
                }
            } else {
                console.log('🆕 PingoChat: No local chat history found');
            }
        } catch (error) {
            console.error('❌ PingoChat: Error loading local chat history:', error);
        }
    }
    
    displayChatHistory() {
        console.log('🎭 PingoChat: Displaying chat history with', this.chatHistory.length, 'messages');
        this.chatMessages.innerHTML = '';
        
        // Debug: Final order check before displaying
        console.log('🔍 PingoChat: Final message display order:');
        this.chatHistory.forEach((message, index) => {
            console.log(`📝 PingoChat: Message ${index + 1}: [${message.timestamp}] ${message.role}: ${(message.content || message.message || '').substring(0, 30)}...`);
        });
        
        this.chatHistory.forEach((message, index) => {
            console.log(`📝 PingoChat: Processing message ${index + 1} for display:`, {
                role: message.role,
                hasAttachment: !!message.attachment,
                attachmentType: message.attachment?.type,
                content: message.content || message.message
            });
            
            // Check if message has attachment property
            if (message.attachment) {
                console.log('📎 PingoChat: Message has attachment, using addMessage()');
                
                // For restored messages, images are now loaded from filesystem
                // No need to process preview_base64 as images are served via get-image.php
                
                this.addMessage(message.role, message.content || message.message, message.attachment, false); // No typing animation for history
            } else if (message.modelInfo && message.role === 'ai') {
                console.log('🤖 PingoChat: AI message with model info, using addMessage()');
                this.addMessage(message.role, message.content || message.message, { modelInfo: message.modelInfo }, false);
            } else {
                // For compatibility with old format
                const content = message.message || message.content;
                console.log('💬 PingoChat: Regular message, using addMessageToChat()');
                this.addMessageToChat(content, message.role, false, false); // No typing animation for history
            }
        });
        
        console.log('✅ PingoChat: Chat history displayed successfully');
        this.scrollToBottom();
    }
    
    async sendMessage() {
        // Check if there are documents attached in any preview area (only visible ones)
        const previewAreas = [
            document.getElementById('document-preview-area-empty'),
            document.getElementById('document-preview-area')
        ];
        
        let hasRealAttachments = false;
        let totalThumbnails = 0;
        
        previewAreas.forEach(area => {
            if (area && !area.classList.contains('hidden')) {
                const thumbnails = area.querySelectorAll('.document-thumbnail, .task-thumbnail');
                if (thumbnails.length > 0) {
                    hasRealAttachments = true;
                    totalThumbnails += thumbnails.length;
                }
            }
        });
        
        console.log('🔍 PingoChat: Found thumbnails before sending:', totalThumbnails, 'in preview areas,', hasRealAttachments ? 'has real attachments' : 'no real attachments');
        
        if (hasRealAttachments) {
            console.log('📎 PingoChat: Sending message with documents/images');
            await this.sendMessageWithDocuments();
            return;
        }
        
        // Normal message sending
        const activeInput = this.chatInputContainer.style.display === 'none' ? this.chatInput : this.chatInputActive;
        const message = activeInput.value.trim();
        
        if (!message || this.isLoading) return;
        
        // Clear input
        activeInput.value = '';
        this.autoResize(activeInput);
        this.updateSendButtonState();
        
        // Clear any remaining document previews to prevent carry-over
        this.clearDocumentPreviews();

        // Show chat interface if it's the first message
        if (this.chatEmptyState.style.display !== 'none') {
            this.showChatInterface();
        }        // Add user message to chat
        this.addMessageToChat(message, 'user');
        
        // Save user message to history with proper timestamp
        const userTimestamp = new Date().toISOString();
        this.chatHistory.push({ 
            role: 'user', 
            content: message,
            timestamp: userTimestamp,
            message: message // For compatibility
        });

        // Show loading
        this.showLoading();

        try {
            const response = await fetch('../pingo/chat-api.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ message: message })
            });

            const data = await response.json();

            this.hideLoading();

            if (data.success) {
                this.addMessageToChat(data.message, 'assistant');

                // Save AI response to history with proper timestamp (ensure it's later than user timestamp)
                const aiTimestamp = new Date(Date.now() + 500).toISOString(); // 500ms later than user
                this.chatHistory.push({ 
                    role: 'assistant', 
                    content: data.message,
                    timestamp: aiTimestamp,
                    message: data.message // For compatibility
                });
                this.saveChatHistory();
            } else {
                this.addMessageToChat('Maaf, terjadi kesalahan: ' + (data.error || 'Unknown error'), 'error');
            }
        } catch (error) {
            this.hideLoading();
            console.error('Error sending message:', error);
            
            // Handle different types of errors with user-friendly messages
            const errorInfo = this.handleAPIError(error);
            this.addMessageToChat(errorInfo.userMessage, 'error');
        }
    }
    
    addMessageToChat(message, role, scroll = true, showTypingAnimation = true, attachment = null) {
        const messageDiv = document.createElement('div');
        messageDiv.className = `chat-message ${role}`;
        
        const timestamp = new Date().toLocaleTimeString('id-ID', {
            hour: '2-digit',
            minute: '2-digit'
        });
        
        if (role === 'user') {
            // Get user initials for avatar
            const userName = this.getUserName();
            const userInitials = this.getUserInitials(userName);
            
            messageDiv.innerHTML = `
                <div class="flex flex-row-reverse gap-2 items-center">
                    <div class="flex shrink-0 items-center justify-center rounded-full font-bold select-none h-7 w-7 text-xs bg-gray-700 text-white">
                        ${userInitials}
                    </div>
                    <div class="group relative inline-flex gap-2 bg-gray-200 rounded-xl pl-6 py-2.5 break-words text-gray-900 transition-all max-w-[75ch] flex-col pr-2.5">
                        <div class="grid grid-cols-1 gap-2 py-0.5">
                            <p class="whitespace-pre-wrap break-words">${this.escapeHtml(message)}</p>
                        </div>
                    </div>
                </div>
                    <div class="absolute bottom-0 right-2 pointer-events-none">
                        <div class="rounded-lg transition min-w-max pointer-events-auto translate-y-4 translate-x-1 group-hover:translate-x-0.5 p-0.5 opacity-0 group-hover:opacity-100">
                            <div class="text-gray-600 flex items-stretch justify-between">
                                <span class="text-xs px-2 py-1">${timestamp}</span>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        } else if (role === 'assistant' || role === 'ai') {
            // Handle model info display
            let modelDisplayHtml = '';
            if (attachment && attachment.modelInfo) {
                const modelName = attachment.modelInfo.model || 'Unknown Model';
                const isVision = attachment.modelInfo.is_vision;
                const modelIcon = isVision ? '🖼️' : '🤖';
                const modelShortName = modelName.includes('llama-4-maverick') ? 'Llama 4 Maverick' : 
                                     modelName.includes('llama') ? 'Llama' :
                                     modelName.includes('gpt') ? 'GPT' :
                                     modelName.split('/').pop() || modelName;
                
                modelDisplayHtml = `
                    <div class="model-info" title="${modelName}">
                        <span class="model-icon">${modelIcon}</span>
                        <span class="model-name">${modelShortName}</span>
                    </div>
                `;
            }
            
            messageDiv.innerHTML = `
                <div class="message-avatar ai-avatar">
                    <i class="ti ti-sparkles"></i>
                </div>
                <div class="message-content ai-message">
                    ${modelDisplayHtml}
                    <div class="message-text" id="ai-message-text-${Date.now()}"></div>
                    <div class="message-time">${timestamp}</div>
                </div>
            `;
            
            this.chatMessages.appendChild(messageDiv);
            
            const messageTextElement = messageDiv.querySelector('.message-text');
            
            if (showTypingAnimation) {
                console.log('⚡ PingoChat: Real-time AI response - showing typing animation');
                // Don't scroll immediately, let typing animation complete first
                // Add fast typing effect for real-time AI/assistant response
                this.typeWriterEffect(messageTextElement, this.formatMessage(message));
                return; // Exit early to prevent duplicate append and scrollToBottom
            } else {
                console.log('📚 PingoChat: Historical AI message - no typing animation');
                // For historical messages, display immediately without animation
                messageTextElement.innerHTML = this.formatMessage(message);
            }
        } else if (role === 'error') {
            messageDiv.innerHTML = `
                <div class="message-avatar error-avatar">
                    <i class="ti ti-alert-circle"></i>
                </div>
                <div class="message-content error-message">
                    <div class="message-text">${this.escapeHtml(message)}</div>
                    <div class="message-time">${timestamp}</div>
                </div>
            `;
        }
        
        this.chatMessages.appendChild(messageDiv);
        
        if (scroll) {
            this.scrollToBottom();
        }
    }
    
    // New method to handle messages with attachments
    addMessage(role, message, attachment = null, showTypingAnimation = true) {
        console.log('➕ PingoChat: Adding message with attachment:', {
            role,
            messageLength: message?.length || 0,
            hasAttachment: !!attachment,
            attachmentType: attachment?.type
        });
        
        const messageDiv = document.createElement('div');
        messageDiv.className = `chat-message ${role}`;
        
        const timestamp = new Date().toLocaleTimeString('id-ID', {
            hour: '2-digit',
            minute: '2-digit'
        });
        
        if (role === 'user') {
            // Attachment will be saved to filesystem by server-side when sent to AI
            if (attachment) {
                console.log('� PingoChat: Attachment ready to send to server for filesystem storage');
            }
            
            // Get user initials for avatar
            const userName = this.getUserName();
            const userInitials = this.getUserInitials(userName);
            
            let attachmentHtml = '';
            if (attachment && (attachment.type === 'document' || attachment.type === 'documents' || attachment.type === 'mixed' || attachment.type === 'images' || attachment.type === 'simple')) {
                console.log('🖼️ PingoChat: Generating attachment HTML for:', attachment.type);
                attachmentHtml = '<div class="mb-1 mt-1"><div class="gap-2 mx-0.5 mb-3 flex flex-wrap">';
                
                // Handle mixed attachments (documents + images + tasks)
                if (attachment.type === 'mixed') {
                    console.log('🔄 PingoChat: Processing mixed attachments - documents:', attachment.documents?.length || 0, 'images:', attachment.images?.length || 0, 'tasks:', attachment.tasks?.length || 0);
                    
                    // Process tasks first
                    if (attachment.tasks && attachment.tasks.length > 0) {
                        attachment.tasks.forEach((task, taskIndex) => {
                            console.log(`📋 PingoChat: Task ${taskIndex + 1} (${task.name}):`, task);
                            
                            // Determine status color based on deadline
                            let statusColor = 'blue';
                            let statusIcon = 'ti-clipboard-text';
                            
                            let formattedDeadline = 'N/A';
                            if (task.deadline) {
                                const deadlineDate = new Date(task.deadline);
                                if (!isNaN(deadlineDate.getTime())) {
                                    const now = new Date();
                                    const daysDiff = Math.ceil((deadlineDate - now) / (1000 * 60 * 60 * 24));
                                    
                                    formattedDeadline = deadlineDate.toLocaleDateString('id-ID', { 
                                        day: 'numeric', 
                                        month: 'short' 
                                    });
                                    
                                    if (daysDiff < 0) {
                                        statusColor = 'red';
                                        statusIcon = 'ti-alert-circle';
                                    } else if (daysDiff <= 3) {
                                        statusColor = 'orange';
                                        statusIcon = 'ti-clock';
                                    }
                                }
                            }
                            
                            // Get task type/extension equivalent  
                            let taskType = 'TASK';
                            if (task.subject) {
                                const mapel = task.subject.toUpperCase();
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

                            attachmentHtml += `
                                <div class="relative">
                                    <div class="group/thumbnail">
                                        <div class="document-thumbnail task-thumbnail rounded-lg text-left cursor-pointer transition-all border border-gray-200/25 flex flex-col justify-between gap-2.5 overflow-hidden px-2.5 py-2 bg-white hover:border-gray-300/50 hover:shadow-lg shadow-sm" style="width: 120px; height: 120px; min-width: 120px;">
                                            <div class="relative flex flex-col gap-1 min-h-0">
                                                <h3 class="text-xs break-words text-gray-900 overflow-hidden display-webkit-box webkit-line-clamp-3 webkit-box-orient-vertical font-medium">${this.escapeHtml(task.name)}</h3>
                                            </div>
                                            <div class="relative flex flex-row items-center gap-1 justify-between">
                                                <div class="flex flex-row gap-1 shrink min-w-0">
                                                    <div class="min-w-0 h-[18px] flex flex-row items-center justify-center gap-0.5 px-1 border border-gray-200/25 shadow-sm rounded bg-white/70 backdrop-blur-sm font-medium">
                                                        <p class="uppercase truncate text-gray-600 text-[11px] leading-[13px]">${taskType}</p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            `;
                        });
                    }
                    
                    // Process documents
                    if (attachment.documents && attachment.documents.length > 0) {
                        attachment.documents.forEach((doc, docIndex) => {
                            const fileName = doc.name || 'unknown_file';
                            const fileExtension = this.getFileExtension(fileName).toUpperCase() || 'FILE';
                            
                            let lineCount, isContentValid, thumbnailClass;
                            
                            if (doc.type === 'chunked') {
                                // Handle chunked documents
                                lineCount = `${doc.total_chunks} chunks`;
                                isContentValid = true;
                                thumbnailClass = '';
                                
                                console.log(`📋 PingoChat: Chunked Document ${docIndex + 1} (${doc.name}):`, {
                                    isChunked: true,
                                    totalChunks: doc.total_chunks,
                                    totalWords: doc.total_words
                                });
                            } else {
                                // Handle regular documents
                                isContentValid = doc.content && !doc.content.includes('Gagal memuat dokumen');
                                lineCount = isContentValid ? this.estimateLineCount(doc.content) : 'Tidak dapat dimuat';
                                thumbnailClass = isContentValid ? '' : 'opacity-50';
                                
                                console.log(`📋 PingoChat: Regular Document ${docIndex + 1} (${doc.name}):`, {
                                    isContentValid,
                                    contentLength: doc.content?.length || 0,
                                    lineCount
                                });
                            }
                            
                            attachmentHtml += `
                                <div class="relative">
                                    <div class="group/thumbnail">
                                        <div class="rounded-lg text-left cursor-pointer transition-all border border-gray-200/25 flex flex-col justify-between gap-2.5 overflow-hidden px-2.5 py-2 bg-white hover:border-gray-300/50 hover:shadow-lg shadow-sm ${thumbnailClass}" style="width: 120px; height: 120px; min-width: 120px;">
                                            <div class="relative flex flex-col gap-1 min-h-0">
                                                <h3 class="text-xs break-words text-gray-900 overflow-hidden display-webkit-box webkit-line-clamp-3 webkit-box-orient-vertical">${this.escapeHtml(doc.name)}</h3>
                                            </div>
                                            <div class="relative flex flex-row items-center gap-1 justify-between">
                                                <div class="flex flex-row gap-1 shrink min-w-0">
                                                    <div class="min-w-0 h-[18px] flex flex-row items-center justify-center gap-0.5 px-1 border border-gray-200/25 shadow-sm rounded bg-white/70 backdrop-blur-sm font-medium">
                                                        <p class="uppercase truncate text-gray-600 text-[11px] leading-[13px]">${fileExtension}</p>
                                                    </div>
                                                </div>
                                            </div>
                                            ${!isContentValid && doc.type !== 'chunked' ? '<div class="absolute inset-0 bg-red-50 bg-opacity-20 flex items-center justify-center"><i class="ti ti-alert-circle text-red-400 text-lg"></i></div>' : ''}
                                        </div>
                                    </div>
                                </div>
                            `;
                        });
                    }
                    
                    // Process images
                    if (attachment.images && attachment.images.length > 0) {
                        attachment.images.forEach((img, imgIndex) => {
                            const fileExtension = this.getFileExtension(img.name).toUpperCase() || 'IMG';
                            const fileSize = this.formatFileSize(img.file_size || 0);
                            
                            console.log(`🖼️ PingoChat: Image ${imgIndex + 1} (${img.name}):`, {
                                hasBase64: !!img.base64_data,
                                savedFilename: img.saved_filename,
                                filePath: img.file_path,
                                size: img.file_size || 0,
                                mimeType: img.mime_type
                            });
                            
                            // Create image preview HTML
                            let imagePreviewHtml;
                            
                            // Check if image is saved to filesystem
                            if (img.saved_filename && img.file_path) {
                                // Use get-image.php endpoint for filesystem images with absolute path
                                const imageUrl = `/lms/src/pingo/get-image.php?filename=${encodeURIComponent(img.saved_filename)}`;
                                console.log('🖼️ PingoChat: Generated image URL:', imageUrl);
                                imagePreviewHtml = `<div class="image-thumbnail-preview" style="background-image: url('${imageUrl}')"></div>`;
                            } else if (img.base64_data) {
                                // Fallback to base64 data
                                imagePreviewHtml = `<div class="image-thumbnail-preview" style="background-image: url('${img.base64_data}')"></div>`;
                            } else {
                                // Create a placeholder with file extension
                                imagePreviewHtml = `
                                    <div class="image-thumbnail-preview" style="background: linear-gradient(135deg, #f3f4f6 0%, #e5e7eb 100%); display: flex; align-items: center; justify-content: center;">
                                        <div class="text-xs text-gray-500 font-medium">${fileExtension}</div>
                                    </div>
                                `;
                            }
                            
                            attachmentHtml += `
                                <div class="relative">
                                    <div class="group/thumbnail">
                                        <div class="document-thumbnail image-thumbnail rounded-lg text-left cursor-pointer transition-all border border-gray-200/25 overflow-hidden bg-white hover:border-gray-300/50 hover:shadow-lg shadow-sm" style="width: 120px; height: 120px; min-width: 120px;" title="${this.escapeHtml(img.name)} (${fileSize})">
                                            ${imagePreviewHtml}
                                        </div>
                                    </div>
                                </div>
                            `;
                        });
                    }
                } else if (attachment.type === 'images') {
                    // Handle images-only attachments
                    console.log('🖼️ PingoChat: Processing images only:', attachment.images?.length || 0);
                    
                    if (attachment.images && attachment.images.length > 0) {
                        attachment.images.forEach((img, imgIndex) => {
                            const fileExtension = this.getFileExtension(img.name).toUpperCase() || 'IMG';
                            const fileSize = this.formatFileSize(img.file_size || 0);
                            
                            // Create image preview HTML
                            let imagePreviewHtml;
                            
                            // Check if image is saved to filesystem
                            if (img.saved_filename && img.file_path) {
                                // Use get-image.php endpoint for filesystem images with absolute path
                                const imageUrl = `/lms/src/pingo/get-image.php?filename=${encodeURIComponent(img.saved_filename)}`;
                                console.log('🖼️ PingoChat: Generated image URL (images-only):', imageUrl);
                                imagePreviewHtml = `<div class="image-thumbnail-preview" style="background-image: url('${imageUrl}')"></div>`;
                            } else if (img.base64_data) {
                                // Fallback to base64 data
                                imagePreviewHtml = `<div class="image-thumbnail-preview" style="background-image: url('${img.base64_data}')"></div>`;
                            } else {
                                // Create a placeholder with file extension
                                imagePreviewHtml = `
                                    <div class="image-thumbnail-preview" style="background: linear-gradient(135deg, #f3f4f6 0%, #e5e7eb 100%); display: flex; align-items: center; justify-content: center;">
                                        <div class="text-xs text-gray-500 font-medium">${fileExtension}</div>
                                    </div>
                                `;
                            }
                            
                            attachmentHtml += `
                                <div class="relative">
                                    <div class="group/thumbnail">
                                        <div class="document-thumbnail image-thumbnail rounded-lg text-left cursor-pointer transition-all border border-gray-200/25 overflow-hidden bg-white hover:border-gray-300/50 hover:shadow-lg shadow-sm" style="width: 120px; height: 120px; min-width: 120px;" title="${this.escapeHtml(img.name)} (${fileSize})">
                                            ${imagePreviewHtml}
                                        </div>
                                    </div>
                                </div>
                            `;
                        });
                    }
                } else if (attachment.type === 'documents') {
                    console.log('📄 PingoChat: Processing', attachment.documents.length, 'documents');
                    attachment.documents.forEach((doc, docIndex) => {
                        const fileName = doc.name || 'unknown_document';
                        const fileExtension = this.getFileExtension(fileName).toUpperCase() || 'FILE';
                        // Check if content is valid or if it's an error message
                        const isContentValid = doc.content && !doc.content.includes('Gagal memuat dokumen');
                        const lineCount = isContentValid ? this.estimateLineCount(doc.content) : 'Tidak dapat dimuat';
                        const thumbnailClass = isContentValid ? '' : 'opacity-50';
                        
                        console.log(`📋 PingoChat: Document ${docIndex + 1} (${fileName}):`, {
                            isContentValid,
                            contentLength: doc.content?.length || 0,
                            lineCount
                        });
                        
                        attachmentHtml += `
                            <div class="relative">
                                <div class="group/thumbnail">
                                    <div class="rounded-lg text-left cursor-pointer transition-all border border-gray-200/25 flex flex-col justify-between gap-2.5 overflow-hidden px-2.5 py-2 bg-white hover:border-gray-300/50 hover:shadow-lg shadow-sm ${thumbnailClass}" style="width: 120px; height: 120px; min-width: 120px;">
                                        <div class="relative flex flex-col gap-1 min-h-0">
                                            <h3 class="text-xs break-words text-gray-900 overflow-hidden display-webkit-box webkit-line-clamp-3 webkit-box-orient-vertical">${this.escapeHtml(doc.name)}</h3>
                                            <p class="text-[10px] overflow-hidden display-webkit-box webkit-line-clamp-1 webkit-box-orient-vertical break-words ${isContentValid ? 'text-gray-500' : 'text-red-500'}">${lineCount}</p>
                                        </div>
                                        <div class="relative flex flex-row items-center gap-1 justify-between">
                                            <div class="flex flex-row gap-1 shrink min-w-0">
                                                <div class="min-w-0 h-[18px] flex flex-row items-center justify-center gap-0.5 px-1 border border-gray-200/25 shadow-sm rounded bg-white/70 backdrop-blur-sm font-medium">
                                                    <p class="uppercase truncate text-gray-600 text-[11px] leading-[13px]">${fileExtension}</p>
                                                </div>
                                            </div>
                                        </div>
                                        ${!isContentValid ? '<div class="absolute inset-0 bg-red-50 bg-opacity-20 flex items-center justify-center"><i class="ti ti-alert-circle text-red-400 text-lg"></i></div>' : ''}
                                    </div>
                                </div>
                            </div>
                        `;
                    });
                } else {
                    // Single document
                    const fileName = attachment.name || 'unknown_document';
                    const fileExtension = this.getFileExtension(fileName).toUpperCase() || 'FILE';
                    const lineCount = this.estimateLineCount(attachment.content);
                    
                    attachmentHtml += `
                        <div class="relative">
                            <div class="group/thumbnail">
                                <div class="rounded-lg text-left cursor-pointer transition-all border border-gray-200/25 flex flex-col justify-between gap-2.5 overflow-hidden px-2.5 py-2 bg-white hover:border-gray-300/50 hover:shadow-lg shadow-sm" style="width: 120px; height: 120px; min-width: 120px;">
                                    <div class="relative flex flex-col gap-1 min-h-0">
                                        <h3 class="text-xs break-words text-gray-900 overflow-hidden display-webkit-box webkit-line-clamp-3 webkit-box-orient-vertical">${this.escapeHtml(attachment.name)}</h3>
                                        <p class="text-[10px] overflow-hidden display-webkit-box webkit-line-clamp-1 webkit-box-orient-vertical break-words text-gray-500">${lineCount}</p>
                                    </div>
                                    <div class="relative flex flex-row items-center gap-1 justify-between">
                                        <div class="flex flex-row gap-1 shrink min-w-0">
                                            <div class="min-w-0 h-[18px] flex flex-row items-center justify-center gap-0.5 px-1 border border-gray-200/25 shadow-sm rounded bg-white/70 backdrop-blur-sm font-medium">
                                                <p class="uppercase truncate text-gray-600 text-[11px] leading-[13px]">${fileExtension}</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;
                }
                
                if (attachment.type === 'simple') {
                    // Simple attachment display - just show thumbnails
                    console.log('📄 PingoChat: Processing simple attachments:', attachment.items?.length || 0);
                    
                    if (attachment.items && attachment.items.length > 0) {
                        attachment.items.forEach((item, index) => {
                            console.log(`📎 PingoChat: Simple item ${index + 1}:`, item);
                            
                            // Determine icon and color based on type
                            let icon = 'ti-file';
                            let bgColor = '#f3f4f6';
                            let iconColor = '#6b7280';
                            
                            if (item.type === 'document') {
                                if (item.fileType === 'PDF') {
                                    icon = 'ti-file-type-pdf';
                                    bgColor = '#fef2f2';
                                    iconColor = '#ef4444';
                                } else if (['DOC', 'DOCX'].includes(item.fileType)) {
                                    icon = 'ti-file-type-doc';
                                    bgColor = '#eff6ff';
                                    iconColor = '#3b82f6';
                                } else {
                                    icon = 'ti-file-text';
                                }
                            } else if (item.type === 'image') {
                                icon = 'ti-photo';
                                bgColor = '#f0fdf4';
                                iconColor = '#059669';
                            } else if (item.type === 'task') {
                                icon = 'ti-clipboard-text';
                                bgColor = '#fef3c7';
                                iconColor = '#d97706';
                            }
                            
                            attachmentHtml += `
                                <div class="relative">
                                    <div class="group/thumbnail">
                                        <div class="document-thumbnail rounded-lg text-left cursor-pointer transition-all border border-gray-200/25 flex flex-col justify-between gap-2.5 overflow-hidden px-2.5 py-2 bg-white hover:border-gray-300/50 hover:shadow-lg shadow-sm" style="width: 120px; height: 120px; min-width: 120px;">
                                            <div class="flex items-center justify-center" style="background: ${bgColor}; height: 60px; border-radius: 6px; margin: -4px -4px 0 -4px;">
                                                <i class="ti ${icon}" style="font-size: 24px; color: ${iconColor};"></i>
                                            </div>
                                            <div class="relative flex flex-col gap-1 min-h-0">
                                                <h3 class="text-xs break-words text-gray-900 overflow-hidden display-webkit-box webkit-line-clamp-2 webkit-box-orient-vertical font-medium">${this.escapeHtml(item.name)}</h3>
                                            </div>
                                            <div class="relative flex flex-row items-center gap-1 justify-between">
                                                <div class="flex flex-row gap-1 shrink min-w-0">
                                                    <div class="min-w-0 h-[18px] flex flex-row items-center justify-center gap-0.5 px-1 border border-gray-200/25 shadow-sm rounded bg-white/70 backdrop-blur-sm font-medium">
                                                        <p class="uppercase truncate text-gray-600 text-[11px] leading-[13px]">${item.fileType}</p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            `;
                        });
                    }
                }
                
                attachmentHtml += '</div></div>';
            }
            
            messageDiv.innerHTML = `
                ${attachmentHtml}
                <div class="flex flex-row-reverse gap-2 items-center">
                    <div class="flex shrink-0 items-center justify-center rounded-full font-bold select-none h-7 w-7 text-xs bg-gray-700 text-white">
                        ${userInitials}
                    </div>
                    <div class="group relative inline-flex gap-2 bg-gray-200 rounded-xl pl-6 py-2.5 break-words text-gray-900 transition-all max-w-[75ch] flex-col pr-2.5">
                        <div class="grid grid-cols-1 gap-2 py-0.5">
                            <p class="whitespace-pre-wrap break-words">${this.escapeHtml(message)}</p>
                        </div>
                        <div class="absolute bottom-0 right-2 pointer-events-none">
                            <div class="rounded-lg transition min-w-max pointer-events-auto translate-y-4 bg-white/80 translate-x-1 group-hover:translate-x-0.5 p-0.5 opacity-0 group-hover:opacity-100">
                                <div class="text-gray-600 flex items-stretch justify-between">
                                    <span class="text-xs px-2 py-1">${timestamp}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        } else if (role === 'ai') {
            messageDiv.innerHTML = `
                <div class="message-avatar ai-avatar">
                    <i class="ti ti-sparkles"></i>
                </div>
                <div class="message-content ai-message">
                    <div class="message-text" id="ai-message-text-${Date.now()}"></div>
                    <div class="message-time">${timestamp}</div>
                </div>
            `;
            
            this.chatMessages.appendChild(messageDiv);
            
            const messageTextElement = messageDiv.querySelector('.message-text');
            
            if (showTypingAnimation) {
                console.log('⚡ PingoChat: Real-time AI response with attachment - showing typing animation');
                // Don't scroll immediately, let typing animation complete first
                // Add fast typing effect for real-time AI response
                this.typeWriterEffect(messageTextElement, this.formatMessage(message));
                return; // Exit early to prevent duplicate append
            } else {
                console.log('📚 PingoChat: Historical AI message with attachment - no typing animation');
                // For historical messages, display immediately without animation
                messageTextElement.innerHTML = this.formatMessage(message);
                this.scrollToBottom(); // Scroll immediately for historical display
                return; // Exit early
            }
        }
        
        this.chatMessages.appendChild(messageDiv);
        this.scrollToBottom();
    }
    
    // Helper methods for user info
    getUserName() {
        // Get from session or default
        return window.userName || 'User';
    }
    
    getUserInitials(name) {
        return name.split(' ')
            .map(word => word.charAt(0))
            .join('')
            .substring(0, 2)
            .toUpperCase();
    }

    // Super fast typewriter effect with progressive scroll
    typeWriterEffect(element, htmlContent, speed = 5) {
        // First, set the final HTML content but make it invisible
        element.innerHTML = htmlContent;
        element.classList.add('typing-effect');
        
        // Get the final rendered text content for typing effect
        const finalText = element.textContent || element.innerText || '';
        
        // Store the final HTML for later
        const finalHTML = element.innerHTML;
        
        // Clear and start typing character by character
        element.innerHTML = '';
        let currentText = '';
        let i = 0;
        let lastScrollTime = 0;
        let userScrollDetected = false;
        const scrollInterval = 50; // Scroll every 50ms (20fps) for smooth following
        
        // Detect user manual scroll to cancel progressive scroll
        const handleUserScroll = () => {
            userScrollDetected = true;
        };
        
        // Add scroll listener to detect user interaction
        this.chatMessages.addEventListener('scroll', handleUserScroll, { passive: true });
        
        const timer = setInterval(() => {
            if (i < finalText.length) {
                currentText += finalText.charAt(i);
                
                // Create temporary element to hold partial content
                const tempElement = document.createElement('div');
                tempElement.innerHTML = htmlContent;
                
                // Truncate the text content while preserving HTML structure
                this.truncateElementToText(tempElement, currentText);
                element.innerHTML = tempElement.innerHTML;
                
                // Progressive scroll that follows typing animation - only if user hasn't scrolled manually
                const currentTime = Date.now();
                if (!userScrollDetected && currentTime - lastScrollTime > scrollInterval) {
                    this.scrollToMessageProgressive();
                    lastScrollTime = currentTime;
                }
                
                i++;
            } else {
                // Animation complete - show final perfect HTML
                element.innerHTML = finalHTML;
                element.classList.remove('typing-effect');
                clearInterval(timer);
                
                // Remove scroll listener
                this.chatMessages.removeEventListener('scroll', handleUserScroll);
                
                // Final scroll to ensure message is perfectly positioned - only if user hasn't scrolled manually
                if (!userScrollDetected) {
                    this.scrollToMessage();
                }
            }
        }, speed);
    }
    
    // Helper to truncate HTML element content to specific text length
    truncateElementToText(element, targetText) {
        const walker = document.createTreeWalker(
            element,
            NodeFilter.SHOW_TEXT,
            null,
            false
        );
        
        let currentLength = 0;
        let textNode;
        
        // Walk through all text nodes
        while (textNode = walker.nextNode()) {
            const nodeText = textNode.textContent;
            const remainingLength = targetText.length - currentLength;
            
            if (remainingLength <= 0) {
                // Remove this node entirely
                textNode.textContent = '';
            } else if (currentLength + nodeText.length <= targetText.length) {
                // Keep entire node
                currentLength += nodeText.length;
            } else {
                // Truncate this node
                textNode.textContent = nodeText.substring(0, remainingLength);
                currentLength = targetText.length;
            }
        }
    }
    
    // Helper method to handle API errors with user-friendly messages
    handleAPIError(error) {
        console.error('🚨 PingoChat: API Error detected:', error.message);
        
        // Check for rate limit error
        if (error.message.includes('429') || error.message.includes('rate_limit_exceeded') || error.message.includes('Rate limit reached')) {
            console.log('⏰ Rate limit detected, showing friendly message');
            
            // Extract wait time if available
            let waitTime = '1-2 menit';
            const timeMatch = error.message.match(/try again in (\d+\.?\d*)(s|m)/i);
            if (timeMatch) {
                const time = parseFloat(timeMatch[1]);
                const unit = timeMatch[2].toLowerCase();
                if (unit === 's') {
                    waitTime = time > 60 ? `${Math.ceil(time/60)} menit` : `${Math.ceil(time)} detik`;
                } else {
                    waitTime = `${Math.ceil(time)} menit`;
                }
            }
            
            return {
                type: 'rate_limit',
                userMessage: `🤖 **Pingo sedang istirahat sebentar...**

Maaf, saya sedang melayani banyak pengguna saat ini dan perlu istirahat sekitar **${waitTime}**.

💡 **Saran sementara:**
• Coba kirim pesan lagi dalam ${waitTime}
• Atau buat pesan yang lebih singkat
• Pertanyaan sederhana bisa dijawab lebih cepat

Terima kasih atas kesabarannya! 😊`
            };
        }
        
        // Check for other API errors
        if (error.message.includes('API Error') || error.message.includes('HTTP 5')) {
            return {
                type: 'server_error',
                userMessage: `🔧 **Ada gangguan teknis sementara**

Maaf, server AI sedang mengalami gangguan. Ini biasanya berlangsung singkat.

💡 **Silakan coba:**
• Tunggu beberapa menit lalu coba lagi
• Refresh halaman jika diperlukan
• Hubungi admin jika masalah berlanjut

Kami akan segera memperbaikinya! 🛠️`
            };
        }
        
        // Check for network/connection errors
        if (error.message.includes('Failed to fetch') || error.message.includes('NetworkError')) {
            return {
                type: 'network_error',
                userMessage: `🌐 **Masalah koneksi internet**

Sepertinya ada masalah dengan koneksi internet Anda.

💡 **Silakan coba:**
• Periksa koneksi internet
• Refresh halaman
• Coba lagi dalam beberapa saat

Jika masalah berlanjut, hubungi admin! 📡`
            };
        }
        
        // Default error
        return {
            type: 'general_error',
            userMessage: `⚠️ **Terjadi kesalahan**

Maaf, ada masalah saat memproses permintaan Anda.

💡 **Silakan coba:**
• Kirim pesan lagi
• Refresh halaman jika diperlukan
• Hubungi admin jika masalah berlanjut

Kami mohon maaf atas ketidaknyamanan ini! 🙏`
        };
    }
    
    // Method to send message to AI with optional attachment
    async sendToAI(message, attachment = null, userDisplayMessage = null) {
        // Add system instruction to prevent tables and keep responses concise
        const enhancedMessage = `INSTRUKSI PENTING:
- JANGAN GUNAKAN TABEL dalam jawaban
- Jawab SINGKAT dan PADAT, maksimal 3-4 paragraf 
- Fokus pada jawaban langsung, jangan berikan teori panjang
- Gunakan bullet points (•) untuk daftar, bukan tabel
- Jangan berikan definisi umum kecuali diminta spesifik

${message}`;

        const requestBody = { 
            message: enhancedMessage 
        };
        
        // Add separate user display message if provided (for database storage)
        if (userDisplayMessage) {
            requestBody.user_display_message = userDisplayMessage;
        }
        
        // Add attachment data if provided
        if (attachment) {
            requestBody.attachment = attachment;
            console.log('📤 PingoChat: Sending message with attachment to server:', attachment);
        }
        
        const response = await fetch('../pingo/chat-api.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(requestBody)
        });
        
        const data = await response.json();
        console.log('📥 PingoChat: Server response:', data);
        
        if (data.success) {
            return {
                message: data.message,
                modelInfo: data.model_info || null
            };
        } else {
            throw new Error(data.error || 'Terjadi kesalahan saat mengirim pesan');
        }
    }
    
    // Method to show typing indicator
    showTypingIndicator() {
        this.isLoading = true;
        const loadingDiv = document.createElement('div');
        loadingDiv.className = 'chat-message assistant loading-message';
        loadingDiv.id = 'typing-indicator';
        loadingDiv.innerHTML = `
            <div class="message-avatar ai-avatar">
                <span>AI</span>
            </div>
            <div class="message-content ai-message">
                <div class="message-text">
                    <div class="typing-indicator">
                        <span></span>
                        <span></span>
                        <span></span>
                    </div>
                </div>
            </div>
        `;
        
        this.chatMessages.appendChild(loadingDiv);
        this.scrollToBottom();
    }
    
    // Method to hide typing indicator
    hideTypingIndicator() {
        this.isLoading = false;
        const typingIndicator = document.getElementById('typing-indicator');
        if (typingIndicator) {
            typingIndicator.remove();
        }
    }
    
    // Method to save chat history
    saveChatHistory() {
        console.log('💾 PingoChat: Saving chat history with', this.chatHistory.length, 'messages');
        try {
            // Clean attachment data - no longer store base64 image data in localStorage
            const historyToSave = this.chatHistory.map((message, index) => {
                if (message.attachment) {
                    console.log(`📎 PingoChat: Processing attachment for message ${index + 1}`);
                    
                    // Create clean attachment without base64 data
                    const cleanAttachment = { ...message.attachment };
                    
                    // Remove base64 data from images - they're now stored in filesystem
                    if (cleanAttachment.images) {
                        cleanAttachment.images = cleanAttachment.images.map(img => ({
                            name: img.name,
                            mime_type: img.mime_type,
                            file_size: img.file_size,
                            saved_filename: img.saved_filename, // Keep filesystem reference
                            file_path: img.file_path, // Keep filesystem path
                            saved_at: img.saved_at, // Keep save timestamp
                            // Remove base64 data - it's now in filesystem
                            has_base64: false, // Indicate no base64 in localStorage
                            is_stored: true // Mark as stored in filesystem
                        }));
                    }
                    
                    // Keep documents content but limit size  
                    if (cleanAttachment.documents) {
                        cleanAttachment.documents = cleanAttachment.documents.map(doc => ({
                            name: doc.name,
                            type: doc.type,
                            content: doc.content ? doc.content.substring(0, 5000) + '...' : '', // Limit content
                            file_size: doc.file_size
                        }));
                    }
                    
                    return {
                        ...message,
                        attachment: cleanAttachment
                    };
                }
                return message;
            });
            
            const dataStr = JSON.stringify(historyToSave);
            console.log('📊 PingoChat: Chat history size:', (dataStr.length / 1024).toFixed(2), 'KB');
            
            // Check if data is too large for localStorage
            if (dataStr.length > 5000000) { // 5MB limit
                console.warn('⚠️ PingoChat: Chat history too large, keeping only recent messages');
                const recentHistory = historyToSave.slice(-20); // Keep only last 20 messages
                localStorage.setItem('pingo_chat_history', JSON.stringify(recentHistory));
            } else {
                localStorage.setItem('pingo_chat_history', dataStr);
            }
            
            console.log('✅ PingoChat: Chat history saved successfully');
        } catch (error) {
            console.error('❌ PingoChat: Error saving chat history:', error);
            // If still failing, save minimal history
            try {
                const minimalHistory = this.chatHistory.map(msg => ({
                    role: msg.role,
                    content: msg.content || msg.message,
                    timestamp: msg.timestamp,
                    message: msg.message || msg.content,
                    modelInfo: msg.modelInfo
                })).slice(-10); // Only last 10 messages without attachments
                
                localStorage.setItem('pingo_chat_history', JSON.stringify(minimalHistory));
                console.log('✅ PingoChat: Minimal chat history saved as fallback');
            } catch (fallbackError) {
                console.error('❌ PingoChat: Even minimal save failed:', fallbackError);
            }
        }
    }
    
    showLoading() {
        this.isLoading = true;
        this.updateSendButtonState(); // Update button state when loading starts
        const loadingDiv = document.createElement('div');
        loadingDiv.className = 'chat-message assistant loading-message';
        loadingDiv.id = 'loading-message';
        loadingDiv.innerHTML = `
            <div class="message-avatar ai-avatar">
                <span>AI</span>
            </div>
            <div class="message-content ai-message">
                <div class="message-text">
                    <div class="typing-indicator">
                        <span></span>
                        <span></span>
                        <span></span>
                    </div>
                </div>
            </div>
        `;
        
        this.chatMessages.appendChild(loadingDiv);
        this.scrollToBottom();
    }
    
    hideLoading() {
        this.isLoading = false;
        this.updateSendButtonState(); // Update button state when loading ends
        const loadingMessage = document.getElementById('loading-message');
        if (loadingMessage) {
            loadingMessage.remove();
        }
    }
    
    showChatInterface() {
        const wasEmptyState = this.chatEmptyState.style.display !== 'none';
        
        this.chatEmptyState.style.display = 'none';
        this.chatMessages.style.display = 'block';
        
        // Show input container only when there's actual content or interaction
        this.showInputContainer();
        
        // Update main container class to manage spacing
        const mainChatContainer = document.querySelector('.flex-1.bg-white.mx-3.md\\:mx-6') || 
                                 document.querySelector('.flex-1.bg-white');
        if (mainChatContainer) {
            mainChatContainer.classList.remove('chat-container-with-hidden-input');
        }
        
        // Re-display chat history if we're transitioning from empty state and have history
        if (wasEmptyState && this.chatHistory.length > 0) {
            console.log('🔄 PingoChat: Re-displaying chat history during interface transition');
            this.displayChatHistory();
        }
    }
    
    hideChatInterface() {
        this.chatEmptyState.style.display = 'flex';
        this.hideInputContainer();
        this.chatMessages.style.display = 'none';
    }
    
    resetToEmptyState() {
        console.log('🔄 PingoChat: Resetting to empty state');
        
        // Hide chat interface
        this.hideChatInterface();
        
        // Reset all inputs and clear values
        if (this.chatInput) {
            this.chatInput.value = '';
            this.chatInput.style.height = 'auto';
        }
        
        if (this.chatInputActive) {
            this.chatInputActive.value = '';
            this.chatInputActive.style.height = 'auto';
        }
        
        // Reset chat input container styles (clear any animation styles)
        if (this.chatInputContainer) {
            this.chatInputContainer.style.opacity = '';
            this.chatInputContainer.style.transform = '';
            this.chatInputContainer.style.transition = '';
            this.chatInputContainer.style.display = 'none';
            this.chatInputContainer.classList.add('hidden');
        }
        
        // Reset empty state input container styles
        const emptyStateContainer = document.querySelector('.empty-state-input .claude-input-container');
        if (emptyStateContainer) {
            emptyStateContainer.style.opacity = '1';
            emptyStateContainer.style.transition = '';
        }
        
        // Ensure empty state is visible
        if (this.chatEmptyState) {
            this.chatEmptyState.style.display = 'flex';
        }
        
        // Update main container class
        const mainChatContainer = document.querySelector('.flex-1.bg-white.mx-3.md\\:mx-6') || 
                                 document.querySelector('.flex-1.bg-white');
        if (mainChatContainer) {
            mainChatContainer.classList.add('chat-container-with-hidden-input');
        }
        
        console.log('✅ PingoChat: Successfully reset to empty state');
    }
    
    showInputContainer() {
        if (this.chatInputContainer) {
            this.chatInputContainer.style.display = 'block';
            this.chatInputContainer.classList.remove('hidden');
            
            // Update main container class
            const mainChatContainer = document.querySelector('.flex-1.bg-white.mx-3.md\\:mx-6') || 
                                     document.querySelector('.flex-1.bg-white');
            if (mainChatContainer) {
                mainChatContainer.classList.remove('chat-container-with-hidden-input');
            }
        }
    }
    
    hideInputContainer() {
        if (this.chatInputContainer) {
            this.chatInputContainer.style.display = 'none';
            this.chatInputContainer.classList.add('hidden');
            
            // Update main container class to extend messages area
            const mainChatContainer = document.querySelector('.flex-1.bg-white.mx-3.md\\:mx-6') || 
                                     document.querySelector('.flex-1.bg-white');
            if (mainChatContainer) {
                mainChatContainer.classList.add('chat-container-with-hidden-input');
            }
        }
    }
    
    showInputContainerFromEmptyState() {
        // This method is called when user starts typing in empty state
        // It should transition to the chat interface but keep the typing content
        console.log('🎬 PingoChat: Transitioning from empty state to chat interface with fade animation');
        
        const emptyStateInput = this.chatInput;
        const typedContent = emptyStateInput?.value || '';
        
        // Start the fade transition animation
        this.fadeTransitionToBottom(typedContent);
    }
    
    async fadeTransitionToBottom(typedContent) {
        console.log('✨ Starting fade transition animation');
        console.log('💬 Typed content:', typedContent);
        
        const emptyStateContainer = document.querySelector('.empty-state-input .claude-input-container');
        const chatInputContainer = this.chatInputContainer;
        
        console.log('📍 Elements found:', {
            emptyStateContainer: !!emptyStateContainer,
            chatInputContainer: !!chatInputContainer,
            chatInputActive: !!this.chatInputActive
        });
        
        if (!emptyStateContainer || !chatInputContainer) {
            console.log('❌ Fade elements not found, using fallback');
            this.showChatInterface();
            return;
        }
        
        // Step 1: Fade out empty state
        console.log('🔄 Step 1: Fading out empty state');
        emptyStateContainer.style.transition = 'opacity 0.3s ease-out';
        emptyStateContainer.style.opacity = '0';
        
        // Step 2: Show chat interface but hide input initially
        setTimeout(() => {
            console.log('🔄 Step 2: Switching to chat interface');
            
            // Hide empty state and show chat messages
            this.chatEmptyState.style.display = 'none';
            this.chatMessages.style.display = 'block';
            
            // Show and prepare input container for animation
            chatInputContainer.style.display = 'block';
            chatInputContainer.classList.remove('hidden');
            chatInputContainer.style.opacity = '0';
            chatInputContainer.style.transform = 'translateY(20px)';
            chatInputContainer.style.transition = 'opacity 0.4s ease-out, transform 0.4s ease-out';
            
            console.log('📦 Chat input container prepared:', {
                display: chatInputContainer.style.display,
                opacity: chatInputContainer.style.opacity,
                transform: chatInputContainer.style.transform
            });
            
            // Transfer content to real input
            if (this.chatInputActive && typedContent) {
                this.chatInputActive.value = typedContent;
                this.autoResize(this.chatInputActive);
                console.log('✍️ Content transferred to active input');
            }
            
            // Force reflow
            chatInputContainer.offsetHeight;
            
            // Step 3: Fade in bottom input with slide up effect
            console.log('🔄 Step 3: Animating input container in');
            requestAnimationFrame(() => {
                chatInputContainer.style.opacity = '1';
                chatInputContainer.style.transform = 'translateY(0)';
                
                console.log('🎨 Animation applied:', {
                    opacity: chatInputContainer.style.opacity,
                    transform: chatInputContainer.style.transform
                });
            });
            
            // Step 4: Focus and cleanup
            setTimeout(() => {
                console.log('🔄 Step 4: Focus and cleanup');
                
                if (this.chatInputActive) {
                    this.chatInputActive.focus();
                    this.chatInputActive.setSelectionRange(typedContent.length, typedContent.length);
                    console.log('🎯 Input focused');
                }
                
                // Clear empty state input
                if (this.chatInput) {
                    this.chatInput.value = '';
                }
                
                // Update main container class
                const mainChatContainer = document.querySelector('.flex-1.bg-white.mx-3.md\\:mx-6') || 
                                         document.querySelector('.flex-1.bg-white');
                if (mainChatContainer) {
                    mainChatContainer.classList.remove('chat-container-with-hidden-input');
                }
                
                console.log('✨ PingoChat: Fade transition completed successfully');
            }, 400);
            
        }, 300);
    }
    
    clearChat() {
        // Show the delete chat modal instead of confirm dialog
        if (window.showDeleteChatModal) {
            window.showDeleteChatModal();
        } else {
            // Fallback to confirm dialog if modal is not available
            this.performClearChat();
        }
    }
    
    async performClearChat() {
        try {
            const response = await fetch('../pingo/clear-chat.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                }
            });
            
            const data = await response.json();
            
            if (data.success) {
                this.chatHistory = [];
                this.chatMessages.innerHTML = '';
                
                // Reset to empty state with proper cleanup
                this.resetToEmptyState();
                
                // Clear localStorage chat history
                localStorage.removeItem('pingo_chat_history');
                // Note: Images are now stored in filesystem, no need to clear localStorage attachments
                
                console.log('Chat cleared successfully including localStorage');
            } else {
                console.error('Gagal menghapus chat:', data.error || 'Unknown error');
                throw new Error(data.error || 'Unknown error');
            }
        } catch (error) {
            console.error('Error clearing chat:', error);
            throw error;
        }
    }
    
    scrollToBottom() {
        console.log('📍 PingoChat: scrollToBottom() called');
        setTimeout(() => {
            this.chatMessages.scrollTop = this.chatMessages.scrollHeight;
        }, 100);
    }
    
    // Smart scroll that only scrolls enough to show the last message, not to the very bottom
    scrollToMessage() {
        console.log('🎯 PingoChat: scrollToMessage() called - smart scroll to AI message start');
        setTimeout(() => {
            const lastMessage = this.chatMessages.lastElementChild;
            if (lastMessage) {
                // Get the position of the AI message
                const messageRect = lastMessage.getBoundingClientRect();
                const containerRect = this.chatMessages.getBoundingClientRect();
                
                // Calculate the scroll position to show AI message at top of visible area
                const scrollTop = this.chatMessages.scrollTop + messageRect.top - containerRect.top - 10; // 10px padding
                
                // Smooth scroll to that position
                this.chatMessages.scrollTo({
                    top: scrollTop,
                    behavior: 'smooth'
                });
            }
        }, 100);
    }

    // Progressive scroll that follows typing animation - more gentle
    scrollToMessageProgressive() {
        const lastMessage = this.chatMessages.lastElementChild;
        if (lastMessage) {
            // Get current message height and container info
            const messageRect = lastMessage.getBoundingClientRect();
            const containerRect = this.chatMessages.getBoundingClientRect();
            
            // Check if message is getting out of view (bottom is below container)
            if (messageRect.bottom > containerRect.bottom - 20) {
                // Gentle scroll to keep the growing message in view
                const newScrollTop = this.chatMessages.scrollTop + 30; // Scroll down 30px gradually
                this.chatMessages.scrollTo({
                    top: newScrollTop,
                    behavior: 'auto' // Instant for smooth following effect
                });
            }
        }
    }
    
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    formatMessage(message) {
        // Enhanced markdown formatting for AI messages
        let formatted = this.escapeHtml(message);
        
        // Remove unwanted horizontal separators (---, ===, etc.)
        formatted = formatted.replace(/^\s*[-=]{3,}\s*$/gm, '');
        formatted = formatted.replace(/\n\s*[-=]{3,}\s*\n/g, '\n');
        formatted = formatted.replace(/^\s*[-=]{3,}\s*\n/g, '');
        formatted = formatted.replace(/\n\s*[-=]{3,}\s*$/g, '');
        
        // Convert line breaks to <br>
        formatted = formatted.replace(/\n/g, '<br>');
        
        // Only apply markdown parsing if the message actually contains markdown syntax
        const hasMarkdown = /^#|\*\*|\*[^*]|`|^\d+\.|^[-*]\s/.test(message);
        
        if (hasMarkdown) {
            // Headers (must be at start of line or after <br>)
            formatted = formatted.replace(/(^|<br>)### (.*?)(<br>|$)/g, '$1<h3 class="text-lg font-semibold text-gray-800 mt-4 mb-2">$2</h3>$3');
            formatted = formatted.replace(/(^|<br>)## (.*?)(<br>|$)/g, '$1<h2 class="text-xl font-semibold text-gray-800 mt-4 mb-2">$2</h2>$3');
            formatted = formatted.replace(/(^|<br>)# (.*?)(<br>|$)/g, '$1<h1 class="text-2xl font-bold text-gray-900 mt-4 mb-3">$2</h1>$3');
            
            // Bold text with **text**
            formatted = formatted.replace(/\*\*(.*?)\*\*/g, '<strong class="font-semibold text-gray-900">$1</strong>');
            
            // Italic text with *text* (but not ** patterns)
            formatted = formatted.replace(/(?<!\*)\*([^*]+?)\*(?!\*)/g, '<em class="italic text-gray-700">$1</em>');
            
            // Numbered lists (1. item)
            formatted = formatted.replace(/(^|<br>)(\d+\.\s+)(.*?)(?=<br>|$)/g, '$1<div class="ml-4 mb-1"><span class="font-medium text-orange-600">$2</span>$3</div>');
            
            // Bullet lists (- item or * item) - only if starts with - or * followed by space
            formatted = formatted.replace(/(^|<br>)([-*]\s+)(.*?)(?=<br>|$)/g, '$1<div class="ml-4 mb-1"><span class="text-orange-600 mr-2">•</span>$3</div>');
            
            // Clean up extra <br> tags around headers and lists
            formatted = formatted.replace(/<br><h([1-3])/g, '<h$1');
            formatted = formatted.replace(/<\/h([1-3])><br>/g, '</h$1>');
            formatted = formatted.replace(/<br><div class="ml-4/g, '<div class="ml-4');
            
            // Add spacing around headers
            formatted = formatted.replace(/<h([1-3])/g, '<div class="mt-3"></div><h$1');
        }
        
        // Always apply these regardless of markdown detection
        // Code blocks with ```code```
        formatted = formatted.replace(/```(.*?)```/gs, '<pre class="bg-gray-100 border border-gray-200 rounded-md p-3 my-2 overflow-x-auto"><code class="text-sm font-mono">$1</code></pre>');
        
        // Inline code with `code`
        formatted = formatted.replace(/`(.*?)`/g, '<code class="bg-gray-100 text-gray-800 px-1 py-0.5 rounded text-sm font-mono">$1</code>');
        
        return formatted;
    }
    
    // Document upload and preview methods
    async handleDocumentUpload(file) {
        try {
            // Show loading thumbnail (no blur effect)
            this.showDocumentThumbnail(file, 'Loading...');
            
            // Parse document content
            const content = await this.parseDocument(file);
            
            // Update thumbnail with parsed content
            this.updateDocumentThumbnail(file, content);
            
        } catch (error) {
            console.error('Error parsing document:', error);
            this.updateDocumentThumbnail(file, 'Gagal memuat dokumen.');
        }
    }

    // Image upload and preview methods
    async handleImageUpload(file) {
        try {
            console.log('📷 PingoChat: Handling image upload:', file.name);
            
            // Validate file type
            if (!file.type.startsWith('image/')) {
                console.error('Invalid file type:', file.type);
                return;
            }
            
            // Check file size (max 5MB before compression)
            const maxSizeBeforeCompression = 5 * 1024 * 1024; // 5MB
            if (file.size > maxSizeBeforeCompression) {
                alert(`Gambar terlalu besar (${this.formatFileSize(file.size)}). Maksimal 5MB. Silakan kompres gambar terlebih dahulu.`);
                return;
            }
            
            console.log('📊 Original file size:', this.formatFileSize(file.size));
            
            // Compress image if needed
            const compressedFile = await this.compressImage(file);
            console.log('📊 Compressed file size:', this.formatFileSize(compressedFile.size));
            
            // Create image preview URL for thumbnail (using original for better quality)
            const previewUrl = URL.createObjectURL(file);
            
            // Convert compressed image to base64 for backend processing
            const base64Data = await this.fileToBase64(compressedFile);
            
            // Check base64 size
            const base64Size = base64Data.length * 0.75; // Approximate binary size
            console.log('📊 Base64 data size:', this.formatFileSize(base64Size));
            
            // Show image thumbnail
            this.showImageThumbnail(file, previewUrl, base64Data);
            
        } catch (error) {
            console.error('Error handling image upload:', error);
            alert('Gagal mengupload gambar: ' + error.message);
        }
    }

    // Compress image to reduce file size
    async compressImage(file, maxWidth = 1920, maxHeight = 1920, quality = 0.9) {
        return new Promise((resolve) => {
            const canvas = document.createElement('canvas');
            const ctx = canvas.getContext('2d');
            const img = new Image();
            
            img.onload = () => {
                // Calculate new dimensions
                let { width, height } = img;
                
                // Only compress if image is larger than max dimensions
                const needsResize = width > maxWidth || height > maxHeight;
                
                if (needsResize) {
                    if (width > height) {
                        if (width > maxWidth) {
                            height = (height * maxWidth) / width;
                            width = maxWidth;
                        }
                    } else {
                        if (height > maxHeight) {
                            width = (width * maxHeight) / height;
                            height = maxHeight;
                        }
                    }
                } else {
                    // Keep original dimensions if already small enough
                    console.log('📊 Image already optimal size, minimal compression applied');
                }
                
                // Set canvas dimensions
                canvas.width = width;
                canvas.height = height;
                
                // Draw compressed image
                ctx.drawImage(img, 0, 0, width, height);
                
                // Convert to blob with higher quality for Vision API
                canvas.toBlob((blob) => {
                    const compressedFile = new File([blob], file.name, {
                        type: file.type.includes('png') ? 'image/png' : 'image/jpeg',
                        lastModified: Date.now()
                    });
                    resolve(compressedFile);
                }, file.type.includes('png') ? 'image/png' : 'image/jpeg', quality);
            };
            
            img.src = URL.createObjectURL(file);
        });
    }

    // Convert file to base64 for backend processing
    fileToBase64(file) {
        return new Promise((resolve, reject) => {
            const reader = new FileReader();
            reader.onload = () => resolve(reader.result);
            reader.onerror = reject;
            reader.readAsDataURL(file);
        });
    }
    
    showDocumentThumbnail(file, content) {
        // Determine which preview area to use (empty state or active chat)
        const isEmptyState = this.chatEmptyState.style.display !== 'none';
        const previewArea = isEmptyState ? 
            document.getElementById('document-preview-area-empty') : 
            document.getElementById('document-preview-area');
        
        const thumbnailsContainer = previewArea.querySelector('.document-thumbnails');
        
        // Animate input wrapper down
        const inputWrapper = previewArea.parentElement.querySelector('.input-wrapper');
        if (inputWrapper) {
            inputWrapper.classList.add('animate-down');
            setTimeout(() => {
                inputWrapper.classList.remove('animate-down');
            }, 300);
        }
        
        // Create thumbnail element
        const thumbnail = document.createElement('div');
        thumbnail.className = 'document-thumbnail';
        thumbnail.dataset.fileName = file.name;
        
        const fileExtension = this.getFileExtension(file.name).toUpperCase() || 'FILE';
        const fileSize = this.formatFileSize(file.size);
        // Check if content indicates parsing failure
        const isParsingFailed = content.includes('tidak dapat dibaca otomatis') || 
                                content.includes('encoding complex') ||
                                content.includes('Copy-paste isi dokumen');
        
        const lineCount = content === 'Loading...' ? 'Loading...' : this.estimateLineCount(content);
        
        thumbnail.innerHTML = `
            <div class="document-thumbnail-header">
                <h3 class="document-thumbnail-title">${this.escapeHtml(file.name)}</h3>
                <p class="document-thumbnail-meta">${lineCount}</p>
                ${isParsingFailed ? '<p class="text-xs text-amber-600 mt-1">⚠️ Perlu input manual</p>' : ''}
            </div>
            <div class="document-thumbnail-footer">
                <div class="document-thumbnail-type">${fileExtension}</div>
                ${isParsingFailed ? '<button class="text-xs text-blue-600 hover:text-blue-800" onclick="addManualDescription(\''+file.name+'\')">+ Tambah deskripsi</button>' : ''}
            </div>
            <button class="document-remove-btn" onclick="removeDocumentThumbnail('${file.name}')">
                <i class="ti ti-x"></i>
            </button>
        `;
        
        // Store content in data attribute
        thumbnail.dataset.content = content;
        
        // Add to container
        thumbnailsContainer.appendChild(thumbnail);
        
        // Show preview area with animation
        previewArea.classList.remove('hidden');
    }

    showImageThumbnail(file, previewUrl, base64Data) {
        console.log('🖼️ PingoChat: Showing image thumbnail:', file.name);
        
        // Determine which preview area to use (empty state or active chat)
        const isEmptyState = this.chatEmptyState.style.display !== 'none';
        const previewArea = isEmptyState ? 
            document.getElementById('document-preview-area-empty') : 
            document.getElementById('document-preview-area');
        
        const thumbnailsContainer = previewArea.querySelector('.document-thumbnails');
        
        // Animate input wrapper down
        const inputWrapper = previewArea.parentElement.querySelector('.input-wrapper');
        if (inputWrapper) {
            inputWrapper.classList.add('animate-down');
            setTimeout(() => {
                inputWrapper.classList.remove('animate-down');
            }, 300);
        }
        
        // Create thumbnail element
        const thumbnail = document.createElement('div');
        thumbnail.className = 'document-thumbnail image-thumbnail';
        thumbnail.dataset.fileName = file.name;
        thumbnail.dataset.fileType = 'image';
        
        const fileExtension = this.getFileExtension(file.name).toUpperCase() || 'IMG';
        const fileSize = this.formatFileSize(file.size);
        
        thumbnail.innerHTML = `
            <div class="image-thumbnail-preview" style="background-image: url('${previewUrl}')"></div>
            <div class="document-thumbnail-header">
                <h3 class="document-thumbnail-title">${this.escapeHtml(file.name)}</h3>
                <p class="document-thumbnail-meta">${fileSize}</p>
            </div>
            <div class="document-thumbnail-footer">
                <div class="document-thumbnail-type">${fileExtension}</div>
            </div>
            <button class="document-remove-btn" onclick="removeImageThumbnail('${file.name}')">
                <i class="ti ti-x"></i>
            </button>
        `;
        
        // Store data in thumbnail for sending to backend
        thumbnail.dataset.previewUrl = previewUrl;
        thumbnail.dataset.base64Data = base64Data;
        thumbnail.dataset.fileSize = file.size;
        thumbnail.dataset.mimeType = file.type;
        
        // Add to container
        thumbnailsContainer.appendChild(thumbnail);
        
        // Show preview area with animation
        previewArea.classList.remove('hidden');
        
        console.log('✅ PingoChat: Image thumbnail added successfully');
    }
    
    updateDocumentThumbnail(file, content) {
        const thumbnail = document.querySelector(`.document-thumbnail[data-file-name="${file.name}"]`);
        if (thumbnail) {
            const metaElement = thumbnail.querySelector('.document-thumbnail-meta');
            metaElement.textContent = this.estimateLineCount(content);
            
            // Update stored content
            thumbnail.dataset.content = content;
        }
    }
    
    estimateLineCount(content) {
        if (!content || content === 'Loading...') return 'Loading...';
        
        // Check for error content
        if (content.includes('Gagal memuat dokumen') || content.includes('Failed to')) {
            return 'Error loading';
        }
        
        try {
            const lines = content.split('\n').length;
            return `${lines} baris`;
        } catch (error) {
            console.error('Error estimating line count:', error);
            return 'Unknown';
        }
    }
    
    async sendMessageWithDocuments() {
        // Get current input
        const isEmptyState = this.chatEmptyState.style.display !== 'none';
        const currentInput = isEmptyState ? this.chatInput : this.chatInputActive;
        const message = currentInput.value.trim();
        
        if (!message) {
            alert('Mohon tulis pesan terlebih dahulu.');
            return;
        }
        
        // Get all thumbnails (documents, images, and tasks)
        const thumbnails = document.querySelectorAll('.document-thumbnail, .task-thumbnail');
        
        if (thumbnails.length === 0) {
            // No attachments, send normal message
            await this.sendMessage();
            return;
        }
        
        // Separate documents, images, and tasks
        const documents = [];
        const images = [];
        const tasks = [];
        
        // Create a snapshot of assignment data before clearing thumbnails
        const assignmentDataSnapshot = window.currentAssignmentData ? JSON.parse(JSON.stringify(window.currentAssignmentData)) : null;
        
        Array.from(thumbnails).forEach(thumb => {
            if (thumb.classList.contains('task-thumbnail')) {
                // This is a task thumbnail
                const assignmentId = thumb.dataset.assignmentId;
                console.log('📋 PingoChat: Processing task thumbnail with ID:', assignmentId);
                
                if (assignmentDataSnapshot) {
                    const assignment = assignmentDataSnapshot.assignment || 
                                     assignmentDataSnapshot.detailedData?.data;
                    if (assignment && assignment.id == assignmentId) {
                        tasks.push({
                            id: assignmentId,
                            name: assignment.judul,
                            subject: assignment.kelas?.mata_pelajaran || 'N/A',
                            deadline: assignment.deadline_formatted || assignment.deadline,
                            analysisPrompt: assignmentDataSnapshot.analysisPrompt
                        });
                        console.log('📋 PingoChat: Added task to attachments:', assignment.judul);
                    } else {
                        console.warn('📋 PingoChat: Task assignment data not matching:', { assignmentId, currentData: assignmentDataSnapshot });
                    }
                } else {
                    console.warn('📋 PingoChat: No assignment data snapshot available for task:', assignmentId);
                }
            } else if (thumb.dataset.fileType === 'image') {
                images.push({
                    name: thumb.dataset.fileName,
                    base64_data: thumb.dataset.base64Data,
                    mime_type: thumb.dataset.mimeType,
                    file_size: parseInt(thumb.dataset.fileSize)
                });
            } else if (thumb.dataset.fileType === 'chunked-document') {
                // Handle chunked documents
                documents.push({
                    name: thumb.dataset.fileName,
                    document_id: thumb.dataset.documentId,
                    total_chunks: parseInt(thumb.dataset.totalChunks) || 0,
                    total_words: parseInt(thumb.dataset.totalWords) || 0,
                    type: 'chunked'
                });
            } else {
                // Handle regular documents
                documents.push({
                    name: thumb.dataset.fileName,
                    content: thumb.dataset.content,
                    type: 'regular'
                });
            }
        });
        
        console.log('📎 PingoChat: Prepared attachments:', { 
            documents: documents.length, 
            images: images.length, 
            tasks: tasks.length 
        });
        
        // Create full message with attachments
        let fullMessage = '';
        
        // Add task context first
        tasks.forEach((task, index) => {
            fullMessage += `${task.analysisPrompt}\n\n`;
            console.log('📋 PingoChat: Added task analysis to message:', task.name);
        });
        
        // Add documents content
        documents.forEach((doc, index) => {
            if (doc.type === 'chunked') {
                // Handle chunked documents
                fullMessage += `Dokumen ${index + 1}: ${doc.name}\n\nStatus: Dokumen berhasil diproses dengan ${doc.total_chunks} chunks (${doc.total_words} kata)\nNote: Dokumen telah di-chunk untuk efisiensi pemrosesan AI.\n\n`;
            } else {
                // Handle regular documents
                // Convert escape characters to actual newlines for better display
                let displayContent = doc.content || '';
                if (displayContent && displayContent.includes('\\n')) {
                    displayContent = displayContent.replace(/\\n/g, '\n');
                }
                
                // Check if this is a parsing failure message
                const isParsingFailed = displayContent && (
                                        displayContent.includes('tidak dapat dibaca otomatis') || 
                                        displayContent.includes('encoding complex') ||
                                        displayContent.includes('Copy-paste isi dokumen')
                                        );
                
                if (isParsingFailed) {
                    fullMessage += `Dokumen ${index + 1}: ${doc.name}\n\nStatus: Dokumen tidak dapat diparse otomatis (format PDF complex)\nCatatan: ${displayContent}\n\n`;
                } else {
                    fullMessage += `Dokumen ${index + 1}: ${doc.name}\n\nKonten:\n${displayContent}\n\n`;
                }
            }
        });
        
        // Add images information
        images.forEach((img, index) => {
            fullMessage += `Gambar ${index + 1}: ${img.name}\n\nCatatan: Gambar telah dilampirkan untuk analisis visual.\n\n`;
        });
        
        fullMessage += `Pertanyaan: ${message}`;
        
        // Clear input FIRST
        currentInput.value = '';
        this.autoResize(currentInput);
        
        // Clear attachments IMMEDIATELY after input cleared (before AI response)
        this.clearDocumentPreviews();
        
        // Create simple attachment object for display
        const simpleAttachment = {
            type: 'simple',
            items: []
        };
        
        // Add all items to simple display
        documents.forEach(doc => {
            simpleAttachment.items.push({
                type: 'document',
                name: doc.name,
                fileType: doc.type === 'chunked' ? 'PDF' : this.getFileExtension(doc.name).toUpperCase()
            });
        });
        
        images.forEach(img => {
            simpleAttachment.items.push({
                type: 'image',
                name: img.name,
                fileType: this.getFileExtension(img.name).toUpperCase()
            });
        });
        
        tasks.forEach(task => {
            simpleAttachment.items.push({
                type: 'task',
                name: task.name,
                fileType: 'TASK'
            });
        });
        
        // Send message with attachments
        await this.sendMessageWithContent(fullMessage, message, simpleAttachment);
    }
    
    clearDocumentPreviews() {
        console.log('🧹 PingoChat: Clearing document previews...');
        
        const previewAreas = [
            document.getElementById('document-preview-area-empty'),
            document.getElementById('document-preview-area')
        ];
        
        // Also check for any remaining thumbnails elsewhere (including task thumbnails)
        const allThumbnails = document.querySelectorAll('.document-thumbnail, .task-thumbnail');
        console.log('🔍 PingoChat: Found total thumbnails to clear:', allThumbnails.length);
        
        previewAreas.forEach(area => {
            if (area) {
                // Clean up image preview URLs to prevent memory leaks
                const imageThumbnails = area.querySelectorAll('.document-thumbnail[data-file-type="image"]');
                imageThumbnails.forEach(thumb => {
                    const previewUrl = thumb.dataset.previewUrl;
                    if (previewUrl) {
                        URL.revokeObjectURL(previewUrl);
                    }
                });
                
                // Animate input wrapper up
                const inputWrapper = area.parentElement.querySelector('.input-wrapper');
                if (inputWrapper) {
                    inputWrapper.classList.add('animate-up');
                    setTimeout(() => {
                        inputWrapper.classList.remove('animate-up');
                    }, 300);
                }
                
                // Clear thumbnails and hide area
                const thumbnailsContainer = area.querySelector('.document-thumbnails');
                if (thumbnailsContainer) {
                    thumbnailsContainer.innerHTML = '';
                }
                area.classList.add('hidden');
            }
        });
        
        console.log('✅ PingoChat: Document previews cleared');
    }
    
    async parseDocument(file) {
        console.log('📄 PingoChat: Parsing document:', file.name, file.type, file.size);
        console.log('🌐 PingoChat: Current URL:', window.location.href);
        console.log('🌐 PingoChat: Current pathname:', window.location.pathname);
        
        const formData = new FormData();
        formData.append('document', file);
        
        // Determine correct API path based on current location
        let apiPath;
        const currentPath = window.location.pathname;
        
        if (currentPath.includes('/src/front/')) {
            // We're in src/front/, so go up one level to src/api/
            apiPath = '../api/parse-document.php';
        } else if (currentPath.includes('/lms/')) {
            // We're in the LMS folder, use absolute path
            apiPath = '/lms/src/api/parse-document.php';
        } else {
            // Fallback to relative path from document root
            apiPath = 'src/api/parse-document.php';
        }
        
        console.log('🎯 PingoChat: Using API path:', apiPath);
        
        // Test network connectivity first
        try {
            console.log('🔍 PingoChat: Testing network connectivity...');
            const testResponse = await fetch('../api/simple-test.php');
            const testData = await testResponse.text();
            console.log('✅ PingoChat: Network test successful:', testData.substring(0, 100));
        } catch (testError) {
            console.warn('⚠️ PingoChat: Network test failed:', testError);
        }
        
        try {
            const response = await fetch(apiPath, {
                method: 'POST',
                body: formData,
                // Add timeout and error handling
                signal: AbortSignal.timeout(30000) // 30 second timeout
            });
            
            console.log('📡 PingoChat: Parse document response status:', response.status, response.statusText);
            console.log('📡 PingoChat: Response headers:', [...response.headers.entries()]);
            
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            
            const responseText = await response.text();
            console.log('📄 PingoChat: Raw response length:', responseText.length);
            console.log('📄 PingoChat: Raw response (first 500 chars):', responseText.substring(0, 500));
            
            // Check if response is empty
            if (!responseText || responseText.trim() === '') {
                console.error('❌ PingoChat: Server returned empty response');
                console.log('📡 PingoChat: Response object:', response);
                throw new Error('Empty response from server');
            }
            
            let data;
            try {
                data = JSON.parse(responseText);
            } catch (jsonError) {
                console.error('❌ PingoChat: JSON parse error:', jsonError);
                console.error('Raw response was:', responseText);
                
                // Check if response looks like it might be truncated JSON
                if (responseText.trim().startsWith('{') && !responseText.trim().endsWith('}')) {
                    console.warn('⚠️ PingoChat: Response appears to be truncated JSON');
                    // Try to extract content if possible
                    const contentMatch = responseText.match(/"content":"([^"]*)/);
                    if (contentMatch) {
                        console.log('🔄 PingoChat: Extracting content from truncated response');
                        return contentMatch[1];
                    }
                }
                
                throw new Error('Invalid JSON response from server. Response: ' + responseText.substring(0, 100));
            }
            
            console.log('📊 PingoChat: Parsed response:', data);
            
            if (data.success) {
                const content = data.data?.content || 'Tidak dapat membaca konten dokumen.';
                console.log('✅ PingoChat: Document parsed successfully, content length:', content.length);
                return content;
            } else {
                const errorMsg = data.error || 'Gagal memproses dokumen';
                console.error('❌ PingoChat: Server returned error:', errorMsg);
                throw new Error(errorMsg);
            }
            
        } catch (error) {
            console.error('💥 PingoChat: Document parsing failed:', error);
            throw error;
        }
    }
    
    async sendMessageWithContent(fullMessage, userMessage, attachment = null) {
        console.log('🚀 PingoChat: Starting sendMessageWithContent...', {
            fullMessageLength: fullMessage.length,
            userMessageLength: userMessage.length,
            hasAttachment: !!attachment,
            isLoading: this.isLoading
        });
        
        if (this.isLoading) return;
        
        this.isLoading = true;
        
        // Show chat interface if in empty state
        console.log('🔍 PingoChat: Checking chat state...');
        console.log('Chat empty state display:', this.chatEmptyState.style.display);
        console.log('Chat input container display:', this.chatInputContainer.style.display);
        
        if (this.chatEmptyState.style.display !== 'none') {
            console.log('📱 PingoChat: Switching from empty state to chat interface...');
            this.chatEmptyState.style.display = 'none';
            this.chatInputContainer.style.display = 'block';
        }
        
        // Add user message to chat
        console.log('➕ PingoChat: Adding user message...');
        this.addMessage('user', userMessage, attachment);
        
        // Show typing indicator
        console.log('⏳ PingoChat: Showing typing indicator...');
        this.showTypingIndicator();
        
        try {
            // Send to AI with attachment data
            console.log('🤖 PingoChat: Sending to AI...');
            const response = await this.sendToAI(fullMessage, attachment, userMessage);
            console.log('✅ PingoChat: AI response received:', response?.message?.length || 0, 'chars');
            
            // Remove typing indicator
            this.hideTypingIndicator();
            
            // Add AI response with model info
            console.log('➕ PingoChat: Adding AI response...');
            const modelInfoAttachment = response.modelInfo ? { modelInfo: response.modelInfo } : null;
            this.addMessage('ai', response.message || response, modelInfoAttachment);
            
            // Save to history with timestamps
            console.log('💾 PingoChat: Saving to history...');
            const currentTimestamp = new Date().toISOString();
            this.chatHistory.push({ 
                role: 'user', 
                content: userMessage, 
                attachment,
                timestamp: currentTimestamp,
                message: userMessage // For compatibility
            });
            this.chatHistory.push({ 
                role: 'ai', 
                content: response.message || response,
                timestamp: new Date(Date.now() + 500).toISOString(), // AI response 500ms later to ensure proper order
                message: response.message || response, // For compatibility
                modelInfo: response.modelInfo || null
            });
            this.saveChatHistory();

            console.log('✅ PingoChat: Message flow completed successfully');

        } catch (error) {
            console.error('💥 PingoChat: Error in sendMessageWithContent:', error);
            this.hideTypingIndicator();
            
            // Handle different types of errors with user-friendly messages
            const errorInfo = this.handleAPIError(error);
            this.addMessage('ai', errorInfo.userMessage);
        }

        console.log('🔓 PingoChat: Setting isLoading to false...');
        this.isLoading = false;
    }
    
    // Utility methods
    getFileExtension(filename) {
        if (!filename || typeof filename !== 'string') {
            return '';
        }
        return filename.split('.').pop() || '';
    }
    
    formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }
}

// Global functions for document preview
window.removeDocumentThumbnail = function(fileName) {
    console.log('🗑️ PingoChat: Removing document thumbnail:', fileName);
    const thumbnail = document.querySelector(`.document-thumbnail[data-file-name="${fileName}"]`);
    if (thumbnail) {
        // Add removing animation
        thumbnail.classList.add('removing');
        
        // Remove after animation completes
        setTimeout(() => {
            thumbnail.remove();
            console.log('✅ PingoChat: Thumbnail removed successfully');
            
            // Check if there are no more thumbnails, hide preview areas
            const allThumbnails = document.querySelectorAll('.document-thumbnail');
            if (allThumbnails.length === 0) {
                console.log('📝 PingoChat: No more thumbnails, hiding preview areas');
                const previewAreas = [
                    document.getElementById('document-preview-area-empty'),
                    document.getElementById('document-preview-area')
                ];
                
                previewAreas.forEach(area => {
                    if (area && !area.classList.contains('hidden')) {
                        // Animate input wrapper up
                        const inputWrapper = area.parentElement.querySelector('.input-wrapper');
                        if (inputWrapper) {
                            inputWrapper.classList.add('animate-up');
                            setTimeout(() => {
                                inputWrapper.classList.remove('animate-up');
                            }, 300);
                        }
                        
                        // Hide preview area
                        area.classList.add('hidden');
                    }
                });
            }
        }, 200); // Match the thumbnailFadeOut animation duration
    }
};

// Global function for image thumbnail removal
window.removeImageThumbnail = function(fileName) {
    console.log('🗑️ PingoChat: Removing image thumbnail:', fileName);
    const thumbnail = document.querySelector(`.document-thumbnail[data-file-name="${fileName}"][data-file-type="image"]`);
    if (thumbnail) {
        // Cleanup preview URL to prevent memory leaks
        const previewUrl = thumbnail.dataset.previewUrl;
        if (previewUrl) {
            URL.revokeObjectURL(previewUrl);
        }
        
        // Add removing animation
        thumbnail.classList.add('removing');
        
        // Remove after animation completes
        setTimeout(() => {
            thumbnail.remove();
            console.log('✅ PingoChat: Image thumbnail removed successfully');
            
            // Check if there are no more thumbnails, hide preview areas
            const allThumbnails = document.querySelectorAll('.document-thumbnail');
            if (allThumbnails.length === 0) {
                console.log('📝 PingoChat: No more thumbnails, hiding preview areas');
                const previewAreas = [
                    document.getElementById('document-preview-area-empty'),
                    document.getElementById('document-preview-area')
                ];
                
                previewAreas.forEach(area => {
                    if (area && !area.classList.contains('hidden')) {
                        // Animate input wrapper up
                        const inputWrapper = area.parentElement.querySelector('.input-wrapper');
                        if (inputWrapper) {
                            inputWrapper.classList.add('animate-up');
                            setTimeout(() => {
                                inputWrapper.classList.remove('animate-up');
                            }, 300);
                        }
                        
                        // Hide preview area
                        area.classList.add('hidden');
                    }
                });
            }
        }, 200); // Match the thumbnailFadeOut animation duration
    }
};

// Global function for manual description
window.addManualDescription = function(fileName) {
    console.log('📝 PingoChat: Adding manual description for:', fileName);
    
    const description = prompt(
        `Dokumen "${fileName}" tidak dapat dibaca otomatis.\n\n` +
        `Silakan masukkan deskripsi atau ringkasan isi dokumen:`,
        ''
    );
    
    if (description && description.trim()) {
        // Find the document thumbnail
        const thumbnail = document.querySelector(`.document-thumbnail[data-file-name="${fileName}"]`);
        if (thumbnail) {
            // Update the content with manual description
            const newContent = `[Deskripsi Manual] ${description.trim()}`;
            thumbnail.dataset.content = newContent;
            
            // Update the meta info
            const metaElement = thumbnail.querySelector('.document-thumbnail-meta');
            if (metaElement) {
                metaElement.textContent = '1 deskripsi manual';
            }
            
            // Remove the warning and add success indicator
            const warningEl = thumbnail.querySelector('.text-amber-600');
            if (warningEl) {
                warningEl.innerHTML = '✅ Deskripsi ditambahkan';
                warningEl.className = 'text-xs text-green-600 mt-1';
            }
            
            // Remove the add description button
            const addBtn = thumbnail.querySelector('button[onclick*="addManualDescription"]');
            if (addBtn) {
                addBtn.remove();
            }
            
            console.log('✅ PingoChat: Manual description added for', fileName);
            alert('Deskripsi berhasil ditambahkan! Sekarang Anda bisa mengajukan pertanyaan tentang dokumen ini.');
        }
    }
};

// Debug functions - accessible from browser console
window.PingoChatDebug = {
    clearAllStorage: function() {
        console.log('🧹 Debug: Clearing ALL Pingo storage...');
        
        // Get all localStorage keys that start with 'pingo'
        const keys = Object.keys(localStorage).filter(key => key.startsWith('pingo'));
        console.log('🗂️ Debug: Found Pingo storage keys:', keys);
        
        keys.forEach(key => {
            localStorage.removeItem(key);
            console.log('🗑️ Debug: Removed key:', key);
        });
        
        console.log('✅ Debug: All Pingo storage cleared');
        alert('All Pingo storage cleared! Please refresh the page.');
    },
    
    showStorageInfo: function() {
        console.log('📊 Debug: Pingo Storage Information:');
        
        const keys = Object.keys(localStorage).filter(key => key.startsWith('pingo'));
        keys.forEach(key => {
            const data = localStorage.getItem(key);
            const size = (data.length / 1024).toFixed(2);
            console.log(`📦 ${key}: ${size} KB`);
            
            try {
                const parsed = JSON.parse(data);
                if (Array.isArray(parsed)) {
                    console.log(`   📄 Contains ${parsed.length} items`);
                }
            } catch (e) {
                console.log('   📄 Raw data (not JSON)');
            }
        });
        
        // Show total storage usage
        const totalSize = keys.reduce((total, key) => {
            return total + localStorage.getItem(key).length;
        }, 0);
        console.log(`📈 Total Pingo storage: ${(totalSize / 1024).toFixed(2)} KB`);
    },
    
    reloadChat: function() {
        console.log('🔄 Debug: Reloading chat...');
        if (window.pingoChat) {
            window.pingoChat.loadChatHistory();
        } else {
            console.error('❌ Debug: PingoChat not initialized');
        }
    },
    
    showChatHistory: function() {
        console.log('📚 Debug: Current chat history:');
        if (window.pingoChat && window.pingoChat.chatHistory) {
            console.table(window.pingoChat.chatHistory.map((msg, index) => ({
                index,
                role: msg.role,
                hasAttachment: !!msg.attachment,
                contentLength: (msg.content || msg.message || '').length
            })));
        } else {
            console.error('❌ Debug: Chat history not available');
        }
    }
};

console.log('🔧 PingoChat Debug: Debug functions available as window.PingoChatDebug');
console.log('📖 Usage examples:');
console.log('  - PingoChatDebug.clearAllStorage() - Clear all storage');
console.log('  - PingoChatDebug.showStorageInfo() - Show storage info');
console.log('  - PingoChatDebug.reloadChat() - Reload chat history');
console.log('  - PingoChatDebug.showChatHistory() - Show current chat history');

// Initialize chat when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    console.log('🌟 PingoChat: DOM Content Loaded, initializing chat...');
    
    // Check if required elements exist
    const requiredElements = [
        'chat-messages',
        'chat-empty-state', 
        'chat-input-container'
    ];
    
    const missingElements = requiredElements.filter(id => !document.getElementById(id));
    
    if (missingElements.length > 0) {
        console.error('❌ PingoChat: Missing required DOM elements:', missingElements);
        console.error('💡 PingoChat: Make sure these elements exist in your HTML');
    } else {
        console.log('✅ PingoChat: All required DOM elements found');
    }
    
    // Initialize chat
    try {
        window.pingoChat = new PingoChat();
        console.log('🎉 PingoChat: Initialization complete!');
    } catch (error) {
        console.error('💥 PingoChat: Failed to initialize:', error);
    }
});
