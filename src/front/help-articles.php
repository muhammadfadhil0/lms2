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

// Get help articles based on user role
try {
    $stmt = $pdo->prepare("
        SELECT * FROM ai_information 
        WHERE target_role = ? OR target_role = 'all' 
        ORDER BY created_at DESC
    ");
    $stmt->execute([$userRole]);
    $articles = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $articles = [];
    error_log("Error fetching help articles: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php require '../../assets/head.php'; ?>
    <title>Bantuan - Edupoint</title>
    
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
        .article-content {
            line-height: 1.8;
        }
        .article-content h1, .article-content h2, .article-content h3 {
            margin-top: 1.5rem;
            margin-bottom: 0.75rem;
            font-weight: 600;
        }
        .article-content h1 { font-size: 1.5rem; }
        .article-content h2 { font-size: 1.25rem; }
        .article-content h3 { font-size: 1.125rem; }
        .article-content p {
            margin-bottom: 1rem;
        }
        .article-content ul, .article-content ol {
            margin-bottom: 1rem;
            margin-left: 1.5rem;
        }
        .article-content li {
            margin-bottom: 0.5rem;
        }
        .search-highlight {
            background-color: #fbbf24;
            padding: 0.125rem 0.25rem;
            border-radius: 0.25rem;
        }
        .line-clamp-2 {
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        .line-clamp-3 {
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        .article-modal-content h1, .article-modal-content h2, .article-modal-content h3 {
            margin-top: 1.5rem;
            margin-bottom: 0.75rem;
            font-weight: 600;
        }
        .article-modal-content h1 { font-size: 1.75rem; color: #1f2937; }
        .article-modal-content h2 { font-size: 1.5rem; color: #374151; }
        .article-modal-content h3 { font-size: 1.25rem; color: #4b5563; }
        .article-modal-content p {
            margin-bottom: 1rem;
            line-height: 1.7;
        }
        .article-modal-content ul, .article-modal-content ol {
            margin-bottom: 1rem;
            margin-left: 1.5rem;
            line-height: 1.7;
        }
        .article-modal-content li {
            margin-bottom: 0.5rem;
        }
        .article-modal-content img {
            max-width: 100%;
            height: auto;
            margin: 1rem 0;
            border-radius: 0.5rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }
        .article-modal-content blockquote {
            border-left: 4px solid #f97316;
            background-color: #fef3c7;
            padding: 1rem 1.5rem;
            margin: 1rem 0;
            border-radius: 0.5rem;
        }
        .article-modal-content pre {
            background-color: #f3f4f6;
            padding: 1rem;
            border-radius: 0.5rem;
            overflow-x: auto;
            margin: 1rem 0;
        }
        .article-modal-content code {
            background-color: #f3f4f6;
            padding: 0.125rem 0.25rem;
            border-radius: 0.25rem;
            font-family: 'Monaco', 'Courier New', monospace;
        }
    </style>
</head>
<body class="bg-gray-50">
    <?php include '../component/sidebar.php'; ?>
    
    <div class="ml-0 md:ml-64 transition-all duration-300">
        <!-- Header -->
        <div class="bg-white shadow-sm border-b border-gray-200 p-6">
            <div class="max-w-4xl mx-auto">
                <div class="text-center mb-8">
                    <div class="flex items-center justify-center mb-4">
                        <div class="p-4 bg-orange-100 rounded-full">
                            <i class="ti ti-help text-orange text-3xl"></i>
                        </div>
                    </div>
                    <h1 class="text-3xl font-bold text-gray-800 mb-2">Pusat Bantuan Edupoint</h1>
                    <p class="text-lg text-gray-600">Temukan jawaban atas pertanyaan Anda</p>
                </div>
                
                <!-- Search Box -->
                <div class="max-w-2xl mx-auto mb-8">
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="ti ti-search text-gray-400"></i>
                        </div>
                        <input type="text" id="searchInput" 
                            class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-transparent"
                            placeholder="Cari artikel bantuan...">
                    </div>
                </div>
            </div>
        </div>

        <!-- Content -->
        <div class="p-6">
            <div class="max-w-4xl mx-auto">
                <?php if (empty($articles)): ?>
                    <!-- No Articles State -->
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-12 text-center">
                        <div class="w-24 h-24 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-6">
                            <i class="ti ti-article text-gray-400 text-3xl"></i>
                        </div>
                        <h3 class="text-xl font-semibold text-gray-800 mb-3">Belum Ada Artikel Bantuan</h3>
                        <p class="text-gray-600 mb-6">Saat ini belum ada artikel bantuan yang tersedia untuk <?php echo $userRole === 'guru' ? 'guru' : 'siswa'; ?>.</p>
                        <p class="text-sm text-gray-500">Artikel bantuan sedang dalam proses pembuatan.</p>
                    </div>
                <?php else: ?>
                    <!-- Articles Grid -->
                    <div id="articlesContainer" class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                        <?php foreach ($articles as $article): ?>
                            <a href="article-detail.php?id=<?php echo $article['id']; ?>" 
                               class="article-card block bg-white rounded-lg shadow-sm border border-gray-200 hover:shadow-lg transition-all duration-300 transform hover:-translate-y-1 group">
                                <div class="p-6">
                                    <div class="flex items-start justify-between mb-4">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php
                                            echo $article['target_role'] === 'guru' ? 'bg-blue-100 text-blue-800' : 
                                                ($article['target_role'] === 'siswa' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800');
                                        ?>">
                                            <i class="<?php
                                                echo $article['target_role'] === 'guru' ? 'ti ti-school' : 
                                                    ($article['target_role'] === 'siswa' ? 'ti ti-user' : 'ti ti-users');
                                            ?> mr-1"></i>
                                            <?php echo $article['target_role'] === 'all' ? 'Semua' : ucfirst($article['target_role']); ?>
                                        </span>
                                        <i class="ti ti-arrow-right text-gray-400 group-hover:text-orange-500 transition-colors transform group-hover:translate-x-1"></i>
                                    </div>
                                    
                                    <h2 class="article-title text-lg font-semibold text-gray-800 mb-3 line-clamp-2 group-hover:text-orange-600 transition-colors">
                                        <?php echo htmlspecialchars($article['title']); ?>
                                    </h2>
                                    
                                    <p class="article-description text-sm text-gray-600 mb-4 line-clamp-3">
                                        <?php 
                                        $description = !empty($article['description']) ? $article['description'] : 
                                                      mb_substr(strip_tags($article['content']), 0, 120) . '...';
                                        echo htmlspecialchars($description); 
                                        ?>
                                    </p>
                                    
                                    <!-- Read More Link -->
                                    <div class="flex items-center justify-between">
                                        <span class="text-orange font-medium text-sm flex items-center group-hover:text-orange-600 transition-colors">
                                            Baca selengkapnya
                                            <i class="ti ti-arrow-right ml-1"></i>
                                        </span>
                                        <span class="text-xs text-gray-400">
                                            <?php echo date('d M Y', strtotime($article['created_at'])); ?>
                                        </span>
                                    </div>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                    
                    <!-- No Search Results -->
                    <div id="noResults" class="hidden bg-white rounded-lg shadow-sm border border-gray-200 p-8 text-center">
                        <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                            <i class="ti ti-search text-gray-400 text-2xl"></i>
                        </div>
                        <h3 class="text-lg font-medium text-gray-800 mb-2">Artikel Tidak Ditemukan</h3>
                        <p class="text-gray-600">Coba gunakan kata kunci yang berbeda atau periksa ejaan Anda.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>



    <script>
        // Search functionality
        const searchInput = document.getElementById('searchInput');
        const articlesContainer = document.getElementById('articlesContainer');
        const noResults = document.getElementById('noResults');
        
        if (searchInput && articlesContainer) {
            searchInput.addEventListener('input', function() {
                const searchTerm = this.value.toLowerCase().trim();
                const articles = articlesContainer.querySelectorAll('.article-card');
                let visibleArticles = 0;
                
                articles.forEach(article => {
                    const title = article.querySelector('.article-title').textContent.toLowerCase();
                    const description = article.querySelector('.article-description').textContent.toLowerCase();
                    
                    if (searchTerm === '' || title.includes(searchTerm) || description.includes(searchTerm)) {
                        article.style.display = 'block';
                        visibleArticles++;
                        
                        // Highlight search terms
                        if (searchTerm !== '') {
                            highlightText(article, searchTerm);
                        } else {
                            removeHighlight(article);
                        }
                    } else {
                        article.style.display = 'none';
                    }
                });
                
                // Show/hide no results message
                if (noResults) {
                    if (visibleArticles === 0 && searchTerm !== '') {
                        noResults.classList.remove('hidden');
                        articlesContainer.style.display = 'none';
                    } else {
                        noResults.classList.add('hidden');
                        articlesContainer.style.display = 'grid';
                    }
                }
            });
        }
        
        // Highlight search terms
        function highlightText(element, searchTerm) {
            removeHighlight(element);
            
            const textNodes = getTextNodes(element);
            textNodes.forEach(node => {
                const text = node.textContent;
                const regex = new RegExp(`(${escapeRegExp(searchTerm)})`, 'gi');
                
                if (regex.test(text)) {
                    const highlightedHTML = text.replace(regex, '<span class="search-highlight">$1</span>');
                    const wrapper = document.createElement('span');
                    wrapper.innerHTML = highlightedHTML;
                    
                    node.parentNode.replaceChild(wrapper, node);
                }
            });
        }
        
        // Remove highlights
        function removeHighlight(element) {
            const highlights = element.querySelectorAll('.search-highlight');
            highlights.forEach(highlight => {
                const parent = highlight.parentNode;
                parent.replaceChild(document.createTextNode(highlight.textContent), highlight);
                parent.normalize();
            });
        }
        
        // Get all text nodes in an element
        function getTextNodes(element) {
            const textNodes = [];
            const walker = document.createTreeWalker(
                element,
                NodeFilter.SHOW_TEXT,
                null,
                false
            );
            
            let node;
            while (node = walker.nextNode()) {
                if (node.textContent.trim()) {
                    textNodes.push(node);
                }
            }
            
            return textNodes;
        }
        
        // Escape special regex characters
        function escapeRegExp(string) {
            return string.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
        }
        
        // Auto-focus search on page load
        if (searchInput) {
            searchInput.focus();
        }
    </script>
</body>
</html>