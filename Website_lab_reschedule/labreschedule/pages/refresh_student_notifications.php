<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    exit('Unauthorized');
}

require '../includes/db_connect.php';

$studentID = $_SESSION['ref_id'];

$notifications = $pdo->prepare("
    SELECT * FROM Notification 
    WHERE StudentID = ? 
    ORDER BY Timestamp DESC 
    LIMIT 5
");
$notifications->execute([$studentID]);

if ($notifications->rowCount() > 0) {
    while ($notif = $notifications->fetch()):
        $timeAgo = time() - strtotime($notif['Timestamp']);
        $timeDisplay = $timeAgo < 60 ? 'Just now' : date('M d, g:i A', strtotime($notif['Timestamp']));
?>
    <div class="d-flex align-items-start mb-3 p-2 rounded" 
         style="background: #f8f9fa; border-left: 4px solid #007bff;">
        <div class="flex-shrink-0 me-3">
            <i class="fas fa-<?= $notif['Type'] == 'reschedule_response' ? 'check-circle' : 'info-circle' ?> text-primary"></i>
        </div>
        <div class="flex-grow-1">
            <div class="d-flex justify-content-between mb-1">
                <span class="badge bg-<?= $notif['Type'] == 'reschedule_response' ? 'success' : 'info' ?>">
                    <?= $notif['Type'] == 'reschedule_response' ? 'Decision Update' : 'General' ?>
                </span>
                <small class="text-muted"><?= $timeDisplay ?></small>
            </div>
            <p class="mb-0 fw-medium"><?= htmlspecialchars($notif['Message']) ?></p>
        </div>
    </div>
<?php
    endwhile;
} else {
    echo "<div class='text-center py-3'>
            <i class='fas fa-bell-slash fa-2x text-muted mb-2'></i><br>
            <span class='text-muted'>No notifications</span>
          </div>";
}
?>
