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
    $stmt = $pdo->prepare("INSERT INTO RescheduleRequest (RequestID, StudentID, ScheduleID, RequestDate, Reason, Status, ForwardedToInstructor) VALUES (?, ?, ?, NOW(), ?, 'Pending', 0)");
    $stmt->execute([$requestID, $studentID, $scheduleID, $reason]);
    // Optionally, insert a notification for the coordinator here
    $message = "<div class='alert alert-success'>Reschedule request submitted!</div>";
}
include '../includes/header.php';
?>
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
    <button type="submit" class="btn btn-primary" style="background:#7d3cff;border:0;">Submit Request</button>
</form>
<?php include '../includes/footer.php'; ?>
