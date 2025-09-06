<el-dialog>
    <dialog id="add-class-modal" aria-labelledby="dialog-title" class="fixed inset-0 size-auto max-h-none max-w-none overflow-y-auto bg-transparent backdrop:bg-transparent z-50">
        <el-dialog-backdrop class="fixed inset-0 bg-gray-500/75 transition-opacity data-closed:opacity-0 data-enter:duration-300 data-enter:ease-out data-leave:duration-200 data-leave:ease-in"></el-dialog-backdrop>

        <div tabindex="0" class="flex min-h-full items-end justify-center p-3 sm:p-6 text-center focus:outline-none sm:items-center sm:p-0">
            <el-dialog-panel class="relative transform overflow-hidden rounded-lg bg-white text-left shadow-xl transition-all data-closed:translate-y-4 data-closed:opacity-0 data-enter:duration-300 data-enter:ease-out data-leave:duration-200 data-leave:ease-in sm:my-8 sm:w-full sm:max-w-md data-closed:sm:translate-y-0 data-closed:sm:scale-95">
                <style>
                    @media (max-width: 640px) {
                        #add-class-modal .modal-header {
                            padding: 1rem 1rem 0.5rem;
                        }
                        #add-class-modal .modal-body {
                            padding: 0.5rem 1rem;
                        }
                        #add-class-modal .modal-footer {
                            padding: 0.75rem 1rem 1rem;
                        }
                        #add-class-modal .modal-title {
                            font-size: 1rem;
                            line-height: 1.25;
                        }
                        #add-class-modal .modal-form label {
                            font-size: 0.875rem;
                            margin-bottom: 0.25rem;
                        }
                        #add-class-modal .modal-form input,
                        #add-class-modal .modal-form select,
                        #add-class-modal .modal-form textarea {
                            font-size: 0.875rem;
                            padding: 0.5rem 0.75rem;
                        }
                        #add-class-modal .modal-footer button {
                            font-size: 0.875rem;
                            padding: 0.625rem 1rem;
                        }
                    }
                </style>
                <div class="modal-header bg-white px-4 pt-5 pb-3 sm:px-6 sm:pt-6 sm:pb-4">
                    <div class="flex items-center">
                        <div class="flex size-10 bg-orange-tipis shrink-0 items-center justify-center rounded-lg">
                            <i class="ti ti-plus text-orange-600 text-lg"></i>
                        </div>
                        <div class="ml-3">
                            <h3 id="dialog-title" class="modal-title text-lg font-semibold text-gray-900">Tambah Kelas</h3>
                        </div>
                    </div>
                </div>

                <div class="modal-body">
                    <form id="add-class-form" class="modal-form" onsubmit="createKelas(event)">
                        <div class="space-y-4">
                            <div>
                                <label for="namaKelas" class="block text-sm font-medium text-gray-700 mb-1">Nama Kelas</label>
                                <input type="text" id="namaKelas" name="namaKelas" required
                                    class="block w-full px-3 py-2 rounded-md border border-gray-300 shadow-sm focus:border-orange-500 focus:ring-orange-500 text-sm"
                                    placeholder="Matematika Kelas X">
                            </div>

                            <div>
                                <label for="mataPelajaran" class="block text-sm font-medium text-gray-700 mb-1">Mata Pelajaran</label>
                                <select id="mataPelajaran" name="mataPelajaran" required
                                    class="block w-full px-3 py-2 rounded-md border border-gray-300 shadow-sm focus:border-orange-500 focus:ring-orange-500 text-sm">
                                    <option value="" disabled selected>Pilih Mata Pelajaran</option>
                                    <option value="Matematika">Matematika</option>
                                    <option value="Bahasa Indonesia">Bahasa Indonesia</option>
                                    <option value="Informatika">Informatika</option>
                                    <option value="Fisika">Fisika</option>
                                    <option value="Kimia">Kimia</option>
                                    <option value="Biologi">Biologi</option>
                                    <option value="Sejarah">Sejarah</option>
                                    <option value="Geografi">Geografi</option>
                                    <option value="Bahasa Inggris">Bahasa Inggris</option>
                                    <option value="Seni Budaya">Seni Budaya</option>
                                    <option value="Olahraga">Olahraga</option>
                                </select>
                            </div>

                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label for="maxSiswa" class="block text-sm font-medium text-gray-700 mb-1">Max Siswa</label>
                                    <input type="number" id="maxSiswa" name="maxSiswa" min="1" max="100" value="30"
                                        class="block w-full px-3 py-2 rounded-md border border-gray-300 shadow-sm focus:border-orange-500 focus:ring-orange-500 text-sm">
                                </div>
                                <div>
                                    <label for="class-image" class="block text-sm font-medium text-gray-700 mb-1">Gambar</label>
                                    <input type="file" id="class-image" name="class_image" accept="image/*"
                                        class="block w-full py-2 px-3 text-sm text-gray-500 border border-gray-300 rounded-md
                                        file:mr-2 file:py-1 file:px-2
                                        file:rounded file:border-0
                                        file:text-xs file:font-medium
                                        file:bg-orange-50 file:text-orange-700
                                        hover:file:bg-orange-100">
                                </div>
                            </div>

                            <div>
                                <label for="deskripsi" class="block text-sm font-medium text-gray-700 mb-1">Deskripsi</label>
                                <textarea id="deskripsi" name="deskripsi" rows="2"
                                    class="block w-full px-3 py-2 rounded-md border border-gray-300 shadow-sm focus:border-orange-500 focus:ring-orange-500 text-sm"
                                    placeholder="Deskripsi singkat kelas..."></textarea>
                            </div>
                        </div>
                    </form>
                </div>

                <div class="modal-footer bg-gray-50 px-4 py-4 sm:px-6 sm:py-5">
                    <div class="flex gap-3">
                        <button type="button" onclick="document.getElementById('add-class-modal').close()"
                            class="flex-1 px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-orange-500">
                            Batal
                        </button>
                        <button type="submit" form="add-class-form"
                            class="flex-1 px-4 py-2 bg-orange-600 text-sm font-medium text-white rounded-md hover:bg-orange-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-orange-500">
                            Tambah
                        </button>
                    </div>
                </div>
            </el-dialog-panel>
        </div>
    </dialog>
</el-dialog>