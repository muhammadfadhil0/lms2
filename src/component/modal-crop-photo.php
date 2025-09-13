<!-- Crop Photo Modal -->
<div id="cropPhotoModal" class="modal-overlay" style="display: none;">
    <div class="modal-content-crop">
        <div class="bg-white rounded-lg shadow-xl max-w-md w-full mx-4">
            <!-- Header -->
            <div class="p-4 border-b border-gray-200">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-gray-900">Crop Foto Profil</h3>
                    <button onclick="closeCropModal()" class="text-gray-400 hover:text-gray-600 transition-colors">
                        <i class="ti ti-x text-xl"></i>
                    </button>
                </div>
            </div>
            
            <!-- Crop Area -->
            <div class="p-4">
                <div class="crop-container">
                    <img id="cropImage" src="" alt="Crop Image" style="max-width: 100%; display: none;">
                </div>
                
                <!-- Loading State -->
                <div id="cropLoading" class="text-center py-8" style="display: none;">
                    <div class="inline-flex items-center">
                        <div class="animate-spin rounded-full h-6 w-6 border-b-2 border-orange"></div>
                        <span class="ml-2 text-gray-600">Memproses foto...</span>
                    </div>
                </div>
            </div>
            
            <!-- Footer -->
            <div class="p-4 border-t border-gray-200">
                <div class="flex justify-end space-x-3">
                    <button onclick="closeCropModal()" class="px-4 py-2 text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors">
                        Batal
                    </button>
                    <button onclick="saveCroppedPhoto()" id="saveCropBtn" class="px-4 py-2 bg-orange text-white rounded-lg hover:bg-orange-600 transition-colors" disabled>
                        <i class="ti ti-device-floppy mr-2"></i>Simpan
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
