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
?>
<?php include '../includes/header.php'; ?>
<div class="card mb-4">
    <div class="card-body">
        <h3 class="card-title">Welcome, <?= htmlspecialchars($coordinator['Name']) ?></h3>
        <p><strong>Email:</strong> <?= htmlspecialchars($coordinator['Email']) ?></p>
        <p><strong>Department:</strong> <?= htmlspecialchars($coordinator['Department']) ?></p>
    </div>
</div>

<!-- Lab Schedules Table -->
<h4>Your Lab Schedules</h4>
<table class="table table-striped">
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
        WHERE ls.CoordinatorID = ?
        ORDER BY ls.Date DESC
    ");
    $schedules->execute([$coordinatorID]);
    while ($row = $schedules->fetch()):
    ?>
        <tr>
            <td><?= htmlspecialchars($row['LabName']) ?></td>
            <td><?= htmlspecialchars($row['Location']) ?></td>
            <td><?= htmlspecialchars($row['Date']) ?></td>
            <td><?= htmlspecialchars($row['TimeSlot']) ?></td>
            <td><?= htmlspecialchars($row['Status']) ?></td>
        </tr>
    <?php endwhile; ?>
    </tbody>
</table>

<!-- Reschedule Requests Table -->
<h4 class="mt-4">Reschedule Requests</h4>
<table class="table table-bordered">
    <thead>
        <tr>
            <th>Request ID</th>
            <th>Student ID</th>
            <th>Schedule ID</th>
            <th>Date</th>
            <th>Reason</th>
            <th>Status</th>
        </tr>
    </thead>
    <tbody>
    <?php
    $requests = $pdo->prepare("SELECT * FROM RescheduleRequest ORDER BY RequestDate DESC");
    $requests->execute();
    while ($row = $requests->fetch()):
    ?>
        <tr>
            <td><?= htmlspecialchars($row['RequestID']) ?></td>
            <td><?= htmlspecialchars($row['StudentID']) ?></td>
            <td><?= htmlspecialchars($row['ScheduleID']) ?></td>
            <td><?= htmlspecialchars($row['RequestDate']) ?></td>
            <td><?= htmlspecialchars($row['Reason']) ?></td>
            <td><?= htmlspecialchars($row['Status']) ?></td>
        </tr>
    <?php endwhile; ?>
    </tbody>
</table>

<!-- Reschedule Log Table -->
<h4 class="mt-4">Reschedule Log</h4>
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
    while ($row = $logs->fetch()):
    ?>
        <tr>
            <td><?= htmlspecialchars($row['LogID']) ?></td>
            <td><?= htmlspecialchars($row['RequestID']) ?></td>
            <td><?= htmlspecialchars($row['Action']) ?></td>
            <td><?= htmlspecialchars($row['Timestamp']) ?></td>
        </tr>
    <?php endwhile; ?>
    </tbody>
</table>

<!-- All Attendance Table -->
<h4 class="mt-4">All Attendance Records</h4>
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
    while ($row = $att->fetch()):
    ?>
        <tr>
            <td><?= htmlspecialchars($row['AttendanceID']) ?></td>
            <td><?= htmlspecialchars($row['StudentID']) ?></td>
            <td><?= htmlspecialchars($row['ScheduleID']) ?></td>
            <td><?= htmlspecialchars($row['Date']) ?></td>
            <td><?= $row['Present'] ? 'Yes' : 'No' ?></td>
        </tr>
    <?php endwhile; ?>
    </tbody>
</table>
<?php include '../includes/footer.php'; ?>


