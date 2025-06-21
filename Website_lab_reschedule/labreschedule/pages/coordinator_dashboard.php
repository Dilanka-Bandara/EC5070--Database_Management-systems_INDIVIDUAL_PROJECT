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

// Fetch labs and instructors for dropdowns
$labs = $pdo->query("SELECT LabID, LabName FROM Lab ORDER BY LabName ASC");
$instructors = $pdo->query("SELECT InstructorID, Name FROM LabInstructor ORDER BY Name ASC");

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

            // Send notification to student - DO NOT include NotificationID, let it auto-increment
            $notificationMessage = "Your reschedule request (ID: $requestID) for lab schedule $scheduleID has been $status by the coordinator.";
            $notif_student = $pdo->prepare("INSERT INTO Notification (Message, Timestamp, Type, StudentID) VALUES (?, NOW(), ?, ?)");
            $notif_student->execute([$notificationMessage, 'reschedule_response', $studentID]);

            // Log the action - DO NOT include LogID, let it auto-increment
            $log = $pdo->prepare("INSERT INTO RescheduleLog (RequestID, Action, Timestamp) VALUES (?, ?, NOW())");
            $log->execute([$requestID, $status]);
        }
        
    } catch (PDOException $e) {
        // Handle error silently - main functionality still works
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
        // Safeguard: Check if ScheduleID already exists
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>LabReschedule - Coordinator Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: linear-gradient(135deg, #fceed1 0%, #ffe6f2 100%); font-family: 'Poppins', sans-serif; padding-top: 30px; padding-bottom: 30px;}
        .container { max-width: 1200px; }
        .dashboard-title {
            color: #4B0082; font-weight: 700; margin-bottom: 25px; text-align: center;
            background: rgba(0, 204, 153, 0.1); padding: 10px; border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.05);
        }
        .welcome-card {
            background: #fff; border-radius: 15px; box-shadow: 0 8px 16px rgba(0,0,0,0.1);
            border: none; overflow: hidden; position: relative; margin-bottom: 30px;
        }
        .welcome-card::before {
            content: ''; position: absolute; top: 0; left: 0; width: 100%; height: 4px;
            background: linear-gradient(90deg, #00CC99, #FF355E);
        }
        .welcome-card h3 { color: #4B0082; font-weight: 600; }
        .section-title {
            color: #4B0082; font-weight: 500; margin-bottom: 15px;
            border-left: 4px solid #00CC99; padding-left: 10px;
        }
        .form-card { background: #fff; border-radius: 15px; box-shadow: 0 8px 16px rgba(0,0,0,0.1); border: none; padding: 25px; margin-bottom: 30px;}
        .table { background: #fff; border-radius: 10px; overflow: hidden; box-shadow: 0 4px 8px rgba(0,0,0,0.05);}
        .table thead { background: #4B0082; color: #fff; }
        .table tbody tr:hover { background: rgba(0,204,153,0.07);}
        .badge.bg-primary { background: #4B0082 !important; }
        .badge.bg-success { background: #00CC99 !important; }
        .badge.bg-danger { background: #FF355E !important; }
        .btn-primary { background: #FF355E; border: none; color: #fff; font-weight: 600; padding: 10px 20px; border-radius: 30px; }
    </style>
</head>
<body>
<div class="container">

<!-- LOGOUT BUTTON -->
<div class="d-flex justify-content-end mb-3">
    <a href="logout.php" class="btn btn-outline-danger">Logout</a>
</div>

<h1 class="dashboard-title">Coordinator Dashboard</h1>
<?= $message ?>

<!-- Coordinator Info -->
<div class="welcome-card mb-4">
    <div class="card-body">
        <h3 class="card-title">Welcome, <?= htmlspecialchars($coordinator['Name']) ?></h3>
        <p class="mb-1"><strong>Email:</strong> <?= htmlspecialchars($coordinator['Email']) ?></p>
        <p class="mb-0"><strong>Department:</strong> <?= htmlspecialchars($coordinator['Department']) ?></p>
    </div>
</div>

<!-- Add New Lab Schedule Form -->
<div class="form-card">
    <h4 class="section-title">Add New Lab Schedule</h4>
    <form method="post">
        <div class="row">
            <div class="col-md-4 mb-3">
                <label class="form-label">Schedule ID</label>
                <input type="text" name="scheduleID" class="form-control" required placeholder="e.g., SCH01">
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label">Select Lab</label>
                <select name="labID" class="form-select" required>
                    <?php foreach ($labs as $lab): ?>
                    <option value="<?= $lab['LabID'] ?>"><?= $lab['LabName'] ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label">Select Instructor</label>
                <select name="instructorID" class="form-select" required>
                    <?php foreach ($instructors as $instructor): ?>
                    <option value="<?= $instructor['InstructorID'] ?>"><?= $instructor['Name'] ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="row">
            <div class="col-md-4 mb-3">
                <label class="form-label">Date</label>
                <input type="date" name="date" class="form-control" required>
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label">Time Slot</label>
                <input type="text" name="timeSlot" class="form-control" required placeholder="e.g., 9-11 AM">
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label">Status</label>
                <select name="status" class="form-select" required>
                    <option value="Scheduled">Scheduled</option>
                    <option value="Cancelled">Cancelled</option>
                    <option value="Completed">Completed</option>
                </select>
            </div>
        </div>
        <button type="submit" name="add_lab" class="btn btn-primary">Add Schedule</button>
    </form>
</div>

<!-- Lab Schedules Table -->
<h4 class="section-title">Your Lab Schedules</h4>
<div class="table-responsive mb-4">
    <table class="table table-striped table-hover">
        <thead>
            <tr>
                <th>ScheduleID</th>
                <th>LabID</th>
                <th>CoordinatorID</th>
                <th>TimeSlot</th>
                <th>Date</th>
                <th>Status</th>
                <th>InstructorID</th>
            </tr>
        </thead>
        <tbody>
        <?php
        $schedules = $pdo->prepare("
            SELECT * FROM LabSchedule WHERE CoordinatorID = ? ORDER BY Date DESC
        ");
        $schedules->execute([$coordinatorID]);
        if ($schedules->rowCount() > 0) {
            foreach ($schedules as $row) {
                echo "<tr>
                    <td>".htmlspecialchars($row['ScheduleID'])."</td>
                    <td>".htmlspecialchars($row['LabID'])."</td>
                    <td>".htmlspecialchars($row['CoordinatorID'])."</td>
                    <td>".htmlspecialchars($row['TimeSlot'])."</td>
                    <td>".htmlspecialchars($row['Date'])."</td>
                    <td><span class='badge bg-primary'>".htmlspecialchars($row['Status'])."</span></td>
                    <td>".htmlspecialchars($row['InstructorID'])."</td>
                </tr>";
            }
        } else {
            echo "<tr><td colspan='7' class='text-center'>No lab schedules found.</td></tr>";
        }
        ?>
        </tbody>
    </table>
</div>

<!-- Reschedule Requests Table (with Accept/Reject) -->
<h4 class="section-title mt-4">Reschedule Requests</h4>
<div class="table-responsive mb-4">
    <table class="table table-bordered">
        <thead>
            <tr>
                <th>Request ID</th>
                <th>Student ID</th>
                <th>Schedule ID</th>
                <th>Date</th>
                <th>Reason</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php
        $requests = $pdo->prepare("
            SELECT * FROM RescheduleRequest
            WHERE ScheduleID IN (SELECT ScheduleID FROM LabSchedule WHERE CoordinatorID = ?)
            ORDER BY RequestDate DESC
        ");
        $requests->execute([$coordinatorID]);
        if ($requests->rowCount() > 0) {
            foreach ($requests as $row) {
                echo "<tr>
                    <td>".htmlspecialchars($row['RequestID'])."</td>
                    <td>".htmlspecialchars($row['StudentID'])."</td>
                    <td>".htmlspecialchars($row['ScheduleID'])."</td>
                    <td>".htmlspecialchars($row['RequestDate'])."</td>
                    <td>".htmlspecialchars($row['Reason'])."</td>
                    <td><span class='badge bg-warning text-dark'>".htmlspecialchars($row['Status'])."</span></td>
                    <td>";
                if ($row['Status'] == 'Pending') {
                    echo "<form action='' method='POST' class='d-inline'>
                        <input type='hidden' name='request_id' value='".htmlspecialchars($row['RequestID'])."'>
                        <button type='submit' name='decision' value='Approved' class='btn btn-success btn-sm'>Accept</button>
                        <button type='submit' name='decision' value='Rejected' class='btn btn-danger btn-sm'>Reject</button>
                    </form>";
                } else {
                    echo "<span class='text-muted'>No action</span>";
                }
                echo "</td></tr>";
            }
        } else {
            echo "<tr><td colspan='7' class='text-center'>No reschedule requests found.</td></tr>";
        }
        ?>
        </tbody>
    </table>
</div>

<!-- Reschedule Log Table -->
<h4 class="section-title mt-4">Reschedule Log</h4>
<div class="table-responsive mb-4">
    <table class="table table-bordered">
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
        $logs = $pdo->prepare("SELECT * FROM RescheduleLog ORDER BY Timestamp DESC");
        $logs->execute();
        if ($logs->rowCount() > 0) {
            foreach ($logs as $row) {
                echo "<tr>
                    <td>".htmlspecialchars($row['LogID'])."</td>
                    <td>".htmlspecialchars($row['RequestID'])."</td>
                    <td>".htmlspecialchars($row['Action'])."</td>
                    <td>".htmlspecialchars($row['Timestamp'])."</td>
                </tr>";
            }
        } else {
            echo "<tr><td colspan='4' class='text-center'>No reschedule logs found.</td></tr>";
        }
        ?>
        </tbody>
    </table>
</div>

<!-- All Attendance Table -->
<h4 class="section-title mt-4">All Attendance Records</h4>
<div class="table-responsive">
    <table class="table table-bordered">
        <thead>
            <tr>
                <th>Attendance ID</th>
                <th>Student ID</th>
                <th>Schedule ID</th>
                <th>Date</th>
                <th>Present</th>
            </tr>
        </thead>
        <tbody>
        <?php
        $att = $pdo->query("SELECT * FROM Attendance ORDER BY Date DESC");
        if ($att->rowCount() > 0) {
            foreach ($att as $row) {
                echo "<tr>
                    <td>".htmlspecialchars($row['AttendanceID'])."</td>
                    <td>".htmlspecialchars($row['StudentID'])."</td>
                    <td>".htmlspecialchars($row['ScheduleID'])."</td>
                    <td>".htmlspecialchars($row['Date'])."</td>
                    <td>".($row['Present'] ? '<span class="badge bg-success">Yes</span>' : '<span class="badge bg-danger">No</span>')."</td>
                </tr>";
            }
        } else {
            echo "<tr><td colspan='5' class='text-center'>No attendance records found.</td></tr>";
        }
        ?>
        </tbody>
    </table>
</div>

</div> <!-- end container -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
