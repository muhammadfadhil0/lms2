<!-- 
    Contoh implementasi modal-choose-assignment.php ke dalam halaman
    Tambahkan kode ini ke halaman di mana Anda ingin menampilkan modal pilihan tugas
-->

<!-- Include modal component -->
<?php require 'src/component/modal-choose-assignment.php'; ?>

<!-- Include JavaScript -->
<script src="src/script/assignment-chooser-modal.js"></script>

<!-- Contoh tombol untuk membuka modal -->
<button 
    onclick="openAssignmentChooser()" 
    class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
    <i class="ti ti-clipboard-text mr-2"></i>
    Pilih Tugas untuk AI
</button>

<!-- Custom event listener untuk handling assignment yang dipilih -->
<script>
document.addEventListener('assignmentSelected', (event) => {
    const assignment = event.detail.assignment;
    
    console.log('Selected Assignment:', assignment);
    
    // Contoh: Tampilkan data tugas yang dipilih
    alert(`Tugas "${assignment.judul}" dari kelas "${assignment.kelas.nama}" berhasil dipilih!`);
    
    // Contoh: Kirim data ke AI atau proses lainnya
    processAssignmentWithAI(assignment);
});

function processAssignmentWithAI(assignment) {
    // Implementasi logic untuk mengirim tugas ke AI
    // Contoh: redirect ke halaman AI dengan assignment ID
    // window.location.href = `ai-analysis.php?assignment_id=${assignment.id}`;
    
    // Atau kirim via AJAX untuk processing
    console.log('Processing assignment with AI:', assignment);
}
</script>

<!-- Styling tambahan jika diperlukan -->
<style>
/* Custom styling untuk modal assignment chooser */
.assignment-item:hover .ti-chevron-right {
    transform: translateX(4px);
    transition: transform 0.2s ease;
}

.assignment-item.selected {
    box-shadow: 0 0 0 2px rgb(59 130 246 / 0.5);
}

/* Custom scrollbar untuk assignment list */
#assignmentItems::-webkit-scrollbar {
    width: 6px;
}

#assignmentItems::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 3px;
}

#assignmentItems::-webkit-scrollbar-thumb {
    background: #c1c1c1;
    border-radius: 3px;
}

#assignmentItems::-webkit-scrollbar-thumb:hover {
    background: #a1a1a1;
}
</style>