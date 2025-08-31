<el-dialog>
    <dialog id="assignment-list-modal" aria-labelledby="assignment-dialog-title" class="fixed inset-0 size-auto max-h-none max-w-none overflow-y-auto bg-transparent backdrop:bg-transparent hidden">
        <el-dialog-backdrop class="fixed inset-0 bg-gray-500/75 transition-opacity data-closed:opacity-0 data-enter:duration-300 data-enter:ease-out data-leave:duration-200 data-leave:ease-in"></el-dialog-backdrop>

        <div tabindex="0" class="flex min-h-full items-end justify-center p-4 text-center focus:outline-none sm:items-center sm:p-0">
            <el-dialog-panel class="relative transform overflow-hidden rounded-lg bg-white text-left shadow-xl transition-all data-closed:translate-y-4 data-closed:opacity-0 data-enter:duration-300 data-enter:ease-out data-leave:duration-200 data-leave:ease-in sm:my-8 sm:w-full sm:max-w-4xl data-closed:sm:translate-y-0 data-closed:sm:scale-95">
                
                <!-- Header -->
                <div class="bg-white px-6 pt-6 pb-4 border-b border-gray-200">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <div class="mx-auto flex size-12 bg-purple-100 shrink-0 items-center justify-center rounded-full sm:mx-0">
                                <span class="ti ti-clipboard-list text-xl text-purple-600"></span>
                            </div>
                            <div class="ml-4">
                                <h3 id="assignment-dialog-title" class="text-xl font-semibold text-gray-900">Semua Tugas</h3>
                                <p class="text-sm text-gray-500">Klik tugas untuk langsung menuju ke postingan</p>
                            </div>
                        </div>
                        <button id="close-assignment-modal" class="text-gray-400 hover:text-gray-600 focus:outline-none">
                            <span class="ti ti-x text-2xl"></span>
                        </button>
                    </div>
                    
                    <!-- Search and Filter Controls -->
                    <div class="mt-4 flex flex-col sm:flex-row gap-4">
                        <!-- Search Input -->
                        <div class="flex-1">
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <span class="ti ti-search text-gray-400"></span>
                                </div>
                                <input type="text" id="assignment-search" placeholder="Cari tugas..." 
                                    class="block w-full pl-10 pr-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 text-sm">
                            </div>
                        </div>
                        
                        <!-- Sort Dropdown -->
                        <div class="sm:w-48">
                            <select id="assignment-sort" class="block w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 text-sm">
                                <option value="created_desc">Terbaru</option>
                                <option value="created_asc">Terlama</option>
                                <option value="name_asc">Nama A-Z</option>
                                <option value="name_desc">Nama Z-A</option>
                                <option value="deadline_asc">Deadline Terdekat</option>
                                <option value="deadline_desc">Deadline Terjauh</option>
                            </select>
                        </div>
                    </div>
                </div>
                
                <!-- Assignment List -->
                <div class="bg-white px-6 py-4 max-h-96 overflow-y-auto">
                    <div id="assignment-list-container">
                        <!-- Loading state -->
                        <div id="assignment-loading" class="text-center py-8">
                            <div class="animate-spin inline-block w-6 h-6 border-2 border-current border-t-transparent text-purple-600 rounded-full mb-2"></div>
                            <p class="text-sm text-gray-500">Memuat tugas...</p>
                        </div>
                        
                        <!-- Assignment items will be populated here -->
                        <div id="assignment-items" class="space-y-3 hidden">
                            <!-- Template will be filled by JavaScript -->
                        </div>
                        
                        <!-- No results state -->
                        <div id="no-assignments" class="text-center py-8 hidden">
                            <div class="text-gray-300 mb-2">
                                <span class="ti ti-clipboard-off text-4xl"></span>
                            </div>
                            <p class="text-sm text-gray-500">Tidak ada tugas ditemukan</p>
                        </div>
                    </div>
                </div>
                
                <!-- Footer -->
                <div class="bg-gray-50 px-6 py-4 text-center">
                    <p class="text-sm text-gray-500">Klik tugas untuk langsung menuju ke postingan • Klik di luar modal untuk menutup</p>
                </div>
            </el-dialog-panel>
        </div>
    </dialog>
</el-dialog>
