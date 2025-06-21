<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: ../index.php");
    exit;
}
require '../includes/db_connect.php';

$message = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // This part of the code remains the same as it correctly handles the insert.
    // The error is in the data being sent, not the insertion logic itself.
    $studentID = $_SESSION['ref_id'];
    $scheduleID = $_POST['scheduleID']; // Now this will be a valid ID from the dropdown
    $reason = $_POST['reason'];
    $requestID = uniqid('RQ');

    $stmt = $pdo->prepare("INSERT INTO RescheduleRequest (RequestID, StudentID, ScheduleID, RequestDate, Reason, Status, ForwardedToInstructor) VALUES (?, ?, ?, NOW(), ?, 'Pending', 0)");
    $stmt->execute([$requestID, $studentID, $scheduleID, $reason]);

    // ... (rest of your notification and logging code) ...

    $message = "<div class='alert alert-success'>Reschedule request submitted!</div>";
}

// Fetch available lab schedules to populate the dropdown
$schedules_stmt = $pdo->query("
    SELECT ls.ScheduleID, l.LabName, ls.Date, ls.TimeSlot
    FROM LabSchedule ls
    JOIN Lab l ON ls.LabID = l.LabID
    WHERE ls.Status = 'Scheduled'
    ORDER BY ls.Date ASC
");
?>
<?php include '../includes/header.php'; ?>
<h3>Request Lab Reschedule</h3>
<?= $message ?>
<form method="post">
    <div class="mb-3">
        <label for="scheduleID" class="form-label">Select Lab Schedule to Reschedule</label>
        <select id="scheduleID" name="scheduleID" class="form-select" required>
            <option value="" disabled selected>-- Choose a Lab Schedule --</option>
            <?php while ($schedule = $schedules_stmt->fetch()): ?>
                <option value="<?= htmlspecialchars($schedule['ScheduleID']) ?>">
                    <?= htmlspecialchars($schedule['ScheduleID']) ?> -
                    <?= htmlspecialchars($schedule['LabName']) ?> on
                    <?= htmlspecialchars($schedule['Date']) ?> at
                    <?= htmlspecialchars($schedule['TimeSlot']) ?>
                </option>
            <?php endwhile; ?>
        </select>
    </div>
    <div class="mb-3">
        <label for="reason" class="form-label">Reason for Rescheduling</label>
        <textarea id="reason" name="reason" class="form-control" required></textarea>
    </div>
    <button type="submit" class="btn btn-primary">Submit Request</button>
</form>
<?php include '../includes/footer.php'; ?>
