<el-dialog>
    <dialog id="submit-assignment-modal" aria-labelledby="dialog-title" class="fixed inset-0 size-auto max-h-none max-w-none overflow-y-auto bg-transparent backdrop:bg-transparent">
        <el-dialog-backdrop class="fixed inset-0 bg-gray-500/75 transition-opacity data-closed:opacity-0 data-enter:duration-300 data-enter:ease-out data-leave:duration-200 data-leave:ease-in"></el-dialog-backdrop>

        <div tabindex="0" class="flex min-h-full items-end justify-center p-6 text-center focus:outline-none sm:items-center sm:p-0">
            <el-dialog-panel class="relative transform overflow-hidden rounded-lg bg-white text-left shadow-xl transition-all data-closed:translate-y-4 data-closed:opacity-0 data-enter:duration-300 data-enter:ease-out data-leave:duration-200 data-leave:ease-in sm:my-8 sm:w-full sm:max-w-2xl data-closed:sm:translate-y-0 data-closed:sm:scale-95">
                <div class="bg-white px-5 pt-6 pb-5 sm:p-7 sm:pb-5">
                    <div class="sm:flex sm:items-start">
                        <div class="mx-auto flex size-14 bg-green-100 shrink-0 items-center justify-center rounded-full sm:mx-0 sm:size-12">
                            <span class="ti ti-upload text-xl text-green-600"></span>
                        </div>
                        <div class="mt-4 text-center sm:mt-0 sm:ml-5 sm:text-left">
                            <h3 id="dialog-title" class="text-lg font-semibold text-gray-900">Kumpulkan Tugas</h3>
                            <div class="mt-2">
                                <p class="text-base text-gray-500" id="assignment-info">Kumpulkan tugas Anda sebelum deadline</p>
                            </div>
                        </div>
                    </div>
                    
                    <form id="submit-assignment-form" class="mt-6" onsubmit="submitAssignment(event)" enctype="multipart/form-data">
                        <input type="hidden" id="assignment_id" name="assignment_id">
                        
                        <div class="space-y-5">
                            <!-- Assignment Details (Read-only) -->
                            <div class="bg-gray-50 p-4 rounded-lg">
                                <h4 class="font-medium text-gray-900 mb-2" id="assignment-title-display">Judul Tugas</h4>
                                <p class="text-sm text-gray-600 mb-2" id="assignment-description-display">Deskripsi tugas</p>
                                <div class="flex items-center text-sm text-gray-500">
                                    <i class="ti ti-clock mr-1"></i>
                                    <span id="assignment-deadline-display">Deadline</span>
                                    <span class="mx-2">•</span>
                                    <i class="ti ti-star mr-1"></i>
                                    <span id="assignment-maxscore-display">Nilai Maks</span>
                                </div>
                            </div>
                            
                            <div>
                                <label for="submission-notes" class="block text-base font-medium text-gray-700 mb-2">Catatan Pengumpulan (Opsional)</label>
                                <textarea id="submission-notes" name="submission_notes" rows="3"
                                    class="mt-1 block w-full px-3 py-3 rounded-md border-2 border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500 text-base"
                                    placeholder="Tambahkan catatan atau penjelasan tambahan untuk tugas Anda"></textarea>
                            </div>
                            
                            <div>
                                <label for="submission-file" class="block text-base font-medium text-gray-700 mb-2">File Tugas</label>
                                <input type="file" id="submission-file" name="submission_file" required accept=".pdf,.doc,.docx,.ppt,.pptx,.txt,.jpg,.jpeg,.png"
                                    class="mt-1 block w-full py-2 px-3 text-base text-gray-500 border-2 border-gray-300 rounded-md
                                    file:mr-4 file:py-2.5 file:px-5
                                    file:rounded-md file:border-0
                                    file:text-base file:font-medium
                                    file:bg-green-50 file:text-green-700
                                    hover:file:bg-green-100">
                                <p class="mt-1 text-sm text-gray-500">Format yang didukung: PDF, DOC, DOCX, PPT, PPTX, TXT, JPG, PNG (Maks 10MB)</p>
                            </div>
                            
                            <!-- Status display for resubmission -->
                            <div id="current-submission-status" class="hidden bg-blue-50 border border-blue-200 rounded-lg p-4">
                                <h5 class="font-medium text-blue-900 mb-2">Status Pengumpulan Saat Ini</h5>
                                <div id="submission-status-content">
                                    <!-- Will be populated by JavaScript -->
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                
                <div class="bg-gray-50 px-5 py-5 sm:px-6 flex flex-col-reverse sm:flex-row sm:justify-end sm:space-x-3">
                    <button type="button" onclick="closeSubmitAssignmentModal()"
                        class="mt-3 sm:mt-0 w-full sm:w-auto inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-3 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                        Batal
                    </button>
                    <button type="submit" form="submit-assignment-form" id="submit-assignment-btn"
                        class="w-full sm:w-auto inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-3 bg-green-600 text-base font-medium text-white hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                        Kumpulkan Tugas
                    </button>
                </div>
            </el-dialog-panel>
        </div>
    </dialog>
</el-dialog>
