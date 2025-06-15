<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: ../index.php");
    exit;
}
require '../includes/db_connect.php';

$message = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $studentID = $_SESSION['ref_id'];
    $scheduleID = $_POST['scheduleID'];
    $reason = $_POST['reason'];
    $requestID = uniqid('RQ');

    // Insert reschedule request
    $stmt = $pdo->prepare("INSERT INTO RescheduleRequest (RequestID, StudentID, ScheduleID, RequestDate, Reason, Status, ForwardedToInstructor) VALUES (?, ?, ?, NOW(), ?, 'Pending', 0)");
    $stmt->execute([$requestID, $studentID, $scheduleID, $reason]);

    // Insert notification for student
    $notif_student = $pdo->prepare("INSERT INTO Notification (NotificationID, Message, Timestamp, Type, StudentID) VALUES (?, ?, NOW(), ?, ?)");
    $notif_student->execute([uniqid('N'), "Your reschedule request has been submitted.", 'reschedule', $studentID]);

    // Find the coordinator for this schedule
    $coordinatorID = null;
    $coor_stmt = $pdo->prepare("SELECT CoordinatorID FROM LabSchedule WHERE ScheduleID = ?");
    $coor_stmt->execute([$scheduleID]);
    if ($row = $coor_stmt->fetch()) {
        $coordinatorID = $row['CoordinatorID'];
        // Insert notification for coordinator
        $notif_coord = $pdo->prepare("INSERT INTO Notification (NotificationID, Message, Timestamp, Type, CoordinatorID) VALUES (?, ?, NOW(), ?, ?)");
        $notif_coord->execute([uniqid('N'), "A student has submitted a reschedule request.", 'reschedule', $coordinatorID]);
    }

    // Insert into RescheduleLog
    $log = $pdo->prepare("INSERT INTO RescheduleLog (LogID, RequestID, Action, Timestamp) VALUES (?, ?, ?, NOW())");
    $log->execute([uniqid('LOG'), $requestID, 'Submitted']);

    $message = "<div class='alert alert-success'>Reschedule request submitted!</div>";
}
?>
<?php include '../includes/header.php'; ?>
<h3>Request Lab Reschedule</h3>
<?= $message ?>
<form method="post">
    <div class="mb-3">
        <label>Schedule ID</label>
        <input type="text" name="scheduleID" class="form-control" required>
    </div>
    <div class="mb-3">
        <label>Reason</label>
        <textarea name="reason" class="form-control" required></textarea>
    </div>
    <button type="submit" class="btn btn-primary">Submit Request</button>
</form>
<?php include '../includes/footer.php'; ?>
