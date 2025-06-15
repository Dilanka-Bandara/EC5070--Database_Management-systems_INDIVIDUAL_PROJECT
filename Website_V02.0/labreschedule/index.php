<?php
session_start();
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    require 'includes/db_connect.php'; // Adjust path if needed

    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $role = $_POST['role'];

    // Fetch user by username and role
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? AND role = ?");
    $stmt->execute([$username, $role]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password_hash'])) {
        // Login success: set session and redirect
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['ref_id'] = $user['ref_id'];
        // Redirect to dashboard based on role
        if ($user['role'] == 'student') {
            header("Location: pages/student_dashboard.php");
        } elseif ($user['role'] == 'instructor') {
            header("Location: pages/instructor_dashboard.php");
        } elseif ($user['role'] == 'coordinator') {
            header("Location: pages/coordinator_dashboard.php");
        }
        exit;
    } else {
        $error = "<div class='alert alert-danger'>Invalid credentials or role.</div>";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Lab Reschedule Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #fceed1; }
        .login-box { margin: 5% auto; max-width: 400px; background: #fff; border-radius: 12px; box-shadow: 0 4px 24px rgba(0,0,0,0.08); padding: 2em; }
        .logo { color: #7d3cff; font-weight: bold; font-size: 2em; text-align: center; }
    </style>
</head>
<body>
<div class="login-box">
    <div class="logo mb-3">LabReschedule</div>
    <?= $error ?>
    <form method="post" autocomplete="off">
        <div class="mb-3">
            <label class="form-label">User ID</label>
            <input type="text" name="username" class="form-control" required placeholder="Enter your ID">
        </div>
        <div class="mb-3">
            <label class="form-label">Password</label>
            <input type="password" name="password" class="form-control" required placeholder="Password">
        </div>
        <div class="mb-3">
            <label class="form-label">Role</label>
            <select name="role" class="form-select" required>
                <option value="student">Student</option>
                <option value="instructor">Lab Instructor</option>
                <option value="coordinator">Subject Coordinator</option>
            </select>
        </div>
        <button type="submit" class="btn btn-primary w-100" style="background:#7d3cff;border:0;">Login</button>
    </form>
</div>
</body>
</html>
