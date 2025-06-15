<?php
include '../includes/header.php';
include '../includes/db_connect.php';

$message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $studentID = $_POST['studentID'];
    $scheduleID = $_POST['scheduleID'];
    $reason = $_POST['reason'];
    $requestID = uniqid('RQ');
    $stmt = $pdo->prepare("INSERT INTO RescheduleRequest (RequestID, StudentID, ScheduleID, RequestDate, Reason, Status, ForwardedToInstructor) VALUES (?, ?, ?, NOW(), ?, 'Pending', 0)");
    if ($stmt->execute([$requestID, $studentID, $scheduleID, $reason])) {
        $message = "<p class='success'>Request submitted successfully!</p>";
    } else {
        $message = "<p class='error'>Error submitting request.</p>";
    }
}
?>
<h2>Request Lab Reschedule</h2>
<?php echo $message; ?>
<form method="post">
    <label>Student ID: <input type="text" name="studentID" required></label>
    <label>Schedule ID: <input type="text" name="scheduleID" required></label>
    <label>Reason:<br><textarea name="reason" required></textarea></label>
    <button type="submit">Submit Request</button>
</form>
<?php include '../includes/footer.php'; ?>
