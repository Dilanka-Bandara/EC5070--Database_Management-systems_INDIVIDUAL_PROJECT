<?php
session_start();
// Security check: Only coordinators can access this
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'coordinator') {
    header("Location: ../index.php");
    exit;
}

require '../includes/db_connect.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['request_id']) && isset($_POST['decision'])) {
    $requestID = $_POST['request_id'];
    $decision = $_POST['decision']; // 'Approved' or 'Rejected'

    // --- 1. Update the RescheduleRequest status ---
    $update_stmt = $pdo->prepare("UPDATE RescheduleRequest SET Status = ?, DecisionDate = NOW() WHERE RequestID = ?");
    $update_stmt->execute([$decision, $requestID]);

    // --- 2. Create a notification for the student ---
    // First, get the StudentID from the request
    $student_stmt = $pdo->prepare("SELECT StudentID FROM RescheduleRequest WHERE RequestID = ?");
    $student_stmt->execute([$requestID]);
    $student = $student_stmt->fetch();

    if ($student) {
        $studentID = $student['StudentID'];
        $message = "Your reschedule request (ID: {$requestID}) has been " . strtolower($decision) . ".";

        $notif_stmt = $pdo->prepare("INSERT INTO Notification (NotificationID, Message, Timestamp, Type, StudentID) VALUES (?, ?, NOW(), ?, ?)");
        $notif_stmt->execute([uniqid('N'), $message, 'decision', $studentID]);
    }

    // --- 3. Log the action in RescheduleLog ---
    $log_action = "Decision made: " . $decision;
    $log_stmt = $pdo->prepare("INSERT INTO RescheduleLog (LogID, RequestID, Action, Timestamp) VALUES (?, ?, ?, NOW())");
    $log_stmt->execute([uniqid('LOG'), $requestID, $log_action]);


    // --- 4. Redirect back to the coordinator dashboard ---
    header("Location: coordinator_dashboard.php?msg=Request+processed+successfully");
    exit;

} else {
    // If accessed incorrectly, redirect
    header("Location: coordinator_dashboard.php");
    exit;
}
?>
