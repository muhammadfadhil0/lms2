<el-dialog>
    <dialog id="assignment-list-modal" aria-labelledby="assignment-dialog-title" class="fixed inset-0 size-auto max-h-none max-w-none overflow-y-auto bg-transparent backdrop:bg-transparent hidden">
        <el-dialog-backdrop class="fixed inset-0 bg-gray-500/75 transition-opacity data-closed:opacity-0 data-enter:duration-300 data-enter:ease-out data-leave:duration-200 data-leave:ease-in"></el-dialog-backdrop>

        <div tabindex="0" class="flex min-h-full items-end justify-center p-3 text-center focus:outline-none sm:items-center sm:p-0">
            <el-dialog-panel class="relative w-full max-w-lg sm:max-w-4xl transform overflow-hidden rounded-xl bg-white text-left shadow-xl transition-all data-closed:translate-y-4 data-closed:opacity-0 data-enter:duration-300 data-enter:ease-out data-leave:duration-200 data-leave:ease-in sm:my-8 data-closed:sm:translate-y-0 data-closed:sm:scale-95">
                
                <!-- Header -->
                <div class="bg-white px-4 pt-4 pb-3 sm:px-6 sm:pt-6 sm:pb-4 border-b border-gray-200 sticky top-0 z-10">
                    <div class="flex items-start sm:items-center justify-between gap-3">
                        <div class="flex items-center min-w-0">
                            <div class="flex items-center justify-center w-10 h-10 sm:w-12 sm:h-12 bg-purple-100 rounded-full flex-shrink-0">
                                <span class="ti ti-clipboard-list text-lg sm:text-xl text-purple-600"></span>
                            </div>
                            <div class="ml-3 min-w-0">
                                <h3 id="assignment-dialog-title" class="text-base sm:text-xl font-semibold text-gray-900 leading-tight truncate">Semua Tugas</h3>
                                <p class="text-[11px] sm:text-sm text-gray-500 hidden sm:block">Tap tugas untuk menuju postingan</p>
                                <p class="text-[11px] text-gray-500 sm:hidden mt-0.5">Tap tugas untuk buka</p>
                            </div>
                        </div>
                        <button id="close-assignment-modal" class="text-gray-400 hover:text-gray-600 focus:outline-none -mr-1 sm:mr-0">
                            <span class="ti ti-x text-xl sm:text-2xl"></span>
                        </button>
                    </div>
                    <!-- Search + Sort (mobile stacked) -->
                    <div class="mt-3 flex flex-col sm:flex-row gap-2 sm:gap-4">
                        <div class="flex-1">
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <span class="ti ti-search text-gray-400 text-sm"></span>
                                </div>
                                <input type="text" id="assignment-search" placeholder="Cari tugas..." 
                                    class="block w-full pl-9 pr-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-purple-500 focus:border-purple-500 text-xs sm:text-sm" />
                            </div>
                        </div>
                        <div class="flex sm:w-48">
                            <select id="assignment-sort" class="block w-full px-2.5 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-purple-500 focus:border-purple-500 text-xs sm:text-sm">
                                <option value="created_desc">Terbaru</option>
                                <option value="created_asc">Terlama</option>
                                <option value="name_asc">A-Z</option>
                                <option value="name_desc">Z-A</option>
                                <option value="deadline_asc">Deadline Dekat</option>
                                <option value="deadline_desc">Deadline Jauh</option>
                            </select>
                        </div>
                    </div>
                </div>
                
                <!-- Assignment List -->
                <div class="bg-white px-4 sm:px-6 py-3 sm:py-4 max-h-[70vh] overflow-y-auto">
                    <div id="assignment-list-container">
                        <!-- Loading state -->
                        <div id="assignment-loading" class="text-center py-6 sm:py-8">
                            <div class="animate-spin inline-block w-5 h-5 sm:w-6 sm:h-6 border-2 border-current border-t-transparent text-purple-600 rounded-full mb-2"></div>
                            <p class="text-xs sm:text-sm text-gray-500">Memuat tugas...</p>
                        </div>
                        
                        <!-- Assignment items will be populated here -->
                        <div id="assignment-items" class="space-y-2 sm:space-y-3 hidden">
                            <!-- Template will be filled by JavaScript -->
                        </div>
                        
                        <!-- No results state -->
                        <div id="no-assignments" class="text-center py-8 hidden">
                            <div class="text-gray-300 mb-2">
                                <span class="ti ti-clipboard-off text-3xl sm:text-4xl"></span>
                            </div>
                            <p class="text-xs sm:text-sm text-gray-500">Tidak ada tugas ditemukan</p>
                        </div>
                    </div>
                </div>
                
                <!-- Footer -->
                <div class="bg-gray-50 px-4 sm:px-6 py-3 sm:py-4 text-center border-t border-gray-200">
                    <p class="text-[11px] sm:text-sm text-gray-500">Tap tugas untuk buka • Tap area gelap untuk tutup</p>
                </div>
            </el-dialog-panel>
        </div>
    </dialog>
</el-dialog>
