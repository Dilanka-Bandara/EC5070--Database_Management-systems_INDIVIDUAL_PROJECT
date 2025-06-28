<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'coordinator') {
    header("Location: ../index.php");
    exit;
}

require '../includes/db_connect.php';

// Fetch coordinator details
$coordinatorID = $_SESSION['ref_id'];
$stmt = $pdo->prepare("SELECT * FROM SubjectCoordinator WHERE CoordinatorID = ?");
$stmt->execute([$coordinatorID]);
$coordinator = $stmt->fetch();

if (!$coordinator) {
    die("Error: Could not find coordinator details for ID: " . htmlspecialchars($coordinatorID));
}

$message = '';

// Handle schedule deletion
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_schedule'])) {
    $scheduleID = $_POST['schedule_id'];
    
    try {
        $pdo->beginTransaction();
        
        // Check if there are any reschedule requests for this schedule
        $checkRequests = $pdo->prepare("SELECT COUNT(*) as count FROM RescheduleRequest WHERE ScheduleID = ?");
        $checkRequests->execute([$scheduleID]);
        $requestCount = $checkRequests->fetch()['count'];
        
        // Check if there are any attendance records for this schedule
        $checkAttendance = $pdo->prepare("SELECT COUNT(*) as count FROM Attendance WHERE ScheduleID = ?");
        $checkAttendance->execute([$scheduleID]);
        $attendanceCount = $checkAttendance->fetch()['count'];
        
        if ($requestCount > 0 || $attendanceCount > 0) {
            // Don't delete, just mark as cancelled
            $updateStmt = $pdo->prepare("UPDATE LabSchedule SET Status = 'Cancelled' WHERE ScheduleID = ? AND CoordinatorID = ?");
            $updateStmt->execute([$scheduleID, $coordinatorID]);
            $message = "<div class='alert alert-warning'><i class='fas fa-exclamation-triangle me-2'></i>Schedule marked as cancelled (has related records).</div>";
        } else {
            // Safe to delete
            $deleteStmt = $pdo->prepare("DELETE FROM LabSchedule WHERE ScheduleID = ? AND CoordinatorID = ?");
            $deleteStmt->execute([$scheduleID, $coordinatorID]);
            $message = "<div class='alert alert-success'><i class='fas fa-check-circle me-2'></i>Schedule deleted successfully!</div>";
        }
        
        $pdo->commit();
        
    } catch (PDOException $e) {
        $pdo->rollback();
        error_log("Delete Schedule Error: " . $e->getMessage());
        $message = "<div class='alert alert-danger'><i class='fas fa-exclamation-triangle me-2'></i>Error deleting schedule: " . htmlspecialchars($e->getMessage()) . "</div>";
    }
}

// Handle schedule update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_schedule'])) {
    $scheduleID = $_POST['schedule_id'];
    $labID = $_POST['lab_id'];
    $instructorID = $_POST['instructor_id'];
    $date = $_POST['date'];
    $timeSlot = $_POST['time_slot'];
    $status = $_POST['status'];
    
    try {
        $updateStmt = $pdo->prepare("
            UPDATE LabSchedule 
            SET LabID = ?, InstructorID = ?, Date = ?, TimeSlot = ?, Status = ? 
            WHERE ScheduleID = ? AND CoordinatorID = ?
        ");
        $updateStmt->execute([$labID, $instructorID, $date, $timeSlot, $status, $scheduleID, $coordinatorID]);
        
        if ($updateStmt->rowCount() > 0) {
            $message = "<div class='alert alert-success'><i class='fas fa-check-circle me-2'></i>Schedule updated successfully!</div>";
        } else {
            $message = "<div class='alert alert-warning'><i class='fas fa-exclamation-triangle me-2'></i>No changes made to the schedule.</div>";
        }
        
    } catch (PDOException $e) {
        error_log("Update Schedule Error: " . $e->getMessage());
        $message = "<div class='alert alert-danger'><i class='fas fa-exclamation-triangle me-2'></i>Error updating schedule: " . htmlspecialchars($e->getMessage()) . "</div>";
    }
}

// Fetch all lab schedules for this coordinator
$schedules = $pdo->prepare("
    SELECT ls.*, l.LabName, l.Location, li.Name as InstructorName,
           (SELECT COUNT(*) FROM RescheduleRequest WHERE ScheduleID = ls.ScheduleID) as RequestCount,
           (SELECT COUNT(*) FROM Attendance WHERE ScheduleID = ls.ScheduleID) as AttendanceCount
    FROM LabSchedule ls
    JOIN Lab l ON ls.LabID = l.LabID
    LEFT JOIN LabInstructor li ON ls.InstructorID = li.InstructorID
    WHERE ls.CoordinatorID = ?
    ORDER BY ls.Date DESC, ls.TimeSlot ASC
");
$schedules->execute([$coordinatorID]);

// Fetch labs and instructors for dropdowns
$labs = $pdo->query("SELECT LabID, LabName FROM Lab ORDER BY LabName ASC");
$instructors = $pdo->query("SELECT InstructorID, Name FROM LabInstructor ORDER BY Name ASC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Lab Schedules - EduPortal</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <style>
        .schedule-card {
            border: 1px solid #e3e6f0;
            border-radius: 10px;
            transition: all 0.3s ease;
            margin-bottom: 20px;
        }
        
        .schedule-card:hover {
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
        
        .schedule-actions {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }
        
        .info-badge {
            font-size: 0.75rem;
            padding: 0.25rem 0.5rem;
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
                <a href="coordinator_dashboard.php" class="nav-link text-white me-3">
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
                            <i class="fas fa-calendar-edit me-3"></i>
                            Manage Lab Schedules
                        </h1>
                        <p class="page-subtitle">View, edit, and delete your lab schedules</p>
                    </div>
                    <div class="col-md-4 text-end">
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addScheduleModal">
                            <i class="fas fa-plus me-2"></i>Add New Schedule
                        </button>
                    </div>
                </div>
            </div>

            <?= $message ?>

            <div class="row">
                <div class="col-12">
                    <?php if ($schedules->rowCount() > 0): ?>
                        <?php while ($schedule = $schedules->fetch()): ?>
                            <div class="schedule-card fade-in">
                                <div class="card-body">
                                    <div class="row align-items-center">
                                        <div class="col-md-3">
                                            <h5 class="mb-1">
                                                <i class="fas fa-flask me-2 text-primary"></i>
                                                <?= htmlspecialchars($schedule['LabName']) ?>
                                            </h5>
                                            <small class="text-muted">
                                                <i class="fas fa-map-marker-alt me-1"></i>
                                                <?= htmlspecialchars($schedule['Location']) ?>
                                            </small>
                                        </div>
                                        
                                        <div class="col-md-2">
                                            <strong>Schedule ID:</strong><br>
                                            <span class="badge bg-secondary"><?= htmlspecialchars($schedule['ScheduleID']) ?></span>
                                        </div>
                                        
                                        <div class="col-md-2">
                                            <strong>Date & Time:</strong><br>
                                            <span><?= date('M d, Y', strtotime($schedule['Date'])) ?></span><br>
                                            <small class="text-muted"><?= htmlspecialchars($schedule['TimeSlot']) ?></small>
                                        </div>
                                        
                                        <div class="col-md-2">
                                            <strong>Instructor:</strong><br>
                                            <span><?= htmlspecialchars($schedule['InstructorName'] ?: 'Not Assigned') ?></span><br>
                                            <?php
                                            $statusClass = match($schedule['Status']) {
                                                'Scheduled' => 'success',
                                                'Completed' => 'primary',
                                                'Cancelled' => 'danger',
                                                default => 'secondary'
                                            };
                                            ?>
                                            <span class="badge bg-<?= $statusClass ?> status-badge"><?= htmlspecialchars($schedule['Status']) ?></span>
                                        </div>
                                        
                                        <div class="col-md-3">
                                            <div class="mb-2">
                                                <?php if ($schedule['RequestCount'] > 0): ?>
                                                    <span class="badge bg-warning info-badge me-1">
                                                        <i class="fas fa-clock me-1"></i><?= $schedule['RequestCount'] ?> Requests
                                                    </span>
                                                <?php endif; ?>
                                                
                                                <?php if ($schedule['AttendanceCount'] > 0): ?>
                                                    <span class="badge bg-info info-badge">
                                                        <i class="fas fa-user-check me-1"></i><?= $schedule['AttendanceCount'] ?> Attendance
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                            
                                            <div class="schedule-actions">
                                                <button class="btn btn-sm btn-outline-primary" 
                                                        onclick="toggleEditForm('<?= $schedule['ScheduleID'] ?>')">
                                                    <i class="fas fa-edit me-1"></i>Edit
                                                </button>
                                                
                                                <button class="btn btn-sm btn-outline-danger" 
                                                        onclick="confirmDelete('<?= $schedule['ScheduleID'] ?>', '<?= htmlspecialchars($schedule['LabName']) ?>', <?= $schedule['RequestCount'] + $schedule['AttendanceCount'] ?>)">
                                                    <i class="fas fa-trash me-1"></i>Delete
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Edit Form (Hidden by default) -->
                                    <div class="edit-form" id="edit-form-<?= $schedule['ScheduleID'] ?>">
                                        <form method="POST">
                                            <input type="hidden" name="schedule_id" value="<?= htmlspecialchars($schedule['ScheduleID']) ?>">
                                            
                                            <div class="row">
                                                <div class="col-md-3 mb-3">
                                                    <label class="form-label">Lab</label>
                                                    <select name="lab_id" class="form-select" required>
                                                        <?php 
                                                        $labs->execute();
                                                        foreach ($labs as $lab): 
                                                        ?>
                                                        <option value="<?= $lab['LabID'] ?>" 
                                                                <?= ($lab['LabID'] == $schedule['LabID']) ? 'selected' : '' ?>>
                                                            <?= htmlspecialchars($lab['LabName']) ?>
                                                        </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                                
                                                <div class="col-md-3 mb-3">
                                                    <label class="form-label">Instructor</label>
                                                    <select name="instructor_id" class="form-select" required>
                                                        <option value="">Choose Instructor</option>
                                                        <?php 
                                                        $instructors->execute();
                                                        foreach ($instructors as $instructor): 
                                                        ?>
                                                        <option value="<?= $instructor['InstructorID'] ?>" 
                                                                <?= ($instructor['InstructorID'] == $schedule['InstructorID']) ? 'selected' : '' ?>>
                                                            <?= htmlspecialchars($instructor['Name']) ?>
                                                        </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                                
                                                <div class="col-md-2 mb-3">
                                                    <label class="form-label">Date</label>
                                                    <input type="date" name="date" class="form-control" 
                                                           value="<?= $schedule['Date'] ?>" required>
                                                </div>
                                                
                                                <div class="col-md-2 mb-3">
                                                    <label class="form-label">Time Slot</label>
                                                    <input type="text" name="time_slot" class="form-control" 
                                                           value="<?= htmlspecialchars($schedule['TimeSlot']) ?>" required>
                                                </div>
                                                
                                                <div class="col-md-2 mb-3">
                                                    <label class="form-label">Status</label>
                                                    <select name="status" class="form-select" required>
                                                        <option value="Scheduled" <?= ($schedule['Status'] == 'Scheduled') ? 'selected' : '' ?>>Scheduled</option>
                                                        <option value="Completed" <?= ($schedule['Status'] == 'Completed') ? 'selected' : '' ?>>Completed</option>
                                                        <option value="Cancelled" <?= ($schedule['Status'] == 'Cancelled') ? 'selected' : '' ?>>Cancelled</option>
                                                    </select>
                                                </div>
                                            </div>
                                            
                                            <div class="d-flex gap-2">
                                                <button type="submit" name="update_schedule" class="btn btn-success">
                                                    <i class="fas fa-save me-2"></i>Save Changes
                                                </button>
                                                <button type="button" class="btn btn-secondary" 
                                                        onclick="toggleEditForm('<?= $schedule['ScheduleID'] ?>')">
                                                    <i class="fas fa-times me-2"></i>Cancel
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="card fade-in">
                            <div class="card-body text-center py-5">
                                <i class="fas fa-calendar-times fa-3x text-muted mb-3"></i>
                                <h5>No Lab Schedules Found</h5>
                                <p class="text-muted">You haven't created any lab schedules yet.</p>
                                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addScheduleModal">
                                    <i class="fas fa-plus me-2"></i>Create Your First Schedule
                                </button>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>

    <!-- Add Schedule Modal -->
    <div class="modal fade" id="addScheduleModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Lab Schedule</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="coordinator_dashboard.php">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Schedule ID</label>
                                <input type="text" name="scheduleID" class="form-control" required placeholder="e.g., SCH01">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Select Lab</label>
                                <select name="labID" class="form-select" required>
                                    <option value="">Choose Lab</option>
                                    <?php 
                                    $labs->execute();
                                    foreach ($labs as $lab): 
                                    ?>
                                    <option value="<?= $lab['LabID'] ?>"><?= htmlspecialchars($lab['LabName']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Select Instructor</label>
                                <select name="instructorID" class="form-select" required>
                                    <option value="">Choose Instructor</option>
                                    <?php 
                                    $instructors->execute();
                                    foreach ($instructors as $instructor): 
                                    ?>
                                    <option value="<?= $instructor['InstructorID'] ?>"><?= htmlspecialchars($instructor['Name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Date</label>
                                <input type="date" name="date" class="form-control" required>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Time Slot</label>
                                <input type="text" name="timeSlot" class="form-control" required placeholder="e.g., 9:00 AM - 11:00 AM">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Status</label>
                                <select name="status" class="form-select" required>
                                    <option value="Scheduled">Scheduled</option>
                                    <option value="Cancelled">Cancelled</option>
                                    <option value="Completed">Completed</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="add_lab" class="btn btn-primary">Add Schedule</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

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
                    <p id="deleteMessage">Are you sure you want to delete this schedule?</p>
                    <div class="alert alert-warning">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Note:</strong> If this schedule has related records (attendance or reschedule requests), 
                        it will be marked as "Cancelled" instead of being deleted.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <form method="POST" style="display: inline;">
                        <input type="hidden" name="schedule_id" id="deleteScheduleId">
                        <button type="submit" name="delete_schedule" class="btn btn-danger">
                            <i class="fas fa-trash me-2"></i>Delete Schedule
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
    function toggleEditForm(scheduleId) {
        const editForm = document.getElementById('edit-form-' + scheduleId);
        if (editForm.style.display === 'none' || editForm.style.display === '') {
            editForm.style.display = 'block';
        } else {
            editForm.style.display = 'none';
        }
    }
    
    // Confirm delete with details
    function confirmDelete(scheduleId, labName, relatedRecords) {
        const deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
        const deleteMessage = document.getElementById('deleteMessage');
        const deleteScheduleId = document.getElementById('deleteScheduleId');
        
        if (relatedRecords > 0) {
            deleteMessage.innerHTML = `
                <strong>Schedule:</strong> ${labName}<br>
                <strong>Schedule ID:</strong> ${scheduleId}<br><br>
                This schedule has ${relatedRecords} related record(s). It will be marked as "Cancelled" instead of being deleted.
            `;
        } else {
            deleteMessage.innerHTML = `
                <strong>Schedule:</strong> ${labName}<br>
                <strong>Schedule ID:</strong> ${scheduleId}<br><br>
                This schedule will be permanently deleted from the database.
            `;
        }
        
        deleteScheduleId.value = scheduleId;
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
