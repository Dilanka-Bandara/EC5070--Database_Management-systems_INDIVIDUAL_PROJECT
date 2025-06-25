<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: ../index.php");
    exit;
}

require '../config/database.php';
$pageTitle = "Student Dashboard";

// Fetch student details
$studentID = $_SESSION['ref_id'];
$stmt = $pdo->prepare("SELECT * FROM Student WHERE StudentID = ?");
$stmt->execute([$studentID]);
$student = $stmt->fetch();

if (!$student) {
    die("Error: Could not find student details for ID: " . htmlspecialchars($studentID));
}

// Fetch dashboard statistics
$stats = [];

// Total reschedule requests
$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM RescheduleRequest WHERE StudentID = ?");
$stmt->execute([$studentID]);
$stats['total_requests'] = $stmt->fetch()['total'];

// Pending requests
$stmt = $pdo->prepare("SELECT COUNT(*) as pending FROM RescheduleRequest WHERE StudentID = ? AND Status = 'Pending'");
$stmt->execute([$studentID]);
$stats['pending_requests'] = $stmt->fetch()['pending'];

// Approved requests
$stmt = $pdo->prepare("SELECT COUNT(*) as approved FROM RescheduleRequest WHERE StudentID = ? AND Status = 'Approved'");
$stmt->execute([$studentID]);
$stats['approved_requests'] = $stmt->fetch()['approved'];

// Unread notifications
$stmt = $pdo->prepare("SELECT COUNT(*) as unread FROM Notification WHERE StudentID = ?");
$stmt->execute([$studentID]);
$stats['unread_notifications'] = $stmt->fetch()['unread'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard - EduPortal</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary fixed-top">
        <div class="container-fluid">
            <a class="navbar-brand d-flex align-items-center" href="../index.php">
                <i class="fas fa-graduation-cap me-2"></i>
                <span class="fw-bold">EduPortal</span>
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="student_dashboard.php">
                            <i class="fas fa-tachometer-alt me-1"></i>Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="reschedule_request.php">
                            <i class="fas fa-calendar-alt me-1"></i>Request Reschedule
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="notifications.php">
                            <i class="fas fa-bell me-1"></i>Notifications
                            <?php if ($stats['unread_notifications'] > 0): ?>
                                <span class="badge bg-danger ms-1"><?= $stats['unread_notifications'] ?></span>
                            <?php endif; ?>
                        </a>
                    </li>
                </ul>
                
                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user-circle me-1"></i>
                            <?= htmlspecialchars($student['Name']) ?>
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="profile.php"><i class="fas fa-user me-2"></i>Profile</a></li>
                            <li><a class="dropdown-item" href="settings.php"><i class="fas fa-cog me-2"></i>Settings</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                        </ul>
                    </li>
                </ul>
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
                            <i class="fas fa-tachometer-alt me-3"></i>
                            Welcome back, <?= htmlspecialchars($student['Name']) ?>
                        </h1>
                        <p class="page-subtitle">Manage your lab schedules and reschedule requests</p>
                    </div>
                    <div class="col-md-4 text-end">
                        <div class="d-flex gap-2 justify-content-end">
                            <a href="reschedule_request.php" class="btn btn-primary">
                                <i class="fas fa-plus me-2"></i>New Request
                            </a>
                            <a href="logout.php" class="btn btn-outline-danger">
                                <i class="fas fa-sign-out-alt me-2"></i>Logout
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Statistics Cards -->
            <div class="row mb-4">
                <div class="col-md-3 mb-3">
                    <div class="stats-card slide-up">
                        <i class="fas fa-calendar-check stats-icon"></i>
                        <div class="stats-number"><?= $stats['total_requests'] ?></div>
                        <div class="stats-label">Total Requests</div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="stats-card slide-up" style="animation-delay: 0.1s;">
                        <i class="fas fa-clock stats-icon"></i>
                        <div class="stats-number"><?= $stats['pending_requests'] ?></div>
                        <div class="stats-label">Pending</div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="stats-card slide-up" style="animation-delay: 0.2s;">
                        <i class="fas fa-check-circle stats-icon"></i>
                        <div class="stats-number"><?= $stats['approved_requests'] ?></div>
                        <div class="stats-label">Approved</div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="stats-card slide-up" style="animation-delay: 0.3s;">
                        <i class="fas fa-bell stats-icon"></i>
                        <div class="stats-number"><?= $stats['unread_notifications'] ?></div>
                        <div class="stats-label">Notifications</div>
                    </div>
                </div>
            </div>

            <?php if (isset($_GET['msg']) && $_GET['msg'] == 'success'): ?>
                <div class="alert alert-success fade-in">
                    <i class="fas fa-check-circle me-2"></i>
                    Request submitted successfully!
                </div>
            <?php endif; ?>

            <!-- Recent Activity -->
            <div class="row">
                <div class="col-lg-8">
                    <!-- Available Lab Schedules (MOVED UP - NOW FIRST) -->
                    <div class="card mb-4 fade-in">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-calendar-alt me-2"></i>
                                Available Lab Schedules
                            </h5>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-container">
                                <table class="table table-hover mb-0">
                                    <thead>
                                        <tr>
                                            <th>Lab</th>
                                            <th>Date & Time</th>
                                            <th>Instructor</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    <?php
                                    $schedules = $pdo->query("
                                        SELECT ls.*, l.LabName, li.Name as InstructorName 
                                        FROM LabSchedule ls 
                                        JOIN Lab l ON ls.LabID = l.LabID 
                                        LEFT JOIN LabInstructor li ON ls.InstructorID = li.InstructorID 
                                        WHERE ls.Status = 'Scheduled' AND ls.Date >= CURDATE()
                                        ORDER BY ls.Date ASC, ls.TimeSlot ASC 
                                        LIMIT 10
                                    ");
                                    
                                    if ($schedules->rowCount() > 0) {
                                        while ($schedule = $schedules->fetch()):
                                    ?>
                                        <tr>
                                            <td>
                                                <div>
                                                    <strong><?= htmlspecialchars($schedule['LabName']) ?></strong><br>
                                                    <small class="text-muted">ID: <?= htmlspecialchars($schedule['ScheduleID']) ?></small>
                                                </div>
                                            </td>
                                            <td>
                                                <div>
                                                    <strong><?= date('M d, Y', strtotime($schedule['Date'])) ?></strong><br>
                                                    <small class="text-muted"><?= htmlspecialchars($schedule['TimeSlot']) ?></small>
                                                </div>
                                            </td>
                                            <td><?= htmlspecialchars($schedule['InstructorName'] ?? 'TBA') ?></td>
                                            <td>
                                                <span class="badge bg-primary"><?= htmlspecialchars($schedule['Status']) ?></span>
                                            </td>
                                            <td>
                                                <a href="reschedule_request.php?schedule=<?= $schedule['ScheduleID'] ?>" 
                                                   class="btn btn-sm btn-outline-primary">
                                                    <i class="fas fa-calendar-plus me-1"></i>Request
                                                </a>
                                            </td>
                                        </tr>
                                    <?php
                                        endwhile;
                                    } else {
                                        echo "<tr><td colspan='5' class='text-center py-4'>
                                                <i class='fas fa-calendar-times fa-2x text-muted mb-2'></i><br>
                                                No upcoming lab schedules
                                              </td></tr>";
                                    }
                                    ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Reschedule Requests (NOW SECOND) -->
                    <div class="card fade-in">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">
                                <i class="fas fa-history me-2"></i>
                                Recent Reschedule Requests
                            </h5>
                            <a href="reschedule_request.php" class="btn btn-sm btn-primary">
                                <i class="fas fa-plus me-1"></i>New Request
                            </a>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-container">
                                <table class="table table-hover mb-0">
                                    <thead>
                                        <tr>
                                            <th>Request ID</th>
                                            <th>Schedule</th>
                                            <th>Date</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    <?php
                                    $recentRequests = $pdo->prepare("
                                        SELECT rr.*, ls.Date as ScheduleDate, ls.TimeSlot, l.LabName 
                                        FROM RescheduleRequest rr 
                                        LEFT JOIN LabSchedule ls ON rr.ScheduleID = ls.ScheduleID 
                                        LEFT JOIN Lab l ON ls.LabID = l.LabID 
                                        WHERE rr.StudentID = ? 
                                        ORDER BY rr.RequestDate DESC 
                                        LIMIT 5
                                    ");
                                    $recentRequests->execute([$studentID]);
                                    
                                    if ($recentRequests->rowCount() > 0) {
                                        while ($req = $recentRequests->fetch()):
                                            $statusClass = match($req['Status']) {
                                                'Approved' => 'success',
                                                'Rejected' => 'danger',
                                                default => 'warning'
                                            };
                                    ?>
                                        <tr>
                                            <td>
                                                <span class="fw-bold"><?= htmlspecialchars($req['RequestID']) ?></span>
                                            </td>
                                            <td>
                                                <div>
                                                    <strong><?= htmlspecialchars($req['LabName'] ?? 'N/A') ?></strong><br>
                                                    <small class="text-muted"><?= htmlspecialchars($req['TimeSlot'] ?? 'N/A') ?></small>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="fw-medium"><?= date('M d, Y', strtotime($req['RequestDate'])) ?></span><br>
                                                <small class="text-muted"><?= date('g:i A', strtotime($req['RequestDate'])) ?></small>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?= $statusClass ?>"><?= htmlspecialchars($req['Status']) ?></span>
                                            </td>
                                            <td>
                                                <button class="btn btn-sm btn-outline-primary" onclick="viewRequest('<?= $req['RequestID'] ?>')">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    <?php
                                        endwhile;
                                    } else {
                                        echo "<tr><td colspan='5' class='text-center py-4'>
                                                <i class='fas fa-inbox fa-2x text-muted mb-2'></i><br>
                                                No reschedule requests found
                                              </td></tr>";
                                    }
                                    ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <!-- Quick Stats -->
                    <div class="card mb-4 fade-in">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-chart-pie me-2"></i>
                                Quick Overview
                            </h5>
                        </div>
                        <div class="card-body">
                            <canvas id="requestChart" width="400" height="200"></canvas>
                        </div>
                    </div>

                    <!-- Recent Notifications -->
                    <div class="card fade-in">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">
                                <i class="fas fa-bell me-2"></i>
                                Recent Notifications
                            </h5>
                            <a href="notifications.php" class="btn btn-sm btn-outline-primary">View All</a>
                        </div>
                        <div class="card-body">
                            <?php
                            $notifications = $pdo->prepare("
                                SELECT * FROM Notification 
                                WHERE StudentID = ? 
                                ORDER BY Timestamp DESC 
                                LIMIT 5
                            ");
                            $notifications->execute([$studentID]);
                            
                            if ($notifications->rowCount() > 0) {
                                while ($notif = $notifications->fetch()):
                            ?>
                                <div class="d-flex align-items-start mb-3 p-2 rounded" style="background: #f8f9fa;">
                                    <div class="flex-shrink-0 me-3">
                                        <i class="fas fa-info-circle text-primary"></i>
                                    </div>
                                    <div class="flex-grow-1">
                                        <p class="mb-1 fw-medium"><?= htmlspecialchars($notif['Message']) ?></p>
                                        <small class="text-muted">
                                            <i class="fas fa-clock me-1"></i>
                                            <?= date('M d, Y g:i A', strtotime($notif['Timestamp'])) ?>
                                        </small>
                                    </div>
                                </div>
                            <?php
                                endwhile;
                            } else {
                                echo "<div class='text-center py-3'>
                                        <i class='fas fa-bell-slash fa-2x text-muted mb-2'></i><br>
                                        <span class='text-muted'>No notifications</span>
                                      </div>";
                            }
                            ?>
                        </div>
                    </div>
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
                    <p class="mb-0">Professional Lab Rescheduling Management System</p>
                </div>
                <div class="col-md-6 text-end">
                    <p class="mb-0">&copy; <?php echo date('Y'); ?> EduPortal. All rights reserved.</p>
                    <small class="text-muted">Version 2.0</small>
                </div>
            </div>
        </div>
    </footer>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
    // Chart.js for request statistics
    const ctx = document.getElementById('requestChart').getContext('2d');
    const requestChart = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: ['Approved', 'Pending', 'Rejected'],
            datasets: [{
                data: [
                    <?= $stats['approved_requests'] ?>,
                    <?= $stats['pending_requests'] ?>,
                    <?= $stats['total_requests'] - $stats['approved_requests'] - $stats['pending_requests'] ?>
                ],
                backgroundColor: [
                    '#10b981',
                    '#f59e0b',
                    '#ef4444'
                ],
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });

    // View request details
    function viewRequest(requestId) {
        alert('Request details for ID: ' + requestId);
    }
    </script>
</body>
</html>
