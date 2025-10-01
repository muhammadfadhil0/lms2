<!-- Modal Edit Kelas -->
<el-dialog>
    <dialog id="edit-class-modal" aria-labelledby="edit-title" class="fixed inset-0 size-auto max-h-none max-w-none overflow-y-auto bg-transparent backdrop:bg-transparent">
        <el-dialog-backdrop class="fixed inset-0 bg-gray-500/75 transition-opacity data-closed:opacity-0 data-enter:duration-300 data-enter:ease-out data-leave:duration-200 data-leave:ease-in"></el-dialog-backdrop>

        <div tabindex="0" class="flex min-h-full items-end justify-center text-center focus:outline-none sm:items-center sm:p-0">
            <el-dialog-panel class="relative transform overflow-hidden rounded-lg bg-white text-left shadow-xl transition-all data-closed:translate-y-4 data-closed:opacity-0 data-enter:duration-300 data-enter:ease-out data-leave:duration-200 data-leave:ease-in sm:my-8 sm:w-full sm:max-w-lg data-closed:sm:translate-y-0 data-closed:sm:scale-95">
                <div class="bg-white px-5 pt-6 pb-5 sm:p-7 sm:pb-5">
                    <!-- Header dengan tombol kembali -->
                    <div class="flex items-center mb-4">
                        <button onclick="backToSettings()" class="flex items-center text-orange-600 hover:text-orange-800 mr-4">
                            <i class="ti ti-arrow-left mr-1"></i>
                            Kembali
                        </button>
                        <h3 id="edit-title" class="text-lg font-semibold text-gray-900">Edit Kelas</h3>
                    </div>
                    
                    <!-- Modal Alert Component -->
                    <?php include 'modal-alert.php'; ?>
                    
                    <form id="edit-class-form" onsubmit="updateClass(event)">
                        <div class="space-y-5">
                            <div>
                                <label for="edit-namaKelas" class="block text-base font-medium text-gray-700 mb-2">Nama Kelas</label>
                                <input type="text" id="edit-namaKelas" name="namaKelas" required
                                    value="<?php echo htmlspecialchars($detailKelas['namaKelas']); ?>"
                                    class="mt-1 block w-full px-3 py-3 rounded-md border-2 border-gray-300 shadow-sm focus:border-orange-500 focus:ring-orange-500 text-base"
                                    placeholder="Contoh: Matematika Kelas X">
                            </div>
                            
                            <div>
                                <label for="edit-deskripsi" class="block text-base font-medium text-gray-700 mb-2">Deskripsi Kelas</label>
                                <textarea id="edit-deskripsi" name="deskripsi" rows="4"
                                    class="mt-1 block w-full px-3 py-3 rounded-md border-2 border-gray-300 shadow-sm focus:border-orange-500 focus:ring-orange-500 text-base"
                                    placeholder="Deskripsi singkat tentang kelas ini"><?php echo htmlspecialchars($detailKelas['deskripsi'] ?? ''); ?></textarea>
                            </div>
                        </div>
                    </form>
                </div>
                
                <div class="bg-gray-50 px-5 py-5 sm:px-6 flex justify-between">
                    <button type="button" onclick="backToSettings()" 
                        class="inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-orange-500">
                        Batal
                    </button>
                    <button id="save-editclass-btn" type="submit" form="edit-class-form"
                        class="inline-flex items-center justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-orange-600 text-base font-medium text-white hover:bg-orange-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-orange-500">
                        <svg id="save-editclass-spinner" class="animate-spin -ml-1 mr-2 h-5 w-5 text-white hidden" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                        </svg>
                        <span id="save-editclass-text">Simpan Perubahan</span>
                    </button>
                </div>
            </el-dialog-panel>
        </div>
    </dialog>
</el-dialog>
