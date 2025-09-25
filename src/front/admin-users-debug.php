<?php
session_start();
$currentPage = 'users';

// Debug mode - show everything
$debug = true;

if ($debug) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    
    echo "<div style='background: #fffbeb; border: 1px solid #f59e0b; padding: 10px; margin: 10px; border-radius: 5px;'>";
    echo "<strong>🐛 DEBUG MODE</strong><br>";
    echo "Session: " . (isset($_SESSION['user']) ? "✅ Set" : "❌ Not set") . "<br>";
    if (isset($_SESSION['user'])) {
        echo "User Role: " . $_SESSION['user']['role'] . "<br>";
    }
    echo "</div>";
}

// Temporary bypass for testing
if (!isset($_SESSION['user'])) {
    // Auto-login as admin for testing
    require_once '../logic/koneksi.php';
    $result = mysqli_query($koneksi, "SELECT * FROM users WHERE role = 'admin' LIMIT 1");
    if (mysqli_num_rows($result) > 0) {
        $admin = mysqli_fetch_assoc($result);
        $_SESSION['user'] = [
            'id' => $admin['id'],
            'namaLengkap' => $admin['namaLengkap'],
            'email' => $admin['email'],
            'role' => $admin['role'],
            'username' => $admin['username']
        ];
        if ($debug) {
            echo "<div style='background: #dcfce7; border: 1px solid #16a34a; padding: 10px; margin: 10px; border-radius: 5px;'>";
            echo "✅ Auto-logged in as: " . $admin['namaLengkap'] . " (" . $admin['role'] . ")";
            echo "</div>";
        }
    }
}

// Original check
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    if ($debug) {
        echo "<div style='background: #fef2f2; border: 1px solid #dc2626; padding: 10px; margin: 10px; border-radius: 5px;'>";
        echo "❌ Access denied: " . (!isset($_SESSION['user']) ? "No session" : "Role is " . $_SESSION['user']['role']);
        echo "</div>";
    }
    header('Location: ../../login.php');
    exit();
}

require_once '../logic/user-logic.php';

$userLogic = new UserLogic();

// Get parameters
$search = $_GET['search'] ?? '';
$role_filter = $_GET['role'] ?? '';
$status_filter = $_GET['status'] ?? '';
$sort_by = $_GET['sort'] ?? 'id';
$sort_order = $_GET['order'] ?? 'DESC';
$page = (int)($_GET['page'] ?? 1);
$per_page = 20;

if ($debug) {
    echo "<div style='background: #f0f9ff; border: 1px solid #0ea5e9; padding: 10px; margin: 10px; border-radius: 5px;'>";
    echo "<strong>📊 Query Parameters:</strong><br>";
    echo "Search: '$search'<br>";
    echo "Role Filter: '$role_filter'<br>";
    echo "Status Filter: '$status_filter'<br>";
    echo "Sort: $sort_by $sort_order<br>";
    echo "Page: $page<br>";
    echo "</div>";
}

try {
    // Get users with filters
    $users = $userLogic->getUsers($search, $role_filter, $status_filter, $sort_by, $sort_order, $page, $per_page);
    $total_users = $userLogic->countUsers($search, $role_filter, $status_filter);
    $total_pages = ceil($total_users / $per_page);

    // Get statistics
    $stats = $userLogic->getUserStats();
    
    if ($debug) {
        echo "<div style='background: #f0fdf4; border: 1px solid #22c55e; padding: 10px; margin: 10px; border-radius: 5px;'>";
        echo "<strong>📈 Results:</strong><br>";
        echo "Users found: " . count($users) . "<br>";
        echo "Total users: $total_users<br>";
        echo "Total pages: $total_pages<br>";
        echo "Stats: ";
        print_r($stats);
        echo "<br>";
        if (!empty($users)) {
            echo "<br><strong>First user sample:</strong><br>";
            print_r($users[0]);
        }
        echo "</div>";
    }
    
} catch (Exception $e) {
    if ($debug) {
        echo "<div style='background: #fef2f2; border: 1px solid #dc2626; padding: 10px; margin: 10px; border-radius: 5px;'>";
        echo "❌ <strong>Error:</strong> " . $e->getMessage() . "<br>";
        echo "<strong>Stack trace:</strong><br><pre>" . $e->getTraceAsString() . "</pre>";
        echo "</div>";
    }
    
    // Set defaults to prevent errors
    $users = [];
    $total_users = 0;
    $total_pages = 0;
    $stats = ['total' => 0, 'guru' => 0, 'siswa' => 0, 'admin' => 0];
}
?>

<?php require '../component/sidebar.php'; ?>
<?php require '../component/menu-bar-mobile.php'; ?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php require '../../assets/head.php'; ?>
    <title>Kelola User - Admin Panel (Debug)</title>
    
    <!-- Include same styles as original -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* Add basic styles for testing */
        body { font-family: Arial, sans-serif; background: #f9fafb; }
        .debug-info { background: #fffbeb; border: 1px solid #f59e0b; padding: 15px; margin: 15px 0; border-radius: 8px; }
        .container { max-width: 1200px; margin: 0 auto; padding: 20px; }
        .card { background: white; border-radius: 8px; padding: 20px; margin: 20px 0; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
        .table { width: 100%; border-collapse: collapse; }
        .table th, .table td { padding: 12px; border-bottom: 1px solid #e5e7eb; text-align: left; }
        .table th { background: #f8fafc; font-weight: 600; }
        .btn { padding: 8px 16px; border-radius: 6px; text-decoration: none; display: inline-block; margin: 2px; }
        .btn-primary { background: #3b82f6; color: white; }
        .btn-success { background: #10b981; color: white; }
        .btn-danger { background: #ef4444; color: white; }
        .badge { padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: 500; }
        .badge-admin { background: #dbeafe; color: #1e40af; }
        .badge-guru { background: #dcfce7; color: #166534; }
        .badge-siswa { background: #fef3c7; color: #92400e; }
        .badge-active { background: #dcfce7; color: #166534; }
        .badge-inactive { background: #fee2e2; color: #991b1b; }
    </style>
</head>

<body>
    <div class="container">
        <h1>🛠️ Admin Users (Debug Mode)</h1>
        
        <?php if ($debug): ?>
        <div class="debug-info">
            <h3>🐛 Debug Information</h3>
            <p><strong>Users Array Count:</strong> <?= count($users) ?></p>
            <p><strong>Total Users from DB:</strong> <?= $total_users ?></p>
            <p><strong>Stats:</strong> <?= json_encode($stats) ?></p>
            <?php if (empty($users) && $total_users > 0): ?>
                <p style="color: red;"><strong>⚠️ Warning:</strong> Database has users but array is empty - check UserLogic::getUsers() method</p>
            <?php endif; ?>
        </div>
        <?php endif; ?>
        
        <div class="card">
            <h2>Statistics</h2>
            <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px;">
                <div style="text-align: center; padding: 20px; background: #f8fafc; border-radius: 8px;">
                    <div style="font-size: 24px; font-weight: bold; color: #3b82f6;"><?= $stats['total'] ?></div>
                    <div style="font-size: 12px; color: #6b7280;">Total User</div>
                </div>
                <div style="text-align: center; padding: 20px; background: #f8fafc; border-radius: 8px;">
                    <div style="font-size: 24px; font-weight: bold; color: #10b981;"><?= $stats['guru'] ?></div>
                    <div style="font-size: 12px; color: #6b7280;">Guru</div>
                </div>
                <div style="text-align: center; padding: 20px; background: #f8fafc; border-radius: 8px;">
                    <div style="font-size: 24px; font-weight: bold; color: #f59e0b;"><?= $stats['siswa'] ?></div>
                    <div style="font-size: 12px; color: #6b7280;">Siswa</div>
                </div>
                <div style="text-align: center; padding: 20px; background: #f8fafc; border-radius: 8px;">
                    <div style="font-size: 24px; font-weight: bold; color: #ef4444;"><?= $stats['admin'] ?></div>
                    <div style="font-size: 12px; color: #6b7280;">Admin</div>
                </div>
            </div>
        </div>

        <div class="card">
            <h2>Users Table (<?= number_format($total_users) ?> total)</h2>
            
            <?php if (empty($users)): ?>
                <div style="text-align: center; padding: 40px; color: #6b7280;">
                    <i class="fas fa-users" style="font-size: 48px; margin-bottom: 16px;"></i>
                    <h3>No Users Found</h3>
                    <p>The users table appears to be empty or there's an issue with the query.</p>
                    <a href="populate-users.php" class="btn btn-primary">Add Test Users</a>
                </div>
            <?php else: ?>
                <table class="table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Username</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Registered</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td>#<?= $user['id'] ?></td>
                                <td><?= htmlspecialchars($user['nama'] ?? $user['namaLengkap'] ?? 'N/A') ?></td>
                                <td><?= htmlspecialchars($user['email'] ?? 'N/A') ?></td>
                                <td><?= htmlspecialchars($user['username'] ?? 'N/A') ?></td>
                                <td>
                                    <span class="badge badge-<?= $user['role'] ?>">
                                        <?= ucfirst($user['role'] ?? 'N/A') ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge badge-<?= $user['status'] ?? 'active' ?>">
                                        <?= ucfirst($user['status'] ?? 'active') ?>
                                    </span>
                                </td>
                                <td><?= date('d/m/Y H:i', strtotime($user['tanggal_registrasi'])) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        
        <div class="card">
            <h3>🔧 Debug Tools</h3>
            <a href="populate-users.php" class="btn btn-primary">Populate Test Users</a>
            <a href="test-users.php" class="btn btn-success">Run Database Test</a>
            <a href="admin-users.php" class="btn btn-primary">Original Admin Users Page</a>
        </div>
    </div>
</body>
</html>