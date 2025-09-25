<?php
/**
 * API Key Switcher Component
 * Komponen untuk memilih API key yang akan digunakan per halaman
 */

// Ambil halaman saat ini dari parameter atau session
$currentPageForApi = $currentPage ?? 'default';
?>

<div class="api-switcher-container bg-white rounded-lg shadow-sm border border-gray-200 p-4 mb-4">
    <div class="flex items-center justify-between mb-3">
        <div class="flex items-center space-x-2">
            <i class="ti ti-cpu text-orange"></i>
            <h3 class="text-sm font-medium text-gray-800">AI Service</h3>
        </div>
        <div class="flex items-center space-x-2">
            <span id="api-status-indicator" class="w-2 h-2 bg-gray-400 rounded-full"></span>
            <span id="api-status-text" class="text-xs text-gray-500">Checking...</span>
        </div>
    </div>
    
    <div class="space-y-3">
        <!-- API Key Selector -->
        <div>
            <label class="block text-xs font-medium text-gray-700 mb-1">
                Pilih API Service untuk halaman ini:
            </label>
            <select id="api-key-selector" class="w-full text-sm border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-orange-500 focus:border-orange-500">
                <option value="">Loading...</option>
            </select>
        </div>
        
        <!-- API Info Display -->
        <div id="api-info-display" class="hidden">
            <div class="bg-gray-50 rounded-lg p-3 space-y-2">
                <div class="flex items-center justify-between">
                    <span class="text-xs text-gray-600">Service:</span>
                    <span id="api-service-name" class="text-xs font-medium text-gray-800"></span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-xs text-gray-600">Model:</span>
                    <span id="api-model-name" class="text-xs font-medium text-gray-800"></span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-xs text-gray-600">Status:</span>
                    <span id="api-key-status" class="text-xs font-medium"></span>
                </div>
            </div>
        </div>
        
        <!-- Quick Actions -->
        <div class="flex space-x-2">
            <button id="test-api-connection" class="flex-1 text-xs px-3 py-2 bg-blue-50 text-blue-700 rounded-lg hover:bg-blue-100 transition-colors">
                <i class="ti ti-test-pipe mr-1"></i>
                Test Connection
            </button>
            <button id="refresh-api-list" class="px-3 py-2 bg-gray-50 text-gray-700 rounded-lg hover:bg-gray-100 transition-colors">
                <i class="ti ti-refresh text-xs"></i>
            </button>
        </div>
    </div>
</div>

<style>
.api-switcher-container {
    font-family: system-ui, -apple-system, sans-serif;
}

#api-key-selector {
    transition: all 0.2s ease;
}

#api-key-selector:focus {
    outline: none;
    box-shadow: 0 0 0 3px rgba(255, 99, 71, 0.1);
}

.api-status-success {
    background-color: #10b981 !important;
}

.api-status-error {
    background-color: #ef4444 !important;
}

.api-status-warning {
    background-color: #f59e0b !important;
}

.api-status-pending {
    background-color: #6b7280 !important;
}
</style>

<script>
class ApiSwitcher {
    constructor(pageName) {
        this.pageName = pageName;
        this.currentApiKey = null;
        this.init();
    }
    
    init() {
        this.bindEvents();
        this.loadAvailableApiKeys();
        this.loadCurrentSelection();
    }
    
    bindEvents() {
        const selector = document.getElementById('api-key-selector');
        const testBtn = document.getElementById('test-api-connection');
        const refreshBtn = document.getElementById('refresh-api-list');
        
        if (selector) {
            selector.addEventListener('change', (e) => this.handleApiKeyChange(e.target.value));
        }
        
        if (testBtn) {
            testBtn.addEventListener('click', () => this.testConnection());
        }
        
        if (refreshBtn) {
            refreshBtn.addEventListener('click', () => this.loadAvailableApiKeys());
        }
    }
    
    async loadAvailableApiKeys() {
        try {
            const response = await fetch('../logic/api-switcher-endpoint.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=get_available_keys`
            });
            
            const result = await response.json();
            
            if (result.success) {
                this.populateApiKeySelector(result.data);
            } else {
                this.showError('Gagal memuat API keys: ' + result.message);
            }
        } catch (error) {
            console.error('Error loading API keys:', error);
            this.showError('Network error saat memuat API keys');
        }
    }
    
    populateApiKeySelector(apiKeys) {
        const selector = document.getElementById('api-key-selector');
        selector.innerHTML = '<option value="">-- Pilih API Service --</option>';
        
        apiKeys.forEach(key => {
            const option = document.createElement('option');
            option.value = key.id;
            option.textContent = `${key.service_label} (${key.service_name})`;
            option.dataset.serviceName = key.service_name;
            option.dataset.modelName = key.model_name || '';
            option.dataset.isActive = key.is_active;
            option.dataset.testStatus = key.test_status || 'pending';
            
            if (!key.is_active) {
                option.disabled = true;
                option.textContent += ' - Nonaktif';
            }
            
            selector.appendChild(option);
        });
    }
    
    async loadCurrentSelection() {
        try {
            const response = await fetch('../logic/api-switcher-endpoint.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=get_current_selection&page=${this.pageName}`
            });
            
            const result = await response.json();
            
            if (result.success && result.data.api_key_id) {
                const selector = document.getElementById('api-key-selector');
                selector.value = result.data.api_key_id;
                this.updateApiInfo();
            }
        } catch (error) {
            console.error('Error loading current selection:', error);
        }
    }
    
    async handleApiKeyChange(apiKeyId) {
        if (!apiKeyId) {
            this.hideApiInfo();
            return;
        }
        
        try {
            const response = await fetch('../logic/api-switcher-endpoint.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=set_page_api_key&page=${this.pageName}&api_key_id=${apiKeyId}`
            });
            
            const result = await response.json();
            
            if (result.success) {
                this.showSuccess('API service berhasil diubah');
                this.updateApiInfo();
            } else {
                this.showError('Gagal mengubah API service: ' + result.message);
            }
        } catch (error) {
            console.error('Error changing API key:', error);
            this.showError('Network error saat mengubah API service');
        }
    }
    
    updateApiInfo() {
        const selector = document.getElementById('api-key-selector');
        const selectedOption = selector.options[selector.selectedIndex];
        
        if (!selectedOption || !selectedOption.value) {
            this.hideApiInfo();
            return;
        }
        
        const infoDisplay = document.getElementById('api-info-display');
        const serviceName = document.getElementById('api-service-name');
        const modelName = document.getElementById('api-model-name');
        const keyStatus = document.getElementById('api-key-status');
        const statusIndicator = document.getElementById('api-status-indicator');
        const statusText = document.getElementById('api-status-text');
        
        serviceName.textContent = selectedOption.dataset.serviceName;
        modelName.textContent = selectedOption.dataset.modelName || 'Default';
        
        const testStatus = selectedOption.dataset.testStatus;
        const isActive = selectedOption.dataset.isActive === '1';
        
        if (!isActive) {
            keyStatus.textContent = 'Nonaktif';
            keyStatus.className = 'text-xs font-medium text-red-600';
            statusIndicator.className = 'w-2 h-2 rounded-full api-status-error';
            statusText.textContent = 'Nonaktif';
        } else if (testStatus === 'success') {
            keyStatus.textContent = 'Aktif & Tested';
            keyStatus.className = 'text-xs font-medium text-green-600';
            statusIndicator.className = 'w-2 h-2 rounded-full api-status-success';
            statusText.textContent = 'Ready';
        } else if (testStatus === 'failed') {
            keyStatus.textContent = 'Test Failed';
            keyStatus.className = 'text-xs font-medium text-red-600';
            statusIndicator.className = 'w-2 h-2 rounded-full api-status-error';
            statusText.textContent = 'Error';
        } else {
            keyStatus.textContent = 'Belum di-test';
            keyStatus.className = 'text-xs font-medium text-yellow-600';
            statusIndicator.className = 'w-2 h-2 rounded-full api-status-warning';
            statusText.textContent = 'Untested';
        }
        
        infoDisplay.classList.remove('hidden');
    }
    
    hideApiInfo() {
        const infoDisplay = document.getElementById('api-info-display');
        const statusIndicator = document.getElementById('api-status-indicator');
        const statusText = document.getElementById('api-status-text');
        
        infoDisplay.classList.add('hidden');
        statusIndicator.className = 'w-2 h-2 rounded-full api-status-pending';
        statusText.textContent = 'No Service';
    }
    
    async testConnection() {
        const selector = document.getElementById('api-key-selector');
        const apiKeyId = selector.value;
        
        if (!apiKeyId) {
            this.showError('Pilih API service terlebih dahulu');
            return;
        }
        
        const testBtn = document.getElementById('test-api-connection');
        const originalText = testBtn.innerHTML;
        testBtn.innerHTML = '<i class="ti ti-loader animate-spin mr-1"></i>Testing...';
        testBtn.disabled = true;
        
        try {
            const response = await fetch('../logic/api-keys-api.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=test&id=${apiKeyId}`
            });
            
            const result = await response.json();
            
            if (result.success) {
                this.showSuccess('✅ ' + result.message);
                // Refresh the selector to get updated test status
                setTimeout(() => {
                    this.loadAvailableApiKeys().then(() => {
                        selector.value = apiKeyId;
                        this.updateApiInfo();
                    });
                }, 1000);
            } else {
                this.showError('❌ ' + result.message);
            }
        } catch (error) {
            console.error('Error testing connection:', error);
            this.showError('Network error saat test koneksi');
        } finally {
            testBtn.innerHTML = originalText;
            testBtn.disabled = false;
        }
    }
    
    showSuccess(message) {
        this.showToast(message, 'success');
    }
    
    showError(message) {
        this.showToast(message, 'error');
    }
    
    showToast(message, type = 'info') {
        // Try to use existing toast function if available
        if (typeof showToast === 'function') {
            showToast(message, type);
            return;
        }
        
        // Fallback toast implementation
        const toast = document.createElement('div');
        toast.className = `fixed top-4 right-4 px-4 py-2 rounded-lg text-white z-50 ${type === 'success' ? 'bg-green-600' : type === 'error' ? 'bg-red-600' : 'bg-blue-600'}`;
        toast.textContent = message;
        document.body.appendChild(toast);
        
        setTimeout(() => toast.remove(), 3000);
    }
    
    // Method to get current selected API key for external use
    getCurrentApiKey() {
        const selector = document.getElementById('api-key-selector');
        return selector.value || null;
    }
}

// Initialize API switcher when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    // Get page name from global variable or default
    const pageName = window.currentPageForApi || '<?= $currentPageForApi ?>';
    window.apiSwitcher = new ApiSwitcher(pageName);
});
</script>
