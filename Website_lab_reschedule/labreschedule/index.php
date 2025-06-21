<?php
session_start();
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    require 'includes/db_connect.php';

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
        body {
            background: linear-gradient(135deg, #fceed1 0%, #ffe6f2 100%);
            font-family: 'Poppins', sans-serif;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-box {
            max-width: 450px;
            background: #fff;
            border-radius: 15px;
            box-shadow: 0 8px 24px rgba(0,0,0,0.1);
            padding: 2.5em;
            position: relative;
        }
        .login-box::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 5px;
            background: linear-gradient(90deg, #00CC99, #FF355E);
        }
        .logo {
            color: #4B0082;
            font-weight: bold;
            font-size: 2.2em;
            text-align: center;
            margin-bottom: 1.5rem;
        }
        .form-control, .form-select {
            border-radius: 8px;
            padding: 12px;
            font-size: 16px;
            transition: all 0.3s ease;
        }
        .form-control:focus, .form-select:focus {
            border-color: #00CC99;
            box-shadow: 0 0 0 0.2rem rgba(0, 204, 153, 0.25);
        }
        .btn-primary {
            background: #FF355E;
            border: none;
            font-weight: 600;
            padding: 12px;
            border-radius: 30px;
            transition: all 0.3s ease;
        }
        .btn-primary:hover {
            background: #e62e4d;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(255, 53, 94, 0.3);
        }
        .alert-danger {
            background: rgba(255, 53, 94, 0.1);
            color: #FF355E;
            border: none;
            border-radius: 8px;
            margin-bottom: 1.5rem;
        }
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
        <button type="submit" class="btn btn-primary w-100">Login</button>
    </form>
</div>
</body>
</html>
