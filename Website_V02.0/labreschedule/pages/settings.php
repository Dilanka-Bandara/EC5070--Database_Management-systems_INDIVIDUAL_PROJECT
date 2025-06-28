<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'coordinator') {
    header("Location: ../index.php");
    exit;
}

require '../includes/db_connect.php';

// Fetch coordinator details
$coordinatorID = $_SESSION['ref_id'];
$stmt = $pdo->prepare("SELECT * FROM SubjectCoordinator WHERE CoordinatorID = ?");
$stmt->execute([$coordinatorID]);
$coordinator = $stmt->fetch();

if (!$coordinator) {
    die("Error: Could not find coordinator details for ID: " . htmlspecialchars($coordinatorID));
}

$message = '';

// Handle password change (simplified for demonstration)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['change_password'])) {
    $currentPassword = $_POST['current_password'];
    $newPassword = $_POST['new_password'];
    $confirmPassword = $_POST['confirm_password'];

    if ($newPassword === $confirmPassword) {
        if (strlen($newPassword) >= 6) {
            // In a real application, you would verify the current password and hash the new one
            $message = "<div class='alert alert-success'><i class='fas fa-check-circle me-2'></i>Password changed successfully!</div>";
        } else {
            $message = "<div class='alert alert-danger'><i class='fas fa-exclamation-triangle me-2'></i>Password must be at least 6 characters long.</div>";
        }
    } else {
        $message = "<div class='alert alert-danger'><i class='fas fa-exclamation-triangle me-2'></i>New passwords do not match.</div>";
    }
}

// Handle notification preferences
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_preferences'])) {
    $emailNotifications = isset($_POST['email_notifications']) ? 1 : 0;
    $smsNotifications = isset($_POST['sms_notifications']) ? 1 : 0;
    
    $message = "<div class='alert alert-success'><i class='fas fa-check-circle me-2'></i>Preferences updated successfully!</div>";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - EduPortal</title>
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
                <a href="coordinator_dashboard.php" class="nav-link text-white me-3">
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
                    <i class="fas fa-cog me-3"></i>
                    Settings
                </h1>
                <p class="page-subtitle">Manage your account settings and preferences</p>
            </div>

            <?= $message ?>

            <div class="row">
                <div class="col-lg-6">
                    <!-- Change Password -->
                    <div class="card mb-4 fade-in">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-lock me-2"></i>
                                Change Password
                            </h5>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <div class="mb-3">
                                    <label class="form-label">Current Password</label>
                                    <input type="password" name="current_password" class="form-control" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">New Password</label>
                                    <input type="password" name="new_password" class="form-control" required minlength="6">
                                    <small class="text-muted">Password must be at least 6 characters long</small>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Confirm New Password</label>
                                    <input type="password" name="confirm_password" class="form-control" required minlength="6">
                                </div>
                                <button type="submit" name="change_password" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i>Change Password
                                </button>
                            </form>
                        </div>
                    </div>

                    <!-- Notification Preferences -->
                    <div class="card fade-in">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-bell me-2"></i>
                                Notification Preferences
                            </h5>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <div class="mb-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="email_notifications" id="emailNotif" checked>
                                        <label class="form-check-label" for="emailNotif">
                                            <i class="fas fa-envelope me-2"></i>Email Notifications
                                        </label>
                                        <small class="d-block text-muted">Receive notifications via email</small>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="sms_notifications" id="smsNotif">
                                        <label class="form-check-label" for="smsNotif">
                                            <i class="fas fa-sms me-2"></i>SMS Notifications
                                        </label>
                                        <small class="d-block text-muted">Receive notifications via SMS</small>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="push_notifications" id="pushNotif" checked>
                                        <label class="form-check-label" for="pushNotif">
                                            <i class="fas fa-mobile-alt me-2"></i>Push Notifications
                                        </label>
                                        <small class="d-block text-muted">Receive push notifications on your device</small>
                                    </div>
                                </div>
                                <button type="submit" name="update_preferences" class="btn btn-success">
                                    <i class="fas fa-save me-2"></i>Update Preferences
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-lg-6">
                    <!-- Account Information -->
                    <div class="card mb-4 fade-in">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-user-shield me-2"></i>
                                Account Information
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-sm-4"><strong>Name:</strong></div>
                                <div class="col-sm-8"><?= htmlspecialchars($coordinator['Name']) ?></div>
                            </div>
                            <hr>
                            <div class="row">
                                <div class="col-sm-4"><strong>Email:</strong></div>
                                <div class="col-sm-8"><?= htmlspecialchars($coordinator['Email']) ?></div>
                            </div>
                            <hr>
                            <div class="row">
                                <div class="col-sm-4"><strong>Department:</strong></div>
                                <div class="col-sm-8"><?= htmlspecialchars($coordinator['Department']) ?></div>
                            </div>
                            <hr>
                            <div class="row">
                                <div class="col-sm-4"><strong>Coordinator ID:</strong></div>
                                <div class="col-sm-8"><?= htmlspecialchars($coordinator['CoordinatorID']) ?></div>
                            </div>
                            <hr>
                            <div class="row">
                                <div class="col-sm-4"><strong>Phone:</strong></div>
                                <div class="col-sm-8"><?= htmlspecialchars($coordinator['Phone'] ?: 'Not provided') ?></div>
                            </div>
                            
                            <div class="mt-3">
                                <a href="profile.php" class="btn btn-outline-primary">
                                    <i class="fas fa-edit me-2"></i>Edit Profile
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- System Information -->
                    <div class="card fade-in">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-info-circle me-2"></i>
                                System Information
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row mb-2">
                                <div class="col-sm-4"><strong>Last Login:</strong></div>
                                <div class="col-sm-8"><?= date('M d, Y g:i A') ?></div>
                            </div>
                            <div class="row mb-2">
                                <div class="col-sm-4"><strong>Account Status:</strong></div>
                                <div class="col-sm-8">
                                    <span class="badge bg-success">Active</span>
                                </div>
                            </div>
                            <div class="row mb-2">
                                <div class="col-sm-4"><strong>Role:</strong></div>
                                <div class="col-sm-8">
                                    <span class="badge bg-primary">Coordinator</span>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-sm-4"><strong>System Version:</strong></div>
                                <div class="col-sm-8">EduPortal v2.0</div>
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

    // Password confirmation validation
    document.querySelector('form').addEventListener('submit', function(e) {
        if (e.submitter && e.submitter.name === 'change_password') {
            const newPassword = document.querySelector('input[name="new_password"]').value;
            const confirmPassword = document.querySelector('input[name="confirm_password"]').value;
            
            if (newPassword !== confirmPassword) {
                e.preventDefault();
                alert('New passwords do not match!');
                return false;
            }
        }
    });
    </script>
</body>
</html>
