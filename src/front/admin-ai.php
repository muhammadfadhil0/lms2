<?php
session_start();
require_once '../logic/koneksi.php';

// Check if user is admin
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header('Location: ../../login.php');
    exit();
}

$currentPage = 'ai';

// Get AI information entries with likes and dislikes
try {
    $stmt = $pdo->query("SELECT *, COALESCE(likes, 0) as likes, COALESCE(dislikes, 0) as dislikes FROM ai_information ORDER BY created_at DESC");
    $aiInfos = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $aiInfos = [];
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php require '../../assets/head.php'; ?>
    <title>Manajemen AI - Edupoint</title>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Tabler Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons@1.119.0/icons.css">
    
    <!-- TinyMCE Rich Text Editor -->
    <script src="https://cdn.tiny.cloud/1/x6xgex4746nelzs3t7p1c8zudvzha8ji8y2p03j3ifm4qb03/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/admin-ai.css">
    
    <style>
        .bg-orange {
            background-color: #f97316;
        }
        .text-orange {
            color: #f97316;
        }
        .border-orange {
            border-color: #f97316;
        }
        .line-clamp-2 {
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        .rich-content img {
            max-width: 100%;
            height: auto;
            border-radius: 0.5rem;
            margin: 0.5rem 0;
        }
        .rich-content h1, .rich-content h2, .rich-content h3 {
            color: #1f2937;
            font-weight: 600;
            margin: 1rem 0 0.5rem 0;
        }
        .rich-content h1 { font-size: 1.5rem; }
        .rich-content h2 { font-size: 1.25rem; }
        .rich-content h3 { font-size: 1.125rem; }
        .rich-content p {
            margin: 0.5rem 0;
            line-height: 1.6;
        }
        .rich-content ul, .rich-content ol {
            margin: 0.5rem 0;
            padding-left: 1.5rem;
        }
        .rich-content blockquote {
            border-left: 4px solid #f97316;
            background: #fef3c7;
            padding: 1rem 1.5rem;
            margin: 1rem 0;
            border-radius: 0.5rem;
        }
        .rich-content code {
            background: #f3f4f6;
            padding: 0.125rem 0.25rem;
            border-radius: 0.25rem;
            font-family: 'Monaco', 'Courier New', monospace;
        }
        .rich-content pre {
            background: #f3f4f6;
            padding: 1rem;
            border-radius: 0.5rem;
            overflow-x: auto;
            margin: 1rem 0;
        }
    </style>
</head>
<body class="bg-gray-50">
    <?php include '../component/sidebar.php'; ?>
    
    <div class="ml-0 md:ml-64 transition-all duration-300">
        <!-- Header -->
        <div class="bg-white shadow-sm border-b border-gray-200 p-6">
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="text-2xl font-bold text-gray-800">Manajemen Artikel Bantuan</h1>
                    <p class="text-gray-600 mt-1">Kelola artikel bantuan yang akan ditampilkan kepada pengguna</p>
                </div>
                <button onclick="openAddModal()" class="bg-orange hover:bg-orange-600 text-white px-4 py-2 rounded-lg flex items-center space-x-2 transition-colors">
                    <i class="ti ti-plus"></i>
                    <span>Tambah Artikel</span>
                </button>
            </div>
        </div>

        <!-- Content -->
        <div class="p-6">
            <!-- Statistics Cards -->
            <div class="grid grid-cols-1 md:grid-cols-5 gap-6 mb-8">
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                    <div class="flex items-center">
                        <div class="p-3 bg-blue-100 rounded-lg">
                            <i class="ti ti-info-circle text-blue-600 text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm text-gray-500">Total Artikel</p>
                            <p class="text-2xl font-bold text-gray-800"><?php echo count($aiInfos); ?></p>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                    <div class="flex items-center">
                        <div class="p-3 bg-green-100 rounded-lg">
                            <i class="ti ti-school text-green-600 text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm text-gray-500">Artikel Guru</p>
                            <p class="text-2xl font-bold text-gray-800">
                                <?php echo count(array_filter($aiInfos, function($info) { return $info['target_role'] === 'guru'; })); ?>
                            </p>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                    <div class="flex items-center">
                        <div class="p-3 bg-purple-100 rounded-lg">
                            <i class="ti ti-users text-purple-600 text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm text-gray-500">Artikel Siswa</p>
                            <p class="text-2xl font-bold text-gray-800">
                                <?php echo count(array_filter($aiInfos, function($info) { return $info['target_role'] === 'siswa'; })); ?>
                            </p>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                    <div class="flex items-center">
                        <div class="p-3 bg-emerald-100 rounded-lg">
                            <i class="ti ti-thumb-up text-emerald-600 text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm text-gray-500">Total Like</p>
                            <p class="text-2xl font-bold text-gray-800">
                                <?php echo array_sum(array_column($aiInfos, 'likes')); ?>
                            </p>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                    <div class="flex items-center">
                        <div class="p-3 bg-red-100 rounded-lg">
                            <i class="ti ti-thumb-down text-red-600 text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm text-gray-500">Total Dislike</p>
                            <p class="text-2xl font-bold text-gray-800">
                                <?php echo array_sum(array_column($aiInfos, 'dislikes')); ?>
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- AI Information List -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-800">Artikel Bantuan</h3>
                    <p class="text-sm text-gray-600">Daftar artikel bantuan yang akan ditampilkan kepada pengguna</p>
                </div>

                <?php if (empty($aiInfos)): ?>
                    <div class="p-8 text-center">
                        <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                            <i class="ti ti-info-circle text-gray-400 text-2xl"></i>
                        </div>
                        <h3 class="text-lg font-medium text-gray-800 mb-2">Belum ada artikel bantuan</h3>
                        <p class="text-gray-600 mb-4">Mulai dengan menambahkan artikel bantuan untuk membantu pengguna</p>
                        <button onclick="openAddModal()" class="bg-orange hover:bg-orange-600 text-white px-4 py-2 rounded-lg transition-colors">
                            Tambah Artikel Pertama
                        </button>
                    </div>
                <?php else: ?>
                    <div class="divide-y divide-gray-200">
                        <?php foreach ($aiInfos as $info): ?>
                            <div class="p-6 hover:bg-gray-50 transition-colors">
                                <div class="flex justify-between items-start">
                                    <div class="flex-1">
                                        <div class="flex items-center space-x-3 mb-2">
                                            <h4 class="text-lg font-medium text-gray-800"><?php echo htmlspecialchars($info['title']); ?></h4>
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php
                                                echo $info['target_role'] === 'guru' ? 'bg-blue-100 text-blue-800' : 
                                                    ($info['target_role'] === 'siswa' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800');
                                            ?>">
                                                <i class="<?php
                                                    echo $info['target_role'] === 'guru' ? 'ti ti-school' : 
                                                        ($info['target_role'] === 'siswa' ? 'ti ti-user' : 'ti ti-users');
                                                ?> mr-1"></i>
                                                <?php echo ucfirst($info['target_role']); ?>
                                            </span>
                                        </div>
                                        <?php if (!empty($info['description'])): ?>
                                            <p class="text-gray-600 mb-2 font-medium"><?php echo htmlspecialchars($info['description']); ?></p>
                                        <?php endif; ?>
                                        <p class="text-gray-500 mb-3 text-sm line-clamp-2"><?php echo htmlspecialchars(substr(strip_tags($info['content']), 0, 150)) . (strlen(strip_tags($info['content'])) > 150 ? '...' : ''); ?></p>
                                        <div class="flex items-center text-sm text-gray-500 space-x-4">
                                            <span>
                                                <i class="ti ti-calendar mr-1"></i>
                                                <?php echo date('d M Y', strtotime($info['created_at'])); ?>
                                            </span>
                                            <span>
                                                <i class="ti ti-clock mr-1"></i>
                                                <?php echo date('H:i', strtotime($info['updated_at'])); ?>
                                            </span>
                                        </div>
                                    </div>
                                    <div class="flex items-center space-x-2 ml-4">
                                        <div class="flex items-center space-x-1 text-sm text-gray-600 mr-2 bg-gray-50 px-3 py-1 rounded-lg">
                                            <i class="ti ti-thumb-up text-green-600"></i>
                                            <span class="font-medium text-green-700"><?php echo $info['likes'] ?? 0; ?></span>
                                            <i class="ti ti-thumb-down text-red-600 ml-2"></i>
                                            <span class="font-medium text-red-700"><?php echo $info['dislikes'] ?? 0; ?></span>
                                        </div>
                                        <button onclick="viewInfo(<?php echo $info['id']; ?>)" class="p-2 text-blue-600 hover:bg-blue-100 rounded-lg transition-colors" title="Lihat">
                                            <i class="ti ti-eye"></i>
                                        </button>
                                        <button onclick="editInfo(<?php echo $info['id']; ?>)" class="p-2 text-orange hover:bg-orange-100 rounded-lg transition-colors" title="Edit">
                                            <i class="ti ti-edit"></i>
                                        </button>
                                        <button onclick="deleteInfo(<?php echo $info['id']; ?>)" class="p-2 text-red-600 hover:bg-red-100 rounded-lg transition-colors" title="Hapus">
                                            <i class="ti ti-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Add/Edit Modal -->
    <div id="aiInfoModal" class="hidden fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 transition-opacity bg-gray-500 bg-opacity-75"></div>
            
            <div class="inline-block w-full max-w-2xl p-6 my-8 overflow-hidden text-left align-middle transition-all transform bg-white shadow-xl rounded-lg">
                <div class="flex justify-between items-center mb-6">
                    <h3 id="modalTitle" class="text-lg font-semibold text-gray-800">Tambah Artikel Bantuan</h3>
                    <button onclick="closeModal()" class="text-gray-400 hover:text-gray-600">
                        <i class="ti ti-x text-xl"></i>
                    </button>
                </div>

                <form id="aiInfoForm" onsubmit="saveInfo(event)">
                    <input type="hidden" id="infoId" name="id">
                    
                    <div class="space-y-6">
                        <!-- Title -->
                        <div>
                            <label for="title" class="block text-sm font-medium text-gray-700 mb-2">Judul Artikel</label>
                            <input type="text" id="title" name="title" required
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-transparent"
                                placeholder="Masukkan judul artikel bantuan...">
                        </div>

                        <!-- Description -->
                        <div>
                            <label for="description" class="block text-sm font-medium text-gray-700 mb-2">Deskripsi Singkat</label>
                            <textarea id="description" name="description" rows="2"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-transparent"
                                placeholder="Masukkan deskripsi singkat untuk preview artikel (opsional)..."></textarea>
                            <p class="text-sm text-gray-500 mt-1">Deskripsi ini akan ditampilkan di card preview artikel.</p>
                        </div>

                        <!-- Target Role -->
                        <div>
                            <label for="targetRole" class="block text-sm font-medium text-gray-700 mb-2">Target Pengguna</label>
                            <select id="targetRole" name="target_role" required
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-transparent">
                                <option value="">Pilih target pengguna</option>
                                <option value="guru">Guru</option>
                                <option value="siswa">Siswa</option>
                                <option value="all">Semua Pengguna</option>
                            </select>
                        </div>

                        <!-- Content -->
                        <div>
                            <label for="content" class="block text-sm font-medium text-gray-700 mb-2">Isi Artikel</label>
                            <textarea id="content" name="content" rows="15"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-transparent"
                                placeholder="Masukkan isi artikel bantuan yang akan ditampilkan kepada pengguna..."></textarea>
                            <p class="text-sm text-gray-500 mt-1">Gunakan editor di atas untuk memformat artikel dengan gambar, heading, bullet points, dan styling lainnya.</p>
                        </div>
                    </div>

                    <div class="flex justify-end space-x-3 mt-8 pt-6 border-t border-gray-200">
                        <button type="button" onclick="closeModal()" 
                            class="px-4 py-2 text-gray-700 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                            Batal
                        </button>
                        <button type="submit" id="submitBtn"
                            class="px-4 py-2 bg-orange hover:bg-orange-600 text-white rounded-lg transition-colors">
                            Simpan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- View Modal -->
    <div id="viewModal" class="hidden fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 transition-opacity bg-gray-500 bg-opacity-75"></div>
            
            <div class="inline-block w-full max-w-2xl p-6 my-8 overflow-hidden text-left align-middle transition-all transform bg-white shadow-xl rounded-lg">
                <div class="flex justify-between items-center mb-6">
                    <h3 class="text-lg font-semibold text-gray-800">Detail Artikel Bantuan</h3>
                    <button onclick="closeViewModal()" class="text-gray-400 hover:text-gray-600">
                        <i class="ti ti-x text-xl"></i>
                    </button>
                </div>

                <div id="viewContent">
                    <!-- Content will be loaded here -->
                </div>
            </div>
        </div>
    </div>

    <script>
        let aiInfos = <?php echo json_encode($aiInfos); ?>;
        let tinymceEditor = null;
        
        // Initialize TinyMCE
        document.addEventListener('DOMContentLoaded', function() {
            tinymce.init({
                selector: '#content',
                height: 400,
                menubar: false,
                plugins: [
                    'advlist', 'autolink', 'lists', 'link', 'image', 'charmap', 'preview',
                    'anchor', 'searchreplace', 'visualblocks', 'code', 'fullscreen',
                    'insertdatetime', 'media', 'table', 'help', 'wordcount', 'emoticons'
                ],
                toolbar: 'undo redo | blocks | bold italic underline strikethrough | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | removeformat | link image media table | emoticons charmap | preview code fullscreen | help',
                content_style: `
                    body { 
                        font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif; 
                        font-size: 14px; 
                        line-height: 1.6; 
                        color: #374151;
                    }
                    h1 { color: #1f2937; font-size: 2rem; margin: 1rem 0; }
                    h2 { color: #374151; font-size: 1.5rem; margin: 1rem 0; }
                    h3 { color: #4b5563; font-size: 1.25rem; margin: 1rem 0; }
                    p { margin: 0.5rem 0; }
                    img { max-width: 100%; height: auto; border-radius: 8px; }
                    blockquote { 
                        border-left: 4px solid #f97316; 
                        background: #fef3c7; 
                        padding: 1rem 1.5rem; 
                        margin: 1rem 0; 
                        border-radius: 0.5rem;
                    }
                    code { 
                        background: #f3f4f6; 
                        padding: 0.125rem 0.25rem; 
                        border-radius: 0.25rem; 
                        font-family: 'Monaco', 'Courier New', monospace;
                    }
                    pre { 
                        background: #f3f4f6; 
                        padding: 1rem; 
                        border-radius: 0.5rem; 
                        overflow-x: auto;
                    }
                `,
                setup: function(editor) {
                    tinymceEditor = editor;
                    
                    // Remove problematic attributes from textarea
                    editor.on('init', function() {
                        const textarea = document.getElementById('content');
                        if (textarea) {
                            textarea.removeAttribute('aria-hidden');
                            textarea.style.visibility = 'hidden';
                            textarea.style.position = 'absolute';
                            textarea.style.left = '-9999px';
                        }
                    });
                    
                    // Sync content with textarea to prevent validation errors
                    editor.on('change', function() {
                        editor.save(); // This syncs content back to textarea
                    });
                    
                    editor.on('keyup', function() {
                        editor.save();
                    });
                    
                    editor.on('paste', function() {
                        setTimeout(function() {
                            editor.save();
                        }, 100);
                    });
                },
                images_upload_handler: function(blobInfo, success, failure) {
                    // Handle image upload
                    uploadImage(blobInfo, success, failure);
                },
                file_picker_callback: function(callback, value, meta) {
                    // Handle file picker for images and media
                    if (meta.filetype === 'image') {
                        const input = document.createElement('input');
                        input.setAttribute('type', 'file');
                        input.setAttribute('accept', 'image/*');
                        
                        input.onchange = function() {
                            const file = this.files[0];
                            const reader = new FileReader();
                            
                            reader.onload = function() {
                                const id = 'blobid' + (new Date()).getTime();
                                const blobCache = tinymce.activeEditor.editorUpload.blobCache;
                                const base64 = reader.result.split(',')[1];
                                const blobInfo = blobCache.create(id, file, base64);
                                blobCache.add(blobInfo);
                                
                                // Upload the image
                                uploadImage(blobInfo, function(url) {
                                    callback(url, { title: file.name });
                                }, function(error) {
                                    failure('Image upload failed: ' + error.message);
                                });
                            };
                            
                            reader.readAsDataURL(file);
                        };
                        
                        input.click();
                    }
                },
                branding: false,
                promotion: false
            });
            
            // Add form submit handler to ensure content sync
            const form = document.getElementById('aiInfoForm');
            if (form) {
                form.addEventListener('submit', function(e) {
                    // Sync TinyMCE content before any validation
                    if (tinymceEditor) {
                        tinymceEditor.save();
                    }
                });
            }
        });
        
        // Handle image upload
        function uploadImage(blobInfo, success, failure) {
            const formData = new FormData();
            formData.append('image', blobInfo.blob(), blobInfo.filename());
            
            fetch('api/upload-image.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    success(data.url);
                } else {
                    failure(data.error || 'Upload gagal');
                }
            })
            .catch(error => {
                failure('Upload error: ' + error.message);
            });
        }
        
        function openAddModal() {
            // Reset modal for new entry
            document.getElementById('modalTitle').textContent = 'Tambah Artikel Bantuan';
            document.getElementById('submitBtn').textContent = 'Simpan';
            document.getElementById('submitBtn').disabled = false;
            
            // Reset form
            document.getElementById('aiInfoForm').reset();
            document.getElementById('infoId').value = '';
            
            // Show modal
            document.getElementById('aiInfoModal').classList.remove('hidden');
            
            // Focus on title field
            setTimeout(() => {
                document.getElementById('title').focus();
            }, 100);
        }

        function editInfo(id) {
            const info = aiInfos.find(item => item.id == id);
            if (!info) {
                showNotification('Data tidak ditemukan!', 'error');
                return;
            }

            // Change modal title and button
            document.getElementById('modalTitle').textContent = 'Edit Artikel Bantuan';
            document.getElementById('submitBtn').textContent = 'Update';
            
            // Fill form with existing data
            document.getElementById('infoId').value = info.id;
            document.getElementById('title').value = info.title;
            document.getElementById('description').value = info.description || '';
            document.getElementById('targetRole').value = info.target_role;
            
            // Set TinyMCE content
            if (tinymceEditor) {
                tinymceEditor.setContent(info.content || '');
            } else {
                document.getElementById('content').value = info.content;
            }
            
            // Show modal
            document.getElementById('aiInfoModal').classList.remove('hidden');
            
            // Focus on title field
            setTimeout(() => {
                document.getElementById('title').focus();
            }, 100);
        }

        function viewInfo(id) {
            const info = aiInfos.find(item => item.id == id);
            if (!info) return;

            const roleColor = info.target_role === 'guru' ? 'bg-blue-100 text-blue-800' : 
                             (info.target_role === 'siswa' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800');
            const roleIcon = info.target_role === 'guru' ? 'ti-school' : 
                            (info.target_role === 'siswa' ? 'ti-user' : 'ti-users');

            document.getElementById('viewContent').innerHTML = `
                <div class="space-y-4">
                    <div>
                        <h4 class="text-xl font-semibold text-gray-800 mb-2">${info.title}</h4>
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium ${roleColor}">
                            <i class="ti ${roleIcon} mr-2"></i>
                            ${info.target_role.charAt(0).toUpperCase() + info.target_role.slice(1)}
                        </span>
                    </div>
                    
                    ${info.description ? `
                        <div class="border-t pt-4">
                            <h5 class="font-medium text-gray-700 mb-2">Deskripsi:</h5>
                            <p class="text-gray-700 bg-blue-50 p-3 rounded-lg">${info.description}</p>
                        </div>
                    ` : ''}
                    
                    <div class="border-t pt-4">
                        <h5 class="font-medium text-gray-700 mb-2">Isi Artikel:</h5>
                        <div class="bg-gray-50 p-4 rounded-lg prose max-w-none">
                            <div class="rich-content">${info.content}</div>
                        </div>
                    </div>
                    
                    <div class="border-t pt-4 flex justify-between text-sm text-gray-500">
                        <span>Dibuat: ${new Date(info.created_at).toLocaleDateString('id-ID')}</span>
                        <span>Diupdate: ${new Date(info.updated_at).toLocaleDateString('id-ID')}</span>
                    </div>
                </div>
            `;
            
            document.getElementById('viewModal').classList.remove('hidden');
        }

        function deleteInfo(id) {
            if (!confirm('Apakah Anda yakin ingin menghapus artikel ini?')) return;

            fetch('api/ai-info.php', {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ id: id })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Error: ' + data.error);
                }
            });
        }

        function saveInfo(event) {
            event.preventDefault();
            
            // Ensure TinyMCE content is synced before validation
            if (tinymceEditor) {
                tinymceEditor.save();
            }
            
            const formData = new FormData(event.target);
            const data = Object.fromEntries(formData);
            
            // Get content from TinyMCE
            if (tinymceEditor) {
                data.content = tinymceEditor.getContent();
            }
            
            // Manual validation for required fields
            if (!data.title || data.title.trim() === '') {
                showNotification('Judul artikel harus diisi!', 'error');
                document.getElementById('title').focus();
                return;
            }
            
            if (!data.content || data.content.trim() === '' || data.content === '<p></p>' || data.content === '<p><br></p>') {
                showNotification('Isi artikel harus diisi!', 'error');
                if (tinymceEditor) {
                    tinymceEditor.focus();
                }
                return;
            }
            
            if (!data.target_role || data.target_role === '') {
                showNotification('Target pengguna harus dipilih!', 'error');
                document.getElementById('targetRole').focus();
                return;
            }
            
            // Show loading state
            const submitBtn = document.getElementById('submitBtn');
            const originalText = submitBtn.textContent;
            submitBtn.textContent = 'Menyimpan...';
            submitBtn.disabled = true;
            
            fetch('api/ai-info.php', {
                method: data.id && data.id !== '' ? 'PUT' : 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(data)
            })
            .then(response => response.json())
            .then(result => {
                submitBtn.textContent = originalText;
                submitBtn.disabled = false;
                
                if (result.success) {
                    closeModal();
                    // Show success message
                    showNotification('Data berhasil disimpan!', 'success');
                    // Reload page after short delay
                    setTimeout(() => {
                        location.reload();
                    }, 1000);
                } else {
                    showNotification('Error: ' + (result.error || 'Gagal menyimpan data'), 'error');
                }
            })
            .catch(error => {
                submitBtn.textContent = originalText;
                submitBtn.disabled = false;
                showNotification('Error: ' + error.message, 'error');
            });
        }
        
        // Simple notification function
        function showNotification(message, type = 'info') {
            const notification = document.createElement('div');
            notification.className = `fixed top-4 right-4 px-4 py-2 rounded-lg text-white z-50 ${
                type === 'success' ? 'bg-green-500' : 
                type === 'error' ? 'bg-red-500' : 'bg-blue-500'
            }`;
            notification.textContent = message;
            
            document.body.appendChild(notification);
            
            setTimeout(() => {
                notification.remove();
            }, 3000);
        }

        function closeModal() {
            document.getElementById('aiInfoModal').classList.add('hidden');
            
            // Reset TinyMCE content
            if (tinymceEditor) {
                tinymceEditor.setContent('');
            }
        }

        function closeViewModal() {
            document.getElementById('viewModal').classList.add('hidden');
        }

        // Close modals when clicking outside
        document.getElementById('aiInfoModal').addEventListener('click', function(e) {
            if (e.target === this) closeModal();
        });

        document.getElementById('viewModal').addEventListener('click', function(e) {
            if (e.target === this) closeViewModal();
        });
    </script>
</body>
</html>