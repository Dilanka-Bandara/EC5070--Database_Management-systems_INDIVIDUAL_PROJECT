<?php
session_start();
header('Content-Type: application/json'); // Set header for JSON response

// Security check: Ensure user is a logged-in coordinator
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'coordinator') {
    http_response_code(403); // Forbidden
    echo json_encode(['success' => false, 'error' => 'Unauthorized access.']);
    exit;
}

// Check if it's a POST request with required data
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['request_id']) || !isset($_POST['decision'])) {
    http_response_code(400); // Bad Request
    echo json_encode(['success' => false, 'error' => 'Invalid request.']);
    exit;
}

require '../includes/db_connect.php';

$requestID = $_POST['request_id'];
$decision = $_POST['decision'];
$status = ($decision === 'Approved') ? 'Approved' : 'Rejected';
$decisionTime = !empty($_POST['decision_time']) ? $_POST['decision_time'] : date('Y-m-d H:i:s');
$coordinatorID = $_SESSION['ref_id'];

try {
    // Start transaction for data consistency
    $pdo->beginTransaction();
    
    // Get student and schedule details for notifications
    $getDetailsStmt = $pdo->prepare("
        SELECT rr.StudentID, s.Name as StudentName, l.LabName, ls.TimeSlot, ls.Date as ScheduleDate
        FROM RescheduleRequest rr
        JOIN LabSchedule ls ON rr.ScheduleID = ls.ScheduleID
        JOIN Lab l ON ls.LabID = l.LabID
        JOIN Student s ON rr.StudentID = s.StudentID
        WHERE rr.RequestID = ? AND rr.Status = 'Pending' FOR UPDATE
    ");
    $getDetailsStmt->execute([$requestID]);
    $details = $getDetailsStmt->fetch();
    
    if (!$details) {
        throw new Exception("Request not found or has already been processed.");
    }

    $studentID = $details['StudentID'];

    // 1. Update the RescheduleRequest table
    $updateStmt = $pdo->prepare("UPDATE RescheduleRequest SET Status = ?, DecisionDate = ? WHERE RequestID = ? AND Status = 'Pending'");
    $updateResult = $updateStmt->execute([$status, $decisionTime, $requestID]);
    
    if (!$updateResult || $updateStmt->rowCount() === 0) {
        throw new Exception("Failed to update the request status. It might have been processed by another coordinator.");
    }
        
    // 2. Insert into the RescheduleLog table
    $logStmt = $pdo->prepare("INSERT INTO RescheduleLog (RequestID, Action, Timestamp) VALUES (?, ?, ?)");
    $logStmt->execute([$requestID, $status, $decisionTime]);

    // 3. Insert notification for the student
    $decisionTimeFormatted = date('M d, Y \a\t g:i A', strtotime($decisionTime));
    $labDetails = "{$details['LabName']} on " . date('M d, Y', strtotime($details['ScheduleDate'])) . " at {$details['TimeSlot']}";
    $studentMessage = "Your reschedule request (ID: $requestID) for $labDetails was $status on $decisionTimeFormatted.";
    $studentNotifStmt = $pdo->prepare("INSERT INTO Notification (Message, Timestamp, Type, StudentID) VALUES (?, ?, ?, ?)");
    $studentNotifStmt->execute([$studentMessage, $decisionTime, 'reschedule_response', $studentID]);
    
    // Commit the transaction
    $pdo->commit();
    
    // Send success response back to the browser
    echo json_encode(['success' => true, 'message' => "Request $requestID has been $status."]);

} catch (Exception $e) {
    // If anything goes wrong, roll back all database changes
    if ($pdo->inTransaction()) {
        $pdo->rollback();
    }
    error_log("API Error: " . $e->getMessage()); // Log error for debugging
    http_response_code(500); // Internal Server Error
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
