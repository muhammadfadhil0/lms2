     <!-- modal ini di gunakan memunculkan help ke it support -->
     <el-dialog>
         <dialog id="help-login" aria-labelledby="dialog-title" class="fixed inset-0 size-auto max-h-none max-w-none overflow-y-auto bg-transparent backdrop:bg-transparent">
             <el-dialog-backdrop class="fixed inset-0 bg-gray-500/75 transition-opacity data-closed:opacity-0 data-enter:duration-300 data-enter:ease-out data-leave:duration-200 data-leave:ease-in"></el-dialog-backdrop>

             <div tabindex="0" class="flex min-h-full items-end justify-center p-4 text-center focus:outline-none sm:items-center sm:p-0">
                 <el-dialog-panel class="relative transform overflow-hidden rounded-lg bg-white text-left shadow-xl transition-all data-closed:translate-y-4 data-closed:opacity-0 data-enter:duration-300 data-enter:ease-out data-leave:duration-200 data-leave:ease-in sm:my-8 sm:w-full sm:max-w-lg data-closed:sm:translate-y-0 data-closed:sm:scale-95">
                     <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                         <div class="sm:flex sm:items-start">
                             <div class="mx-auto flex size-12 shrink-0 items-center justify-center rounded-full bg-blue-100 sm:mx-0 sm:size-10">
                                 <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-help w-6 h-6 text-blue-600" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                     <path stroke="none" d="m0 0h24v24H0z" fill="none" />
                                     <path d="m12 16v.01" />
                                     <path d="m12 13a2 2 0 0 0 .914 -3.782a1.98 1.98 0 0 0 -2.414 .483" />
                                     <path d="m12 3c7.2 0 9 1.8 9 9s-1.8 9 -9 9s-9 -1.8 -9 -9s1.8 -9 9 -9z" />
                                 </svg>
                             </div>
                             <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                                 <h3 id="dialog-title" class="text-base font-semibold text-gray-900">Mengalami kesulitan untuk login?</h3>
                                 <div class="mt-2">
                                     <p class="text-sm text-gray-500">Pilih masalah yang Anda alami:</p>
                                 </div>
                             </div>
                         </div>
                     </div>
                     <div class="bg-gray-50 px-4 py-3 sm:px-6">
                         <div class="space-y-3">
                             <!-- Option 1: Lupa Akun -->
                             <button onclick="window.location.href='src/front/forgot-username-step1.php'" class="w-full flex items-center justify-between rounded-lg border border-gray-200 bg-white px-4 py-3 text-left hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                                 <div class="flex items-center">
                                     <div class="flex-shrink-0">
                                         <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                             <path stroke="none" d="m0 0h24v24H0z" fill="none" />
                                             <path d="M12 12m-9 0a9 9 0 1 0 18 0a9 9 0 1 0 -18 0" />
                                             <path d="M12 10m-3 0a3 3 0 1 0 6 0a3 3 0 1 0 -6 0" />
                                             <path d="M6.168 18.849a4 4 0 0 1 3.832 -2.849h4a4 4 0 0 1 3.834 2.855" />
                                         </svg>
                                     </div>
                                     <div class="ml-3">
                                         <p class="text-sm font-medium text-gray-900">Saya lupa akun saya</p>
                                         <p class="text-xs text-gray-500">Username atau email tidak diingat</p>
                                     </div>
                                 </div>
                                 <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-400" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                     <path stroke="none" d="m0 0h24v24H0z" fill="none" />
                                     <path d="M9 6l6 6l-6 6" />
                                 </svg>
                             </button>

                             <!-- Option 2: Lupa Password -->
                             <button class="w-full flex items-center justify-between rounded-lg border border-gray-200 bg-white px-4 py-3 text-left hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                                 <div class="flex items-center">
                                     <div class="flex-shrink-0">
                                         <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                             <path stroke="none" d="m0 0h24v24H0z" fill="none" />
                                             <path d="M5 13a2 2 0 0 1 2 -2h10a2 2 0 0 1 2 2v6a2 2 0 0 1 -2 2H7a2 2 0 0 1 -2 -2v-6z" />
                                             <path d="M11 16a1 1 0 1 0 2 0a1 1 0 0 0 -2 0" />
                                             <path d="M8 11V7a4 4 0 1 1 8 0v4" />
                                         </svg>
                                     </div>
                                     <div class="ml-3">
                                         <p class="text-sm font-medium text-gray-900">Saya lupa password saya</p>
                                         <p class="text-xs text-gray-500">Password tidak diingat atau ingin reset</p>
                                     </div>
                                 </div>
                                 <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-400" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                     <path stroke="none" d="m0 0h24v24H0z" fill="none" />
                                     <path d="M9 6l6 6l-6 6" />
                                 </svg>
                             </button>
                         </div>
                         
                         <p class="mt-4 text-center text-xs text-gray-400">Klik atau sentuh mana saja untuk menutup</p>
                     </div>
                 </el-dialog-panel>
             </div>
         </dialog>
     </el-dialog>