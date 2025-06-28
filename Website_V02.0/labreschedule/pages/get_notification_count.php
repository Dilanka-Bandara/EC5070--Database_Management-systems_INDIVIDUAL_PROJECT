<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header('Content-Type: application/json');
    exit(json_encode(['count' => 0]));
}

require '../includes/db_connect.php';

$studentID = $_SESSION['ref_id'];

// Count unread notifications (you can modify this logic as needed)
$stmt = $pdo->prepare("SELECT COUNT(*) as count FROM Notification WHERE StudentID = ?");
$stmt->execute([$studentID]);
$result = $stmt->fetch();

header('Content-Type: application/json');
echo json_encode(['count' => $result['count']]);
?>
