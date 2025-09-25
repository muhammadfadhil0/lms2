<el-dialog>
    <dialog id="schedule-list-modal" aria-labelledby="schedule-dialog-title" class="fixed inset-0 size-auto max-h-none max-w-none overflow-y-auto bg-transparent backdrop:bg-transparent hidden">
        <el-dialog-backdrop class="fixed inset-0 bg-gray-500/75 transition-opacity data-closed:opacity-0 data-enter:duration-300 data-enter:ease-out data-leave:duration-200 data-leave:ease-in"></el-dialog-backdrop>

        <div tabindex="0" class="flex min-h-full items-end justify-center p-4 text-center focus:outline-none sm:items-center sm:p-0">
            <el-dialog-panel class="relative transform overflow-hidden rounded-lg bg-white text-left shadow-xl transition-all data-closed:translate-y-4 data-closed:opacity-0 data-enter:duration-300 data-enter:ease-out data-leave:duration-200 data-leave:ease-in sm:my-8 sm:w-full sm:max-w-3xl data-closed:sm:translate-y-0 data-closed:sm:scale-95">
                <!-- Header -->
                <div class="bg-white px-6 pt-6 pb-4 border-b border-gray-200">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <div class="mx-auto flex size-12 bg-blue-100 shrink-0 items-center justify-center rounded-full sm:mx-0">
                                <span class="ti ti-calendar text-xl text-blue-600"></span>
                            </div>
                            <div class="ml-4">
                                <h3 id="schedule-dialog-title" class="text-xl font-semibold text-gray-900">Jadwal Kelas</h3>
                                <p class="text-sm text-gray-500">Daftar file jadwal yang telah diupload</p>
                            </div>
                        </div>
                        <button data-close-schedule-modal class="text-gray-400 hover:text-gray-600 focus:outline-none">
                            <span class="ti ti-x text-2xl"></span>
                        </button>
                    </div>
                    <!-- Search / Filter -->
                    <div class="mt-4 flex flex-col sm:flex-row gap-4">
                        <div class="flex-1">
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <span class="ti ti-search text-gray-400"></span>
                                </div>
                                <input type="text" id="schedule-search" placeholder="Cari jadwal..." class="block w-full pl-10 pr-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm">
                            </div>
                        </div>
                        <div class="sm:w-48">
                            <select id="schedule-sort" class="block w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm">
                                <option value="created_desc">Terbaru</option>
                                <option value="created_asc">Terlama</option>
                                <option value="name_asc">Nama A-Z</option>
                                <option value="name_desc">Nama Z-A</option>
                            </select>
                        </div>
                    </div>
                </div>
                <!-- List -->
                <div class="bg-white px-6 py-4 max-h-96 overflow-y-auto">
                    <div id="schedule-list-container">
                        <div id="schedule-loading" class="text-center py-8">
                            <div class="animate-spin inline-block w-6 h-6 border-2 border-current border-t-transparent text-blue-600 rounded-full mb-2"></div>
                            <p class="text-sm text-gray-500">Memuat jadwal...</p>
                        </div>
                        <div id="schedule-items" class="space-y-3 hidden"></div>
                        <div id="no-schedules" class="text-center py-8 hidden">
                            <div class="text-gray-300 mb-2">
                                <span class="ti ti-calendar-off text-4xl"></span>
                            </div>
                            <p class="text-sm text-gray-500">Tidak ada jadwal ditemukan</p>
                        </div>
                    </div>
                </div>
                <!-- Footer -->
                <div class="bg-gray-50 px-6 py-4 text-center">
                    <p class="text-sm text-gray-500">Klik jadwal untuk melihat atau download • Klik di luar modal untuk menutup</p>
                </div>
            </el-dialog-panel>
        </div>
    </dialog>
</el-dialog>
