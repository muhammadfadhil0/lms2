<!-- Modal Pengaturan Perizinan -->
<el-dialog>
    <dialog id="class-permissions-modal" aria-labelledby="permissions-title" class="fixed inset-0 size-auto max-h-none max-w-none overflow-y-auto bg-transparent backdrop:bg-transparent">
        <el-dialog-backdrop class="fixed inset-0 bg-gray-500/75 transition-opacity data-closed:opacity-0 data-enter:duration-300 data-enter:ease-out data-leave:duration-200 data-leave:ease-in"></el-dialog-backdrop>

        <div tabindex="0" class="flex min-h-full items-end justify-center p-6 text-center focus:outline-none sm:items-center sm:p-0">
            <el-dialog-panel class="relative transform overflow-hidden rounded-lg bg-white text-left shadow-xl transition-all data-closed:translate-y-4 data-closed:opacity-0 data-enter:duration-300 data-enter:ease-out data-leave:duration-200 data-leave:ease-in sm:my-8 sm:w-full sm:max-w-lg data-closed:sm:translate-y-0 data-closed:sm:scale-95">
                <div class="bg-white px-5 pt-6 pb-5 sm:p-7 sm:pb-5">
                    <!-- Header dengan tombol kembali -->
                    <div class="flex items-center mb-4">
                        <button onclick="backToSettings()" class="flex items-center text-orange-600 hover:text-orange-800 mr-4">
                            <i class="ti ti-arrow-left mr-1"></i>
                            Kembali
                        </button>
                        <h3 id="permissions-title" class="text-lg font-semibold text-gray-900">Pengaturan Perizinan</h3>
                    </div>
                    
                    <!-- Modal Alert Component -->
                    <?php include 'modal-alert.php'; ?>
                    
                    <form id="permissions-form" onsubmit="updatePermissions(event)">
                        <div class="space-y-6">
                            <!-- Batasi Hak Memposting -->
                            <div class="flex items-start">
                                <div class="flex items-center h-5">
                                    <input id="restrict-posting" name="restrict_posting" type="checkbox" 
                                           class="focus:ring-orange-500 h-4 w-4 text-orange-600 border-gray-300 rounded"
                                           <?php echo isset($detailKelas['restrict_posting']) && $detailKelas['restrict_posting'] ? 'checked' : ''; ?>>
                                </div>
                                <div class="ml-3 text-sm">
                                    <label for="restrict-posting" class="font-medium text-gray-700">Batasi Hak Memposting</label>
                                    <p class="text-gray-500">Jika diaktifkan, hanya guru yang dapat membuat postingan baru. Siswa tidak akan melihat form posting.</p>
                                </div>
                            </div>
                            
                            <!-- Batasi Hak Komentar -->
                            <div class="flex items-start">
                                <div class="flex items-center h-5">
                                    <input id="restrict-comments" name="restrict_comments" type="checkbox" 
                                           class="focus:ring-orange-500 h-4 w-4 text-orange-600 border-gray-300 rounded"
                                           <?php echo isset($detailKelas['restrict_comments']) && $detailKelas['restrict_comments'] ? 'checked' : ''; ?>>
                                </div>
                                <div class="ml-3 text-sm">
                                    <label for="restrict-comments" class="font-medium text-gray-700">Batasi Hak Komentar</label>
                                    <p class="text-gray-500">Jika diaktifkan, siswa tidak dapat melihat atau membuat komentar pada postingan.</p>
                                </div>
                            </div>
                            
                            <!-- Kunci Kelas -->
                            <div class="flex items-start">
                                <div class="flex items-center h-5">
                                    <input id="lock-class" name="lock_class" type="checkbox" 
                                           class="focus:ring-orange-500 h-4 w-4 text-orange-600 border-gray-300 rounded"
                                           <?php echo isset($detailKelas['lock_class']) && $detailKelas['lock_class'] ? 'checked' : ''; ?>>
                                </div>
                                <div class="ml-3 text-sm">
                                    <label for="lock-class" class="font-medium text-gray-700">Kunci Kelas</label>
                                    <p class="text-gray-500">Jika diaktifkan, mahasiswa baru tidak dapat bergabung ke kelas ini. Kode kelas akan ditolak.</p>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Informasi Efek Pengaturan -->
                        <div class="mt-6 p-4 bg-blue-50 rounded-lg">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <i class="ti ti-info-circle text-blue-400"></i>
                                </div>
                                <div class="ml-3">
                                    <h3 class="text-sm font-medium text-blue-800">Informasi Pengaturan</h3>
                                    <div class="mt-2 text-sm text-blue-700">
                                        <ul class="list-disc pl-5 space-y-1">
                                            <li>Perubahan pengaturan akan langsung berlaku untuk semua siswa</li>
                                            <li>Siswa yang sedang online akan melihat perubahan setelah memuat ulang halaman</li>
                                            <li>Anda dapat mengubah pengaturan kapan saja</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Debug Info (Hidden by default) -->
                        <div id="debug-permissions" class="mt-4 p-3 bg-gray-50 rounded text-xs" style="display: none;">
                            <strong>Debug PHP Values:</strong><br>
                            restrict_posting: <?php echo isset($detailKelas['restrict_posting']) ? $detailKelas['restrict_posting'] : 'NOT SET'; ?><br>
                            restrict_comments: <?php echo isset($detailKelas['restrict_comments']) ? $detailKelas['restrict_comments'] : 'NOT SET'; ?><br>
                            lock_class: <?php echo isset($detailKelas['lock_class']) ? $detailKelas['lock_class'] : 'NOT SET'; ?>
                        </div>
                    </form>
                </div>
                
                <div class="bg-gray-50 px-5 py-5 sm:px-6 flex justify-between">
                    <button type="button" onclick="backToSettings()" 
                        class="inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-orange-500">
                        Batal
                    </button>
                    <button type="submit" form="permissions-form"
                        class="inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-orange-600 text-base font-medium text-white hover:bg-orange-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-orange-500">
                        Simpan Pengaturan
                    </button>
                </div>
            </el-dialog-panel>
        </div>
    </dialog>
</el-dialog>
