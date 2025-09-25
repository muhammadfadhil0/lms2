<!-- Modal Delete Class - Component -->
<el-dialog>
    <dialog id="deleteClassModal" aria-labelledby="delete-class-dialog-title" class="fixed inset-0 size-auto max-h-none max-w-none overflow-y-auto bg-transparent backdrop:bg-transparent">
        <el-dialog-backdrop class="fixed inset-0 bg-gray-500/75 transition-opacity data-closed:opacity-0 data-enter:duration-300 data-enter:ease-out data-leave:duration-200 data-leave:ease-in"></el-dialog-backdrop>

        <div tabindex="0" class="flex min-h-full items-end justify-center p-4 text-center focus:outline-none sm:items-center sm:p-0">
            <el-dialog-panel class="relative transform overflow-hidden rounded-lg bg-white text-left shadow-xl transition-all data-closed:translate-y-4 data-closed:opacity-0 data-enter:duration-300 data-enter:ease-out data-leave:duration-200 data-leave:ease-in sm:my-8 sm:w-full sm:max-w-lg data-closed:sm:translate-y-0 data-closed:sm:scale-95">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="sm:flex sm:items-start">
                        <div class="mx-auto flex size-12 shrink-0 items-center justify-center rounded-full bg-red-100 sm:mx-0 sm:size-10">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-trash w-6 h-6 text-red-600" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                <path stroke="none" d="m0 0h24v24H0z" fill="none" />
                                <path d="m4 7l16 0" />
                                <path d="m10 11l0 6" />
                                <path d="m14 11l0 6" />
                                <path d="m5 7l1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2 -2l1 -12" />
                                <path d="m9 7v-3a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v3" />
                            </svg>
                        </div>
                        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                            <h3 id="delete-class-dialog-title" class="text-base font-semibold text-gray-900">Hapus Kelas</h3>
                            <div class="mt-2">
                                <p class="text-sm text-gray-500">Apakah Anda yakin ingin menghapus kelas "<span id="classNameToDelete" class="font-medium text-gray-700"></span>"?</p>
                                <p class="text-sm text-gray-500 mt-2">Tindakan ini tidak dapat dibatalkan dan akan menghapus:</p>
                                <ul class="text-sm text-gray-500 mt-1 ml-4 list-disc">
                                    <li>Semua data siswa dalam kelas</li>
                                    <li>Semua ujian dan hasil ujian</li>
                                    <li>Semua postingan dan materi</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6">
                    <button id="confirmDeleteClassBtn" type="button" class="inline-flex w-full justify-center rounded-md bg-red-600 px-3 py-2 text-sm font-semibold text-white shadow-xs hover:bg-red-500 sm:ml-3 sm:w-auto items-center disabled:opacity-50 disabled:cursor-not-allowed">
                        <span class="delete-class-btn-text">Hapus Kelas</span>
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-loader-2 w-4 h-4 ml-2 animate-spin hidden delete-class-btn-loading" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                            <path stroke="none" d="m0 0h24v24H0z" fill="none" />
                            <path d="m12 3a9 9 0 1 0 9 9" />
                        </svg>
                    </button>
                    <button id="cancelDeleteClassBtn" type="button" class="mt-3 inline-flex w-full justify-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-xs ring-1 ring-inset ring-gray-300 hover:bg-gray-50 sm:mt-0 sm:w-auto">
                        Batal
                    </button>
                </div>
            </el-dialog-panel>
        </div>
    </dialog>
</el-dialog>