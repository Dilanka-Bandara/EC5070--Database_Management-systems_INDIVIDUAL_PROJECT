<?php
include '../includes/header.php';
include '../includes/db_connect.php';

echo "<h2>Attendance Records</h2>";

$stmt = $pdo->query("SELECT Attendance.AttendanceID, Student.Name AS StudentName, LabSchedule.TimeSlot, Attendance.Date, Attendance.Present
    FROM Attendance
    JOIN Student ON Attendance.StudentID = Student.StudentID
    JOIN LabSchedule ON Attendance.ScheduleID = LabSchedule.ScheduleID
    ORDER BY Attendance.Date DESC");

echo "<table border='1' cellpadding='8' style='width:100%;border-collapse:collapse;'>";
echo "<tr><th>Attendance ID</th><th>Student</th><th>Time Slot</th><th>Date</th><th>Present</th></tr>";
while ($row = $stmt->fetch()) {
    $present = $row['Present'] ? 'Yes' : 'No';
    echo "<tr>
            <td>{$row['AttendanceID']}</td>
            <td>{$row['StudentName']}</td>
            <td>{$row['TimeSlot']}</td>
            <td>{$row['Date']}</td>
            <td>{$present}</td>
          </tr>";
}
echo "</table>";

include '../includes/footer.php';
?>
