<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: ../index.php");
    exit;
}

require '../includes/db_connect.php';

// Fetch student details
$studentID = $_SESSION['ref_id'];
$stmt = $pdo->prepare("SELECT * FROM Student WHERE StudentID = ?");
$stmt->execute([$studentID]);
$student = $stmt->fetch();

if (!$student) {
    die("Error: Could not find student details for ID: " . htmlspecialchars($studentID));
}

$message = '';

// Helper function to safely get array values
function safeGet($array, $key, $default = '') {
    return isset($array[$key]) ? $array[$key] : $default;
}

// FIXED: Function to generate unique NotificationID
function generateUniqueNotificationID($pdo) {
    $maxAttempts = 10;
    
    for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
        // Generate ID with microtime for better uniqueness
        $microtime = str_replace('.', '', microtime(true));
        $randomPart = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
        $notificationID = 'N' . substr($microtime, -8) . $randomPart;
        
        // Check if this ID already exists
        $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM Notification WHERE NotificationID = ?");
        $checkStmt->execute([$notificationID]);
        
        if ($checkStmt->fetchColumn() == 0) {
            return $notificationID; // Unique ID found
        }
    }
    
    // Fallback: use timestamp with random suffix
    return 'N' . time() . rand(100000, 999999);
}

// FIXED: Handle request deletion with proper unique ID generation
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_request'])) {
    $requestID = $_POST['request_id'];
    
    try {
        $pdo->beginTransaction();
        
        // Check if request is still pending and belongs to this student
        $checkStmt = $pdo->prepare("SELECT * FROM RescheduleRequest WHERE RequestID = ? AND StudentID = ? AND Status = 'Pending'");
        $checkStmt->execute([$requestID, $studentID]);
        $request = $checkStmt->fetch();
        
        if ($request) {
            // Delete the request
            $deleteStmt = $pdo->prepare("DELETE FROM RescheduleRequest WHERE RequestID = ? AND StudentID = ? AND Status = 'Pending'");
            $deleteStmt->execute([$requestID, $studentID]);
            
            // FIXED: Generate unique NotificationID
            $notificationID = generateUniqueNotificationID($pdo);
            $notifMessage = "Your reschedule request (ID: $requestID) has been cancelled successfully.";
            $notifStmt = $pdo->prepare("INSERT INTO Notification (NotificationID, Message, Timestamp, Type, StudentID) VALUES (?, ?, ?, ?, ?)");
            $notifStmt->execute([$notificationID, $notifMessage, date('Y-m-d H:i:s'), 'request_cancelled', $studentID]);
            
            $pdo->commit();
            $message = "<div class='alert alert-success'><i class='fas fa-check-circle me-2'></i>Request deleted successfully!</div>";
        } else {
            $message = "<div class='alert alert-danger'><i class='fas fa-exclamation-triangle me-2'></i>Request not found or cannot be deleted.</div>";
        }
        
    } catch (PDOException $e) {
        $pdo->rollback();
        error_log("Delete Request Error: " . $e->getMessage());
        $message = "<div class='alert alert-danger'><i class='fas fa-exclamation-triangle me-2'></i>Error deleting request: " . htmlspecialchars($e->getMessage()) . "</div>";
    }
}

// FIXED: Handle request update with proper unique ID generation
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_request'])) {
    $requestID = $_POST['request_id'];
    $newReason = trim($_POST['reason']);
    
    try {
        $pdo->beginTransaction();
        
        // Check if request is still pending and belongs to this student
        $checkStmt = $pdo->prepare("SELECT * FROM RescheduleRequest WHERE RequestID = ? AND StudentID = ? AND Status = 'Pending'");
        $checkStmt->execute([$requestID, $studentID]);
        $request = $checkStmt->fetch();
        
        if ($request) {
            // Update the request
            $updateStmt = $pdo->prepare("UPDATE RescheduleRequest SET Reason = ? WHERE RequestID = ? AND StudentID = ? AND Status = 'Pending'");
            $updateStmt->execute([$newReason, $requestID, $studentID]);
            
            // FIXED: Generate unique NotificationID
            $notificationID = generateUniqueNotificationID($pdo);
            $notifMessage = "Your reschedule request (ID: $requestID) has been updated successfully.";
            $notifStmt = $pdo->prepare("INSERT INTO Notification (NotificationID, Message, Timestamp, Type, StudentID) VALUES (?, ?, ?, ?, ?)");
            $notifStmt->execute([$notificationID, $notifMessage, date('Y-m-d H:i:s'), 'request_updated', $studentID]);
            
            $pdo->commit();
            $message = "<div class='alert alert-success'><i class='fas fa-check-circle me-2'></i>Request updated successfully!</div>";
        } else {
            $message = "<div class='alert alert-danger'><i class='fas fa-exclamation-triangle me-2'></i>Request not found or cannot be updated.</div>";
        }
        
    } catch (PDOException $e) {
        $pdo->rollback();
        error_log("Update Request Error: " . $e->getMessage());
        $message = "<div class='alert alert-danger'><i class='fas fa-exclamation-triangle me-2'></i>Error updating request: " . htmlspecialchars($e->getMessage()) . "</div>";
    }
}

// Fetch all requests for this student
$requests = $pdo->prepare("
    SELECT rr.*, ls.Date as ScheduleDate, ls.TimeSlot, l.LabName, l.Location,
           rl.Timestamp as DecisionTimestamp
    FROM RescheduleRequest rr
    LEFT JOIN LabSchedule ls ON rr.ScheduleID = ls.ScheduleID
    LEFT JOIN Lab l ON ls.LabID = l.LabID
    LEFT JOIN RescheduleLog rl ON rr.RequestID = rl.RequestID AND rl.Action = rr.Status
    WHERE rr.StudentID = ?
    ORDER BY rr.RequestDate DESC
");
$requests->execute([$studentID]);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Requests - EduPortal</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <style>
        .request-card {
            border: 1px solid #e3e6f0;
            border-radius: 10px;
            transition: all 0.3s ease;
            margin-bottom: 20px;
        }
        
        .request-card:hover {
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            transform: translateY(-2px);
        }
        
        .status-badge {
            font-size: 0.8rem;
            padding: 0.4rem 0.8rem;
        }
        
        .edit-form {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
            margin-top: 15px;
            display: none;
        }
        
        .request-actions {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }
        
        .pending-card {
            border-left: 4px solid #ffc107;
        }
        
        .approved-card {
            border-left: 4px solid #28a745;
        }
        
        .rejected-card {
            border-left: 4px solid #dc3545;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary fixed-top">
        <div class="container-fluid">
            <a class="navbar-brand d-flex align-items-center" href="../index.php">
                <i class="fas fa-graduation-cap me-2"></i>
                <span class="fw-bold">EduPortal</span>
            </a>
            
            <div class="navbar-nav ms-auto">
                <a href="student_dashboard.php" class="nav-link text-white me-3">
                    <i class="fas fa-arrow-left me-1"></i>Back to Dashboard
                </a>
                <a href="logout.php" class="nav-link text-danger">
                    <i class="fas fa-sign-out-alt me-1"></i>Logout
                </a>
            </div>
        </div>
    </nav>
    
    <!-- Main Content -->
    <main class="main-content">
        <div class="container-fluid">
            <div class="page-header fade-in">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h1 class="page-title">
                            <i class="fas fa-edit me-3"></i>
                            Manage Reschedule Requests
                        </h1>
                        <p class="page-subtitle">View, edit, and delete your reschedule requests</p>
                    </div>
                    <div class="col-md-4 text-end">
                        <a href="reschedule_request.php" class="btn btn-primary">
                            <i class="fas fa-plus me-2"></i>New Request
                        </a>
                    </div>
                </div>
            </div>

            <?= $message ?>

            <div class="row">
                <div class="col-12">
                    <?php if ($requests->rowCount() > 0): ?>
                        <?php while ($request = $requests->fetch()): ?>
                            <?php
                            $statusClass = match($request['Status']) {
                                'Pending' => 'warning',
                                'Approved' => 'success',
                                'Rejected' => 'danger',
                                default => 'secondary'
                            };
                            
                            $cardClass = match($request['Status']) {
                                'Pending' => 'pending-card',
                                'Approved' => 'approved-card',
                                'Rejected' => 'rejected-card',
                                default => ''
                            };
                            ?>
                            <div class="request-card <?= $cardClass ?> fade-in">
                                <div class="card-body">
                                    <div class="row align-items-center">
                                        <div class="col-md-3">
                                            <h5 class="mb-1">
                                                <i class="fas fa-flask me-2 text-primary"></i>
                                                <?= htmlspecialchars(safeGet($request, 'LabName', 'Lab Name Not Available')) ?>
                                            </h5>
                                            <small class="text-muted">
                                                <i class="fas fa-map-marker-alt me-1"></i>
                                                <?= htmlspecialchars(safeGet($request, 'Location', 'Location Not Available')) ?>
                                            </small>
                                        </div>
                                        
                                        <div class="col-md-2">
                                            <strong>Request ID:</strong><br>
                                            <span class="badge bg-secondary"><?= htmlspecialchars($request['RequestID']) ?></span>
                                        </div>
                                        
                                        <div class="col-md-2">
                                            <strong>Schedule Date:</strong><br>
                                            <span><?= $request['ScheduleDate'] ? date('M d, Y', strtotime($request['ScheduleDate'])) : 'N/A' ?></span><br>
                                            <small class="text-muted"><?= htmlspecialchars(safeGet($request, 'TimeSlot', 'Time Not Available')) ?></small>
                                        </div>
                                        
                                        <div class="col-md-2">
                                            <strong>Request Date:</strong><br>
                                            <span><?= date('M d, Y', strtotime($request['RequestDate'])) ?></span><br>
                                            <small class="text-muted"><?= date('g:i A', strtotime($request['RequestDate'])) ?></small>
                                        </div>
                                        
                                        <div class="col-md-3">
                                            <div class="mb-2">
                                                <span class="badge bg-<?= $statusClass ?> status-badge"><?= htmlspecialchars($request['Status']) ?></span>
                                                
                                                <?php if ($request['DecisionTimestamp'] && $request['Status'] !== 'Pending'): ?>
                                                    <br><small class="text-muted">
                                                        <i class="fas fa-clock me-1"></i>
                                                        Decision: <?= date('M d, Y g:i A', strtotime($request['DecisionTimestamp'])) ?>
                                                    </small>
                                                <?php endif; ?>
                                            </div>
                                            
                                            <?php if ($request['Status'] === 'Pending'): ?>
                                            <div class="request-actions">
                                                <button class="btn btn-sm btn-outline-primary" 
                                                        onclick="toggleEditForm('<?= $request['RequestID'] ?>')">
                                                    <i class="fas fa-edit me-1"></i>Edit
                                                </button>
                                                
                                                <button class="btn btn-sm btn-outline-danger" 
                                                        onclick="confirmDelete('<?= $request['RequestID'] ?>', '<?= htmlspecialchars(safeGet($request, 'LabName', 'Lab')) ?>')">
                                                    <i class="fas fa-trash me-1"></i>Delete
                                                </button>
                                            </div>
                                            <?php else: ?>
                                            <small class="text-muted">
                                                <i class="fas fa-lock me-1"></i>Cannot modify processed requests
                                            </small>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    
                                    <!-- Current Reason Display -->
                                    <div class="row mt-3">
                                        <div class="col-12">
                                            <strong>Reason:</strong>
                                            <p class="mb-0 text-muted"><?= htmlspecialchars($request['Reason']) ?></p>
                                        </div>
                                    </div>
                                    
                                    <!-- Edit Form (Hidden by default) -->
                                    <?php if ($request['Status'] === 'Pending'): ?>
                                    <div class="edit-form" id="edit-form-<?= $request['RequestID'] ?>">
                                        <form method="POST">
                                            <input type="hidden" name="request_id" value="<?= htmlspecialchars($request['RequestID']) ?>">
                                            
                                            <div class="mb-3">
                                                <label class="form-label">Update Reason for Reschedule</label>
                                                <textarea name="reason" class="form-control" rows="3" required 
                                                          placeholder="Please provide a detailed reason for your reschedule request..."><?= htmlspecialchars($request['Reason']) ?></textarea>
                                                <small class="text-muted">Provide a clear and detailed explanation for why you need to reschedule this lab session.</small>
                                            </div>
                                            
                                            <div class="d-flex gap-2">
                                                <button type="submit" name="update_request" class="btn btn-success">
                                                    <i class="fas fa-save me-2"></i>Save Changes
                                                </button>
                                                <button type="button" class="btn btn-secondary" 
                                                        onclick="toggleEditForm('<?= $request['RequestID'] ?>')">
                                                    <i class="fas fa-times me-2"></i>Cancel
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="card fade-in">
                            <div class="card-body text-center py-5">
                                <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                                <h5>No Reschedule Requests Found</h5>
                                <p class="text-muted">You haven't submitted any reschedule requests yet.</p>
                                <a href="reschedule_request.php" class="btn btn-primary">
                                    <i class="fas fa-plus me-2"></i>Create Your First Request
                                </a>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title text-danger">
                        <i class="fas fa-exclamation-triangle me-2"></i>Confirm Deletion
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p id="deleteMessage">Are you sure you want to delete this reschedule request?</p>
                    <div class="alert alert-warning">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Note:</strong> This action cannot be undone. Once deleted, you'll need to create a new request if needed.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <form method="POST" style="display: inline;">
                        <input type="hidden" name="request_id" id="deleteRequestId">
                        <button type="submit" name="delete_request" class="btn btn-danger">
                            <i class="fas fa-trash me-2"></i>Delete Request
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-dark text-light py-4 mt-5">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <h5><i class="fas fa-graduation-cap me-2"></i>EduPortal</h5>
                    <p class="mb-0">Professional Lab Management System</p>
                </div>
                <div class="col-md-6 text-end">
                    <p class="mb-0">&copy; <?php echo date('Y'); ?> EduPortal. All rights reserved.</p>
                    <small class="text-muted">Version 2.0</small>
                </div>
            </div>
        </div>
    </footer>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
    // Toggle edit form visibility
    function toggleEditForm(requestId) {
        const editForm = document.getElementById('edit-form-' + requestId);
        if (editForm.style.display === 'none' || editForm.style.display === '') {
            editForm.style.display = 'block';
        } else {
            editForm.style.display = 'none';
        }
    }
    
    // Confirm delete with details
    function confirmDelete(requestId, labName) {
        const deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
        const deleteMessage = document.getElementById('deleteMessage');
        const deleteRequestId = document.getElementById('deleteRequestId');
        
        deleteMessage.innerHTML = `
            <strong>Request ID:</strong> ${requestId}<br>
            <strong>Lab:</strong> ${labName}<br><br>
            Are you sure you want to delete this reschedule request? This action cannot be undone.
        `;
        
        deleteRequestId.value = requestId;
        deleteModal.show();
    }
    
    // Auto-hide alerts
    document.addEventListener('DOMContentLoaded', function() {
        const alerts = document.querySelectorAll('.alert');
        alerts.forEach(alert => {
            setTimeout(() => {
                alert.style.opacity = '0';
                alert.style.transform = 'translateY(-10px)';
                setTimeout(() => {
                    if (alert.parentNode) {
                        alert.remove();
                    }
                }, 300);
            }, 5000);
        });
    });
    </script>
</body>
</html>
