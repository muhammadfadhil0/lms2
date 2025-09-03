// Kerjakan Ujian JavaScript
// Global variables
let currentQuestion = 1;
let timeLeft = 0;
let examTimer, autoSaveTimer;
let flagged = new Set();
let latestSaveSuccess = true;
let unsaved = new Set(); // question numbers with local change not yet saved
let autoSaveManager; // Auto save manager instance

document.addEventListener('DOMContentLoaded', () => {
    if (window.examData && window.examData.isStarted) {
        // Initialize auto save manager
        autoSaveManager = new window.AutoSaveManager(window.examData.ujianSiswaId);
        
        initMap();
        initEvents();
        startTimer();
        startAutoSave();
        updateMap();
        updateNavButtons();
        initAutoSaveIndicators();
    }
});

function confirmStart() {
    return confirm('Mulai ujian sekarang?');
}

function initMap() {
    const map = document.getElementById('question-map');
    if (!map) return;
    
    map.innerHTML = '';
    for (let i = 1; i <= window.examData.totalQuestions; i++) {
        const b = document.createElement('button');
        b.type = 'button';
        b.textContent = i;
        b.className = 'q-btn' + (i === 1 ? ' current' : '');
        b.dataset.q = i;
        b.onclick = () => go(i);
        map.appendChild(b);
    }
}

function initEvents() {
    const btnPrev = document.getElementById('btn-prev');
    const btnNext = document.getElementById('btn-next');
    const btnFinish = document.getElementById('btn-finish');
    const btnFlag = document.getElementById('btn-flag');
    const cancelFinish = document.getElementById('cancelFinish');
    const confirmFinish = document.getElementById('confirmFinish');
    
    if (btnPrev) btnPrev.onclick = () => go(currentQuestion - 1);
    if (btnNext) btnNext.onclick = () => go(currentQuestion + 1);
    if (btnFinish) btnFinish.onclick = showFinishModal;
    if (btnFlag) btnFlag.onclick = toggleFlag;
    if (cancelFinish) cancelFinish.onclick = hideFinishModal;
    if (confirmFinish) confirmFinish.onclick = finishExam;
    
    // Remove old change/input listeners - now handled by AutoSaveManager
    // Just keep track of local changes for UI updates
    document.addEventListener('change', e => {
        if (e.target.name && e.target.name.startsWith('soal_')) {
            updateMap(); // Update map to show answered status
        }
    });
    
    document.addEventListener('input', e => {
        if (e.target.name && e.target.name.startsWith('soal_')) {
            updateMap(); // Update map to show answered status
        }
    });
    
    // Listen to auto save status changes
    document.addEventListener('questionStatusChanged', (e) => {
        const { soalId, status } = e.detail;
        updateQuestionIndicator(soalId, status);
    });
    
    window.addEventListener('beforeunload', e => {
        if (window.examData && window.examData.isStarted && timeLeft > 0) {
            e.preventDefault();
            e.returnValue = '';
        }
    });
}

function go(n) {
    if (n < 1 || n > window.examData.totalQuestions) return;
    
    const currentCard = document.querySelector(`.question-card[data-question="${currentQuestion}"]`);
    if (currentCard) currentCard.classList.remove('active');
    
    currentQuestion = n;
    
    const newCard = document.querySelector(`.question-card[data-question="${currentQuestion}"]`);
    if (newCard) newCard.classList.add('active');
    
    updateMap();
    updateNavButtons();
    window.scrollTo({
        top: 0,
        behavior: 'smooth'
    });
}

function updateNavButtons() {
    const btnPrev = document.getElementById('btn-prev');
    const btnNext = document.getElementById('btn-next');
    
    if (btnPrev) btnPrev.disabled = currentQuestion === 1;
    if (btnNext) btnNext.disabled = currentQuestion === window.examData.totalQuestions;
}

function updateMap() {
    document.querySelectorAll('#question-map .q-btn').forEach(btn => {
        const q = +btn.dataset.q;
        // Remove status classes tapi pertahankan saved class jika ada
        btn.classList.remove('current', 'answered', 'flagged', 'error', 'saving');
        
        if (q === currentQuestion) {
            btn.classList.add('current');
        } else if (isAnswered(q)) {
            // Hanya tambah answered jika belum ada saved class
            if (!btn.classList.contains('saved')) {
                btn.classList.add('answered');
            }
        }
        
        if (flagged.has(q)) {
            btn.classList.add('flagged');
        }
    });
}

/**
 * Initialize auto save indicators
 */
function initAutoSaveIndicators() {
    // Semua indikator sudah ada di HTML, tidak perlu create lagi
    // Set initial state to idle
    if (autoSaveManager) {
        autoSaveManager.showGlobalSaveStatus('idle');
    }
}

/**
 * Show legacy status (for backward compatibility)
 */
function showSaved() {
    if (autoSaveManager) {
        autoSaveManager.showGlobalSaveStatus('saved');
    }
}

function showUnsaved() {
    if (autoSaveManager) {
        autoSaveManager.showGlobalSaveStatus('loading');
    }
}

/**
 * Update question indicator based on auto save status
 */
function updateQuestionIndicator(soalId, status) {
    // Find question element
    const questionElement = document.querySelector(`input[name="soal_${soalId}"], textarea[name="soal_${soalId}"]`);
    if (!questionElement) return;
    
    const questionCard = questionElement.closest('.question-card');
    if (!questionCard) return;
    
    const questionNumber = questionCard.dataset.question;
    const mapButton = document.querySelector(`#question-map .q-btn[data-q="${questionNumber}"]`);
    
    if (mapButton) {
        // Remove existing status classes
        mapButton.classList.remove('saving', 'saved', 'error');
        
        // Add appropriate class based on status
        switch (status) {
            case 'saving':
                mapButton.classList.add('saving');
                break;
            case 'saved':
                mapButton.classList.add('saved');
                break;
            case 'error':
                mapButton.classList.add('error');
                break;
        }
    }
}

function isAnswered(q) {
    const card = document.querySelector(`.question-card[data-question="${q}"]`);
    if (!card) return false;
    const r = card.querySelector('input[type=radio]:checked');
    const t = card.querySelector('textarea');
    return (r !== null) || (t && t.value.trim().length > 0);
}

function getCurrentSoalId() {
    const card = document.querySelector(`.question-card[data-question="${currentQuestion}"]`);
    if (!card) return null;
    const el = card.querySelector('input[name^="soal_"],textarea[name^="soal_"]');
    return el ? el.name.replace('soal_', '') : null;
}

function getCurrentAnswer() {
    const card = document.querySelector(`.question-card[data-question="${currentQuestion}"]`);
    if (!card) return '';
    const r = card.querySelector('input[type=radio]:checked');
    if (r) return r.value;
    const t = card.querySelector('textarea');
    return t ? t.value : '';
}

function saveCurrent(showNotify) {
    const soalId = getCurrentSoalId();
    if (!soalId) return;
    const fd = new FormData();
    fd.append('action', 'submit_answer');
    fd.append('ujian_siswa_id', window.examData.ujianSiswaId);
    fd.append('soal_id', soalId);
    fd.append('jawaban', getCurrentAnswer());
    fetch('kerjakan-ujian.php', {
            method: 'POST',
            body: fd
        })
        .then(r => r.json())
        .then(d => {
            latestSaveSuccess = true;
            unsaved.delete(currentQuestion);
            showSaved();
            updateMap();
            if (showNotify) notify('Jawaban disimpan', 'success');
        })
        .catch(() => {
            latestSaveSuccess = false;
            showUnsaved();
            if (showNotify) notify('Gagal menyimpan', 'error');
        });
}

function startTimer() {
    if (!window.examData) return;
    
    timeLeft = window.examData.duration;
    const el1 = document.getElementById('timer');
    const el2 = document.getElementById('timer-top');
    
    examTimer = setInterval(() => {
        timeLeft--;
        if (timeLeft < 0) timeLeft = 0;
        const h = Math.floor(timeLeft / 3600);
        const m = Math.floor((timeLeft % 3600) / 60);
        const s = timeLeft % 60;
        const str = `${String(h).padStart(2,'0')}:${String(m).padStart(2,'0')}:${String(s).padStart(2,'0')}`;
        if (el1) el1.textContent = str;
        if (el2) el2.textContent = str;
        if (timeLeft === 300) notify('Sisa waktu 5 menit', 'warning');
        if (timeLeft === 0) {
            clearInterval(examTimer);
            autoFinish();
        }
    }, 1000);
}

function startAutoSave() {
    // Legacy auto save timer for compatibility
    // Now mainly used for periodic status updates
    autoSaveTimer = setInterval(() => {
        // Update map and global status
        updateMap();
        
        // Check if we have unsaved changes and update status accordingly
        const hasUnsaved = Array.from(unsaved).some(q => isAnswered(q));
        if (hasUnsaved) {
            showUnsaved();
        } else {
            showSaved();
        }
    }, 10000); // Check every 10 seconds
}

// Save all unsaved answers
function saveUnsavedAnswers() {
    unsaved.forEach(questionNum => {
        if (questionNum !== currentQuestion) {
            const card = document.querySelector(`.question-card[data-question="${questionNum}"]`);
            if (card && isAnswered(questionNum)) {
                // Get soal_id and answer for this question
                const el = card.querySelector('input[name^="soal_"],textarea[name^="soal_"]');
                if (!el) return;
                
                const soalId = el.name.replace('soal_', '');
                let jawaban = '';
                
                const radioChecked = card.querySelector('input[type=radio]:checked');
                const textarea = card.querySelector('textarea');
                
                if (radioChecked) {
                    jawaban = radioChecked.value;
                } else if (textarea) {
                    jawaban = textarea.value;
                }
                
                if (jawaban.trim()) {
                    // Save this answer
                    const fd = new FormData();
                    fd.append('action', 'submit_answer');
                    fd.append('ujian_siswa_id', window.examData.ujianSiswaId);
                    fd.append('soal_id', soalId);
                    fd.append('jawaban', jawaban);
                    
                    fetch('kerjakan-ujian.php', {
                        method: 'POST',
                        body: fd
                    }).then(r => r.json()).then(d => {
                        if (d.success) {
                            unsaved.delete(questionNum);
                        }
                    }).catch(e => {
                        console.error('Auto-save error for question', questionNum, e);
                    });
                }
            }
        }
    });
}

function toggleFlag() {
    if (flagged.has(currentQuestion)) flagged.delete(currentQuestion);
    else flagged.add(currentQuestion);
    updateMap();
}

function showSaved() {
    const savedEl = document.getElementById('save-status');
    const unsavedEl = document.getElementById('save-status-unsaved');
    if (savedEl) savedEl.classList.remove('hidden');
    if (unsavedEl) unsavedEl.classList.add('hidden');
}

function showUnsaved() {
    const savedEl = document.getElementById('save-status');
    const unsavedEl = document.getElementById('save-status-unsaved');
    if (savedEl) savedEl.classList.add('hidden');
    if (unsavedEl) unsavedEl.classList.remove('hidden');
}

function showFinishModal() {
    const modal = document.getElementById('finishModal');
    if (modal) modal.classList.remove('hidden');
}

function hideFinishModal() {
    const modal = document.getElementById('finishModal');
    if (modal) modal.classList.add('hidden');
}

function finishExam() {
    console.log('Starting finishExam...', window.examData.ujianSiswaId);
    
    // First, force save all answers using auto save manager
    if (autoSaveManager) {
        autoSaveManager.forceSaveAll().then((success) => {
            if (success) {
                console.log('All answers saved, proceeding to finish exam');
                proceedToFinishExam();
            } else {
                console.warn('Some answers may not have been saved, but proceeding anyway');
                proceedToFinishExam();
            }
        }).catch((error) => {
            console.error('Error saving answers before finish:', error);
            proceedToFinishExam(); // Still proceed even if save fails
        });
    } else {
        // Fallback to old method if auto save manager not available
        saveAllAnswers().then(() => {
            proceedToFinishExam();
        }).catch((error) => {
            console.error('Error saving answers:', error);
            proceedToFinishExam();
        });
    }
}

function proceedToFinishExam() {
    // Now finish the exam
    const fd = new FormData();
    fd.append('action', 'submit_answer');
    fd.append('ujian_siswa_id', window.examData.ujianSiswaId);
    fd.append('soal_id', '0');
    fd.append('jawaban', '');
    fd.append('finish_exam', '1');

    console.log('Sending finish exam request...');
    fetch('kerjakan-ujian.php', {
            method: 'POST',
            body: fd
        })
        .then(response => {
            console.log('Response status:', response.status);
            console.log('Response headers:', response.headers.get('Content-Type'));

            // Check if response is actually JSON
            const contentType = response.headers.get('Content-Type');
            if (!contentType || !contentType.includes('application/json')) {
                throw new Error('Response is not JSON. Content-Type: ' + contentType);
            }

            return response.text(); // Get as text first to debug
        })
        .then(text => {
            console.log('Raw response text:', text);
            try {
                const data = JSON.parse(text);
                console.log('Parsed JSON response:', data);

                if (data.success) {
                    console.log('Ujian berhasil diselesaikan, redirecting...');
                    window.location = 'ujian-user.php?finished=1';
                } else {
                    console.error('Gagal menyelesaikan ujian:', data.message);
                    alert('Gagal menyelesaikan ujian: ' + data.message);
                    window.location = 'ujian-user.php';
                }
            } catch (parseError) {
                console.error('JSON parse error:', parseError);
                console.error('Raw response that failed to parse:', text);
                alert('Server returned invalid response. Check console for details.');
                window.location = 'ujian-user.php';
            }
        })
        .catch(error => {
            console.error('Finish exam error:', error);
            alert('Terjadi kesalahan saat menyelesaikan ujian: ' + error.message);
            // Tetap redirect meskipun ada error
            window.location = 'ujian-user.php';
        });
}

// Save all answers before finishing exam
function saveAllAnswers() {
    return new Promise((resolve, reject) => {
        const savePromises = [];
        
        for (let q = 1; q <= window.examData.totalQuestions; q++) {
            const card = document.querySelector(`.question-card[data-question="${q}"]`);
            if (!card) continue;
            
            // Get soal_id from form elements
            const el = card.querySelector('input[name^="soal_"],textarea[name^="soal_"]');
            if (!el) continue;
            
            const soalId = el.name.replace('soal_', '');
            let jawaban = '';
            
            // Get answer based on question type
            const radioChecked = card.querySelector('input[type=radio]:checked');
            const textarea = card.querySelector('textarea');
            
            if (radioChecked) {
                jawaban = radioChecked.value;
            } else if (textarea && textarea.value.trim()) {
                jawaban = textarea.value.trim();
            } else {
                // Skip empty answers
                continue;
            }
            
            // Create save promise for this answer
            const fd = new FormData();
            fd.append('action', 'submit_answer');
            fd.append('ujian_siswa_id', window.examData.ujianSiswaId);
            fd.append('soal_id', soalId);
            fd.append('jawaban', jawaban);
            
            const savePromise = fetch('kerjakan-ujian.php', {
                method: 'POST',
                body: fd
            }).then(response => response.json()).then(data => {
                if (!data.success) {
                    throw new Error(`Failed to save answer for question ${q}: ${data.message || 'Unknown error'}`);
                }
            });
            
            savePromises.push(savePromise);
        }
        
        if (savePromises.length === 0) {
            resolve();
            return;
        }
        
        Promise.all(savePromises)
            .then(() => {
                console.log(`Successfully saved ${savePromises.length} answers`);
                resolve();
            })
            .catch(error => {
                console.error('Error saving some answers:', error);
                reject(error);
            });
    });
}

function autoFinish() {
    notify('Waktu habis! Menyelesaikan ujian...', 'warning');
    setTimeout(finishExam, 1500);
}

function notify(msg, type) {
    const n = document.createElement('div');
    n.className = 'fixed top-4 right-4 z-50 px-4 py-3 rounded-lg shadow text-white text-sm font-medium ' + (type === 'success' ? 'bg-green-600' : type === 'error' ? 'bg-red-600' : type === 'warning' ? 'bg-amber-500' : 'bg-blue-600');
    n.textContent = msg;
    document.body.appendChild(n);
    setTimeout(() => n.remove(), 2500);
}