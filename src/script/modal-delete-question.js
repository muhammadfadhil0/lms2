/**
 * Modal Delete Question Handler
 * Handles the confirmation modal for deleting questions
 */

class DeleteQuestionModal {
    constructor() {
        this.modal = document.getElementById('deleteQuestionModal');
        this.confirmBtn = document.getElementById('confirmDeleteQuestionBtn');
        this.cancelBtn = document.getElementById('cancelDeleteQuestionBtn');
    this.backdrop = this.modal?.querySelector('el-dialog-backdrop');
    this.panel = this.modal?.querySelector('el-dialog-panel');
        
        this.currentQuestionCard = null;
        this.isProcessing = false;
        
        this.init();
    }
    
    init() {
        if (!this.modal) return;
        
        // Bind event listeners
        this.confirmBtn?.addEventListener('click', () => this.handleConfirm());
        this.cancelBtn?.addEventListener('click', () => this.close());
        this.backdrop?.addEventListener('click', () => this.close());
        
        // Close on Escape key
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && this.isOpen()) {
                this.close();
            }
        });
        
        // Prevent panel clicks from closing modal
        this.panel?.addEventListener('click', (e) => {
            e.stopPropagation();
        });
    }
    
    show(questionCard) {
        if (!this.modal || this.isProcessing) return;
        
        this.currentQuestionCard = questionCard;
        
        // Get question number for better UX
        const questionTitle = questionCard.querySelector('h3')?.textContent || 'soal ini';
        const dialogTitle = this.modal.querySelector('#delete-question-dialog-title');
        const dialogMessage = this.modal.querySelector('.text-gray-500');
        
        if (dialogTitle) {
            dialogTitle.textContent = `Hapus ${questionTitle}`;
        }
        
        if (dialogMessage) {
            dialogMessage.textContent = `Apakah Anda yakin ingin menghapus ${questionTitle.toLowerCase()}? Tindakan ini tidak dapat dibatalkan dan semua data terkait akan ikut terhapus.`;
        }
        
        // Show modal with animation
        this.modal.style.display = 'block';
        this.modal.removeAttribute('aria-hidden');

        // Trigger reflow then remove data-closed for animation
        requestAnimationFrame(()=>{
            this.backdrop?.removeAttribute('data-closed');
            this.panel?.removeAttribute('data-closed');
        });
        
        // Focus management
        setTimeout(() => {
            this.cancelBtn?.focus();
        }, 100);
        
        // Prevent body scroll
        document.body.style.overflow = 'hidden';
    }
    
    close() {
        if (!this.modal) return;
        
        // Reset loading state jika masih processing
        if (this.isProcessing) {
            this.setLoading(false);
        }
        
        // Add closing animation
    this.backdrop?.setAttribute('data-closed','');
    this.panel?.setAttribute('data-closed','');
        
        // Hide modal after animation
        setTimeout(() => {
            this.modal.style.display = 'none';
            this.modal.setAttribute('aria-hidden','true');
            this.currentQuestionCard = null;
            
            // Restore body scroll
            document.body.style.overflow = '';
        }, 200);
    }
    
    isOpen() {
        return this.modal && this.modal.style.display === 'block';
    }
    
    setLoading(loading) {
        if (!this.confirmBtn) return;
        
        const btnText = this.confirmBtn.querySelector('.delete-question-btn-text');
        const btnLoading = this.confirmBtn.querySelector('.delete-question-btn-loading');
        
        this.isProcessing = loading;
        this.confirmBtn.disabled = loading;
        this.cancelBtn.disabled = loading;
        
        if (loading) {
            btnText.textContent = 'Menghapus...';
            btnLoading?.classList.remove('hidden');
        } else {
            btnText.textContent = 'Hapus Soal';
            btnLoading?.classList.add('hidden');
        }
    }
    
    async handleConfirm() {
        if (!this.currentQuestionCard || this.isProcessing) return;
        
        const existingId = this.currentQuestionCard.getAttribute('data-soal-id');
        const totalCards = document.querySelectorAll('.question-card').length;
        
        // Allow deletion of all questions (removed restriction)
        
        this.setLoading(true);
        
        try {
            if (existingId) {
                // Delete from server with timeout
                console.log('Menghapus soal dengan ID:', existingId);
                const formData = new FormData();
                formData.append('soal_id', existingId);
                
                // Tambah timeout 5 detik (lebih pendek)
                const controller = new AbortController();
                const timeoutId = setTimeout(() => controller.abort(), 5000);
                
                const response = await fetch('../logic/delete-question.php', {
                    method: 'POST',
                    body: formData,
                    signal: controller.signal
                });
                
                clearTimeout(timeoutId);
                
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                
                const result = await response.json();
                console.log('Response dari server:', result);
                
                if (result.success) {
                    this.deleteQuestionFromDOM();
                    // Show success notification
                    if (typeof showToast === 'function') {
                        showToast('Soal berhasil dihapus', 'success');
                    }
                } else {
                    throw new Error(result.message || 'Gagal menghapus soal dari server');
                }
            } else {
                // Just remove from DOM (not saved yet)
                console.log('Menghapus soal lokal (belum disimpan)');
                this.deleteQuestionFromDOM();
                // Show success notification
                if (typeof showToast === 'function') {
                    showToast('Soal berhasil dihapus', 'success');
                }
            }
        } catch (error) {
            console.error('Error deleting question:', error);
            if (error.name === 'AbortError') {
                // Pada timeout, hapus dari DOM saja dulu untuk UX yang lebih baik
                console.warn('Timeout server, hapus dari UI dulu');
                alert('Server lambat merespons, soal dihapus dari tampilan. Refresh halaman untuk memastikan.');
                this.deleteQuestionFromDOM();
            } else {
                alert('Gagal menghapus soal: ' + error.message);
                this.setLoading(false);
            }
        }
    }
    
    deleteQuestionFromDOM() {
        if (!this.currentQuestionCard) return;
        
        console.log('Mulai hapus dari DOM...');
        
        // Remove the question card
        this.currentQuestionCard.remove();
        console.log('Question card removed');
        
        // Update UI with error handling
        try {
            if (typeof updateQuestionNumbers === 'function') {
                updateQuestionNumbers();
                console.log('updateQuestionNumbers completed');
            }
        } catch (error) {
            console.error('Error in updateQuestionNumbers:', error);
        }
        
        try {
            if (typeof updateQuestionNavigation === 'function') {
                updateQuestionNavigation();
                console.log('updateQuestionNavigation completed');
            }
        } catch (error) {
            console.error('Error in updateQuestionNavigation:', error);
        }
        
        try {
            if (typeof updateStats === 'function') {
                updateStats();
                console.log('updateStats completed');
            }
        } catch (error) {
            console.error('Error in updateStats:', error);
        }
        
        console.log('Menutup modal...');
        this.close();
    }
}

// Initialize the modal when DOM is ready
let deleteQuestionModal;

document.addEventListener('DOMContentLoaded', function() {
    // Inisialisasi dan pasang ke window setelah DOM siap
    deleteQuestionModal = new DeleteQuestionModal();
    window.deleteQuestionModal = deleteQuestionModal;
});

// Jika script lain mencoba akses sebelum DOM ready, siapkan getter malas
if (!window.deleteQuestionModal) {
    Object.defineProperty(window, 'deleteQuestionModal', {
        configurable: true,
        get() {
            // Coba temukan modal bila belum dibuat
            if (!deleteQuestionModal && document.getElementById('deleteQuestionModal')) {
                deleteQuestionModal = new DeleteQuestionModal();
                // Ubah property menjadi value final
                Object.defineProperty(window, 'deleteQuestionModal', { value: deleteQuestionModal, writable: false });
            }
            return deleteQuestionModal;
        }
    });
}
