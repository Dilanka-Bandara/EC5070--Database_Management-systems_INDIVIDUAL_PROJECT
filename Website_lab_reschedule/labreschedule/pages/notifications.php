<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit;
}
require '../includes/db_connect.php';
include '../includes/header.php';

$ref_id = $_SESSION['ref_id'];
$role = $_SESSION['role'];
// Example: show notifications for the logged-in user
$stmt = $pdo->prepare("SELECT * FROM Notification WHERE StudentID = ? ORDER BY Timestamp DESC");
$stmt->execute([$ref_id]);
echo "<h3>Your Notifications</h3><ul class='list-group'>";
while ($row = $stmt->fetch()) {
    echo "<li class='list-group-item'>{$row['Message']} <span class='text-muted'>[{$row['Timestamp']}]</span></li>";
}
echo "</ul>";
include '../includes/footer.php';
?>
