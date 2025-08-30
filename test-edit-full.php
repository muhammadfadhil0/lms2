<?php
session_start();

// Test login sebagai guru jika belum login
if (!isset($_SESSION['user'])) {
    $_SESSION['user'] = [
        'id' => 1,
        'username' => 'testguru',
        'namaLengkap' => 'Test Guru',
        'role' => 'guru'
    ];
}

$kelas_id = 1; // Test dengan kelas ID 1
?>
<!DOCTYPE html>
<html>
<head>
    <title>Test Edit Post Full</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons@latest/icons-sprite.svg">
    <style>
        .modal-overlay {
            backdrop-filter: blur(4px);
        }
    </style>
</head>
<body class="bg-gray-100 p-8">
    <h1 class="text-2xl font-bold mb-4">Test Edit Post - Full Integration</h1>
    
    <!-- Sample Post Card -->
    <div class="bg-white rounded-lg shadow-sm mb-6">
        <div class="p-4 lg:p-6">
            <div class="flex items-start space-x-3 lg:space-x-4 mb-4">
                <div class="w-10 h-10 lg:w-12 lg:h-12 rounded-full bg-orange-500 flex items-center justify-center">
                    <i class="ti ti-user text-white"></i>
                </div>
                <div class="flex-1">
                    <h3 class="font-semibold text-gray-900 text-sm lg:text-base">Test Guru</h3>
                    <p class="text-xs lg:text-sm text-gray-600">
                        Guru • 5 menit yang lalu
                    </p>
                </div>
                <div class="dropdown relative">
                    <button class="text-gray-400 hover:text-gray-600" onclick="toggleDropdown(this)">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M10 6a2 2 0 110-4 2 2 0 010 4zM10 12a2 2 0 110-4 2 2 0 010 4zM10 18a2 2 0 110-4 2 2 0 010 4z"></path>
                        </svg>
                    </button>
                    <div class="dropdown-menu absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg z-10 hidden">
                        <button onclick="openEditPostModal(1)" class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                            <span>✏️ Edit</span>
                        </button>
                        <button onclick="deletePost(1)" class="block w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-gray-100">
                            <span>🗑️ Hapus</span>
                        </button>
                    </div>
                </div>
            </div>
            <div class="mb-4">
                <p class="text-gray-800 text-sm lg:text-base whitespace-pre-wrap">Ini adalah contoh postingan yang bisa diedit. Mari kita test fungsi edit postingan.</p>
            </div>
        </div>
    </div>
    
    <!-- Include Modal -->
    <?php include 'src/component/modal-edit-post.php'; ?>
    
    <!-- Scripts -->
    <script src="src/script/edit-post-modal.js"></script>
    
    <script>
        // Set global variables
        window.currentUserId = <?php echo $_SESSION['user']['id']; ?>;
        window.currentUserRole = '<?php echo $_SESSION['user']['role']; ?>';
        
        // Dropdown toggle function
        function toggleDropdown(button) {
            const dropdown = button.nextElementSibling;
            dropdown.classList.toggle('hidden');
            
            // Close dropdown when clicking outside
            document.addEventListener('click', function closeDropdown(e) {
                if (!button.contains(e.target) && !dropdown.contains(e.target)) {
                    dropdown.classList.add('hidden');
                    document.removeEventListener('click', closeDropdown);
                }
            });
        }
        
        // Test console logs
        setTimeout(() => {
            console.log('Testing edit modal availability...');
            console.log('window.editPostModal:', window.editPostModal);
            console.log('Modal element:', document.getElementById('modalEditPost'));
        }, 1000);
    </script>
</body>
</html>
