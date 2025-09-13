<!-- Modal Atur Siswa -->
<el-dialog>
    <dialog id="manage-students-modal" aria-labelledby="students-title" class="fixed inset-0 size-auto max-h-none max-w-none overflow-y-auto bg-transparent backdrop:bg-transparent">
        <el-dialog-backdrop class="fixed inset-0 bg-gray-500/75 transition-opacity data-closed:opacity-0 data-enter:duration-300 data-enter:ease-out data-leave:duration-200 data-leave:ease-in"></el-dialog-backdrop>

        <div tabindex="0" class="flex min-h-full items-end justify-center p-6 text-center focus:outline-none sm:items-center sm:p-0">
            <el-dialog-panel class="relative transform overflow-hidden rounded-lg bg-white text-left shadow-xl transition-all data-closed:translate-y-4 data-closed:opacity-0 data-enter:duration-300 data-enter:ease-out data-leave:duration-200 data-leave:ease-in sm:my-8 sm:w-full sm:max-w-2xl data-closed:sm:translate-y-0 data-closed:sm:scale-95">
                <div class="bg-white px-5 pt-6 pb-5 sm:p-7 sm:pb-5">
                    <!-- Header dengan tombol kembali -->
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center">
                            <button onclick="backToSettings()" class="flex items-center text-orange-600 hover:text-orange-800 mr-4">
                                <i class="ti ti-arrow-left mr-1"></i>
                                Kembali
                            </button>
                            <h3 id="students-title" class="text-lg font-semibold text-gray-900">Atur Siswa</h3>
                        </div>
                        <div class="text-sm text-gray-500">
                            Total: <span id="student-count"><?php echo $jumlahSiswa; ?></span> siswa
                        </div>
                    </div>
                    
                    <!-- Modal Alert Component -->
                    <?php include 'modal-alert.php'; ?>
                    
                    <!-- Search Bar -->
                    <div class="mb-4">
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="ti ti-search text-gray-400"></i>
                            </div>
                            <input type="text" id="search-students" placeholder="Cari siswa..."
                                class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md leading-5 bg-white placeholder-gray-500 focus:outline-none focus:placeholder-gray-400 focus:ring-1 focus:ring-orange-500 focus:border-orange-500 text-sm">
                        </div>
                    </div>
                    
                    <!-- Students List -->
                    <div class="max-h-96 overflow-y-auto">
                        <div id="students-list" class="space-y-3">
                            <?php if (!empty($siswaKelas)): ?>
                                <?php foreach ($siswaKelas as $siswa): ?>
                                <div class="flex items-center p-3 border border-gray-200 rounded-lg hover:bg-gray-50 student-item" data-student-id="<?php echo $siswa['id']; ?>">
                                    <!-- Foto Profil -->
                                    <div class="w-12 h-12 rounded-full bg-orange-500 flex items-center justify-center text-white font-medium mr-4 shrink-0">
                                        <?php if (!empty($siswa['fotoProfil'])): ?>
                                            <img src="<?php echo htmlspecialchars($siswa['fotoProfil']); ?>" 
                                                 alt="<?php echo htmlspecialchars($siswa['namaLengkap']); ?>" 
                                                 class="w-full h-full object-cover rounded-full">
                                        <?php else: ?>
                                            <?php echo strtoupper(substr($siswa['namaLengkap'], 0, 1)); ?>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <!-- Info Siswa -->
                                    <div class="flex-1 min-w-0">
                                        <h4 class="text-sm font-medium text-gray-900 truncate">
                                            <?php echo htmlspecialchars($siswa['namaLengkap']); ?>
                                        </h4>
                                        <p class="text-sm text-gray-500 truncate">
                                            <?php echo htmlspecialchars($siswa['email']); ?>
                                        </p>
                                        <p class="text-xs text-gray-400">
                                            Bergabung: <?php echo date('d M Y', strtotime($siswa['tanggalBergabung'])); ?>
                                        </p>
                                    </div>
                                    
                                    <!-- Tombol Keluarkan -->
                                    <button onclick="confirmRemoveStudent(<?php echo $siswa['id']; ?>, '<?php echo htmlspecialchars($siswa['namaLengkap'], ENT_QUOTES); ?>')"
                                        class="ml-4 px-3 py-1.5 text-sm font-medium text-red-600 bg-red-50 rounded-md hover:bg-red-100 hover:text-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 shrink-0">
                                        Keluarkan
                                    </button>
                                </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="text-center py-8 text-gray-500">
                                    <i class="ti ti-users text-4xl mb-2"></i>
                                    <p class="text-lg font-medium">Belum ada siswa</p>
                                    <p class="text-sm">Siswa akan muncul setelah bergabung dengan kode kelas</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Info Kode Kelas -->
                    <div class="mt-4 p-3 bg-orange-50 rounded-lg">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-orange-800">Kode Kelas untuk Bergabung</p>
                                <p class="text-xs text-orange-600">Bagikan kode ini kepada siswa</p>
                            </div>
                            <div class="flex items-center space-x-2">
                                <code class="px-3 py-1 bg-white text-orange-800 font-mono font-bold rounded border">
                                    <?php echo htmlspecialchars($detailKelas['kodeKelas']); ?>
                                </code>
                                <button onclick="copyClassCode()" class="p-2 text-orange-600 hover:bg-orange-100 rounded">
                                    <i class="ti ti-copy"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="bg-gray-50 px-5 py-4 sm:px-6 flex justify-between">
                    <button type="button" onclick="backToSettings()" 
                        class="inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-orange-500">
                        Tutup
                    </button>
                </div>
            </el-dialog-panel>
        </div>
    </dialog>
</el-dialog>

<!-- Modal Konfirmasi Keluarkan Siswa -->
<el-dialog>
    <dialog id="confirm-remove-student-modal" aria-labelledby="confirm-title" class="fixed inset-0 size-auto max-h-none max-w-none overflow-y-auto bg-transparent backdrop:bg-transparent">
        <el-dialog-backdrop class="fixed inset-0 bg-gray-500/75 transition-opacity data-closed:opacity-0 data-enter:duration-300 data-enter:ease-out data-leave:duration-200 data-leave:ease-in"></el-dialog-backdrop>

        <div tabindex="0" class="flex min-h-full items-end justify-center p-6 text-center focus:outline-none sm:items-center sm:p-0">
            <el-dialog-panel class="relative transform overflow-hidden rounded-lg bg-white text-left shadow-xl transition-all data-closed:translate-y-4 data-closed:opacity-0 data-enter:duration-300 data-enter:ease-out data-leave:duration-200 data-leave:ease-in sm:my-8 sm:w-full sm:max-w-lg data-closed:sm:translate-y-0 data-closed:sm:scale-95">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="sm:flex sm:items-start">
                        <div class="mx-auto flex size-12 shrink-0 items-center justify-center rounded-full bg-red-100 sm:mx-0 sm:size-10">
                            <i class="ti ti-alert-triangle text-red-600"></i>
                        </div>
                        <div class="mt-3 text-center sm:ml-4 sm:mt-0 sm:text-left">
                            <h3 id="confirm-title" class="text-base font-semibold text-gray-900">Keluarkan Siswa</h3>
                            <div class="mt-2">
                                <p class="text-sm text-gray-500">
                                    Apakah Anda yakin ingin mengeluarkan <strong id="student-name-confirm"></strong> dari kelas? 
                                    Siswa akan kehilangan akses ke semua materi dan postingan kelas.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6">
                    <button type="button" onclick="removeStudent()" id="confirm-remove-btn"
                        class="inline-flex w-full justify-center rounded-md bg-red-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-red-500 sm:ml-3 sm:w-auto">
                        Ya, Keluarkan
                    </button>
                    <button type="button" onclick="closeConfirmRemoveModal()"
                        class="mt-3 inline-flex w-full justify-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50 sm:mt-0 sm:w-auto">
                        Batal
                    </button>
                </div>
            </el-dialog-panel>
        </div>
    </dialog>
</el-dialog>
