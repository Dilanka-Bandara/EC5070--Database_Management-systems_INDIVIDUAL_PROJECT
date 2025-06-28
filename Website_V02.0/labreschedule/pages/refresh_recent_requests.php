<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    exit('Unauthorized');
}

require '../includes/db_connect.php';

$studentID = $_SESSION['ref_id'];

$recentRequests = $pdo->prepare("
    SELECT rr.*, ls.Date as ScheduleDate, ls.TimeSlot, l.LabName 
    FROM RescheduleRequest rr 
    LEFT JOIN LabSchedule ls ON rr.ScheduleID = ls.ScheduleID 
    LEFT JOIN Lab l ON ls.LabID = l.LabID 
    WHERE rr.StudentID = ? 
    ORDER BY rr.RequestDate DESC 
    LIMIT 5
");
$recentRequests->execute([$studentID]);
?>

<table class="table table-hover mb-0">
    <thead>
        <tr>
            <th>Request ID</th>
            <th>Schedule</th>
            <th>Date</th>
            <th>Status</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
    <?php
    if ($recentRequests->rowCount() > 0) {
        while ($req = $recentRequests->fetch()):
            $statusClass = match($req['Status']) {
                'Approved' => 'success',
                'Rejected' => 'danger',
                default => 'warning'
            };
    ?>
        <tr>
            <td>
                <span class="fw-bold"><?= htmlspecialchars($req['RequestID']) ?></span>
            </td>
            <td>
                <div>
                    <strong><?= htmlspecialchars($req['LabName'] ?? 'N/A') ?></strong><br>
                    <small class="text-muted"><?= htmlspecialchars($req['TimeSlot'] ?? 'N/A') ?></small>
                </div>
            </td>
            <td>
                <span class="fw-medium"><?= date('M d, Y', strtotime($req['RequestDate'])) ?></span><br>
                <small class="text-muted"><?= date('g:i A', strtotime($req['RequestDate'])) ?></small>
            </td>
            <td>
                <span class="badge bg-<?= $statusClass ?>"><?= htmlspecialchars($req['Status']) ?></span>
            </td>
            <td>
                <button class="btn btn-sm btn-outline-primary" onclick="viewRequest('<?= $req['RequestID'] ?>')">
                    <i class="fas fa-eye"></i>
                </button>
            </td>
        </tr>
    <?php
        endwhile;
    } else {
        echo "<tr><td colspan='5' class='text-center py-4'>
                <i class='fas fa-inbox fa-2x text-muted mb-2'></i><br>
                No reschedule requests found
              </td></tr>";
    }
    ?>
    </tbody>
</table>
