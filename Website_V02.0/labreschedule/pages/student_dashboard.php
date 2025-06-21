<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: ../index.php");
    exit;
}
require '../includes/db_connect.php';

// Fetch student details
$studentID = $_SESSION['ref_id'];
$stmt = $pdo->prepare("SELECT * FROM Student WHERE StudentID = ?");
$stmt->execute([$studentID]);
$student = $stmt->fetch();
?>
<?php include '../includes/header.php'; ?>
<div class="card mb-4">
    <div class="card-body">
        <h3 class="card-title">Welcome, <?= htmlspecialchars($student['Name']) ?></h3>
        <p><strong>Email:</strong> <?= htmlspecialchars($student['Email']) ?></p>
        <p><strong>Phone:</strong> <?= htmlspecialchars($student['Phone']) ?></p>
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
        WHERE ls.ScheduleID IN (
            SELECT ScheduleID FROM Attendance WHERE StudentID = ?
        )
        ORDER BY ls.Date DESC
    ");
    $schedules->execute([$studentID]);
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

<a href="reschedule_request.php" class="btn btn-warning">Request Lab Reschedule</a>
<!-- Notifications Table -->
<h4 class="mt-4">Your Notifications</h4>
<table class="table table-bordered">
    <thead>
        <tr>
            <th>Message</th>
            <th>Type</th>
            <th>Timestamp</th>
        </tr>
    </thead>
    <tbody>
    <?php
    $notif = $pdo->prepare("SELECT * FROM Notification WHERE StudentID = ? ORDER BY Timestamp DESC");
    $notif->execute([$studentID]);
    while ($row = $notif->fetch()):
    ?>
        <tr>
            <td><?= htmlspecialchars($row['Message']) ?></td>
            <td><?= htmlspecialchars($row['Type']) ?></td>
            <td><?= htmlspecialchars($row['Timestamp']) ?></td>
        </tr>
    <?php endwhile; ?>
    </tbody>
</table>

<!-- Attendance Table -->
<h4 class="mt-4">Your Attendance</h4>
<table class="table table-bordered">
    <thead>
        <tr>
            <th>Date</th>
            <th>Schedule ID</th>
            <th>Present</th>
        </tr>
    </thead>
    <tbody>
    <?php
    $att = $pdo->prepare("SELECT * FROM Attendance WHERE StudentID = ? ORDER BY Date DESC");
    $att->execute([$studentID]);
    while ($row = $att->fetch()):
    ?>
        <tr>
            <td><?= htmlspecialchars($row['Date']) ?></td>
            <td><?= htmlspecialchars($row['ScheduleID']) ?></td>
            <td><?= $row['Present'] ? 'Yes' : 'No' ?></td>
        </tr>
    <?php endwhile; ?>
    </tbody>
</table>
<?php include '../includes/footer.php'; ?>


