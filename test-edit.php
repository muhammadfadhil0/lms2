<!DOCTYPE html>
<html>
<head>
    <title>Test Edit Post</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="p-8">
    <h1 class="text-2xl font-bold mb-4">Test Edit Post Modal</h1>
    
    <!-- Include Modal -->
    <?php include 'src/component/modal-edit-post.php'; ?>
    
    <!-- Test Button -->
    <button 
        onclick="openEditPostModal(1)" 
        class="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600"
    >
        Test Edit Post ID 1
    </button>
    
    <!-- Test Scripts -->
    <script src="src/script/edit-post-modal.js"></script>
    
    <script>
        // Override the default openEditPostModal for testing
        function testModal() {
            console.log('Testing modal...');
            if (window.editPostModal) {
                console.log('Modal instance found');
                // Test with fake data
                window.editPostModal.currentPostId = 1;
                window.editPostModal.modal.classList.remove('hidden');
                document.body.style.overflow = 'hidden';
                
                // Set test data
                document.getElementById('editPostId').value = 1;
                document.getElementById('editPostContent').value = 'Test post content';
            } else {
                console.error('Modal instance not found');
            }
        }
        
        // Add test button
        setTimeout(() => {
            const btn = document.createElement('button');
            btn.textContent = 'Test Modal Direct';
            btn.className = 'ml-4 px-4 py-2 bg-green-500 text-white rounded hover:bg-green-600';
            btn.onclick = testModal;
            document.body.appendChild(btn);
        }, 1000);
    </script>
</body>
</html>
