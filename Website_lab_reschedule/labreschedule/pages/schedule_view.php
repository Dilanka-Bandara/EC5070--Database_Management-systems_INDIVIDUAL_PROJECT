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

// Helper function to safely get array values
function safeGet($array, $key, $default = '') {
    return isset($array[$key]) ? $array[$key] : $default;
}

// Handle filter form submission
$filterDate = $_GET['filter_date'] ?? '';
$filterStatus = $_GET['filter_status'] ?? '';
$filterLab = $_GET['filter_lab'] ?? '';

// Build the query with filters
$whereConditions = ["ls.InstructorID = ?"];
$queryParams = [$instructorID];

if (!empty($filterDate)) {
    $whereConditions[] = "ls.Date = ?";
    $queryParams[] = $filterDate;
}

if (!empty($filterStatus)) {
    $whereConditions[] = "ls.Status = ?";
    $queryParams[] = $filterStatus;
}

if (!empty($filterLab)) {
    $whereConditions[] = "ls.LabID = ?";
    $queryParams[] = $filterLab;
}

$whereClause = implode(' AND ', $whereConditions);

// Fetch lab schedules with filters
$schedulesQuery = "
    SELECT ls.*, l.LabName, l.Location, l.Capacity, sc.Name as CoordinatorName,
           (SELECT COUNT(*) FROM Attendance WHERE ScheduleID = ls.ScheduleID) as AttendanceCount,
           (SELECT COUNT(*) FROM RescheduleRequest WHERE ScheduleID = ls.ScheduleID) as RequestCount,
           (SELECT COUNT(*) FROM RescheduleRequest WHERE ScheduleID = ls.ScheduleID AND Status = 'Pending') as PendingRequests
    FROM LabSchedule ls
    JOIN Lab l ON ls.LabID = l.LabID
    LEFT JOIN SubjectCoordinator sc ON ls.CoordinatorID = sc.CoordinatorID
    WHERE $whereClause
    ORDER BY ls.Date DESC, ls.TimeSlot ASC
";

$schedules = $pdo->prepare($schedulesQuery);
$schedules->execute($queryParams);

// Fetch statistics
$stats = [];

// Total schedules
$totalStmt = $pdo->prepare("SELECT COUNT(*) as total FROM LabSchedule WHERE InstructorID = ?");
$totalStmt->execute([$instructorID]);
$stats['total_schedules'] = $totalStmt->fetch()['total'];

// Upcoming schedules
$upcomingStmt = $pdo->prepare("SELECT COUNT(*) as upcoming FROM LabSchedule WHERE InstructorID = ? AND Date >= CURDATE() AND Status = 'Scheduled'");
$upcomingStmt->execute([$instructorID]);
$stats['upcoming_schedules'] = $upcomingStmt->fetch()['upcoming'];

// Completed schedules
$completedStmt = $pdo->prepare("SELECT COUNT(*) as completed FROM LabSchedule WHERE InstructorID = ? AND Status = 'Completed'");
$completedStmt->execute([$instructorID]);
$stats['completed_schedules'] = $completedStmt->fetch()['completed'];

// Total attendance records
$attendanceStmt = $pdo->prepare("
    SELECT COUNT(*) as total_attendance 
    FROM Attendance a 
    JOIN LabSchedule ls ON a.ScheduleID = ls.ScheduleID 
    WHERE ls.InstructorID = ?
");
$attendanceStmt->execute([$instructorID]);
$stats['total_attendance'] = $attendanceStmt->fetch()['total_attendance'];

// Fetch labs for filter dropdown
$labs = $pdo->query("SELECT DISTINCT l.LabID, l.LabName FROM Lab l JOIN LabSchedule ls ON l.LabID = ls.LabID WHERE ls.InstructorID = '$instructorID' ORDER BY l.LabName ASC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Schedules - EduPortal</title>
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
        
        .scheduled-card {
            border-left: 4px solid #28a745;
        }
        
        .completed-card {
            border-left: 4px solid #007bff;
        }
        
        .cancelled-card {
            border-left: 4px solid #dc3545;
        }
        
        .filter-card {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
            border-radius: 10px;
        }
        
        .info-badge {
            font-size: 0.75rem;
            padding: 0.25rem 0.5rem;
        }
        
        .schedule-actions {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }
        
        .calendar-view {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
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
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h1 class="page-title">
                            <i class="fas fa-calendar-alt me-3"></i>
                            Lab Schedules
                        </h1>
                        <p class="page-subtitle">View and manage your assigned lab schedules</p>
                    </div>
                    <div class="col-md-4 text-end">
                        <div class="btn-group">
                            <a href="instructor_attendance.php" class="btn btn-success">
                                <i class="fas fa-user-check me-2"></i>Mark Attendance
                            </a>
                            <a href="instructor_manage_attendance.php" class="btn btn-outline-primary">
                                <i class="fas fa-edit me-2"></i>Manage Attendance
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Statistics Cards -->
            <div class="row mb-4">
                <div class="col-md-3 mb-3">
                    <div class="stats-card slide-up">
                        <i class="fas fa-calendar-alt stats-icon text-success"></i>
                        <div class="stats-number"><?= $stats['total_schedules'] ?></div>
                        <div class="stats-label">Total Schedules</div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="stats-card slide-up" style="animation-delay: 0.1s;">
                        <i class="fas fa-clock stats-icon text-warning"></i>
                        <div class="stats-number"><?= $stats['upcoming_schedules'] ?></div>
                        <div class="stats-label">Upcoming</div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="stats-card slide-up" style="animation-delay: 0.2s;">
                        <i class="fas fa-check-circle stats-icon text-primary"></i>
                        <div class="stats-number"><?= $stats['completed_schedules'] ?></div>
                        <div class="stats-label">Completed</div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="stats-card slide-up" style="animation-delay: 0.3s;">
                        <i class="fas fa-user-check stats-icon text-info"></i>
                        <div class="stats-number"><?= $stats['total_attendance'] ?></div>
                        <div class="stats-label">Attendance Records</div>
                    </div>
                </div>
            </div>

            <?= $message ?>

            <div class="row">
                <div class="col-lg-3">
                    <!-- Filter Card -->
                    <div class="card filter-card fade-in">
                        <div class="card-header border-0">
                            <h5 class="mb-0">
                                <i class="fas fa-filter me-2"></i>
                                Filter Schedules
                            </h5>
                        </div>
                        <div class="card-body">
                            <form method="GET" action="">
                                <div class="mb-3">
                                    <label class="form-label text-white">Date</label>
                                    <input type="date" name="filter_date" class="form-control" 
                                           value="<?= htmlspecialchars($filterDate) ?>">
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label text-white">Status</label>
                                    <select name="filter_status" class="form-select">
                                        <option value="">All Statuses</option>
                                        <option value="Scheduled" <?= $filterStatus === 'Scheduled' ? 'selected' : '' ?>>Scheduled</option>
                                        <option value="Completed" <?= $filterStatus === 'Completed' ? 'selected' : '' ?>>Completed</option>
                                        <option value="Cancelled" <?= $filterStatus === 'Cancelled' ? 'selected' : '' ?>>Cancelled</option>
                                    </select>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label text-white">Lab</label>
                                    <select name="filter_lab" class="form-select">
                                        <option value="">All Labs</option>
                                        <?php foreach ($labs as $lab): ?>
                                        <option value="<?= $lab['LabID'] ?>" <?= $filterLab === $lab['LabID'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($lab['LabName']) ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="d-grid gap-2">
                                    <button type="submit" class="btn btn-light">
                                        <i class="fas fa-search me-2"></i>Apply Filters
                                    </button>
                                    <a href="schedule_view.php" class="btn btn-outline-light">
                                        <i class="fas fa-times me-2"></i>Clear Filters
                                    </a>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Quick Actions -->
                    <div class="card mt-4 fade-in">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-bolt me-2"></i>
                                Quick Actions
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="d-grid gap-2">
                                <a href="instructor_attendance.php" class="btn btn-success">
                                    <i class="fas fa-user-check me-2"></i>Mark Attendance
                                </a>
                                <a href="instructor_manage_attendance.php" class="btn btn-primary">
                                    <i class="fas fa-edit me-2"></i>Manage Attendance
                                </a>
                                <a href="instructor_dashboard.php" class="btn btn-outline-secondary">
                                    <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-9">
                    <?php if ($schedules->rowCount() > 0): ?>
                        <?php while ($schedule = $schedules->fetch()): ?>
                            <?php
                            $statusClass = match($schedule['Status']) {
                                'Scheduled' => 'success',
                                'Completed' => 'primary',
                                'Cancelled' => 'danger',
                                default => 'secondary'
                            };
                            
                            $cardClass = match($schedule['Status']) {
                                'Scheduled' => 'scheduled-card',
                                'Completed' => 'completed-card',
                                'Cancelled' => 'cancelled-card',
                                default => ''
                            };
                            
                            $isUpcoming = strtotime($schedule['Date']) >= strtotime(date('Y-m-d'));
                            $isPast = strtotime($schedule['Date']) < strtotime(date('Y-m-d'));
                            ?>
                            <div class="schedule-card <?= $cardClass ?> fade-in">
                                <div class="card-body">
                                    <div class="row align-items-center">
                                        <div class="col-md-4">
                                            <h5 class="mb-1">
                                                <i class="fas fa-flask me-2 text-success"></i>
                                                <?= htmlspecialchars($schedule['LabName']) ?>
                                            </h5>
                                            <small class="text-muted">
                                                <i class="fas fa-map-marker-alt me-1"></i>
                                                <?= htmlspecialchars($schedule['Location']) ?>
                                            </small>
                                            <br>
                                            <small class="text-muted">
                                                <i class="fas fa-users me-1"></i>
                                                Capacity: <?= htmlspecialchars($schedule['Capacity']) ?>
                                            </small>
                                        </div>
                                        
                                        <div class="col-md-3">
                                            <strong>Schedule Details:</strong><br>
                                            <span class="badge bg-secondary"><?= htmlspecialchars($schedule['ScheduleID']) ?></span><br>
                                            <small class="text-muted">
                                                <i class="fas fa-calendar me-1"></i>
                                                <?= date('M d, Y', strtotime($schedule['Date'])) ?>
                                            </small><br>
                                            <small class="text-muted">
                                                <i class="fas fa-clock me-1"></i>
                                                <?= htmlspecialchars($schedule['TimeSlot']) ?>
                                            </small>
                                        </div>
                                        
                                        <div class="col-md-3">
                                            <strong>Coordinator:</strong><br>
                                            <span><?= htmlspecialchars(safeGet($schedule, 'CoordinatorName', 'Not Assigned')) ?></span><br>
                                            
                                            <div class="mt-2">
                                                <span class="badge bg-<?= $statusClass ?> status-badge"><?= htmlspecialchars($schedule['Status']) ?></span>
                                                
                                                <?php if ($isUpcoming && $schedule['Status'] === 'Scheduled'): ?>
                                                    <br><span class="badge bg-warning info-badge mt-1">
                                                        <i class="fas fa-exclamation me-1"></i>Upcoming
                                                    </span>
                                                <?php elseif ($isPast && $schedule['Status'] === 'Scheduled'): ?>
                                                    <br><span class="badge bg-danger info-badge mt-1">
                                                        <i class="fas fa-clock me-1"></i>Overdue
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        
                                        <div class="col-md-2">
                                            <div class="mb-2">
                                                <?php if ($schedule['AttendanceCount'] > 0): ?>
                                                    <span class="badge bg-info info-badge me-1">
                                                        <i class="fas fa-user-check me-1"></i><?= $schedule['AttendanceCount'] ?> Attendance
                                                    </span>
                                                <?php endif; ?>
                                                
                                                <?php if ($schedule['PendingRequests'] > 0): ?>
                                                    <br><span class="badge bg-warning info-badge mt-1">
                                                        <i class="fas fa-clock me-1"></i><?= $schedule['PendingRequests'] ?> Pending
                                                    </span>
                                                <?php endif; ?>
                                                
                                                <?php if ($schedule['RequestCount'] > 0): ?>
                                                    <br><span class="badge bg-secondary info-badge mt-1">
                                                        <i class="fas fa-list me-1"></i><?= $schedule['RequestCount'] ?> Total Requests
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                            
                                            <div class="schedule-actions">
                                                <?php if ($schedule['Status'] === 'Scheduled'): ?>
                                                    <a href="instructor_attendance.php?schedule=<?= $schedule['ScheduleID'] ?>" 
                                                       class="btn btn-sm btn-success" title="Mark Attendance">
                                                        <i class="fas fa-user-check"></i>
                                                    </a>
                                                <?php endif; ?>
                                                
                                                <a href="instructor_manage_attendance.php?schedule=<?= $schedule['ScheduleID'] ?>" 
                                                   class="btn btn-sm btn-primary" title="Manage Attendance">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                
                                                <button class="btn btn-sm btn-outline-info" 
                                                        onclick="showScheduleDetails('<?= $schedule['ScheduleID'] ?>')" 
                                                        title="View Details">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="card fade-in">
                            <div class="card-body text-center py-5">
                                <i class="fas fa-calendar-times fa-3x text-muted mb-3"></i>
                                <h5>No Lab Schedules Found</h5>
                                <?php if (!empty($filterDate) || !empty($filterStatus) || !empty($filterLab)): ?>
                                    <p class="text-muted">No schedules match your current filters.</p>
                                    <a href="schedule_view.php" class="btn btn-primary">
                                        <i class="fas fa-times me-2"></i>Clear Filters
                                    </a>
                                <?php else: ?>
                                    <p class="text-muted">You haven't been assigned to any lab schedules yet.</p>
                                    <a href="instructor_dashboard.php" class="btn btn-primary">
                                        <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>

    <!-- Schedule Details Modal -->
    <div class="modal fade" id="scheduleDetailsModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-calendar-alt me-2"></i>Schedule Details
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="scheduleDetailsContent">
                    <div class="text-center py-4">
                        <i class="fas fa-spinner fa-spin fa-2x text-muted"></i>
                        <p class="mt-2 text-muted">Loading schedule details...</p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
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
    // Show schedule details in modal
    function showScheduleDetails(scheduleId) {
        const modal = new bootstrap.Modal(document.getElementById('scheduleDetailsModal'));
        const content = document.getElementById('scheduleDetailsContent');
        
        // Show loading state
        content.innerHTML = `
            <div class="text-center py-4">
                <i class="fas fa-spinner fa-spin fa-2x text-muted"></i>
                <p class="mt-2 text-muted">Loading schedule details...</p>
            </div>
        `;
        
        modal.show();
        
        // Fetch schedule details (you can implement this as needed)
        setTimeout(() => {
            content.innerHTML = `
                <div class="row">
                    <div class="col-md-6">
                        <h6><i class="fas fa-info-circle me-2"></i>Schedule Information</h6>
                        <p><strong>Schedule ID:</strong> ${scheduleId}</p>
                        <p><strong>Status:</strong> <span class="badge bg-success">Scheduled</span></p>
                        <p><strong>Created:</strong> ${new Date().toLocaleDateString()}</p>
                    </div>
                    <div class="col-md-6">
                        <h6><i class="fas fa-chart-bar me-2"></i>Statistics</h6>
                        <p><strong>Attendance Records:</strong> 0</p>
                        <p><strong>Reschedule Requests:</strong> 0</p>
                        <p><strong>Pending Requests:</strong> 0</p>
                    </div>
                </div>
                <div class="mt-3">
                    <h6><i class="fas fa-tools me-2"></i>Quick Actions</h6>
                    <div class="btn-group">
                        <a href="instructor_attendance.php?schedule=${scheduleId}" class="btn btn-success btn-sm">
                            <i class="fas fa-user-check me-1"></i>Mark Attendance
                        </a>
                        <a href="instructor_manage_attendance.php?schedule=${scheduleId}" class="btn btn-primary btn-sm">
                            <i class="fas fa-edit me-1"></i>Manage Attendance
                        </a>
                    </div>
                </div>
            `;
        }, 1000);
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
