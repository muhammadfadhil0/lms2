<?php
session_start();
$currentPage = 'kelas';

// Check if user is logged in and is a guru
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] != 'guru') {
    header("Location: ../../login.php");
    exit();
}

// Include logic files
require_once '../logic/kelas-logic.php';
require_once '../logic/postingan-logic.php';

// Check if kelas ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: beranda-guru.php");
    exit();
}

$kelasLogic = new KelasLogic();
$kelas_id = intval($_GET['id']);
$guru_id = $_SESSION['user']['id'];

// Get class details
$detailKelas = $kelasLogic->getDetailKelas($kelas_id);

// Check if class exists and belongs to this guru
if (!$detailKelas || $detailKelas['guru_id'] != $guru_id) {
    header("Location: beranda-guru.php");
    exit();
}

// Get class students
$siswaKelas = $kelasLogic->getSiswaKelas($kelas_id);
?>
<!-- includes -->
<?php require '../component/sidebar.php'; ?>
<?php require '../component/menu-bar-mobile.php'; ?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php require '../../assets/head.php'; ?>
    <title>Laporan Tugas - <?php echo htmlspecialchars($detailKelas['namaKelas']); ?></title>
</head>

<body class="bg-gray-50">
    <!-- Main Content -->
    <div class="md:ml-64 min-h-screen transition-all duration-300 ease-in-out" data-main-content>
        <!-- Breadcrumb -->
        <div class="bg-white border-b border-gray-200 p-4">
            <div class="flex items-center space-x-2 text-sm">
                <a href="kelas-guru.php?id=<?php echo $kelas_id; ?>" class="text-orange-600 hover:text-orange-800 flex items-center">
                    <i class="ti ti-arrow-left mr-1"></i>
                    Kembali ke Kelas
                </a>
                <span class="text-gray-400">/</span>
                <span class="text-gray-600">Laporan Tugas</span>
            </div>
        </div>

        <!-- Header -->
        <div class="bg-white border-b border-gray-200 p-6">
            <div class="max-w-7xl mx-auto">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">Laporan Tugas</h1>
                        <p class="text-gray-600 mt-1"><?php echo htmlspecialchars($detailKelas['namaKelas']); ?></p>
                    </div>
                    <div class="flex items-center space-x-3">
                        <div class="bg-blue-50 px-4 py-2 rounded-lg">
                            <span class="text-blue-600 font-medium"><?php echo count($siswaKelas); ?> Siswa</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content Area -->
        <div class="max-w-7xl mx-auto p-6">
            <!-- Assignment List -->
            <div class="bg-white rounded-lg shadow-sm mb-6">
                <div class="p-6 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">Daftar Tugas</h3>
                    <p class="text-gray-600 mt-1">Pilih tugas untuk melihat laporan pengumpulan siswa</p>
                </div>
                <div id="assignments-list" class="divide-y divide-gray-200">
                    <!-- Loading state -->
                    <div class="p-6 text-center text-gray-500">
                        <i class="ti ti-loader animate-spin text-4xl mb-4"></i>
                        <p class="text-lg font-medium">Memuat daftar tugas...</p>
                    </div>
                </div>
            </div>

            <!-- Assignment Report (will be shown when assignment is selected) -->
            <div id="assignment-report" class="hidden">
                <div class="bg-white rounded-lg shadow-sm">
                    <div class="p-6 border-b border-gray-200">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 id="selected-assignment-title" class="text-lg font-semibold text-gray-900">Judul Tugas</h3>
                                <p id="selected-assignment-info" class="text-gray-600 mt-1">Info tugas</p>
                            </div>
                            <div class="flex items-center space-x-4">
                                <div class="text-center">
                                    <div id="submitted-count" class="text-2xl font-bold text-green-600">0</div>
                                    <div class="text-sm text-gray-600">Terkumpul</div>
                                </div>
                                <div class="text-center">
                                    <div id="graded-count" class="text-2xl font-bold text-blue-600">0</div>
                                    <div class="text-sm text-gray-600">Dinilai</div>
                                </div>
                                <div class="text-center">
                                    <div id="pending-count" class="text-2xl font-bold text-orange-600">0</div>
                                    <div class="text-sm text-gray-600">Belum Terkumpul</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Student Submissions Table -->
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Siswa</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tanggal Pengumpulan</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nilai</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                                </tr>
                            </thead>
                            <tbody id="submissions-table" class="bg-white divide-y divide-gray-200">
                                <!-- Will be populated by JavaScript -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Grade Assignment Modal -->
    <div id="grade-modal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
        <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-2/3 lg:w-1/2 shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-medium text-gray-900">Nilai Tugas Siswa</h3>
                    <button onclick="closeGradeModal()" class="text-gray-400 hover:text-gray-600">
                        <i class="ti ti-x text-xl"></i>
                    </button>
                </div>
                
                <form id="grade-form">
                    <input type="hidden" id="submission_id" name="submission_id">
                    
                    <div class="mb-4">
                        <h4 id="student-name" class="font-medium text-gray-900 mb-2">Nama Siswa</h4>
                        <div class="bg-gray-50 p-4 rounded-lg mb-4">
                            <p class="text-sm text-gray-600 mb-2">File yang dikumpulkan:</p>
                            <a id="submission-file-link" href="#" target="_blank" class="text-blue-600 hover:text-blue-800 flex items-center">
                                <i class="ti ti-file mr-2"></i>
                                <span id="submission-file-name">file.pdf</span>
                            </a>
                            <div id="submission-notes" class="mt-3"></div>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <label for="score" class="block text-sm font-medium text-gray-700 mb-2">Nilai</label>
                        <input type="number" id="score" name="score" min="0" max="100" 
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <p class="text-sm text-gray-500 mt-1">Nilai maksimal: <span id="max-score">100</span></p>
                    </div>
                    
                    <div class="mb-6">
                        <label for="feedback" class="block text-sm font-medium text-gray-700 mb-2">Feedback untuk Siswa</label>
                        <textarea id="feedback" name="feedback" rows="4" 
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                            placeholder="Berikan feedback untuk siswa..."></textarea>
                    </div>
                    
                    <div class="flex justify-end space-x-3">
                        <button type="button" onclick="closeGradeModal()" 
                            class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">
                            Batal
                        </button>
                        <button type="submit" 
                            class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                            Simpan Nilai
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="../script/menu-bar-script.js"></script>
    <script>
        const kelasId = <?php echo $kelas_id; ?>;
        const preselectedAssignmentId = <?php echo isset($_GET['assignment_id']) ? intval($_GET['assignment_id']) : 'null'; ?>;
        let currentAssignmentId = null;

        // Load assignments when page loads
        document.addEventListener('DOMContentLoaded', function() {
            loadAssignments();
        });

        function loadAssignments() {
            console.log('🎯 Loading assignments for kelas:', kelasId);
            
            fetch('../logic/get-assignments.php?kelas_id=' + kelasId)
                .then(response => {
                    console.log('📡 Response status:', response.status);
                    return response.json();
                })
                .then(data => {
                    console.log('📦 Response data:', data);
                    
                    if (data.success) {
                        displayAssignments(data.assignments);
                        
                        // Auto-select assignment if specified in URL
                        if (preselectedAssignmentId && data.assignments.find(a => a.id == preselectedAssignmentId)) {
                            selectAssignment(preselectedAssignmentId);
                        }
                    } else {
                        console.error('❌ Assignment loading failed:', data.message);
                        document.getElementById('assignments-list').innerHTML = 
                            '<div class="p-6 text-center text-red-500">Error: ' + data.message + '</div>';
                    }
                })
                .catch(error => {
                    console.error('❌ Fetch error:', error);
                    document.getElementById('assignments-list').innerHTML = 
                        '<div class="p-6 text-center text-red-500">Network error: ' + error.message + '</div>';
                });
        }

        function displayAssignments(assignments) {
            const container = document.getElementById('assignments-list');
            if (assignments.length === 0) {
                container.innerHTML = '<div class="p-6 text-center text-gray-500">Belum ada tugas di kelas ini</div>';
                return;
            }

            container.innerHTML = assignments.map(assignment => `
                <div class="border-b last:border-b-0 assignment-item" data-assignment-id="${assignment.id}">
                    <div class="p-6 hover:bg-gray-50 transition-colors">
                        <div class="flex items-center justify-between">
                            <div class="flex-1 cursor-pointer" onclick="selectAssignment(${assignment.id})">
                                <h4 class="font-medium text-gray-900">${assignment.judul}</h4>
                                <p class="text-sm text-gray-600 mt-1">${assignment.deskripsi}</p>
                                <div class="flex items-center space-x-4 mt-2 text-sm text-gray-500">
                                    <span><i class="ti ti-clock mr-1"></i>Deadline: ${new Date(assignment.deadline).toLocaleString('id-ID')}</span>
                                    <span><i class="ti ti-star mr-1"></i>Nilai Maks: ${assignment.nilai_maksimal}</span>
                                </div>
                            </div>
                            <div class="flex items-center space-x-4">
                                <div class="text-right">
                                    <div class="text-sm text-gray-500">Terkumpul</div>
                                    <div class="text-2xl font-bold text-blue-600">${assignment.submitted_count || 0}/${assignment.total_students || 0}</div>
                                </div>
                                <button onclick="deleteAssignment(${assignment.id})" 
                                        class="text-red-600 hover:text-red-800 hover:bg-red-50 p-2 rounded-lg transition-colors"
                                        title="Hapus Tugas">
                                    <i class="ti ti-trash text-lg"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            `).join('');
        }

        function selectAssignment(assignmentId) {
            // Remove previous selection
            document.querySelectorAll('.assignment-item').forEach(item => {
                item.classList.remove('bg-blue-50', 'border-l-4', 'border-l-blue-500');
            });
            
            // Highlight selected assignment
            const selectedItem = document.querySelector(`[data-assignment-id="${assignmentId}"]`);
            if (selectedItem) {
                selectedItem.classList.add('bg-blue-50', 'border-l-4', 'border-l-blue-500');
            }
            
            currentAssignmentId = assignmentId;
            loadAssignmentReport(assignmentId);
        }

        function loadAssignmentReport(assignmentId) {
            console.log('🎯 Loading assignment report for ID:', assignmentId);
            
            fetch('../logic/get-assignment-report.php?assignment_id=' + assignmentId)
                .then(response => {
                    console.log('📡 Report response status:', response.status);
                    return response.json();
                })
                .then(data => {
                    console.log('📦 Report data:', data);
                    
                    if (data.success) {
                        displayAssignmentReport(data.assignment, data.submissions);
                    } else {
                        console.error('❌ Report loading failed:', data.message);
                        alert('Gagal memuat laporan tugas: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('❌ Report fetch error:', error);
                    alert('Terjadi kesalahan saat memuat laporan: ' + error.message);
                });
        }

        function displayAssignmentReport(assignment, submissions) {
            // Update assignment info
            document.getElementById('selected-assignment-title').textContent = assignment.judul;
            document.getElementById('selected-assignment-info').textContent = 
                `Deadline: ${new Date(assignment.deadline).toLocaleString('id-ID')} • Nilai Maks: ${assignment.nilai_maksimal}`;

            // Update statistics
            const submitted = submissions.filter(s => s.status !== 'belum_mengumpulkan').length;
            const graded = submissions.filter(s => s.status === 'dinilai').length;
            const pending = submissions.filter(s => s.status === 'belum_mengumpulkan').length;

            document.getElementById('submitted-count').textContent = submitted;
            document.getElementById('graded-count').textContent = graded;
            document.getElementById('pending-count').textContent = pending;

            // Update submissions table
            const tbody = document.getElementById('submissions-table');
            tbody.innerHTML = submissions.map(submission => {
                let statusBadge = '';
                let actionButton = '';

                switch(submission.status) {
                    case 'dikumpulkan':
                        statusBadge = '<span class="px-2 py-1 text-xs font-semibold bg-yellow-100 text-yellow-800 rounded-full">Menunggu Penilaian</span>';
                        actionButton = `<button onclick="gradeSubmission(${submission.submission_id})" class="text-blue-600 hover:text-blue-800 text-sm font-medium">Nilai</button>`;
                        break;
                    case 'dinilai':
                        statusBadge = '<span class="px-2 py-1 text-xs font-semibold bg-green-100 text-green-800 rounded-full">Sudah Dinilai</span>';
                        actionButton = `<button onclick="gradeSubmission(${submission.submission_id})" class="text-blue-600 hover:text-blue-800 text-sm font-medium">Edit Nilai</button>`;
                        break;
                    default:
                        statusBadge = '<span class="px-2 py-1 text-xs font-semibold bg-red-100 text-red-800 rounded-full">Belum Mengumpulkan</span>';
                        actionButton = '<span class="text-gray-400 text-sm">-</span>';
                }

                return `
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div class="w-8 h-8 bg-gray-300 rounded-full flex items-center justify-center mr-3">
                                    <i class="ti ti-user text-gray-600"></i>
                                </div>
                                <div class="text-sm font-medium text-gray-900">${submission.nama_siswa}</div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">${statusBadge}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            ${submission.tanggal_pengumpulan ? new Date(submission.tanggal_pengumpulan).toLocaleString('id-ID') : '-'}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            ${submission.nilai !== null ? `${submission.nilai}/${assignment.nilai_maksimal}` : '-'}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm">${actionButton}</td>
                    </tr>
                `;
            }).join('');

            // Show the report
            document.getElementById('assignment-report').classList.remove('hidden');
        }

        function gradeSubmission(submissionId) {
            console.log('🎯 gradeSubmission called with ID:', submissionId);
            
            fetch('../logic/get-submission-details.php?submission_id=' + submissionId)
                .then(response => {
                    console.log('📡 get-submission-details response status:', response.status);
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {
                    console.log('📦 get-submission-details data:', data);
                    if (data.success) {
                        showGradeModal(data.submission);
                    } else {
                        console.error('❌ API returned error:', data.message);
                        alert('Gagal memuat detail pengumpulan: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('❌ Fetch error:', error);
                    alert('Terjadi kesalahan saat memuat detail pengumpulan: ' + error.message);
                });
        }

        function showGradeModal(submission) {
            console.log('🎯 showGradeModal called with submission:', submission);
            
            document.getElementById('submission_id').value = submission.id;
            document.getElementById('student-name').textContent = submission.nama_siswa;
            document.getElementById('submission-file-name').textContent = submission.file_path;
            document.getElementById('submission-file-link').href = '../../' + submission.file_path;
            document.getElementById('max-score').textContent = submission.nilai_maksimal;
            
            console.log('📁 File path set to:', '../../' + submission.file_path);
            
            if (submission.catatan_pengumpulan) {
                document.getElementById('submission-notes').innerHTML = 
                    '<p class="text-sm text-gray-600"><strong>Catatan siswa:</strong> ' + submission.catatan_pengumpulan + '</p>';
            }
            
            if (submission.nilai !== null) {
                document.getElementById('score').value = submission.nilai;
                document.getElementById('feedback').value = submission.feedback || '';
            }

            document.getElementById('grade-modal').classList.remove('hidden');
        }

        function closeGradeModal() {
            document.getElementById('grade-modal').classList.add('hidden');
            document.getElementById('grade-form').reset();
        }

        // Handle grade form submission
        document.getElementById('grade-form').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            
            fetch('../logic/grade-submission.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    closeGradeModal();
                    loadAssignmentReport(currentAssignmentId); // Reload the report
                    alert('Nilai berhasil disimpan');
                } else {
                    alert('Gagal menyimpan nilai: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Terjadi kesalahan saat menyimpan nilai');
            });
        });

        // Delete assignment function
        function deleteAssignment(assignmentId) {
            if (!confirm('Apakah Anda yakin ingin menghapus tugas ini? Semua data pengumpulan dan nilai akan terhapus.')) {
                return;
            }

            const formData = new FormData();
            formData.append('assignment_id', assignmentId);

            fetch('../logic/delete-assignment.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Tugas berhasil dihapus');
                    loadAssignments(); // Reload assignments list
                    
                    // Clear assignment report if this was the selected assignment
                    if (currentAssignmentId == assignmentId) {
                        currentAssignmentId = null;
                        document.getElementById('selected-assignment-title').textContent = 'Pilih tugas dari daftar';
                        document.getElementById('selected-assignment-info').textContent = '';
                        document.getElementById('submissions-table').innerHTML = '';
                        document.getElementById('submitted-count').textContent = '0';
                        document.getElementById('graded-count').textContent = '0';
                        document.getElementById('pending-count').textContent = '0';
                    }
                } else {
                    alert('Gagal menghapus tugas: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Terjadi kesalahan saat menghapus tugas');
            });
        }
    </script>
</body>

</html>
