<el-dialog>
    <dialog id="upload-material-modal" aria-labelledby="material-dialog-title" class="fixed inset-0 size-auto max-h-none max-w-none overflow-y-auto bg-transparent backdrop:bg-transparent">
        <el-dialog-backdrop class="fixed inset-0 bg-gray-500/75 transition-opacity data-closed:opacity-0 data-enter:duration-300 data-enter:ease-out data-leave:duration-200 data-leave:ease-in"></el-dialog-backdrop>

        <div tabindex="0" class="flex min-h-full items-end justify-center p-6 text-center focus:outline-none sm:items-center sm:p-0">
            <el-dialog-panel class="relative transform overflow-hidden rounded-lg bg-white text-left shadow-xl transition-all data-closed:translate-y-4 data-closed:opacity-0 data-enter:duration-300 data-enter:ease-out data-leave:duration-200 data-leave:ease-in sm:my-8 sm:w-full sm:max-w-2xl data-closed:sm:translate-y-0 data-closed:sm:scale-95">
                <div class="bg-white px-5 pt-6 pb-5 sm:p-7 sm:pb-5">
                    <div class="sm:flex sm:items-start">
                        <div class="mx-auto flex size-14 bg-green-100 shrink-0 items-center justify-center rounded-full sm:mx-0 sm:size-12">
                            <span class="ti ti-upload text-xl text-green-600"></span>
                        </div>
                        <div class="mt-4 text-center sm:mt-0 sm:ml-5 sm:text-left">
                            <h3 id="material-dialog-title" class="text-lg font-semibold text-gray-900">Upload Materi Pembelajaran</h3>
                            <div class="mt-2">
                                <p class="text-base text-gray-500">Kelola materi pembelajaran untuk siswa</p>
                            </div>
                        </div>
                    </div>

                    <!-- Upload Form -->
                    <form id="upload-material-form" class="mt-6" enctype="multipart/form-data">
                        <div class="space-y-5">
                            <div>
                                <label for="material-file" class="block text-base font-medium text-gray-700 mb-2">File Materi</label>
                                <input type="file" id="material-file" name="material_file" accept=".pdf,.doc,.docx,.ppt,.pptx,.jpg,.jpeg,.png" required
                                    class="mt-1 block w-full py-2 px-3 text-base text-gray-500 border-2 border-gray-300 rounded-md
                                    file:mr-4 file:py-2.5 file:px-5
                                    file:rounded-md file:border-0
                                    file:text-base file:font-medium
                                    file:bg-green-50 file:text-green-700
                                    hover:file:bg-green-100">
                                <p class="mt-2 text-sm text-gray-500">Format yang didukung: PDF, DOC, DOCX, PPT, PPTX, JPG, PNG (Maks. 20MB)</p>
                            </div>
                        </div>
                    </form>

                    <!-- Existing Materials -->
                    <div class="mt-8">
                        <h4 class="text-base font-semibold text-gray-900 mb-4">Materi yang Sudah Diupload</h4>
                        <div id="existing-materials" class="space-y-3 max-h-60 overflow-y-auto">
                            <!-- Will be populated by JavaScript -->
                            <div class="text-center py-8 text-gray-500">
                                <i class="ti ti-loader animate-spin text-2xl mb-2"></i>
                                <p class="text-sm">Memuat materi...</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="bg-gray-50 px-5 py-5 sm:px-6 flex flex-col sm:flex-row-reverse gap-3">
                    <button type="submit" form="upload-material-form"
                        class="w-full sm:w-auto inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-3 bg-green-600 text-base font-medium text-white hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                        Upload Materi
                    </button>
                    <button type="button" onclick="closeMaterialModal()"
                        class="w-full sm:w-auto inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-3 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                        Tutup
                    </button>
                </div>
            </el-dialog-panel>
        </div>
    </dialog>
</el-dialog>
