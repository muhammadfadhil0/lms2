<!-- Modal Confirm Exam Finish - Component -->
<dialog id="confirmFinishModal" aria-labelledby="finish-dialog-title" class="backdrop:bg-gray-500/75 bg-transparent p-4 max-w-none max-h-none border-none fixed inset-0">
    <div class="modal-content relative transform overflow-hidden rounded-lg bg-white text-left shadow-xl sm:my-8 sm:w-full sm:max-w-lg w-full">
        <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
            <div class="sm:flex sm:items-start">
                <div class="mx-auto flex size-12 shrink-0 items-center justify-center rounded-full bg-blue-100 sm:mx-0 sm:size-10">
                    <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-check w-6 h-6 text-blue-600" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                        <path stroke="none" d="m0 0h24v24H0z" fill="none" />
                        <path d="m5 12l5 5l10 -10" />
                    </svg>
                </div>
                <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                    <h3 id="finish-dialog-title" class="text-base font-semibold text-gray-900">Tandai Ujian Selesai</h3>
                    <div class="mt-2">
                        <p class="text-sm text-gray-500">Apakah Anda yakin ingin menandai ujian ini sebagai selesai? Ujian akan hilang dari tampilan siswa dan dipindahkan ke arsip.</p>
                    </div>
                </div>
            </div>
        </div>
        <div class="bg-gray-50 px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6">
            <button id="confirmFinishBtn" type="button" class="inline-flex w-full justify-center rounded-md bg-blue-600 px-3 py-2 text-sm font-semibold text-white shadow-xs hover:bg-blue-500 sm:ml-3 sm:w-auto items-center disabled:opacity-50 disabled:cursor-not-allowed">
                <span class="finish-btn-text">Tandai Selesai</span>
                <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-loader-2 w-4 h-4 ml-2 animate-spin hidden finish-btn-loading" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                    <path stroke="none" d="m0 0h24v24H0z" fill="none" />
                    <path d="m12 3a9 9 0 1 0 9 9" />
                </svg>
            </button>
            <button id="cancelFinishBtn" type="button" class="mt-3 inline-flex w-full justify-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-xs ring-1 ring-inset ring-gray-300 hover:bg-gray-50 sm:mt-0 sm:w-auto">
                Batal
            </button>
        </div>
    </div>
</dialog>
