// Auto Save Manager untuk ujian
class AutoSaveManager {
    constructor(ujianSiswaId) {
        this.ujianSiswaId = ujianSiswaId;
        // Defensive: jika ujianSiswaId tidak valid, matikan autosave dan beri pesan jelas di console
        if (!this.ujianSiswaId || isNaN(parseInt(this.ujianSiswaId)) || Number(this.ujianSiswaId) <= 0) {
            console.error('AutoSaveManager disabled: invalid ujianSiswaId:', this.ujianSiswaId);
            // Provide a no-op instance surface so callers won't break
            this.saveTimeout = null;
            this.saveQueue = new Map();
            this.isProcessing = false;
            this.saveDelay = 1000;
            this.maxRetries = 3;
            this.statusIndicators = new Map();
            this.initEventListeners = () => {};
            this.loadInitialStatus = async () => { this.showGlobalSaveStatus && this.showGlobalSaveStatus('error'); };
            this.showGlobalSaveStatus = (s) => {
                const container = document.getElementById('answer-status-container');
                if (container) container.innerHTML = `<div class="save-status status-error"><span class="label">Auto-save dinonaktifkan (ujianSiswaId tidak valid)</span></div>`;
            };
            // show error status immediately
            try { this.showGlobalSaveStatus('error'); } catch (e) {}
            return;
        }
        this.saveTimeout = null;
        this.saveQueue = new Map(); // Map untuk queue jawaban yang belum disimpan
        this.isProcessing = false;
        this.saveDelay = 1000; // Delay 1 detik setelah user berhenti mengetik
        this.maxRetries = 3;
        this.statusIndicators = new Map(); // Map untuk status indikator per soal
        
        this.initEventListeners();
        this.loadInitialStatus();
    }
    
    /**
     * Inisialisasi event listeners untuk auto save
     */
    initEventListeners() {
        // Event listener untuk radio button (pilihan ganda)
        document.addEventListener('change', (e) => {
            if (e.target.type === 'radio' && e.target.name.startsWith('soal_')) {
                const soalId = this.extractSoalId(e.target.name);
                const jawaban = e.target.value;
                
                // Show immediate loading status
                this.showGlobalSaveStatus('loading');
                
                this.queueSave(soalId, jawaban);
                this.updateQuestionStatus(soalId, 'saving');
            }
        });
        
        // Event listener untuk textarea (essay) - typing indicator
        document.addEventListener('input', (e) => {
            if (e.target.tagName === 'TEXTAREA' && e.target.name.startsWith('soal_')) {
                const soalId = this.extractSoalId(e.target.name);
                const jawaban = e.target.value;
                
                // Show typing status immediately
                this.showGlobalSaveStatus('loading');
                
                this.queueSave(soalId, jawaban);
                this.updateQuestionStatus(soalId, 'typing');
            }
        });
        
        // Event listener untuk blur pada textarea (save immediately when focus lost)
        document.addEventListener('blur', (e) => {
            if (e.target.tagName === 'TEXTAREA' && e.target.name.startsWith('soal_')) {
                const soalId = this.extractSoalId(e.target.name);
                const jawaban = e.target.value;
                
                if (jawaban.trim()) {
                    this.showGlobalSaveStatus('loading');
                    this.immediateSave(soalId, jawaban);
                }
            }
        });
        
        // Event listener untuk focus - show ready status
        document.addEventListener('focus', (e) => {
            if ((e.target.type === 'radio' || e.target.tagName === 'TEXTAREA') && 
                e.target.name && e.target.name.startsWith('soal_')) {
                // Only show idle if not currently saving
                const currentStatus = document.getElementById('save-status-loading');
                if (currentStatus && currentStatus.classList.contains('hidden')) {
                    this.showGlobalSaveStatus('idle');
                }
            }
        });
    }
    
    /**
     * Extract soal ID dari nama field
     */
    extractSoalId(fieldName) {
        return fieldName.replace('soal_', '');
    }
    
    /**
     * Queue jawaban untuk disimpan dengan delay
     */
    queueSave(soalId, jawaban) {
        // Clear timeout sebelumnya jika ada
        if (this.saveTimeout) {
            clearTimeout(this.saveTimeout);
        }
        
        // Tambahkan ke queue
        this.saveQueue.set(soalId, jawaban);
        
        // Set timeout untuk save
        this.saveTimeout = setTimeout(() => {
            this.processSaveQueue();
        }, this.saveDelay);
    }
    
    /**
     * Save jawaban langsung tanpa delay
     */
    immediateSave(soalId, jawaban) {
        if (this.saveTimeout) {
            clearTimeout(this.saveTimeout);
        }
        
        this.saveQueue.set(soalId, jawaban);
        this.processSaveQueue();
    }
    
    /**
     * Proses queue untuk save jawaban
     */
    async processSaveQueue() {
        if (this.isProcessing || this.saveQueue.size === 0) {
            return;
        }
        
        this.isProcessing = true;
        
        // Ambil semua jawaban dari queue
        const savePromises = [];
        const queueCopy = new Map(this.saveQueue);
        this.saveQueue.clear();
        
        for (const [soalId, jawaban] of queueCopy) {
            savePromises.push(this.saveAnswer(soalId, jawaban));
        }
        
        try {
            await Promise.all(savePromises);
        } catch (error) {
            console.error('Error processing save queue:', error);
        }
        
        this.isProcessing = false;
        
        // Jika ada jawaban baru yang masuk saat processing, proses lagi
        if (this.saveQueue.size > 0) {
            setTimeout(() => this.processSaveQueue(), 100);
        }
    }
    
    /**
     * Save satu jawaban ke server
     */
    async saveAnswer(soalId, jawaban, retryCount = 0) {
        try {
            // Show loading status
            this.updateQuestionStatus(soalId, 'saving');
            this.showGlobalSaveStatus('loading');
            
            const formData = new FormData();
            formData.append('action', 'auto_save');
            formData.append('ujian_siswa_id', this.ujianSiswaId);
            formData.append('soal_id', soalId);
            formData.append('jawaban', jawaban);
            
            const response = await fetch('../logic/auto-save-api.php', {
                method: 'POST',
                body: formData
            });

            // Read response text for better debugging when something goes wrong
            const rawText = await response.text();

            if (!response.ok) {
                console.error(`AutoSave server returned HTTP ${response.status}`, rawText);
                throw new Error(`HTTP error! status: ${response.status} - ${rawText}`);
            }

            let result;
            try {
                result = JSON.parse(rawText);
            } catch (parseErr) {
                console.error('Failed to parse JSON from auto-save response:', parseErr, rawText);
                throw new Error('Invalid JSON response from server');
            }

            if (result.success) {
                this.updateQuestionStatus(soalId, 'saved');
                this.updateQuestionMapIndicator(soalId, true);
                
                // Update global save status
                this.showGlobalSaveStatus('saved');
                
                // Log untuk debugging
                console.log(`Auto-saved answer for soal ${soalId}:`, result);
            } else {
                console.error('AutoSave API returned success=false:', result, 'raw:', rawText);
                throw new Error(result.message || 'Failed to save');
            }
            
        } catch (error) {
            console.error(`Error saving answer for soal ${soalId}:`, error);
            
            // Retry jika belum mencapai max retries
            if (retryCount < this.maxRetries) {
                console.log(`Retrying save for soal ${soalId}, attempt ${retryCount + 1}`);
                setTimeout(() => {
                    this.saveAnswer(soalId, jawaban, retryCount + 1);
                }, 1000 * (retryCount + 1)); // Exponential backoff
            } else {
                this.updateQuestionStatus(soalId, 'error');
                this.updateQuestionMapIndicator(soalId, false);
                this.showGlobalSaveStatus('error');
            }
        }
    }
    
    /**
     * Update status indikator untuk soal tertentu
     */
    updateQuestionStatus(soalId, status) {
        this.statusIndicators.set(soalId, status);
        
        // Dispatch event untuk komponen lain
        document.dispatchEvent(new CustomEvent('questionStatusChanged', {
            detail: { soalId, status }
        }));
    }
    
    /**
     * Update indikator pada question map dengan centang untuk yang tersimpan
     */
    updateQuestionMapIndicator(soalId, isSaved) {
        // Cari nomor soal berdasarkan soal ID
        const questionElement = document.querySelector(`input[name="soal_${soalId}"], textarea[name="soal_${soalId}"]`);
        if (!questionElement) return;
        
        const questionCard = questionElement.closest('.question-card');
        if (!questionCard) return;
        
        const questionNumber = questionCard.dataset.question;
        const mapButton = document.querySelector(`#question-map .q-btn[data-q="${questionNumber}"]`);
        
        if (mapButton) {
            // Remove existing status classes
            mapButton.classList.remove('saved', 'error', 'saving');
            
            // Add appropriate class
            if (isSaved) {
                mapButton.classList.add('saved');
                console.log(`Question ${questionNumber} marked as saved in map`);
            } else {
                mapButton.classList.add('error');
            }
        }
    }
    
    /**
     * Update global save status indicator dengan sistem container tunggal
     */
    showGlobalSaveStatus(status) {
        const container = document.getElementById('answer-status-container');
        if (!container) return;
        
        // Template untuk setiap status
        const templates = {
            idle: {
                className: 'save-status status-idle',
                icon: '',
                label: 'Siap menerima jawaban'
            },
            loading: {
                className: 'save-status status-loading',
                icon: 'spinner',
                label: 'Sedang mengupload jawaban Anda...'
            },
            saved: {
                className: 'save-status status-saved',
                icon: 'checkmark',
                label: 'Jawaban Anda tersimpan'
            },
            error: {
                className: 'save-status status-error',
                icon: 'error-icon',
                label: 'Gagal menyimpan jawaban'
            }
        };
        
        const template = templates[status] || templates.idle;
        
        // Fade out current status
        const currentStatus = container.querySelector('.save-status');
        if (currentStatus) {
            currentStatus.classList.add('fade-out');
            
            setTimeout(() => {
                // Replace content
                container.innerHTML = `
                    <div class="${template.className} fade-in">
                        <span class="icon ${template.icon}"></span>
                        <span class="label">${template.label}</span>
                    </div>
                `;
                
                // Auto hide success message after 3 seconds
                if (status === 'saved') {
                    setTimeout(() => {
                        this.showGlobalSaveStatus('idle');
                    }, 3000);
                }
            }, 150); // Wait for fade out animation
        } else {
            // First time initialization
            container.innerHTML = `
                <div class="${template.className}">
                    <span class="icon ${template.icon}"></span>
                    <span class="label">${template.label}</span>
                </div>
            `;
        }
    }
    
    /**
     * Load initial status untuk semua jawaban
     */
    async loadInitialStatus() {
        try {
            const formData = new FormData();
            formData.append('action', 'get_status');
            formData.append('ujian_siswa_id', this.ujianSiswaId);
            
            const response = await fetch('../logic/auto-save-api.php', {
                method: 'POST',
                body: formData
            });
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const result = await response.json();
            
            if (result.success) {
                // Update status untuk semua soal
                for (const [soalId, statusData] of Object.entries(result.data)) {
                    if (statusData.is_answered) {
                        this.updateQuestionStatus(soalId, 'saved');
                        this.updateQuestionMapIndicator(soalId, true);
                    }
                }
                
                // Set initial global status
                this.showGlobalSaveStatus('idle');
                
                console.log('Initial status loaded:', result.data);
            }
            
        } catch (error) {
            console.error('Error loading initial status:', error);
            // Set fallback status
            this.showGlobalSaveStatus('idle');
        }
    }
    
    /**
     * Force save semua jawaban yang ada
     */
    async forceSaveAll() {
        const allAnswers = new Map();
        
        // Collect all radio button answers
        document.querySelectorAll('input[type="radio"]:checked[name^="soal_"]').forEach(radio => {
            const soalId = this.extractSoalId(radio.name);
            allAnswers.set(soalId, radio.value);
        });
        
        // Collect all textarea answers
        document.querySelectorAll('textarea[name^="soal_"]').forEach(textarea => {
            const soalId = this.extractSoalId(textarea.name);
            if (textarea.value.trim()) {
                allAnswers.set(soalId, textarea.value);
            }
        });
        
        // Save all answers
        const savePromises = [];
        for (const [soalId, jawaban] of allAnswers) {
            savePromises.push(this.saveAnswer(soalId, jawaban));
        }
        
        try {
            await Promise.all(savePromises);
            console.log('All answers force saved successfully');
            return true;
        } catch (error) {
            console.error('Error force saving all answers:', error);
            return false;
        }
    }
    
    /**
     * Get status untuk soal tertentu
     */
    getQuestionStatus(soalId) {
        return this.statusIndicators.get(soalId) || 'unknown';
    }
    
    /**
     * Clear semua jawaban (untuk reset)
     */
    async clearAllAnswers() {
        const formData = new FormData();
        formData.append('action', 'clear_all');
        formData.append('ujian_siswa_id', this.ujianSiswaId);
        
        try {
            const response = await fetch('../logic/auto-save-api.php', {
                method: 'POST',
                body: formData
            });
            
            const result = await response.json();
            return result.success;
        } catch (error) {
            console.error('Error clearing all answers:', error);
            return false;
        }
    }
}

// Export untuk digunakan di file lain
window.AutoSaveManager = AutoSaveManager;
