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

// Defensive Check: Ensure instructor data was found
if (!$instructor) {
    die("Error: Could not find instructor details for ID: " . htmlspecialchars($instructorID));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <!-- Dynamic Page Title for Browser Tab -->
    <title>LabReschedule - Lab Instructor Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Custom CSS for Colorful and Professional Design -->
    <style>
        body {
            background: linear-gradient(135deg, #fceed1 0%, #ffe6f2 100%);
            font-family: 'Poppins', sans-serif;
        }
        .welcome-card {
            background: #ffffff;
            border-radius: 15px;
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1);
            border: none;
            overflow: hidden;
            position: relative;
        }
        .welcome-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: linear-gradient(90deg, #00CC99, #FF355E);
        }
        .welcome-card h3 {
            color: #4B0082;
            font-weight: 600;
        }
        .section-title {
            color: #4B0082;
            font-weight: 500;
            margin-bottom: 15px;
            border-left: 4px solid #00CC99;
            padding-left: 10px;
        }
        .table {
            background: #ffffff;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.05);
        }
        .table thead {
            background: #4B0082;
            color: #ffffff;
        }
        .table tbody tr:hover {
            background: rgba(0, 204, 153, 0.1);
        }
        .dashboard-title {
            color: #4B0082;
            font-weight: 700;
            margin-bottom: 20px;
            text-align: center;
            background: rgba(0, 204, 153, 0.1);
            padding: 10px;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.05);
        }
    </style>
</head>
<body>
<?php include '../includes/header.php'; ?>

<!-- Main Dashboard Title at the Top -->
<h1 class="dashboard-title">Lab Instructor Dashboard</h1>

<!-- Instructor Information Card -->
<div class="welcome-card mb-4">
    <div class="card-body">
        <h3 class="card-title">Welcome, <?= htmlspecialchars($instructor['Name']) ?></h3>
        <p class="mb-1"><strong>Email:</strong> <?= htmlspecialchars($instructor['Email']) ?></p>
        <p class="mb-0"><strong>Specialization:</strong> <?= htmlspecialchars($instructor['Specialization']) ?></p>
    </div>
</div>

<!-- Lab Schedules Table -->
<h4 class="section-title">Your Lab Schedules</h4>
<div class="table-responsive mb-4">
    <table class="table table-striped table-hover">
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
                <td><?= htmlspecialchars($row['LabName']) ?></td>
                <td><?= htmlspecialchars($row['Location']) ?></td>
                <td><?= htmlspecialchars($row['Date']) ?></td>
                <td><?= htmlspecialchars($row['TimeSlot']) ?></td>
                <td><span class="badge bg-primary"><?= htmlspecialchars($row['Status']) ?></span></td>
            </tr>
        <?php
            endwhile;
        } else {
            echo "<tr><td colspan='5' class='text-center'>No lab schedules found.</td></tr>";
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
            while ($row = $logs->fetch()):
        ?>
            <tr>
                <td><?= htmlspecialchars($row['LogID']) ?></td>
                <td><?= htmlspecialchars($row['RequestID']) ?></td>
                <td><?= htmlspecialchars($row['Action']) ?></td>
                <td><?= htmlspecialchars($row['Timestamp']) ?></td>
            </tr>
        <?php
            endwhile;
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
            while ($row = $att->fetch()):
        ?>
            <tr>
                <td><?= htmlspecialchars($row['AttendanceID']) ?></td>
                <td><?= htmlspecialchars($row['StudentID']) ?></td>
                <td><?= htmlspecialchars($row['ScheduleID']) ?></td>
                <td><?= htmlspecialchars($row['Date']) ?></td>
                <td><?= $row['Present'] ? '<span class="badge bg-success">Yes</span>' : '<span class="badge bg-danger">No</span>' ?></td>
            </tr>
        <?php
            endwhile;
        } else {
            echo "<tr><td colspan='5' class='text-center'>No attendance records found.</td></tr>";
        }
        ?>
        </tbody>
    </table>
</div>

<?php include '../includes/footer.php'; ?>
</body>
</html>
