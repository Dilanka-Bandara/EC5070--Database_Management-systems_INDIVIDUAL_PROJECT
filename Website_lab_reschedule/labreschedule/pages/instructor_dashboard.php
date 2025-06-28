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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instructor Dashboard - EduPortal</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
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
                        <a class="nav-link active" href="instructor_dashboard.php">
                            <i class="fas fa-tachometer-alt me-1"></i>Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="schedule_view.php">
                            <i class="fas fa-calendar-alt me-1"></i>View Schedules
                        </a>
                    </li>
                    <!-- In your instructor_dashboard.php navigation -->
                    <li class="nav-item">
                        <a class="nav-link" href="instructor_manage_attendance.php">
                            <i class="fas fa-edit me-1"></i>Manage Attendance
                        </a>
                    </li>

                    <!-- In your instructor_dashboard.php navigation -->
                    <li class="nav-item">
                        <a class="nav-link" href="instructor_attendance.php">
                            <i class="fas fa-user-check me-1"></i>Mark Attendance
                        </a>
                    </li>

                </ul>
                
                
                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user-circle me-1"></i>
                            <?= htmlspecialchars($instructor['Name']) ?>
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
                            <i class="fas fa-chalkboard-teacher me-3"></i>
                            Welcome, <?= htmlspecialchars($instructor['Name']) ?>
                        </h1>
                        <p class="page-subtitle">Manage your lab schedules and track student progress</p>
                    </div>
                    <div class="col-md-4 text-end">
                        <a href="logout.php" class="btn btn-outline-danger">
                            <i class="fas fa-sign-out-alt me-2"></i>Logout
                        </a>
                    </div>
                </div>
            </div>

            <!-- Instructor Information Card -->
            <div class="card mb-4 fade-in">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-8">
                            <h3 class="card-title text-primary">Instructor Information</h3>
                            <p class="mb-1"><strong>Email:</strong> <?= htmlspecialchars($instructor['Email']) ?></p>
                            <p class="mb-0"><strong>Specialization:</strong> <?= htmlspecialchars($instructor['Specialization']) ?></p>
                        </div>
                        <div class="col-md-4 text-end">
                            <i class="fas fa-user-tie fa-4x text-primary opacity-25"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Lab Schedules Table -->
            <div class="card mb-4 fade-in">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-calendar-week me-2"></i>
                        Your Lab Schedules
                    </h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-container">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Lab Name</th>
                                    <th>Location</th>
                                    <th>Date</th>
                                    <th>Time Slot</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php
                            $schedules = $pdo->prepare("
                                SELECT ls.*, l.LabName, l.Location
                                FROM LabSchedule ls
                                JOIN Lab l ON ls.LabID = l.LabID
                                WHERE ls.InstructorID = ?
                                ORDER BY ls.Date DESC
                            ");
                            $schedules->execute([$instructorID]);
                            if ($schedules->rowCount() > 0) {
                                while ($row = $schedules->fetch()):
                            ?>
                                <tr>
                                    <td>
                                        <div>
                                            <strong><?= htmlspecialchars($row['LabName']) ?></strong><br>
                                            <small class="text-muted">ID: <?= htmlspecialchars($row['ScheduleID']) ?></small>
                                        </div>
                                    </td>
                                    <td><?= htmlspecialchars($row['Location']) ?></td>
                                    <td>
                                        <span class="fw-medium"><?= date('M d, Y', strtotime($row['Date'])) ?></span>
                                    </td>
                                    <td><?= htmlspecialchars($row['TimeSlot']) ?></td>
                                    <td>
                                        <?php
                                        $statusClass = match($row['Status']) {
                                            'Scheduled' => 'primary',
                                            'Completed' => 'success',
                                            'Cancelled' => 'danger',
                                            default => 'secondary'
                                        };
                                        ?>
                                        <span class="badge bg-<?= $statusClass ?>"><?= htmlspecialchars($row['Status']) ?></span>
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

            <!-- Additional Tables -->
            <div class="row">
                <div class="col-lg-6">
                    <!-- Reschedule Log Table -->
                    <div class="card mb-4 fade-in">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-history me-2"></i>
                                Recent Reschedule Log
                            </h5>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-container">
                                <table class="table table-hover mb-0">
                                    <thead>
                                        <tr>
                                            <th>Log ID</th>
                                            <th>Request ID</th>
                                            <th>Action</th>
                                            <th>Timestamp</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    <?php
                                    $logs = $pdo->prepare("SELECT * FROM RescheduleLog ORDER BY Timestamp DESC LIMIT 5");
                                    $logs->execute();
                                    if ($logs->rowCount() > 0) {
                                        while ($row = $logs->fetch()):
                                    ?>
                                        <tr>
                                            <td><?= htmlspecialchars($row['LogID']) ?></td>
                                            <td><?= htmlspecialchars($row['RequestID']) ?></td>
                                            <td><?= htmlspecialchars($row['Action']) ?></td>
                                            <td>
                                                <small><?= date('M d, Y g:i A', strtotime($row['Timestamp'])) ?></small>
                                            </td>
                                        </tr>
                                    <?php
                                        endwhile;
                                    } else {
                                        echo "<tr><td colspan='4' class='text-center py-4'>
                                                <i class='fas fa-history fa-2x text-muted mb-2'></i><br>
                                                No reschedule logs found
                                              </td></tr>";
                                    }
                                    ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-6">
                    <!-- Attendance Records -->
                    <div class="card fade-in">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-clipboard-check me-2"></i>
                                Recent Attendance Records
                            </h5>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-container">
                                <table class="table table-hover mb-0">
                                    <thead>
                                        <tr>
                                            <th>Student ID</th>
                                            <th>Schedule ID</th>
                                            <th>Date</th>
                                            <th>Present</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    <?php
                                    $att = $pdo->query("SELECT * FROM Attendance ORDER BY Date DESC LIMIT 5");
                                    if ($att->rowCount() > 0) {
                                        while ($row = $att->fetch()):
                                    ?>
                                        <tr>
                                            <td><?= htmlspecialchars($row['StudentID']) ?></td>
                                            <td><?= htmlspecialchars($row['ScheduleID']) ?></td>
                                            <td>
                                                <small><?= date('M d, Y', strtotime($row['Date'])) ?></small>
                                            </td>
                                            <td>
                                                <?= $row['Present'] ? '<span class="badge bg-success">Yes</span>' : '<span class="badge bg-danger">No</span>' ?>
                                            </td>
                                        </tr>
                                    <?php
                                        endwhile;
                                    } else {
                                        echo "<tr><td colspan='4' class='text-center py-4'>
                                                <i class='fas fa-clipboard fa-2x text-muted mb-2'></i><br>
                                                No attendance records found
                                              </td></tr>";
                                    }
                                    ?>
                                    </tbody>
                                </table>
                            </div>
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
</body>
</html>
