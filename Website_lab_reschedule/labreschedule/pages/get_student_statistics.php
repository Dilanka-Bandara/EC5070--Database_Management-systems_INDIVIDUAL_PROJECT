<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header('Content-Type: application/json');
    exit(json_encode(['error' => 'Unauthorized']));
}

require '../includes/db_connect.php';
$studentID = $_SESSION['ref_id'];

$stats = [];

$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM RescheduleRequest WHERE StudentID = ?");
$stmt->execute([$studentID]);
$stats['total_requests'] = $stmt->fetch()['total'];

$stmt = $pdo->prepare("SELECT COUNT(*) as pending FROM RescheduleRequest WHERE StudentID = ? AND Status = 'Pending'");
$stmt->execute([$studentID]);
$stats['pending_requests'] = $stmt->fetch()['pending'];

$stmt = $pdo->prepare("SELECT COUNT(*) as approved FROM RescheduleRequest WHERE StudentID = ? AND Status = 'Approved'");
$stmt->execute([$studentID]);
$stats['approved_requests'] = $stmt->fetch()['approved'];

$stmt = $pdo->prepare("SELECT COUNT(*) as unread FROM Notification WHERE StudentID = ?");
$stmt->execute([$studentID]);
$stats['unread_notifications'] = $stmt->fetch()['unread'];

header('Content-Type: application/json');
echo json_encode($stats);
?>
