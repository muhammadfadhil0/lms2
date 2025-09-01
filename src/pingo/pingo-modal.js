/**
 * PingoAI Modal Script
 * Script untuk menangani modal PingoAI dan generate soal otomatis
 */

document.addEventListener('DOMContentLoaded', function() {
    // Elements
    const pingoModal = document.getElementById('add-soal-ai');
    const generateBtn = document.getElementById('generate-questions-btn');
    const closeBtn = document.querySelector('#add-soal-ai button[command="close"]');
    const backdrop = pingoModal; // Use the modal itself for backdrop clicking
    
    // Form elements
    const questionCountInput = document.querySelector('#add-soal-ai input[type="number"]');
    const questionTypeSelect = document.getElementById('tipe-soal');
    const answerOptionsDiv = document.getElementById('pilihan-jawaban');
    const answerOptionsSelect = answerOptionsDiv ? answerOptionsDiv.querySelector('select') : null;
    const difficultySelect = document.getElementById('tingkat-kesulitan');
    const promptTextarea = document.getElementById('ai-prompt');
    
    // Toggle elements
    const toggleButton = document.getElementById('toggle-prompt-details');
    const toggleText = document.getElementById('toggle-text');
    const toggleIcon = document.getElementById('toggle-icon');
    const promptDetails = document.getElementById('prompt-details');
    
    // Get exam data
    const ujianId = document.getElementById('ujian_id');
    const ujianIdValue = ujianId ? ujianId.value : null;
    const isAutoScore = ujianId ? ujianId.getAttribute('data-autoscore') === '1' : false;
    
    // Debug: Log ujian info on load
    console.log('PingoAI Debug - ujianId element:', ujianId);
    console.log('PingoAI Debug - ujianIdValue:', ujianIdValue);
    console.log('PingoAI Debug - isAutoScore:', isAutoScore);
    
    const examNameElement = document.querySelector('[data-exam-name]') || 
                           document.querySelector('h1, h2, h3').textContent.includes('Ujian') ? 
                           document.querySelector('h1, h2, h3') : null;
    const examName = examNameElement ? examNameElement.textContent.replace('Buat Soal Ujian', '').trim() : 'Ujian';
    
    // Loading backdrop element
    let loadingBackdrop = null;
    
    // Initialize event listeners
    if (generateBtn) {
        generateBtn.addEventListener('click', handleGenerateQuestions);
        console.log('PingoAI Debug - Generate button event listener attached');
    } else {
        console.error('PingoAI Debug - Generate button not found!');
    }
    
    if (closeBtn) {
        closeBtn.addEventListener('click', closeModal);
    }
    
    // Toggle button event listener
    if (toggleButton) {
        toggleButton.addEventListener('click', function(event) {
            event.preventDefault();
            event.stopPropagation();
            console.log('PingoAI Debug - Toggle button clicked');
            togglePromptDetails();
        });
        console.log('PingoAI Debug - Toggle button event listener attached');
    } else {
        console.error('PingoAI Debug - Toggle button not found!');
    }
    
    // Form change listeners
    if (questionCountInput) {
        questionCountInput.addEventListener('input', updatePromptPreview);
    }
    
    if (questionTypeSelect) {
        questionTypeSelect.addEventListener('change', handleQuestionTypeChange);
        questionTypeSelect.addEventListener('change', updatePromptPreview);
    }
    
    if (difficultySelect) {
        difficultySelect.addEventListener('change', updatePromptPreview);
    }
    
    if (answerOptionsSelect) {
        answerOptionsSelect.addEventListener('change', updatePromptPreview);
    }
    
    // Modal backdrop click handler
    if (backdrop) {
        backdrop.addEventListener('click', function(e) {
            if (e.target === backdrop) {
                closeModal();
            }
        });
    }
    
    // Escape key handler
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && pingoModal && pingoModal.style.display === 'block') {
            closeModal();
        }
    });
    
    // Initialize on load
    handleQuestionTypeChange();
    updatePromptPreview();
    checkAutoScoreMode();
    
    /**
     * Check if auto score mode is active and restrict question types
     */
    function checkAutoScoreMode() {
        const autoScoreNotice = document.getElementById('auto-score-notice');
        
        if (isAutoScore) {
            // Force multiple choice and disable other options
            if (questionTypeSelect) {
                questionTypeSelect.value = 'multiple_choice';
                const essayOption = questionTypeSelect.querySelector('option[value="essay"]');
                if (essayOption) {
                    essayOption.disabled = true;
                    essayOption.style.display = 'none';
                }
                questionTypeSelect.disabled = true;
                questionTypeSelect.classList.add('opacity-50', 'cursor-not-allowed');
            }
            
            // Show notice
            if (autoScoreNotice) {
                autoScoreNotice.classList.remove('hidden');
            }
        } else {
            // Enable all options
            if (questionTypeSelect) {
                const essayOption = questionTypeSelect.querySelector('option[value="essay"]');
                if (essayOption) {
                    essayOption.disabled = false;
                    essayOption.style.display = 'block';
                }
                questionTypeSelect.disabled = false;
                questionTypeSelect.classList.remove('opacity-50', 'cursor-not-allowed');
            }
            
            // Hide notice
            if (autoScoreNotice) {
                autoScoreNotice.classList.add('hidden');
            }
        }
    }
    
    /**
     * Open modal
     */
    function openModal() {
        // Check auto score mode on each open
        checkAutoScoreMode();
        updatePromptPreview();
        
        // Show modal
        pingoModal.style.display = 'block';
        pingoModal.setAttribute('aria-hidden', 'false');
        pingoModal.setAttribute('open', '');
        
        // Get animation elements
        const backdrop = pingoModal.querySelector('.modal-backdrop');
        const panel = pingoModal.querySelector('.modal-panel');
        
        // Force reflow
        pingoModal.offsetHeight;
        
        // Add show classes with animation
        if (backdrop) {
            backdrop.classList.remove('opacity-0');
            backdrop.classList.add('opacity-100');
        }
        
        if (panel) {
            panel.classList.remove('opacity-0', 'scale-95', 'translate-y-4');
            panel.classList.add('opacity-100', 'scale-100', 'translate-y-0');
        }
        
        // Focus management
        const firstFocusable = pingoModal.querySelector('input, select, textarea, button');
        if (firstFocusable) {
            firstFocusable.focus();
        }
    }
    
    /**
     * Close modal
     */
    function closeModal() {
        const backdrop = pingoModal.querySelector('.modal-backdrop');
        const panel = pingoModal.querySelector('.modal-panel');
        
        // Add hide classes with animation
        if (backdrop) {
            backdrop.classList.remove('opacity-100');
            backdrop.classList.add('opacity-0');
        }
        
        if (panel) {
            panel.classList.remove('opacity-100', 'scale-100', 'translate-y-0');
            panel.classList.add('opacity-0', 'scale-95', 'translate-y-4');
        }
        
        // Wait for animation to complete before hiding
        setTimeout(() => {
            pingoModal.style.display = 'none';
            pingoModal.setAttribute('aria-hidden', 'true');
            pingoModal.removeAttribute('open');
            resetForm();
        }, 150);
    }
    
    /**
     * Update prompt preview
     */
    function updatePromptPreview() {
        if (!questionCountInput || !questionTypeSelect || !difficultySelect) {
            return;
        }
        
        const count = questionCountInput.value || 5;
        const questionType = questionTypeSelect.value;
        const difficulty = difficultySelect.value;
        const answerOptions = answerOptionsSelect ? answerOptionsSelect.value : 4;
        
        // Get exam data for context
        const examData = getExamData();
        
        // Update exam data display
        const examDataDiv = document.getElementById('exam-data');
        if (examDataDiv) {
            examDataDiv.textContent = JSON.stringify(examData, null, 2);
        }
        
        // Build detailed prompt
        const promptData = {
            ujian_id: ujianIdValue,
            jumlah_soal: count,
            tipe_soal: questionType,
            tingkat_kesulitan: difficulty,
            pilihan_jawaban: questionType === 'multiple_choice' ? answerOptions : null,
            auto_score: isAutoScore,
            exam_context: examData
        };
        
        const fullPrompt = buildDetailedPrompt(promptData);
        
        // Update prompt display
        const promptTextarea = document.getElementById('ai-prompt');
        if (promptTextarea) {
            promptTextarea.value = fullPrompt;
        }
        
        // Update expected response format
        const expectedResponse = document.getElementById('expected-response');
        if (expectedResponse) {
            expectedResponse.value = getExpectedResponseFormat(questionType, answerOptions);
        }
    }
    
    /**
     * Get exam data from current page
     */
    function getExamData() {
        const examNameEl = document.getElementById('exam-name-display');
        const examClassEl = document.getElementById('exam-class-display');
        const examSubjectEl = document.getElementById('exam-subject-display');
        const examTopicEl = document.getElementById('exam-topic-display');
        const examDescEl = document.getElementById('exam-description-display');
        
        return {
            nama_ujian: examNameEl ? examNameEl.textContent.trim() : 'Ujian',
            kelas: examClassEl ? examClassEl.textContent.trim() : '',
            mata_pelajaran: examSubjectEl ? examSubjectEl.textContent.trim() : '',
            materi_topik: examTopicEl ? examTopicEl.textContent.trim() : '',
            deskripsi: examDescEl ? examDescEl.textContent.trim() : '',
            auto_score_aktif: isAutoScore,
            ujian_id: ujianIdValue
        };
    }
    
    /**
     * Build detailed prompt for AI
     */
    function buildDetailedPrompt(data) {
        let prompt = `Anda adalah asisten AI yang ahli dalam membuat soal ujian pendidikan.

KONTEKS UJIAN:
- ID Ujian: ${data.ujian_id}
- Nama Ujian: ${data.exam_context.nama_ujian}
- Mata Pelajaran: ${data.exam_context.mata_pelajaran}
- Kelas: ${data.exam_context.kelas}
- Materi/Topik: ${data.exam_context.materi_topik || 'Umum'}
- Deskripsi: ${data.exam_context.deskripsi || 'Tidak ada deskripsi khusus'}
- Mode Auto Score: ${data.auto_score ? 'AKTIF (hanya pilihan ganda)' : 'TIDAK AKTIF'}

INSTRUKSI PEMBUATAN SOAL:
1. Buat ${data.jumlah_soal} soal ${data.tipe_soal === 'multiple_choice' ? 'PILIHAN GANDA' : 'ESSAY'}
2. Tingkat kesulitan: ${data.tingkat_kesulitan.toUpperCase()}
${data.tipe_soal === 'multiple_choice' ? `3. Setiap soal memiliki ${data.pilihan_jawaban} pilihan jawaban (A-${String.fromCharCode(64 + parseInt(data.pilihan_jawaban))})` : ''}
4. Soal harus sesuai dengan mata pelajaran dan materi yang disebutkan
5. Gunakan bahasa Indonesia yang baik dan benar
6. Pastikan soal sesuai dengan tingkat pendidikan siswa

PANDUAN KESULITAN:
- MUDAH: Konsep dasar, mengingat, pemahaman sederhana
- SEDANG: Penerapan konsep, analisis sederhana
- SULIT: Analisis kompleks, sintesis, evaluasi

FORMAT RESPONS:
Berikan respons dalam format JSON yang valid sesuai dengan struktur yang diminta.`;

        return prompt;
    }
    
    /**
     * Get expected response format
     */
    function getExpectedResponseFormat(questionType, answerOptions) {
        if (questionType === 'multiple_choice') {
            const optionLetters = [];
            for (let i = 0; i < answerOptions; i++) {
                optionLetters.push(String.fromCharCode(65 + i));
            }
            
            return `{
  "success": true,
  "questions": [
    {
      "pertanyaan": "Teks soal pertama...",
      "tipe": "multiple_choice",
      "pilihan": {
        ${optionLetters.map(letter => `"${letter}": "Teks pilihan ${letter}"`).join(',\n        ')}
      },
      "kunci_jawaban": "${optionLetters[0]}",
      "penjelasan": "Penjelasan jawaban...",
      "poin": 10
    }
  ]
}`;
        } else {
            return `{
  "success": true,
  "questions": [
    {
      "pertanyaan": "Teks soal pertama...",
      "tipe": "essay",
      "kunci_jawaban": "Contoh jawaban yang diharapkan...",
      "penjelasan": "Kriteria penilaian...",
      "poin": 20
    }
  ]
}`;
        }
    }
    
    /**
     * Handle question type change
     */
    function handleQuestionTypeChange() {
        if (!questionTypeSelect || !answerOptionsDiv) return;
        
        const questionType = questionTypeSelect.value;
        
        if (questionType === 'multiple_choice') {
            answerOptionsDiv.style.display = 'block';
        } else {
            answerOptionsDiv.style.display = 'none';
        }
        
        updatePromptPreview();
    }
    
    /**
     * Reset form to default values
     */
    function resetForm() {
        if (questionCountInput) questionCountInput.value = 5;
        if (questionTypeSelect && !isAutoScore) questionTypeSelect.value = 'multiple_choice';
        if (answerOptionsSelect) answerOptionsSelect.value = 4;
        if (difficultySelect) difficultySelect.value = 'sedang';
        
        // Always check auto score mode after reset
        checkAutoScoreMode();
        handleQuestionTypeChange();
        updatePromptPreview();
        
        // Reset generate button
        if (generateBtn) {
            generateBtn.innerHTML = '<svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" /></svg>Generate Soal';
            generateBtn.disabled = false;
        }
    }
    
    /**
     * Show loading backdrop
     */
    function showLoadingBackdrop() {
        if (!loadingBackdrop) {
            loadingBackdrop = document.createElement('div');
            loadingBackdrop.className = 'pingo-loading-backdrop fixed inset-0 flex items-center justify-center z-[9999] opacity-0 transition-all duration-300 ease-out';
            loadingBackdrop.style.background = 'rgba(0, 0, 0, 0.3)';
            loadingBackdrop.style.backdropFilter = 'blur(8px)';
            loadingBackdrop.style.webkitBackdropFilter = 'blur(8px)';
            
            loadingBackdrop.innerHTML = `
                <div class="pingo-loading-content bg-white/90 backdrop-blur-sm rounded-2xl p-8 shadow-2xl border border-white/20 transform scale-95 transition-all duration-300 ease-out">
                    <div class="flex flex-col items-center space-y-4">
                        <div class="relative">
                            <svg class="w-12 h-12 text-orange-500 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                            </svg>
                            <div class="absolute inset-0 w-12 h-12 border-4 border-orange-200 rounded-full animate-pulse"></div>
                        </div>
                        <div class="text-center">
                            <h3 class="text-xl font-semibold text-gray-800 mb-2">PingoAI Sedang Bekerja</h3>
                            <p class="text-gray-600 text-sm">Generating questions with artificial intelligence...</p>
                        </div>
                        <div class="flex space-x-1">
                            <div class="w-2 h-2 bg-orange-400 rounded-full animate-bounce" style="animation-delay: 0ms"></div>
                            <div class="w-2 h-2 bg-orange-400 rounded-full animate-bounce" style="animation-delay: 150ms"></div>
                            <div class="w-2 h-2 bg-orange-400 rounded-full animate-bounce" style="animation-delay: 300ms"></div>
                        </div>
                    </div>
                </div>
            `;
        }
        
        document.body.appendChild(loadingBackdrop);
        
        // Trigger fade in animation
        requestAnimationFrame(() => {
            loadingBackdrop.classList.add('opacity-100');
            const content = loadingBackdrop.querySelector('.pingo-loading-content');
            if (content) {
                content.classList.remove('scale-95');
                content.classList.add('scale-100');
            }
        });
    }
    
    /**
     * Hide loading backdrop
     */
    function hideLoadingBackdrop() {
        if (loadingBackdrop && loadingBackdrop.parentNode) {
            // Trigger fade out animation
            loadingBackdrop.classList.remove('opacity-100');
            loadingBackdrop.classList.add('opacity-0');
            
            const content = loadingBackdrop.querySelector('.pingo-loading-content');
            if (content) {
                content.classList.remove('scale-100');
                content.classList.add('scale-95');
            }
            
            // Remove element after animation completes
            setTimeout(() => {
                if (loadingBackdrop && loadingBackdrop.parentNode) {
                    loadingBackdrop.parentNode.removeChild(loadingBackdrop);
                }
            }, 300);
        }
    }
    
    /**
     * Show toast notification
     */
    function showToast(message, type = 'info') {
        // Remove existing toasts
        const existingToasts = document.querySelectorAll('.pingo-toast');
        existingToasts.forEach(toast => toast.remove());
        
        const toast = document.createElement('div');
        toast.className = `pingo-toast fixed top-4 right-4 px-6 py-4 rounded-lg shadow-lg z-[10000] transform transition-all duration-300 translate-x-full`;
        
        const colors = {
            'success': 'bg-green-500 text-white',
            'error': 'bg-red-500 text-white',
            'warning': 'bg-yellow-500 text-black',
            'info': 'bg-blue-500 text-white'
        };
        
        toast.className += ` ${colors[type] || colors.info}`;
        toast.textContent = message;
        
        document.body.appendChild(toast);
        
        // Show toast
        setTimeout(() => {
            toast.classList.remove('translate-x-full');
            toast.classList.add('translate-x-0');
        }, 100);
        
        // Hide toast after 5 seconds
        setTimeout(() => {
            toast.classList.remove('translate-x-0');
            toast.classList.add('translate-x-full');
            setTimeout(() => toast.remove(), 300);
        }, 5000);
    }
    
    /**
     * Handle generate questions
     */
    async function handleGenerateQuestions() {
        try {
            if (!questionCountInput || !questionTypeSelect || !difficultySelect) {
                showToast('Form elements not found', 'error');
                return;
            }
            
            // Validate ujian ID
            if (!ujianIdValue) {
                showToast('ID Ujian tidak ditemukan. Pastikan Anda berada di halaman yang benar.', 'error');
                return;
            }
            
            const count = parseInt(questionCountInput.value);
            if (!count || count < 1 || count > 20) {
                showToast('Jumlah soal harus antara 1-20', 'error');
                return;
            }
            
            // Show loading state
            generateBtn.innerHTML = '<svg class="w-4 h-4 mr-2 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" /></svg>Generating...';
            generateBtn.disabled = true;
            
            showLoadingBackdrop();
            
            // Prepare data
            const requestData = {
                ujian_id: parseInt(ujianIdValue),
                question_count: count,
                question_type: questionTypeSelect.value,
                answer_options: parseInt(answerOptionsSelect.value),
                difficulty: difficultySelect.value
            };
            
            console.log('Sending request data:', requestData);
            
            // Make API call
            const response = await fetch('../pingo/generate-questions.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(requestData)
            });
            
            const result = await response.json();
            
            hideLoadingBackdrop();
            
            if (result.success) {
                showToast(`Berhasil generate ${result.total} soal menggunakan PingoAI!`, 'success');
                closeModal();
                
                // Refresh page to show new questions
                setTimeout(() => {
                    window.location.reload();
                }, 1500);
                
                // Update statistics if function exists
                if (typeof updateStatistics === 'function') {
                    updateStatistics();
                }
            } else {
                throw new Error(result.error || 'Failed to generate questions');
            }
            
        } catch (error) {
            console.error('Error generating questions:', error);
            hideLoadingBackdrop();
            showToast('Error: ' + error.message, 'error');
        } finally {
            // Reset button state
            generateBtn.innerHTML = '<svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" /></svg>Generate Soal';
            generateBtn.disabled = false;
        }
    }
    
    /**
     * Update statistics display after adding questions
     */
    function updateStatistics() {
        const totalQuestions = document.querySelectorAll('.question-card').length;
        
        // Calculate total points by looking at existing questions
        let totalPoints = 0;
        document.querySelectorAll('.question-card').forEach(card => {
            // Look for points in the card structure - use proper JavaScript instead of invalid selector
            let pointsElement = card.querySelector('[class*="poin"]');
            
            // If not found, look for elements containing "poin" text
            if (!pointsElement) {
                const textElements = card.querySelectorAll('.text-sm, .text-xs, .text-base, [class*="text-"]');
                textElements.forEach(el => {
                    if (el.textContent && el.textContent.toLowerCase().includes('poin')) {
                        pointsElement = el;
                    }
                });
            }
            
            if (pointsElement) {
                const pointsText = pointsElement.textContent || '0';
                const points = parseInt(pointsText.replace(/\D/g, '')) || 10;
                totalPoints += points;
            } else {
                totalPoints += 10; // Default points
            }
        });
        
        const totalQuestionsElement = document.getElementById('total-questions');
        const totalPointsElement = document.getElementById('total-points');
        
        if (totalQuestionsElement) totalQuestionsElement.textContent = totalQuestions;
        if (totalPointsElement) totalPointsElement.textContent = totalPoints;
        
        // Also update any navigation elements
        updateQuestionNavigation();
    }
    
    /**
     * Update question navigation if exists
     */
    function updateQuestionNavigation() {
        const navContainer = document.getElementById('question-nav');
        if (navContainer) {
            // Add navigation buttons for new questions
            const questions = document.querySelectorAll('.question-card');
            questions.forEach((card, index) => {
                const questionId = card.getAttribute('data-soal-id');
                if (questionId && !navContainer.querySelector(`[data-nav-question="${questionId}"]`)) {
                    const navButton = document.createElement('button');
                    navButton.className = 'w-10 h-10 rounded-lg border border-gray-200 hover:bg-orange-50 hover:border-orange-200 transition-colors flex items-center justify-center text-sm font-medium';
                    navButton.setAttribute('data-nav-question', questionId);
                    navButton.textContent = index + 1;
                    navContainer.appendChild(navButton);
                    
                    // Add click handler to scroll to question
                    navButton.addEventListener('click', () => {
                        card.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    });
                }
            });
        }
    }
    
    // Expose openModal and showToast globally for external calls
    window.openPingoModal = openModal;
    window.showToast = showToast;
    
    /**
     * Toggle prompt details visibility
     */
    function togglePromptDetails() {
        if (!promptDetails || !toggleText || !toggleIcon) return;
        
        const isHidden = promptDetails.classList.contains('hidden');
        
        if (isHidden) {
            // Show details
            promptDetails.classList.remove('hidden');
            promptDetails.style.maxHeight = promptDetails.scrollHeight + 'px';
            toggleText.textContent = 'Sembunyikan';
            toggleIcon.style.transform = 'rotate(180deg)';
        } else {
            // Hide details
            promptDetails.style.maxHeight = '0px';
            toggleText.textContent = 'Lihat Selengkapnya';
            toggleIcon.style.transform = 'rotate(0deg)';
            
            // Wait for animation to complete before adding hidden class
            setTimeout(() => {
                promptDetails.classList.add('hidden');
                promptDetails.style.maxHeight = '';
            }, 300);
        }
    }
    
    // CSS for animations and modal styling
    const style = document.createElement('style');
    style.textContent = `
        #add-soal-ai {
            backdrop-filter: blur(4px);
            z-index: 9998;
        }
        
        /* Toggle animations for prompt details */
        #prompt-details {
            overflow: hidden;
            transition: max-height 300ms cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        #toggle-icon {
            transition: transform 200ms cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        #toggle-prompt-details:hover {
            text-decoration: underline;
        }
        
        #add-soal-ai .modal-backdrop {
            transition: opacity 150ms ease-out;
        }
        
        #add-soal-ai .modal-panel {
            transition: all 150ms ease-out;
            transform-origin: center;
        }
        
        .pingo-toast {
            max-width: 400px;
            word-wrap: break-word;
        }
        
        /* Loading Backdrop Styles */
        .pingo-loading-backdrop {
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
            background: rgba(0, 0, 0, 0.3) !important;
            transition: all 300ms cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        .pingo-loading-content {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.3);
            box-shadow: 
                0 25px 50px -12px rgba(0, 0, 0, 0.25),
                0 0 0 1px rgba(255, 255, 255, 0.1),
                inset 0 1px 0 rgba(255, 255, 255, 0.2);
            transition: all 300ms cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        /* Enhanced animations */
        @keyframes gentleBounce {
            0%, 80%, 100% { 
                transform: scale(1);
                animation-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
            }
            40% { 
                transform: scale(1.1);
                animation-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
            }
        }
        
        .pingo-loading-content .animate-bounce {
            animation: gentleBounce 1.5s infinite;
        }
        
        /* Glassmorphism effect for modern browsers */
        @supports (backdrop-filter: blur(8px)) {
            .pingo-loading-backdrop {
                background: rgba(0, 0, 0, 0.2);
            }
            
            .pingo-loading-content {
                background: rgba(255, 255, 255, 0.9);
            }
        }
        
        @media (max-width: 640px) {
            #add-soal-ai .modal-panel {
                margin: 1rem;
                max-height: calc(100vh - 2rem);
            }
            
            .pingo-loading-content {
                margin: 1rem;
                padding: 1.5rem;
            }
        }
        
        /* Prefers reduced motion */
        @media (prefers-reduced-motion: reduce) {
            .pingo-loading-backdrop,
            .pingo-loading-content {
                transition: opacity 150ms ease-out;
            }
            
            .pingo-loading-content .animate-bounce {
                animation: none;
            }
            
            .pingo-loading-content .animate-spin {
                animation: none;
            }
        }
    `;
    document.head.appendChild(style);
});
