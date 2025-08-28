            let questionCounter = 1;
            
            function setupQuestionTypeHandler(questionCard) {
                const typeSelect = questionCard.querySelector('.question-type-select');
                const answerOptions = questionCard.querySelector('.answer-options');
                const answerKey = questionCard.querySelector('.answer-key');
                const autoGradingToggle = questionCard.querySelector('.auto-grading-toggle');
                
                typeSelect.addEventListener('change', function() {
                    const questionType = this.value;

                    if (questionType === 'multiple_choice') {
                        answerOptions.classList.remove('hidden');
                        answerKey.classList.add('hidden');
                        autoGradingToggle.checked = true;
                        autoGradingToggle.disabled = false;
                    } else {
                        answerOptions.classList.add('hidden');
                        answerKey.classList.remove('hidden');
                        autoGradingToggle.checked = false;
                        autoGradingToggle.disabled = true;
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
                });
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
                    
                    if (document.querySelectorAll('.question-card').length > 1) {
                        if (confirm('Apakah Anda yakin ingin menghapus soal ini?')) {
                            questionCard.remove();
                            updateQuestionNumbers();
                            updateQuestionNavigation();
                            updateStats();
                        }
                    } else {
                        alert('Tidak dapat menghapus soal terakhir. Ujian harus memiliki minimal 1 soal.');
                    }
                });
            }

            // Points Change Handler
            function setupPointsHandler(questionCard) {
                const pointsInput = questionCard.querySelector('.question-points');
                pointsInput.addEventListener('change', updateStats);
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

                    <!-- Question Settings -->
                    <div class="flex flex-wrap items-center justify-between pt-4 border-t border-gray-200">
                        <div class="flex items-center space-x-4">
                            <!-- Auto Grading Toggle -->
                            <div class="flex items-center space-x-2">
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" class="auto-grading-toggle sr-only peer" checked>
                                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-orange-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-orange"></div>
                                </label>
                                <span class="text-sm text-gray-700">Penilaian Otomatis</span>
                            </div>

                            <!-- Point Value -->
                            <div class="flex items-center space-x-2">
                                <span class="text-sm text-gray-700">Poin:</span>
                                <input type="number" class="question-points w-16 px-2 py-1 border border-gray-300 rounded text-sm focus:ring-2 focus:ring-orange-500 focus:border-orange-500" value="10" min="1">
                            </div>
                        </div>

                        <!-- Required Toggle -->
                        <div class="flex items-center space-x-2">
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" class="required-toggle sr-only peer">
                                <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-orange-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-orange"></div>
                            </label>
                            <span class="text-sm text-gray-700">Wajib</span>
                        </div>
                    </div>
                `;
                
                questionsContainer.appendChild(newQuestion);
                
                // Setup all handlers for the new question
                setupQuestionHandlers(newQuestion);
                
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
                const newCard = originalCard.cloneNode(true);
                questionCounter++;
                newCard.dataset.questionId = questionCounter;
                
                // Update question number
                newCard.querySelector('h3').textContent = `Soal ${questionCounter}`;
                
                // Update radio button names for multiple choice
                const radioButtons = newCard.querySelectorAll('input[type="radio"]');
                radioButtons.forEach(radio => {
                    radio.name = `correct_answer_${questionCounter}`;
                    radio.checked = false;
                });
                
                // Clear text inputs except for options
                const questionText = newCard.querySelector('.question-text');
                questionText.value = '';
                
                // Hide image preview if exists
                const imagePreview = newCard.querySelector('.image-preview');
                const addImageBtn = newCard.querySelector('.add-image-btn');
                imagePreview.classList.add('hidden');
                addImageBtn.style.display = 'flex';
                
                // Insert after original card
                originalCard.parentNode.insertBefore(newCard, originalCard.nextSibling);
                
                // Setup handlers
                setupQuestionHandlers(newCard);
                
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
            }

            // Update question numbers
            function updateQuestionNumbers() {
                const questionCards = document.querySelectorAll('.question-card');
                questionCards.forEach((card, index) => {
                    const questionNumber = index + 1;
                    card.querySelector('h3').textContent = `Soal ${questionNumber}`;
                    
                    // Update required/optional status
                    const statusSpan = card.querySelector('h3').nextElementSibling;
                    const requiredToggle = card.querySelector('.required-toggle');
                    statusSpan.textContent = requiredToggle.checked ? '(Wajib)' : '(Opsional)';
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
                    navItem.className = 'question-nav-item w-full text-left px-3 py-2 rounded-lg border transition-colors';
                    navItem.dataset.question = questionNumber;
                    navItem.textContent = `${questionNumber}. Soal`;
                    
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
            }

            // Update stats
            function updateStats() {
                const totalQuestions = document.querySelectorAll('.question-card').length;
                const totalPoints = Array.from(document.querySelectorAll('.question-points'))
                    .reduce((sum, input) => sum + parseInt(input.value || 0), 0);
                
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
                // Setup handlers for initial question
                const initialQuestion = document.querySelector('.question-card');
                setupQuestionHandlers(initialQuestion);
                
                // Add question button
                document.getElementById('add-question-btn').addEventListener('click', addNewQuestion);
                
                // Add description button
                document.getElementById('add-description-btn').addEventListener('click', addDescription);
                
                // Action buttons
                document.getElementById('save-draft-btn').addEventListener('click', function() {
                    alert('Draft berhasil disimpan!');
                });
                
                document.getElementById('preview-exam-btn').addEventListener('click', function() {
                    alert('Membuka preview ujian...');
                });
                
                document.getElementById('publish-exam-btn').addEventListener('click', function() {
                    if (confirm('Apakah Anda yakin ingin mempublikasikan ujian ini? Ujian yang sudah dipublikasikan tidak dapat diubah.')) {
                        alert('Ujian berhasil dipublikasikan!');
                    }
                });
                
                // Update initial stats
                updateStats();
            });
