/**
 * Pingo Chat JavaScript
 * Handles chat functionality for Pingo AI assistant
 */

class PingoChat {
    constructor() {
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
        
        this.initEventListeners();
        this.loadChatHistory();
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
        
        // Auto-resize textarea
        this.chatInput?.addEventListener('input', () => this.autoResize(this.chatInput));
        this.chatInputActive?.addEventListener('input', () => this.autoResize(this.chatInputActive));
        
        // Clear chat button
        this.clearButton?.addEventListener('click', () => this.clearChat());
    }
    
    autoResize(textarea) {
        textarea.style.height = 'auto';
        textarea.style.height = Math.min(textarea.scrollHeight, 120) + 'px';
    }
    
    async loadChatHistory() {
        try {
            const response = await fetch('../pingo/chat-api.php', {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json'
                }
            });
            
            const data = await response.json();
            
            if (data.success && data.messages.length > 0) {
                this.chatHistory = data.messages;
                this.displayChatHistory();
                this.showChatInterface();
            }
        } catch (error) {
            console.error('Error loading chat history:', error);
        }
    }
    
    displayChatHistory() {
        this.chatMessages.innerHTML = '';
        
        this.chatHistory.forEach(message => {
            this.addMessageToChat(message.message, message.role, false);
        });
        
        this.scrollToBottom();
    }
    
    async sendMessage() {
        const activeInput = this.chatInputContainer.style.display === 'none' ? this.chatInput : this.chatInputActive;
        const message = activeInput.value.trim();
        
        if (!message || this.isLoading) return;
        
        // Clear input
        activeInput.value = '';
        this.autoResize(activeInput);
        
        // Show chat interface if it's the first message
        if (this.chatEmptyState.style.display !== 'none') {
            this.showChatInterface();
        }
        
        // Add user message to chat
        this.addMessageToChat(message, 'user');
        
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
            } else {
                this.addMessageToChat('Maaf, terjadi kesalahan: ' + (data.error || 'Unknown error'), 'error');
            }
        } catch (error) {
            this.hideLoading();
            this.addMessageToChat('Maaf, terjadi kesalahan koneksi. Silakan coba lagi.', 'error');
            console.error('Error sending message:', error);
        }
    }
    
    addMessageToChat(message, role, scroll = true) {
        const messageDiv = document.createElement('div');
        messageDiv.className = `chat-message ${role}`;
        
        const timestamp = new Date().toLocaleTimeString('id-ID', {
            hour: '2-digit',
            minute: '2-digit'
        });
        
        if (role === 'user') {
            messageDiv.innerHTML = `
                <div class="message-content user-message">
                    <div class="message-text">${this.escapeHtml(message)}</div>
                    <div class="message-time">${timestamp}</div>
                </div>
                <div class="message-avatar user-avatar">
                    <i class="ti ti-user"></i>
                </div>
            `;
        } else if (role === 'assistant') {
            messageDiv.innerHTML = `
                <div class="message-avatar ai-avatar">
                    <span>AI</span>
                </div>
                <div class="message-content ai-message">
                    <div class="message-text">${this.formatMessage(message)}</div>
                    <div class="message-time">${timestamp}</div>
                </div>
            `;
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
    
    showLoading() {
        this.isLoading = true;
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
        const loadingMessage = document.getElementById('loading-message');
        if (loadingMessage) {
            loadingMessage.remove();
        }
    }
    
    showChatInterface() {
        this.chatEmptyState.style.display = 'none';
        this.chatInputContainer.style.display = 'block';
        this.chatMessages.style.display = 'block';
    }
    
    hideChatInterface() {
        this.chatEmptyState.style.display = 'flex';
        this.chatInputContainer.style.display = 'none';
        this.chatMessages.style.display = 'none';
    }
    
    async clearChat() {
        if (!confirm('Apakah Anda yakin ingin menghapus semua riwayat chat?')) {
            return;
        }
        
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
                this.hideChatInterface();
                
                // Reset input
                if (this.chatInput) this.chatInput.value = '';
                if (this.chatInputActive) this.chatInputActive.value = '';
            } else {
                alert('Gagal menghapus chat: ' + (data.error || 'Unknown error'));
            }
        } catch (error) {
            alert('Terjadi kesalahan saat menghapus chat');
            console.error('Error clearing chat:', error);
        }
    }
    
    scrollToBottom() {
        setTimeout(() => {
            this.chatMessages.scrollTop = this.chatMessages.scrollHeight;
        }, 100);
    }
    
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    formatMessage(message) {
        // Enhanced markdown formatting for AI messages
        let formatted = this.escapeHtml(message);
        
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
}

// Initialize chat when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    new PingoChat();
});
