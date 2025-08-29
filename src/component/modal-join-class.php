<el-dialog>
    <dialog id="join-class-modal" aria-labelledby="dialog-title" class="fixed inset-0 size-auto max-h-none max-w-none overflow-y-auto bg-transparent backdrop:bg-transparent">
        <el-dialog-backdrop class="fixed inset-0 bg-gray-500/75 transition-opacity data-closed:opacity-0 data-enter:duration-300 data-enter:ease-out data-leave:duration-200 data-leave:ease-in"></el-dialog-backdrop>

        <div tabindex="0" class="flex min-h-full items-end justify-center p-6 text-center focus:outline-none sm:items-center sm:p-0">
            <el-dialog-panel class="relative transform overflow-hidden rounded-lg bg-white text-left shadow-xl transition-all data-closed:translate-y-4 data-closed:opacity-0 data-enter:duration-300 data-enter:ease-out data-leave:duration-200 data-leave:ease-in sm:my-8 sm:w-full sm:max-w-md data-closed:sm:translate-y-0 data-closed:sm:scale-95">
                <div class="bg-white px-5 pt-6 pb-5 sm:p-7 sm:pb-5">
                    <!-- Modal Alert Component -->
                    <?php include 'modal-alert.php'; ?>
                    
                    <div class="sm:flex sm:items-start">
                        <div class="mx-auto flex size-14 bg-orange-100 shrink-0 items-center justify-center rounded-full sm:mx-0 sm:size-12">
                            <span class="ti ti-user-plus text-xl text-orange-600"></span>
                        </div>
                        <div class="mt-4 text-center sm:mt-0 sm:ml-5 sm:text-left">
                            <h3 id="dialog-title" class="text-lg font-semibold text-gray-900">Gabung Kelas</h3>
                            <div class="mt-2">
                                <p class="text-base text-gray-500">Masukkan kode kelas untuk bergabung</p>
                            </div>
                        </div>
                    </div>
                    
                    <form id="join-class-form" class="mt-6" onsubmit="event.preventDefault(); submitJoinKelas();">
                        <div class="space-y-5">
                            <div>
                                <label for="kodeKelas" class="block text-base font-medium text-gray-700 mb-2">Kode Kelas</label>
                                <input type="text" id="kodeKelas" name="kodeKelas" required
                                    class="mt-1 block w-full px-3 py-3 rounded-md border-2 border-gray-300 shadow-sm focus:border-orange-500 focus:ring-orange-500 text-base uppercase"
                                    placeholder="Contoh: ABC123"
                                    style="text-transform: uppercase;">
                                <p class="mt-2 text-sm text-gray-500">Dapatkan kode kelas dari guru Anda</p>
                            </div>
                        </div>
                    </form>
                </div>
                
                <div class="bg-gray-50 px-5 py-5 sm:px-6 flex flex-col space-y-3">
                    <button type="button" onclick="submitJoinKelas()" id="join-class-submit-btn"
                        class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-3 bg-orange-600 text-base font-medium text-white hover:bg-orange-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-orange-500">
                        <i class="ti ti-user-plus mr-2"></i>
                        Gabung Kelas
                    </button>
                    <button type="button" command="close" commandfor="dialog" 
                        class="w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-orange-500">
                        Batal
                    </button>
                    <p class="mt-4 text-center text-sm text-gray-500">Klik atau sentuh mana saja untuk menutup</p>
                </div>
            </el-dialog-panel>
        </div>
    </dialog>
</el-dialog>
