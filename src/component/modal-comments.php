<el-dialog>
    <dialog id="comments-modal" aria-labelledby="comments-dialog-title" class="fixed inset-0 size-auto max-h-none max-w-none overflow-y-auto bg-transparent backdrop:bg-transparent">
        <el-dialog-backdrop class="fixed inset-0 bg-gray-500/75 transition-opacity data-closed:opacity-0 data-enter:duration-300 data-enter:ease-out data-leave:duration-200 data-leave:ease-in"></el-dialog-backdrop>

        <div tabindex="0" class="flex min-h-full items-end justify-center p-6 text-center focus:outline-none sm:items-center sm:p-0">
            <el-dialog-panel class="relative transform overflow-hidden rounded-lg bg-white text-left shadow-xl transition-all data-closed:translate-y-4 data-closed:opacity-0 data-enter:duration-300 data-enter:ease-out data-leave:duration-200 data-leave:ease-in sm:my-8 sm:w-full sm:max-w-2xl data-closed:sm:translate-y-0 data-closed:sm:scale-95">
                <div class="bg-white px-5 pt-6 pb-4 sm:p-6 sm:pb-4">
                    <div class="sm:flex sm:items-start">
                        <div class="mx-auto flex size-14 bg-blue-100 shrink-0 items-center justify-center rounded-full sm:mx-0 sm:size-12">
                            <span class="ti ti-message-circle text-xl text-blue-600"></span>
                        </div>
                        <div class="mt-4 text-center sm:mt-0 sm:ml-5 sm:text-left flex-1">
                            <h3 id="comments-dialog-title" class="text-lg font-semibold text-gray-900">Komentar</h3>
                            <div class="mt-2">
                                <p class="text-base text-gray-500">Lihat dan tambahkan komentar pada postingan ini</p>
                            </div>
                        </div>
                        <button type="button" onclick="closeCommentsModal()" class="ml-auto text-gray-400 hover:text-gray-600">
                            <i class="ti ti-x text-xl"></i>
                        </button>
                    </div>
                    
                    <!-- Comments Container -->
                    <div class="mt-6">
                        <div id="modal-comments-list" class="max-h-96 overflow-y-auto space-y-4">
                            <!-- Comments will be loaded here -->
                            <div class="text-center py-8 text-gray-500">
                                <i class="ti ti-loader animate-spin text-2xl mb-2"></i>
                                <p>Memuat komentar...</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Comment Input Form -->
                <div class="bg-gray-50 px-5 py-4 sm:px-6">
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
                            class="self-start px-4 py-2 bg-orange-600 text-white rounded-lg hover:bg-orange-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-orange-500 text-sm font-medium">
                            Kirim
                        </button>
                    </form>
                </div>
            </el-dialog-panel>
        </div>
    </dialog>
</el-dialog>
