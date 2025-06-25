<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'coordinator') {
    header("Location: ../index.php");
    exit;
}

require '../includes/db_connect.php';
$pageTitle = "Coordinator Dashboard";

// Fetch coordinator details
$coordinatorID = $_SESSION['ref_id'];
$stmt = $pdo->prepare("SELECT * FROM SubjectCoordinator WHERE CoordinatorID = ?");
$stmt->execute([$coordinatorID]);
$coordinator = $stmt->fetch();

if (!$coordinator) {
    die("Error: Could not find coordinator details for ID: " . htmlspecialchars($coordinatorID));
}

// Handle notification deletion
if (isset($_POST['delete_notification'])) {
    $notificationId = $_POST['notification_id'];
    $deleteStmt = $pdo->prepare("DELETE FROM Notification WHERE NotificationID = ?");
    $deleteStmt->execute([$notificationId]);
}

// Handle Accept/Reject for reschedule requests
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['decision']) && isset($_POST['request_id'])) {
    $requestID = $_POST['request_id'];
    $decision = $_POST['decision'];
    $status = ($decision === 'Approved') ? 'Approved' : 'Rejected';

    try {
        // Get student ID from the request
        $getStudentStmt = $pdo->prepare("SELECT StudentID, ScheduleID FROM RescheduleRequest WHERE RequestID = ?");
        $getStudentStmt->execute([$requestID]);
        $requestData = $getStudentStmt->fetch();
        
        if ($requestData) {
            $studentID = $requestData['StudentID'];
            $scheduleID = $requestData['ScheduleID'];

            // Update the request status
            $stmt = $pdo->prepare("UPDATE RescheduleRequest SET Status = ? WHERE RequestID = ?");
            $stmt->execute([$status, $requestID]);

            // Log the action with current timestamp (this creates our decision timestamp)
            $log = $pdo->prepare("INSERT INTO RescheduleLog (RequestID, Action, Timestamp) VALUES (?, ?, NOW())");
            $log->execute([$requestID, $status]);

            // Get the exact decision time from the log we just created
            $getDecisionTime = $pdo->prepare("
                SELECT Timestamp FROM RescheduleLog 
                WHERE RequestID = ? AND Action = ? 
                ORDER BY Timestamp DESC LIMIT 1
            ");
            $getDecisionTime->execute([$requestID, $status]);
            $decisionTime = $getDecisionTime->fetch();

            // Send notification to student with exact decision time
            if ($decisionTime) {
                $decisionTimeFormatted = date('M d, Y \a\t g:i A', strtotime($decisionTime['Timestamp']));
                $notificationMessage = "Your reschedule request (ID: $requestID) for lab schedule $scheduleID was $status on $decisionTimeFormatted by the coordinator.";
            } else {
                $notificationMessage = "Your reschedule request (ID: $requestID) for lab schedule $scheduleID has been $status by the coordinator.";
            }
            
            $notif_student = $pdo->prepare("INSERT INTO Notification (Message, Timestamp, Type, StudentID) VALUES (?, NOW(), ?, ?)");
            $notif_student->execute([$notificationMessage, 'reschedule_response', $studentID]);

            // Send confirmation notification to coordinator
            $coordinatorMessage = "You have successfully $status reschedule request (ID: $requestID) for student ID: $studentID.";
            $notif_coordinator = $pdo->prepare("INSERT INTO Notification (Message, Timestamp, Type, CoordinatorID) VALUES (?, NOW(), ?, ?)");
            $notif_coordinator->execute([$coordinatorMessage, 'action_confirmation', $coordinatorID]);
        }
        
    } catch (PDOException $e) {
        error_log("Notification/Log error: " . $e->getMessage());
    }
}

// Handle form submission for adding a new lab
$message = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_lab'])) {
    $scheduleID = trim($_POST['scheduleID']);
    $labID = $_POST['labID'];
    $instructorID = $_POST['instructorID'];
    $date = $_POST['date'];
    $timeSlot = $_POST['timeSlot'];
    $status = $_POST['status'];

    try {
        // Check if ScheduleID already exists
        $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM LabSchedule WHERE ScheduleID = ?");
        $checkStmt->execute([$scheduleID]);
        if ($checkStmt->fetchColumn() > 0) {
            $message = "<div class='alert alert-danger'>Schedule ID '{$scheduleID}' already exists. Please choose a different one.</div>";
        } else {
            $stmt = $pdo->prepare("INSERT INTO LabSchedule (ScheduleID, LabID, CoordinatorID, TimeSlot, Date, Status, InstructorID) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$scheduleID, $labID, $coordinatorID, $timeSlot, $date, $status, $instructorID]);
            $message = "<div class='alert alert-success'>New lab schedule added successfully!</div>";
        }
    } catch (PDOException $e) {
        $message = "<div class='alert alert-danger'>Error: " . htmlspecialchars($e->getMessage()) . "</div>";
    }
}

// Fetch dashboard statistics
$stats = [];

// Total lab schedules managed
$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM LabSchedule WHERE CoordinatorID = ?");
$stmt->execute([$coordinatorID]);
$stats['total_schedules'] = $stmt->fetch()['total'];

// Pending reschedule requests
$stmt = $pdo->prepare("
    SELECT COUNT(*) as pending 
    FROM RescheduleRequest rr
    JOIN LabSchedule ls ON rr.ScheduleID = ls.ScheduleID
    WHERE ls.CoordinatorID = ? AND rr.Status = 'Pending'
");
$stmt->execute([$coordinatorID]);
$stats['pending_requests'] = $stmt->fetch()['pending'];

// Approved requests this month
$stmt = $pdo->prepare("
    SELECT COUNT(*) as approved 
    FROM RescheduleRequest rr
    JOIN LabSchedule ls ON rr.ScheduleID = ls.ScheduleID
    WHERE ls.CoordinatorID = ? AND rr.Status = 'Approved' AND MONTH(rr.RequestDate) = MONTH(CURDATE())
");
$stmt->execute([$coordinatorID]);
$stats['approved_requests'] = $stmt->fetch()['approved'];

// Rejected requests
$stmt = $pdo->prepare("
    SELECT COUNT(*) as rejected 
    FROM RescheduleRequest rr
    JOIN LabSchedule ls ON rr.ScheduleID = ls.ScheduleID
    WHERE ls.CoordinatorID = ? AND rr.Status = 'Rejected'
");
$stmt->execute([$coordinatorID]);
$stats['rejected_requests'] = $stmt->fetch()['rejected'];

// Total instructors under coordination
$stmt = $pdo->prepare("SELECT COUNT(DISTINCT InstructorID) as total FROM LabSchedule WHERE CoordinatorID = ? AND InstructorID IS NOT NULL");
$stmt->execute([$coordinatorID]);
$stats['total_instructors'] = $stmt->fetch()['total'];

// Fetch labs and instructors for dropdowns
$labs = $pdo->query("SELECT LabID, LabName FROM Lab ORDER BY LabName ASC");
$instructors = $pdo->query("SELECT InstructorID, Name FROM LabInstructor ORDER BY Name ASC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Coordinator Dashboard - EduPortal</title>
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
                        <a class="nav-link active" href="coordinator_dashboard.php">
                            <i class="fas fa-tachometer-alt me-1"></i>Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="schedule_view.php">
                            <i class="fas fa-calendar-alt me-1"></i>Manage Schedules
                        </a>
                    </li>
                </ul>
                
                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user-circle me-1"></i>
                            <?= htmlspecialchars($coordinator['Name']) ?>
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
                            <i class="fas fa-user-tie me-3"></i>
                            Welcome, <?= htmlspecialchars($coordinator['Name']) ?>
                        </h1>
                        <p class="page-subtitle">Coordinate lab schedules and manage reschedule requests</p>
                    </div>
                    <div class="col-md-4 text-end">
                        <div class="d-flex gap-2 justify-content-end">
                            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addScheduleModal">
                                <i class="fas fa-plus me-2"></i>Add Schedule
                            </button>
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
                        <i class="fas fa-calendar-alt stats-icon"></i>
                        <div class="stats-number"><?= $stats['total_schedules'] ?></div>
                        <div class="stats-label">Total Schedules</div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="stats-card slide-up" style="animation-delay: 0.1s;">
                        <i class="fas fa-clock stats-icon"></i>
                        <div class="stats-number"><?= $stats['pending_requests'] ?></div>
                        <div class="stats-label">Pending Requests</div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="stats-card slide-up" style="animation-delay: 0.2s;">
                        <i class="fas fa-check-circle stats-icon"></i>
                        <div class="stats-number"><?= $stats['approved_requests'] ?></div>
                        <div class="stats-label">Approved This Month</div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="stats-card slide-up" style="animation-delay: 0.3s;">
                        <i class="fas fa-times-circle stats-icon"></i>
                        <div class="stats-number"><?= $stats['rejected_requests'] ?></div>
                        <div class="stats-label">Rejected Requests</div>
                    </div>
                </div>
            </div>

            <?= $message ?>

            <!-- Main Content -->
            <div class="row">
                <div class="col-lg-8">
                    <!-- Pending Reschedule Requests -->
                    <div class="card mb-4 fade-in">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">
                                <i class="fas fa-exclamation-circle me-2"></i>
                                Pending Reschedule Requests
                            </h5>
                            <span class="badge bg-warning"><?= $stats['pending_requests'] ?> Pending</span>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-container">
                                <table class="table table-hover mb-0">
                                    <thead>
                                        <tr>
                                            <th>Request ID</th>
                                            <th>Student</th>
                                            <th>Lab Schedule</th>
                                            <th>Reason</th>
                                            <th>Date</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    <?php
                                    $pendingRequests = $pdo->prepare("
                                        SELECT rr.*, s.Name as StudentName, l.LabName, ls.TimeSlot, ls.Date as ScheduleDate
                                        FROM RescheduleRequest rr
                                        JOIN Student s ON rr.StudentID = s.StudentID
                                        JOIN LabSchedule ls ON rr.ScheduleID = ls.ScheduleID
                                        JOIN Lab l ON ls.LabID = l.LabID
                                        WHERE ls.CoordinatorID = ? AND rr.Status = 'Pending'
                                        ORDER BY rr.RequestDate DESC
                                    ");
                                    $pendingRequests->execute([$coordinatorID]);
                                    
                                    if ($pendingRequests->rowCount() > 0) {
                                        while ($request = $pendingRequests->fetch()):
                                    ?>
                                        <tr>
                                            <td>
                                                <span class="fw-bold"><?= htmlspecialchars($request['RequestID']) ?></span>
                                            </td>
                                            <td>
                                                <div>
                                                    <strong><?= htmlspecialchars($request['StudentName']) ?></strong><br>
                                                    <small class="text-muted">ID: <?= htmlspecialchars($request['StudentID']) ?></small>
                                                </div>
                                            </td>
                                            <td>
                                                <div>
                                                    <strong><?= htmlspecialchars($request['LabName']) ?></strong><br>
                                                    <small class="text-muted">
                                                        <?= date('M d, Y', strtotime($request['ScheduleDate'])) ?> - 
                                                        <?= htmlspecialchars($request['TimeSlot']) ?>
                                                    </small>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="text-truncate d-inline-block" style="max-width: 150px;" 
                                                      title="<?= htmlspecialchars($request['Reason']) ?>">
                                                    <?= htmlspecialchars($request['Reason']) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="fw-medium"><?= date('M d, Y', strtotime($request['RequestDate'])) ?></span><br>
                                                <small class="text-muted"><?= date('g:i A', strtotime($request['RequestDate'])) ?></small>
                                            </td>
                                            <td>
                                                <form method="POST" class="d-inline">
                                                    <input type="hidden" name="request_id" value="<?= htmlspecialchars($request['RequestID']) ?>">
                                                    <div class="btn-group" role="group">
                                                        <button type="submit" name="decision" value="Approved" 
                                                                class="btn btn-sm btn-success" title="Approve">
                                                            <i class="fas fa-check"></i>
                                                        </button>
                                                        <button type="submit" name="decision" value="Rejected" 
                                                                class="btn btn-sm btn-danger" title="Reject">
                                                            <i class="fas fa-times"></i>
                                                        </button>
                                                    </div>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php
                                        endwhile;
                                    } else {
                                        echo "<tr><td colspan='6' class='text-center py-4'>
                                                <i class='fas fa-check-circle fa-2x text-success mb-2'></i><br>
                                                No pending requests
                                              </td></tr>";
                                    }
                                    ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Lab Schedules -->
                    <div class="card mb-4 fade-in">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">
                                <i class="fas fa-calendar-week me-2"></i>
                                Recent Lab Schedules
                            </h5>
                            <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addScheduleModal">
                                <i class="fas fa-plus me-1"></i>Add New
                            </button>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-container">
                                <table class="table table-hover mb-0">
                                    <thead>
                                        <tr>
                                            <th>Schedule ID</th>
                                            <th>Lab</th>
                                            <th>Instructor</th>
                                            <th>Date & Time</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    <?php
                                    $schedules = $pdo->prepare("
                                        SELECT ls.*, l.LabName, l.Location, li.Name as InstructorName
                                        FROM LabSchedule ls
                                        JOIN Lab l ON ls.LabID = l.LabID
                                        LEFT JOIN LabInstructor li ON ls.InstructorID = li.InstructorID
                                        WHERE ls.CoordinatorID = ?
                                        ORDER BY ls.Date DESC
                                        LIMIT 5
                                    ");
                                    $schedules->execute([$coordinatorID]);
                                    
                                    if ($schedules->rowCount() > 0) {
                                        while ($schedule = $schedules->fetch()):
                                            $statusClass = match($schedule['Status']) {
                                                'Scheduled' => 'primary',
                                                'Completed' => 'success',
                                                'Cancelled' => 'danger',
                                                default => 'secondary'
                                            };
                                    ?>
                                        <tr>
                                            <td>
                                                <span class="fw-bold"><?= htmlspecialchars($schedule['ScheduleID']) ?></span>
                                            </td>
                                            <td>
                                                <div>
                                                    <strong><?= htmlspecialchars($schedule['LabName']) ?></strong><br>
                                                    <small class="text-muted"><?= htmlspecialchars($schedule['Location']) ?></small>
                                                </div>
                                            </td>
                                            <td>
                                                <?= htmlspecialchars($schedule['InstructorName'] ?? 'Not Assigned') ?>
                                            </td>
                                            <td>
                                                <div>
                                                    <strong><?= date('M d, Y', strtotime($schedule['Date'])) ?></strong><br>
                                                    <small class="text-muted"><?= htmlspecialchars($schedule['TimeSlot']) ?></small>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?= $statusClass ?>"><?= htmlspecialchars($schedule['Status']) ?></span>
                                            </td>
                                        </tr>
                                    <?php
                                        endwhile;
                                    } else {
                                        echo "<tr><td colspan='5' class='text-center py-4'>
                                                <i class='fas fa-calendar-times fa-2x text-muted mb-2'></i><br>
                                                No lab schedules found
                                              </td></tr>";
                                    }
                                    ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Approved & Rejected Requests (UPDATED WITH RESCHEDULE LOG TIMESTAMP) -->
                    <div class="card fade-in">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-list-check me-2"></i>
                                Recent Approved & Rejected Requests
                            </h5>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-container">
                                <table class="table table-hover mb-0">
                                    <thead>
                                        <tr>
                                            <th>Request ID</th>
                                            <th>Student</th>
                                            <th>Lab Schedule</th>
                                            <th>Status</th>
                                            <th>Decision Date</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    <?php
                                    $processedRequests = $pdo->prepare("
                                        SELECT rr.*, s.Name as StudentName, l.LabName, ls.TimeSlot, ls.Date as ScheduleDate,
                                               rl.Timestamp as DecisionTimestamp
                                        FROM RescheduleRequest rr
                                        JOIN Student s ON rr.StudentID = s.StudentID
                                        JOIN LabSchedule ls ON rr.ScheduleID = ls.ScheduleID
                                        JOIN Lab l ON ls.LabID = l.LabID
                                        LEFT JOIN RescheduleLog rl ON rr.RequestID = rl.RequestID 
                                            AND rl.Action = rr.Status
                                        WHERE ls.CoordinatorID = ? AND rr.Status IN ('Approved', 'Rejected')
                                        ORDER BY COALESCE(rl.Timestamp, rr.RequestDate) DESC
                                        LIMIT 10
                                    ");
                                    $processedRequests->execute([$coordinatorID]);
                                    
                                    if ($processedRequests->rowCount() > 0) {
                                        while ($request = $processedRequests->fetch()):
                                            $statusClass = $request['Status'] === 'Approved' ? 'success' : 'danger';
                                    ?>
                                        <tr>
                                            <td><span class="fw-bold"><?= htmlspecialchars($request['RequestID']) ?></span></td>
                                            <td>
                                                <div>
                                                    <strong><?= htmlspecialchars($request['StudentName']) ?></strong><br>
                                                    <small class="text-muted">ID: <?= htmlspecialchars($request['StudentID']) ?></small>
                                                </div>
                                            </td>
                                            <td>
                                                <div>
                                                    <strong><?= htmlspecialchars($request['LabName']) ?></strong><br>
                                                    <small class="text-muted">
                                                        <?= date('M d, Y', strtotime($request['ScheduleDate'])) ?> - 
                                                        <?= htmlspecialchars($request['TimeSlot']) ?>
                                                    </small>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?= $statusClass ?>"><?= htmlspecialchars($request['Status']) ?></span>
                                            </td>
                                            <td>
                                                <?php if ($request['DecisionTimestamp']): ?>
                                                    <span class="fw-medium"><?= date('M d, Y', strtotime($request['DecisionTimestamp'])) ?></span><br>
                                                    <small class="text-muted">
                                                        <?= date('g:i A', strtotime($request['DecisionTimestamp'])) ?>
                                                        <span class="badge bg-success ms-1">Decision Time</span>
                                                    </small>
                                                <?php else: ?>
                                                    <span class="fw-medium"><?= date('M d, Y', strtotime($request['RequestDate'])) ?></span><br>
                                                    <small class="text-muted">
                                                        <?= date('g:i A', strtotime($request['RequestDate'])) ?>
                                                        <span class="badge bg-secondary ms-1">Request Time</span>
                                                    </small>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php
                                        endwhile;
                                    } else {
                                        echo "<tr><td colspan='5' class='text-center py-4'>
                                                <i class='fas fa-inbox fa-2x text-muted mb-2'></i><br>
                                                No processed requests found
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
                    <!-- Quick Overview Chart -->
                    <div class="card mb-4 fade-in">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-chart-pie me-2"></i>
                                Quick Overview
                            </h5>
                        </div>
                        <div class="card-body">
                            <canvas id="overviewChart" width="400" height="200"></canvas>
                        </div>
                    </div>

                    <!-- Recent Activity -->
                    <div class="card mb-4 fade-in">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-clock me-2"></i>
                                Recent Activity
                            </h5>
                        </div>
                        <div class="card-body">
                            <?php
                            $recentActivity = $pdo->prepare("
                                SELECT rl.*, rr.StudentID, s.Name as StudentName
                                FROM RescheduleLog rl
                                JOIN RescheduleRequest rr ON rl.RequestID = rr.RequestID
                                JOIN Student s ON rr.StudentID = s.StudentID
                                JOIN LabSchedule ls ON rr.ScheduleID = ls.ScheduleID
                                WHERE ls.CoordinatorID = ?
                                ORDER BY rl.Timestamp DESC
                                LIMIT 5
                            ");
                            $recentActivity->execute([$coordinatorID]);
                            
                            if ($recentActivity->rowCount() > 0) {
                                while ($activity = $recentActivity->fetch()):
                            ?>
                                <div class="d-flex align-items-start mb-3 p-2 rounded" style="background: #f8f9fa;">
                                    <div class="flex-shrink-0 me-3">
                                        <i class="fas fa-history text-primary"></i>
                                    </div>
                                    <div class="flex-grow-1">
                                        <p class="mb-1 fw-medium"><?= htmlspecialchars($activity['Action']) ?></p>
                                        <small class="text-muted d-block">
                                            Request ID: <?= htmlspecialchars($activity['RequestID']) ?>
                                        </small>
                                        <small class="text-muted">
                                            <i class="fas fa-clock me-1"></i>
                                            <?= date('M d, Y g:i A', strtotime($activity['Timestamp'])) ?>
                                        </small>
                                    </div>
                                </div>
                            <?php
                                endwhile;
                            } else {
                                echo "<div class='text-center py-3'>
                                        <i class='fas fa-history fa-2x text-muted mb-2'></i><br>
                                        <span class='text-muted'>No recent activity</span>
                                      </div>";
                            }
                            ?>
                        </div>
                    </div>

                    <!-- System Notifications with Delete -->
                    <div class="card fade-in">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">
                                <i class="fas fa-bell me-2"></i>
                                System Notifications
                            </h5>
                            <button class="btn btn-sm btn-outline-primary" onclick="refreshNotifications()">
                                <i class="fas fa-sync-alt"></i>
                            </button>
                        </div>
                        <div class="card-body" id="notificationContainer">
                            <?php
                            // Get system-wide notifications or coordinator-specific ones
                            $notifications = $pdo->prepare("
                                SELECT DISTINCT n.*, s.Name as StudentName
                                FROM Notification n
                                LEFT JOIN Student s ON n.StudentID = s.StudentID
                                WHERE (n.StudentID IN (
                                    SELECT DISTINCT rr.StudentID 
                                    FROM RescheduleRequest rr 
                                    JOIN LabSchedule ls ON rr.ScheduleID = ls.ScheduleID 
                                    WHERE ls.CoordinatorID = ?
                                ) OR n.CoordinatorID = ?)
                                ORDER BY n.Timestamp DESC 
                                LIMIT 5
                            ");
                            $notifications->execute([$coordinatorID, $coordinatorID]);
                            
                            if ($notifications->rowCount() > 0) {
                                while ($notif = $notifications->fetch()):
                            ?>
                                <div class="d-flex align-items-start mb-3 p-2 rounded position-relative" style="background: #f8f9fa;">
                                    <div class="flex-shrink-0 me-3">
                                        <i class="fas fa-info-circle text-primary"></i>
                                    </div>
                                    <div class="flex-grow-1">
                                        <p class="mb-1 fw-medium"><?= htmlspecialchars($notif['Message']) ?></p>
                                        <small class="text-muted">
                                            <?php if ($notif['StudentName']): ?>
                                                <i class="fas fa-user me-1"></i><?= htmlspecialchars($notif['StudentName']) ?>
                                            <?php else: ?>
                                                <i class="fas fa-user-tie me-1"></i>System
                                            <?php endif; ?>
                                            <i class="fas fa-clock ms-2 me-1"></i>
                                            <?= date('M d, Y g:i A', strtotime($notif['Timestamp'])) ?>
                                        </small>
                                    </div>
                                    <?php if (isset($notif['NotificationID'])): ?>
                                    <form method="POST" class="position-absolute top-0 end-0 p-1">
                                        <input type="hidden" name="notification_id" value="<?= $notif['NotificationID'] ?>">
                                        <button type="submit" name="delete_notification" class="btn btn-sm btn-outline-danger" 
                                                title="Delete notification" style="font-size: 0.7rem; padding: 0.2rem 0.4rem;">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </form>
                                    <?php endif; ?>
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

    <!-- Add Schedule Modal -->
    <div class="modal fade" id="addScheduleModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Lab Schedule</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
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
                                    <option value="<?= $lab['LabID'] ?>"><?= $lab['LabName'] ?></option>
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
                                    <option value="<?= $instructor['InstructorID'] ?>"><?= $instructor['Name'] ?></option>
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
    // Working Chart.js for system overview
    <?php
    // Get data for chart showing request status distribution
    $chartData = $pdo->prepare("
        SELECT 
            COUNT(CASE WHEN rr.Status = 'Pending' THEN 1 END) as pending,
            COUNT(CASE WHEN rr.Status = 'Approved' THEN 1 END) as approved,
            COUNT(CASE WHEN rr.Status = 'Rejected' THEN 1 END) as rejected
        FROM RescheduleRequest rr
        JOIN LabSchedule ls ON rr.ScheduleID = ls.ScheduleID
        WHERE ls.CoordinatorID = ?
    ");
    $chartData->execute([$coordinatorID]);
    $overview = $chartData->fetch();
    ?>

    const ctx = document.getElementById('overviewChart').getContext('2d');
    const overviewChart = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: ['Pending', 'Approved', 'Rejected'],
            datasets: [{
                data: [
                    <?= $overview['pending'] ?? 0 ?>,
                    <?= $overview['approved'] ?? 0 ?>,
                    <?= $overview['rejected'] ?? 0 ?>
                ],
                backgroundColor: [
                    '#f59e0b',
                    '#10b981',
                    '#ef4444'
                ],
                borderWidth: 2,
                borderColor: '#ffffff'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        padding: 20,
                        usePointStyle: true
                    }
                }
            }
        }
    });

    // Notification refresh functionality
    function refreshNotifications() {
        const refreshButton = document.querySelector('.btn-outline-primary');
        const notificationContainer = document.getElementById('notificationContainer');
        
        // Show loading state
        if (refreshButton) {
            refreshButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
            refreshButton.disabled = true;
        }
        
        fetch('refresh_notifications.php')
            .then(response => response.text())
            .then(data => {
                notificationContainer.innerHTML = data;
            })
            .catch(error => {
                console.error('Error refreshing notifications:', error);
            })
            .finally(() => {
                // Reset refresh button
                if (refreshButton) {
                    refreshButton.innerHTML = '<i class="fas fa-sync-alt"></i>';
                    refreshButton.disabled = false;
                }
            });
    }

    // Auto-refresh notifications every 30 seconds
    setInterval(refreshNotifications, 30000);

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
