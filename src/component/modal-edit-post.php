<!-- Modal Edit Post -->
<el-dialog>
    <dialog id="modalEditPost" aria-labelledby="edit-post-title" class="fixed inset-0 size-auto max-h-none max-w-none overflow-y-auto bg-transparent backdrop:bg-transparent">
        <el-dialog-backdrop class="fixed inset-0 bg-gray-500/75 transition-opacity data-closed:opacity-0 data-enter:duration-300 data-enter:ease-out data-leave:duration-200 data-leave:ease-in"></el-dialog-backdrop>

        <div tabindex="0" class="flex min-h-full items-end justify-center p-6 text-center focus:outline-none sm:items-center sm:p-0">
            <el-dialog-panel class="relative transform overflow-hidden rounded-lg bg-white text-left shadow-xl transition-all data-closed:translate-y-4 data-closed:opacity-0 data-enter:duration-300 data-enter:ease-out data-leave:duration-200 data-leave:ease-in sm:my-8 sm:w-full sm:max-w-2xl data-closed:sm:translate-y-0 data-closed:sm:scale-95">
                <div class="bg-white px-5 pt-6 pb-5 sm:p-7 sm:pb-5">
                    <!-- Header -->
                    <div class="flex items-center mb-4">
                        <h3 id="edit-post-title" class="text-lg font-semibold text-gray-900">Edit Postingan</h3>
                        <button type="button" class="close-modal-edit-post ml-auto text-gray-400 hover:text-gray-600">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                    
                    <form id="editPostForm">
                        <input type="hidden" id="editPostId" name="postingan_id">
                        
                        <div class="space-y-5">
                            <!-- Konten Postingan -->
                            <div>
                                <label for="editPostContent" class="block text-base font-medium text-gray-700 mb-2">
                                    Konten Postingan
                                </label>
                                <textarea 
                                    id="editPostContent" 
                                    name="konten" 
                                    rows="4" 
                                    class="mt-1 block w-full py-3 px-4 text-base border-2 border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-orange-500 resize-none"
                                    placeholder="Tulis konten postingan..."
                                    required
                                ></textarea>
                            </div>
                            
                            <!-- Gambar Saat Ini -->
                            <div id="currentImagesContainer">
                                <label class="block text-base font-medium text-gray-700 mb-2">
                                    Gambar Saat Ini
                                </label>
                                <div id="currentImagesList" class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                                    <!-- Gambar akan dimuat di sini -->
                                </div>
                            </div>
                            
                            <!-- Tambah Gambar Baru -->
                            <div>
                                <label class="block text-base font-medium text-gray-700 mb-2">
                                    Tambah Gambar Baru
                                </label>
                                <div class="flex items-center gap-3">
                                    <input 
                                        type="file" 
                                        id="editPostImages" 
                                        name="images[]" 
                                        multiple 
                                        accept="image/*"
                                        class="hidden"
                                    >
                                    <button 
                                        type="button" 
                                        id="addImageBtn"
                                        class="flex items-center justify-center w-20 h-20 border-2 border-dashed border-gray-300 rounded-lg hover:border-orange-400 hover:bg-orange-50 transition-colors"
                                    >
                                        <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                        </svg>
                                    </button>
                                    <div id="newImagesPreview" class="flex gap-2 flex-wrap flex-1">
                                        <!-- Preview gambar baru akan ditampilkan di sini -->
                                    </div>
                                </div>
                                <p class="mt-2 text-sm text-gray-500">Format yang didukung: JPG, PNG, GIF (maksimal 5MB per gambar)</p>
                            </div>
                        </div>
                    </form>
                </div>
                
                <div class="bg-gray-50 px-5 py-5 sm:px-6 flex justify-between">
                    <button type="button" class="close-modal-edit-post inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-orange-500">
                        Batal
                    </button>
                    <button type="submit" form="editPostForm" class="inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-orange-600 text-base font-medium text-white hover:bg-orange-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-orange-500">
                        Simpan Perubahan
                    </button>
                </div>
            </el-dialog-panel>
        </div>
    </dialog>
</el-dialog>
