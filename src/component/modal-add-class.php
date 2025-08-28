<el-dialog>
    <dialog id="add-class-modal" aria-labelledby="dialog-title" class="fixed inset-0 size-auto max-h-none max-w-none overflow-y-auto bg-transparent backdrop:bg-transparent">
        <el-dialog-backdrop class="fixed inset-0 bg-gray-500/75 transition-opacity data-closed:opacity-0 data-enter:duration-300 data-enter:ease-out data-leave:duration-200 data-leave:ease-in"></el-dialog-backdrop>

        <div tabindex="0" class="flex min-h-full items-end justify-center p-6 text-center focus:outline-none sm:items-center sm:p-0">
            <el-dialog-panel class="relative transform overflow-hidden rounded-lg bg-white text-left shadow-xl transition-all data-closed:translate-y-4 data-closed:opacity-0 data-enter:duration-300 data-enter:ease-out data-leave:duration-200 data-leave:ease-in sm:my-8 sm:w-full sm:max-w-xl data-closed:sm:translate-y-0 data-closed:sm:scale-95">
                <div class="bg-white px-5 pt-6 pb-5 sm:p-7 sm:pb-5">
                    <div class="sm:flex sm:items-start">
                        <div class="mx-auto flex size-14 bg-orange-100 shrink-0 items-center justify-center rounded-full sm:mx-0 sm:size-12">
                            <span class="ti ti-plus text-xl text-orange-600"></span>
                        </div>
                        <div class="mt-4 text-center sm:mt-0 sm:ml-5 sm:text-left">
                            <h3 id="dialog-title" class="text-lg font-semibold text-gray-900">Tambah Kelas Baru</h3>
                            <div class="mt-2">
                                <p class="text-base text-gray-500">Tambahkan kelas baru untuk diajarkan kepada siswa</p>
                            </div>
                        </div>
                    </div>
                    
                    <form id="add-class-form" class="mt-6">
                        <div class="space-y-5">
                            <div>
                                <label for="class-name" class="block text-base font-medium text-gray-700 mb-2">Nama Kelas</label>
                                <input type="text" id="class-name" name="class_name" required
                                    class="mt-1 block w-full px-3 py-3 rounded-md border-2 border-gray-300 shadow-sm focus:border-orange-500 focus:ring-orange-500 text-base"
                                    placeholder="Contoh: Matematika Kelas X">
                            </div>
                            
                            <div>
                                <label for="class-subject" class="block text-base font-medium text-gray-700 mb-2">Mata Pelajaran</label>
                                <select id="class-subject" name="subject" required
                                    class="mt-1 block w-full px-3 py-3 rounded-md border-2 border-gray-300 shadow-sm focus:border-orange-500 focus:ring-orange-500 text-base">
                                    <option value="" disabled selected>Pilih Mata Pelajaran</option>
                                    <option value="matematika">Matematika</option>
                                    <option value="bahasa-indonesia">Bahasa Indonesia</option>
                                    <option value="fisika">Fisika</option>
                                    <option value="kimia">Kimia</option>
                                    <option value="biologi">Biologi</option>
                                    <option value="sejarah">Sejarah</option>
                                </select>
                            </div>
                            
                            <div>
                                <label for="class-description" class="block text-base font-medium text-gray-700 mb-2">Deskripsi Kelas</label>
                                <textarea id="class-description" name="description" rows="4"
                                    class="mt-1 block w-full px-3 py-3 rounded-md border-2 border-gray-300 shadow-sm focus:border-orange-500 focus:ring-orange-500 text-base"
                                    placeholder="Deskripsi singkat tentang kelas ini"></textarea>
                            </div>
                            
                            <div class="grid grid-cols-2 gap-6">
                                <div>
                                    <label for="class-duration" class="block text-base font-medium text-gray-700 mb-2">Durasi (jam/minggu)</label>
                                    <input type="number" id="class-duration" name="duration" required min="1"
                                        class="mt-1 block w-full px-3 py-3 rounded-md border-2 border-gray-300 shadow-sm focus:border-orange-500 focus:ring-orange-500 text-base"
                                        placeholder="2">
                                </div>
                                <div>
                                    <label for="class-capacity" class="block text-base font-medium text-gray-700 mb-2">Kapasitas Siswa</label>
                                    <input type="number" id="class-capacity" name="capacity" required min="1"
                                        class="mt-1 block w-full px-3 py-3 rounded-md border-2 border-gray-300 shadow-sm focus:border-orange-500 focus:ring-orange-500 text-base"
                                        placeholder="30">
                                </div>
                            </div>
                            
                            <div>
                                <label for="class-image" class="block text-base font-medium text-gray-700 mb-2">Gambar Kelas</label>
                                <input type="file" id="class-image" name="class_image" accept="image/*"
                                    class="mt-1 block w-full py-2 px-3 text-base text-gray-500 border-2 border-gray-300 rounded-md
                                    file:mr-4 file:py-2.5 file:px-5
                                    file:rounded-md file:border-0
                                    file:text-base file:font-medium
                                    file:bg-orange-50 file:text-orange-700
                                    hover:file:bg-orange-100">
                            </div>
                        </div>
                    </form>
                </div>
                
                <div class="bg-gray-50 px-5 py-5 sm:px-6 text-center">
                    <button type="submit" form="add-class-form"
                        class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-3 bg-orange-600 text-base font-medium text-white hover:bg-orange-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-orange-500">
                        Tambah Kelas
                    </button>
                    <p class="mt-4 text-center text-sm text-gray-500">Klik atau sentuh mana saja untuk menutup</p>
                </div>
            </el-dialog-panel>
        </div>
    </dialog>
</el-dialog>
        </div>
    </dialog>
</el-dialog>
