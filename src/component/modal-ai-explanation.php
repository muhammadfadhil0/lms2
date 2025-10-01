<?php
// AI Explanation Popup Component
// Simple popup for displaying AI explanations about posts
?>

<!-- AI Explanation Floating Container -->
<div id="ai-explanation-container" class="fixed top-0 left-0 w-full h-full pointer-events-none z-[10000]">
    <div id="ai-explanation-popup" 
         class="hidden absolute bg-white border border-gray-200 rounded-lg shadow-xl pointer-events-auto max-w-sm w-80"
         style="box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);">
        
        <!-- Loading State -->
        <div id="ai-popup-loading" class="p-4">
            <div class="flex items-center space-x-3">
                <div class="flex-shrink-0">
                    <div class="w-8 h-8 border-2 border-orange-200 border-t-orange-600 rounded-full animate-spin"></div>
                </div>
                <div>
                    <p class="text-sm font-medium text-gray-800">Menganalisis...</p>
                    <p class="text-xs text-gray-500">AI sedang memproses postingan</p>
                </div>
            </div>
        </div>

        <!-- Error State -->
        <div id="ai-popup-error" class="hidden p-4">
            <div class="flex items-center space-x-3 mb-3">
                <div class="flex-shrink-0">
                    <i class="ti ti-alert-triangle text-red-500 text-lg"></i>
                </div>
                <div>
                    <p class="text-sm font-medium text-gray-800">Gagal menganalisis</p>
                    <p class="text-xs text-gray-500">Terjadi kesalahan</p>
                </div>
            </div>
            <button onclick="retryAiExplanation()" class="w-full bg-orange-600 text-white text-xs px-3 py-2 rounded hover:bg-orange-700 transition-colors">
                Coba Lagi
            </button>
        </div>

        <!-- Content State -->
        <div id="ai-popup-content" class="hidden p-4 max-h-96 overflow-y-auto">
            <!-- Post Type Indicator -->
            <div id="ai-post-type" class="hidden mb-3 p-2 rounded-lg">
                <div class="flex items-center text-xs font-medium">
                    <i id="post-type-icon" class="mr-2"></i>
                    <span id="post-type-text"></span>
                    <span id="urgency-badge" class="ml-auto px-2 py-1 rounded-full text-xs"></span>
                </div>
            </div>

            <div class="mb-3">
                <div class="flex items-center mb-2">
                    <i class="ti ti-sparkles text-orange mr-2"></i>
                    <h5 class="text-sm font-semibold text-gray-800">Analisis PingoAI</h5>
                </div>
                <div id="ai-analysis-text" class="text-xs text-gray-700 leading-relaxed">
                    <!-- AI analysis content -->
                </div>
            </div>

            <!-- Key Points -->
            <div id="ai-key-points" class="hidden mb-3">
                <h6 class="text-xs font-medium text-gray-700 mb-2 flex items-center">
                    <i class="ti ti-list-check text-blue-600 mr-1"></i>
                    Poin Penting
                </h6>
                <ul id="key-points-list" class="text-xs text-gray-600 space-y-1">
                    <!-- Key points -->
                </ul>
            </div>



            <div class="mt-3 pt-3 border-t border-gray-100">
                <p class="text-xs text-gray-400 flex items-center">
                    <i class="ti ti-info-circle mr-1"></i>
                    Analisis dibuat oleh AI
                </p>
            </div>
        </div>
    </div>
</div>

<style>
/* AI Explanation Popup Styling */
#ai-explanation-container {
    z-index: 10000;
    pointer-events: none;
}

#ai-explanation-popup {
    pointer-events: auto;
    backdrop-filter: blur(8px);
    background-color: rgba(255, 255, 255, 0.95);
    border: 1px solid #e5e7eb;
    transition: all 0.2s ease-out;
}

/* Orange colors fallback for loading spinner */
.border-orange-200 {
    border-color: #fed7aa !important;
}

.border-t-orange-600 {
    border-top-color: #ea580c !important;
}

.bg-orange-600 {
    background-color: #ea580c !important;
}

.hover\:bg-orange-700:hover {
    background-color: #c2410c !important;
}

#ai-explanation-popup:not(.hidden) {
    animation: aiPopupFadeIn 0.2s ease-out;
}

@keyframes aiPopupFadeIn {
    from {
        opacity: 0;
        transform: translateY(-8px) scale(0.95);
    }
    to {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
}

/* Mobile responsive adjustments */
@media (max-width: 640px) {
    #ai-explanation-popup {
        min-width: 280px;
        max-width: calc(100vw - 16px);
    }
}
</style>