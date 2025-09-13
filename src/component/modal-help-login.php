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
                                     <p class="text-sm text-gray-500">Tim IT Support kami siap membantu Anda melalui WhatsApp untuk menyelesaikan masalah login dengan cepat.</p>
                                 </div>
                             </div>
                         </div>
                     </div>
                     <div class="bg-gray-50 px-4 py-3 sm:px-6 text-center">
                         <a href="https://wa.me/6281234567890?text=Halo%2C%20saya%20mengalami%20masalah%20login%20di%20sistem%20Point.%20Mohon%20bantuannya." target="_blank" class="inline-flex w-full justify-center rounded-md bg-green-600 px-3 py-2 text-sm font-semibold text-white shadow-xs hover:bg-green-500 items-center">
                             <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-brand-whatsapp w-4 h-4 mr-2" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                 <path stroke="none" d="m0 0h24v24H0z" fill="none" />
                                 <path d="m3 21l1.65 -3.8a9 9 0 1 1 3.4 2.9l-5.05 .9" />
                                 <path d="m9 10a.5 .5 0 0 0 1 0v-1a.5 .5 0 0 0 -1 0v1a5 5 0 0 0 5 5h1a.5 .5 0 0 0 0 -1h-1a.5 .5 0 0 0 0 1" />
                             </svg>
                             Hubungi WhatsApp
                         </a>
                         <p class="mt-3 text-center text-xs text-gray-400">Klik atau sentuh mana saja untuk menutup</p>
                     </div>
                 </el-dialog-panel>
             </div>
         </dialog>
     </el-dialog>