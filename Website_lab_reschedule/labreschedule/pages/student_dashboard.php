<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: ../index.php");
    exit;
}
include '../includes/header.php';
?>
<h2 class="mb-4">Welcome, Student <?= htmlspecialchars($_SESSION['ref_id']) ?></h2>
<a href="reschedule_request.php" class="btn btn-warning mb-3">Request Lab Reschedule</a>
<a href="notifications.php" class="btn btn-info mb-3">Notifications</a>
<!-- Here you can show student's schedules, requests, etc. -->
<?php include '../includes/footer.php'; ?>
