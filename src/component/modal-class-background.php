<!-- Modal Pengaturan Latar Belakang -->
<el-dialog>
    <dialog id="class-background-modal" aria-labelledby="background-title" class="fixed inset-0 size-auto max-h-none max-w-none overflow-y-auto bg-transparent backdrop:bg-transparent">
        <el-dialog-backdrop class="fixed inset-0 bg-gray-500/75 transition-opacity data-closed:opacity-0 data-enter:duration-300 data-enter:ease-out data-leave:duration-200 data-leave:ease-in"></el-dialog-backdrop>

        <div tabindex="0" class="flex min-h-full items-end justify-center p-6 text-center focus:outline-none sm:items-center sm:p-0">
            <el-dialog-panel class="relative transform overflow-hidden rounded-lg bg-white text-left shadow-xl transition-all data-closed:translate-y-4 data-closed:opacity-0 data-enter:duration-300 data-enter:ease-out data-leave:duration-200 data-leave:ease-in sm:my-8 sm:w-full sm:max-w-xl data-closed:sm:translate-y-0 data-closed:sm:scale-95">
                <div class="bg-white px-5 pt-6 pb-5 sm:p-7 sm:pb-5">
                    <!-- Header dengan tombol kembali -->
                    <div class="flex items-center mb-4">
                        <button onclick="backToSettings()" class="flex items-center text-orange-600 hover:text-orange-800 mr-4">
                            <i class="ti ti-arrow-left mr-1"></i>
                            Kembali
                        </button>
                        <h3 id="background-title" class="text-lg font-semibold text-gray-900">Latar Belakang Kelas</h3>
                    </div>
                    
                    <!-- Modal Alert Component -->
                    <?php include 'modal-alert.php'; ?>
                    
                    <form id="background-form" onsubmit="updateBackground(event)">
                        <div class="space-y-5">
                            <!-- Preview Gambar Saat Ini -->
                            <div>
                                <label class="block text-base font-medium text-gray-700 mb-2">Gambar Saat Ini</label>
                                <div class="relative w-full h-32 rounded-lg overflow-hidden border-2 border-gray-200">
                                    <img id="current-background" src="<?php echo !empty($detailKelas['gambar_kelas']) ? '../../' . htmlspecialchars($detailKelas['gambar_kelas']) : ''; ?>" 
                                         alt="Latar belakang kelas" class="w-full h-full object-cover <?php echo empty($detailKelas['gambar_kelas']) ? 'hidden' : ''; ?>"
                                         onerror="this.style.display='none'; document.getElementById('no-background').classList.remove('hidden');">
                                    <div id="no-background" class="w-full h-full bg-gradient-to-r from-orange-500 to-orange-600 flex items-center justify-center <?php echo !empty($detailKelas['gambar_kelas']) ? 'hidden' : ''; ?>">
                                        <i class="ti ti-photo text-white text-4xl opacity-50"></i>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Upload Gambar Baru -->
                            <div>
                                <label for="new-background" class="block text-base font-medium text-gray-700 mb-2">Upload Gambar Baru</label>
                                <input type="file" id="new-background" name="background_image" accept="image/*"
                                    class="mt-1 block w-full py-2 px-3 text-base text-gray-500 border-2 border-gray-300 rounded-md
                                    file:mr-4 file:py-2.5 file:px-5
                                    file:rounded-md file:border-0
                                    file:text-base file:font-medium
                                    file:bg-orange-50 file:text-orange-700
                                    hover:file:bg-orange-100"
                                    onchange="previewBackground(this)">
                                <p class="mt-2 text-sm text-gray-500">Format yang didukung: JPG, PNG, GIF (maksimal 5MB)</p>
                            </div>
                            
                            <!-- Preview Gambar Baru -->
                            <div id="new-preview-container" class="hidden">
                                <label class="block text-base font-medium text-gray-700 mb-2">Preview Gambar Baru</label>
                                <div class="relative w-full h-32 rounded-lg overflow-hidden border-2 border-orange-200">
                                    <img id="new-background-preview" src="" alt="Preview" class="w-full h-full object-cover">
                                </div>
                            </div>
                            
                            <!-- Opsi Hapus Gambar -->
                            <div class="flex items-center">
                                <input type="checkbox" id="remove-background" name="remove_background" class="h-4 w-4 text-orange-600 focus:ring-orange-500 border-gray-300 rounded">
                                <label for="remove-background" class="ml-2 block text-sm text-gray-700">
                                    Hapus gambar latar belakang (gunakan warna default)
                                </label>
                            </div>
                        </div>
                    </form>
                </div>
                
                <div class="bg-gray-50 px-5 py-5 sm:px-6 flex justify-between">
                    <button type="button" onclick="backToSettings()" 
                        class="inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-orange-500">
                        Batal
                    </button>
                    <button id="save-background-btn" type="submit" form="background-form"
                        class="inline-flex items-center justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-orange-600 text-base font-medium text-white hover:bg-orange-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-orange-500">
                        <svg id="save-background-spinner" class="animate-spin -ml-1 mr-2 h-5 w-5 text-white hidden" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                        </svg>
                        <span id="save-background-text">Simpan</span>
                    </button>
                </div>
            </el-dialog-panel>
        </div>
    </dialog>
</el-dialog>
