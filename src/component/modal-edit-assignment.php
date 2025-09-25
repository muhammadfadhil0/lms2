<!-- Modal Edit Assignment -->
<el-dialog>
    <dialog id="modalEditAssignment" aria-labelledby="edit-assignment-title" class="fixed inset-0 size-auto max-h-none max-w-none overflow-y-auto bg-transparent backdrop:bg-transparent">
        <el-dialog-backdrop class="fixed inset-0 bg-gray-500/75 transition-opacity data-closed:opacity-0 data-enter:duration-300 data-enter:ease-out data-leave:duration-200 data-leave:ease-in"></el-dialog-backdrop>

        <div tabindex="0" class="flex min-h-full items-center justify-center p-2 px-2 text-center focus:outline-none sm:p-0">
            <el-dialog-panel class="relative transform overflow-hidden rounded-lg bg-white text-left shadow-xl transition-all data-closed:translate-y-4 data-closed:opacity-0 data-enter:duration-300 data-enter:ease-out data-leave:duration-200 data-leave:ease-in my-2 mx-2 sm:my-8 sm:mx-0 sm:w-full sm:max-w-2xl data-closed:sm:translate-y-0 data-closed:sm:scale-95">
                <div class="bg-white px-5 pt-4 pb-4 sm:p-7 sm:pb-5">
                    <div class="sm:flex sm:items-start">
                        <div class="mx-auto flex size-12 bg-blue-100 shrink-0 items-center justify-center rounded-full sm:mx-0 sm:size-12">
                            <span class="ti ti-edit text-lg text-blue-600 sm:text-xl"></span>
                        </div>
                        <div class="mt-4 text-center sm:mt-0 sm:ml-5 sm:text-left">
                            <h3 id="edit-assignment-title" class="text-base font-semibold text-gray-900 sm:text-lg">Edit Tugas</h3>
                            <div class="mt-2">
                                <p class="text-sm text-gray-500 sm:text-base">Edit informasi tugas untuk siswa di kelas ini</p>
                            </div>
                        </div>
                    </div>
                    
                    <form id="edit-assignment-form" class="mt-5">
                        <input type="hidden" id="editAssignmentId" name="assignment_id">
                        
                        <div class="space-y-4 sm:space-y-5">
                            <div>
                                <label for="editAssignmentTitle" class="block text-sm font-medium text-gray-700 mb-1.5 sm:text-base sm:mb-2">Judul Tugas</label>
                                <input type="text" id="editAssignmentTitle" name="assignmentTitle" required
                                    class="mt-1 block w-full px-3 py-2.5 rounded-md border-2 border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm sm:text-base"
                                    placeholder="Contoh: Tugas Matematika Bab 1">
                            </div>
                            
                            <div>
                                <label for="editAssignmentDescription" class="block text-sm font-medium text-gray-700 mb-1.5 sm:text-base sm:mb-2">Deskripsi Tugas</label>
                                <textarea id="editAssignmentDescription" name="assignmentDescription" rows="4" required
                                    class="mt-1 block w-full px-3 py-2.5 rounded-md border-2 border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm sm:text-base"
                                    placeholder="Jelaskan detail tugas yang harus dikerjakan siswa"></textarea>
                            </div>
                            
                            <!-- File Saat Ini -->
                            <div id="currentAssignmentFileContainer">
                                <label class="block text-sm font-medium text-gray-700 mb-1.5 sm:text-base sm:mb-2">File Tugas Saat Ini</label>
                                <div id="currentAssignmentFile" class="mb-3">
                                    <!-- File saat ini akan dimuat di sini -->
                                </div>
                            </div>
                            
                            <div>
                                <label for="editAssignmentFiles" class="block text-sm font-medium text-gray-700 mb-1.5 sm:text-base sm:mb-2">Tambah/Ganti File Tugas (Opsional) - Maksimal 4 File Total</label>
                                
                                <!-- Files Preview Container for New Files -->
                                <div id="editAssignmentFilesPreview" class="mb-3 space-y-2">
                                    <!-- New file previews will be added here dynamically -->
                                </div>
                                
                                <!-- Upload Button -->
                                <div id="editAssignmentFileUploadContainer" class="mb-3">
                                    <input type="file" id="editAssignmentFiles" name="assignment_files[]" multiple
                                        accept=".pdf,.doc,.docx,.ppt,.pptx,.txt,.xls,.xlsx,.zip,.rar,.jpg,.jpeg,.png,.gif,.mp4,.mp3,.avi,.mov"
                                        onchange="handleEditAssignmentFilesSelect(this)"
                                        class="hidden">
                                    
                                    <button type="button" onclick="document.getElementById('editAssignmentFiles').click()" 
                                        id="editAssignmentAddFileBtn"
                                        class="w-full flex items-center justify-center px-4 py-3 border-2 border-dashed border-gray-300 rounded-md hover:border-blue-500 hover:bg-blue-50 transition-colors">
                                        <i class="ti ti-plus text-gray-400 mr-2"></i>
                                        <span class="text-sm text-gray-600">Tambah File Tugas</span>
                                        <span id="editFileCountIndicator" class="ml-2 text-xs text-gray-500">(0/4)</span>
                                    </button>
                                </div>
                                
                                <p class="mt-1 text-xs text-gray-500 sm:text-sm">Format yang didukung: PDF, DOC, DOCX, PPT, PPTX, TXT, XLS, XLSX, ZIP, RAR, JPG, PNG, GIF, MP4, MP3, AVI, MOV (Maks 10MB per file, 4 file maksimal)</p>
                            </div>
                            
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label for="editAssignmentDeadline" class="block text-sm font-medium text-gray-700 mb-1.5 sm:text-base sm:mb-2">Tanggal Deadline</label>
                                    <input type="datetime-local" id="editAssignmentDeadline" name="assignmentDeadline" required
                                        class="mt-1 block w-full px-3 py-2.5 rounded-md border-2 border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm sm:text-base">
                                </div>
                                
                                <div>
                                    <label for="editMaxScore" class="block text-sm font-medium text-gray-700 mb-1.5 sm:text-base sm:mb-2">Nilai Maksimal</label>
                                    <input type="number" id="editMaxScore" name="maxScore" min="1" max="1000" required
                                        class="mt-1 block w-full px-3 py-2.5 rounded-md border-2 border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm sm:text-base">
                                </div>
                            </div>
                        </div>
                    </form>
                </div>

                <div class="bg-gray-50 px-3 py-3 sm:px-6 sm:py-5 flex sm:flex-row sm:justify-end sm:space-x-3 gap-2 sm:gap-0" style="align-items: end;">
                    <button type="button" onclick="closeEditAssignmentModal()"
                        class="mt-2 sm:mt-0 w-full sm:w-auto inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2.5 bg-white text-sm sm:text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        Batal
                    </button>
                    <button type="submit" form="edit-assignment-form"
                        class="w-full sm:w-auto inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2.5 bg-blue-600 text-sm sm:text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        Simpan Perubahan
                    </button>
                </div>
            </el-dialog-panel>
        </div>
    </dialog>
</el-dialog>