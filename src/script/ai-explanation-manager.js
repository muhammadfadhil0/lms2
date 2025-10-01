/**
 * AI Explanation Popup Manager
 * Handles the AI explanation popup for posts (simple popup like class delete)
 */
class AiExplanationManager {
    constructor() {
        this.popup = null;
        this.currentPostId = null;
        this.currentButton = null;
        this.retryCallback = null;
        this.init();
    }

    init() {
        // Try to find the popup element
        this.popup = document.getElementById('ai-explanation-popup');
        if (!this.popup) {
            console.error('AI explanation popup element not found. Available elements:', document.querySelectorAll('[id*="ai"]'));
            // Try alternative selectors
            this.popup = document.querySelector('#ai-explanation-popup');
            if (!this.popup) {
                console.error('Still not found. DOM might not be ready yet.');
                return;
            }
        }
        console.log('AI popup element found:', this.popup);
        this.bindEvents();
    }

    bindEvents() {
        // Use more specific event delegation for dynamically created buttons
        document.body.addEventListener('click', (e) => {
            // Check if clicked element or its parent has the ai-explain-btn class
            const aiButton = e.target.closest('.ai-explain-btn');
            if (aiButton) {
                e.preventDefault();
                e.stopPropagation();
                const postId = aiButton.getAttribute('data-post-id');
                console.log('AI button clicked:', postId, aiButton);
                if (postId) {
                    this.showExplanation(postId, aiButton);
                } else {
                    console.error('No post ID found on button');
                }
                return;
            }

            // Close popup when clicking outside (but not on AI button)
            if (!e.target.closest('#ai-explanation-popup')) {
                this.close();
            }
        });

        // Close popup on scroll or resize
        window.addEventListener('scroll', () => {
            this.close();
        });
        
        window.addEventListener('resize', () => {
            this.close();
        });

        // Close popup with Escape key
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && this.popup && !this.popup.classList.contains('hidden')) {
                this.close();
            }
        });
    }

    async showExplanation(postId, buttonElement) {
        console.log('showExplanation called with:', { postId, buttonElement });
        
        if (!postId) {
            console.error('Post ID is required');
            return;
        }

        if (!buttonElement) {
            console.error('Button element is required');
            return;
        }

        this.currentPostId = postId;
        this.currentButton = buttonElement;
        
        console.log('About to position and show popup');
        this.positionAndShow();
        this.showLoading();

        try {
            // Get post content first
            const post = await this.getPostContent(postId);
            if (!post) {
                throw new Error('Post not found');
            }

            // Get AI explanation
            const explanation = await this.getAiExplanation(post);
            this.displayExplanation(post, explanation);

        } catch (error) {
            console.error('Error getting AI explanation:', error);
            this.showError();
        }
    }

    positionAndShow() {
        if (!this.popup || !this.currentButton) {
            console.error('Popup or button not available');
            return;
        }

        // Close any other popups first (but don't reset current state)
        this.hideAllPopups();

        // Calculate position similar to class dropdown
        const buttonRect = this.currentButton.getBoundingClientRect();
        const popupWidth = 320; // w-80 = 20rem = 320px
        const popupHeight = this.popup.offsetHeight || 200; // estimate

        let left = buttonRect.right - popupWidth;
        let top = buttonRect.bottom + 8;

        // Adjust if popup goes off-screen
        if (left < 8) left = 8;
        if (left + popupWidth > window.innerWidth - 8) {
            left = window.innerWidth - popupWidth - 8;
        }
        if (top + popupHeight > window.innerHeight - 8) {
            top = buttonRect.top - popupHeight - 8;
        }

        this.popup.style.left = left + 'px';
        this.popup.style.top = top + 'px';
        this.popup.classList.remove('hidden');
    }

    hideAllPopups() {
        // Hide popup without resetting state
        if (this.popup) {
            this.popup.classList.add('hidden');
        }
    }

    async getPostContent(postId) {
        try {
            const response = await fetch('../api/get-post-content.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ post_id: postId })
            });

            const data = await response.json();
            
            if (!data.success) {
                throw new Error(data.message || 'Failed to get post content');
            }

            console.log('Post data received:', data.post);
            return data.post;
        } catch (error) {
            console.error('Error fetching post content:', error);
            throw error;
        }
    }

    // Get configured API key for current page
    async getPageApiKey() {
        try {
            // Determine current page type
            let pageName = this.getCurrentPageName();
            
            console.log('Getting API key for page:', pageName);
            
            const response = await fetch('../logic/api-switcher-endpoint.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'get_page_api_key',
                    page: pageName
                })
            });
            
            const result = await response.json();
            
            if (result.success && result.data) {
                console.log('API key retrieved for page:', result.data);
                return result.data;
            } else {
                console.warn('No API key configured for page, using default');
                return null;
            }
        } catch (error) {
            console.error('Error getting page API key:', error);
            return null;
        }
    }
    
    // Determine current page name for API key selection
    getCurrentPageName() {
        const path = window.location.pathname;
        const filename = path.split('/').pop();
        
        if (filename === 'beranda-user.php') {
            return 'ai-explanation-beranda';
        } else if (filename === 'kelas-user.php') {
            return 'ai-explanation-kelas-user';
        } else if (filename === 'kelas-guru.php') {
            return 'ai-explanation-kelas-guru';
        } else {
            // Default fallback
            return 'ai-explanation-beranda';
        }
    }

    async getAiExplanation(post) {
        try {
            // ⭐ SUPER DEBUG MODE ACTIVATED! ⭐
            console.log('⭐ AI API CALL STARTED ⭐');
            console.log('⭐ Post object:', post);
            console.log('⭐ Post object keys:', Object.keys(post));
            console.log('⭐ Post ID (postinganId):', post.postinganId);
            console.log('⭐ Post ID (id):', post.id);
            console.log('⭐ Post ID (postId):', post.postId);
            
            // Check berbagai kemungkinan nama field ID
            let actualPostId = post.postinganId || post.id || post.postId;
            console.log('⭐ Actual Post ID found:', actualPostId);
            
            if (!actualPostId) {
                console.error('⭐ CRITICAL: No valid post ID found!');
                throw new Error('Post ID not found in post object');
            }
            
            // Get configured API key for this page
            const apiKeyData = await this.getPageApiKey();
            
            const requestData = { 
                post_id: actualPostId,
                use_configured_api: apiKeyData ? true : false,
                api_key_id: apiKeyData ? apiKeyData.id : null
            };
            console.log('⭐ Request data:', requestData);
            console.log('⭐ Post ID value:', actualPostId);
            console.log('⭐ Post ID type:', typeof actualPostId);
            console.log('⭐ API Key data:', apiKeyData);
            
            const jsonBody = JSON.stringify(requestData);
            console.log('⭐ Request JSON:', jsonBody);
            console.log('⭐ JSON length:', jsonBody.length);
            
            // Call real AI API using Groq like in pingo
            const apiUrl = '../api/ai-post-explanation.php';
            console.log('⭐ Making fetch request to:', apiUrl);
            console.log('⭐ Current page URL:', window.location.href);
            console.log('⭐ Resolved API URL:', new URL(apiUrl, window.location.href).href);
            
            const fetchOptions = {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: jsonBody
            };
            
            console.log('⭐ Fetch options:', fetchOptions);
            console.log('⭐ Body being sent:', fetchOptions.body);
            
            const response = await fetch(apiUrl, fetchOptions);

            console.log('⭐ Response received!');
            console.log('⭐ Response status:', response.status);
            console.log('⭐ Response headers:', Object.fromEntries(response.headers));
            
            // Check if response is ok
            if (!response.ok) {
                console.error('⭐ Response not OK!', response.status, response.statusText);
            }
            
            // Get response text first to debug
            const responseText = await response.text();
            console.log('⭐ Raw response text:', responseText);
            
            // Try to parse JSON
            let result;
            try {
                result = JSON.parse(responseText);
                console.log('⭐ Parsed JSON successfully:', result);
            } catch (jsonError) {
                console.error('⭐ JSON PARSE ERROR!', jsonError);
                console.error('⭐ Failed to parse response:', responseText);
                throw new Error('Invalid JSON response from server: ' + responseText.substring(0, 100));
            }
            
            if (!result.success) {
                // Check if it's an authentication error - handle gracefully
                if (result.error && result.error.includes('Unauthorized')) {
                    console.warn('Not authenticated, falling back to mock explanation');
                    return this.generateMockExplanation(post);
                }
                throw new Error(result.error || 'Failed to generate AI explanation');
            }

            // Return AI-generated explanation in expected format with assignment data
            return {
                summary: post.konten ? post.konten.substring(0, 150) + (post.konten.length > 150 ? '...' : '') : 'Postingan tanpa teks',
                analysis: result.explanation.analysis,
                keyPoints: (result.explanation.keyPoints || []).slice(0, 3), // Limit to max 3 points
                postType: result.explanation.postType || 'biasa',
                urgency: result.explanation.urgency || 'low',
                isAssignment: result.explanation.postType === 'tugas'
            };

        } catch (error) {
            console.error('Error getting AI explanation:', error);
            
            // Check if it's an authentication error
            if (error.message.includes('Unauthorized')) {
                console.warn('User not logged in, using fallback explanation');
            }
            
            // Fallback to simple mock if AI fails
            console.log('Falling back to mock explanation');
            return this.generateMockExplanation(post);
        }
    }

    generateMockExplanation(post) {
        // Enhanced mock function with assignment detection
        const contentLength = post.konten ? post.konten.length : 0;
        const hasMedia = post.gambar && post.gambar.length > 0;
        const hasFiles = post.files && post.files.length > 0;
        const authorName = post.authorName || 'Pengguna';
        const className = post.namaKelas || 'kelas ini';
        const subject = 'mata pelajaran';

        let explanation = {
            summary: post.konten ? post.konten.substring(0, 150) + (contentLength > 150 ? '...' : '') : 'Postingan tanpa teks',
            analysis: '',
            keyPoints: [],
            postType: 'biasa',
            urgency: 'low',
            isAssignment: false
        };

        // ⭐ ENHANCED: Detect assignment posts
        const isAssignmentPost = this.detectAssignmentPost(post);
        if (isAssignmentPost.isAssignment) {
            explanation.postType = 'tugas';
            explanation.isAssignment = true;
            explanation.urgency = isAssignmentPost.urgency;
        }

        // AI Free Expression Analysis with Assignment Context
        if (post.konten) {
            const content = post.konten;
            
            // Let AI analyze freely based on available data including assignment context
            explanation.analysis = this.generateFreeAIAnalysis(authorName, className, subject, content, hasMedia, hasFiles, post, isAssignmentPost);
            
            // Generate key points based on AI observation (max 3)
            explanation.keyPoints = this.generateAIKeyPoints(content, hasMedia, hasFiles, isAssignmentPost).slice(0, 3);
        } else {
            // Postingan tanpa teks, AI tetap bebas berekspresi
            if (isAssignmentPost.isAssignment) {
                explanation.analysis = `${authorName} ngasih tugas tapi cuma pake file/gambar aja. Mungkin instruksinya ada di attachment yang dibagi.`;
            } else {
                explanation.analysis = `${authorName} cuma sharing gambar atau file aja nih, mungkin biar lebih jelas atau sekadar berbagi sesuatu yang menarik.`;
            }
            explanation.keyPoints = this.generateAIKeyPoints('', hasMedia, hasFiles, isAssignmentPost).slice(0, 3);
        }

        return explanation;
    }

    // ⭐ NEW: Enhanced assignment detection function
    detectAssignmentPost(post) {
        let isAssignment = false;
        let urgency = 'low';
        let assignmentData = {};

        // Check database fields first
        if (post.tipePost === 'tugas' || post.isAssignment || post.assignment_id) {
            isAssignment = true;
            
            // Check deadline urgency if available
            if (post.deadline || (post.assignmentDetails && post.assignmentDetails.deadline)) {
                const deadline = new Date(post.deadline || post.assignmentDetails.deadline);
                const now = new Date();
                const timeDiff = deadline.getTime() - now.getTime();
                const daysDiff = Math.ceil(timeDiff / (1000 * 3600 * 24));
                
                if (daysDiff < 0) {
                    urgency = 'high'; // Already passed
                } else if (daysDiff <= 1) {
                    urgency = 'high'; // Due today or tomorrow
                } else if (daysDiff <= 3) {
                    urgency = 'medium'; // Due in 2-3 days
                } else {
                    urgency = 'low'; // More than 3 days
                }
                
                assignmentData.deadline = deadline;
                assignmentData.daysLeft = daysDiff;
            }
            
            if (post.assignmentDetails) {
                assignmentData.title = post.assignmentDetails.title || post.assignmentDetails.judul;
                assignmentData.description = post.assignmentDetails.description || post.assignmentDetails.deskripsi;
                assignmentData.maxScore = post.assignmentDetails.maxScore || post.assignmentDetails.nilai_maksimal;
            }
        }

        // Check content for assignment keywords
        if (!isAssignment && post.konten) {
            const assignmentKeywords = [
                'tugas', 'deadline', 'kerjakan', 'submit', 'kumpul', 'dikumpulkan', 
                'buku', 'halaman', 'latihan', 'soal', 'pr', 'pekerjaan rumah',
                'minggu depan', 'besok', 'hari ini', 'selesaikan', 'jawab'
            ];
            
            const contentLower = post.konten.toLowerCase();
            const foundKeywords = [];
            
            for (let keyword of assignmentKeywords) {
                if (contentLower.includes(keyword)) {
                    foundKeywords.push(keyword);
                    if (!isAssignment) {
                        isAssignment = true;
                        urgency = 'medium'; // Likely assignment but no specific deadline
                    }
                }
            }
            
            assignmentData.detectedKeywords = foundKeywords;
            
            // Check for book references (buku halaman X)
            const bookMatch = contentLower.match(/buku.*?halaman\s*(\d+)/);
            if (bookMatch) {
                assignmentData.bookReference = `halaman ${bookMatch[1]}`;
            }
            
            // Check for deadline mentions
            const deadlineWords = ['deadline', 'batas', 'sampai', 'sebelum', 'minggu depan', 'besok'];
            for (let word of deadlineWords) {
                if (contentLower.includes(word)) {
                    urgency = Math.max(urgency === 'low' ? 'medium' : urgency, 'medium');
                    break;
                }
            }
        }

        return {
            isAssignment,
            urgency,
            assignmentData
        };
    }

    // AI Free Analysis Generator with Assignment Context - bebas berekspresi! (Max 50 kata)
    generateFreeAIAnalysis(authorName, className, subject, content, hasMedia, hasFiles, post, assignmentContext) {
        /* ENHANCED GUIDELINES:
           AI boleh: menganalisis konteks, menebak maksud, berekspresi bebas tentang apa yang diamati
           AI tidak boleh: menggunakan template kaku, terlalu formal, atau terlalu panjang
           Maksimal 50 kata untuk analisis utama
           BARU: Deteksi tugas dan deadline dengan konteks yang lebih kaya
        */

        const indicators = {
            question: content.includes('?') || /\b(apa|kenapa|gimana|bagaimana|bisakah|bisa ga|kapan|dimana)\b/i.test(content),
            excitement: /[!]{1,}/.test(content) || /\b(wow|keren|hebat|bagus)\b/i.test(content),
            time: /\b(pagi|siang|sore|malam|jam|waktu|besok|kemarin)\b/i.test(content),
            casual: /\b(hai|halo|guys|teman|yuk|ayo)\b/i.test(content),
            work: /\b(tugas|kerja|buat|selesai|deadline|submit)\b/i.test(content),
            check: /\b(cek|lihat|periksa|review)\b/i.test(content),
            book: /\b(buku|halaman|latihan|soal|nomor)\b/i.test(content),
            urgent: /\b(cepet|segera|sekarang|deadline|batas)\b/i.test(content)
        };

        let analysis = `${authorName} `;
        
        // ⭐ PRIORITAS TINGGI: Assignment Analysis
        if (assignmentContext.isAssignment) {
            const urgency = assignmentContext.urgency;
            const assignmentData = assignmentContext.assignmentData;
            
            if (urgency === 'high') {
                if (assignmentData.daysLeft < 0) {
                    analysis += `ngasih tugas yang deadlinenya udah lewat! Wah telat nih, mungkin ada perpanjangan.`;
                } else if (assignmentData.daysLeft <= 1) {
                    analysis += `ngasih tugas yang deadlinenya besok/hari ini! Urgent banget nih, harus langsung dikerjain.`;
                }
            } else if (urgency === 'medium') {
                if (assignmentData.daysLeft && assignmentData.daysLeft <= 3) {
                    analysis += `ngasih tugas dengan deadline ${assignmentData.daysLeft} hari lagi. Masih ada waktu tapi jangan santai-santai.`;
                } else {
                    analysis += `ngasih tugas nih. Ada beberapa hari untuk ngerjain, tapi lebih baik mulai dari sekarang.`;
                }
            } else {
                analysis += `ngasih tugas baru. Deadlinenya masih lama, tapi bagus kalau mulai dipelajari dari sekarang.`;
            }
            
            // Add book reference if detected
            if (assignmentData.bookReference) {
                analysis += ` Tugasnya dari ${assignmentData.bookReference}.`;
            }
            
        } else {
            // AI bebas menebak dan berekspresi untuk postingan biasa (dibatasi 50 kata)
            if (indicators.question && indicators.work) {
                analysis += `nanyain soal tugas nih. Kayaknya ada yang bingung atau butuh bantuan sama kerjaan.`;
            } else if (indicators.book && indicators.work) {
                analysis += `ngomongin soal buku sama latihan. Mungkin bahas materi atau tugas dari buku.`;
            } else if (indicators.question) {
                analysis += `lagi tanya sesuatu. Butuh jawaban atau penjelasan dari teman-teman sepertinya.`;
            } else if (indicators.excitement && hasMedia) {
                analysis += `excited banget! Ada file/gambar juga yang dibagi, pasti ada yang menarik.`;
            } else if (indicators.casual && indicators.time) {
                analysis += `ngobrol santai sambil nyebut waktu. Mungkin ngatur jadwal atau sekadar sapaan.`;
            } else if (indicators.check) {
                analysis += `lagi ngecek sesuatu atau minta orang lain review. Perlu konfirmasi kayaknya.`;
            } else if (hasFiles || hasMedia) {
                analysis += `sharing sesuatu! Ada file/media yang dibagi, pasti info penting.`;
            } else if (content.length < 20) {
                analysis += `komen singkat aja. Mungkin respon cepat atau sekadar nyapa.`;
            } else if (content.length > 100) {
                analysis += `pesan panjang banget! Lagi jelasin sesuatu yang detail atau penting.`;
            } else {
                analysis += `ngobrol biasa di kelas. Sharing info atau diskusi ringan.`;
            }
        }

        return analysis;
    }

    // Generate key points from AI observation with assignment context
    generateAIKeyPoints(content, hasMedia, hasFiles, assignmentContext = {}) {
        const points = [];
        
        // ⭐ ENHANCED: Assignment-specific points
        if (assignmentContext.isAssignment) {
            points.push('📝 Ini adalah postingan tugas');
            
            if (assignmentContext.urgency === 'high') {
                points.push('🔥 Deadline mendesak!');
            } else if (assignmentContext.urgency === 'medium') {
                points.push('⏰ Deadline dalam beberapa hari');
            }
            
            if (assignmentContext.assignmentData.bookReference) {
                points.push(`📚 Referensi: ${assignmentContext.assignmentData.bookReference}`);
            }
            
            if (assignmentContext.assignmentData.maxScore) {
                points.push(`🎯 Nilai maksimal: ${assignmentContext.assignmentData.maxScore}`);
            }
            
            if (assignmentContext.assignmentData.daysLeft !== undefined) {
                if (assignmentContext.assignmentData.daysLeft < 0) {
                    points.push('❌ Deadline sudah terlewat');
                } else if (assignmentContext.assignmentData.daysLeft === 0) {
                    points.push('🚨 Deadline hari ini');
                } else if (assignmentContext.assignmentData.daysLeft === 1) {
                    points.push('⚡ Deadline besok');
                } else {
                    points.push(`📅 Sisa ${assignmentContext.assignmentData.daysLeft} hari`);
                }
            }
            
            if (assignmentContext.assignmentData.detectedKeywords && assignmentContext.assignmentData.detectedKeywords.length > 0) {
                points.push(`🔍 Kata kunci: ${assignmentContext.assignmentData.detectedKeywords.join(', ')}`);
            }
        }
        
        // Standard content analysis
        if (content.includes('?')) points.push('Ada pertanyaan yang diajukan');
        if (hasMedia) points.push('Disertai dengan media/gambar');
        if (hasFiles) points.push('Ada file yang dibagikan');
        if (/[!]{2,}/.test(content)) points.push('Nada komunikasi sangat antusias');
        if (content.length > 150) points.push('Pesan yang cukup detail');
        if (/\b(buku|halaman|latihan)\b/i.test(content) && !assignmentContext.isAssignment) {
            points.push('Menyebutkan referensi buku/materi');
        }
        
        return points.length > 0 ? points.slice(0, 3) : ['Komunikasi standar di kelas'];
    }



    displayExplanation(post, explanation) {
        this.hideLoading();
        this.hideError();
        this.showContent();

        // ⭐ ENHANCED: Display post type indicator for assignments
        if (explanation.isAssignment || explanation.postType === 'tugas') {
            const postTypeSection = document.getElementById('ai-post-type');
            const postTypeIcon = document.getElementById('post-type-icon');
            const postTypeText = document.getElementById('post-type-text');
            const urgencyBadge = document.getElementById('urgency-badge');
            
            if (postTypeSection && postTypeIcon && postTypeText) {
                postTypeIcon.className = 'ti ti-clipboard-text text-blue-600 mr-2';
                postTypeText.textContent = 'Postingan Tugas';
                postTypeSection.className = 'mb-3 p-2 rounded-lg bg-blue-50 border border-blue-200';
                
                // Set urgency badge
                if (urgencyBadge) {
                    const urgency = explanation.urgency || 'low';
                    if (urgency === 'high') {
                        urgencyBadge.className = 'ml-auto px-2 py-1 rounded-full text-xs bg-red-100 text-red-700';
                        urgencyBadge.textContent = 'Urgent';
                    } else if (urgency === 'medium') {
                        urgencyBadge.className = 'ml-auto px-2 py-1 rounded-full text-xs bg-yellow-100 text-yellow-700';
                        urgencyBadge.textContent = 'Mendesak';
                    } else {
                        urgencyBadge.className = 'ml-auto px-2 py-1 rounded-full text-xs bg-green-100 text-green-700';
                        urgencyBadge.textContent = 'Normal';
                    }
                }
                
                postTypeSection.classList.remove('hidden');
            }
        } else {
            // Hide post type for regular posts
            const postTypeSection = document.getElementById('ai-post-type');
            if (postTypeSection) {
                postTypeSection.classList.add('hidden');
            }
        }

        // Populate AI analysis
        const analysisElement = document.getElementById('ai-analysis-text');
        if (analysisElement) {
            analysisElement.innerHTML = this.formatText(explanation.analysis);
        }

        // Populate key points
        if (explanation.keyPoints && explanation.keyPoints.length > 0) {
            const keyPointsSection = document.getElementById('ai-key-points');
            const keyPointsList = document.getElementById('key-points-list');
            
            if (keyPointsSection && keyPointsList) {
                keyPointsList.innerHTML = '';
                explanation.keyPoints.forEach(point => {
                    const li = document.createElement('li');
                    li.className = 'text-xs text-gray-600';
                    li.innerHTML = `• ${point}`;
                    keyPointsList.appendChild(li);
                });
                keyPointsSection.classList.remove('hidden');
            }
        }

        // ⭐ ENHANCED: Populate related topics
        if (explanation.relatedTopics && explanation.relatedTopics.length > 0) {
            const topicsSection = document.getElementById('ai-related-topics');
            const topicsList = document.getElementById('topics-list');
            
            if (topicsSection && topicsList) {
                topicsList.innerHTML = '';
                explanation.relatedTopics.forEach(topic => {
                    const span = document.createElement('span');
                    span.className = 'px-2 py-1 bg-purple-100 text-purple-700 rounded-full text-xs';
                    span.textContent = topic;
                    topicsList.appendChild(span);
                });
                topicsSection.classList.remove('hidden');
            }
        }
    }

    formatText(text) {
        // Simple text formatting for better readability
        return text
            .replace(/\n\n/g, '</p><p>')
            .replace(/\n/g, '<br>')
            .replace(/^/, '<p>')
            .replace(/$/, '</p>');
    }

    close() {
        if (this.popup) {
            this.popup.classList.add('hidden');
            this.currentPostId = null;
            this.currentButton = null;
        }
    }

    showLoading() {
        document.getElementById('ai-popup-loading')?.classList.remove('hidden');
        document.getElementById('ai-popup-error')?.classList.add('hidden');
        document.getElementById('ai-popup-content')?.classList.add('hidden');
    }

    hideLoading() {
        document.getElementById('ai-popup-loading')?.classList.add('hidden');
    }

    showError() {
        this.hideLoading();
        document.getElementById('ai-popup-error')?.classList.remove('hidden');
        document.getElementById('ai-popup-content')?.classList.add('hidden');
        
        // Set retry callback
        this.retryCallback = () => {
            if (this.currentPostId && this.currentButton) {
                this.showExplanation(this.currentPostId, this.currentButton);
            }
        };
    }

    hideError() {
        document.getElementById('ai-popup-error')?.classList.add('hidden');
    }

    showContent() {
        document.getElementById('ai-popup-content')?.classList.remove('hidden');
        // Hide optional sections initially
        document.getElementById('ai-key-points')?.classList.add('hidden');
    }

    retry() {
        if (this.retryCallback) {
            this.retryCallback();
        }
    }
}

// Global functions for popup control
function retryAiExplanation() {
    if (window.aiExplanationManager) {
        window.aiExplanationManager.retry();
    }
}

// Note: AiExplanationManager is now initialized in kelas-guru.php 
// after all other managers to ensure proper timing