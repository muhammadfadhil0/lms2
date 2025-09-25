<?php
session_start();

// Check if user is admin
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

$action = $_POST['action'] ?? '';

switch ($action) {
    case 'toggle_maintenance':
        toggleMaintenanceMode();
        break;
    
    case 'get_maintenance_status':
        getMaintenanceStatus();
        break;
        
    case 'update_maintenance_message':
        updateMaintenanceMessage();
        break;
        
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        break;
}

function toggleMaintenanceMode() {
    try {
        $configFile = __DIR__ . '/../config/maintenance.php';
        
        // Debug: Check if config file exists
        if (!file_exists($configFile)) {
            echo json_encode(['success' => false, 'message' => 'Config file not found at: ' . $configFile]);
            return;
        }
        
        $config = include $configFile;
        
        // Toggle maintenance mode
        $newStatus = !$config['maintenance_mode'];
        
        // Update config
        $config['maintenance_mode'] = $newStatus;
        $config['last_updated'] = date('Y-m-d H:i:s');
        $config['updated_by'] = $_SESSION['user']['nama'] ?? 'Admin';
        
        // Write back to file
        $configContent = "<?php\n/**\n * Maintenance Configuration File\n * File ini menyimpan status maintenance mode sistem\n */\n\nreturn " . var_export($config, true) . ";\n?>";
        
        // Debug: Check if file is writable
        if (!is_writable($configFile)) {
            echo json_encode(['success' => false, 'message' => 'Config file tidak dapat ditulis: ' . $configFile]);
            return;
        }
        
        $writeResult = file_put_contents($configFile, $configContent);
        
        if ($writeResult !== false) {
            // Clear PHP opcache if enabled
            if (function_exists('opcache_invalidate')) {
                opcache_invalidate($configFile, true);
            }
            
            echo json_encode([
                'success' => true, 
                'message' => 'Maintenance mode ' . ($newStatus ? 'diaktifkan' : 'dinonaktifkan'),
                'maintenance_mode' => $newStatus
            ]);
        } else {
            echo json_encode([
                'success' => false, 
                'message' => 'Gagal menyimpan konfigurasi ke file: ' . $configFile,
                'debug' => [
                    'config_file' => $configFile,
                    'is_writable' => is_writable($configFile),
                    'file_exists' => file_exists($configFile)
                ]
            ]);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
}

function getMaintenanceStatus() {
    try {
        $configFile = __DIR__ . '/../config/maintenance.php';
        
        // Debug: Check if config file exists
        if (!file_exists($configFile)) {
            echo json_encode(['success' => false, 'message' => 'Config file not found at: ' . $configFile]);
            return;
        }
        
        $config = include $configFile;
        echo json_encode([
            'success' => true,
            'data' => $config
        ]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
}

function updateMaintenanceMessage() {
    try {
        $newMessage = $_POST['message'] ?? '';
        $newHeader = $_POST['header'] ?? '';
        
        if (empty($newMessage)) {
            echo json_encode(['success' => false, 'message' => 'Pesan tidak boleh kosong']);
            return;
        }
        
        if (empty($newHeader)) {
            echo json_encode(['success' => false, 'message' => 'Header tidak boleh kosong']);
            return;
        }
        
        $configFile = __DIR__ . '/../config/maintenance.php';
        
        // Debug: Check if config file exists
        if (!file_exists($configFile)) {
            echo json_encode(['success' => false, 'message' => 'Config file not found at: ' . $configFile]);
            return;
        }
        
        $config = include $configFile;
        
        // Update message and header
        $config['maintenance_message'] = $newMessage;
        $config['maintenance_header'] = $newHeader;
        $config['last_updated'] = date('Y-m-d H:i:s');
        $config['updated_by'] = $_SESSION['user']['nama'] ?? 'Admin';
        
        // Write back to file
        $configContent = "<?php\n/**\n * Maintenance Configuration File\n * File ini menyimpan status maintenance mode sistem\n */\n\nreturn " . var_export($config, true) . ";\n?>";
        
        if (file_put_contents($configFile, $configContent)) {
            echo json_encode([
                'success' => true, 
                'message' => 'Header dan pesan maintenance berhasil diupdate',
                'new_message' => $newMessage,
                'new_header' => $newHeader
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Gagal menyimpan konfigurasi']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
}
?>