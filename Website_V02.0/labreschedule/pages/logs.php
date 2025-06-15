<?php
include '../includes/header.php';
include '../includes/db_connect.php';

echo "<h2>Reschedule Logs</h2>";

$stmt = $pdo->query("SELECT RescheduleLog.LogID, RescheduleLog.Action, RescheduleLog.Timestamp, RescheduleRequest.RequestID
    FROM RescheduleLog
    JOIN RescheduleRequest ON RescheduleLog.RequestID = RescheduleRequest.RequestID
    ORDER BY RescheduleLog.Timestamp DESC");

echo "<table border='1' cellpadding='8' style='width:100%;border-collapse:collapse;'>";
echo "<tr><th>Log ID</th><th>Request ID</th><th>Action</th><th>Timestamp</th></tr>";
while ($row = $stmt->fetch()) {
    echo "<tr>
            <td>{$row['LogID']}</td>
            <td>{$row['RequestID']}</td>
            <td>{$row['Action']}</td>
            <td>{$row['Timestamp']}</td>
          </tr>";
}
echo "</table>";

include '../includes/footer.php';
?>
