<?php
session_start();
require_once 'config/database.php';

$error = '';

if ($_POST) {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? '';
    
    if ($username && $password && $role) {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? AND role = ?");
        $stmt->execute([$username, $role]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password_hash'])) {
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['ref_id'] = $user['ref_id'];
            
            if ($user['role'] == 'student') {
                header("Location: pages/student_dashboard.php");
            } elseif ($user['role'] == 'instructor') {
                header("Location: pages/instructor_dashboard.php");
            } elseif ($user['role'] == 'coordinator') {
                header("Location: pages/coordinator_dashboard.php");
            }
            exit;
        } else {
            $error = "Invalid credentials. Please check your username, password, and role.";
        }
    } else {
        $error = "Please fill in all required fields.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EduPortal - Login</title>
    <link rel="stylesheet" href="assets/css/login.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <div class="logo">
                    <i class="fas fa-graduation-cap"></i>
                    <h1>EduPortal</h1>
                </div>
                <p class="subtitle">Welcome back! Please sign in to your account</p>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <span><?php echo htmlspecialchars($error); ?></span>
                </div>
            <?php endif; ?>

            <form class="login-form" method="POST" action="">
                <div class="form-group">
                    <label for="role" class="form-label">
                        <i class="fas fa-user-tag"></i>
                        Role
                    </label>
                    <select id="role" name="role" class="form-select" required>
                        <option value="">Select your role</option>
                        <option value="student" <?php echo (isset($_POST['role']) && $_POST['role'] == 'student') ? 'selected' : ''; ?>>Student</option>
                        <option value="instructor" <?php echo (isset($_POST['role']) && $_POST['role'] == 'instructor') ? 'selected' : ''; ?>>Instructor</option>
                        <option value="coordinator" <?php echo (isset($_POST['role']) && $_POST['role'] == 'coordinator') ? 'selected' : ''; ?>>Coordinator</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="username" class="form-label">
                        <i class="fas fa-user"></i>
                        Username
                    </label>
                    <input 
                        type="text" 
                        id="username" 
                        name="username" 
                        class="form-input" 
                        placeholder="Enter your username"
                        value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>"
                        required
                    >
                </div>

                <div class="form-group">
                    <label for="password" class="form-label">
                        <i class="fas fa-lock"></i>
                        Password
                    </label>
                    <div class="password-input-wrapper">
                        <input 
                            type="password" 
                            id="password" 
                            name="password" 
                            class="form-input" 
                            placeholder="Enter your password"
                            required
                        >
                        <button type="button" class="password-toggle" onclick="togglePassword()">
                            <i class="fas fa-eye" id="password-eye"></i>
                        </button>
                    </div>
                </div>

                <div class="form-options">
                    <label class="checkbox-wrapper">
                        <input type="checkbox" name="remember_me">
                        <span class="checkmark"></span>
                        Remember me
                    </label>
                    <a href="forgot-password.php" class="forgot-link">Forgot password?</a>
                </div>

                <button type="submit" class="login-btn">
                    <i class="fas fa-sign-in-alt"></i>
                    Sign In
                </button>
            </form>

            <div class="login-footer">
                <p>Don't have an account? <a href="register.php">Sign up here</a></p>
            </div>
        </div>

        <div class="background-decoration">
            <div class="shape shape-1"></div>
            <div class="shape shape-2"></div>
            <div class="shape shape-3"></div>
        </div>
    </div>

    <script src="assets/js/login.js"></script>
</body>
</html>
