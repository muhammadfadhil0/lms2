/**
 * Contoh implementasi join class dengan modal alert system
 */

// Fungsi untuk join kelas
async function submitJoinKelas() {
    const kodeKelas = document.getElementById('kodeKelas').value.trim().toUpperCase();
    
    // Validasi input
    if (!kodeKelas) {
        showModalAlert('error', 'Kode Kelas Diperlukan!', 'Mohon masukkan kode kelas yang valid.', 0);
        return;
    }
    
    // Validasi format kode
    if (kodeKelas.length < 5) {
        showModalAlert('error', 'Format Kode Salah!', 'Kode kelas harus minimal 5 karakter.', 0);
        return;
    }
    
    try {
        // Show loading
        showModalAlert('info', 'Memproses...', 'Sedang mencoba bergabung ke kelas...');
        
        const formData = new FormData();
        formData.append('action', 'join_kelas');
        formData.append('kodeKelas', kodeKelas);
        
        const response = await fetch('../logic/join-kelas.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            showModalAlert('success', 'Berhasil Bergabung!', 
                `Anda berhasil bergabung ke kelas ${result.namaKelas || 'tersebut'}.`);
            
            // Redirect setelah 2 detik
            setTimeout(() => {
                window.location.href = result.redirect || '../front/beranda-user.php';
            }, 2000);
            
        } else {
            // Handle different error scenarios
            handleJoinError(result.error, result.message);
        }
        
    } catch (error) {
        console.error('Error:', error);
        showModalAlert('error', 'Kesalahan Jaringan!', 
            'Tidak dapat terhubung ke server. Periksa koneksi internet Anda.', 0);
    }
}

// Fungsi untuk menangani berbagai jenis error join class
function handleJoinError(errorType, message) {
    switch (errorType) {
        case 'CLASS_LOCKED':
            showModalAlert('warning', 'Kelas Dikunci!', 
                'Kelas ini telah dikunci dan tidak menerima mahasiswa baru.', 0);
            break;
            
        case 'CLASS_NOT_FOUND':
            showModalAlert('error', 'Kelas Tidak Ditemukan!', 
                'Kode kelas yang Anda masukkan tidak valid atau tidak ditemukan.', 0);
            break;
            
        case 'ALREADY_JOINED':
            showModalAlert('info', 'Sudah Bergabung!', 
                'Anda sudah menjadi anggota kelas ini.');
            break;
            
        case 'CLASS_FULL':
            showModalAlert('warning', 'Kelas Penuh!', 
                'Kelas ini sudah mencapai batas maksimum anggota.', 0);
            break;
            
        case 'PERMISSION_DENIED':
            showModalAlert('error', 'Akses Ditolak!', 
                'Anda tidak memiliki izin untuk bergabung ke kelas ini.', 0);
            break;
            
        default:
            showModalAlert('error', 'Gagal Bergabung!', 
                message || 'Terjadi kesalahan saat mencoba bergabung ke kelas.', 0);
    }
}

// Fungsi untuk menutup modal join class
function closeJoinModal() {
    const modal = document.getElementById('join-class-modal');
    if (modal) {
        modal.close();
        // Reset form dan hide alerts
        document.getElementById('join-class-form').reset();
        hideAllModalAlerts();
    }
}

// Event listener untuk modal
document.addEventListener('DOMContentLoaded', function() {
    const joinModal = document.getElementById('join-class-modal');
    if (joinModal) {
        // Auto focus ke input kode kelas saat modal dibuka
        joinModal.addEventListener('show', function() {
            setTimeout(() => {
                const kodeInput = document.getElementById('kodeKelas');
                if (kodeInput) {
                    kodeInput.focus();
                }
            }, 100);
        });
        
        // Clear alerts saat modal ditutup
        joinModal.addEventListener('close', function() {
            hideAllModalAlerts();
            document.getElementById('join-class-form').reset();
        });
    }
    
    // Auto uppercase untuk input kode kelas
    const kodeInput = document.getElementById('kodeKelas');
    if (kodeInput) {
        kodeInput.addEventListener('input', function(e) {
            e.target.value = e.target.value.toUpperCase();
        });
    }
});

// Fungsi global untuk membuka modal join
function openJoinClassModal() {
    const modal = document.getElementById('join-class-modal');
    if (modal) {
        hideAllModalAlerts(); // Clear any existing alerts
        modal.showModal();
    }
}
