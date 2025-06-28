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

$message = '';

// Helper function to safely get array values
function safeGet($array, $key, $default = '') {
    return isset($array[$key]) ? $array[$key] : $default;
}

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_profile'])) {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $course = trim($_POST['course']);

    try {
        // Update student details (only update fields that exist)
        $updateStudent = $pdo->prepare("UPDATE Student SET Name = ?, Email = ? WHERE StudentID = ?");
        $updateStudent->execute([$name, $email, $studentID]);

        $message = "<div class='alert alert-success'><i class='fas fa-check-circle me-2'></i>Profile updated successfully!</div>";
        
        // Refresh student data
        $stmt->execute([$studentID]);
        $student = $stmt->fetch();
    } catch (PDOException $e) {
        $message = "<div class='alert alert-danger'><i class='fas fa-exclamation-triangle me-2'></i>Error updating profile: " . htmlspecialchars($e->getMessage()) . "</div>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Profile - EduPortal</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary fixed-top">
        <div class="container-fluid">
            <a class="navbar-brand d-flex align-items-center" href="../index.php">
                <i class="fas fa-graduation-cap me-2"></i>
                <span class="fw-bold">EduPortal</span>
            </a>
            
            <div class="navbar-nav ms-auto">
                <a href="student_dashboard.php" class="nav-link text-white me-3">
                    <i class="fas fa-arrow-left me-1"></i>Back to Dashboard
                </a>
                <a href="logout.php" class="nav-link text-danger">
                    <i class="fas fa-sign-out-alt me-1"></i>Logout
                </a>
            </div>
        </div>
    </nav>
    
    <!-- Main Content -->
    <main class="main-content">
        <div class="container-fluid">
            <div class="page-header fade-in">
                <h1 class="page-title">
                    <i class="fas fa-user me-3"></i>
                    Student Profile
                </h1>
                <p class="page-subtitle">Manage your personal information</p>
            </div>

            <?= $message ?>

            <div class="row">
                <div class="col-lg-8">
                    <!-- Profile Information -->
                    <div class="card fade-in">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-user-edit me-2"></i>
                                Personal Information
                            </h5>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Full Name</label>
                                        <input type="text" name="name" class="form-control" 
                                               value="<?= htmlspecialchars(safeGet($student, 'Name')) ?>" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Email Address</label>
                                        <input type="email" name="email" class="form-control" 
                                               value="<?= htmlspecialchars(safeGet($student, 'Email')) ?>" required>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Phone Number</label>
                                        <input type="text" name="phone" class="form-control" 
                                               value="<?= htmlspecialchars(safeGet($student, 'Phone')) ?>"
                                               placeholder="Enter phone number">
                                        <small class="text-muted">Optional field</small>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Course</label>
                                        <input type="text" name="course" class="form-control" 
                                               value="<?= htmlspecialchars(safeGet($student, 'Course')) ?>"
                                               placeholder="Enter course name">
                                        <small class="text-muted">Optional field</small>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Student ID</label>
                                        <input type="text" class="form-control" 
                                               value="<?= htmlspecialchars(safeGet($student, 'StudentID')) ?>" readonly>
                                        <small class="text-muted">System generated ID</small>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">User ID</label>
                                        <input type="text" class="form-control" 
                                               value="<?= htmlspecialchars(safeGet($student, 'UserID')) ?>" readonly>
                                        <small class="text-muted">System generated ID</small>
                                    </div>
                                </div>
                                <div class="d-flex gap-2">
                                    <button type="submit" name="update_profile" class="btn btn-primary">
                                        <i class="fas fa-save me-2"></i>Update Profile
                                    </button>
                                    <a href="student_dashboard.php" class="btn btn-secondary">
                                        <i class="fas fa-times me-2"></i>Cancel
                                    </a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <!-- Profile Summary -->
                    <div class="card fade-in">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-info-circle me-2"></i>
                                Profile Summary
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="text-center mb-4">
                                <div class="profile-avatar bg-success text-white rounded-circle d-inline-flex align-items-center justify-content-center" 
                                     style="width: 80px; height: 80px; font-size: 2rem;">
                                    <?= strtoupper(substr(safeGet($student, 'Name', 'S'), 0, 1)) ?>
                                </div>
                                <h5 class="mt-3 mb-1"><?= htmlspecialchars(safeGet($student, 'Name', 'Unknown Student')) ?></h5>
                                <p class="text-muted"><?= htmlspecialchars(safeGet($student, 'Course', 'Course not specified')) ?></p>
                            </div>
                            
                            <div class="profile-info">
                                <div class="d-flex align-items-center mb-3">
                                    <i class="fas fa-envelope text-success me-3"></i>
                                    <div>
                                        <small class="text-muted d-block">Email</small>
                                        <span><?= htmlspecialchars(safeGet($student, 'Email', 'Not provided')) ?></span>
                                    </div>
                                </div>
                                <div class="d-flex align-items-center mb-3">
                                    <i class="fas fa-phone text-success me-3"></i>
                                    <div>
                                        <small class="text-muted d-block">Phone</small>
                                        <span><?= htmlspecialchars(safeGet($student, 'Phone', 'Not provided')) ?></span>
                                    </div>
                                </div>
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-id-badge text-success me-3"></i>
                                    <div>
                                        <small class="text-muted d-block">Student ID</small>
                                        <span><?= htmlspecialchars(safeGet($student, 'StudentID', 'Not assigned')) ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Actions -->
                    <div class="card fade-in mt-4">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-bolt me-2"></i>
                                Quick Actions
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="d-grid gap-2">
                                <a href="student_settings.php" class="btn btn-outline-primary">
                                    <i class="fas fa-cog me-2"></i>Account Settings
                                </a>
                                <a href="student_dashboard.php" class="btn btn-outline-success">
                                    <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                                </a>
                                <a href="reschedule_request.php" class="btn btn-outline-info">
                                    <i class="fas fa-calendar-plus me-2"></i>New Request
                                </a>
                                <a href="logout.php" class="btn btn-outline-danger">
                                    <i class="fas fa-sign-out-alt me-2"></i>Logout
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="bg-dark text-light py-4 mt-5">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <h5><i class="fas fa-graduation-cap me-2"></i>EduPortal</h5>
                    <p class="mb-0">Professional Lab Rescheduling Management System</p>
                </div>
                <div class="col-md-6 text-end">
                    <p class="mb-0">&copy; <?php echo date('Y'); ?> EduPortal. All rights reserved.</p>
                    <small class="text-muted">Version 2.0</small>
                </div>
            </div>
        </div>
    </footer>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
    // Auto-hide alerts
    document.addEventListener('DOMContentLoaded', function() {
        const alerts = document.querySelectorAll('.alert');
        alerts.forEach(alert => {
            setTimeout(() => {
                alert.style.opacity = '0';
                alert.style.transform = 'translateY(-10px)';
                setTimeout(() => {
                    if (alert.parentNode) {
                        alert.remove();
                    }
                }, 300);
            }, 5000);
        });
    });
    </script>
</body>
</html>
