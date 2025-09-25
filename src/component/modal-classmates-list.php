<el-dialog>
    <dialog id="classmates-list-modal" aria-labelledby="classmates-dialog-title" class="fixed inset-0 size-auto max-h-none max-w-none overflow-y-auto bg-transparent backdrop:bg-transparent hidden">
        <el-dialog-backdrop class="fixed inset-0 bg-gray-500/75 transition-opacity data-closed:opacity-0 data-enter:duration-300 data-enter:ease-out data-leave:duration-200 data-leave:ease-in"></el-dialog-backdrop>

        <div tabindex="0" class="flex min-h-full items-end justify-center p-4 text-center focus:outline-none sm:items-center sm:p-0">
            <el-dialog-panel class="relative transform overflow-hidden rounded-lg bg-white text-left shadow-xl transition-all data-closed:translate-y-4 data-closed:opacity-0 data-enter:duration-300 data-enter:ease-out data-leave:duration-200 data-leave:ease-in sm:my-8 sm:w-full sm:max-w-3xl data-closed:sm:translate-y-0 data-closed:sm:scale-95">
                <!-- Header -->
                <div class="bg-white px-6 pt-6 pb-4 border-b border-gray-200">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <div class="mx-auto flex size-12 bg-orange-100 shrink-0 items-center justify-center rounded-full sm:mx-0">
                                <span class="ti ti-users text-xl text-orange-600"></span>
                            </div>
                            <div class="ml-4">
                                <h3 id="classmates-dialog-title" class="text-xl font-semibold text-gray-900">Teman Sekelas</h3>
                                <p class="text-sm text-gray-500">Daftar siswa yang telah bergabung</p>
                            </div>
                        </div>
                        <button data-close-classmates-modal class="text-gray-400 hover:text-gray-600 focus:outline-none">
                            <span class="ti ti-x text-2xl"></span>
                        </button>
                    </div>
                    <!-- Search -->
                    <div class="mt-4 flex flex-col sm:flex-row gap-4">
                        <div class="flex-1">
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <span class="ti ti-search text-gray-400"></span>
                                </div>
                                <input type="text" id="classmates-search" placeholder="Cari teman..." class="block w-full pl-10 pr-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500 text-sm">
                            </div>
                        </div>
                        <div class="sm:w-48">
                            <select id="classmates-sort" class="block w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500 text-sm">
                                <option value="name_asc">Nama A-Z</option>
                                <option value="name_desc">Nama Z-A</option>
                                <option value="joined_desc">Terbaru Bergabung</option>
                                <option value="joined_asc">Terlama Bergabung</option>
                            </select>
                        </div>
                    </div>
                </div>
                <!-- List -->
                <div class="bg-white px-6 py-4 max-h-96 overflow-y-auto">
                    <div id="classmates-list-container">
                        <div id="classmates-loading" class="text-center py-8">
                            <div class="animate-spin inline-block w-6 h-6 border-2 border-current border-t-transparent text-orange-600 rounded-full mb-2"></div>
                            <p class="text-sm text-gray-500">Memuat daftar teman...</p>
                        </div>
                        <div id="classmates-items" class="space-y-3 hidden"></div>
                        <div id="no-classmates" class="text-center py-8 hidden">
                            <div class="text-gray-300 mb-2">
                                <span class="ti ti-user-off text-4xl"></span>
                            </div>
                            <p class="text-sm text-gray-500">Tidak ada teman ditemukan</p>
                        </div>
                    </div>
                </div>
                <!-- Footer -->
                <div class="bg-gray-50 px-6 py-4 text-center">
                    <p class="text-sm text-gray-500">Gunakan kolom pencarian untuk menemukan teman • Klik di luar modal untuk menutup</p>
                </div>
            </el-dialog-panel>
        </div>
    </dialog>
</el-dialog>
