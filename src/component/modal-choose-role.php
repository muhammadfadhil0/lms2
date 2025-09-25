<!-- Modal untuk memilih role siswa atau guru -->
<el-dialog>
    <dialog id="choose-role" aria-labelledby="dialog-title" class="fixed inset-0 size-auto max-h-none max-w-none overflow-y-auto bg-transparent backdrop:bg-transparent">
        <el-dialog-backdrop class="fixed inset-0 bg-gray-500/75 transition-opacity data-closed:opacity-0 data-enter:duration-300 data-enter:ease-out data-leave:duration-200 data-leave:ease-in"></el-dialog-backdrop>

        <div tabindex="0" class="flex min-h-full items-end justify-center p-4 text-center focus:outline-none sm:items-center sm:p-0">
            <el-dialog-panel class="relative transform overflow-hidden rounded-lg bg-white text-left shadow-xl transition-all data-closed:translate-y-4 data-closed:opacity-0 data-enter:duration-300 data-enter:ease-out data-leave:duration-200 data-leave:ease-in sm:my-8 sm:w-full sm:max-w-4xl data-closed:sm:translate-y-0 data-closed:sm:scale-95">
                <div class="bg-white px-6 pt-6 pb-4">
                    <div class="text-center">
                        <h3 id="dialog-title" class="text-xl font-bold text-gray-900">Sebelum itu, apa yang Anda inginkan?</h3>
                        <p class="mt-1 text-sm text-gray-600">Daftar sebagai siswa atau guru untuk memulai perjalanan belajar Anda</p>
                    </div>
                </div>
                
                <div class="bg-gray-50 px-6 py-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <!-- Card Siswa -->
                        <button onclick="redirectToRegister('siswa')" class="w-full bg-white rounded-xl border-2 border-gray-200 hover:border-blue-500 hover:shadow-md transition-all duration-200 p-6 text-left group">
                            <div class="flex items-start">
                                <div class="flex-shrink-0">
                                    <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center group-hover:bg-blue-200 transition-colors">
                                        <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                                        </svg>
                                    </div>
                                </div>
                                <div class="ml-4 flex-1">
                                    <h4 class="text-lg font-semibold text-gray-900 group-hover:text-blue-700">Saya Siswa</h4>
                                    <p class="text-sm text-gray-600 mt-1 mb-3">Bergabunglah untuk mengakses materi pembelajaran dan mengerjakan tugas</p>
                                    
                                    <div class="space-y-2">
                                        <div class="flex items-center text-xs text-gray-600">
                                            <svg class="w-3 h-3 text-green-500 mr-2 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                            </svg>
                                            <span>Akses materi pembelajaran</span>
                                        </div>
                                        <div class="flex items-center text-xs text-gray-600">
                                            <svg class="w-3 h-3 text-green-500 mr-2 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                            </svg>
                                            <span>Mengerjakan tugas dan ujian</span>
                                        </div>
                                        <div class="flex items-center text-xs text-gray-600">
                                            <svg class="w-3 h-3 text-green-500 mr-2 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                            </svg>
                                            <span>Melihat nilai dan progress</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="ml-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400 group-hover:text-blue-500" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                        <path stroke="none" d="m0 0h24v24H0z" fill="none" />
                                        <path d="M9 6l6 6l-6 6" />
                                    </svg>
                                </div>
                            </div>
                        </button>

                        <!-- Card Guru -->
                        <button onclick="redirectToRegister('guru')" class="w-full bg-white rounded-xl border-2 border-gray-200 hover:border-orange-500 hover:shadow-md transition-all duration-200 p-6 text-left group">
                            <div class="flex items-start">
                                <div class="flex-shrink-0">
                                    <div class="w-12 h-12 bg-orange-100 rounded-lg flex items-center justify-center group-hover:bg-orange-200 transition-colors">
                                        <svg class="w-6 h-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                                        </svg>
                                    </div>
                                </div>
                                <div class="ml-4 flex-1">
                                    <h4 class="text-lg font-semibold text-gray-900 group-hover:text-orange-700">Saya Guru</h4>
                                    <p class="text-sm text-gray-600 mt-1 mb-3">Bergabunglah untuk membuat kelas dan mengelola pembelajaran</p>
                                    
                                    <div class="space-y-2">
                                        <div class="flex items-center text-xs text-gray-600">
                                            <svg class="w-3 h-3 text-green-500 mr-2 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                            </svg>
                                            <span>Membuat dan mengelola kelas</span>
                                        </div>
                                        <div class="flex items-center text-xs text-gray-600">
                                            <svg class="w-3 h-3 text-green-500 mr-2 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                            </svg>
                                            <span>Upload materi pembelajaran</span>
                                        </div>
                                        <div class="flex items-center text-xs text-gray-600">
                                            <svg class="w-3 h-3 text-green-500 mr-2 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                            </svg>
                                            <span>Memantau progress siswa</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="ml-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400 group-hover:text-orange-500" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                        <path stroke="none" d="m0 0h24v24H0z" fill="none" />
                                        <path d="M9 6l6 6l-6 6" />
                                    </svg>
                                </div>
                            </div>
                        </button>
                    </div>
                    
                    <div class="mt-6 text-center">
                        <p class="text-xs text-gray-500">
                            Sudah punya akun? 
                            <button onclick="document.getElementById('choose-role').close()" class="font-medium text-orange hover:text-orange-600">Kembali ke login</button>
                        </p>
                    </div>
                    
                    <p class="mt-3 text-center text-xs text-gray-400">Klik atau sentuh mana saja untuk menutup</p>
                </div>
            </el-dialog-panel>
        </div>
    </dialog>
</el-dialog>

<script>
function redirectToRegister(role) {
    // Detect current path to determine the correct redirect URL
    const currentPath = window.location.pathname;
    let redirectUrl;
    
    if (currentPath.includes('/src/front/')) {
        // We're already in the front folder (e.g., register.php)
        redirectUrl = `register.php?role=${role}`;
    } else {
        // We're in the root directory (e.g., login.php)
        redirectUrl = `src/front/register.php?role=${role}`;
    }
    
    window.location.href = redirectUrl;
}
</script>