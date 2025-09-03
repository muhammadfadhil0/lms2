<!-- Modal Ujian Sudah Selesai -->
<div id="modal-ujian-selesai" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="mt-3 text-center">
            <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100">
                <i class="ti ti-circle-x text-red-600 text-2xl"></i>
            </div>
            <h3 class="text-lg font-medium text-gray-900 mt-3">Ujian Sudah Selesai</h3>
            <div class="mt-2 px-7 py-3">
                <p class="text-sm text-gray-500">
                    Anda sudah menyelesaikan ujian ini sebelumnya. Silakan lihat hasil ujian atau pilih ujian lain yang tersedia.
                </p>
            </div>
            <div class="items-center px-4 py-3">
                <button id="btn-tutup-modal-selesai" class="px-4 py-2 bg-red-500 text-white text-base font-medium rounded-md w-full shadow-sm hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-300">
                    Tutup
                </button>
            </div>
        </div>
    </div>
</div>

<script>
// Function untuk menampilkan modal ujian sudah selesai
function showModalUjianSelesai() {
    document.getElementById('modal-ujian-selesai').classList.remove('hidden');
}

// Function untuk menutup modal ujian sudah selesai
function hideModalUjianSelesai() {
    document.getElementById('modal-ujian-selesai').classList.add('hidden');
}

// Event listener untuk tombol tutup
document.getElementById('btn-tutup-modal-selesai').addEventListener('click', hideModalUjianSelesai);

// Event listener untuk menutup modal ketika area luar diklik
document.getElementById('modal-ujian-selesai').addEventListener('click', function(e) {
    if (e.target === this) {
        hideModalUjianSelesai();
    }
});
</script>
