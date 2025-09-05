<!-- Profile Photo Dropdown Modal -->
<div id="profilePhotoDropdown" class="modal-overlay" style="display: none;">
    <div class="modal-content-dropdown">
        <div class="bg-white rounded-lg shadow-xl max-w-xs w-full mx-4">
            <!-- Header -->
            <div class="p-4 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900">Foto Profil</h3>
            </div>
            
            <!-- Menu Options -->
            <div class="p-2">
                <!-- Upload New Photo -->
                <button onclick="selectNewPhoto()" class="w-full flex items-center px-3 py-2 text-left text-gray-700 hover:bg-gray-100 rounded-lg transition-colors">
                    <i class="ti ti-upload mr-3 text-blue-500"></i>
                    <span>Upload Foto Baru</span>
                </button>
                
                <!-- Delete Current Photo (shown only if user has photo) -->
                <button onclick="deleteCurrentPhoto()" class="w-full flex items-center px-3 py-2 text-left text-red-600 hover:bg-red-50 rounded-lg transition-colors" id="deletePhotoBtn" style="display: none;">
                    <i class="ti ti-trash mr-3"></i>
                    <span>Hapus Foto</span>
                </button>
            </div>
            
            <!-- Footer -->
            <div class="p-3 border-t border-gray-200">
                <button onclick="closeProfileDropdown()" class="w-full px-4 py-2 text-gray-600 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors text-sm">
                    Batal
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Hidden File Input -->
<input type="file" id="photoFileInput" accept="image/*" style="display: none;" onchange="handleFileSelect(event)">
