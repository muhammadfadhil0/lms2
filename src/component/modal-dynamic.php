<?php
// Modal Dynamic Component
// Include this component in any front page that needs dynamic modal support

// Only load if user is logged in
if (!isset($_SESSION['user'])) {
    return;
}

// Get current page filename
$current_page = basename($_SERVER['SCRIPT_NAME']);
$user_id = $_SESSION['user']['id'];
$session_id = session_id();

// Database connection should already be available
if (!isset($koneksi)) {
    require_once '../logic/koneksi.php';
}

// Function to check if modal should be displayed
function shouldDisplayModal($modal, $user_id, $session_id, $current_page, $koneksi)
{
    global $current_page;

    // Check if modal is active
    if (!$modal['is_active']) {
        return false;
    }

    // Check if current page is in target files
    $target_files = json_decode($modal['target_files'], true);
    if (!in_array($current_page, $target_files)) {
        return false;
    }

    // Check display frequency
    switch ($modal['display_frequency']) {
        case 'once_forever':
            // Check if user has ever seen this modal
            $check_stmt = $koneksi->prepare("SELECT id FROM modal_display_tracking WHERE modal_id = ? AND user_id = ? LIMIT 1");
            $check_stmt->bind_param("ii", $modal['id'], $user_id);
            $check_stmt->execute();
            $result = $check_stmt->get_result();
            return $result->num_rows === 0; // Show only if never seen

        case 'once_per_session':
            // Check if user has seen this modal in current session
            // First check if there's any record for this modal+user+page combination
            $check_stmt = $koneksi->prepare("SELECT session_id, displayed_at FROM modal_display_tracking WHERE modal_id = ? AND user_id = ? AND file_name = ? ORDER BY displayed_at DESC LIMIT 1");
            $check_stmt->bind_param("iis", $modal['id'], $user_id, $current_page);
            $check_stmt->execute();
            $result = $check_stmt->get_result();
            
            if ($result->num_rows === 0) {
                return true; // Never shown before, show it
            }
            
            $row = $result->fetch_assoc();
            // If session_id matches current session, don't show again
            if ($row['session_id'] === $session_id) {
                return false; // Already shown in this session
            }
            
            return true; // Different session, show it

        case 'always':
        default:
            return true; // Always show
    }
}

// Function to record modal display
function recordModalDisplay($modal_id, $user_id, $session_id, $current_page, $koneksi)
{
    // Check if record already exists
    $check_stmt = $koneksi->prepare("SELECT id FROM modal_display_tracking WHERE modal_id = ? AND user_id = ? AND file_name = ?");
    $check_stmt->bind_param("iis", $modal_id, $user_id, $current_page);
    $check_stmt->execute();
    $result = $check_stmt->get_result();
    
    if ($result->num_rows > 0) {
        // Update existing record with new session_id and timestamp
        $update_stmt = $koneksi->prepare("UPDATE modal_display_tracking SET session_id = ?, displayed_at = CURRENT_TIMESTAMP WHERE modal_id = ? AND user_id = ? AND file_name = ?");
        $update_stmt->bind_param("siis", $session_id, $modal_id, $user_id, $current_page);
        $update_stmt->execute();
    } else {
        // Insert new record
        $insert_stmt = $koneksi->prepare("INSERT INTO modal_display_tracking (modal_id, user_id, file_name, session_id) VALUES (?, ?, ?, ?)");
        $insert_stmt->bind_param("iiss", $modal_id, $user_id, $current_page, $session_id);
        $insert_stmt->execute();
    }
}

// Get all active dynamic modals ordered by priority
$modals_query = "SELECT * FROM dynamic_modals 
                WHERE is_active = 1 
                ORDER BY priority DESC, created_at DESC";
$modals_result = $koneksi->query($modals_query);

$display_modals = [];
while ($modal = $modals_result->fetch_assoc()) {
    if (shouldDisplayModal($modal, $user_id, $session_id, $current_page, $koneksi)) {
        $display_modals[] = $modal;
        // Record that we're about to show this modal
        recordModalDisplay($modal['id'], $user_id, $session_id, $current_page, $koneksi);
    }
}

// If we have modals to display, show them
if (!empty($display_modals)): ?>
    <style>
        .dynamic-modal {
            display: none;
            position: fixed;
            z-index: 10000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(5px);
        }

        .dynamic-modal.show {
            display: flex;
            align-items: center;
            justify-content: center;
            animation: fadeIn 0.3s ease-in-out;
        }

        .dynamic-modal-dialog {
            background: white;
            border-radius: 12px;
            max-width: 500px;
            width: 90%;
            max-height: 80vh;
            overflow-y: auto;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            animation: slideIn 0.3s ease-out;
            position: relative;
        }

        .dynamic-modal-content {
            padding: 24px;
        }

        .dynamic-modal-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 20px;
        }

        .dynamic-modal-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: #1f2937;
            margin: 0;
            flex: 1;
            padding-right: 16px;
        }

        .dynamic-modal-close {
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: #6b7280;
            padding: 4px;
            border-radius: 4px;
            transition: all 0.2s;
            flex-shrink: 0;
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .dynamic-modal-close:hover {
            color: #374151;
            background-color: #f3f4f6;
        }

        .dynamic-modal-image {
            width: 100%;
            max-height: 300px;
            object-fit: cover;
            border-radius: 8px;
            margin-bottom: 16px;
        }

        .dynamic-modal-description {
            color: #4b5563;
            line-height: 1.6;
            margin-bottom: 24px;
        }

        .dynamic-modal-actions {
            display: flex;
            gap: 12px;
            justify-content: stretch;
        }

        .dynamic-modal-btn {
            flex: 1;
            padding: 12px 16px;
            border-radius: 6px;
            border: none;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.2s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .dynamic-modal-btn-primary {
            background: #f97316;
            color: white;
        }

        .dynamic-modal-btn-primary:hover {
            background: #ea580c;
        }

        .dynamic-modal-btn-secondary {
            background: #f3f4f6;
            color: #374151;
        }

        .dynamic-modal-btn-secondary:hover {
            background: #e5e7eb;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
            }

            to {
                opacity: 1;
            }
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-20px) scale(0.95);
            }

            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        .dynamic-modal-counter {
            position: absolute;
            top: 16px;
            right: 60px;
            background: rgba(0, 0, 0, 0.7);
            color: white;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 500;
        }
    </style>

    <!-- Dynamic Modals -->
    <?php foreach ($display_modals as $index => $modal): ?>
        <div id="dynamic-modal-<?= $modal['id'] ?>" class="dynamic-modal" data-modal-id="<?= $modal['id'] ?>">
            <div class="dynamic-modal-dialog">
                <div class="dynamic-modal-content">
                    <?php if ($modal['image_path']): ?>
                        <img src="../../<?= htmlspecialchars($modal['image_path']) ?>"
                            alt="<?= htmlspecialchars($modal['title']) ?>" class="dynamic-modal-image">
                    <?php endif; ?>

                    <div class="dynamic-modal-description">
                        <h3 class="dynamic-modal-title"><?= htmlspecialchars($modal['title']) ?></h3>
                        <?= nl2br(htmlspecialchars($modal['description'])) ?>
                    </div>

                    <div class="dynamic-modal-actions">
                        <button class="dynamic-modal-btn dynamic-modal-btn-secondary"
                            onclick="closeDynamicModal(<?= $modal['id'] ?>)">
                            Tutup
                        </button>
                        <?php if (count($display_modals) > 1): ?>
                            <?php if ($index < count($display_modals) - 1): ?>
                                <button class="dynamic-modal-btn dynamic-modal-btn-primary"
                                    onclick="nextDynamicModal(<?= $modal['id'] ?>)">
                                    Selanjutnya
                                </button>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>

                    <?php if (count($display_modals) > 1): ?>
                        <div class="dynamic-modal-counter">
                            <?= $index + 1 ?> dari <?= count($display_modals) ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endforeach; ?>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const dynamicModals = <?= json_encode($display_modals) ?>;

            if (dynamicModals.length > 0) {
                // Show first modal after a short delay
                setTimeout(() => {
                    showDynamicModal(dynamicModals[0].id);
                }, 500);
            }
        });

        function showDynamicModal(modalId) {
            document.getElementById(`dynamic-modal-${modalId}`).classList.add('show');
        }

        function closeDynamicModal(modalId) {
            document.getElementById(`dynamic-modal-${modalId}`).classList.remove('show');
        }

        function nextDynamicModal(currentModalId) {
            const dynamicModals = <?= json_encode($display_modals) ?>;
            const currentIndex = dynamicModals.findIndex(modal => modal.id == currentModalId);

            // Close current modal
            closeDynamicModal(currentModalId);

            // Show next modal if exists
            if (currentIndex >= 0 && currentIndex < dynamicModals.length - 1) {
                setTimeout(() => {
                    showDynamicModal(dynamicModals[currentIndex + 1].id);
                }, 300);
            }
        }

        // Close modal when clicking outside
        document.addEventListener('click', function (event) {
            if (event.target.classList.contains('dynamic-modal')) {
                const modalId = event.target.dataset.modalId;
                closeDynamicModal(modalId);
            }
        });

        // Close modal with Escape key
        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') {
                const openModal = document.querySelector('.dynamic-modal.show');
                if (openModal) {
                    const modalId = openModal.dataset.modalId;
                    closeDynamicModal(modalId);
                }
            }
        });
    </script>

<?php endif; // End if display_modals ?>