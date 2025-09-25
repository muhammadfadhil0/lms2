<?php
session_start();
header('Content-Type: application/json');

// Check if user is admin
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

require_once '../logic/advertisement-logic.php';

$advertisementLogic = new AdvertisementLogic();
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

try {
    switch ($method) {
        case 'GET':
            switch ($action) {
                case 'list':
                    $advertisements = $advertisementLogic->getAllAdvertisements();
                    echo json_encode(['success' => true, 'data' => $advertisements]);
                    break;
                    
                case 'active':
                    $advertisements = $advertisementLogic->getActiveAdvertisements();
                    echo json_encode(['success' => true, 'data' => $advertisements]);
                    break;
                    
                case 'get':
                    $id = $_GET['id'] ?? null;
                    if (!$id) {
                        echo json_encode(['success' => false, 'message' => 'ID tidak ditemukan']);
                        break;
                    }
                    $advertisement = $advertisementLogic->getAdvertisementById($id);
                    if ($advertisement) {
                        echo json_encode(['success' => true, 'data' => $advertisement]);
                    } else {
                        echo json_encode(['success' => false, 'message' => 'Advertisement tidak ditemukan']);
                    }
                    break;
                    
                default:
                    echo json_encode(['success' => false, 'message' => 'Action tidak valid']);
            }
            break;
            
        case 'POST':
            $input = json_decode(file_get_contents('php://input'), true);
            
            switch ($action) {
                case 'create':
                    // Handle file upload if image is provided
                    $imagePath = null;
                    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                        $uploadDir = '../../assets/img/ads/';
                        if (!is_dir($uploadDir)) {
                            mkdir($uploadDir, 0755, true);
                        }
                        
                        $fileExtension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
                        $fileName = 'ad_' . time() . '_' . uniqid() . '.' . $fileExtension;
                        $uploadPath = $uploadDir . $fileName;
                        
                        if (move_uploaded_file($_FILES['image']['tmp_name'], $uploadPath)) {
                            $imagePath = '../../assets/img/ads/' . $fileName;
                        }
                    }
                    
                    $data = [
                        'title' => $input['title'] ?? $_POST['title'],
                        'description' => $input['description'] ?? $_POST['description'],
                        'link_url' => $input['link_url'] ?? $_POST['link_url'] ?? '#',
                        'image_path' => $imagePath,
                        'is_active' => $input['is_active'] ?? $_POST['is_active'] ?? 1,
                        'created_by' => $_SESSION['user']['id']
                    ];
                    
                    $result = $advertisementLogic->createAdvertisement($data);
                    echo json_encode($result);
                    break;
                    
                case 'update':
                    $id = $input['id'] ?? $_POST['id'];
                    if (!$id) {
                        echo json_encode(['success' => false, 'message' => 'ID tidak ditemukan']);
                        break;
                    }
                    
                    // Get existing advertisement for image handling
                    $existing = $advertisementLogic->getAdvertisementById($id);
                    $imagePath = $existing['image_path'];
                    
                    // Handle file upload if new image is provided
                    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                        $uploadDir = '../../assets/img/ads/';
                        if (!is_dir($uploadDir)) {
                            mkdir($uploadDir, 0755, true);
                        }
                        
                        $fileExtension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
                        $fileName = 'ad_' . time() . '_' . uniqid() . '.' . $fileExtension;
                        $uploadPath = $uploadDir . $fileName;
                        
                        if (move_uploaded_file($_FILES['image']['tmp_name'], $uploadPath)) {
                            // Delete old image if exists
                            if ($imagePath && file_exists($imagePath)) {
                                unlink($imagePath);
                            }
                            $imagePath = '../../assets/img/ads/' . $fileName;
                        }
                    }
                    
                    $data = [
                        'title' => $input['title'] ?? $_POST['title'],
                        'description' => $input['description'] ?? $_POST['description'],
                        'link_url' => $input['link_url'] ?? $_POST['link_url'] ?? '#',
                        'image_path' => $imagePath,
                        'is_active' => $input['is_active'] ?? $_POST['is_active'] ?? 1
                    ];
                    
                    $result = $advertisementLogic->updateAdvertisement($id, $data);
                    echo json_encode($result);
                    break;
                    
                case 'toggle':
                    $id = $input['id'] ?? null;
                    if (!$id) {
                        echo json_encode(['success' => false, 'message' => 'ID tidak ditemukan']);
                        break;
                    }
                    
                    $result = $advertisementLogic->toggleStatus($id);
                    echo json_encode($result);
                    break;
                    
                default:
                    echo json_encode(['success' => false, 'message' => 'Action tidak valid']);
            }
            break;
            
        case 'DELETE':
            $input = json_decode(file_get_contents('php://input'), true);
            $id = $input['id'] ?? null;
            
            if (!$id) {
                echo json_encode(['success' => false, 'message' => 'ID tidak ditemukan']);
                break;
            }
            
            // Get advertisement for image cleanup
            $advertisement = $advertisementLogic->getAdvertisementById($id);
            
            $result = $advertisementLogic->deleteAdvertisement($id);
            
            // Delete image file if exists
            if ($result['success'] && $advertisement && $advertisement['image_path']) {
                if (file_exists($advertisement['image_path'])) {
                    unlink($advertisement['image_path']);
                }
            }
            
            echo json_encode($result);
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Method tidak valid']);
    }
} catch (Exception $e) {
    error_log("Advertisement API Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Internal server error']);
}
?>