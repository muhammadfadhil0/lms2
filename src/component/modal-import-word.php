<!-- Modal Import Word -->
<dialog id="import-word" aria-labelledby="dialog-title" class="fixed inset-0 size-auto max-h-none max-w-none overflow-y-auto bg-transparent backdrop:bg-transparent z-[9999]" style="display: none;" aria-hidden="true">
  <div class="modal-backdrop fixed inset-0 bg-black/20 transition-opacity duration-300 opacity-0"></div>

  <div tabindex="0" class="modal-container flex min-h-full items-end justify-center p-4 text-center focus:outline-none sm:items-center sm:p-0 relative z-10">
    <div class="modal-panel relative transform overflow-hidden rounded-xl bg-white shadow-2xl transition-all duration-300 ease-out opacity-0 scale-95 translate-y-4 sm:my-8 sm:w-full sm:max-w-2xl sm:translate-y-0">

      <!-- Header -->
      <div class="bg-gradient-to-r from-orange-500 to-orange-600 px-6 py-4">
        <div class="flex items-center">
          <div class="flex size-10 shrink-0 items-center justify-center rounded-full bg-white/20">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="size-5 text-white">
              <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" stroke-linecap="round" stroke-linejoin="round" />
              <polyline points="14,2 14,8 20,8" stroke-linecap="round" stroke-linejoin="round" />
              <line x1="16" y1="13" x2="8" y2="13" stroke-linecap="round" stroke-linejoin="round" />
              <line x1="16" y1="17" x2="8" y2="17" stroke-linecap="round" stroke-linejoin="round" />
              <polyline points="10,9 9,9 8,9" stroke-linecap="round" stroke-linejoin="round" />
            </svg>
          </div>
          <div class="mt-4 text-center sm:mt-0 sm:ml-5 sm:text-left">
            <h3 id="dialog-title" class="text-lg font-semibold text-white">Import Soal dari Word</h3>
            <div class="">
              <p class="text-base text-white">Import soal secara otomatis dari dokumen Word Anda.</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Content -->
      <div class="px-6 py-6">
        <div class="space-y-6">

          <!-- Download Template Section -->
          <div class="bg-gradient-to-r from-orange-50 to-amber-50 border border-orange-200 rounded-lg p-4">
            <div class="flex items-start space-x-3">
              <div class="flex-shrink-0">
                <div class="flex size-8 items-center justify-center rounded-full bg-orange-100">
                  <svg class="size-4 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                  </svg>
                </div>
              </div>
              <div class="flex-1">
                <h4 class="text-sm font-semibold text-start text-orange-800 mb-1">Download Template</h4>
                <p class="text-sm text-orange-700 text-start mb-3">
                  Unduh template Word untuk format soal yang benar.
                </p>
                <a href="../../assets/templates/template soal.docx" 
                   download="Template Soal.docx"
                   style="text-align: left; display: flex; align-items: center; justify-content: flex-start;"
                   class="px-4 py-2 bg-orange-600 text-white rounded-lg hover:bg-orange-700 focus:outline-none focus:ring-2 focus:ring-orange-500 focus:ring-offset-2 transition-colors text-sm font-medium shadow-sm w-full">
                  <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                  </svg>
                  Download Template
                </a>
              </div>
            </div>
          </div>

          <!-- Upload File Section -->
          <div>
            <label class="block text-sm font-semibold text-gray-700 mb-3">
              <span class="flex items-center">
                <svg class="w-4 h-4 mr-1.5 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                Upload File Soal
              </span>
            </label>
            
            <div class="border-2 border-dashed border-gray-300 rounded-lg p-6 text-center hover:border-orange-400 transition-colors bg-gray-50 hover:bg-orange-50">
              <div class="space-y-4">
                <div class="mx-auto w-12 h-12 bg-orange-100 rounded-full flex items-center justify-center">
                  <svg class="w-6 h-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                  </svg>
                </div>
                
                <div>
                  <h4 class="text-sm font-medium text-gray-900 mb-1">Upload File Word (.docx)</h4>
                  <p class="text-sm text-gray-500">Pilih file Word yang sudah berisi soal-soal</p>
                </div>
                
                <div class="space-y-3">
                  <input type="file" id="word-file-input" accept=".docx" class="hidden" onchange="handleFileSelect(this)">
                  <button type="button" onclick="document.getElementById('word-file-input').click()" 
                          class="inline-flex items-center px-4 py-3 bg-white border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-orange-500 transition-colors font-medium shadow-sm">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                    </svg>
                    Pilih File
                  </button>
                  
                  <!-- Selected file display -->
                  <div id="selected-file" class="hidden">
                    <div class="flex items-center justify-center space-x-2 p-3 bg-orange-50 border border-orange-200 rounded-lg">
                      <svg class="w-5 h-5 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                      </svg>
                      <span id="file-name" class="text-sm font-medium text-orange-800"></span>
                      <button type="button" onclick="clearSelectedFile()" class="text-red-500 hover:text-red-700 ml-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                      </button>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Instructions -->
          <div class="">
            <div class="flex items-center justify-between mb-3">
              <h4 class="text-sm font-semibold text-gray-700">
                <span class="flex items-center">
                  <svg class="w-4 h-4 mr-1.5 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                  </svg>
                  Petunjuk Format
                </span>
              </h4>
            </div>
            
            <div class="">
              <div class="">
                <ul class="text-xs text-orange-700 space-y-3">
                  <li>
                    <div class="flex items-center">
                      <svg class="w-3 h-3 mr-1.5 text-orange-500" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                      </svg>
                      <span class="font-medium">File harus berformat .docx</span>
                    </div>
                    <p class="text-gray-600 text-xs ml-4.5 text-start mt-1">Pastikan file yang Anda upload adalah dokumen Microsoft Word dengan ekstensi .docx, bukan format lain seperti .doc atau .pdf</p>
                  </li>
                  <li>
                    <div class="flex items-center">
                      <svg class="w-3 h-3 mr-1.5 text-orange-500" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                      </svg>
                      <span class="font-medium">Maksimal ukuran file 10MB</span>
                    </div>
                    <p class="text-gray-600 text-xs ml-4.5 text-start mt-1">Ukuran file yang diupload tidak boleh melebihi 10MB. Jika file terlalu besar, coba kompres atau kurangi konten yang tidak perlu</p>
                  </li>
                  <li>
                    <div class="flex items-center">
                      <svg class="w-3 h-3 mr-1.5 text-orange-500" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                      </svg>
                      <span class="font-medium">Gunakan format tabel seperti contoh</span>
                    </div>
                    <p class="text-gray-600 text-xs ml-4.5 text-start mt-1">Ikuti format penulisan yang telah ditentukan pada template. Setiap soal harus mengikuti pola nomor, pertanyaan, pilihan jawaban, dan kunci jawaban</p>
                  </li>
                </ul>
              </div>
            </div>
          </div>

        </div>
      </div>

      <!-- Footer -->
      <div class="bg-gray-50 px-6 py-4 border-t border-gray-200 flex flex-col-reverse sm:flex-row sm:justify-end sm:space-x-3 space-y-3 space-y-reverse sm:space-y-0">
        <button type="button" onclick="closeImportModal()" class="inline-flex justify-center px-6 py-2.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-orange-500 focus:ring-offset-2 transition-colors">
          Batal
        </button>
        <button type="button" id="import-btn" onclick="processImport()" disabled
                class="inline-flex justify-center items-center px-6 py-2.5 text-sm font-medium text-white bg-gradient-to-r from-orange-500 to-orange-600 rounded-lg hover:from-orange-600 hover:to-orange-700 focus:outline-none focus:ring-2 focus:ring-orange-500 focus:ring-offset-2 shadow-sm transition-all disabled:from-gray-400 disabled:to-gray-400 disabled:cursor-not-allowed">
          <svg id="import-icon" class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
          </svg>
          <span id="import-text">Import Soal</span>
        </button>
      </div>

    </div>
  </div>
</dialog>

<style>
/* Modal animations matching PingoAI style */
#import-word .modal-backdrop {
    opacity: 0;
    transition: opacity 300ms ease-out;
}

#import-word.modal-show .modal-backdrop {
    opacity: 1;
}

#import-word .modal-panel {
    opacity: 0;
    transform: scale(0.95) translateY(16px);
    transition: all 300ms ease-out;
}

#import-word.modal-show .modal-panel {
    opacity: 1;
    transform: scale(1) translateY(0);
}

/* Responsive positioning */
@media (max-width: 640px) {
    #import-word .modal-container {
        align-items: flex-end;
        padding: 1rem;
    }
    
    #import-word.modal-show .modal-panel {
        transform: scale(1) translateY(0);
    }
}

/* Loading state animation */
@keyframes spin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}

.animate-spin {
    animation: spin 1s linear infinite;
}

/* Custom scrollbars */
#import-word {
    scrollbar-width: thin;
    scrollbar-color: rgba(156, 163, 175, 0.3) transparent;
}

#import-word::-webkit-scrollbar {
    width: 8px;
}

#import-word::-webkit-scrollbar-track {
    background: transparent;
}

#import-word::-webkit-scrollbar-thumb {
    background: rgba(156, 163, 175, 0.3);
    border-radius: 4px;
}

#import-word::-webkit-scrollbar-thumb:hover {
    background: rgba(156, 163, 175, 0.5);
}
</style>

<script>
// Modal Import Word Functions - matching PingoAI style
function openImportModal() {
    const modal = document.getElementById('import-word');
    if (modal) {
        // Show modal
        modal.style.display = 'block';
        modal.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
        
        // Trigger enter animation
        requestAnimationFrame(() => {
            modal.classList.add('modal-show');
        });
    }
}

function closeImportModal() {
    const modal = document.getElementById('import-word');
    if (modal) {
        // Trigger exit animation
        modal.classList.remove('modal-show');
        
        // Hide modal after animation completes
        setTimeout(() => {
            modal.style.display = 'none';
            modal.setAttribute('aria-hidden', 'true');
            document.body.style.overflow = '';
            clearSelectedFile();
        }, 300);
    }
}

function handleFileSelect(input) {
    const file = input.files[0];
    const selectedFileDiv = document.getElementById('selected-file');
    const fileNameSpan = document.getElementById('file-name');
    const importBtn = document.getElementById('import-btn');
    
    if (file) {
        // Check file type
        if (!file.name.toLowerCase().endsWith('.docx')) {
            showToast('File harus berformat .docx', 'error');
            input.value = '';
            return;
        }
        
        // Check file size (max 10MB)
        if (file.size > 10 * 1024 * 1024) {
            showToast('File terlalu besar. Maksimal 10MB', 'error');
            input.value = '';
            return;
        }
        
        fileNameSpan.textContent = file.name;
        selectedFileDiv.classList.remove('hidden');
        importBtn.disabled = false;
    } else {
        clearSelectedFile();
    }
}

function clearSelectedFile() {
    const input = document.getElementById('word-file-input');
    const selectedFileDiv = document.getElementById('selected-file');
    const importBtn = document.getElementById('import-btn');
    
    input.value = '';
    selectedFileDiv.classList.add('hidden');
    importBtn.disabled = true;
}

function processImport() {
    const fileInput = document.getElementById('word-file-input');
    const file = fileInput.files[0];
    
    if (!file) {
        showToast('Pilih file terlebih dahulu', 'error');
        return;
    }
    
    // Get ujian_id from the page
    const ujianIdInput = document.getElementById('ujian_id');
    if (!ujianIdInput) {
        showToast('ID ujian tidak ditemukan', 'error');
        return;
    }
    
    const ujianId = ujianIdInput.value;
    
    // Show loading state
    const importBtn = document.getElementById('import-btn');
    const importIcon = document.getElementById('import-icon');
    const importText = document.getElementById('import-text');
    
    importBtn.disabled = true;
    importIcon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />';
    importIcon.classList.add('animate-spin');
    importText.textContent = 'Memproses...';
    
    // Create FormData
    const formData = new FormData();
    formData.append('word_file', file);
    formData.append('ujian_id', ujianId);
    
    // Send to API
    fetch('../api/import-word-soal.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        // Debug: log raw response
        return response.text().then(text => {
            console.log('Raw response:', text);
            try {
                return JSON.parse(text);
            } catch (e) {
                console.error('JSON parse error:', e);
                console.error('Response text:', text);
                throw new Error('Invalid JSON response from server');
            }
        });
    })
    .then(data => {
        if (data.success) {
            showToast(`Berhasil mengimport ${data.imported_count} soal`, 'success');
            closeImportModal();
            
            // Refresh the page to show imported questions
            setTimeout(() => {
                location.reload();
            }, 1500);
        } else {
            showToast(data.message || 'Gagal mengimport soal', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('Terjadi kesalahan saat mengimport', 'error');
    })
    .finally(() => {
        // Reset button state
        importBtn.disabled = false;
        importIcon.classList.remove('animate-spin');
        importIcon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />';
        importText.textContent = 'Import Soal';
    });
}

// Close modal when clicking backdrop
document.addEventListener('click', function(e) {
    const modal = document.getElementById('import-word');
    
    if (modal && modal.style.display !== 'none' && e.target.classList.contains('modal-backdrop')) {
        closeImportModal();
    }
});

// Close modal with Escape key
document.addEventListener('keydown', function(e) {
    const modal = document.getElementById('import-word');
    if (e.key === 'Escape' && modal && modal.style.display !== 'none') {
        closeImportModal();
    }
});

// Prevent closing modal when clicking inside modal panel
document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('import-word');
    if (modal) {
        const modalPanel = modal.querySelector('.modal-panel');
        if (modalPanel) {
            modalPanel.addEventListener('click', function(e) {
                e.stopPropagation();
            });
        }
        
        // Also handle backdrop clicks
        const backdrop = modal.querySelector('.modal-backdrop');
        if (backdrop) {
            backdrop.addEventListener('click', closeImportModal);
        }
    }
});
</script>