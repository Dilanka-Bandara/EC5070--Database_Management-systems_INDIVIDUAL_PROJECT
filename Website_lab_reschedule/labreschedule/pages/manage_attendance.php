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
$attendanceRecords = [];
$selectedSchedule = null;

// Helper function to generate unique AttendanceID
function generateUniqueAttendanceID($pdo) {
    $maxAttempts = 10;
    
    for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
        // Get the highest existing AttendanceID
        $maxIdStmt = $pdo->prepare("SELECT AttendanceID FROM Attendance ORDER BY AttendanceID DESC LIMIT 1");
        $maxIdStmt->execute();
        $maxIdResult = $maxIdStmt->fetch();
        
        $nextNumber = 1;
        if ($maxIdResult && $maxIdResult['AttendanceID']) {
            preg_match('/A(\d+)/', $maxIdResult['AttendanceID'], $matches);
            if (isset($matches[1])) {
                $nextNumber = intval($matches[1]) + 1;
            }
        }
        
        $attendanceID = 'A' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);
        
        // Check if this ID already exists
        $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM Attendance WHERE AttendanceID = ?");
        $checkStmt->execute([$attendanceID]);
        
        if ($checkStmt->fetchColumn() == 0) {
            return $attendanceID;
        }
    }
    
    // Fallback
    return 'A' . time() . rand(100, 999);
}

// Handle attendance deletion
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_attendance'])) {
    $attendanceID = $_POST['attendance_id'];
    
    try {
        $pdo->beginTransaction();
        
        // Get attendance details before deletion
        $getAttendanceStmt = $pdo->prepare("
            SELECT a.*, s.Name as StudentName, l.LabName, ls.Date as ScheduleDate, ls.TimeSlot
            FROM Attendance a
            JOIN Student s ON a.StudentID = s.StudentID
            JOIN LabSchedule ls ON a.ScheduleID = ls.ScheduleID
            JOIN Lab l ON ls.LabID = l.LabID
            WHERE a.AttendanceID = ? AND ls.CoordinatorID = ?
        ");
        $getAttendanceStmt->execute([$attendanceID, $coordinatorID]);
        $attendanceDetails = $getAttendanceStmt->fetch();
        
        if ($attendanceDetails) {
            // Delete the attendance record
            $deleteStmt = $pdo->prepare("DELETE FROM Attendance WHERE AttendanceID = ?");
            $deleteStmt->execute([$attendanceID]);
            
            $pdo->commit();
            $message = "<div class='alert alert-success'><i class='fas fa-check-circle me-2'></i>Attendance record deleted successfully!</div>";
        } else {
            $message = "<div class='alert alert-danger'><i class='fas fa-exclamation-triangle me-2'></i>Attendance record not found or access denied.</div>";
        }
        
    } catch (PDOException $e) {
        $pdo->rollback();
        error_log("Delete Attendance Error: " . $e->getMessage());
        $message = "<div class='alert alert-danger'><i class='fas fa-exclamation-triangle me-2'></i>Error deleting attendance: " . htmlspecialchars($e->getMessage()) . "</div>";
    }
}

// Handle attendance update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_attendance'])) {
    $attendanceID = $_POST['attendance_id'];
    $present = isset($_POST['present']) ? 1 : 0;
    $date = $_POST['date'];
    
    try {
        $pdo->beginTransaction();
        
        // Verify the attendance belongs to coordinator's schedule
        $verifyStmt = $pdo->prepare("
            SELECT a.* FROM Attendance a
            JOIN LabSchedule ls ON a.ScheduleID = ls.ScheduleID
            WHERE a.AttendanceID = ? AND ls.CoordinatorID = ?
        ");
        $verifyStmt->execute([$attendanceID, $coordinatorID]);
        
        if ($verifyStmt->fetch()) {
            // Update the attendance record
            $updateStmt = $pdo->prepare("UPDATE Attendance SET Present = ?, Date = ? WHERE AttendanceID = ?");
            $updateStmt->execute([$present, $date, $attendanceID]);
            
            $pdo->commit();
            $message = "<div class='alert alert-success'><i class='fas fa-check-circle me-2'></i>Attendance updated successfully!</div>";
        } else {
            $message = "<div class='alert alert-danger'><i class='fas fa-exclamation-triangle me-2'></i>Attendance record not found or access denied.</div>";
        }
        
    } catch (PDOException $e) {
        $pdo->rollback();
        error_log("Update Attendance Error: " . $e->getMessage());
        $message = "<div class='alert alert-danger'><i class='fas fa-exclamation-triangle me-2'></i>Error updating attendance: " . htmlspecialchars($e->getMessage()) . "</div>";
    }
}

// Handle adding new attendance record
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_attendance'])) {
    $studentID = $_POST['student_id'];
    $scheduleID = $_POST['schedule_id'];
    $present = isset($_POST['present']) ? 1 : 0;
    $date = $_POST['date'];
    
    try {
        $pdo->beginTransaction();
        
        // Check if attendance already exists for this student, schedule, and date
        $checkExistingStmt = $pdo->prepare("SELECT COUNT(*) FROM Attendance WHERE StudentID = ? AND ScheduleID = ? AND Date = ?");
        $checkExistingStmt->execute([$studentID, $scheduleID, $date]);
        
        if ($checkExistingStmt->fetchColumn() == 0) {
            // Generate unique AttendanceID
            $attendanceID = generateUniqueAttendanceID($pdo);
            
            // Insert new attendance record
            $insertStmt = $pdo->prepare("INSERT INTO Attendance (AttendanceID, StudentID, ScheduleID, Date, Present) VALUES (?, ?, ?, ?, ?)");
            $insertStmt->execute([$attendanceID, $studentID, $scheduleID, $date, $present]);
            
            $pdo->commit();
            $message = "<div class='alert alert-success'><i class='fas fa-check-circle me-2'></i>Attendance record added successfully!</div>";
        } else {
            $message = "<div class='alert alert-warning'><i class='fas fa-exclamation-triangle me-2'></i>Attendance record already exists for this student on this date.</div>";
        }
        
    } catch (PDOException $e) {
        $pdo->rollback();
        error_log("Add Attendance Error: " . $e->getMessage());
        $message = "<div class='alert alert-danger'><i class='fas fa-exclamation-triangle me-2'></i>Error adding attendance: " . htmlspecialchars($e->getMessage()) . "</div>";
    }
}

// Fetch attendance records for selected schedule
if (isset($_POST['get_attendance']) && !empty($_POST['schedule_id'])) {
    $scheduleID = $_POST['schedule_id'];
    $date = $_POST['attendance_date'];
    
    // Get schedule details
    $scheduleStmt = $pdo->prepare("
        SELECT ls.*, l.LabName, l.Location 
        FROM LabSchedule ls 
        JOIN Lab l ON ls.LabID = l.LabID 
        WHERE ls.ScheduleID = ? AND ls.CoordinatorID = ?
    ");
    $scheduleStmt->execute([$scheduleID, $coordinatorID]);
    $selectedSchedule = $scheduleStmt->fetch();
    
    if ($selectedSchedule) {
        // Get attendance records for this schedule and date
        $attendanceStmt = $pdo->prepare("
            SELECT a.*, s.Name as StudentName, s.Email as StudentEmail
            FROM Attendance a
            JOIN Student s ON a.StudentID = s.StudentID
            WHERE a.ScheduleID = ? AND a.Date = ?
            ORDER BY s.Name ASC
        ");
        $attendanceStmt->execute([$scheduleID, $date]);
        $attendanceRecords = $attendanceStmt->fetchAll();
    }
}

// Fetch lab schedules assigned to this coordinator
$schedules = $pdo->prepare("
    SELECT ls.*, l.LabName, l.Location 
    FROM LabSchedule ls 
    JOIN Lab l ON ls.LabID = l.LabID 
    WHERE ls.CoordinatorID = ?
    ORDER BY ls.Date DESC, ls.TimeSlot ASC
");
$schedules->execute([$coordinatorID]);

// Fetch all students for adding new attendance
$students = $pdo->query("SELECT StudentID, Name FROM Student ORDER BY Name ASC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Attendance - EduPortal</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <style>
        .attendance-card {
            border: 1px solid #e3e6f0;
            border-radius: 10px;
            transition: all 0.3s ease;
            margin-bottom: 15px;
        }
        
        .attendance-card:hover {
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        
        .present-card {
            border-left: 4px solid #28a745;
        }
        
        .absent-card {
            border-left: 4px solid #dc3545;
        }
        
        .edit-form {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
            margin-top: 10px;
            display: none;
        }
        
        .schedule-info {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
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
                            <i class="fas fa-user-check me-3"></i>
                            Manage Student Attendance
                        </h1>
                        <p class="page-subtitle">View, edit, and delete attendance records</p>
                    </div>
                    <div class="col-md-4 text-end">
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addAttendanceModal">
                            <i class="fas fa-plus me-2"></i>Add Attendance
                        </button>
                    </div>
                </div>
            </div>

            <?= $message ?>

            <div class="row">
                <div class="col-lg-4">
                    <!-- Schedule Selection -->
                    <div class="card fade-in">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-calendar-alt me-2"></i>
                                Select Lab Schedule
                            </h5>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <div class="mb-3">
                                    <label class="form-label">Lab Schedule</label>
                                    <select name="schedule_id" class="form-select" required>
                                        <option value="">Choose Lab Schedule</option>
                                        <?php while ($schedule = $schedules->fetch()): ?>
                                            <option value="<?= $schedule['ScheduleID'] ?>" 
                                                    <?= (isset($_POST['schedule_id']) && $_POST['schedule_id'] == $schedule['ScheduleID']) ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($schedule['LabName']) ?> - 
                                                <?= date('M d, Y', strtotime($schedule['Date'])) ?> 
                                                (<?= htmlspecialchars($schedule['TimeSlot']) ?>)
                                            </option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Attendance Date</label>
                                    <input type="date" name="attendance_date" class="form-control" 
                                           value="<?= $_POST['attendance_date'] ?? date('Y-m-d') ?>" required>
                                </div>
                                
                                <button type="submit" name="get_attendance" class="btn btn-primary w-100">
                                    <i class="fas fa-search me-2"></i>Get Attendance Records
                                </button>
                            </form>
                        </div>
                    </div>

                    <!-- Attendance Summary -->
                    <?php if (!empty($attendanceRecords)): ?>
                    <div class="card mt-4 fade-in">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-chart-bar me-2"></i>
                                Attendance Summary
                            </h5>
                        </div>
                        <div class="card-body">
                            <?php
                            $presentCount = array_sum(array_column($attendanceRecords, 'Present'));
                            $totalCount = count($attendanceRecords);
                            $absentCount = $totalCount - $presentCount;
                            $percentage = $totalCount > 0 ? round(($presentCount / $totalCount) * 100, 1) : 0;
                            ?>
                            <div class="row text-center">
                                <div class="col-6">
                                    <div class="p-3 bg-success text-white rounded">
                                        <i class="fas fa-user-check fa-2x mb-2"></i>
                                        <h4><?= $presentCount ?></h4>
                                        <small>Present</small>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="p-3 bg-danger text-white rounded">
                                        <i class="fas fa-user-times fa-2x mb-2"></i>
                                        <h4><?= $absentCount ?></h4>
                                        <small>Absent</small>
                                    </div>
                                </div>
                            </div>
                            <div class="mt-3 text-center">
                                <div class="progress">
                                    <div class="progress-bar bg-success" style="width: <?= $percentage ?>%"></div>
                                </div>
                                <small class="text-muted">Attendance Rate: <?= $percentage ?>%</small>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>

                <div class="col-lg-8">
                    <?php if ($selectedSchedule): ?>
                    <!-- Schedule Information -->
                    <div class="schedule-info fade-in">
                        <div class="row align-items-center">
                            <div class="col-md-8">
                                <h4><i class="fas fa-flask me-2"></i><?= htmlspecialchars($selectedSchedule['LabName']) ?></h4>
                                <p class="mb-1">
                                    <i class="fas fa-map-marker-alt me-2"></i>
                                    <?= htmlspecialchars($selectedSchedule['Location']) ?>
                                </p>
                                <p class="mb-0">
                                    <i class="fas fa-calendar me-2"></i>
                                    <?= date('l, M d, Y', strtotime($selectedSchedule['Date'])) ?> - 
                                    <?= htmlspecialchars($selectedSchedule['TimeSlot']) ?>
                                </p>
                            </div>
                            <div class="col-md-4 text-end">
                                <div class="badge bg-light text-dark p-2">
                                    <i class="fas fa-id-badge me-1"></i>
                                    <?= htmlspecialchars($selectedSchedule['ScheduleID']) ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if (!empty($attendanceRecords)): ?>
                    <!-- Attendance Records -->
                    <div class="card fade-in">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-list-check me-2"></i>
                                Attendance Records (<?= count($attendanceRecords) ?> students)
                            </h5>
                        </div>
                        <div class="card-body p-0">
                            <?php foreach ($attendanceRecords as $record): ?>
                                <div class="attendance-card <?= $record['Present'] ? 'present-card' : 'absent-card' ?>">
                                    <div class="card-body p-3">
                                        <div class="row align-items-center">
                                            <div class="col-md-4">
                                                <div class="d-flex align-items-center">
                                                    <div class="me-3">
                                                        <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" 
                                                             style="width: 40px; height: 40px;">
                                                            <?= strtoupper(substr($record['StudentName'], 0, 1)) ?>
                                                        </div>
                                                    </div>
                                                    <div>
                                                        <h6 class="mb-1"><?= htmlspecialchars($record['StudentName']) ?></h6>
                                                        <small class="text-muted">
                                                            <i class="fas fa-id-card me-1"></i>
                                                            <?= htmlspecialchars($record['StudentID']) ?>
                                                        </small>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <div class="col-md-3">
                                                <small class="text-muted">
                                                    <i class="fas fa-envelope me-1"></i>
                                                    <?= htmlspecialchars($record['StudentEmail']) ?>
                                                </small>
                                            </div>
                                            
                                            <div class="col-md-2">
                                                <span class="badge bg-<?= $record['Present'] ? 'success' : 'danger' ?> p-2">
                                                    <i class="fas fa-<?= $record['Present'] ? 'check' : 'times' ?> me-1"></i>
                                                    <?= $record['Present'] ? 'Present' : 'Absent' ?>
                                                </span>
                                            </div>
                                            
                                            <div class="col-md-3 text-end">
                                                <div class="btn-group" role="group">
                                                    <button class="btn btn-sm btn-outline-primary" 
                                                            onclick="toggleEditForm('<?= $record['AttendanceID'] ?>')">
                                                        <i class="fas fa-edit me-1"></i>Edit
                                                    </button>
                                                    <button class="btn btn-sm btn-outline-danger" 
                                                            onclick="confirmDelete('<?= $record['AttendanceID'] ?>', '<?= htmlspecialchars($record['StudentName']) ?>')">
                                                        <i class="fas fa-trash me-1"></i>Delete
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- Edit Form (Hidden by default) -->
                                        <div class="edit-form" id="edit-form-<?= $record['AttendanceID'] ?>">
                                            <form method="POST">
                                                <input type="hidden" name="attendance_id" value="<?= htmlspecialchars($record['AttendanceID']) ?>">
                                                
                                                <div class="row">
                                                    <div class="col-md-6 mb-3">
                                                        <label class="form-label">Date</label>
                                                        <input type="date" name="date" class="form-control" 
                                                               value="<?= $record['Date'] ?>" required>
                                                    </div>
                                                    <div class="col-md-6 mb-3">
                                                        <label class="form-label">Attendance Status</label>
                                                        <div class="form-check form-switch">
                                                            <input class="form-check-input" type="checkbox" name="present" 
                                                                   id="present_<?= $record['AttendanceID'] ?>" 
                                                                   <?= $record['Present'] ? 'checked' : '' ?>>
                                                            <label class="form-check-label" for="present_<?= $record['AttendanceID'] ?>">
                                                                Present
                                                            </label>
                                                        </div>
                                                    </div>
                                                </div>
                                                
                                                <div class="d-flex gap-2">
                                                    <button type="submit" name="update_attendance" class="btn btn-success btn-sm">
                                                        <i class="fas fa-save me-2"></i>Save Changes
                                                    </button>
                                                    <button type="button" class="btn btn-secondary btn-sm" 
                                                            onclick="toggleEditForm('<?= $record['AttendanceID'] ?>')">
                                                        <i class="fas fa-times me-2"></i>Cancel
                                                    </button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php elseif (isset($_POST['get_attendance'])): ?>
                    <div class="card fade-in">
                        <div class="card-body text-center py-5">
                            <i class="fas fa-users fa-3x text-muted mb-3"></i>
                            <h5>No Attendance Records Found</h5>
                            <p class="text-muted">No attendance records found for the selected schedule and date.</p>
                            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addAttendanceModal">
                                <i class="fas fa-plus me-2"></i>Add Attendance Record
                            </button>
                        </div>
                    </div>
                    <?php else: ?>
                    <div class="card fade-in">
                        <div class="card-body text-center py-5">
                            <i class="fas fa-calendar-alt fa-3x text-muted mb-3"></i>
                            <h5>Select Lab Schedule</h5>
                            <p class="text-muted">Please select a lab schedule and date to view attendance records.</p>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>

    <!-- Add Attendance Modal -->
    <div class="modal fade" id="addAttendanceModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add Attendance Record</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Student</label>
                            <select name="student_id" class="form-select" required>
                                <option value="">Choose Student</option>
                                <?php 
                                $students->execute();
                                foreach ($students as $student): 
                                ?>
                                <option value="<?= $student['StudentID'] ?>"><?= htmlspecialchars($student['Name']) ?> (<?= $student['StudentID'] ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Lab Schedule</label>
                            <select name="schedule_id" class="form-select" required>
                                <option value="">Choose Schedule</option>
                                <?php 
                                $schedules->execute();
                                foreach ($schedules as $schedule): 
                                ?>
                                <option value="<?= $schedule['ScheduleID'] ?>">
                                    <?= htmlspecialchars($schedule['LabName']) ?> - <?= date('M d, Y', strtotime($schedule['Date'])) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Date</label>
                            <input type="date" name="date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="present" id="present_new" checked>
                                <label class="form-check-label" for="present_new">
                                    Mark as Present
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="add_attendance" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Add Attendance
                        </button>
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
                    <p id="deleteMessage">Are you sure you want to delete this attendance record?</p>
                    <div class="alert alert-warning">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Note:</strong> This action cannot be undone.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <form method="POST" style="display: inline;">
                        <input type="hidden" name="attendance_id" id="deleteAttendanceId">
                        <button type="submit" name="delete_attendance" class="btn btn-danger">
                            <i class="fas fa-trash me-2"></i>Delete Record
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
    function toggleEditForm(attendanceId) {
        const editForm = document.getElementById('edit-form-' + attendanceId);
        if (editForm.style.display === 'none' || editForm.style.display === '') {
            editForm.style.display = 'block';
        } else {
            editForm.style.display = 'none';
        }
    }
    
    // Confirm delete with details
    function confirmDelete(attendanceId, studentName) {
        const deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
        const deleteMessage = document.getElementById('deleteMessage');
        const deleteAttendanceId = document.getElementById('deleteAttendanceId');
        
        deleteMessage.innerHTML = `
            <strong>Student:</strong> ${studentName}<br>
            <strong>Attendance ID:</strong> ${attendanceId}<br><br>
            Are you sure you want to delete this attendance record?
        `;
        
        deleteAttendanceId.value = attendanceId;
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
