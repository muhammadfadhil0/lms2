<!-- Alert Ujian Sudah Selesai -->
<div id="alert-ujian-selesai" class="hidden fixed top-4 right-4 z-50 transform transition-all duration-300 ease-in-out translate-x-full">
    <div class="bg-white border-l-4 border-red-500 rounded-lg shadow-lg p-4 max-w-sm w-full">
        <div class="flex items-start">
            <div class="flex-shrink-0">
                <i class="ti ti-circle-x text-red-500 text-xl"></i>
            </div>
            <div class="ml-3 flex-1">
                <h3 class="text-sm font-medium text-gray-900">Ujian Sudah Selesai</h3>
                <p class="mt-1 text-sm text-gray-600">
                    Anda sudah menyelesaikan ujian ini sebelumnya. Silakan lihat hasil ujian atau pilih ujian lain yang tersedia.
                </p>
            </div>
            <div class="ml-4 flex-shrink-0">
                <button id="btn-tutup-alert-selesai" class="inline-flex text-gray-400 hover:text-gray-600 focus:outline-none focus:text-gray-600 transition ease-in-out duration-150">
                    <i class="ti ti-x text-lg"></i>
                </button>
            </div>
        </div>
    </div>
</div>

<script>
// Function untuk menampilkan alert ujian sudah selesai
function showAlertUjianSelesai() {
    const alert = document.getElementById('alert-ujian-selesai');
    alert.classList.remove('hidden');
    
    // Trigger reflow untuk memastikan transisi berjalan
    alert.offsetHeight;
    
    // Slide in dari kanan
    alert.classList.remove('translate-x-full');
    
    // Auto hide setelah 5 detik
    setTimeout(() => {
        hideAlertUjianSelesai();
    }, 5000);
}

// Function untuk menyembunyikan alert ujian sudah selesai
function hideAlertUjianSelesai() {
    const alert = document.getElementById('alert-ujian-selesai');
    
    // Slide out ke kanan
    alert.classList.add('translate-x-full');
    
    // Sembunyikan setelah animasi selesai
    setTimeout(() => {
        alert.classList.add('hidden');
    }, 300);
}

// Event listener untuk tombol tutup
document.getElementById('btn-tutup-alert-selesai').addEventListener('click', hideAlertUjianSelesai);

// Backward compatibility - alias untuk function lama
function showModalUjianSelesai() {
    showAlertUjianSelesai();
}

function hideModalUjianSelesai() {
    hideAlertUjianSelesai();
}
</script>
