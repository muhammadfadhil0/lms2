<!-- cek sekarang ada di halaman apa -->
<?php 
session_start();
$currentPage = 'pingo'; 

// Check if user is logged in
if (!isset($_SESSION['user'])) {
    header("Location: ../../index.php");
    exit();
}
?>
<!-- includes -->
<?php require '../component/sidebar.php'; ?>
<?php require '../component/menu-bar-mobile.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php require '../../assets/head.php'; ?>
    <link rel="stylesheet" href="../pingo/chat.css">
    <title>Pingo</title>
</head>
<body class="">

    <!-- Main Content -->
    <div data-main-content class="md:ml-64 h-screen pb-16 md:pb-0 transition-all duration-300 ease-in-out flex flex-col">
        <!-- Header -->
        <header class="bg-white p-3 md:p-6 flex-shrink-0 border-b border-gray-200">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-lg md:text-2xl font-bold text-gray-800">Pingo</h1>
                    <p class="text-xs md:text-sm text-gray-600">Tanya apapun tentang pembelajaran</p>
                </div>
                <div class="chat-actions">
                    <button id="clear-button" class="chat-button" title="Hapus Chat">
                        <i class="ti ti-trash text-base md:text-lg"></i>
                        <span class="hidden sm:inline">Hapus Chat</span>
                    </button>
                    <button class="p-1.5 md:p-2 text-gray-400 hover:text-gray-600 transition-colors">
                        <i class="ti ti-bell text-base md:text-xl"></i>
                    </button>
                    <button class="p-1.5 md:p-2 text-gray-400 hover:text-gray-600 transition-colors">
                        <i class="ti ti-search text-base md:text-xl"></i>
                    </button>
                </div>
            </div>
        </header>

        <!-- Chat Container -->
        <main class="flex-1 flex flex-col overflow-hidden">
            <!-- Chat Messages Area -->
            <div class="flex-1 bg-white mx-3 md:mx-6 mt-3 md:mt-6 mb-2 md:mb-4 flex flex-col overflow-hidden rounded-lg border border-gray-200 relative">
                
                <!-- Empty State -->
                <div id="chat-empty-state" class="chat-empty-state">
                    <div class="empty-state-content">
                        <div class="empty-state-greeting">
                            <div class="pingo-icon">
                                AI
                            </div>
                            <h1>Halo, saya Pingo!</h1>
                            <p>Saya siap membantu Anda dengan pertanyaan seputar pembelajaran. Tanya apa saja yang ingin Anda ketahui!</p>
                        </div>
                        
                        <!-- Empty State Input -->
                        <div class="empty-state-input">
                            <div class="input-wrapper">
                                <textarea 
                                    id="chat-input" 
                                    placeholder="Tanya sesuatu tentang pembelajaran..."
                                    rows="1"
                                ></textarea>
                                <div class="send-button-wrapper">
                                    <button id="send-button" type="button">
                                        <i class="ti ti-send"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Chat Messages -->
                <div id="chat-messages" class="flex-1 overflow-y-auto">
                    <!-- Chat messages akan dimuat di sini via JavaScript -->
                </div>

                <!-- Chat Input (Hidden initially) -->
                <div id="chat-input-container" class="chat-input-container" style="display: none;">
                    <div class="input-group">
                        <div class="input-wrapper">
                            <textarea 
                                id="chat-input-active" 
                                placeholder="Ketik pesan Anda di sini..."
                                rows="1"
                            ></textarea>
                            <div class="send-button-wrapper">
                                <button id="send-button-active" type="button">
                                    <i class="ti ti-send"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script src="../script/menu-bar-script.js"></script>
    <script src="../pingo/chat.js"></script>
</body>
</html>