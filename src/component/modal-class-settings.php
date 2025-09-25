<!-- Modal Pengaturan Kelas (Compact + Mobile bottom) -->
<el-dialog>
    <dialog id="class-settings-modal" aria-labelledby="settings-title"
        class="fixed inset-0 size-auto max-h-none max-w-none overflow-y-auto bg-transparent backdrop:bg-transparent z-50">
        <el-dialog-backdrop id="class-settings-backdrop"
            class="fixed inset-0 bg-gray-500/60 transition-opacity data-closed:opacity-0 data-enter:duration-300 data-enter:ease-out data-leave:duration-200 data-leave:ease-in"></el-dialog-backdrop>

        <div tabindex="0"
            class="flex min-h-full items-end justify-center sm:p-6 text-center focus:outline-none sm:items-center sm:p-0">
            <el-dialog-panel id=""
                class="relative transform overflow-hidden rounded-lg bg-white text-left shadow-xl transition-all data-closed:translate-y-4 data-closed:opacity-0 data-enter:duration-300 data-enter:ease-out data-leave:duration-200 data-leave:ease-in sm:my-8 sm:w-full sm:max-w-lg data-closed:sm:translate-y-0 data-closed:sm:scale-95">
                <style>
                    /* Desktop: spacious like modal-create-assignment */
                    #class-settings-panel {
                        max-width: 672px;
                    }

                    @media (min-width: 640px) {
                        #class-settings-panel {
                            padding: 1.75rem 1.5rem 1.25rem 1.5rem;
                        }

                        .modal-settings-content {
                            padding: 0;
                        }
                    }

                    #class-settings-panel .sm\:p-7 {
                        padding: 1.75rem;
                    }

                    #class-settings-panel .sm\:pb-5 {
                        padding-bottom: 1.25rem;
                    }

                    #class-settings-panel .sm\:text-lg {
                        font-size: 1.125rem;
                    }

                    /* Mobile: make modal anchored to bottom, full-width and compact */
                    @media (max-width: 640px) {
                        #class-settings-modal {
                            align-items: end;
                        }

                        #class-settings-panel {
                            border-bottom-left-radius: 0.5rem;
                            border-bottom-right-radius: 0.5rem;
                            margin: 0;
                            width: 100%;
                            max-width: 100%;
                            transform: translateY(0);
                            padding: 0.5rem 0.75rem 0.5rem;
                        }

                        #class-settings-modal .sm\:flex {
                            display: flex;
                        }

                        #class-settings-backdrop {
                            background: linear-gradient(rgba(0, 0, 0, 0.35), rgba(0, 0, 0, 0.35));
                        }

                        /* Align header and subheader to left, next to icon */
                        #class-settings-head {
                            text-align: left;
                            margin-top: 0.125rem;
                        }

                        #class-settings-head .mt-2 {
                            margin-top: 0.25rem;
                        }

                        /* Position close button a bit inward on mobile */
                        #class-settings-panel>.relative .absolute.top-3.right-3 {
                            right: 0.5rem;
                            top: 0.5rem;
                        }
                    }
                </style>
                <div class="bg-white px-4 pt-4 pb-3 sm:px-7 sm:pt-5 sm:pb-4 relative modal-settings-content" id="class-settings-panel">
                    <!-- Modal Alert Component -->
                    <?php include 'modal-alert.php'; ?>

                    <div class="flex items-start">
                        <div
                            class="flex size-12 bg-orange-100 shrink-0 items-center justify-center rounded-full mr-3.5 sm:mx-0 sm:size-12">
                            <span class="ti ti-settings text-lg text-orange-600 sm:text-xl"></span>
                        </div>
                        <div class="mt-0 mb-3 text-left sm:mt-0 sm:ml-5 sm:text-left" id="class-settings-head">
                            <h3 id="settings-title" class="text-base font-semibold text-gray-900 sm:text-lg">Pengaturan
                                Kelas</h3>
                            <div class="">
                                <p class="text-xs text-gray-500 sm:text-base">Kelola pengaturan dan preferensi kelas</p>
                            </div>
                        </div>
                    </div>

                    <div class="mt-3 space-y-2 sm:space-y-2">
                        <!-- Informasi Kelas (removed) -->

                        <!-- Latar Belakang Kelas -->
                        <button onclick="openBackgroundModal()"
                            class="w-full flex items-center p-3 sm:p-3 text-left hover:bg-orange-50 rounded-lg transition-colors border border-gray-200 hover:border-orange-200">
                            <div
                                class="w-9 h-9 sm:w-10 sm:h-10 bg-blue-100 rounded-lg flex items-center justify-center mr-3.5 sm:mr-4">
                                <i class="ti ti-photo text-blue-600 text-base sm:text-lg"></i>
                            </div>
                            <div class="flex-1">
                                <h4 class="font-medium text-gray-900 text-sm sm:text-base">Latar Belakang Kelas</h4>
                                <p class="text-xs text-gray-500 sm:text-sm">Ubah gambar latar belakang kelas</p>
                            </div>
                            <i class="ti ti-chevron-right text-gray-400 text-sm sm:text-base"></i>
                        </button>

                        <!-- Edit Kelas -->
                        <button onclick="openEditClassModal()"
                            class="w-full flex items-center p-3 sm:p-3 text-left hover:bg-orange-50 rounded-lg transition-colors border border-gray-200 hover:border-orange-200">
                            <div
                                class="w-9 h-9 sm:w-10 sm:h-10 bg-green-100 rounded-lg flex items-center justify-center mr-3.5 sm:mr-4">
                                <i class="ti ti-edit text-green-600 text-base sm:text-lg"></i>
                            </div>
                            <div class="flex-1">
                                <h4 class="font-medium text-gray-900 text-sm sm:text-base">Edit Kelas</h4>
                                <p class="text-xs text-gray-500 sm:text-sm">Ubah nama dan mata pelajaran</p>
                            </div>
                            <i class="ti ti-chevron-right text-gray-400 text-sm sm:text-base"></i>
                        </button>

                        <!-- Atur Siswa -->
                        <button onclick="openManageStudentsModal()"
                            class="w-full flex items-center p-3 sm:p-3 text-left hover:bg-orange-50 rounded-lg transition-colors border border-gray-200 hover:border-orange-200">
                            <div
                                class="w-9 h-9 sm:w-10 sm:h-10 bg-purple-100 rounded-lg flex items-center justify-center mr-3.5 sm:mr-4">
                                <i class="ti ti-users text-purple-600 text-base sm:text-lg"></i>
                            </div>
                            <div class="flex-1">
                                <h4 class="font-medium text-gray-900 text-sm sm:text-base">Atur Siswa</h4>
                                <p class="text-xs text-gray-500 sm:text-sm">Kelola siswa yang tergabung</p>
                            </div>
                            <i class="ti ti-chevron-right text-gray-400 text-sm sm:text-base"></i>
                        </button>

                        <!-- Perizinan -->
                        <button onclick="openPermissionsModal()"
                            class="w-full flex items-center p-3 sm:p-3 text-left hover:bg-orange-50 rounded-lg transition-colors border border-gray-200 hover:border-orange-200">
                            <div
                                class="w-9 h-9 sm:w-10 sm:h-10 bg-red-100 rounded-lg flex items-center justify-center mr-3.5 sm:mr-4">
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

                <!-- footer kept minimal; removed backdrop hint as requested -->
                <div class="bg-gray-50 px-4 flex py-3.5 sm:px-6 sm:py-4 text-center">
                    <button type="button" onclick="closeClassSettingsModal()"
                        class="mt-2 sm:mt-0 w-full flex-grow sm:w-auto inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2.5 bg-white text-sm sm:text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        Batal
                    </button>
                </div>
            </el-dialog-panel>
        </div>
    </dialog>
</el-dialog>
<script>
    // Close modal when clicking on backdrop or pressing Escape
    (function () {
        const dialog = document.getElementById('class-settings-modal');
        const backdrop = document.getElementById('class-settings-backdrop');
        const panel = document.getElementById('class-settings-panel');
        if (!dialog) return;

        function closeModal() {
            try { dialog.close(); } catch (e) {
                // fallback: hide
                dialog.setAttribute('data-closed', 'true');
                dialog.style.display = 'none';
            }
        }

        // Expose global function to close this modal from inline onclick handlers
        window.closeClassSettingsModal = closeModal;

        if (backdrop) backdrop.addEventListener('click', closeModal);

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') closeModal();
        });

        // When opening, ensure on mobile the panel is focused
        dialog.addEventListener('show', function () {
            if (window.innerWidth <= 640 && panel) panel.scrollIntoView({ behavior: 'smooth', block: 'end' });
        });
    })();
</script>