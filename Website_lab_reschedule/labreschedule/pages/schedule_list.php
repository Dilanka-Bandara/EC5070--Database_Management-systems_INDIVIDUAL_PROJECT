<?php
include '../includes/header.php';
include '../includes/db_connect.php';

echo "<h2>Lab Schedules</h2>";

$stmt = $pdo->query("SELECT LabSchedule.ScheduleID, Lab.LabName, LabSchedule.TimeSlot, LabSchedule.Date, LabSchedule.Status
    FROM LabSchedule
    JOIN Lab ON LabSchedule.LabID = Lab.LabID
    ORDER BY LabSchedule.Date ASC");

echo "<table border='1' cellpadding='8' style='width:100%;border-collapse:collapse;'>";
echo "<tr><th>Schedule ID</th><th>Lab Name</th><th>Time Slot</th><th>Date</th><th>Status</th></tr>";
while ($row = $stmt->fetch()) {
    echo "<tr>";
    echo "<td>{$row['ScheduleID']}</td>";
    echo "<td>{$row['LabName']}</td>";
    echo "<td>{$row['TimeSlot']}</td>";
    echo "<td>{$row['Date']}</td>";
    echo "<td>{$row['Status']}</td>";
    echo "</tr>";
}
echo "</table>";

include '../includes/footer.php';
?>
