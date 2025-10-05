<?php
session_start();
require_once '../logic/koneksi.php';

// Check if user is logged in
if (!isset($_SESSION['user'])) {
    header('Location: ../../login.php');
    exit();
}

$currentPage = 'help';
$userRole = $_SESSION['user']['role'];

// Get article ID from URL
$articleId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$articleId) {
    header('Location: help-articles.php');
    exit();
}

// Get specific article based on user role and article ID
try {
    $stmt = $pdo->prepare("
        SELECT * FROM ai_information 
        WHERE id = ? AND (target_role = ? OR target_role = 'all')
    ");
    $stmt->execute([$articleId, $userRole]);
    $article = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$article) {
        header('Location: help-articles.php?error=not_found');
        exit();
    }
} catch (PDOException $e) {
    error_log("Error fetching article: " . $e->getMessage());
    header('Location: help-articles.php?error=database');
    exit();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php require '../../assets/head.php'; ?>
    <title><?php echo htmlspecialchars($article['title']); ?> - Bantuan Edupoint</title>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Tabler Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons@1.119.0/icons.css">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../css/style.css">
    
    <style>
        .bg-orange {
            background-color: #f97316;
        }
        .text-orange {
            color: #f97316;
        }
        .border-orange {
            border-color: #f97316;
        }
        
        /* Article Content Styling */
        .article-content {
            line-height: 1.8;
            font-size: 1.1rem;
            color: #1f2937;
        }
        .article-content h1, .article-content h2, .article-content h3, .article-content h4, .article-content h5, .article-content h6 {
            margin-top: 2rem;
            margin-bottom: 1rem;
            font-weight: 700;
            color: #111827;
        }
        .article-content h1 { font-size: 2.25rem; color: #111827; }
        .article-content h2 { font-size: 1.875rem; color: #111827; }
        .article-content h3 { font-size: 1.5rem; color: #1f2937; }
        .article-content h4 { font-size: 1.25rem; color: #1f2937; }
        .article-content h5 { font-size: 1.125rem; color: #374151; }
        .article-content h6 { font-size: 1rem; color: #374151; }
        
        .article-content p {
            margin-bottom: 1.25rem;
            color: #1f2937;
            font-weight: 400;
        }
        
        .article-content ul, .article-content ol {
            margin-bottom: 1.25rem;
            margin-left: 2rem;
            color: #1f2937;
        }
        
        .article-content li {
            margin-bottom: 0.5rem;
            line-height: 1.7;
            color: #1f2937;
            font-weight: 400;
        }
        
        .article-content ul li {
            list-style-type: disc;
        }
        
        .article-content ol li {
            list-style-type: decimal;
        }
        
        .article-content img {
            max-width: 100%;
            height: auto;
            margin: 2rem auto;
            border-radius: 0.75rem;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
            display: block;
        }
        
        .article-content blockquote {
            border-left: 4px solid #f97316;
            background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
            padding: 1.5rem 2rem;
            margin: 2rem 0;
            border-radius: 0.75rem;
            font-style: italic;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }
        
        .article-content code {
            background: #f3f4f6;
            padding: 0.25rem 0.5rem;
            border-radius: 0.375rem;
            font-family: 'Monaco', 'Courier New', monospace;
            font-size: 0.875rem;
            color: #dc2626;
        }
        
        .article-content pre {
            background: #1f2937;
            color: #f9fafb;
            padding: 1.5rem;
            border-radius: 0.75rem;
            overflow-x: auto;
            margin: 2rem 0;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }
        
        .article-content pre code {
            background: transparent;
            padding: 0;
            color: #f9fafb;
        }
        
        .article-content table {
            width: 100%;
            margin: 2rem 0;
            border-collapse: collapse;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            border-radius: 0.75rem;
            overflow: hidden;
        }
        
        .article-content th, .article-content td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .article-content th {
            background: #f97316;
            color: white;
            font-weight: 600;
        }
        
        .article-content tr:nth-child(even) {
            background: #f9fafb;
        }
        
        .article-content a {
            color: #f97316;
            text-decoration: underline;
            font-weight: 500;
        }
        
        .article-content a:hover {
            color: #ea580c;
        }
        
        .article-content strong {
            font-weight: 700;
            color: #111827;
        }
        
        .article-content em {
            font-style: italic;
            color: #374151;
            font-weight: 400;
        }
        
        .back-button {
            transition: all 0.3s ease;
        }
        
        .back-button:hover {
            transform: translateX(-4px);
        }
        
        .print-button:hover {
            background: #f97316;
            color: white;
        }
        
        /* Header text styling for better contrast */
        .header-gradient {
            background: linear-gradient(135deg, #f97316 0%, #ea580c 100%);
        }
        
        .header-text {
            text-shadow: 0 1px 3px rgba(0, 0, 0, 0.3);
        }
        
        .header-badge {
            background: rgba(255, 255, 255, 0.3) !important;
            color: white !important;
            font-weight: 600 !important;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .article-title {
            color: white !important;
            font-weight: 800 !important;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
        }
        
        .article-description {
            color: rgba(255, 255, 255, 0.95) !important;
            font-weight: 500 !important;
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.2);
        }
        
        .article-meta {
            color: rgba(255, 255, 255, 0.9) !important;
            font-weight: 500 !important;
        }
    </style>
</head>
<body class="bg-gray-50">
    <?php include '../component/sidebar.php'; ?>
    
    <div class="ml-0 md:ml-64 transition-all duration-300">
        <!-- Header -->
        <div class="header-gradient text-white">
            <div class="max-w-4xl mx-auto px-6 py-8">
                <!-- Back Button -->
                <div class="mb-6">
                    <a href="help-articles.php" class="back-button inline-flex items-center text-white hover:text-orange-100 transition-colors font-medium header-text">
                        <i class="ti ti-arrow-left mr-2 text-white"></i>
                        <span>Kembali ke Pusat Bantuan</span>
                    </a>
                </div>
                
                <!-- Article Header -->
                <div class="mb-6">
                    <div class="flex items-center space-x-3 mb-4">
                        <span class="header-badge inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold">
                            <i class="<?php
                                echo $article['target_role'] === 'guru' ? 'ti ti-school' : 
                                    ($article['target_role'] === 'siswa' ? 'ti ti-user' : 'ti ti-users');
                            ?> mr-1 text-white"></i>
                            <?php echo $article['target_role'] === 'all' ? 'Semua Pengguna' : ucfirst($article['target_role']); ?>
                        </span>
                    </div>
                    
                    <h1 class="article-title text-3xl md:text-4xl mb-4 leading-tight">
                        <?php echo htmlspecialchars($article['title']); ?>
                    </h1>
                    
                    <?php if (!empty($article['description'])): ?>
                        <p class="article-description text-xl leading-relaxed">
                            <?php echo htmlspecialchars($article['description']); ?>
                        </p>
                    <?php endif; ?>
                </div>
                
                <!-- Article Meta -->
                <div class="flex flex-wrap items-center gap-4 text-sm article-meta">
                    <div class="flex items-center">
                        <i class="ti ti-calendar mr-2 text-white"></i>
                        <span>Dibuat: <?php echo date('d F Y, H:i', strtotime($article['created_at'])); ?></span>
                    </div>
                    <?php if ($article['updated_at'] !== $article['created_at']): ?>
                        <div class="flex items-center">
                            <i class="ti ti-clock mr-2 text-white"></i>
                            <span>Diperbarui: <?php echo date('d F Y, H:i', strtotime($article['updated_at'])); ?></span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Article Content -->
        <div class="max-w-4xl mx-auto px-6 py-8">
            <!-- Main Article Content without card wrapper -->
            <div class="article-content prose max-w-none bg-white px-8 md:px-12 py-8 md:py-12">
                <?php 
                // Display the article content with HTML formatting
                $content = $article['content'];
                
                // If content is plain text, convert line breaks to HTML
                if (strip_tags($content) === $content) {
                    $content = nl2br(htmlspecialchars($content));
                }
                
                echo $content;
                ?>
            </div>
            
            <!-- Feedback Card -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden mt-8">
                <div class="px-8 md:px-12 py-6">
                    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                        <div class="text-sm text-gray-700">
                            <p class="mb-1 text-lg font-semibold text-gray-800">
                                Artikel ini membantu Anda?
                            </p>
                            <p class="text-gray-600">Berikan feedback untuk membantu kami meningkatkan kualitas artikel.</p>
                        </div>
                        
                        <div class="flex space-x-3">
                            <button onclick="likeArticle()" id="likeBtn" class="like-button px-6 py-3 border border-green-300 text-green-700 rounded-lg transition-all duration-200 flex items-center hover:bg-green-50 hover:border-green-400">
                                <i class="ti ti-thumb-up mr-2"></i>
                                <span id="likeText">Membantu</span>
                                <span id="likeCount" class="ml-2 bg-green-100 text-green-800 px-2 py-0.5 rounded-full text-xs font-medium">0</span>
                            </button>
                            <button onclick="dislikeArticle()" id="dislikeBtn" class="dislike-button px-6 py-3 border border-red-300 text-red-700 rounded-lg transition-all duration-200 flex items-center hover:bg-red-50 hover:border-red-400">
                                <i class="ti ti-thumb-down mr-2"></i>
                                <span id="dislikeText">Kurang Membantu</span>
                                <span id="dislikeCount" class="ml-2 bg-red-100 text-red-800 px-2 py-0.5 rounded-full text-xs font-medium">0</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Related Articles or Navigation -->
            <div class="mt-8">
                <div class="text-center">
                    <a href="help-articles.php" class="inline-flex items-center px-6 py-3 bg-white border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors">
                        <i class="ti ti-arrow-left mr-2"></i>
                        Lihat Artikel Bantuan Lainnya
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script>
        const articleId = <?php echo $article['id']; ?>;
        let userFeedback = null;
        
        // Load feedback data on page load
        document.addEventListener('DOMContentLoaded', function() {
            loadFeedbackData();
            
            // Smooth scrolling for anchor links
            const links = document.querySelectorAll('a[href^="#"]');
            links.forEach(link => {
                link.addEventListener('click', function(e) {
                    e.preventDefault();
                    const target = document.querySelector(this.getAttribute('href'));
                    if (target) {
                        target.scrollIntoView({ behavior: 'smooth' });
                    }
                });
            });
        });
        
        function loadFeedbackData() {
            // Use simplified endpoint
            fetch(`../api/feedback-simple.php?article_id=${articleId}`)
                .then(response => {
                    console.log('Response status:', response.status);
                    console.log('Response headers:', response.headers.get('content-type'));
                    
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    
                    return response.text(); // Get as text first
                })
                .then(text => {
                    console.log('Raw response:', text);
                    
                    try {
                        const data = JSON.parse(text);
                        console.log('Parsed data:', data);
                        
                        if (data.success) {
                            document.getElementById('likeCount').textContent = data.likes || 0;
                            document.getElementById('dislikeCount').textContent = data.dislikes || 0;
                            userFeedback = data.user_feedback;
                            
                            console.log('Loaded feedback data:', { likes: data.likes, dislikes: data.dislikes, userFeedback });
                            
                            // Update button states
                            updateButtonStates();
                        } else {
                            console.error('API returned error:', data.error);
                            showNotification('Error loading feedback: ' + data.error, 'error');
                        }
                    } catch (jsonError) {
                        console.error('JSON parse error:', jsonError);
                        console.error('Response text:', text);
                        showNotification('Error parsing response data', 'error');
                    }
                })
                .catch(error => {
                    console.error('Error loading feedback data:', error);
                    showNotification('Failed to load feedback data', 'error');
                });
        }
        
        function updateButtonStates() {
            const likeBtn = document.getElementById('likeBtn');
            const dislikeBtn = document.getElementById('dislikeBtn');
            
            // Reset button states
            likeBtn.classList.remove('bg-green-100', 'border-green-500');
            dislikeBtn.classList.remove('bg-red-100', 'border-red-500');
            
            // Apply active states based on user feedback
            if (userFeedback === 'like') {
                likeBtn.classList.add('bg-green-100', 'border-green-500');
                document.getElementById('likeText').textContent = 'Membantu ✓';
            } else {
                document.getElementById('likeText').textContent = 'Membantu';
            }
            
            if (userFeedback === 'dislike') {
                dislikeBtn.classList.add('bg-red-100', 'border-red-500');
                document.getElementById('dislikeText').textContent = 'Kurang Membantu ✓';
            } else {
                document.getElementById('dislikeText').textContent = 'Kurang Membantu';
            }
        }
        
        function likeArticle() {
            submitFeedback('like');
        }
        
        function dislikeArticle() {
            submitFeedback('dislike');
        }
        
        function submitFeedback(action) {
            const button = action === 'like' ? document.getElementById('likeBtn') : document.getElementById('dislikeBtn');
            const originalText = button.querySelector('span').textContent;
            
            // Show loading state
            button.disabled = true;
            button.querySelector('span').textContent = 'Loading...';
            
            fetch('../api/feedback-simple.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    article_id: articleId,
                    action: action
                })
            })
            .then(response => {
                console.log('Submit response status:', response.status);
                
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                
                return response.text(); // Get as text first
            })
            .then(text => {
                console.log('Submit raw response:', text);
                
                try {
                    const data = JSON.parse(text);
                    if (data.success) {
                        // Update counts
                        document.getElementById('likeCount').textContent = data.likes || 0;
                        document.getElementById('dislikeCount').textContent = data.dislikes || 0;
                        userFeedback = data.user_feedback;
                        
                        // Update button states
                        updateButtonStates();
                        
                        // Show success message
                        showNotification('Terima kasih atas feedback Anda!', 'success');
                    } else {
                        showNotification('Error: ' + data.error, 'error');
                    }
                } catch (jsonError) {
                    console.error('JSON parse error:', jsonError);
                    console.error('Response text:', text);
                    showNotification('Error parsing response', 'error');
                }
            })
            .catch(error => {
                console.error('Error submitting feedback:', error);
                showNotification('Terjadi kesalahan saat mengirim feedback', 'error');
            })
            .finally(() => {
                // Reset button state
                button.disabled = false;
                if (!userFeedback || userFeedback !== action) {
                    button.querySelector('span').textContent = originalText;
                }
            });
        }
        
        function showNotification(message, type = 'info') {
            const notification = document.createElement('div');
            notification.className = `fixed top-4 right-4 px-4 py-2 rounded-lg text-white z-50 ${
                type === 'success' ? 'bg-green-500' : 
                type === 'error' ? 'bg-red-500' : 'bg-blue-500'
            }`;
            notification.textContent = message;
            
            document.body.appendChild(notification);
            
            setTimeout(() => {
                notification.remove();
            }, 3000);
        }
    </script>
</body>
</html>