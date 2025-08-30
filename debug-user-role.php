<?php
session_start();
if (!isset($_SESSION['user'])) {
    echo "No user session found";
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Debug User Role</title>
</head>
<body>
    <h1>Debug User Role</h1>
    <p>PHP Session Role: <?php echo $_SESSION['user']['role']; ?></p>
    <p>User ID: <?php echo $_SESSION['user']['id']; ?></p>
    <p>Name: <?php echo $_SESSION['user']['namaLengkap']; ?></p>
    
    <script>
        // Set the role in JavaScript (same as kelas-user.php now does)
        window.currentUserRole = '<?php echo $_SESSION['user']['role']; ?>';
        
        console.log('JavaScript currentUserRole:', window.currentUserRole);
        console.log('Role check for siswa:', window.currentUserRole === 'siswa');
        
        // Also display in page
        document.addEventListener('DOMContentLoaded', function() {
            const info = document.createElement('div');
            info.innerHTML = `
                <h2>JavaScript Variables</h2>
                <p>window.currentUserRole: ${window.currentUserRole}</p>
                <p>Is siswa: ${window.currentUserRole === 'siswa'}</p>
            `;
            document.body.appendChild(info);
        });
    </script>
</body>
</html>
