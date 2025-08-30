<el-dialog>
    <dialog id="create-assignment-modal" aria-labelledby="dialog-title" class="fixed inset-0 size-auto max-h-none max-w-none overflow-y-auto bg-transparent backdrop:bg-transparent">
        <el-dialog-backdrop class="fixed inset-0 bg-gray-500/75 transition-opacity data-closed:opacity-0 data-enter:duration-300 data-enter:ease-out data-leave:duration-200 data-leave:ease-in"></el-dialog-backdrop>

        <div tabindex="0" class="flex min-h-full items-end justify-center p-6 text-center focus:outline-none sm:items-center sm:p-0">
            <el-dialog-panel class="relative transform overflow-hidden rounded-lg bg-white text-left shadow-xl transition-all data-closed:translate-y-4 data-closed:opacity-0 data-enter:duration-300 data-enter:ease-out data-leave:duration-200 data-leave:ease-in sm:my-8 sm:w-full sm:max-w-2xl data-closed:sm:translate-y-0 data-closed:sm:scale-95">
                <div class="bg-white px-5 pt-6 pb-5 sm:p-7 sm:pb-5">
                    <div class="sm:flex sm:items-start">
                        <div class="mx-auto flex size-14 bg-blue-100 shrink-0 items-center justify-center rounded-full sm:mx-0 sm:size-12">
                            <span class="ti ti-clipboard-list text-xl text-blue-600"></span>
                        </div>
                        <div class="mt-4 text-center sm:mt-0 sm:ml-5 sm:text-left">
                            <h3 id="dialog-title" class="text-lg font-semibold text-gray-900">Buat Tugas Baru</h3>
                            <div class="mt-2">
                                <p class="text-base text-gray-500">Buat tugas untuk siswa di kelas ini</p>
                            </div>
                        </div>
                    </div>
                    
                    <form id="create-assignment-form" class="mt-6">
                        <div class="space-y-5">
                            <div>
                                <label for="assignmentTitle" class="block text-base font-medium text-gray-700 mb-2">Judul Tugas</label>
                                <input type="text" id="assignmentTitle" name="assignmentTitle" required
                                    class="mt-1 block w-full px-3 py-3 rounded-md border-2 border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-base"
                                    placeholder="Contoh: Tugas Matematika Bab 1">
                            </div>
                            
                            <div>
                                <label for="assignmentDescription" class="block text-base font-medium text-gray-700 mb-2">Deskripsi Tugas</label>
                                <textarea id="assignmentDescription" name="assignmentDescription" rows="4" required
                                    class="mt-1 block w-full px-3 py-3 rounded-md border-2 border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-base"
                                    placeholder="Jelaskan detail tugas yang harus dikerjakan siswa"></textarea>
                            </div>
                            
                            <div>
                                <label for="assignment-file" class="block text-base font-medium text-gray-700 mb-2">File Tugas (Opsional)</label>
                                <input type="file" id="assignment-file" name="assignment_file" accept=".pdf,.doc,.docx,.ppt,.pptx,.txt"
                                    class="mt-1 block w-full py-2 px-3 text-base text-gray-500 border-2 border-gray-300 rounded-md
                                    file:mr-4 file:py-2.5 file:px-5
                                    file:rounded-md file:border-0
                                    file:text-base file:font-medium
                                    file:bg-blue-50 file:text-blue-700
                                    hover:file:bg-blue-100">
                                <p class="mt-1 text-sm text-gray-500">Format yang didukung: PDF, DOC, DOCX, PPT, PPTX, TXT (Maks 10MB)</p>
                            </div>
                            
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label for="assignmentDeadline" class="block text-base font-medium text-gray-700 mb-2">Tanggal Deadline</label>
                                    <input type="datetime-local" id="assignmentDeadline" name="assignmentDeadline" required
                                        class="mt-1 block w-full px-3 py-3 rounded-md border-2 border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-base">
                                </div>
                                
                                <div>
                                    <label for="maxScore" class="block text-base font-medium text-gray-700 mb-2">Nilai Maksimal</label>
                                    <input type="number" id="maxScore" name="maxScore" min="1" max="1000" value="100" required
                                        class="mt-1 block w-full px-3 py-3 rounded-md border-2 border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-base">
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                
                <div class="bg-gray-50 px-5 py-5 sm:px-6 flex flex-col-reverse sm:flex-row sm:justify-end sm:space-x-3">
                    <button type="button" onclick="closeCreateAssignmentModal()"
                        class="mt-3 sm:mt-0 w-full sm:w-auto inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-3 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        Batal
                    </button>
                    <button type="submit" form="create-assignment-form"
                        class="w-full sm:w-auto inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-3 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        Buat Tugas
                    </button>
                </div>
            </el-dialog-panel>
        </div>
    </dialog>
</el-dialog>
