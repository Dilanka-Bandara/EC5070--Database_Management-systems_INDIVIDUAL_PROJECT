<?php
include '../includes/header.php';
include '../includes/db_connect.php';

echo "<h2>Notifications</h2>";

$stmt = $pdo->query("SELECT * FROM Notification ORDER BY Timestamp DESC");

echo "<ul>";
while ($row = $stmt->fetch()) {
    echo "<li><strong>{$row['Type']}</strong> ({$row['Timestamp']}): {$row['Message']}</li>";
}
echo "</ul>";

include '../includes/footer.php';
?>
