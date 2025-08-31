<el-dialog>
    <dialog id="upload-schedule-modal" aria-labelledby="schedule-dialog-title" class="fixed inset-0 size-auto max-h-none max-w-none overflow-y-auto bg-transparent backdrop:bg-transparent">
        <el-dialog-backdrop class="fixed inset-0 bg-gray-500/75 transition-opacity data-closed:opacity-0 data-enter:duration-300 data-enter:ease-out data-leave:duration-200 data-leave:ease-in"></el-dialog-backdrop>

    <div tabindex="0" class="flex min-h-full items-center justify-center p-4 text-center focus:outline-none sm:p-0">
            <el-dialog-panel class="relative transform overflow-hidden rounded-lg bg-white text-left shadow-xl transition-all data-closed:translate-y-4 data-closed:opacity-0 data-enter:duration-300 data-enter:ease-out data-leave:duration-200 data-leave:ease-in sm:my-8 sm:w-full sm:max-w-2xl data-closed:sm:translate-y-0 data-closed:sm:scale-95">
        <div class="bg-white px-4 pt-5 pb-4 sm:p-7 sm:pb-5">
                    <div class="sm:flex sm:items-start">
            <div class="mx-auto flex size-12 bg-blue-100 shrink-0 items-center justify-center rounded-full sm:mx-0 sm:size-12">
                <span class="ti ti-calendar-plus text-lg text-blue-600 sm:text-xl"></span>
                        </div>
                        <div class="mt-4 text-center sm:mt-0 sm:ml-5 sm:text-left">
                <h3 id="schedule-dialog-title" class="text-base font-semibold text-gray-900 sm:text-lg">Upload Jadwal Kelas</h3>
                            <div class="mt-2">
                <p class="text-sm text-gray-500 sm:text-base">Kelola jadwal kelas untuk siswa</p>
                            </div>
                        </div>
                    </div>

                    <!-- Upload Form -->
                    <form id="upload-schedule-form" class="mt-5" enctype="multipart/form-data">
                        <div class="space-y-4 sm:space-y-5">
                            <div>
                                <label for="schedule-file" class="block text-sm font-medium text-gray-700 mb-1.5 sm:text-base sm:mb-2">File Jadwal</label>
                                <input type="file" id="schedule-file" name="schedule_file" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png" required
                                    class="mt-1 block w-full py-2 px-3 text-sm sm:text-base text-gray-500 border-2 border-gray-300 rounded-md
                                    file:mr-3 file:py-2 file:px-4 sm:file:py-2.5 sm:file:px-5
                                    file:rounded-md file:border-0
                                    file:text-sm sm:file:text-base file:font-medium
                                    file:bg-blue-50 file:text-blue-700
                                    hover:file:bg-blue-100 focus:outline-none focus:ring-2 focus:ring-blue-500/40">
                                <p class="mt-2 text-xs text-gray-500 sm:text-sm">Format yang didukung: PDF, DOC, DOCX, JPG, PNG (Maks. 10MB)</p>
                            </div>
                        </div>
                    </form>

                    <!-- Existing Schedules -->
                    <div class="mt-7 sm:mt-8">
                        <h4 class="text-sm font-semibold text-gray-900 mb-3 sm:text-base sm:mb-4">Jadwal yang Sudah Diupload</h4>
                        <div id="existing-schedules" class="space-y-2 sm:space-y-3 max-h-56 sm:max-h-60 overflow-y-auto">
                            <!-- Will be populated by JavaScript -->
                            <div class="text-center py-6 sm:py-8 text-gray-500">
                                <i class="ti ti-loader animate-spin text-xl sm:text-2xl mb-2"></i>
                                <p class="text-xs sm:text-sm">Memuat jadwal...</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="bg-gray-50 px-4 py-4 sm:px-6 sm:py-5 flex flex-col sm:flex-row-reverse gap-2.5 sm:gap-3">
                    <button type="submit" form="upload-schedule-form"
                        class="w-full sm:w-auto inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2.5 bg-blue-600 text-sm sm:text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        Upload Jadwal
                    </button>
                    <button type="button" onclick="closeScheduleModal()"
                        class="w-full sm:w-auto inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2.5 bg-white text-sm sm:text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        Tutup
                    </button>
                </div>
            </el-dialog-panel>
        </div>
    </dialog>
</el-dialog>
