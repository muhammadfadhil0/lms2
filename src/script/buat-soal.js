            // Set initial counter based on existing rendered cards
            let questionCounter = document.querySelectorAll ? document.querySelectorAll('.question-card').length || 1 : 1;
            
            function setupQuestionTypeHandler(questionCard) {
                const typeSelect = questionCard.querySelector('.question-type-select');
                const answerOptions = questionCard.querySelector('.answer-options');
                const answerKey = questionCard.querySelector('.answer-key');
                typeSelect.addEventListener('change', function() {
                    const questionType = this.value;
                    if (questionType === 'multiple_choice') {
                        answerOptions.classList.remove('hidden');
                        answerKey.classList.add('hidden');
                    } else {
                        answerOptions.classList.add('hidden');
                        answerKey.classList.remove('hidden');
                    }
                });
            }

    

            // Add Image Handler
            function setupImageHandler(questionCard) {
                const addImageBtn = questionCard.querySelector('.add-image-btn');
                const imageInput = questionCard.querySelector('.image-input');
                const imagePreview = questionCard.querySelector('.image-preview');
                const removeImageBtn = questionCard.querySelector('.remove-image');
                
                addImageBtn.addEventListener('click', () => {
                    imageInput.click();
                });
                
                imageInput.addEventListener('change', function(e) {
                    const file = e.target.files[0];
                    if (file) {
                        const reader = new FileReader();
                        reader.onload = function(e) {
                            const img = imagePreview.querySelector('img');
                            img.src = e.target.result;
                            imagePreview.classList.remove('hidden');
                            addImageBtn.style.display = 'none';
                        };
                        reader.readAsDataURL(file);
                    }
                });
                
                removeImageBtn.addEventListener('click', () => {
                    imagePreview.classList.add('hidden');
                    addImageBtn.style.display = 'flex';
                    imageInput.value = '';
                });
            }

            // Add Option Handler (for multiple choice)
            function setupAddOptionHandler(questionCard) {
                const addOptionBtn = questionCard.querySelector('.add-option');
                const optionsContainer = questionCard.querySelector('.answer-options .space-y-2');
                
                addOptionBtn.addEventListener('click', function() {
                    const currentOptions = optionsContainer.querySelectorAll('.flex.items-center.space-x-3');
                    const nextLetter = String.fromCharCode(65 + currentOptions.length); // A, B, C, D, E, ...
                    const questionId = questionCard.dataset.questionId;
                    
                    const newOption = document.createElement('div');
                    newOption.className = 'flex items-center space-x-3';
                    newOption.innerHTML = `
                        <input type="radio" name="correct_answer_${questionId}" value="${nextLetter}" class="text-orange-500 focus:ring-orange-500">
                        <span class="w-6 text-sm font-medium text-gray-600">${nextLetter}.</span>
                        <input type="text" class="option-input flex-1 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500" placeholder="Pilihan ${nextLetter}">
                        <button class="remove-option text-gray-400 hover:text-red-500 p-1">
                            <i class="ti ti-x"></i>
                        </button>
                    `;
                    
                    optionsContainer.appendChild(newOption);
                    setupRemoveOptionHandler(newOption);
                    // Mark card as unsaved when options change
                    markCardSaved(questionCard, false);
                });
            }

            // Remove Option Handler
            function setupRemoveOptionHandler(optionElement) {
                const removeBtn = optionElement.querySelector('.remove-option');
                removeBtn.addEventListener('click', function() {
                    const container = optionElement.parentNode;
                    optionElement.remove();
                    
                    // Relabel remaining options
                    const remainingOptions = container.querySelectorAll('.flex.items-center.space-x-3');
                    remainingOptions.forEach((option, index) => {
                        const letter = String.fromCharCode(65 + index);
                        option.querySelector('span').textContent = letter + '.';
                        option.querySelector('input[type="radio"]').value = letter;
                        option.querySelector('input[type="text"]').placeholder = `Pilihan ${letter}`;
                    });
                    // Mark parent question unsaved
                    const q = optionElement.closest('.question-card');
                    if (q) markCardSaved(q, false);
                });
            }

            // Create or update save-status badge in question header
            function ensureSaveBadge(questionCard) {
                const header = questionCard.querySelector('.flex.items-center.justify-between.mb-4');
                if (!header) return null;
                let badge = header.querySelector('.save-status-badge');
                if (!badge) {
                    badge = document.createElement('span');
                    badge.className = 'save-status-badge ml-3 text-sm px-2 py-0.5 rounded-full font-medium';
                    header.querySelector('.flex.items-center.space-x-3')?.appendChild(badge);
                }
                return badge;
            }

            function markCardSaved(questionCard, saved) {
                if (!questionCard) return;
                questionCard.dataset.saved = saved ? '1' : '0';
                const badge = ensureSaveBadge(questionCard);
                if (!badge) return;
                if (saved) {
                    badge.textContent = 'Tersimpan';
                    badge.classList.remove('bg-amber-100','text-amber-700');
                    badge.classList.add('bg-green-100','text-green-700');
                } else {
                    badge.textContent = 'Belum disimpan';
                    badge.classList.remove('bg-green-100','text-green-700');
                    badge.classList.add('bg-amber-100','text-amber-700');
                }
            }

            // Question Card Click Handler
            function setupQuestionCardHandler(questionCard) {
                questionCard.addEventListener('click', function() {
                    // Remove active class from all cards
                    document.querySelectorAll('.question-card').forEach(card => {
                        card.classList.remove('active');
                    });
                    
                    // Add active class to clicked card
                    this.classList.add('active');
                    
                    // Update navigation
                    updateQuestionNavigation();
                });
            }

            // Duplicate Question Handler
            function setupDuplicateHandler(questionCard) {
                const duplicateBtn = questionCard.querySelector('.duplicate-question');
                duplicateBtn.addEventListener('click', function(e) {
                    e.stopPropagation();
                    duplicateQuestion(questionCard);
                });
            }

            // Delete Question Handler
            function setupDeleteHandler(questionCard) {
                const deleteBtn = questionCard.querySelector('.delete-question');
                deleteBtn.addEventListener('click', function(e) {
                    e.stopPropagation();
                    
                    // Check if modal is available, fallback to confirm if not
                    if (window.deleteQuestionModal && typeof window.deleteQuestionModal.show === 'function') {
                        window.deleteQuestionModal.show(questionCard);
                    } else {
                        console.warn('Modal hapus soal belum siap, fallback ke confirm().', window.deleteQuestionModal);
                        // Fallback to original confirm dialog
                        const existingId = questionCard.getAttribute('data-soal-id');
                        const totalCards = document.querySelectorAll('.question-card').length;
                        // Allow deletion of all questions (removed restriction)
                        if(!confirm('Hapus soal ini?')) return;
                        if(existingId){
                                const fd = new FormData(); fd.append('soal_id', existingId);
                                fetch('../logic/delete-question.php',{method:'POST', body:fd})
                                    .then(r=>r.json()).then(j=>{
                                        if(j.success){
                                                questionCard.remove();
                                                updateQuestionNumbers(); updateQuestionNavigation(); updateStats();
                                                // Show success notification
                                                if (typeof showToast === 'function') {
                                                    showToast('Soal berhasil dihapus', 'success');
                                                }
                                        } else alert('Gagal hapus: '+(j.message||''));
                                    }).catch(()=>alert('Gagal hapus (network).'));
                        } else {
                                questionCard.remove(); updateQuestionNumbers(); updateQuestionNavigation(); updateStats();
                                // Show success notification for local deletion
                                if (typeof showToast === 'function') {
                                    showToast('Soal berhasil dihapus', 'success');
                                }
                        }
                    }
                });
            }

            // Points Change Handler
            function setupPointsHandler(questionCard) {
                const pointsInput = questionCard.querySelector('.question-points');
                if (pointsInput) {
                    pointsInput.addEventListener('input', updateStats);
                    pointsInput.addEventListener('change', updateStats);
                }
            }

            // Add New Question
            function addNewQuestion() {
                questionCounter++;
                const questionsContainer = document.getElementById('questions-container');
                
                const newQuestion = document.createElement('div');
                newQuestion.className = 'question-card bg-white rounded-lg shadow-sm border border-gray-200 p-6';
                newQuestion.dataset.questionId = questionCounter;
                
                newQuestion.innerHTML = `
                    <!-- Question Header -->
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center space-x-3">
                            <div class="drag-handle p-1 text-gray-400 hover:text-gray-600">
                                <i class="ti ti-grip-vertical"></i>
                            </div>
                            <h3 class="text-lg font-medium text-gray-800">Soal ${questionCounter}</h3>
                            <span class="text-sm text-gray-500">(Opsional)</span>
                        </div>
                        <div class="flex items-center space-x-2">
                            <button class="duplicate-question p-2 text-gray-400 hover:text-orange transition-colors rounded-lg hover:bg-gray-50" title="Duplikat Soal">
                                <i class="ti ti-copy"></i>
                            </button>
                            <button class="delete-question p-2 text-gray-400 hover:text-red-500 transition-colors rounded-lg hover:bg-gray-50" title="Hapus Soal">
                                <i class="ti ti-trash"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Question Type Selector -->
                    <div class="mb-4">
                        <select class="question-type-select w-full md:w-auto px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500 bg-white">
                            <option value="multiple_choice">Pilihan Ganda</option>
                            <option value="short_answer">Jawaban Singkat</option>
                            <option value="long_answer">Jawaban Panjang</option>
                        </select>
                    </div>

                    <!-- Question Input -->
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Pertanyaan</label>
                        <textarea class="question-text w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500 resize-none" 
                                  rows="3" placeholder="Masukkan pertanyaan..."></textarea>
                    </div>

                    <!-- Question Image -->
                    <div class="mb-4">
                        <button class="add-image-btn flex items-center space-x-2 px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                            <i class="ti ti-photo"></i>
                            <span>Tambah Gambar</span>
                        </button>
                        <input type="file" class="image-input hidden" accept="image/*">
                        <div class="image-preview mt-2 hidden">
                            <img class="max-w-full h-auto rounded-lg border border-gray-200" alt="Preview">
                            <button class="remove-image mt-2 text-red-500 hover:text-red-700 text-sm">
                                <i class="ti ti-x"></i> Hapus Gambar
                            </button>
                        </div>
                    </div>

                    <!-- Answer Options (Multiple Choice) -->
                    <div class="answer-options mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Pilihan Jawaban</label>
                        <div class="space-y-2">
                            <div class="flex items-center space-x-3">
                                <input type="radio" name="correct_answer_${questionCounter}" value="A" class="text-orange-500 focus:ring-orange-500">
                                <span class="w-6 text-sm font-medium text-gray-600">A.</span>
                                <input type="text" class="option-input flex-1 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500" placeholder="Pilihan A">
                                <button class="remove-option text-gray-400 hover:text-red-500 p-1">
                                    <i class="ti ti-x"></i>
                                </button>
                            </div>
                            <div class="flex items-center space-x-3">
                                <input type="radio" name="correct_answer_${questionCounter}" value="B" class="text-orange-500 focus:ring-orange-500">
                                <span class="w-6 text-sm font-medium text-gray-600">B.</span>
                                <input type="text" class="option-input flex-1 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500" placeholder="Pilihan B">
                                <button class="remove-option text-gray-400 hover:text-red-500 p-1">
                                    <i class="ti ti-x"></i>
                                </button>
                            </div>
                            <div class="flex items-center space-x-3">
                                <input type="radio" name="correct_answer_${questionCounter}" value="C" class="text-orange-500 focus:ring-orange-500">
                                <span class="w-6 text-sm font-medium text-gray-600">C.</span>
                                <input type="text" class="option-input flex-1 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500" placeholder="Pilihan C">
                                <button class="remove-option text-gray-400 hover:text-red-500 p-1">
                                    <i class="ti ti-x"></i>
                                </button>
                            </div>
                            <div class="flex items-center space-x-3">
                                <input type="radio" name="correct_answer_${questionCounter}" value="D" class="text-orange-500 focus:ring-orange-500">
                                <span class="w-6 text-sm font-medium text-gray-600">D.</span>
                                <input type="text" class="option-input flex-1 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500" placeholder="Pilihan D">
                                <button class="remove-option text-gray-400 hover:text-red-500 p-1">
                                    <i class="ti ti-x"></i>
                                </button>
                            </div>
                        </div>
                        <button class="add-option mt-2 text-orange hover:text-orange-600 text-sm font-medium">
                            <i class="ti ti-plus"></i> Tambah Pilihan
                        </button>
                    </div>

                    <!-- Short/Long Answer (Hidden by default) -->
                    <div class="answer-key hidden mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Kunci Jawaban</label>
                        <textarea class="answer-key-text w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500 resize-none" 
                                  rows="2" placeholder="Masukkan kunci jawaban..."></textarea>
                    </div>
                    
                    <!-- Points Section -->
                    <div class="points-section mt-4 pt-4 border-t border-gray-200">
                        <div class="flex items-center space-x-2">
                            <span class="text-sm text-gray-700">Poin:</span>
                            <input type="number" class="question-points w-20 px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-orange-500 focus:border-orange-500" value="10" min="1" max="100">
                            <span class="text-xs text-gray-500">poin</span>
                        </div>
                    </div>
                </div>
                `;
                
                questionsContainer.appendChild(newQuestion);
                
                // Setup all handlers for the new question
                setupQuestionHandlers(newQuestion);

                // New question is unsaved by default
                markCardSaved(newQuestion, false);
                
                // Check if auto score is active and hide points section if needed
                const autoScoreFlag = document.getElementById('ujian_id')?.dataset.autoscore === '1';
                if (autoScoreFlag) {
                    const pointsSection = newQuestion.querySelector('.points-section');
                    if (pointsSection) {
                        pointsSection.classList.add('hidden');
                    }
                }
                
                // Make the new question active
                document.querySelectorAll('.question-card').forEach(card => {
                    card.classList.remove('active');
                });
                newQuestion.classList.add('active');
                
                // Scroll to new question
                newQuestion.scrollIntoView({ behavior: 'smooth', block: 'center' });
                
                // Update navigation and stats
                updateQuestionNavigation();
                updateStats();
            }

            // Duplicate Question
            function duplicateQuestion(originalCard) {
                // Gather original data to copy (values/properties, not attributes)
                const origTypeSelect = originalCard.querySelector('.question-type-select');
                const origType = origTypeSelect ? origTypeSelect.value : 'multiple_choice';
                const origQuestionText = originalCard.querySelector('.question-text')?.value || '';
                const origOptions = Array.from(originalCard.querySelectorAll('.answer-options .flex.items-center.space-x-3')).map(opt => {
                    return {
                        letter: opt.querySelector('input[type=radio]')?.value || '',
                        text: opt.querySelector('input[type=text]')?.value || '',
                        checked: !!opt.querySelector('input[type=radio]')?.checked
                    };
                });
                const origAnswerKey = originalCard.querySelector('.answer-key-text')?.value || '';
                const origPoints = originalCard.querySelector('.question-points')?.value || '';

                // Clone node and then apply copied values to avoid losing runtime state
                const newCard = originalCard.cloneNode(true);
                questionCounter++;
                newCard.dataset.questionId = questionCounter;
                // Ensure cloned card does not keep the original server ID
                if (newCard.hasAttribute('data-soal-id')) {
                    newCard.removeAttribute('data-soal-id');
                }

                // Update question number/title
                const h3 = newCard.querySelector('h3');
                if (h3) h3.textContent = `Soal ${questionCounter}`;

                // Update question type select
                const newTypeSelect = newCard.querySelector('.question-type-select');
                if (newTypeSelect) {
                    newTypeSelect.value = origType;
                    // Show/hide sections according to type
                    const newAnswerOptions = newCard.querySelector('.answer-options');
                    const newAnswerKey = newCard.querySelector('.answer-key');
                    if (origType === 'multiple_choice') {
                        newAnswerOptions?.classList.remove('hidden');
                        newAnswerKey?.classList.add('hidden');
                    } else {
                        newAnswerOptions?.classList.add('hidden');
                        newAnswerKey?.classList.remove('hidden');
                    }
                }

                // Set question text
                const newQuestionText = newCard.querySelector('.question-text');
                if (newQuestionText) newQuestionText.value = origQuestionText;

                // Copy options and checked state
                const newOptions = newCard.querySelectorAll('.answer-options .flex.items-center.space-x-3');
                newOptions.forEach((optElem, idx) => {
                    const radio = optElem.querySelector('input[type=radio]');
                    const textInput = optElem.querySelector('input[type=text]');
                    const span = optElem.querySelector('span');
                    const orig = origOptions[idx] || { letter: String.fromCharCode(65 + idx), text: '', checked: false };
                    // Update letter label
                    if (span) span.textContent = orig.letter ? `${orig.letter}.` : `${String.fromCharCode(65 + idx)}.`;
                    // Update radio name and value
                    if (radio) {
                        radio.name = `correct_answer_${questionCounter}`;
                        radio.value = orig.letter || String.fromCharCode(65 + idx);
                        radio.checked = !!orig.checked;
                    }
                    // Update option text
                    if (textInput) {
                        textInput.value = orig.text || '';
                        textInput.placeholder = `Pilihan ${orig.letter || String.fromCharCode(65 + idx)}`;
                    }
                });

                // If there are more origOptions than cloned nodes, append them
                if (origOptions.length > newOptions.length) {
                    const optionsContainer = newCard.querySelector('.answer-options .space-y-2');
                    for (let i = newOptions.length; i < origOptions.length; i++) {
                        const o = origOptions[i];
                        const letter = o.letter || String.fromCharCode(65 + i);
                        const newOption = document.createElement('div');
                        newOption.className = 'flex items-center space-x-3';
                        newOption.innerHTML = `
                            <input type="radio" name="correct_answer_${questionCounter}" value="${letter}" class="text-orange-500 focus:ring-orange-500" ${o.checked? 'checked':''}>
                            <span class="w-6 text-sm font-medium text-gray-600">${letter}.</span>
                            <input type="text" class="option-input flex-1 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500" placeholder="Pilihan ${letter}" value="${o.text}">
                            <button class="remove-option text-gray-400 hover:text-red-500 p-1">
                                <i class="ti ti-x"></i>
                            </button>
                        `;
                        optionsContainer.appendChild(newOption);
                        setupRemoveOptionHandler(newOption);
                    }
                }

                // Copy answer key for short/long answers
                const newAnswerKeyText = newCard.querySelector('.answer-key-text');
                if (newAnswerKeyText) newAnswerKeyText.value = origAnswerKey;

                // Copy points
                const newPointsInput = newCard.querySelector('.question-points');
                if (newPointsInput) newPointsInput.value = origPoints;

                // Hide image preview and reset image input UI
                const imagePreview = newCard.querySelector('.image-preview');
                const addImageBtn = newCard.querySelector('.add-image-btn');
                if (imagePreview) imagePreview.classList.add('hidden');
                if (addImageBtn) addImageBtn.style.display = 'flex';

                // Ensure cloned card is marked unsaved
                markCardSaved(newCard, false);
                // Insert after original card
                originalCard.parentNode.insertBefore(newCard, originalCard.nextSibling);

                // Setup handlers
                setupQuestionHandlers(newCard);

                // Check if auto score is active and hide points section if needed
                const autoScoreFlag = document.getElementById('ujian_id')?.dataset.autoscore === '1';
                if (autoScoreFlag) {
                    const pointsSection = newCard.querySelector('.points-section');
                    if (pointsSection) {
                        pointsSection.classList.add('hidden');
                    }
                }

                // Make new card active
                document.querySelectorAll('.question-card').forEach(card => {
                    card.classList.remove('active');
                });
                newCard.classList.add('active');

                // Scroll to new question
                newCard.scrollIntoView({ behavior: 'smooth', block: 'center' });

                updateQuestionNavigation();
                updateStats();
            }

            // Setup all handlers for a question card
            function setupQuestionHandlers(questionCard) {
                setupQuestionTypeHandler(questionCard);
                setupImageHandler(questionCard);
                setupAddOptionHandler(questionCard);
                setupQuestionCardHandler(questionCard);
                setupDuplicateHandler(questionCard);
                setupDeleteHandler(questionCard);
                setupPointsHandler(questionCard);
                
                // Setup remove option handlers for existing options
                questionCard.querySelectorAll('.remove-option').forEach(btn => {
                    setupRemoveOptionHandler(btn.closest('.flex.items-center.space-x-3'));
                });

                // Ensure save badge exists and set initial saved state
                const hasServerId = !!questionCard.getAttribute('data-soal-id');
                markCardSaved(questionCard, hasServerId);

                // Mark as unsaved when user edits fields
                const inputs = questionCard.querySelectorAll('input[type="text"], textarea, select, input[type="number"]');
                inputs.forEach(inp => {
                    const ev = inp.tagName.toLowerCase() === 'select' ? 'change' : 'input';
                    inp.addEventListener(ev, () => markCardSaved(questionCard, false));
                });

                // Radio changes (correct answer) should mark unsaved
                questionCard.querySelectorAll('input[type="radio"]').forEach(r => r.addEventListener('change', () => markCardSaved(questionCard, false)));
            }

            // Update question numbers
            function updateQuestionNumbers() {
                const questionCards = document.querySelectorAll('.question-card');
                questionCards.forEach((card, index) => {
                    const questionNumber = index + 1;
                    card.querySelector('h3').textContent = `Soal ${questionNumber}`;
                    
                    // Update required/optional status
                    const statusSpan = card.querySelector('h3').nextElementSibling;
                    if (statusSpan) {
                        const requiredToggle = card.querySelector('.required-toggle');
                        if (requiredToggle && Object.prototype.hasOwnProperty.call(requiredToggle, 'checked')) {
                            statusSpan.textContent = requiredToggle.checked ? '(Wajib)' : '(Opsional)';
                        } else {
                            // Default jika toggle tidak ada
                            statusSpan.textContent = '(Opsional)';
                        }
                    }
                });
            }

            // Update question navigation
            function updateQuestionNavigation() {
                const questionNav = document.getElementById('question-nav');
                const questionCards = document.querySelectorAll('.question-card');
                
                questionNav.innerHTML = '';
                
                questionCards.forEach((card, index) => {
                    const questionNumber = index + 1;
                    const navItem = document.createElement('button');
                    navItem.className = 'question-nav-item flex items-center justify-center aspect-square text-sm rounded-lg border transition-colors';
                    navItem.dataset.question = questionNumber;
                    navItem.textContent = questionNumber;
                    
                    if (card.classList.contains('active')) {
                        navItem.className += ' border-orange bg-orange-50 text-orange font-medium';
                    } else {
                        navItem.className += ' border-gray-200 text-gray-700 hover:bg-gray-50';
                    }
                    
                    navItem.addEventListener('click', () => {
                        document.querySelectorAll('.question-card').forEach(c => c.classList.remove('active'));
                        card.classList.add('active');
                        card.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        updateQuestionNavigation();
                    });
                    
                    questionNav.appendChild(navItem);
                });
                // Apply overflow class if more than 30
                if(questionCards.length > 30){
                    questionNav.classList.add('overflow-limit');
                } else {
                    questionNav.classList.remove('overflow-limit');
                }
            }

            // Update stats
            function updateStats() {
                const autoScoreFlag = document.getElementById('ujian_id')?.dataset.autoscore === '1';
                const cards = Array.from(document.querySelectorAll('.question-card'));
                const activeCards = autoScoreFlag ? cards.filter(c=>c.querySelector('.question-type-select')?.value==='multiple_choice') : cards;
                const totalQuestions = activeCards.length;
                let totalPoints = 0;
                
                if(autoScoreFlag){
                    // For auto score: distribute 100 points evenly among active MC questions
                    const validCards = activeCards.filter(c=>c.querySelector('.question-text')?.value?.trim());
                    if (validCards.length > 0) {
                        totalPoints = 100; // Always 100 for auto score
                    }
                } else {
                    // Normal mode: sum all points from inputs
                    document.querySelectorAll('.question-points').forEach(inp => { 
                        const value = parseInt(inp.value) || 0;
                        totalPoints += value;
                    });
                }
                
                document.getElementById('total-questions').textContent = totalQuestions;
                document.getElementById('total-points').textContent = totalPoints;
            }

            // Add Description
            function addDescription() {
                const questionsContainer = document.getElementById('questions-container');
                
                const descriptionDiv = document.createElement('div');
                descriptionDiv.className = 'description-card bg-blue-50 rounded-lg border border-blue-200 p-6';
                descriptionDiv.innerHTML = `
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-medium text-blue-800 flex items-center">
                            <i class="ti ti-info-circle mr-2"></i>
                            Deskripsi
                        </h3>
                        <button class="delete-description text-gray-400 hover:text-red-500 p-1">
                            <i class="ti ti-x"></i>
                        </button>
                    </div>
                    <textarea class="w-full px-4 py-3 border border-blue-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 resize-none bg-white" 
                              rows="3" placeholder="Tambahkan deskripsi atau instruksi tambahan..."></textarea>
                `;
                
                questionsContainer.appendChild(descriptionDiv);
                
                // Setup delete handler
                descriptionDiv.querySelector('.delete-description').addEventListener('click', () => {
                    descriptionDiv.remove();
                });
                
                // Scroll to description
                descriptionDiv.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }

            // Initialize
            document.addEventListener('DOMContentLoaded', function() {
                            // Detect autoScore flag from server (inject via hidden or query?) Attempt read from a global meta
                            const ujianHidden = document.getElementById('ujian_id');
                            const autoScoreFlag = ujianHidden && ujianHidden.dataset.autoscore === '1';
                            console.log('AutoScore flag detected:', autoScoreFlag, 'from dataset:', ujianHidden?.dataset.autoscore);
                
                // Setup handlers for initial question
                document.querySelectorAll('.question-card').forEach(card=>setupQuestionHandlers(card));
                // Build navigation buttons (attach click handlers)
                updateQuestionNavigation();
                
                // Add question button
                document.getElementById('add-question-btn').addEventListener('click', addNewQuestion);
                
                // Add description button
                document.getElementById('add-description-btn').addEventListener('click', addDescription);
                
                // Action buttons
                // Question validation function
                function validateQuestions() {
                    const cards = document.querySelectorAll('.question-card');
                    const incompleteQuestions = [];
                    
                    cards.forEach((card, index) => {
                        const questionNumber = index + 1;
                        const type = card.querySelector('.question-type-select').value;
                        const pertanyaan = card.querySelector('.question-text').value.trim();
                        const isActive = card.dataset.active !== '0';
                        
                        if (!isActive) return; // Skip inactive questions (for auto-score mode)
                        
                        let issues = [];
                        
                        // Check if question text is filled
                        if (!pertanyaan) {
                            issues.push('Pertanyaan belum diisi');
                        }
                        
                        // Check based on question type
                        if (type === 'multiple_choice') {
                            const options = card.querySelectorAll('.answer-options .flex.items-center.space-x-3');
                            const correct = card.querySelector('input[type=radio]:checked');
                            
                            // Check if at least 2 options are filled
                            const filledOptions = Array.from(options).filter(opt => 
                                opt.querySelector('input[type=text]').value.trim() !== ''
                            );
                            
                            if (filledOptions.length < 2) {
                                issues.push('Minimal 2 pilihan jawaban harus diisi');
                            }
                            
                            // Check if correct answer is selected
                            if (!correct) {
                                issues.push('Jawaban benar belum dipilih');
                            }
                        } else {
                            // Short answer or long answer
                            const kunci = card.querySelector('.answer-key-text').value.trim();
                            if (!kunci) {
                                issues.push('Kunci jawaban belum diisi');
                            }
                        }
                        
                        if (issues.length > 0) {
                            incompleteQuestions.push({
                                questionNumber: questionNumber,
                                questionId: card.dataset.questionId,
                                issues: issues,
                                card: card
                            });
                        }
                    });
                    
                    return incompleteQuestions;
                }

                // Show incomplete questions modal
                function showIncompleteQuestionsModal(incompleteQuestions) {
                    const modal = document.getElementById('incomplete-questions-modal');
                    const listContainer = document.getElementById('incomplete-questions-list');
                    
                    // Clear previous content
                    listContainer.innerHTML = '';
                    
                    // Populate incomplete questions
                    incompleteQuestions.forEach(question => {
                        const questionItem = document.createElement('div');
                        questionItem.className = 'question-item-card border border-red-200 rounded-lg p-3 bg-red-50 cursor-pointer';
                        questionItem.innerHTML = `
                            <div class="flex items-start justify-between">
                                <div class="flex-1">
                                    <h4 class="text-sm font-medium text-red-800">Soal ${question.questionNumber}</h4>
                                    <ul class="text-xs text-red-600 mt-1 space-y-1">
                                        ${question.issues.map(issue => `<li>• ${issue}</li>`).join('')}
                                    </ul>
                                </div>
                                <div class="ml-2 text-red-500">
                                    <i class="ti ti-arrow-right"></i>
                                </div>
                            </div>
                        `;
                        
                        // Add click handler to navigate to question
                        questionItem.addEventListener('click', () => {
                            modal.close();
                            navigateToQuestion(question.card);
                        });
                        
                        listContainer.appendChild(questionItem);
                    });
                    
                    // Show modal with simple fade
                    modal.showModal();
                }

                // Navigate to specific question
                function navigateToQuestion(questionCard) {
                    // Remove active class from all cards
                    document.querySelectorAll('.question-card').forEach(card => {
                        card.classList.remove('active');
                    });
                    
                    // Add active class to target card
                    questionCard.classList.add('active');
                    
                    // Scroll to the question
                    questionCard.scrollIntoView({ 
                        behavior: 'smooth', 
                        block: 'center' 
                    });
                    
                    // Focus on the first empty field
                    const questionText = questionCard.querySelector('.question-text');
                    if (!questionText.value.trim()) {
                        questionText.focus();
                    } else if (questionCard.querySelector('.question-type-select').value === 'multiple_choice') {
                        const emptyOption = questionCard.querySelector('.answer-options input[type=text][value=""]');
                        if (emptyOption) {
                            emptyOption.focus();
                        }
                    } else {
                        const answerKey = questionCard.querySelector('.answer-key-text');
                        if (!answerKey.value.trim()) {
                            answerKey.focus();
                        }
                    }
                    
                    // Update navigation
                    updateQuestionNavigation();
                }

                // Setup modal event handlers
                function setupModalHandlers() {
                    const modal = document.getElementById('incomplete-questions-modal');
                    const closeBtn = document.getElementById('close-incomplete-modal');
                                        
                    closeBtn.addEventListener('click', () => {
                        modal.close();
                    });
                    
                    
                    // Close modal when clicking outside
                    modal.addEventListener('click', (e) => {
                        if (e.target === modal) {
                            modal.close();
                        }
                    });
                    
                    // Close modal with Escape key
                    modal.addEventListener('keydown', (e) => {
                        if (e.key === 'Escape') {
                            modal.close();
                        }
                    });
                }

                async function serializeAndSendAll(publish=false){
                    // Validate questions before saving
                    const incompleteQuestions = validateQuestions();
                    if (incompleteQuestions.length > 0) {
                        showIncompleteQuestionsModal(incompleteQuestions);
                        return;
                    }
                    
                    const ujianId = document.getElementById('ujian_id').value;
                    const cards = document.querySelectorAll('.question-card');
                    let successCount = 0; let failCount=0;
                    // If autoScore, compute equal points distribution then scale to 100
                    let autoPointsMap = {};
                    if(autoScoreFlag){
                        const validCards = Array.from(cards).filter(c=>c.querySelector('.question-text').value.trim() && c.dataset.active !== '0');
                        const n = validCards.length;
                        let base = Math.floor(100 / n);
                        let remainder = 100 - (base * n);
                        validCards.forEach((c,i)=>{ autoPointsMap[c.dataset.questionId] = base + (i < remainder ? 1 : 0); });
                    }
                    for (const card of cards){
                        const type = card.querySelector('.question-type-select').value;
                        const pertanyaan = card.querySelector('.question-text').value.trim();
                        let poin = parseInt(card.querySelector('.question-points')?.value || 10);
                        
                        // Skip inactive questions (auto-score mode)
                        if (card.dataset.active === '0') continue;
                        
                        if(autoScoreFlag){
                            // Force multiple choice
                            if(type !== 'multiple_choice'){
                                continue; // skip non MC if any slipped in
                            }
                            poin = autoPointsMap[card.dataset.questionId] || poin;
                        }
                        if(!pertanyaan){continue;} // skip kosong
                        let formData = new FormData();
                        const existingId = card.getAttribute('data-soal-id');
                        formData.append('ujian_id', ujianId);
                        formData.append('poin', poin);
                        if(type==='multiple_choice'){
                            formData.append('tipe','multiple_choice');
                            const options = card.querySelectorAll('.answer-options .flex.items-center.space-x-3');
                            const correct = card.querySelector('input[type=radio]:checked');
                            options.forEach(opt=>{
                                const letter = opt.querySelector('input[type=radio]').value;
                                const text = opt.querySelector('input[type=text]').value.trim();
                                if(text){
                                    formData.append('pilihan['+letter+']', text);
                                }
                            });
                            if(correct){formData.append('kunci_pilihan', correct.value);}    
                        } else if (type==='short_answer'){
                            formData.append('tipe','short_answer');
                            formData.append('kunci', card.querySelector('.answer-key-text').value.trim());
                        } else {
                            formData.append('tipe','long_answer');
                            formData.append('kunci', card.querySelector('.answer-key-text').value.trim());
                        }
                        formData.append('pertanyaan', pertanyaan);
                        try {
                            const endpoint = existingId ? '../logic/update-question.php' : '../logic/create-question.php';
                            if(existingId){ formData.append('soal_id', existingId); }
                            const resp = await fetch(endpoint,{method:'POST', body:formData});
                            const json = await resp.json();
                                if(json.success){
                                    successCount++;
                                    // If server returned a new soal_id for created question, set it
                                    if(!existingId && json.soal_id){ card.setAttribute('data-soal-id', json.soal_id); }
                                    // Mark this card saved
                                    markCardSaved(card, true);
                                } else {
                                    failCount++;
                                    // keep as unsaved
                                    markCardSaved(card, false);
                                }
                        } catch(e){failCount++;}
                    }
                    
                    // Show success message
                    if (failCount === 0 && successCount > 0) {
                        showToast(`Berhasil menyimpan ${successCount} soal!`, 'success');
                        const ts = new Date();
                        const fmt = ts.toLocaleString();
                        setHeaderSaveStatus(true, fmt);
                    } else if (failCount > 0) {
                        showToast(`Simpan selesai. Berhasil: ${successCount}, Gagal: ${failCount}`, 'warning');
                        // Partial save: update header with time but keep unsaved indicator
                        const ts = new Date();
                        const fmt = ts.toLocaleString();
                        setHeaderSaveStatus(false, fmt);
                    }
                    
                    if(publish && failCount===0){
                        if(confirm('Semua soal tersimpan. Aktifkan ujian sekarang?')){
                            // publish ujian
                            const pubForm = new FormData();
                            pubForm.append('ujian_id', ujianId);
                            pubForm.append('status','aktif');
                            fetch('../logic/update-status-ujian.php',{method:'POST',body:pubForm})
                                .then(r=>r.json()).then(j=>{
                                    if(j.success){
                                        showToast('Ujian berhasil dipublikasikan!', 'success');
                                        setTimeout(() => {
                                            window.location.href='ujian-guru.php';
                                        }, 2000);
                                    } else showToast('Gagal publish: '+j.message, 'error');
                                }).catch(()=>showToast('Gagal publish (network).', 'error'));
                        }
                    }
                }

                // Setup modal event handlers when DOM is ready
                setupModalHandlers();

                document.getElementById('save-draft-btn').addEventListener('click', function() {
                    serializeAndSendAll(false);
                });
                
                document.getElementById('preview-exam-btn').addEventListener('click', function() {
                    alert('Membuka preview ujian...');
                });
                
                // Update initial stats
                updateStats();

                // Global save UI helpers
                function setHeaderSaveStatus(saved, timestamp) {
                    const lastSavedEl = document.getElementById('last-saved');
                    const dot = document.getElementById('save-dot');
                    if (!lastSavedEl) return;
                    if (saved) {
                        lastSavedEl.textContent = timestamp ? `Terakhir disimpan: ${timestamp}` : 'Tersimpan';
                        if (dot) { dot.classList.remove('bg-amber-300'); dot.classList.add('bg-green-500'); }
                    } else {
                        lastSavedEl.textContent = 'Belum disimpan';
                        if (dot) { dot.classList.remove('bg-green-500'); dot.classList.add('bg-amber-300'); }
                    }
                }

                // Update header when any card becomes unsaved
                document.addEventListener('input', () => setHeaderSaveStatus(false));
                document.addEventListener('change', () => setHeaderSaveStatus(false));

                // Handle auto score mode
                if(autoScoreFlag){
                    console.log('Initializing auto score mode');
                    document.querySelectorAll('.question-card').forEach(card=>{
                        const sel = card.querySelector('.question-type-select');
                        if(sel){
                            if(sel.value!=='multiple_choice'){
                                card.classList.add('opacity-60','pointer-events-none','relative');
                                if(!card.querySelector('.autoScore-disabled-note')){
                                    const overlay=document.createElement('div');
                                    overlay.className='autoScore-disabled-note absolute inset-0 flex items-center justify-center text-center p-4';
                                    overlay.innerHTML='<div class="bg-white/80 backdrop-blur-sm rounded-md p-3 text-xs font-medium text-amber-700 border border-amber-300 shadow-sm">Penilaian otomatis: soal non pilihan ganda tidak diujikan.</div>';
                                    card.appendChild(overlay);
                                }
                                // Mark as inactive
                                card.dataset.active = '0';
                            } else {
                                sel.value='multiple_choice';
                                Array.from(sel.options).forEach(opt=>{ if(opt.value!=='multiple_choice'){ opt.disabled=true; opt.hidden=true; }});
                                sel.addEventListener('change', ()=>{ sel.value='multiple_choice'; });
                                // Mark as active
                                card.dataset.active = '1';
                            }
                        }
                    });
                    document.querySelectorAll('.answer-key').forEach(el=>el.classList.add('hidden'));
                    
                    // Hide points sections when auto score is active
                    document.querySelectorAll('.points-section').forEach(section => {
                        section.classList.add('hidden');
                    });
                    
                    // Add notification if not already exists
                    if (!document.querySelector('.autoscore-notification')) {
                        const note = document.createElement('div');
                        note.className='autoscore-notification hidden mb-4 p-4 border border-orange bg-orange-50 text-sm text-orange-700 rounded';
                        note.innerHTML = '<strong>Mode Hitung Nilai Otomatis aktif:</strong> hanya soal pilihan ganda yang diujikan, poin dihitung otomatis (distribusi ke total 100).';
                        document.querySelector('.max-w-7xl')?.prepend(note);
                    }
                    
                    // Update stats after applying auto score logic
                    updateStats();
                } else {
                    console.log('Auto score mode not active');
                }
            });
