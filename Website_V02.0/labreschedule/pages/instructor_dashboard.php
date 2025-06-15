<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'instructor') {
    header("Location: ../index.php");
    exit;
}
include '../includes/header.php';
?>
<h2 class="mb-4">Welcome, Lab Instructor <?= htmlspecialchars($_SESSION['ref_id']) ?></h2>
<a href="notifications.php" class="btn btn-info mb-3">Notifications</a>
<!-- Here you can show instructor's pending reschedule requests, etc. -->
<?php include '../includes/footer.php'; ?>
