<!-- Modal Pengaturan Kelas -->
<el-dialog>
    <dialog id="class-settings-modal" aria-labelledby="settings-title" class="fixed inset-0 size-auto max-h-none max-w-none overflow-y-auto bg-transparent backdrop:bg-transparent">
        <el-dialog-backdrop class="fixed inset-0 bg-gray-500/75 transition-opacity data-closed:opacity-0 data-enter:duration-300 data-enter:ease-out data-leave:duration-200 data-leave:ease-in"></el-dialog-backdrop>

    <div tabindex="0" class="flex min-h-full items-center justify-center p-4 text-center focus:outline-none sm:p-0">
            <el-dialog-panel class="relative transform overflow-hidden rounded-lg bg-white text-left shadow-xl transition-all data-closed:translate-y-4 data-closed:opacity-0 data-enter:duration-300 data-enter:ease-out data-leave:duration-200 data-leave:ease-in sm:my-8 sm:w-full sm:max-w-lg data-closed:sm:translate-y-0 data-closed:sm:scale-95">
        <div class="bg-white px-4 pt-5 pb-4 sm:p-7 sm:pb-5">
                    <!-- Modal Alert Component -->
                    <?php include 'modal-alert.php'; ?>
                    
                    <div class="sm:flex sm:items-start">
                        <div class="mx-auto flex size-12 bg-orange-100 shrink-0 items-center justify-center rounded-full sm:mx-0 sm:size-12">
                            <span class="ti ti-settings text-lg text-orange-600 sm:text-xl"></span>
                        </div>
                        <div class="mt-4 text-center sm:mt-0 sm:ml-5 sm:text-left">
                            <h3 id="settings-title" class="text-base font-semibold text-gray-900 sm:text-lg">Pengaturan Kelas</h3>
                            <div class="mt-2">
                                <p class="text-sm text-gray-500 sm:text-base">Kelola pengaturan dan preferensi kelas</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mt-5 space-y-2.5 sm:space-y-3">
                        <!-- Latar Belakang Kelas -->
                        <button onclick="openBackgroundModal()" class="w-full flex items-center p-3.5 sm:p-4 text-left hover:bg-orange-50 rounded-lg transition-colors border border-gray-200 hover:border-orange-200">
                            <div class="w-9 h-9 sm:w-10 sm:h-10 bg-blue-100 rounded-lg flex items-center justify-center mr-3.5 sm:mr-4">
                                <i class="ti ti-photo text-blue-600 text-base sm:text-lg"></i>
                            </div>
                            <div class="flex-1">
                                <h4 class="font-medium text-gray-900 text-sm sm:text-base">Latar Belakang Kelas</h4>
                                <p class="text-xs text-gray-500 sm:text-sm">Ubah gambar latar belakang kelas</p>
                            </div>
                            <i class="ti ti-chevron-right text-gray-400 text-sm sm:text-base"></i>
                        </button>

                        <!-- Edit Kelas -->
                        <button onclick="openEditClassModal()" class="w-full flex items-center p-3.5 sm:p-4 text-left hover:bg-orange-50 rounded-lg transition-colors border border-gray-200 hover:border-orange-200">
                            <div class="w-9 h-9 sm:w-10 sm:h-10 bg-green-100 rounded-lg flex items-center justify-center mr-3.5 sm:mr-4">
                                <i class="ti ti-edit text-green-600 text-base sm:text-lg"></i>
                            </div>
                            <div class="flex-1">
                                <h4 class="font-medium text-gray-900 text-sm sm:text-base">Edit Kelas</h4>
                                <p class="text-xs text-gray-500 sm:text-sm">Ubah nama dan mata pelajaran</p>
                            </div>
                            <i class="ti ti-chevron-right text-gray-400 text-sm sm:text-base"></i>
                        </button>

                        <!-- Atur Siswa -->
                        <button onclick="openManageStudentsModal()" class="w-full flex items-center p-3.5 sm:p-4 text-left hover:bg-orange-50 rounded-lg transition-colors border border-gray-200 hover:border-orange-200">
                            <div class="w-9 h-9 sm:w-10 sm:h-10 bg-purple-100 rounded-lg flex items-center justify-center mr-3.5 sm:mr-4">
                                <i class="ti ti-users text-purple-600 text-base sm:text-lg"></i>
                            </div>
                            <div class="flex-1">
                                <h4 class="font-medium text-gray-900 text-sm sm:text-base">Atur Siswa</h4>
                                <p class="text-xs text-gray-500 sm:text-sm">Kelola siswa yang tergabung</p>
                            </div>
                            <i class="ti ti-chevron-right text-gray-400 text-sm sm:text-base"></i>
                        </button>

                        <!-- Perizinan -->
                        <button onclick="openPermissionsModal()" class="w-full flex items-center p-3.5 sm:p-4 text-left hover:bg-orange-50 rounded-lg transition-colors border border-gray-200 hover:border-orange-200">
                            <div class="w-9 h-9 sm:w-10 sm:h-10 bg-red-100 rounded-lg flex items-center justify-center mr-3.5 sm:mr-4">
                                <i class="ti ti-shield text-red-600 text-base sm:text-lg"></i>
                            </div>
                            <div class="flex-1">
                                <h4 class="font-medium text-gray-900 text-sm sm:text-base">Perizinan</h4>
                                <p class="text-xs text-gray-500 sm:text-sm">Atur hak akses siswa</p>
                            </div>
                            <i class="ti ti-chevron-right text-gray-400 text-sm sm:text-base"></i>
                        </button>
                    </div>
                </div>
                
                <div class="bg-gray-50 px-4 py-3.5 sm:px-6 sm:py-4 text-center">
                    <p class="text-xs text-gray-500 sm:text-sm">Klik di luar modal untuk menutup</p>
                </div>
            </el-dialog-panel>
        </div>
    </dialog>
</el-dialog>
