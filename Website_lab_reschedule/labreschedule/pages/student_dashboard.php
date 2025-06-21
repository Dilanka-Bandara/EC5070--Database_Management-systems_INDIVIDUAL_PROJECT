<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: ../index.php");
    exit;
}
require '../includes/db_connect.php';

// Fetch student details
$studentID = $_SESSION['ref_id'];
$stmt = $pdo->prepare("SELECT * FROM Student WHERE StudentID = ?");
$stmt->execute([$studentID]);
$student = $stmt->fetch();

if (!$student) {
    die("Error: Could not find student details for ID: " . htmlspecialchars($studentID));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>LabReschedule - Student Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: linear-gradient(135deg, #fceed1 0%, #ffe6f2 100%); font-family: 'Poppins', sans-serif; padding-top: 30px; padding-bottom: 30px;}
        .container { max-width: 1200px; }
        .dashboard-title {
            color: #4B0082; font-weight: 700; margin-bottom: 25px; text-align: center;
            background: rgba(0, 204, 153, 0.1); padding: 10px; border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.05);
        }
        .welcome-card {
            background: #fff; border-radius: 15px; box-shadow: 0 8px 16px rgba(0,0,0,0.1);
            border: none; overflow: hidden; position: relative; margin-bottom: 30px;
        }
        .welcome-card::before {
            content: ''; position: absolute; top: 0; left: 0; width: 100%; height: 4px;
            background: linear-gradient(90deg, #00CC99, #FF355E);
        }
        .welcome-card h3 { color: #4B0082; font-weight: 600; }
        .section-title {
            color: #4B0082; font-weight: 500; margin-bottom: 15px;
            border-left: 4px solid #00CC99; padding-left: 10px;
        }
        .table { background: #fff; border-radius: 10px; overflow: hidden; box-shadow: 0 4px 8px rgba(0,0,0,0.05);}
        .table thead { background: #4B0082; color: #fff; }
        .table tbody tr:hover { background: rgba(0,204,153,0.07);}
        .badge.bg-primary { background: #4B0082 !important; }
        .badge.bg-success { background: #00CC99 !important; }
        .badge.bg-danger { background: #FF355E !important; }
        .btn-primary { background: #FF355E; border: none; color: #fff; font-weight: 600; padding: 10px 20px; border-radius: 30px; }
    </style>
</head>
<body>
<div class="container">

<!-- LOGOUT BUTTON -->
<div class="d-flex justify-content-end mb-3">
    <a href="logout.php" class="btn btn-outline-danger">Logout</a>
</div>

<h1 class="dashboard-title">Student Dashboard</h1>

<?php if (isset($_GET['msg']) && $_GET['msg'] == 'success'): ?>
    <div class="alert alert-success">Request submitted successfully!</div>
<?php endif; ?>

<!-- Student Info -->
<div class="welcome-card mb-4">
    <div class="card-body">
        <h3 class="card-title">Welcome, <?= htmlspecialchars($student['Name']) ?></h3>
        <p class="mb-1"><strong>Email:</strong> <?= htmlspecialchars($student['Email']) ?></p>
        <p class="mb-0"><strong>Phone:</strong> <?= htmlspecialchars($student['Phone']) ?></p>
    </div>
</div>

<!-- Your Notifications Table - MOVED TO TOP -->
<h4 class="section-title">Your Notifications</h4>
<div class="table-responsive mb-4">
    <table class="table table-bordered">
        <thead>
            <tr>
                <th>Message</th>
                <th>Type</th>
                <th>Timestamp</th>
            </tr>
        </thead>
        <tbody>
        <?php
        $notif = $pdo->prepare("SELECT * FROM Notification WHERE StudentID = ? ORDER BY Timestamp DESC");
        $notif->execute([$studentID]);
        if ($notif->rowCount() > 0) {
            foreach ($notif as $row) {
                echo "<tr>
                    <td>".htmlspecialchars($row['Message'])."</td>
                    <td>".htmlspecialchars($row['Type'])."</td>
                    <td>".htmlspecialchars($row['Timestamp'])."</td>
                </tr>";
            }
        } else {
            echo "<tr><td colspan='3' class='text-center'>No notifications found.</td></tr>";
        }
        ?>
        </tbody>
    </table>
</div>

<!-- My Reschedule Requests -->
<h4 class="section-title">My Reschedule Requests</h4>
<div class="table-responsive mb-4">
    <table class="table table-bordered">
        <thead>
            <tr>
                <th>Request ID</th>
                <th>Schedule ID</th>
                <th>Date Requested</th>
                <th>Reason</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
        <?php
        $myRequests = $pdo->prepare("SELECT * FROM RescheduleRequest WHERE StudentID = ? ORDER BY RequestDate DESC");
        $myRequests->execute([$studentID]);
        if ($myRequests->rowCount() > 0) {
            foreach ($myRequests as $req) {
                $statusClass = $req['Status'] == 'Approved' ? 'bg-success' : ($req['Status'] == 'Rejected' ? 'bg-danger' : 'bg-warning text-dark');
                echo "<tr>
                    <td>".htmlspecialchars($req['RequestID'])."</td>
                    <td>".htmlspecialchars($req['ScheduleID'])."</td>
                    <td>".htmlspecialchars($req['RequestDate'])."</td>
                    <td>".htmlspecialchars($req['Reason'])."</td>
                    <td><span class='badge $statusClass'>".htmlspecialchars($req['Status'])."</span></td>
                </tr>";
            }
        } else {
            echo "<tr><td colspan='5' class='text-center'>No reschedule requests found.</td></tr>";
        }
        ?>
        </tbody>
    </table>
</div>

<!-- Lab Schedules Table -->
<h4 class="section-title">All Available Lab Schedules</h4>
<div class="table-responsive mb-4">
    <table class="table table-striped table-hover">
        <thead>
            <tr>
                <th>Schedule ID</th>
                <th>Lab Name</th>
                <th>Date</th>
                <th>Time Slot</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
        <?php
        $schedules = $pdo->query("
            SELECT ls.ScheduleID, l.LabName, ls.Date, ls.TimeSlot, ls.Status 
            FROM LabSchedule ls 
            JOIN Lab l ON ls.LabID = l.LabID 
            WHERE ls.Status = 'Scheduled' 
            ORDER BY ls.Date ASC
        ");
        if ($schedules->rowCount() > 0) {
            foreach ($schedules as $row) {
                echo "<tr>
                    <td>".htmlspecialchars($row['ScheduleID'])."</td>
                    <td>".htmlspecialchars($row['LabName'])."</td>
                    <td>".htmlspecialchars($row['Date'])."</td>
                    <td>".htmlspecialchars($row['TimeSlot'])."</td>
                    <td><span class='badge bg-primary'>".htmlspecialchars($row['Status'])."</span></td>
                </tr>";
            }
        } else {
            echo "<tr><td colspan='5' class='text-center'>No lab schedules found.</td></tr>";
        }
        ?>
        </tbody>
    </table>
</div>

<a href="reschedule_request.php" class="btn btn-primary">Request Lab Reschedule</a>

<!-- Your Attendance History -->
<h4 class="section-title mt-4">Your Attendance History</h4>
<div class="table-responsive">
    <table class="table table-bordered">
        <thead>
            <tr>
                <th>Date</th>
                <th>Schedule ID</th>
                <th>Present</th>
            </tr>
        </thead>
        <tbody>
        <?php
        $att = $pdo->prepare("SELECT * FROM Attendance WHERE StudentID = ? ORDER BY Date DESC");
        $att->execute([$studentID]);
        if ($att->rowCount() > 0) {
            foreach ($att as $row) {
                echo "<tr>
                    <td>".htmlspecialchars($row['Date'])."</td>
                    <td>".htmlspecialchars($row['ScheduleID'])."</td>
                    <td>".($row['Present'] ? '<span class="badge bg-success">Yes</span>' : '<span class="badge bg-danger">No</span>')."</td>
                </tr>";
            }
        } else {
            echo "<tr><td colspan='3' class='text-center'>No attendance records found.</td></tr>";
        }
        ?>
        </tbody>
    </table>
</div>

</div> <!-- end container -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
