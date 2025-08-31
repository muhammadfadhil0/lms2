<el-dialog>
    <dialog id="join-class-modal" aria-labelledby="dialog-title" class="fixed inset-0 size-auto max-h-none max-w-none overflow-y-auto bg-transparent backdrop:bg-transparent">
        <el-dialog-backdrop class="fixed inset-0 bg-gray-500/75 transition-opacity data-closed:opacity-0 data-enter:duration-300 data-enter:ease-out data-leave:duration-200 data-leave:ease-in"></el-dialog-backdrop>

        <div tabindex="0" class="flex min-h-full items-end justify-center p-4 text-center focus:outline-none sm:items-center sm:p-0">
            <el-dialog-panel class="relative w-full max-w-sm sm:max-w-md transform overflow-hidden rounded-xl bg-white text-left shadow-xl transition-all data-closed:translate-y-4 data-closed:opacity-0 data-enter:duration-300 data-enter:ease-out data-leave:duration-200 data-leave:ease-in sm:my-8 data-closed:sm:translate-y-0 data-closed:sm:scale-95">
                <div class="bg-white px-4 pt-5 pb-4 sm:px-7 sm:pt-7 sm:pb-5">
                    <!-- Modal Alert Component -->
                    <?php include 'modal-alert.php'; ?>
                    
                    <div class="flex items-center">
                        <div class="flex items-center justify-center w-12 h-12 rounded-full bg-orange-100 sm:w-12 sm:h-12">
                            <span class="ti ti-user-plus text-lg text-orange-600"></span>
                        </div>
                        <div class="ml-4 min-w-0">
                            <h3 id="dialog-title" class="text-base sm:text-lg font-semibold text-gray-900 leading-tight">Gabung Kelas</h3>
                            <p class="mt-1 text-xs sm:text-sm text-gray-500 truncate">Masukkan kode yang diberikan guru</p>
                        </div>
                    </div>
                    
                    <form id="join-class-form" class="mt-5 sm:mt-6" onsubmit="event.preventDefault(); submitJoinKelas();">
                        <div class="space-y-4">
                            <div>
                                <label for="kodeKelas" class="block text-sm font-medium text-gray-700 mb-1">Kode Kelas</label>
                                <div class="relative">
                                    <input type="text" id="kodeKelas" name="kodeKelas" required maxlength="10" autocomplete="off" inputmode="text"
                                        class="tracking-widest caret-orange-600 placeholder-gray-400 block w-full px-3 py-2 rounded-md border border-gray-300 focus:border-orange-500 focus:ring-2 focus:ring-orange-500 focus:ring-offset-0 text-sm font-semibold uppercase font-mono"
                                        placeholder="ABC123"
                                        style="text-transform: uppercase;">
                                    <button type="button" onclick="document.getElementById('kodeKelas').value=''; document.getElementById('kodeKelas').focus();" class="absolute inset-y-0 right-0 px-3 flex items-center text-gray-400 hover:text-gray-600">
                                        <i class="ti ti-x text-sm"></i>
                                    </button>
                                </div>
                                <p class="mt-2 text-[11px] leading-snug text-gray-500">Huruf & angka tanpa spasi. Contoh: <span class="font-semibold text-gray-700">ABC123</span></p>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="bg-gray-50 px-4 py-4 sm:px-6 sm:py-5 border-t border-gray-100">
                    <div class="flex flex-col sm:flex-row-reverse sm:justify-between gap-2 sm:gap-3">
                        <button type="button" onclick="submitJoinKelas()" id="join-class-submit-btn"
                            class="w-full inline-flex justify-center items-center rounded-md px-4 py-2.5 bg-orange-600 text-sm font-medium text-white hover:bg-orange-700 focus:outline-none focus:ring-2 focus:ring-orange-500 shadow-sm">
                            <i class="ti ti-user-plus mr-2 text-base"></i>
                            Gabung
                        </button>
                    </div>
                    <p class="mt-3 text-center text-[11px] text-gray-400">Tap area selain modal untuk tutup</p>
                </div>
            </el-dialog-panel>
        </div>
    </dialog>
</el-dialog>
