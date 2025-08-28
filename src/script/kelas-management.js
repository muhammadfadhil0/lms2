// Function untuk membuat kelas baru
async function createKelas(event) {
    event.preventDefault();
    
    const form = event.target;
    const formData = new FormData(form);
    // Cari submit button menggunakan selector yang lebih spesifik
    const submitBtn = document.querySelector('button[form="add-class-form"]');
    const originalText = submitBtn ? submitBtn.innerHTML : 'Tambah Kelas';
    
    // Disable button dan show loading
    if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="ti ti-loader animate-spin mr-2"></i>Membuat...';
    }
    
    try {
        const response = await fetch('../logic/create-kelas.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            // Show success message
            showNotification('Kelas berhasil dibuat dengan kode: ' + result.kode_kelas + '. Halaman akan di-refresh untuk menampilkan kelas baru.', 'success');
            
            // Close modal
            closeModal('add-class-modal');
            
            // Reset form
            form.reset();
            
            // Reload page with new class highlight
            setTimeout(() => {
                window.location.href = 'beranda-guru.php?new_class=' + result.kelas_id;
            }, 2000);
        } else {
            showNotification(result.message || 'Gagal membuat kelas', 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showNotification('Terjadi kesalahan saat membuat kelas', 'error');
    } finally {
        // Re-enable button
        if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
        }
    }
}

// Function untuk menampilkan notifikasi
function showNotification(message, type = 'info') {
    // Create notification element
    const notification = document.createElement('div');
    notification.className = `fixed top-4 right-4 z-50 p-4 rounded-lg shadow-lg max-w-sm ${
        type === 'success' ? 'bg-green-500 text-white' :
        type === 'error' ? 'bg-red-500 text-white' :
        'bg-blue-500 text-white'
    }`;
    
    notification.innerHTML = `
        <div class="flex items-center">
            <i class="ti ti-${type === 'success' ? 'check' : type === 'error' ? 'x' : 'info-circle'} mr-2"></i>
            <span>${message}</span>
            <button onclick="this.parentElement.parentElement.remove()" class="ml-2 text-white hover:text-gray-200">
                <i class="ti ti-x"></i>
            </button>
        </div>
    `;
    
    document.body.appendChild(notification);
    
    // Auto remove after 5 seconds
    setTimeout(() => {
        if (notification.parentElement) {
            notification.remove();
        }
    }, 5000);
}

// Function untuk close modal
function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.close();
    }
}

// Function untuk toggle dropdown
function toggleDropdown(dropdownId) {
    const dropdown = document.getElementById(dropdownId);
    if (dropdown) {
        dropdown.classList.toggle('hidden');
        
        // Close dropdown when clicking outside
        document.addEventListener('click', function closeDropdown(e) {
            if (!e.target.closest(`#${dropdownId}`) && !e.target.closest(`[onclick*="${dropdownId}"]`)) {
                dropdown.classList.add('hidden');
                document.removeEventListener('click', closeDropdown);
            }
        });
    }
}

// Function untuk edit kelas
function editKelas(kelasId) {
    // TODO: Implement edit functionality
    showNotification('Fitur edit akan segera tersedia', 'info');
}

// Function untuk hapus kelas
function hapusKelas(kelasId) {
    if (confirm('Apakah Anda yakin ingin menghapus kelas ini?')) {
        // TODO: Implement delete functionality
        showNotification('Fitur hapus akan segera tersedia', 'info');
    }
}

// Function untuk submit join kelas
async function submitJoinKelas() {
    const form = document.getElementById('join-class-form');
    const submitBtn = document.getElementById('join-class-submit-btn');
    
    if (!form) {
        showNotification('Form tidak ditemukan', 'error');
        return;
    }
    
    // Validate form
    const kodeKelasInput = form.querySelector('input[name="kodeKelas"]');
    if (!kodeKelasInput || !kodeKelasInput.value.trim()) {
        showNotification('Kode kelas harus diisi', 'error');
        kodeKelasInput?.focus();
        return;
    }
    
    const formData = new FormData(form);
    const originalText = submitBtn ? submitBtn.innerHTML : 'Gabung Kelas';
    
    // Disable button dan show loading
    if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="ti ti-loader animate-spin mr-2"></i>Bergabung...';
    }
    
    try {
        const response = await fetch('../logic/join-kelas.php', {
            method: 'POST',
            body: formData
        });
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const result = await response.json();
        
        if (result.success) {
            showNotification(result.message, 'success');
            form.reset();
            
            // Close modal
            closeModal('join-class-modal');
            
            // Redirect to class page if kelas_id is provided
            if (result.kelas_id) {
                setTimeout(() => {
                    window.location.href = 'kelas-user.php?id=' + result.kelas_id;
                }, 1000);
            } else {
                setTimeout(() => {
                    window.location.reload();
                }, 1500);
            }
        } else {
            showNotification(result.message || 'Gagal bergabung dengan kelas', 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showNotification('Terjadi kesalahan saat bergabung kelas', 'error');
    } finally {
        // Re-enable button
        if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
        }
    }
}

// Function untuk join kelas (untuk siswa) - Kept for backward compatibility
async function joinKelas(event) {
    event.preventDefault();
    await submitJoinKelas();
}

// Function untuk handle submit button click
function handleJoinKelasSubmit() {
    const form = document.getElementById('join-class-form');
    if (form) {
        const event = new Event('submit', { bubbles: true, cancelable: true });
        form.dispatchEvent(event);
    }
}

// Setup event listeners when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    // Setup form submit handler
    const joinForm = document.getElementById('join-class-form');
    if (joinForm) {
        joinForm.addEventListener('submit', joinKelas);
    }
    
    // Setup button click handler
    const submitBtn = document.querySelector('button[form="join-class-form"]');
    if (submitBtn) {
        submitBtn.addEventListener('click', function(e) {
            e.preventDefault();
            handleJoinKelasSubmit();
        });
    }
});
