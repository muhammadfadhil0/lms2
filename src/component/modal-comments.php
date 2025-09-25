<!-- Comments Modal -->
<dialog id="comments-modal" class="modal fixed inset-0 z-50 overflow-y-auto p-4 md:p-6">
    <div class="flex min-h-full items-end justify-center md:items-center">
        <div class="bg-white rounded-t-lg md:rounded-lg w-full max-w-2xl shadow-xl transform transition-all">
        <div class="px-4 md:px-6 pt-4 md:pt-6 pb-4">
            <div class="flex items-start">
                <div class="mx-auto flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-full bg-blue-100 sm:mx-0 sm:h-10 sm:w-10">
                    <i class="ti ti-message-circle text-xl text-blue-600"></i>
                </div>
                <div class="mt-3 text-center sm:ml-4 sm:mt-0 sm:text-left flex-1">
                    <h3 class="text-lg font-semibold leading-6 text-gray-900">Komentar</h3>
                    <p class="text-sm text-gray-500">Lihat dan tambahkan komentar pada postingan ini</p>
                </div>
                <button type="button" onclick="closeCommentsModal()" class="text-gray-400 hover:text-gray-600">
                    <i class="ti ti-x text-xl"></i>
                </button>
            </div>
            
            <!-- Comments Container -->
            <div class="mt-6">
                <div id="modal-comments-list" class="max-h-60 md:max-h-96 overflow-y-auto space-y-4">
                    <!-- Comments will be loaded here -->
                    <div class="text-center py-8 text-gray-500">
                        <i class="ti ti-loader animate-spin text-2xl mb-2"></i>
                        <p>Memuat komentar...</p>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Comment Input Form -->
        <div class="bg-gray-50 px-4 md:px-6 py-4">
            <form id="modal-comment-form" class="flex space-x-3">
                <input type="hidden" id="modal-post-id" value="">
                <div class="flex-1">
                    <textarea id="modal-comment-input" 
                        placeholder="Tulis komentar... (tekan Enter untuk mengirim)" 
                        rows="2"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg resize-none focus:ring-2 focus:ring-orange-500 focus:border-orange-500 text-sm"
                        required></textarea>
                </div>
                <button type="submit" 
                    class="self-start px-3 md:px-4 py-2 bg-orange-600 text-white rounded-lg hover:bg-orange-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-orange-500 text-sm font-medium">
                    Kirim
                </button>
            </form>
        </div>
        </div>
    </div>
</dialog>

<style>
#comments-modal {
    margin: 0;
    padding: 0;
    border: none;
    background: transparent;
    max-width: none;
    max-height: none;
    width: 100vw;
    height: 100vh;
    opacity: 0;
    transition: opacity 0.25s ease-in-out;
}

#comments-modal[open] {
    opacity: 1;
}

#comments-modal::backdrop {
    background-color: rgba(0, 0, 0, 0.5);
    transition: background-color 0.25s ease-in-out;
}

#comments-modal .transform {
    transition: transform 0.25s ease-in-out;
}

/* Mobile-specific styles */
@media (max-width: 767px) {
    #comments-modal .transform {
        transform: translateY(100%);
    }
    
    #comments-modal[open] .transform {
        transform: translateY(0);
    }
    
    #comments-modal .bg-white {
        border-radius: 1rem 1rem 0 0;
        max-height: 85vh;
        margin-bottom: 0;
    }
    
    #comments-modal .min-h-full {
        align-items: flex-end;
    }
}

/* Desktop styles */
@media (min-width: 768px) {
    #comments-modal .transform {
        transform: scale(0.95) translateY(-10px);
    }
    
    #comments-modal[open] .transform {
        transform: scale(1) translateY(0);
    }
    
    #comments-modal .bg-white {
        border-radius: 0.5rem;
        max-height: 90vh;
    }
    
    #comments-modal .min-h-full {
        align-items: center;
    }
}
</style>
