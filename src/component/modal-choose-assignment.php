<!-- Modal Choose Assignment - Component -->
<el-dialog>
    <dialog id="chooseAssignmentModal" aria-labelledby="choose-assignment-dialog-title" class="fixed inset-0 size-auto max-h-none max-w-none overflow-y-auto bg-transparent backdrop:bg-transparent">
        <el-dialog-backdrop class="fixed inset-0 bg-gray-500/75 transition-opacity data-closed:opacity-0 data-enter:duration-300 data-enter:ease-out data-leave:duration-200 data-leave:ease-in"></el-dialog-backdrop>

        <div tabindex="0" class="flex min-h-full items-end justify-center p-4 text-center focus:outline-none sm:items-center sm:p-0">
            <el-dialog-panel class="relative transform overflow-hidden rounded-lg bg-white text-left shadow-xl transition-all data-closed:translate-y-4 data-closed:opacity-0 data-enter:duration-300 data-enter:ease-out data-leave:duration-200 data-leave:ease-in sm:my-8 sm:w-full sm:max-w-2xl data-closed:sm:translate-y-0 data-closed:sm:scale-95">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="sm:flex sm:items-start">
                        <div class="mx-auto flex size-12 shrink-0 items-center justify-center rounded-full bg-blue-100 sm:mx-0 sm:size-10">
                            <i class="ti ti-clipboard-text text-blue-600 text-xl"></i>
                        </div>
                        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                            <h3 id="choose-assignment-dialog-title" class="text-lg font-semibold text-gray-900 mb-4">Pilih Tugas untuk Analisis AI</h3>
                            
                            <!-- Loading State -->
                            <div id="assignmentLoadingState" class="flex items-center justify-center py-8 hidden">
                                <i class="ti ti-loader-2 text-2xl animate-spin text-gray-400 mr-2"></i>
                                <span class="text-gray-500">Memuat daftar tugas...</span>
                            </div>

                            <!-- Error State -->
                            <div id="assignmentErrorState" class="hidden">
                                <div class="bg-red-50 border border-red-200 rounded-md p-4">
                                    <div class="flex">
                                        <i class="ti ti-alert-circle text-red-400 text-lg mr-2"></i>
                                        <div>
                                            <h4 class="text-sm font-medium text-red-800">Gagal memuat tugas</h4>
                                            <p class="text-sm text-red-700 mt-1" id="assignmentErrorMessage">Terjadi kesalahan saat mengambil data tugas.</p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Assignment List -->
                            <div id="assignmentListContainer" class="mt-4">
                                <div class="max-h-96 overflow-y-auto">
                                    <!-- Filter Section -->
                                    <div class="mb-4 p-3 bg-gray-50 rounded-lg">
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Filter berdasarkan kelas:</label>
                                        <select id="classFilter" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                            <option value="">Semua Kelas</option>
                                            <!-- Options akan diisi via JavaScript -->
                                        </select>
                                    </div>

                                    <!-- Assignment Items Container -->
                                    <div id="assignmentItems" class="space-y-3">
                                        <!-- Assignment items akan diisi via JavaScript -->
                                    </div>

                                    <!-- Empty State -->
                                    <div id="assignmentEmptyState" class="hidden text-center py-8">
                                        <i class="ti ti-clipboard-off text-4xl text-gray-300 mb-2"></i>
                                        <p class="text-gray-500 text-sm">Tidak ada tugas yang ditemukan.</p>
                                        <p class="text-gray-400 text-xs mt-1">Anda belum memiliki tugas di kelas yang diikuti.</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="bg-gray-50 px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6">
                    <button id="selectAssignmentBtn" type="button" class="inline-flex w-full justify-center rounded-md bg-blue-600 px-3 py-2 text-sm font-semibold text-white shadow-xs hover:bg-blue-500 sm:ml-3 sm:w-auto items-center disabled:opacity-50 disabled:cursor-not-allowed" disabled>
                        <span class="select-assignment-btn-text">Pilih Tugas</span>
                        <i class="ti ti-loader-2 text-sm ml-2 animate-spin hidden select-assignment-btn-loading"></i>
                    </button>
                    <button id="cancelChooseAssignmentBtn" type="button" class="mt-3 inline-flex w-full justify-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-xs ring-1 ring-inset ring-gray-300 hover:bg-gray-50 sm:mt-0 sm:w-auto">
                        Batal
                    </button>
                </div>
            </el-dialog-panel>
        </div>
    </dialog>
</el-dialog>

<style>
/* Custom styles untuk assignment items */
.assignment-item {
    @apply border border-gray-200 rounded-lg p-4 cursor-pointer hover:bg-blue-50 hover:border-blue-300 transition-all duration-200;
}

.assignment-item.selected {
    @apply bg-blue-50 border-blue-500 ring-2 ring-blue-200;
}

.assignment-item:hover {
    @apply shadow-sm;
}

.assignment-deadline-urgent {
    @apply text-red-600 bg-red-100 px-2 py-1 rounded-md text-xs font-medium;
}

.assignment-deadline-soon {
    @apply text-orange-600 bg-orange-100 px-2 py-1 rounded-md text-xs font-medium;
}

.assignment-deadline-normal {
    @apply text-gray-600 bg-gray-100 px-2 py-1 rounded-md text-xs font-medium;
}
</style>