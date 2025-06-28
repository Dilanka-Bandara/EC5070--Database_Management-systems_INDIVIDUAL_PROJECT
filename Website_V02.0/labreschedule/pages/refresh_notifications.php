<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'coordinator') {
    exit('Unauthorized');
}

require '../includes/db_connect.php';

$coordinatorID = $_SESSION['ref_id'];

// Get recent notifications related to coordinator's schedules AND coordinator's own notifications
$notifications = $pdo->prepare("
    SELECT DISTINCT n.*, s.Name as StudentName
    FROM Notification n
    LEFT JOIN Student s ON n.StudentID = s.StudentID
    WHERE (n.StudentID IN (
        SELECT DISTINCT rr.StudentID 
        FROM RescheduleRequest rr 
        JOIN LabSchedule ls ON rr.ScheduleID = ls.ScheduleID 
        WHERE ls.CoordinatorID = ?
    ) OR n.CoordinatorID = ?)
    ORDER BY n.Timestamp DESC 
    LIMIT 5
");
$notifications->execute([$coordinatorID, $coordinatorID]);

if ($notifications->rowCount() > 0) {
    while ($notif = $notifications->fetch()):
?>
    <div class="d-flex align-items-start mb-3 p-2 rounded position-relative" style="background: #f8f9fa;">
        <div class="flex-shrink-0 me-3">
            <i class="fas fa-info-circle text-primary"></i>
        </div>
        <div class="flex-grow-1">
            <p class="mb-1 fw-medium"><?= htmlspecialchars($notif['Message']) ?></p>
            <small class="text-muted">
                <?php if ($notif['StudentName']): ?>
                    <i class="fas fa-user me-1"></i><?= htmlspecialchars($notif['StudentName']) ?>
                <?php else: ?>
                    <i class="fas fa-user-tie me-1"></i>System
                <?php endif; ?>
                <i class="fas fa-clock ms-2 me-1"></i>
                <?= date('M d, Y g:i A', strtotime($notif['Timestamp'])) ?>
            </small>
        </div>
        <?php if (isset($notif['NotificationID'])): ?>
        <form method="POST" class="position-absolute top-0 end-0 p-1" action="coordinator_dashboard.php">
            <input type="hidden" name="notification_id" value="<?= $notif['NotificationID'] ?>">
            <button type="submit" name="delete_notification" class="btn btn-sm btn-outline-danger" 
                    title="Delete notification" style="font-size: 0.7rem; padding: 0.2rem 0.4rem;">
                <i class="fas fa-times"></i>
            </button>
        </form>
        <?php endif; ?>
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
