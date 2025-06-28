<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'instructor') {
    header("Location: ../index.php");
    exit;
}

require '../includes/db_connect.php';

// Fetch instructor details
$instructorID = $_SESSION['ref_id'];
$stmt = $pdo->prepare("SELECT * FROM LabInstructor WHERE InstructorID = ?");
$stmt->execute([$instructorID]);
$instructor = $stmt->fetch();

if (!$instructor) {
    die("Error: Could not find instructor details for ID: " . htmlspecialchars($instructorID));
}

$message = '';
$students = [];
$selectedSchedule = null;

// FIXED: Handle attendance submission with proper unique ID generation
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_attendance'])) {
    $scheduleID = $_POST['schedule_id'];
    $date = $_POST['attendance_date'];
    $attendanceData = $_POST['attendance'] ?? [];
    
    try {
        $pdo->beginTransaction();
        
        // First, delete existing attendance for this schedule and date
        $deleteStmt = $pdo->prepare("DELETE FROM Attendance WHERE ScheduleID = ? AND Date = ?");
        $deleteStmt->execute([$scheduleID, $date]);
        
        // FIXED: Get the highest existing AttendanceID to ensure uniqueness
        $maxIdStmt = $pdo->prepare("SELECT AttendanceID FROM Attendance ORDER BY AttendanceID DESC LIMIT 1");
        $maxIdStmt->execute();
        $maxIdResult = $maxIdStmt->fetch();
        
        // Extract the numeric part and increment
        $nextNumber = 1;
        if ($maxIdResult && $maxIdResult['AttendanceID']) {
            // Extract number from AttendanceID like 'A001', 'A002', etc.
            preg_match('/A(\d+)/', $maxIdResult['AttendanceID'], $matches);
            if (isset($matches[1])) {
                $nextNumber = intval($matches[1]) + 1;
            }
        }
        
        $attendanceCount = 0;
        
        // Insert new attendance records with guaranteed unique IDs
        foreach ($attendanceData as $studentID => $present) {
            // FIXED: Generate truly unique AttendanceID
            $attendanceID = 'A' . str_pad($nextNumber + $attendanceCount, 3, '0', STR_PAD_LEFT);
            
            $insertStmt = $pdo->prepare("INSERT INTO Attendance (AttendanceID, StudentID, ScheduleID, Date, Present) VALUES (?, ?, ?, ?, ?)");
            $insertStmt->execute([$attendanceID, $studentID, $scheduleID, $date, $present]);
            $attendanceCount++;
        }
        
        $pdo->commit();
        $message = "<div class='alert alert-success'><i class='fas fa-check-circle me-2'></i>Attendance marked successfully for " . count($attendanceData) . " students!</div>";
        
    } catch (PDOException $e) {
        $pdo->rollback();
        error_log("Attendance Error: " . $e->getMessage());
        $message = "<div class='alert alert-danger'><i class='fas fa-exclamation-triangle me-2'></i>Error marking attendance: " . htmlspecialchars($e->getMessage()) . "</div>";
    }
}

// Fetch students for selected schedule
if (isset($_POST['get_students']) && !empty($_POST['schedule_id'])) {
    $scheduleID = $_POST['schedule_id'];
    $date = $_POST['attendance_date'];
    
    // Get schedule details
    $scheduleStmt = $pdo->prepare("
        SELECT ls.*, l.LabName, l.Location 
        FROM LabSchedule ls 
        JOIN Lab l ON ls.LabID = l.LabID 
        WHERE ls.ScheduleID = ? AND ls.InstructorID = ?
    ");
    $scheduleStmt->execute([$scheduleID, $instructorID]);
    $selectedSchedule = $scheduleStmt->fetch();
    
    if ($selectedSchedule) {
        // Get all students (you can modify this query based on your enrollment system)
        $studentsStmt = $pdo->prepare("
            SELECT s.StudentID, s.Name, s.Email,
                   COALESCE(a.Present, 0) as Present
            FROM Student s
            LEFT JOIN Attendance a ON s.StudentID = a.StudentID 
                AND a.ScheduleID = ? AND a.Date = ?
            ORDER BY s.Name ASC
        ");
        $studentsStmt->execute([$scheduleID, $date]);
        $students = $studentsStmt->fetchAll();
    }
}

// Fetch lab schedules assigned to this instructor
$schedules = $pdo->prepare("
    SELECT ls.*, l.LabName, l.Location 
    FROM LabSchedule ls 
    JOIN Lab l ON ls.LabID = l.LabID 
    WHERE ls.InstructorID = ? AND ls.Status = 'Scheduled'
    ORDER BY ls.Date DESC, ls.TimeSlot ASC
");
$schedules->execute([$instructorID]);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mark Attendance - EduPortal</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <style>
        .attendance-card {
            border: 1px solid #e3e6f0;
            border-radius: 10px;
            transition: all 0.3s ease;
        }
        
        .attendance-card:hover {
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        
        .student-row {
            padding: 15px;
            border-bottom: 1px solid #f1f3f4;
            transition: background-color 0.2s ease;
        }
        
        .student-row:hover {
            background-color: #f8f9fa;
        }
        
        .student-row:last-child {
            border-bottom: none;
        }
        
        .attendance-toggle {
            transform: scale(1.2);
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
                <a href="instructor_dashboard.php" class="nav-link text-white me-3">
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
                <h1 class="page-title">
                    <i class="fas fa-user-check me-3"></i>
                    Mark Lab Attendance
                </h1>
                <p class="page-subtitle">Select lab schedule and mark student attendance</p>
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
                                
                                <button type="submit" name="get_students" class="btn btn-primary w-100">
                                    <i class="fas fa-users me-2"></i>Get Students
                                </button>
                            </form>
                        </div>
                    </div>

                    <!-- Attendance Summary -->
                    <?php if (!empty($students)): ?>
                    <div class="card mt-4 fade-in">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-chart-bar me-2"></i>
                                Attendance Summary
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row text-center">
                                <div class="col-6">
                                    <div class="p-3 bg-success text-white rounded">
                                        <i class="fas fa-user-check fa-2x mb-2"></i>
                                        <h4 id="present-count">0</h4>
                                        <small>Present</small>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="p-3 bg-danger text-white rounded">
                                        <i class="fas fa-user-times fa-2x mb-2"></i>
                                        <h4 id="absent-count"><?= count($students) ?></h4>
                                        <small>Absent</small>
                                    </div>
                                </div>
                            </div>
                            <div class="mt-3">
                                <small class="text-muted">Total Students: <?= count($students) ?></small>
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

                    <?php if (!empty($students)): ?>
                    <!-- Attendance Form -->
                    <div class="card fade-in">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">
                                <i class="fas fa-list-check me-2"></i>
                                Student Attendance (<?= count($students) ?> students)
                            </h5>
                            <div class="btn-group" role="group">
                                <button type="button" class="btn btn-sm btn-success" onclick="markAllPresent()">
                                    <i class="fas fa-check-double me-1"></i>All Present
                                </button>
                                <button type="button" class="btn btn-sm btn-danger" onclick="markAllAbsent()">
                                    <i class="fas fa-times me-1"></i>All Absent
                                </button>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <form method="POST" id="attendanceForm">
                                <input type="hidden" name="schedule_id" value="<?= htmlspecialchars($_POST['schedule_id']) ?>">
                                <input type="hidden" name="attendance_date" value="<?= htmlspecialchars($_POST['attendance_date']) ?>">
                                
                                <?php foreach ($students as $index => $student): ?>
                                <div class="student-row">
                                    <div class="row align-items-center">
                                        <div class="col-md-6">
                                            <div class="d-flex align-items-center">
                                                <div class="me-3">
                                                    <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" 
                                                         style="width: 40px; height: 40px;">
                                                        <?= strtoupper(substr($student['Name'], 0, 1)) ?>
                                                    </div>
                                                </div>
                                                <div>
                                                    <h6 class="mb-1"><?= htmlspecialchars($student['Name']) ?></h6>
                                                    <small class="text-muted">
                                                        <i class="fas fa-id-card me-1"></i>
                                                        <?= htmlspecialchars($student['StudentID']) ?>
                                                    </small>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <small class="text-muted">
                                                <i class="fas fa-envelope me-1"></i>
                                                <?= htmlspecialchars($student['Email']) ?>
                                            </small>
                                        </div>
                                        <div class="col-md-3 text-end">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input attendance-toggle" 
                                                       type="checkbox" 
                                                       name="attendance[<?= $student['StudentID'] ?>]" 
                                                       value="1"
                                                       id="attendance_<?= $student['StudentID'] ?>"
                                                       <?= $student['Present'] ? 'checked' : '' ?>
                                                       onchange="updateAttendanceCount()">
                                                <label class="form-check-label fw-bold" for="attendance_<?= $student['StudentID'] ?>">
                                                    <span class="present-text text-success" style="display: <?= $student['Present'] ? 'inline' : 'none' ?>;">
                                                        <i class="fas fa-check me-1"></i>Present
                                                    </span>
                                                    <span class="absent-text text-danger" style="display: <?= $student['Present'] ? 'none' : 'inline' ?>;">
                                                        <i class="fas fa-times me-1"></i>Absent
                                                    </span>
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                                
                                <div class="p-3 bg-light">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <small class="text-muted">
                                                <i class="fas fa-info-circle me-1"></i>
                                                Click the toggle switches to mark attendance
                                            </small>
                                        </div>
                                        <button type="submit" name="submit_attendance" class="btn btn-success">
                                            <i class="fas fa-save me-2"></i>Save Attendance
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                    <?php elseif (isset($_POST['get_students'])): ?>
                    <div class="card fade-in">
                        <div class="card-body text-center py-5">
                            <i class="fas fa-users fa-3x text-muted mb-3"></i>
                            <h5>No Students Found</h5>
                            <p class="text-muted">No students are enrolled for the selected lab schedule.</p>
                        </div>
                    </div>
                    <?php else: ?>
                    <div class="card fade-in">
                        <div class="card-body text-center py-5">
                            <i class="fas fa-calendar-alt fa-3x text-muted mb-3"></i>
                            <h5>Select Lab Schedule</h5>
                            <p class="text-muted">Please select a lab schedule and date to view students and mark attendance.</p>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>

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
    // Update attendance count and toggle text
    function updateAttendanceCount() {
        const checkboxes = document.querySelectorAll('.attendance-toggle');
        let presentCount = 0;
        
        checkboxes.forEach(checkbox => {
            const row = checkbox.closest('.student-row');
            const presentText = row.querySelector('.present-text');
            const absentText = row.querySelector('.absent-text');
            
            if (checkbox.checked) {
                presentCount++;
                presentText.style.display = 'inline';
                absentText.style.display = 'none';
            } else {
                presentText.style.display = 'none';
                absentText.style.display = 'inline';
            }
        });
        
        const totalStudents = checkboxes.length;
        const absentCount = totalStudents - presentCount;
        
        document.getElementById('present-count').textContent = presentCount;
        document.getElementById('absent-count').textContent = absentCount;
    }
    
    // Mark all students present
    function markAllPresent() {
        const checkboxes = document.querySelectorAll('.attendance-toggle');
        checkboxes.forEach(checkbox => {
            checkbox.checked = true;
        });
        updateAttendanceCount();
    }
    
    // Mark all students absent
    function markAllAbsent() {
        const checkboxes = document.querySelectorAll('.attendance-toggle');
        checkboxes.forEach(checkbox => {
            checkbox.checked = false;
        });
        updateAttendanceCount();
    }
    
    // Initialize attendance count on page load
    document.addEventListener('DOMContentLoaded', function() {
        updateAttendanceCount();
        
        // Auto-hide alerts
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
    
    // Form validation
    document.getElementById('attendanceForm')?.addEventListener('submit', function(e) {
        const checkboxes = document.querySelectorAll('.attendance-toggle');
        if (checkboxes.length === 0) {
            e.preventDefault();
            alert('No students found to mark attendance.');
            return false;
        }
        
        const confirmed = confirm('Are you sure you want to save the attendance? This will overwrite any existing attendance for this date.');
        if (!confirmed) {
            e.preventDefault();
            return false;
        }
    });
    </script>
</body>
</html>
