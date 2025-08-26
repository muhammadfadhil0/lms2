<el-dialog>
         <dialog id="logout-modal" aria-labelledby="dialog-title" class="fixed inset-0 size-auto max-h-none max-w-none overflow-y-auto bg-transparent backdrop:bg-transparent">
             <el-dialog-backdrop class="fixed inset-0 bg-gray-500/75 transition-opacity data-closed:opacity-0 data-enter:duration-300 data-enter:ease-out data-leave:duration-200 data-leave:ease-in"></el-dialog-backdrop>

             <div tabindex="0" class="flex min-h-full items-end justify-center p-4 text-center focus:outline-none sm:items-center sm:p-0">
                 <el-dialog-panel class="relative transform overflow-hidden rounded-lg bg-white text-left shadow-xl transition-all data-closed:translate-y-4 data-closed:opacity-0 data-enter:duration-300 data-enter:ease-out data-leave:duration-200 data-leave:ease-in sm:my-8 sm:w-full sm:max-w-lg data-closed:sm:translate-y-0 data-closed:sm:scale-95">
                     <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                         <div class="sm:flex sm:items-start">
                             <div class="mx-auto flex size-12 bg-red-100 shrink-0 items-center justify-center rounded-full bg-blue-100 sm:mx-0 sm:size-10">
                                <span class="ti ti-alert-square-rounded text-lg text-red-500"></span>
                             </div>
                             <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                                 <h3 id="dialog-title" class="text-base font-semibold text-gray-900">Ingin Keluar?</h3>
                                 <div class="mt-2">
                                     <p class="text-sm text-gray-500">Anda akan keluar dari akun Anda dan harus login kembali untuk kembali</p>
                                 </div>
                             </div>
                         </div>
                     </div>
                     <div class="bg-gray-50 px-4 py-3 sm:px-6 text-center">
                         <a href="../logic/logout.php" class="inline-flex w-full justify-center rounded-md bg-red-600 px-3 py-2 text-sm font-semibold text-white shadow-xs hover:bg-red-500 items-center">
                             Keluar
                         </a>
                         <p class="mt-3 text-center text-xs text-gray-400">Klik atau sentuh mana saja untuk menutup</p>
                     </div>
                 </el-dialog-panel>
             </div>
         </dialog>
     </el-dialog>